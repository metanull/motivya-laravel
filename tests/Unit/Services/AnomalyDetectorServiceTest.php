<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\PaymentAnomalyType;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\PaymentAnomaly;
use App\Models\SportSession;
use App\Models\User;
use App\Services\AnomalyDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('detectConfirmedBookingsMissingPayment', function () {
    it('creates an anomaly for a confirmed booking with no payment', function () {
        $session = SportSession::factory()->published()->create();
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 0,
            'stripe_payment_intent_id' => null,
        ]);

        app(AnomalyDetectorService::class)->detectConfirmedBookingsMissingPayment();

        expect(PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::ConfirmedBookingMissingPayment->value)
            ->where('related_booking_id', $booking->id)
            ->exists())->toBeTrue();
    });

    it('does not flag confirmed bookings that have a payment', function () {
        $session = SportSession::factory()->published()->create();
        $athlete = User::factory()->athlete()->create();
        Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 5000,
            'stripe_payment_intent_id' => 'pi_test',
        ]);

        app(AnomalyDetectorService::class)->detectConfirmedBookingsMissingPayment();

        expect(PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::ConfirmedBookingMissingPayment->value)->exists())->toBeFalse();
    });
});

describe('detectPaidBookingsCancelledWithoutRefund', function () {
    it('creates an anomaly for a cancelled booking with positive amount_paid and no refund', function () {
        $session = SportSession::factory()->published()->create();
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => BookingStatus::Cancelled,
            'amount_paid' => 3000,
            'refunded_at' => null,
        ]);

        app(AnomalyDetectorService::class)->detectPaidBookingsCancelledWithoutRefund();

        expect(PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::PaidBookingCancelledWithoutRefund->value)
            ->where('related_booking_id', $booking->id)
            ->exists())->toBeTrue();
    });
});

describe('detectCompletedSessionsWithoutInvoice', function () {
    it('creates an anomaly for a completed session with no invoice', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->create([
            'coach_id' => $coach->id,
            'status' => SessionStatus::Completed,
        ]);

        app(AnomalyDetectorService::class)->detectCompletedSessionsWithoutInvoice();

        expect(PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::CompletedSessionWithoutInvoice->value)
            ->where('related_session_id', $session->id)
            ->exists())->toBeTrue();
    });
});

describe('detectInvoiceTotalMismatches', function () {
    it('creates an anomaly when invoice totals do not match', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->create(['coach_id' => $coach->id]);

        Invoice::factory()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
            'revenue_ttc' => 12100,
            'revenue_htva' => 9000, // should be 10000 for correct 21% VAT
            'vat_amount' => 2100,   // 9000 + 2100 = 11100 ≠ 12100
        ]);

        app(AnomalyDetectorService::class)->detectInvoiceTotalMismatches();

        expect(PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::InvoiceTotalMismatch->value)->exists())->toBeTrue();
    });

    it('does not flag invoices with correct totals', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->create(['coach_id' => $coach->id]);

        Invoice::factory()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
            'revenue_ttc' => 12100,
            'revenue_htva' => 10000,
            'vat_amount' => 2100, // 10000 + 2100 = 12100 ✓
        ]);

        app(AnomalyDetectorService::class)->detectInvoiceTotalMismatches();

        expect(PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::InvoiceTotalMismatch->value)->exists())->toBeFalse();
    });
});

describe('detectAll', function () {
    it('runs all five detection methods without error', function () {
        expect(fn () => app(AnomalyDetectorService::class)->detectAll())->not->toThrow(Exception::class);
    });
});

describe('createIfNotOpen deduplication', function () {
    it('does not create a duplicate open anomaly for the same type and model', function () {
        $session = SportSession::factory()->published()->create();
        $athlete = User::factory()->athlete()->create();
        Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 0,
            'stripe_payment_intent_id' => null,
        ]);

        $service = app(AnomalyDetectorService::class);
        $service->detectConfirmedBookingsMissingPayment();
        $service->detectConfirmedBookingsMissingPayment(); // run twice

        expect(
            PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::ConfirmedBookingMissingPayment->value)->count()
        )->toBe(1);
    });
});

describe('resolve', function () {
    it('marks an anomaly as resolved with reason and actor', function () {
        $actor = User::factory()->accountant()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        app(AnomalyDetectorService::class)->resolve($anomaly, $actor, 'Manually fixed in Stripe.');

        $fresh = $anomaly->fresh();
        expect($fresh->resolution_status)->toBe('resolved')
            ->and($fresh->resolution_reason)->toBe('Manually fixed in Stripe.')
            ->and($fresh->resolved_by)->toBe($actor->id)
            ->and($fresh->resolved_at)->not->toBeNull();
    });
});

describe('ignore', function () {
    it('marks an anomaly as ignored with reason and actor', function () {
        $actor = User::factory()->accountant()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        app(AnomalyDetectorService::class)->ignore($anomaly, $actor, 'Known false positive.');

        $fresh = $anomaly->fresh();
        expect($fresh->resolution_status)->toBe('ignored')
            ->and($fresh->resolution_reason)->toBe('Known false positive.')
            ->and($fresh->resolved_by)->toBe($actor->id)
            ->and($fresh->resolved_at)->not->toBeNull();
    });
});

// Story 1.5: classifyBooking must derive per-booking anomaly flags from booking fields.
describe('classifyBooking', function () {

    it('flags a confirmed booking with amount_paid > 0 and no payment intent', function () {
        $session = SportSession::factory()->published()->create();
        $booking = Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => User::factory()->athlete()->create()->id,
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 2500,
            'stripe_payment_intent_id' => null,
        ]);

        $flags = app(AnomalyDetectorService::class)->classifyBooking($booking);

        expect($flags['missing_payment_intent'])->toBeTrue();
        expect($flags['has_anomaly'])->toBeTrue();
    });

    it('does not flag a confirmed booking that has a payment intent', function () {
        $session = SportSession::factory()->published()->create();
        $booking = Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => User::factory()->athlete()->create()->id,
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 2500,
            'stripe_payment_intent_id' => 'pi_test_ok',
        ]);

        $flags = app(AnomalyDetectorService::class)->classifyBooking($booking);

        expect($flags['missing_payment_intent'])->toBeFalse();
        expect($flags['has_anomaly'])->toBeFalse();
    });

    it('flags a confirmed booking with amount_paid 0', function () {
        $session = SportSession::factory()->published()->create();
        $booking = Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => User::factory()->athlete()->create()->id,
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 0,
            'stripe_payment_intent_id' => null,
        ]);

        $flags = app(AnomalyDetectorService::class)->classifyBooking($booking);

        expect($flags['confirmed_without_payment'])->toBeTrue();
        expect($flags['has_anomaly'])->toBeTrue();
    });

    it('flags a cancelled booking with a positive amount_paid and no refund date', function () {
        $session = SportSession::factory()->published()->create();
        $booking = Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => User::factory()->athlete()->create()->id,
            'status' => BookingStatus::Cancelled,
            'amount_paid' => 3000,
            'stripe_payment_intent_id' => 'pi_cancelled',
            'refunded_at' => null,
            'cancelled_at' => now(),
        ]);

        $flags = app(AnomalyDetectorService::class)->classifyBooking($booking);

        expect($flags['paid_cancelled_without_refund'])->toBeTrue();
        expect($flags['has_anomaly'])->toBeTrue();
    });

    it('does not flag a refunded booking', function () {
        $session = SportSession::factory()->published()->create();
        $booking = Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => User::factory()->athlete()->create()->id,
            'status' => BookingStatus::Refunded,
            'amount_paid' => 2000,
            'stripe_payment_intent_id' => 'pi_refunded',
            'refunded_at' => now(),
            'cancelled_at' => now(),
        ]);

        $flags = app(AnomalyDetectorService::class)->classifyBooking($booking);

        expect($flags['paid_cancelled_without_refund'])->toBeFalse();
        expect($flags['has_anomaly'])->toBeFalse();
    });
});
