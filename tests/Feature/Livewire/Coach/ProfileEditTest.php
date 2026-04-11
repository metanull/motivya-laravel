<?php

declare(strict_types=1);

use App\Livewire\Coach\ProfileEdit;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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

    it('prefills form from existing profile', function () {
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
            ->assertSet('form.postalCode', '1000')
            ->assertSet('form.specialties', ['yoga', 'fitness']);
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

        Livewire::actingAs($coach)
            ->test(ProfileEdit::class)
            ->set('form.bio', 'Updated bio.')
            ->set('form.experienceLevel', 'expert')
            ->set('form.postalCode', '1050')
            ->set('form.specialties', ['yoga', 'running'])
            ->set('form.enterpriseNumber', '0123.456.789')
            ->call('save')
            ->assertDispatched('notify');

        $coach->refresh();
        $profile = $coach->coachProfile;
        expect($profile->bio)->toBe('Updated bio.');
        expect($profile->experience_level)->toBe('expert');
        expect($profile->postal_code)->toBe('1050');
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
            ->set('form.postalCode', '')
            ->set('form.specialties', [])
            ->call('save')
            ->assertHasErrors(['form.bio', 'form.experienceLevel', 'form.postalCode', 'form.specialties']);
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

        Livewire::actingAs($coach)
            ->test(ProfileEdit::class)
            ->set('form.bio', 'New bio.')
            ->set('form.experienceLevel', 'intermediate')
            ->set('form.postalCode', '1000')
            ->set('form.specialties', ['fitness'])
            ->call('save')
            ->assertDispatched('notify');

        $coach->refresh();
        expect($coach->coachProfile)->not->toBeNull();
        expect($coach->coachProfile->bio)->toBe('New bio.');
    });
});
