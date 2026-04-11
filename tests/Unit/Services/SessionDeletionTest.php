<?php

declare(strict_types=1);

use App\Models\SportSession;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('session deletion', function () {
    it('deletes a draft session', function () {
        $session = SportSession::factory()->draft()->create();

        $service = app(SessionService::class);
        $service->delete($session);

        $this->assertDatabaseMissing('sport_sessions', ['id' => $session->id]);
    });

    it('refuses to delete a published session', function () {
        $session = SportSession::factory()->published()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->delete($session))
            ->toThrow(InvalidArgumentException::class, 'Only draft sessions can be deleted.');
    });

    it('refuses to delete a confirmed session', function () {
        $session = SportSession::factory()->confirmed()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->delete($session))
            ->toThrow(InvalidArgumentException::class, 'Only draft sessions can be deleted.');
    });

    it('refuses to delete a completed session', function () {
        $session = SportSession::factory()->completed()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->delete($session))
            ->toThrow(InvalidArgumentException::class, 'Only draft sessions can be deleted.');
    });

    it('refuses to delete a cancelled session', function () {
        $session = SportSession::factory()->cancelled()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->delete($session))
            ->toThrow(InvalidArgumentException::class, 'Only draft sessions can be deleted.');
    });
});
