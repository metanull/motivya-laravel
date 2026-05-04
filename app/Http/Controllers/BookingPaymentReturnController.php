<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\PaymentHoldService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class BookingPaymentReturnController extends Controller
{
    /**
     * Display the payment return page after a Stripe Checkout redirect.
     *
     * Accepts status=success|cancel|failed (anything else defaults to cancel).
     * For cancel/failed with a PendingPayment booking, shows retry and cancel-hold
     * options instead of auto-cancelling.
     */
    public function __invoke(Request $request): View
    {
        $booking = Booking::with('sportSession')->findOrFail($request->integer('booking'));
        $user = $request->user();

        abort_unless($user instanceof User && $booking->athlete_id === $user->id, 403);

        $paymentStatus = in_array($request->query('status'), ['success', 'cancel', 'failed'], true)
            ? $request->query('status')
            : 'cancel';

        // For cancel/failed states: expose whether the athlete can retry or cancel the hold.
        // We intentionally do NOT auto-cancel here — the athlete decides.
        $canRetry = $paymentStatus !== 'success'
            && $booking->status === BookingStatus::PendingPayment
            && ! $booking->isPaymentExpired();

        $canCancelHold = $paymentStatus !== 'success'
            && $booking->status === BookingStatus::PendingPayment;

        return view('bookings.payment-return', [
            'booking' => $booking,
            'paymentStatus' => $paymentStatus,
            'canRetry' => $canRetry,
            'canCancelHold' => $canCancelHold,
        ]);
    }

    /**
     * Retry payment: create a new Stripe Checkout Session for a pending booking
     * and redirect the athlete to the Stripe-hosted checkout page.
     */
    public function retryPayment(
        Request $request,
        Booking $booking,
        PaymentHoldService $paymentHoldService,
    ): RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User && $booking->athlete_id === $user->id, 403);

        try {
            $checkoutSession = $paymentHoldService->retryPayment($booking, $user);
        } catch (AuthorizationException) {
            abort(403);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('bookings.payment-return', [
                    'booking' => $booking->getKey(),
                    'status' => 'failed',
                ])
                ->with('error', $e->getMessage());
        }

        return redirect()->away((string) $checkoutSession->url);
    }

    /**
     * Cancel hold: release the seat reservation and cancel the pending booking.
     */
    public function cancelHold(
        Request $request,
        Booking $booking,
        PaymentHoldService $paymentHoldService,
    ): RedirectResponse {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User && $booking->athlete_id === $user->id, 403);

        try {
            $paymentHoldService->cancelHold($booking, $user);
        } catch (\Throwable) {
            // Booking may have already expired or been cancelled — proceed to redirect.
        }

        return redirect()
            ->route('athlete.dashboard')
            ->with('success', __('bookings.payment_return_cancel_hold_success'));
    }
}
