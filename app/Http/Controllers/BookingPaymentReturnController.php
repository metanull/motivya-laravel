<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BookingPaymentReturnController extends Controller
{
    public function __invoke(Request $request, BookingService $bookingService): View
    {
        $booking = Booking::with('sportSession')->findOrFail($request->integer('booking'));
        $user = $request->user();

        abort_unless($user instanceof User && $booking->athlete_id === $user->id, 403);

        $paymentStatus = $request->string('status')->value();

        if ($paymentStatus === 'cancel' && $booking->status === BookingStatus::PendingPayment) {
            try {
                $bookingService->cancel($booking, $user);
            } catch (\Throwable) {
                // Booking may have already expired or been cancelled — proceed to show the page.
            }
            $booking->refresh()->load('sportSession');
        }

        return view('bookings.payment-return', [
            'booking' => $booking,
            'paymentStatus' => $paymentStatus,
        ]);
    }
}
