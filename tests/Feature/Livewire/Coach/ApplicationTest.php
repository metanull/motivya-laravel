<?php

declare(strict_types=1);

use App\Enums\CoachProfileStatus;
use App\Events\NewCoachApplication;
use App\Livewire\Coach\Application;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Helper: return form field values representing a pre-validated address.
 * Used in tests that need submit() to succeed without calling the geocoding API.
 *
 * @return array<string, mixed>
 */
function coachApplicationAddressFields(): array
{
    return [
        'form.addressQuery' => 'Grand-Place, 1000 Bruxelles',
        'form.addressValidated' => true,
        'form.formattedAddress' => 'Grand-Place, 1000 Bruxelles, Belgium',
        'form.postalCode' => '1000',
        'form.locality' => 'Bruxelles',
        'form.country' => 'BE',
        'form.latitude' => 50.8467,
        'form.longitude' => 4.3525,
        'form.geocodingProvider' => 'google',
    ];
}

describe('Coach Application Form', function () {

    it('renders the application page for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('coach.apply'))
            ->assertOk();
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.apply'))
            ->assertForbidden();
    });

    it('denies access to admins', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('coach.apply'))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $this->get(route('coach.apply'))
            ->assertRedirect(route('login'));
    });

    it('denies access to athletes who already have a coach profile', function () {
        $athlete = User::factory()->athlete()->create();
        CoachProfile::factory()->for($athlete)->create();

        $this->actingAs($athlete)
            ->get(route('coach.apply'))
            ->assertForbidden();
    });

    it('navigates through steps', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->assertSet('step', 1)
            ->set('form.specialties', ['fitness'])
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('form.addressQuery', 'Grand-Place, 1000 Bruxelles')
            ->set('form.enterprise_number', '0123.456.789')
            ->call('nextStep')
            ->assertSet('step', 3)
            ->call('previousStep')
            ->assertSet('step', 2);
    });

    it('validates step 1 requires specialties', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', [])
            ->call('nextStep')
            ->assertHasErrors(['form.specialties'])
            ->assertSet('step', 1);
    });

    it('validates step 2 requires address query and enterprise number', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', ['yoga'])
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('form.addressQuery', '')
            ->set('form.enterprise_number', '')
            ->call('nextStep')
            ->assertHasErrors(['form.addressQuery', 'form.enterprise_number'])
            ->assertSet('step', 2);
    });

    it('validates enterprise number format', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', ['yoga'])
            ->call('nextStep')
            ->set('form.addressQuery', 'Grand-Place, 1000 Bruxelles')
            ->set('form.enterprise_number', '123456789')
            ->call('nextStep')
            ->assertHasErrors(['form.enterprise_number'])
            ->assertSet('step', 2);
    });

    it('blocks submission when address is not validated', function () {
        $athlete = User::factory()->athlete()->create();

        // Fake all outbound HTTP so the geocoding provider returns no results.
        // The Google API returns ZERO_RESULTS status → no results → addressValidated stays false.
        Http::fake([
            '*' => Http::response(['status' => 'ZERO_RESULTS', 'results' => []], 200),
        ]);

        // Config must have a key so the service doesn't fail-fast.
        config(['maps.geocoding_provider' => 'google', 'maps.google_api_key' => 'test-key']);

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', ['fitness'])
            ->set('form.bio', 'A great coach')
            ->set('form.experience_level', 'advanced')
            ->call('nextStep')
            ->set('form.addressQuery', 'Unknown Place XYZ 00000')
            ->set('form.addressValidated', false)
            ->set('form.enterprise_number', '0123.456.789')
            ->call('nextStep')
            ->set('form.terms_accepted', true)
            ->call('submit')
            ->assertHasErrors(['form.addressQuery']);
    });

    it('submits application and creates coach profile', function () {
        Event::fake([NewCoachApplication::class]);

        $athlete = User::factory()->athlete()->create();

        $livewire = Livewire::actingAs($athlete)->test(Application::class);

        $livewire
            ->set('form.specialties', ['fitness', 'yoga'])
            ->set('form.bio', 'Experienced fitness coach')
            ->set('form.experience_level', 'advanced')
            ->call('nextStep');

        foreach (coachApplicationAddressFields() as $key => $value) {
            $livewire->set($key, $value);
        }

        $livewire
            ->set('form.enterprise_number', '0123.456.789')
            ->call('nextStep')
            ->set('form.terms_accepted', true)
            ->call('submit')
            ->assertRedirect(route('home'));

        $profile = CoachProfile::where('user_id', $athlete->id)->first();
        expect($profile)->not->toBeNull();
        expect($profile->status)->toBe(CoachProfileStatus::Pending);
        expect($profile->specialties)->toBe(['fitness', 'yoga']);
        expect($profile->bio)->toBe('Experienced fitness coach');
        expect($profile->experience_level)->toBe('advanced');
        expect($profile->postal_code)->toBe('1000');
        expect($profile->country)->toBe('BE');
        expect($profile->formatted_address)->toBe('Grand-Place, 1000 Bruxelles, Belgium');
        expect($profile->enterprise_number)->toBe('0123.456.789');

        Event::assertDispatched(NewCoachApplication::class, function ($event) use ($profile) {
            return $event->coachProfileId === $profile->id;
        });
    });

    it('requires terms acceptance to submit', function () {
        $athlete = User::factory()->athlete()->create();

        $livewire = Livewire::actingAs($athlete)->test(Application::class);

        $livewire
            ->set('form.specialties', ['fitness'])
            ->call('nextStep');

        foreach (coachApplicationAddressFields() as $key => $value) {
            $livewire->set($key, $value);
        }

        $livewire
            ->set('form.enterprise_number', '0123.456.789')
            ->call('nextStep')
            ->set('form.terms_accepted', false)
            ->call('submit')
            ->assertHasErrors(['form.terms_accepted']);
    });

});
