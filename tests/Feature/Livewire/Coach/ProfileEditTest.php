<?php

declare(strict_types=1);

use App\Livewire\Coach\ProfileEdit;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Helper: return form field values representing a pre-validated address.
 * Used in tests that need save() to succeed without calling the geocoding API.
 *
 * @return array<string, mixed>
 */
function coachProfileAddressFields(): array
{
    return [
        'form.addressQuery' => 'Chaussée de Waterloo 1050, Ixelles, Bruxelles',
        'form.addressValidated' => true,
        'form.formattedAddress' => 'Chaussée de Waterloo 1050, 1050 Ixelles, Belgium',
        'form.postalCode' => '1050',
        'form.locality' => 'Ixelles',
        'form.country' => 'BE',
        'form.latitude' => 50.8127,
        'form.longitude' => 4.3700,
        'form.geocodingProvider' => 'google',
    ];
}

describe('coach profile edit', function () {
    it('renders for a coach', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        $this->actingAs($coach)
            ->get(route('coach.profile.edit'))
            ->assertOk();
    });

    it('does not render for an athlete', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('coach.profile.edit'))
            ->assertForbidden();
    });

    it('prefills form from existing profile — legacy postal_code only', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create([
            'user_id' => $coach->id,
            'bio' => 'My coaching bio.',
            'experience_level' => 'advanced',
            'postal_code' => '1000',
            'specialties' => ['yoga', 'fitness'],
        ]);

        Livewire::actingAs($coach)
            ->test(ProfileEdit::class)
            ->assertSet('form.bio', 'My coaching bio.')
            ->assertSet('form.experienceLevel', 'advanced')
            // postalCode normalized field populated from legacy postal_code column
            ->assertSet('form.postalCode', '1000')
            ->assertSet('form.specialties', ['yoga', 'fitness'])
            // Legacy profile has no formatted_address → addressValidated is false
            ->assertSet('form.addressValidated', false);
    });

    it('prefills form from a geocoded profile — addressValidated is true', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->withValidatedAddress()->create([
            'user_id' => $coach->id,
            'bio' => 'Geocoded bio.',
            'experience_level' => 'expert',
            'specialties' => ['running'],
        ]);

        Livewire::actingAs($coach)
            ->test(ProfileEdit::class)
            ->assertSet('form.bio', 'Geocoded bio.')
            ->assertSet('form.addressValidated', true)
            ->assertSet('form.addressQuery', 'Grand-Place, 1000 Bruxelles, Belgium');
    });

    it('saves profile updates', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create([
            'user_id' => $coach->id,
            'bio' => 'Old bio.',
            'experience_level' => 'beginner',
            'postal_code' => '1000',
            'specialties' => ['yoga'],
        ]);

        $livewire = Livewire::actingAs($coach)->test(ProfileEdit::class);

        $livewire
            ->set('form.bio', 'Updated bio.')
            ->set('form.experienceLevel', 'expert')
            ->set('form.specialties', ['yoga', 'running'])
            ->set('form.enterpriseNumber', '0123.456.789');

        foreach (coachProfileAddressFields() as $key => $value) {
            $livewire->set($key, $value);
        }

        $livewire->call('save')->assertDispatched('notify');

        $coach->refresh();
        $profile = $coach->coachProfile;
        expect($profile->bio)->toBe('Updated bio.');
        expect($profile->experience_level)->toBe('expert');
        expect($profile->postal_code)->toBe('1050');
        expect($profile->formatted_address)->toBe('Chaussée de Waterloo 1050, 1050 Ixelles, Belgium');
        expect($profile->specialties)->toBe(['yoga', 'running']);
        expect($profile->enterprise_number)->toBe('0123.456.789');
    });

    it('validates required fields', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        Livewire::actingAs($coach)
            ->test(ProfileEdit::class)
            ->set('form.bio', '')
            ->set('form.experienceLevel', '')
            ->set('form.addressQuery', '')
            ->set('form.addressValidated', false)
            ->set('form.specialties', [])
            ->call('save')
            ->assertHasErrors(['form.bio', 'form.experienceLevel', 'form.addressQuery', 'form.specialties']);
    });

    it('blocks save when address query is present but not validated', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->withValidatedAddress()->create(['user_id' => $coach->id]);

        // Fake all outbound HTTP so the geocoding provider returns no results.
        Http::fake([
            '*' => Http::response(['status' => 'ZERO_RESULTS', 'results' => []], 200),
        ]);
        // Config must have a key so the resolver picks Google provider.
        Config::set('maps.google.api_key', 'test-key');

        Livewire::actingAs($coach)
            ->test(ProfileEdit::class)
            ->set('form.addressQuery', 'Some Unknown Place XYZ 00000')
            ->set('form.addressValidated', false)
            ->call('save')
            ->assertHasErrors(['form.addressQuery']);
    });

    it('does not allow editing is_vat_subject', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->vatSubject()->create(['user_id' => $coach->id]);

        // isVatSubject is read-only — set from model, not editable
        Livewire::actingAs($coach)
            ->test(ProfileEdit::class)
            ->assertSet('isVatSubject', true);
    });

    it('creates profile if none exists', function () {
        $coach = User::factory()->coach()->create();

        $livewire = Livewire::actingAs($coach)->test(ProfileEdit::class);

        $livewire
            ->set('form.bio', 'New bio.')
            ->set('form.experienceLevel', 'intermediate')
            ->set('form.specialties', ['fitness']);

        foreach (coachProfileAddressFields() as $key => $value) {
            $livewire->set($key, $value);
        }

        $livewire->call('save')->assertDispatched('notify');

        $coach->refresh();
        expect($coach->coachProfile)->not->toBeNull();
        expect($coach->coachProfile->bio)->toBe('New bio.');
        expect($coach->coachProfile->postal_code)->toBe('1050');
    });
});
