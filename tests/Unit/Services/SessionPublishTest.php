<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\SportSession;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('session publishing', function () {
    it('publishes a complete draft session', function () {
        $session = SportSession::factory()->draft()->create();

        $service = app(SessionService::class);
        $service->publish($session);

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Published);
    });

    it('refuses to publish a non-draft session', function () {
        $session = SportSession::factory()->published()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(InvalidArgumentException::class, 'Only draft sessions can be published.');
    });

    it('refuses to publish a confirmed session', function () {
        $session = SportSession::factory()->confirmed()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(InvalidArgumentException::class, 'Only draft sessions can be published.');
    });

    it('refuses to publish a completed session', function () {
        $session = SportSession::factory()->completed()->create();

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(InvalidArgumentException::class, 'Only draft sessions can be published.');
    });

    it('refuses to publish a session with missing title', function () {
        $session = SportSession::factory()->draft()->create(['title' => '']);

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(ValidationException::class);
    });

    it('refuses to publish a session with missing location', function () {
        $session = SportSession::factory()->draft()->create(['location' => '']);

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(ValidationException::class);
    });

    it('refuses to publish a session with zero price', function () {
        $session = SportSession::factory()->draft()->create(['price_per_person' => 0]);

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(ValidationException::class);
    });

    it('refuses to publish a session with zero min participants', function () {
        $session = SportSession::factory()->draft()->create(['min_participants' => 0]);

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(ValidationException::class);
    });
});
