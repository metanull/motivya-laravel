<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session as CheckoutSession;

uses(RefreshDatabase::class);

describe('PaymentService audit', function () {
    it('records a booking.payment_started event when creating a checkout session', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_account_id' => 'acct_audit_test']);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create(['price_per_person' => 2000]);
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->for($session, 'sportSession')->for($athlete, 'athlete')->create();

        $service = new PaymentService(
            auditService: app(AuditService::class),
            createCheckoutSessionUsing: fn (array $payload): CheckoutSession => CheckoutSession::constructFrom([
                'id' => 'cs_audit_test_001',
                'url' => 'https://checkout.stripe.com/pay/cs_audit_test_001',
            ]),
        );

        $service->createCheckoutSession($booking);

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingPaymentStarted->value)->exists()
        )->toBeTrue();
    });

    it('stores the stripe checkout session id in the audit new_values', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_account_id' => 'acct_audit_test2']);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create(['price_per_person' => 1500]);
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->for($session, 'sportSession')->for($athlete, 'athlete')->create();

        $service = new PaymentService(
            auditService: app(AuditService::class),
            createCheckoutSessionUsing: fn (array $payload): CheckoutSession => CheckoutSession::constructFrom([
                'id' => 'cs_audit_values_check',
                'url' => 'https://checkout.stripe.com/pay/cs_audit_values_check',
            ]),
        );

        $service->createCheckoutSession($booking);

        $audit = AuditEvent::where('event_type', AuditEventType::BookingPaymentStarted->value)->firstOrFail();

        expect($audit->new_values['stripe_checkout_session_id'])->toBe('cs_audit_values_check');
    });
});
