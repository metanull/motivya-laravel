<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use App\Livewire\Session\Edit;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('session editing', function () {
    it('renders the edit form for the owning coach', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        $this->actingAs($coach)
            ->get(route('coach.sessions.edit', $session))
            ->assertOk();
    });

    it('pre-fills the form with existing session data', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
            'activity_type' => ActivityType::Yoga->value,
            'level' => SessionLevel::Beginner->value,
            'title' => 'My Session',
            'price_per_person' => 1500,
        ]);

        Livewire::actingAs($coach)
            ->test(Edit::class, ['sportSession' => $session])
            ->assertSet('form.activityType', ActivityType::Yoga->value)
            ->assertSet('form.level', SessionLevel::Beginner->value)
            ->assertSet('form.title', 'My Session')
            ->assertSet('form.priceEuros', '15.00');
    });

    it('denies access to a different coach', function () {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        $this->actingAs($otherCoach)
            ->get(route('coach.sessions.edit', $session))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->draft()->create();

        $this->actingAs($athlete)
            ->get(route('coach.sessions.edit', $session))
            ->assertForbidden();
    });

    it('denies editing a completed session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->completed()->create(['coach_id' => $coach->id]);

        $this->actingAs($coach)
            ->get(route('coach.sessions.edit', $session))
            ->assertForbidden();
    });

    it('denies editing a cancelled session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->cancelled()->create(['coach_id' => $coach->id]);

        $this->actingAs($coach)
            ->get(route('coach.sessions.edit', $session))
            ->assertForbidden();
    });

    it('updates a draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
            'title' => 'Old Title',
            'date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $futureDate = now()->addDays(14)->format('Y-m-d');

        Livewire::actingAs($coach)
            ->test(Edit::class, ['sportSession' => $session])
            ->set('form.activityType', ActivityType::Running->value)
            ->set('form.level', SessionLevel::Advanced->value)
            ->set('form.title', 'Updated Title')
            ->set('form.location', 'New Location')
            ->set('form.postalCode', '1050')
            ->set('form.date', $futureDate)
            ->set('form.startTime', '08:00')
            ->set('form.endTime', '09:30')
            ->set('form.priceEuros', '20.00')
            ->set('form.minParticipants', 2)
            ->set('form.maxParticipants', 12)
            ->call('save')
            ->assertDispatched('notify')
            ->assertRedirect();

        $session->refresh();
        expect($session->title)->toBe('Updated Title');
        expect($session->activity_type)->toBe(ActivityType::Running);
        expect($session->price_per_person)->toBe(2000);
        expect($session->status)->toBe(SessionStatus::Draft);
    });

    it('updates a published session', function () {
        $coach = User::factory()->coach()->create();
        $futureDate = now()->addDays(14)->format('Y-m-d');
        $session = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'title' => 'Published Session',
            'date' => $futureDate,
        ]);

        Livewire::actingAs($coach)
            ->test(Edit::class, ['sportSession' => $session])
            ->set('form.title', 'Updated Published')
            ->call('save')
            ->assertDispatched('notify')
            ->assertRedirect();

        $session->refresh();
        expect($session->title)->toBe('Updated Published');
        expect($session->status)->toBe(SessionStatus::Published);
    });

    it('allows admin to edit any session', function () {
        $admin = User::factory()->admin()->create();
        $session = SportSession::factory()->draft()->create([
            'date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['sportSession' => $session])
            ->set('form.title', 'Admin Edit')
            ->call('save')
            ->assertDispatched('notify')
            ->assertRedirect();

        expect($session->refresh()->title)->toBe('Admin Edit');
    });

    it('validates required fields on update', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        Livewire::actingAs($coach)
            ->test(Edit::class, ['sportSession' => $session])
            ->set('form.title', '')
            ->set('form.location', '')
            ->set('form.postalCode', '')
            ->call('save')
            ->assertHasErrors(['form.title', 'form.location', 'form.postalCode']);
    });
});
