<?php

declare(strict_types=1);

use App\Contracts\BookingServiceContract;
use App\Contracts\PaymentServiceContract;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\PaymentHoldService;
use Illuminate\Auth\Access\AuthorizationException;
use Stripe\Checkout\Session as CheckoutSession;

describe('PaymentHoldService', function () {
    describe('retryPayment()', function () {
        it('throws AuthorizationException when booking belongs to a different athlete', function () {
            $athlete = new User(['id' => 10]);
            $athlete->id = 10;

            $booking = new Booking([
                'athlete_id' => 99, // different athlete
                'status' => BookingStatus::PendingPayment,
            ]);

            $service = new PaymentHoldService(
                Mockery::mock(BookingServiceContract::class),
                Mockery::mock(PaymentServiceContract::class),
            );

            expect(fn () => $service->retryPayment($booking, $athlete))
                ->toThrow(AuthorizationException::class);
        });

        it('throws InvalidArgumentException when booking is not PendingPayment', function () {
            $athlete = new User;
            $athlete->id = 5;

            $booking = new Booking([
                'athlete_id' => 5,
                'status' => BookingStatus::Confirmed, // not pending
            ]);

            $service = new PaymentHoldService(
                Mockery::mock(BookingServiceContract::class),
                Mockery::mock(PaymentServiceContract::class),
            );

            expect(fn () => $service->retryPayment($booking, $athlete))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws InvalidArgumentException when payment has expired', function () {
            $athlete = new User;
            $athlete->id = 5;

            $booking = new Booking([
                'athlete_id' => 5,
                'status' => BookingStatus::PendingPayment,
            ]);
            // Force the cast to be applied
            $booking->forceFill([
                'payment_expires_at' => now()->subMinutes(10), // expired
            ]);

            $service = new PaymentHoldService(
                Mockery::mock(BookingServiceContract::class),
                Mockery::mock(PaymentServiceContract::class),
            );

            expect(fn () => $service->retryPayment($booking, $athlete))
                ->toThrow(InvalidArgumentException::class);
        });

        it('calls paymentService::createCheckoutSession and returns the result', function () {
            $athlete = new User;
            $athlete->id = 7;

            $booking = new Booking([
                'athlete_id' => 7,
                'status' => BookingStatus::PendingPayment,
            ]);
            // Ensure payment_expires_at is in the future so it is not expired
            $booking->forceFill(['payment_expires_at' => now()->addMinutes(20)]);

            $fakeCheckoutSession = CheckoutSession::constructFrom([
                'id' => 'cs_test_fake',
                'url' => 'https://checkout.stripe.com/fake',
                'object' => 'checkout.session',
            ]);

            $mockPaymentService = Mockery::mock(PaymentServiceContract::class);
            $mockPaymentService->shouldReceive('createCheckoutSession')
                ->once()
                ->with($booking)
                ->andReturn($fakeCheckoutSession);

            $service = new PaymentHoldService(
                Mockery::mock(BookingServiceContract::class),
                $mockPaymentService,
            );

            $result = $service->retryPayment($booking, $athlete);

            expect($result)->toBe($fakeCheckoutSession);
        });
    });

    describe('cancelHold()', function () {
        it('delegates to BookingService::cancel()', function () {
            $athlete = new User;
            $athlete->id = 3;

            $booking = new Booking(['athlete_id' => 3]);

            $mockBookingService = Mockery::mock(BookingServiceContract::class);
            $mockBookingService->shouldReceive('cancel')
                ->once()
                ->with($booking, $athlete);

            $service = new PaymentHoldService(
                $mockBookingService,
                Mockery::mock(PaymentServiceContract::class),
            );

            $service->cancelHold($booking, $athlete);
        });
    });
});
