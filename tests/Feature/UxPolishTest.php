<?php

declare(strict_types=1);

use App\Livewire\Athlete\Dashboard as AthleteDashboard;
use App\Livewire\Coach\Dashboard as CoachDashboard;
use App\Livewire\Session\Show;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('MVP UX Polish', function () {

    // ------------------------------------------------------------------ //
    // 1. Homepage usefulness
    // ------------------------------------------------------------------ //

    describe('welcome page', function () {
        it('shows the meaningful subtitle instead of repeating the app name', function () {
            $this->get('/')
                ->assertOk()
                ->assertSee(__('common.welcome_subtitle'));
        });

        it('shows the athlete value-proposition text', function () {
            $this->get('/')
                ->assertOk()
                ->assertSee(__('common.welcome_for_athletes'));
        });

        it('shows the coach value-proposition text', function () {
            $this->get('/')
                ->assertOk()
                ->assertSee(__('common.welcome_for_coaches'));
        });
    });

    // ------------------------------------------------------------------ //
    // 2. Status labels — session show page
    // ------------------------------------------------------------------ //

    describe('session show page status badges', function () {
        it('shows blue badge for a published session', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create();

            Livewire::actingAs($athlete)
                ->test(Show::class, ['sportSession' => $session])
                ->assertSeeHtml('bg-blue-100');
        });

        it('shows green badge for a confirmed session', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->confirmed()->create();

            Livewire::actingAs($athlete)
                ->test(Show::class, ['sportSession' => $session])
                ->assertSeeHtml('bg-green-100');
        });

        it('shows gray badge for a draft session viewed by its coach', function () {
            $coach = User::factory()->coach()->create();
            $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

            Livewire::actingAs($coach)
                ->test(Show::class, ['sportSession' => $session])
                ->assertSeeHtml('bg-gray-100');
        });

        it('shows purple badge for a completed session', function () {
            $coach = User::factory()->coach()->create();
            $session = SportSession::factory()->completed()->create(['coach_id' => $coach->id]);

            Livewire::actingAs($coach)
                ->test(Show::class, ['sportSession' => $session])
                ->assertSeeHtml('bg-purple-100');
        });

        it('shows red badge for a cancelled session', function () {
            $coach = User::factory()->coach()->create();
            $session = SportSession::factory()->cancelled()->create(['coach_id' => $coach->id]);

            Livewire::actingAs($coach)
                ->test(Show::class, ['sportSession' => $session])
                ->assertSeeHtml('bg-red-100');
        });
    });

    // ------------------------------------------------------------------ //
    // 3. Coach form guidance — hint texts
    // ------------------------------------------------------------------ //

    describe('session create form guidance', function () {
        it('shows price hint text on create form', function () {
            $coach = User::factory()->coach()->create();

            $this->actingAs($coach)
                ->get(route('coach.sessions.create'))
                ->assertOk()
                ->assertSee(__('sessions.price_hint'));
        });

        it('shows min participants hint text on create form', function () {
            $coach = User::factory()->coach()->create();

            $this->actingAs($coach)
                ->get(route('coach.sessions.create'))
                ->assertOk()
                ->assertSee(__('sessions.min_participants_hint'));
        });

        it('shows max participants hint text on create form', function () {
            $coach = User::factory()->coach()->create();

            $this->actingAs($coach)
                ->get(route('coach.sessions.create'))
                ->assertOk()
                ->assertSee(__('sessions.max_participants_hint'));
        });

        it('shows price hint text on edit form', function () {
            $coach = User::factory()->coach()->create();
            $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

            $this->actingAs($coach)
                ->get(route('coach.sessions.edit', $session))
                ->assertOk()
                ->assertSee(__('sessions.price_hint'));
        });

        it('shows min participants hint text on edit form', function () {
            $coach = User::factory()->coach()->create();
            $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

            $this->actingAs($coach)
                ->get(route('coach.sessions.edit', $session))
                ->assertOk()
                ->assertSee(__('sessions.min_participants_hint'));
        });
    });

    // ------------------------------------------------------------------ //
    // 4. Post-publish sharing — coach session card
    // ------------------------------------------------------------------ //

    describe('coach dashboard session card sharing', function () {
        it('shows WhatsApp share button for a published session', function () {
            $coach = User::factory()->coach()->create();
            SportSession::factory()->published()->create([
                'coach_id' => $coach->id,
                'title' => 'Yoga Share Test',
                'date' => now()->addDays(5),
            ]);

            Livewire::actingAs($coach)
                ->test(CoachDashboard::class)
                ->assertSeeHtml('wa.me');
        });

        it('shows WhatsApp share button for a confirmed session', function () {
            $coach = User::factory()->coach()->create();
            SportSession::factory()->confirmed()->create([
                'coach_id' => $coach->id,
                'title' => 'Boxing Share Test',
                'date' => now()->addDays(3),
            ]);

            Livewire::actingAs($coach)
                ->test(CoachDashboard::class)
                ->assertSeeHtml('wa.me');
        });

        it('does not show WhatsApp share button for a draft session', function () {
            $coach = User::factory()->coach()->create();
            SportSession::factory()->draft()->create([
                'coach_id' => $coach->id,
                'title' => 'Draft No Share',
            ]);

            Livewire::actingAs($coach)
                ->test(CoachDashboard::class)
                ->set('tab', 'drafts')
                ->assertDontSeeHtml('wa.me');
        });
    });

    // ------------------------------------------------------------------ //
    // 5. Role-specific dashboard clarity
    // ------------------------------------------------------------------ //

    describe('athlete dashboard empty state', function () {
        it('shows explore CTA when athlete has no upcoming bookings', function () {
            $athlete = User::factory()->athlete()->create();

            Livewire::actingAs($athlete)
                ->test(AthleteDashboard::class)
                ->assertSee(__('athlete.no_upcoming_cta'));
        });

        it('does not show explore CTA when athlete has upcoming bookings', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->confirmed()->create([
                'date' => now()->addDays(3),
            ]);
            Booking::factory()->confirmed()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
            ]);

            Livewire::actingAs($athlete)
                ->test(AthleteDashboard::class)
                ->assertDontSee(__('athlete.no_upcoming_cta'));
        });
    });

    describe('coach dashboard drafts empty state', function () {
        it('shows create session CTA when coach has no draft sessions', function () {
            $coach = User::factory()->coach()->create();

            Livewire::actingAs($coach)
                ->test(CoachDashboard::class)
                ->set('tab', 'drafts')
                ->assertSee(__('coach.no_drafts_cta'));
        });

        it('does not show drafts CTA when coach has draft sessions', function () {
            $coach = User::factory()->coach()->create();
            SportSession::factory()->draft()->create([
                'coach_id' => $coach->id,
                'title' => 'My Draft',
            ]);

            Livewire::actingAs($coach)
                ->test(CoachDashboard::class)
                ->set('tab', 'drafts')
                ->assertDontSee(__('coach.no_drafts_cta'));
        });
    });
});
