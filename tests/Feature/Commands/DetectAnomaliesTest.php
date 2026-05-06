<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\PaymentAnomalyType;
use App\Models\Booking;
use App\Models\PaymentAnomaly;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('payments:detect-anomalies', function (): void {

    it('exits successfully and outputs expected messages', function (): void {
        $this->artisan('payments:detect-anomalies')
            ->expectsOutputToContain('Detecting payment anomalies')
            ->expectsOutputToContain('Done.')
            ->assertSuccessful();
    });

    it('calls detectAll by persisting anomalies when triggering data exists', function (): void {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create(['coach_id' => $coach->id]);
        $athlete = User::factory()->athlete()->create();

        Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 0,
            'stripe_payment_intent_id' => null,
        ]);

        $this->artisan('payments:detect-anomalies')->assertSuccessful();

        expect(
            PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::ConfirmedBookingMissingPayment->value)
                ->where('resolution_status', 'open')
                ->exists()
        )->toBeTrue();
    });

});
