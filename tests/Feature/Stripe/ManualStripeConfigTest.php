<?php

declare(strict_types=1);

require_once __DIR__.'/../../Manual/StripeIntegration/Support.php';

describe('manual Stripe UAT configuration', function () {
    it('reads readiness flags from cached Laravel config values', function (): void {
        unset($_SERVER['MOTIVYA_STRIPE_LIVE_TESTS'], $_ENV['MOTIVYA_STRIPE_LIVE_TESTS']);
        unset($_SERVER['MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID'], $_ENV['MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID']);

        config([
            'services.stripe.key' => 'pk_test_config_ready',
            'services.stripe.secret' => 'sk_test_config_ready',
            'services.stripe.webhook.secret' => 'whsec_config_ready',
            'services.stripe.manual_tests.enabled' => '1',
            'services.stripe.manual_tests.connected_account_id' => 'acct_config_ready',
            'services.stripe.manual_tests.base_url' => 'https://motivya.metanull.eu',
        ]);

        $config = requireLiveStripeIntegration();

        expect($config['publishable_key'])->toBe('pk_test_config_ready')
            ->and($config['secret_key'])->toBe('sk_test_config_ready')
            ->and($config['webhook_secret'])->toBe('whsec_config_ready')
            ->and($config['base_url'])->toBe('https://motivya.metanull.eu');
    });

    it('uses an optional configured connected account when one is present', function (): void {
        unset($_SERVER['MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID'], $_ENV['MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID']);

        config([
            'services.stripe.manual_tests.connected_account_id' => 'acct_config_ready',
        ]);

        expect(manualStripeConnectedAccountId('config_test'))->toBe('acct_config_ready');
    });
});
