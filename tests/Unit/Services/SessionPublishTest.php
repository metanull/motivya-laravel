<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('session publishing', function () {
    it('publishes a complete draft session', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->for($coach)->create([
            'stripe_account_id' => 'acct_test',
            'stripe_onboarding_complete' => true,
        ]);
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

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

    it('throws ValidationException with stripe_onboarding key when stripe not complete', function () {
        $coach = User::factory()->coach()->create();
        // No CoachProfile → stripe onboarding incomplete
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        $service = app(SessionService::class);

        try {
            $service->publish($session);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('stripe_onboarding');
        }
    });

    it('throws ValidationException when coach profile exists but stripe_onboarding_complete is false', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->for($coach)->create([
            'stripe_account_id' => 'acct_incomplete',
            'stripe_onboarding_complete' => false,
        ]);
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        $service = app(SessionService::class);

        try {
            $service->publish($session);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('stripe_onboarding');
        }
    });

    it('refuses to publish a session with missing title', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->for($coach)->create([
            'stripe_account_id' => 'acct_test',
            'stripe_onboarding_complete' => true,
        ]);
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id, 'title' => '']);

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(ValidationException::class);
    });

    it('refuses to publish a session with missing location', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->for($coach)->create([
            'stripe_account_id' => 'acct_test',
            'stripe_onboarding_complete' => true,
        ]);
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id, 'location' => '']);

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(ValidationException::class);
    });

    it('refuses to publish a session with zero price', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->for($coach)->create([
            'stripe_account_id' => 'acct_test',
            'stripe_onboarding_complete' => true,
        ]);
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id, 'price_per_person' => 0]);

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(ValidationException::class);
    });

    it('refuses to publish a session with zero min participants', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->for($coach)->create([
            'stripe_account_id' => 'acct_test',
            'stripe_onboarding_complete' => true,
        ]);
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id, 'min_participants' => 0]);

        $service = app(SessionService::class);

        expect(fn () => $service->publish($session))
            ->toThrow(ValidationException::class);
    });
});
