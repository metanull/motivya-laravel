<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Events\SessionCompleted;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('sessions:complete-finished', function () {
    afterEach(function (): void {
        Carbon::setTestNow();
    });

    it('marks confirmed sessions as completed after their end time', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-10',
            'end_time' => '11:00:00',
        ]);

        $this->artisan('sessions:complete-finished')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Completed);
    });

    it('dispatches SessionCompleted event when session is completed', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');
        Event::fake([SessionCompleted::class]);

        $session = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-10',
            'end_time' => '11:00:00',
        ]);

        $this->artisan('sessions:complete-finished')->assertSuccessful();

        Event::assertDispatched(SessionCompleted::class, function (SessionCompleted $event) use ($session): bool {
            return $event->session->id === $session->id;
        });
    });

    it('does not complete sessions whose end time has not yet passed', function (): void {
        Carbon::setTestNow('2026-05-10 10:00:00');

        $session = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-10',
            'end_time' => '11:00:00',
        ]);

        $this->artisan('sessions:complete-finished')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Confirmed);
    });

    it('does not complete published sessions', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->published()->create([
            'date' => '2026-05-10',
            'end_time' => '11:00:00',
        ]);

        $this->artisan('sessions:complete-finished')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Published);
    });

    it('does not re-complete already completed sessions', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->completed()->create([
            'date' => '2026-05-10',
            'end_time' => '11:00:00',
        ]);

        $this->artisan('sessions:complete-finished')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Completed);
    });

    it('does not complete cancelled sessions', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');

        $session = SportSession::factory()->cancelled()->create([
            'date' => '2026-05-10',
            'end_time' => '11:00:00',
        ]);

        $this->artisan('sessions:complete-finished')->assertSuccessful();

        expect($session->fresh()->status)->toBe(SessionStatus::Cancelled);
    });

    it('completes multiple finished sessions in one run', function (): void {
        Carbon::setTestNow('2026-05-10 15:00:00');

        $sessionA = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-09',
            'end_time' => '11:00:00',
        ]);
        $sessionB = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-10',
            'end_time' => '12:00:00',
        ]);

        $this->artisan('sessions:complete-finished')->assertSuccessful();

        expect($sessionA->fresh()->status)->toBe(SessionStatus::Completed);
        expect($sessionB->fresh()->status)->toBe(SessionStatus::Completed);
    });
});
