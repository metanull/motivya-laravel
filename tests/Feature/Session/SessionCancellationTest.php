<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Events\SessionCancelled;
use App\Models\SportSession;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('session cancellation', function () {
    it('cancels a published session', function () {
        Event::fake();
        $session = SportSession::factory()->published()->create();

        $service = app(SessionService::class);
        $service->cancel($session);

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Cancelled);

        // Published sessions don't dispatch SessionCancelled (no refunds needed)
        Event::assertNotDispatched(SessionCancelled::class);
    });

    it('cancels a confirmed session and dispatches event', function () {
        Event::fake();
        $session = SportSession::factory()->confirmed()->create();

        $service = app(SessionService::class);
        $service->cancel($session);

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Cancelled);

        Event::assertDispatched(SessionCancelled::class, function (SessionCancelled $event) use ($session) {
            return $event->session->id === $session->id;
        });
    });

    it('refuses to cancel a draft session', function () {
        $session = SportSession::factory()->draft()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->cancel($session))
            ->toThrow(InvalidArgumentException::class, 'Only published or confirmed sessions can be cancelled.');
    });

    it('refuses to cancel a completed session', function () {
        $session = SportSession::factory()->completed()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->cancel($session))
            ->toThrow(InvalidArgumentException::class, 'Only published or confirmed sessions can be cancelled.');
    });

    it('refuses to cancel an already cancelled session', function () {
        $session = SportSession::factory()->cancelled()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->cancel($session))
            ->toThrow(InvalidArgumentException::class, 'Only published or confirmed sessions can be cancelled.');
    });
});
