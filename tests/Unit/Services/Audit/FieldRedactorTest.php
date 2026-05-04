<?php

declare(strict_types=1);

use App\Services\Audit\FieldRedactor;

describe('FieldRedactor', function () {

    beforeEach(function () {
        $this->redactor = new FieldRedactor;
    });

    describe('exact field name redaction', function () {

        it('redacts password', function () {
            $result = $this->redactor->redact(['password' => 'secret123']);

            expect($result['password'])->toBe('[REDACTED]');
        });

        it('redacts remember_token', function () {
            $result = $this->redactor->redact(['remember_token' => 'tok_abc']);

            expect($result['remember_token'])->toBe('[REDACTED]');
        });

        it('redacts two_factor_secret', function () {
            $result = $this->redactor->redact(['two_factor_secret' => 'JBSWY3DPEHPK3PXP']);

            expect($result['two_factor_secret'])->toBe('[REDACTED]');
        });

        it('redacts two_factor_recovery_codes', function () {
            $result = $this->redactor->redact(['two_factor_recovery_codes' => ['code1', 'code2']]);

            expect($result['two_factor_recovery_codes'])->toBe('[REDACTED]');
        });

        it('redacts oauth_token', function () {
            $result = $this->redactor->redact(['oauth_token' => 'ya29.abcdef']);

            expect($result['oauth_token'])->toBe('[REDACTED]');
        });

        it('redacts oauth_refresh_token', function () {
            $result = $this->redactor->redact(['oauth_refresh_token' => 'refresh_abc']);

            expect($result['oauth_refresh_token'])->toBe('[REDACTED]');
        });

        it('redacts api_token', function () {
            $result = $this->redactor->redact(['api_token' => 'mytoken123']);

            expect($result['api_token'])->toBe('[REDACTED]');
        });

        it('redacts stripe_secret', function () {
            $result = $this->redactor->redact(['stripe_secret' => 'sk_live_xxx']);

            expect($result['stripe_secret'])->toBe('[REDACTED]');
        });

        it('redacts stripe_webhook_secret', function () {
            $result = $this->redactor->redact(['stripe_webhook_secret' => 'whsec_xxx']);

            expect($result['stripe_webhook_secret'])->toBe('[REDACTED]');
        });

        it('redacts password_confirmation', function () {
            $result = $this->redactor->redact(['password_confirmation' => 'secret']);

            expect($result['password_confirmation'])->toBe('[REDACTED]');
        });

    });

    describe('suffix-based redaction', function () {

        it('redacts any field ending in _token', function () {
            $result = $this->redactor->redact([
                'access_token' => 'tok_123',
                'reset_token' => 'reset_abc',
                'custom_auth_token' => 'auth_xyz',
            ]);

            expect($result['access_token'])->toBe('[REDACTED]');
            expect($result['reset_token'])->toBe('[REDACTED]');
            expect($result['custom_auth_token'])->toBe('[REDACTED]');
        });

        it('redacts any field ending in _secret', function () {
            $result = $this->redactor->redact([
                'client_secret' => 'cs_test_xxx',
                'app_secret' => 'supersecret',
            ]);

            expect($result['client_secret'])->toBe('[REDACTED]');
            expect($result['app_secret'])->toBe('[REDACTED]');
        });

    });

    describe('non-sensitive fields', function () {

        it('preserves non-sensitive fields unchanged', function () {
            $result = $this->redactor->redact([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'status' => 'published',
                'price' => 1250,
            ]);

            expect($result['name'])->toBe('Alice');
            expect($result['email'])->toBe('alice@example.com');
            expect($result['status'])->toBe('published');
            expect($result['price'])->toBe(1250);
        });

    });

    describe('nested arrays', function () {

        it('recursively redacts nested sensitive fields', function () {
            $result = $this->redactor->redact([
                'user' => [
                    'name' => 'Bob',
                    'password' => 'secret',
                    'api_token' => 'tok_abc',
                ],
                'status' => 'ok',
            ]);

            expect($result['user']['name'])->toBe('Bob');
            expect($result['user']['password'])->toBe('[REDACTED]');
            expect($result['user']['api_token'])->toBe('[REDACTED]');
            expect($result['status'])->toBe('ok');
        });

    });

    describe('case insensitivity', function () {

        it('redacts case-insensitive field names', function () {
            $result = $this->redactor->redact([
                'PASSWORD' => 'secret',
                'Api_Token' => 'tok123',
            ]);

            expect($result['PASSWORD'])->toBe('[REDACTED]');
            expect($result['Api_Token'])->toBe('[REDACTED]');
        });

    });

    describe('empty input', function () {

        it('returns an empty array when given an empty array', function () {
            expect($this->redactor->redact([]))->toBe([]);
        });

    });

});
