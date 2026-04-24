<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('StripeConnectService', function () {
    it('creates and stores a stripe express account for an approved coach', function () {
        $coach = CoachProfile::factory()->approved()->create();

        $service = new StripeConnectService(
            createAccountUsing: function (array $payload) use ($coach): object {
                expect($payload['type'])->toBe('express');
                expect($payload['country'])->toBe('BE');
                expect($payload['email'])->toBe($coach->user->email);
                expect($payload['capabilities']['transfers']['requested'])->toBeTrue();
                expect($payload['capabilities']['bancontact_payments']['requested'])->toBeTrue();
                expect($payload['business_profile']['mcc'])->toBe('7941');
                expect($payload['business_profile']['url'])->toContain('/coaches/');
                expect($payload['metadata']['coach_id'])->toBe((string) $coach->id);
                expect($payload['metadata']['enterprise_number'])->toBe((string) $coach->enterprise_number);

                return (object) ['id' => 'acct_test_123'];
            },
        );

        $accountId = $service->createExpressAccount($coach);

        expect($accountId)->toBe('acct_test_123');
        expect($coach->fresh()->stripe_account_id)->toBe('acct_test_123');
    });

    it('returns the existing stripe account id without creating a new account', function () {
        $coach = CoachProfile::factory()->approved()->create([
            'stripe_account_id' => 'acct_existing_123',
        ]);

        $service = new StripeConnectService(
            createAccountUsing: function (): object {
                throw new RuntimeException('Stripe should not be called when the account already exists.');
            },
        );

        expect($service->createExpressAccount($coach))->toBe('acct_existing_123');
    });

    it('generates an onboarding link for an existing stripe account', function () {
        $coach = CoachProfile::factory()->approved()->create([
            'stripe_account_id' => 'acct_ready_123',
        ]);

        $service = new StripeConnectService(
            createAccountLinkUsing: function (array $payload) use ($coach): object {
                expect($payload['account'])->toBe($coach->stripe_account_id);
                expect($payload['type'])->toBe('account_onboarding');
                expect($payload['refresh_url'])->toBe(route('coach.stripe.refresh'));
                expect($payload['return_url'])->toBe(route('coach.stripe.return'));

                return (object) ['url' => 'https://connect.stripe.test/onboarding'];
            },
        );

        expect($service->generateOnboardingLink($coach))->toBe('https://connect.stripe.test/onboarding');
    });

    it('refuses to create a stripe account for a non-approved coach', function () {
        $coach = CoachProfile::factory()->pending()->create();

        $service = app(StripeConnectService::class);

        expect(fn () => $service->createExpressAccount($coach))
            ->toThrow(InvalidArgumentException::class, 'Only approved coaches can create a Stripe Express account.');
    });
});
