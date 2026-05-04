<?php

declare(strict_types=1);

use App\Contracts\PaymentServiceContract;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentHoldService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;

uses(RefreshDatabase::class);

describe('BookingPaymentReturnController', function () {
    describe('__invoke (GET payment-return page)', function () {
        it('returns 200 for success status', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->confirmed()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
            ]);

            $this->actingAs($athlete)
                ->get(route('bookings.payment-return', [
                    'booking' => $booking->id,
                    'status' => 'success',
                ]))
                ->assertOk()
                ->assertSee(__('bookings.payment_return_success_title'));
        });

        it('returns 200 for cancel status with pending booking and shows retry options', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
                'payment_expires_at' => now()->addMinutes(20),
            ]);

            $this->actingAs($athlete)
                ->get(route('bookings.payment-return', [
                    'booking' => $booking->id,
                    'status' => 'cancel',
                ]))
                ->assertOk()
                ->assertSee(__('bookings.payment_return_pending_title'))
                ->assertSee(__('bookings.payment_return_retry_action'))
                ->assertSee(__('bookings.payment_return_cancel_hold_action'));
        });

        it('does NOT auto-cancel the booking on cancel status', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
                'payment_expires_at' => now()->addMinutes(20),
            ]);

            $this->actingAs($athlete)
                ->get(route('bookings.payment-return', [
                    'booking' => $booking->id,
                    'status' => 'cancel',
                ]))
                ->assertOk();

            // Booking must still be PendingPayment (no auto-cancel)
            expect($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
        });

        it('returns 200 for failed status with pending booking', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
                'payment_expires_at' => now()->addMinutes(20),
            ]);

            $this->actingAs($athlete)
                ->get(route('bookings.payment-return', [
                    'booking' => $booking->id,
                    'status' => 'failed',
                ]))
                ->assertOk()
                ->assertSee(__('bookings.payment_return_pending_title'));
        });

        it('shows failed card when booking is no longer pending', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->cancelled()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
            ]);

            $this->actingAs($athlete)
                ->get(route('bookings.payment-return', [
                    'booking' => $booking->id,
                    'status' => 'cancel',
                ]))
                ->assertOk()
                ->assertSee(__('bookings.payment_return_failed_title'));
        });

        it('shows failed card when pending booking has expired', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
                'payment_expires_at' => now()->subMinutes(5), // expired
            ]);

            $this->actingAs($athlete)
                ->get(route('bookings.payment-return', [
                    'booking' => $booking->id,
                    'status' => 'cancel',
                ]))
                ->assertOk()
                ->assertSee(__('bookings.payment_return_cancel_hold_action')) // can still cancel, just can't retry
                ->assertDontSee(__('bookings.payment_return_retry_action'));
        });

        it('returns 403 when athlete does not own the booking', function () {
            $athlete = User::factory()->athlete()->create();
            $otherAthlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $otherAthlete->id,
                'sport_session_id' => $session->id,
            ]);

            $this->actingAs($athlete)
                ->get(route('bookings.payment-return', [
                    'booking' => $booking->id,
                    'status' => 'cancel',
                ]))
                ->assertForbidden();
        });

        it('returns 401 for unauthenticated user', function () {
            $this->get(route('bookings.payment-return', [
                'booking' => 999,
                'status' => 'success',
            ]))->assertRedirect(route('login'));
        });
    });

    describe('retryPayment (POST bookings/{booking}/retry-payment)', function () {
        it('redirects to stripe checkout URL on success', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
                'price_per_person' => 1500,
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
                'payment_expires_at' => now()->addMinutes(20),
            ]);

            // Bind a mock PaymentServiceContract that returns a fake checkout session
            $fakeSession = Session::constructFrom([
                'id' => 'cs_test_fake',
                'url' => 'https://checkout.stripe.com/fake-session-url',
                'object' => 'checkout.session',
            ]);

            $mockPaymentService = Mockery::mock(PaymentServiceContract::class);
            $mockPaymentService->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn($fakeSession);

            $this->app->instance(PaymentHoldService::class, new PaymentHoldService(
                app(BookingService::class),
                $mockPaymentService,
            ));

            $this->actingAs($athlete)
                ->post(route('bookings.retry-payment', $booking))
                ->assertRedirect('https://checkout.stripe.com/fake-session-url');
        });

        it('redirects back with error when booking is expired', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
                'payment_expires_at' => now()->subMinutes(5), // expired
            ]);

            $this->actingAs($athlete)
                ->post(route('bookings.retry-payment', $booking))
                ->assertRedirect();
        });

        it('returns 403 when athlete does not own the booking', function () {
            $athlete = User::factory()->athlete()->create();
            $otherAthlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $otherAthlete->id,
                'sport_session_id' => $session->id,
            ]);

            $this->actingAs($athlete)
                ->post(route('bookings.retry-payment', $booking))
                ->assertForbidden();
        });

        it('returns 401 for unauthenticated user', function () {
            $booking = Booking::factory()->pendingPayment()->create();

            $this->post(route('bookings.retry-payment', $booking))
                ->assertRedirect(route('login'));
        });
    });

    describe('cancelHold (POST bookings/{booking}/cancel-hold)', function () {
        it('cancels the booking and redirects to athlete dashboard', function () {
            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $athlete->id,
                'sport_session_id' => $session->id,
                'payment_expires_at' => now()->addMinutes(20),
            ]);

            $this->actingAs($athlete)
                ->post(route('bookings.cancel-hold', $booking))
                ->assertRedirect(route('athlete.dashboard'));

            expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
        });

        it('returns 403 when athlete does not own the booking', function () {
            $athlete = User::factory()->athlete()->create();
            $otherAthlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->create([
                'date' => now()->addDays(3),
            ]);
            $booking = Booking::factory()->pendingPayment()->create([
                'athlete_id' => $otherAthlete->id,
                'sport_session_id' => $session->id,
            ]);

            $this->actingAs($athlete)
                ->post(route('bookings.cancel-hold', $booking))
                ->assertForbidden();
        });

        it('returns 401 for unauthenticated user', function () {
            $booking = Booking::factory()->pendingPayment()->create();

            $this->post(route('bookings.cancel-hold', $booking))
                ->assertRedirect(route('login'));
        });
    });
});
