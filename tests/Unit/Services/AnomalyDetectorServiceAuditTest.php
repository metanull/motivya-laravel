<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\PaymentAnomaly;
use App\Models\User;
use App\Services\AnomalyDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AnomalyDetectorService audit', function () {
    it('records an anomaly.resolved event when resolving an anomaly', function () {
        $actor = User::factory()->create(['role' => UserRole::Accountant]);
        $booking = Booking::factory()->confirmed()->create();
        $anomaly = PaymentAnomaly::factory()->create([
            'anomaly_type' => 'confirmed_booking_missing_payment',
            'anomalous_model_type' => Booking::class,
            'anomalous_model_id' => $booking->id,
            'resolution_status' => 'open',
        ]);
        $service = app(AnomalyDetectorService::class);

        $service->resolve($anomaly, $actor, 'Payment verified manually');

        expect(
            AuditEvent::where('event_type', AuditEventType::AnomalyResolved->value)->exists()
        )->toBeTrue();
    });

    it('includes the resolution reason in resolve audit metadata', function () {
        $actor = User::factory()->create(['role' => UserRole::Accountant]);
        $booking = Booking::factory()->confirmed()->create();
        $anomaly = PaymentAnomaly::factory()->create([
            'anomaly_type' => 'confirmed_booking_missing_payment',
            'anomalous_model_type' => Booking::class,
            'anomalous_model_id' => $booking->id,
            'resolution_status' => 'open',
        ]);
        $service = app(AnomalyDetectorService::class);

        $service->resolve($anomaly, $actor, 'Payment verified manually');

        $audit = AuditEvent::where('event_type', AuditEventType::AnomalyResolved->value)->firstOrFail();

        expect($audit->metadata['reason'])->toBe('Payment verified manually');
    });

    it('records an anomaly.ignored event when ignoring an anomaly', function () {
        $actor = User::factory()->create(['role' => UserRole::Accountant]);
        $booking = Booking::factory()->confirmed()->create();
        $anomaly = PaymentAnomaly::factory()->create([
            'anomaly_type' => 'confirmed_booking_missing_payment',
            'anomalous_model_type' => Booking::class,
            'anomalous_model_id' => $booking->id,
            'resolution_status' => 'open',
        ]);
        $service = app(AnomalyDetectorService::class);

        $service->ignore($anomaly, $actor, 'Known edge case — no action needed');

        expect(
            AuditEvent::where('event_type', AuditEventType::AnomalyIgnored->value)->exists()
        )->toBeTrue();
    });

    it('includes the ignore reason in ignore audit metadata', function () {
        $actor = User::factory()->create(['role' => UserRole::Accountant]);
        $booking = Booking::factory()->confirmed()->create();
        $anomaly = PaymentAnomaly::factory()->create([
            'anomaly_type' => 'confirmed_booking_missing_payment',
            'anomalous_model_type' => Booking::class,
            'anomalous_model_id' => $booking->id,
            'resolution_status' => 'open',
        ]);
        $service = app(AnomalyDetectorService::class);

        $service->ignore($anomaly, $actor, 'Known edge case — no action needed');

        $audit = AuditEvent::where('event_type', AuditEventType::AnomalyIgnored->value)->firstOrFail();

        expect($audit->metadata['reason'])->toBe('Known edge case — no action needed');
    });
});
