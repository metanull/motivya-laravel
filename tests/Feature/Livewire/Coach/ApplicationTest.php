<?php

declare(strict_types=1);

use App\Enums\CoachProfileStatus;
use App\Events\NewCoachApplication;
use App\Livewire\Coach\Application;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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
            ->set('form.postal_code', '1000')
            ->set('form.country', 'BE')
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

    it('validates step 2 requires postal code and enterprise number', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', ['yoga'])
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('form.postal_code', '')
            ->set('form.enterprise_number', '')
            ->call('nextStep')
            ->assertHasErrors(['form.postal_code', 'form.enterprise_number'])
            ->assertSet('step', 2);
    });

    it('validates enterprise number format', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', ['yoga'])
            ->call('nextStep')
            ->set('form.postal_code', '1000')
            ->set('form.enterprise_number', '123456789')
            ->call('nextStep')
            ->assertHasErrors(['form.enterprise_number'])
            ->assertSet('step', 2);
    });

    it('submits application and creates coach profile', function () {
        Event::fake([NewCoachApplication::class]);

        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', ['fitness', 'yoga'])
            ->set('form.bio', 'Experienced fitness coach')
            ->set('form.experience_level', 'advanced')
            ->call('nextStep')
            ->set('form.postal_code', '1000')
            ->set('form.country', 'BE')
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
        expect($profile->enterprise_number)->toBe('0123.456.789');

        Event::assertDispatched(NewCoachApplication::class, function ($event) use ($profile) {
            return $event->coachProfileId === $profile->id;
        });
    });

    it('requires terms acceptance to submit', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', ['fitness'])
            ->call('nextStep')
            ->set('form.postal_code', '1000')
            ->set('form.country', 'BE')
            ->set('form.enterprise_number', '0123.456.789')
            ->call('nextStep')
            ->set('form.terms_accepted', false)
            ->call('submit')
            ->assertHasErrors(['form.terms_accepted']);
    });

});
