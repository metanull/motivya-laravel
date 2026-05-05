<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Services\Audit\AuditService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Refund as StripeRefund;

uses(RefreshDatabase::class);

describe('RefundService audit', function () {
    it('records a refund.requested event before executing the Stripe refund', function () {
        $booking = Booking::factory()->cancelled()->create([
            'stripe_payment_intent_id' => 'pi_audit_refund_test',
        ]);

        $service = new RefundService(
            auditService: app(AuditService::class),
            createRefundUsing: fn (array $payload): StripeRefund => StripeRefund::constructFrom(['id' => 're_audit_001']),
        );

        $service->refund($booking);

        expect(
            AuditEvent::where('event_type', AuditEventType::RefundRequested->value)->exists()
        )->toBeTrue();
    });

    it('records a refund.completed event after a successful Stripe refund', function () {
        $booking = Booking::factory()->cancelled()->create([
            'stripe_payment_intent_id' => 'pi_audit_refund_completed',
        ]);

        $service = new RefundService(
            auditService: app(AuditService::class),
            createRefundUsing: fn (array $payload): StripeRefund => StripeRefund::constructFrom(['id' => 're_audit_002']),
        );

        $service->refund($booking);

        expect(
            AuditEvent::where('event_type', AuditEventType::RefundCompleted->value)->exists()
        )->toBeTrue();
    });

    it('records a refund.failed event when the Stripe refund throws', function () {
        $booking = Booking::factory()->cancelled()->create([
            'stripe_payment_intent_id' => 'pi_audit_refund_failed',
        ]);

        $service = new RefundService(
            auditService: app(AuditService::class),
            createRefundUsing: function (array $payload): never {
                throw new RuntimeException('Stripe error');
            },
        );

        try {
            $service->refund($booking);
        } catch (RuntimeException) {
            // expected
        }

        expect(
            AuditEvent::where('event_type', AuditEventType::RefundFailed->value)->exists()
        )->toBeTrue();
    });

    it('does not record any audit event for an already-refunded booking', function () {
        $booking = Booking::factory()->refunded()->create([
            'stripe_payment_intent_id' => 'pi_already_refunded',
        ]);

        $service = new RefundService(
            auditService: app(AuditService::class),
            createRefundUsing: fn (array $payload): StripeRefund => StripeRefund::constructFrom(['id' => 're_audit_skip']),
        );

        $service->refund($booking);

        expect(AuditEvent::count())->toBe(0);
    });

    it('records both requested and completed in the correct order', function () {
        $booking = Booking::factory()->cancelled()->create([
            'stripe_payment_intent_id' => 'pi_audit_order_check',
        ]);

        $service = new RefundService(
            auditService: app(AuditService::class),
            createRefundUsing: fn (array $payload): StripeRefund => StripeRefund::constructFrom(['id' => 're_order_003']),
        );

        $service->refund($booking);

        $events = AuditEvent::orderBy('occurred_at')->pluck('event_type')->all();

        expect($events[0]->value)->toBe(AuditEventType::RefundRequested->value)
            ->and($events[1]->value)->toBe(AuditEventType::RefundCompleted->value);
    });
});
