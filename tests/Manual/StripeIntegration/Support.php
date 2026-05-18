<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Stripe\Account as StripeAccount;
use Stripe\PaymentIntent;
use Stripe\Stripe;

function stripeIntegrationEnv(string $key): ?string
{
    $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}

function stripeIntegrationValue(string $key, ?string $configKey = null): ?string
{
    $value = stripeIntegrationEnv($key);

    if ($value !== null || $configKey === null) {
        return $value;
    }

    $configured = config($configKey);

    return is_string($configured) && trim($configured) !== '' ? trim($configured) : null;
}

/**
 * @return array{publishable_key: string, secret_key: string, webhook_secret: string, base_url: string}
 */
function requireLiveStripeIntegration(): array
{
    $enabled = stripeIntegrationValue('MOTIVYA_STRIPE_LIVE_TESTS', 'services.stripe.manual_tests.enabled');
    $publishableKey = stripeIntegrationValue('STRIPE_KEY', 'services.stripe.key');
    $secretKey = stripeIntegrationValue('STRIPE_SECRET', 'services.stripe.secret');
    $webhookSecret = stripeIntegrationValue('STRIPE_WEBHOOK_SECRET', 'services.stripe.webhook.secret');
    $baseUrl = stripeIntegrationValue('MOTIVYA_QA_BASE_URL', 'services.stripe.manual_tests.base_url');

    $errors = [];

    if ($enabled !== '1') {
        $errors[] = 'MOTIVYA_STRIPE_LIVE_TESTS must be set to 1.';
    }

    if (! is_string($publishableKey) || ! str_starts_with($publishableKey, 'pk_test_')) {
        $errors[] = 'STRIPE_KEY must be a Stripe test-mode publishable key beginning with pk_test_.';
    }

    if (! is_string($secretKey) || ! str_starts_with($secretKey, 'sk_test_')) {
        $errors[] = 'STRIPE_SECRET must be a Stripe test-mode secret key beginning with sk_test_.';
    }

    if (! is_string($webhookSecret) || ! str_starts_with($webhookSecret, 'whsec_')) {
        $errors[] = 'STRIPE_WEBHOOK_SECRET must be a webhook signing secret beginning with whsec_.';
    }

    if (! is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
        $errors[] = 'MOTIVYA_QA_BASE_URL must be a valid URL for the app under test.';
    }

    if ($errors !== []) {
        throw new RuntimeException("Stripe manual integration readiness failed:\n- ".implode("\n- ", $errors));
    }

    config([
        'app.url' => $baseUrl,
        'services.stripe.key' => $publishableKey,
        'services.stripe.secret' => $secretKey,
        'services.stripe.webhook.secret' => $webhookSecret,
    ]);

    Stripe::setApiKey($secretKey);

    return [
        'publishable_key' => $publishableKey,
        'secret_key' => $secretKey,
        'webhook_secret' => $webhookSecret,
        'base_url' => $baseUrl,
    ];
}

function manualStripeConnectedAccountId(string $qaRunId): string
{
    $configured = stripeIntegrationValue('MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID', 'services.stripe.manual_tests.connected_account_id');

    if (is_string($configured) && str_starts_with($configured, 'acct_')) {
        return $configured;
    }

    try {
        $accounts = StripeAccount::all(['limit' => 100]);
    } catch (Throwable $exception) {
        throw new RuntimeException('Unable to list Stripe test connected accounts: '.$exception->getMessage(), previous: $exception);
    }

    foreach ($accounts->data as $account) {
        if (is_string($account->id ?? null) && str_starts_with($account->id, 'acct_')) {
            return $account->id;
        }
    }

    try {
        $account = StripeAccount::create([
            'type' => 'express',
            'country' => 'BE',
            'email' => "connect.{$qaRunId}@motivya.test",
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
            'metadata' => [
                'qa_run_id' => $qaRunId,
                'created_by' => 'motivya_manual_stripe_suite',
            ],
        ]);
    } catch (Throwable $exception) {
        throw new RuntimeException('Unable to create a Stripe test connected account: '.$exception->getMessage(), previous: $exception);
    }

    if (! is_string($account->id ?? null) || ! str_starts_with($account->id, 'acct_')) {
        throw new RuntimeException('Stripe did not return a valid connected account ID.');
    }

    return $account->id;
}

function manualStripeQaRunId(string $prefix): string
{
    return sprintf('%s_%s_%s', $prefix, now()->format('YmdHis'), bin2hex(random_bytes(3)));
}

function manualStripeCoach(string $qaRunId, string $connectedAccountId, bool $isVatSubject = true): User
{
    $coach = User::factory()->coach()->create([
        'name' => "Stripe QA Coach {$qaRunId}",
        'email' => "coach.{$qaRunId}@motivya.test",
        'email_verified_at' => now(),
    ]);

    CoachProfile::factory()
        ->approved()
        ->for($coach)
        ->create([
            'is_vat_subject' => $isVatSubject,
            'stripe_account_id' => $connectedAccountId,
            'stripe_onboarding_complete' => true,
            'enterprise_number' => '0123.456.789',
        ]);

    return $coach;
}

function manualStripeAthlete(string $qaRunId): User
{
    return User::factory()->athlete()->create([
        'name' => "Stripe QA Athlete {$qaRunId}",
        'email' => "athlete.{$qaRunId}@motivya.test",
        'email_verified_at' => now(),
    ]);
}

function manualStripeSession(string $qaRunId, User $coach, array $overrides = []): SportSession
{
    return SportSession::factory()
        ->published()
        ->for($coach, 'coach')
        ->create(array_merge([
            'title' => "Stripe QA Session {$qaRunId}",
            'postal_code' => '1000',
            'min_participants' => 1,
            'max_participants' => 3,
            'price_per_person' => 2500,
            'current_participants' => 0,
        ], $overrides));
}

function postManualStripeWebhook(string $webhookSecret, string $eventId, string $eventType, array $data): TestResponse
{
    $payload = json_encode([
        'id' => $eventId,
        'type' => $eventType,
        'object' => 'event',
        'api_version' => '2024-06-20',
        'created' => time(),
        'data' => ['object' => $data],
        'livemode' => false,
        'pending_webhooks' => 1,
    ], JSON_THROW_ON_ERROR);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $webhookSecret);

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

function createManualStripeSucceededPaymentIntent(string $qaRunId, int $amount, array $metadata = []): PaymentIntent
{
    /** @var PaymentIntent $paymentIntent */
    $paymentIntent = PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'eur',
        'payment_method' => 'pm_card_visa',
        'confirm' => true,
        'automatic_payment_methods' => [
            'enabled' => true,
            'allow_redirects' => 'never',
        ],
        'metadata' => array_merge(['qa_run_id' => $qaRunId], $metadata),
    ]);

    return $paymentIntent;
}

function manualOriginalInvoice(string $qaRunId, Booking $booking): Invoice
{
    $session = $booking->sportSession;
    $revenueTtc = $booking->amount_paid;
    $revenueHtva = intdiv($revenueTtc * 100 + 60, 121);
    $commissionAmount = (int) round($revenueHtva * 30 / 100);

    return Invoice::factory()->invoice()->for($session->coach, 'coach')->create([
        'sport_session_id' => $session->id,
        'billing_period_start' => $session->date,
        'billing_period_end' => $session->date,
        'revenue_ttc' => $revenueTtc,
        'revenue_htva' => $revenueHtva,
        'vat_amount' => (int) round($revenueHtva * 21 / 100),
        'stripe_fee' => (int) round($revenueTtc * 15 / 1000),
        'subscription_fee' => 0,
        'commission_amount' => $commissionAmount,
        'coach_payout' => $revenueHtva - $commissionAmount,
        'platform_margin' => $commissionAmount,
        'plan_applied' => 'freemium',
        'tax_category_code' => 'S',
        'status' => InvoiceStatus::Draft->value,
        'xml_path' => "manual-stripe/{$qaRunId}/original.xml",
    ]);
}

function manualConfirmedPaidBooking(string $qaRunId, string $connectedAccountId, int $amount = 2500): Booking
{
    $coach = manualStripeCoach($qaRunId, $connectedAccountId);
    $athlete = manualStripeAthlete($qaRunId);
    $session = manualStripeSession($qaRunId, $coach, [
        'price_per_person' => $amount,
        'current_participants' => 1,
    ]);
    $paymentIntent = createManualStripeSucceededPaymentIntent($qaRunId, $amount, [
        'session_id' => (string) $session->id,
        'athlete_id' => (string) $athlete->id,
        'coach_id' => (string) $coach->id,
    ]);

    return Booking::factory()
        ->confirmed()
        ->for($session, 'sportSession')
        ->for($athlete, 'athlete')
        ->create([
            'amount_paid' => $amount,
            'stripe_payment_intent_id' => $paymentIntent->id,
        ]);
}
