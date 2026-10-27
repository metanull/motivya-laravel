<?php

declare(strict_types=1);

use Laravel\Cashier\Cashier;
use Stripe\Stripe;
use Stripe\Util\ApiVersion;

describe('Stripe API version pin', function () {
    it('pins the static Stripe client to the configured API version', function (): void {
        expect(Stripe::getApiVersion())->toBe(config('services.stripe.api_version'));
    });

    it('matches the API version of the installed Stripe SDK and Cashier', function (): void {
        // When this fails, the stripe/stripe-php upgrade changed the Stripe API version.
        // Review https://docs.stripe.com/changelog for the calls this app makes, then
        // update services.stripe.api_version and the webhook endpoint API version.
        expect(config('services.stripe.api_version'))->toBe(ApiVersion::CURRENT)
            ->and(Cashier::STRIPE_VERSION)->toBe(ApiVersion::CURRENT);
    });
});
