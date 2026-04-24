<?php

declare(strict_types=1);

use App\Events\CoachStripeOnboardingComplete;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stripeWebhookSecret = 'whsec_account_updated';
    config(['services.stripe.webhook.secret' => $this->stripeWebhookSecret]);

    $this->postStripeWebhook = function (string $eventId, string $eventType, array $data): TestResponse {
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
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $this->stripeWebhookSecret);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    };
});

describe('account.updated webhook', function () {
    it('marks Stripe onboarding as complete and dispatches CoachStripeOnboardingComplete', function () {
        Event::fake([CoachStripeOnboardingComplete::class]);

        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_onboarding_complete',
            'stripe_onboarding_complete' => false,
        ]);

        $response = ($this->postStripeWebhook)('evt_account_updated', 'account.updated', [
            'id' => 'acct_onboarding_complete',
            'details_submitted' => true,
            'charges_enabled' => true,
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        expect($coachProfile->fresh()->stripe_onboarding_complete)->toBeTrue();

        Event::assertDispatched(CoachStripeOnboardingComplete::class, fn (CoachStripeOnboardingComplete $event): bool => $event->coachProfileId === $coachProfile->id);
    });
});
