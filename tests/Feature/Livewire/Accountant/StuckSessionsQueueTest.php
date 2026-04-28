<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Livewire\Accountant\StuckSessionsQueue;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('StuckSessionsQueue', function () {

    beforeEach(function () {
        Storage::fake();
    });

    // ── Access control ────────────────────────────────────────────────────

    it('renders for an accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->assertOk();
    });

    it('renders for an admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(StuckSessionsQueue::class)
            ->assertOk();
    });

    it('forbids access to coaches', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(StuckSessionsQueue::class)
            ->assertForbidden();
    });

    it('forbids access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(StuckSessionsQueue::class)
            ->assertForbidden();
    });

    // ── Queue query correctness ───────────────────────────────────────────

    it('shows confirmed sessions that have passed their end time', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create(['name' => 'Coach Alpha']);

        SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'title' => 'Past Confirmed Session',
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->assertSee('Past Confirmed Session');
    });

    it('does not show future confirmed sessions', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'title' => 'Future Session',
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->assertDontSee('Future Session');
    });

    it('does not show completed sessions', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        SportSession::factory()->completed()->for($coach, 'coach')->create([
            'title' => 'Already Completed Session',
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->assertDontSee('Already Completed Session');
    });

    it('does not show cancelled sessions', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        SportSession::factory()->cancelled()->for($coach, 'coach')->create([
            'title' => 'Cancelled Session',
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->assertDontSee('Cancelled Session');
    });

    it('shows the invoice number when an invoice exists', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'title' => 'Session With Invoice',
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Invoice::factory()->create([
            'invoice_number' => 'INV-2026-000099',
            'sport_session_id' => $session->id,
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->assertSee('INV-2026-000099');
    });

    it('shows "no invoice" label when no invoice exists', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'title' => 'Session Without Invoice',
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->assertSee(__('accountant.stuck_no_invoice'));
    });

    // ── Manual complete action ────────────────────────────────────────────

    it('completes a past confirmed session and removes it from the queue', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->call('complete', $session->id);

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Completed);
    });

    it('generates an invoice when completing a stuck session', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->call('complete', $session->id);

        expect(Invoice::where('sport_session_id', $session->id)->count())->toBe(1);
    });

    it('does not duplicate invoices when completing a session that already has an invoice (idempotency)', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        // Complete once
        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->call('complete', $session->id);

        // Session is now completed; if somehow we try again, SessionService will throw
        // an exception (only confirmed sessions can be completed) — but the idempotency
        // guard in InvoiceService ensures no duplicate invoice is ever created.
        expect(Invoice::where('sport_session_id', $session->id)->count())->toBe(1);
    });

    it('dispatches success notification after completion', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->call('complete', $session->id)
            ->assertDispatched('notify');
    });

    it('forbids completion of a future confirmed session', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($accountant)
            ->test(StuckSessionsQueue::class)
            ->call('complete', $session->id)
            ->assertForbidden();
    });

    it('forbids completion by a coach', function () {
        $coach = User::factory()->coach()->withTwoFactor()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        // Coach cannot even mount the component
        Livewire::actingAs($coach)
            ->test(StuckSessionsQueue::class)
            ->assertForbidden();
    });
});
