<?php

declare(strict_types=1);

require_once __DIR__.'/Support.php';

describe('Stripe manual integration readiness', function () {
    it('requires explicit Stripe test-mode configuration before live tests run', function (): void {
        $config = requireLiveStripeIntegration();

        expect($config['publishable_key'])->toStartWith('pk_test_')
            ->and($config['secret_key'])->toStartWith('sk_test_')
            ->and($config['webhook_secret'])->toStartWith('whsec_')
            ->and($config['connected_account_id'])->toStartWith('acct_')
            ->and(filter_var($config['base_url'], FILTER_VALIDATE_URL))->not->toBeFalse();
    });
});
