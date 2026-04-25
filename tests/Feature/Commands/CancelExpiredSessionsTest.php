<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Events\SessionCancelled;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('sessions:cancel-expired', function () {
    afterEach(function (): void {
        Carbon::setTestNow();
    });

    it('cancels published sessions whose start time has passed', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->published()->create([
            'date' => '2026-05-10',
            'start_time' => '10:00:00',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Cancelled);
    });

    it('does not cancel confirmed sessions', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-10',
            'start_time' => '10:00:00',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Confirmed);
    });

    it('does not cancel draft sessions', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->draft()->create([
            'date' => '2026-05-10',
            'start_time' => '10:00:00',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Draft);
    });

    it('does not cancel completed sessions', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->completed()->create([
            'date' => '2026-05-10',
            'start_time' => '10:00:00',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Completed);
    });

    it('does not cancel future published sessions', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->published()->create([
            'date' => '2026-05-11',
            'start_time' => '10:00:00',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Published);
    });

    it('does not dispatch SessionCancelled for published (non-confirmed) sessions', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');
        Event::fake([SessionCancelled::class]);

        $session = SportSession::factory()->published()->create([
            'date' => '2026-05-10',
            'start_time' => '10:00:00',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        // Published sessions have no confirmed bookings requiring refunds,
        // so SessionCancelled is not dispatched.
        Event::assertNotDispatched(SessionCancelled::class);
    });

    it('cancels multiple expired published sessions in one run', function (): void {
        Carbon::setTestNow('2026-05-10 15:00:00');

        $sessionA = SportSession::factory()->published()->create([
            'date' => '2026-05-09',
            'start_time' => '10:00:00',
        ]);
        $sessionB = SportSession::factory()->published()->create([
            'date' => '2026-05-10',
            'start_time' => '09:00:00',
        ]);

        $this->artisan('sessions:cancel-expired')->assertSuccessful();

        expect($sessionA->fresh()->status)->toBe(SessionStatus::Cancelled);
        expect($sessionB->fresh()->status)->toBe(SessionStatus::Cancelled);
    });
});
