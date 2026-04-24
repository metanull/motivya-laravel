<?php

declare(strict_types=1);

namespace App\Livewire\Athlete;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Dashboard extends Component
{
    public string $tab = 'upcoming';

    public function render(): View
    {
        /** @var User $athlete */
        $athlete = auth()->user();

        $upcoming = Booking::query()
            ->where('athlete_id', $athlete->id)
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
            ->whereHas('sportSession', fn ($q) => $q->where('date', '>=', now()->toDateString()))
            ->with(['sportSession', 'sportSession.coach'])
            ->get()
            ->sortBy(fn (Booking $b) => $b->sportSession->date);

        $past = Booking::query()
            ->where('athlete_id', $athlete->id)
            ->where(function ($q): void {
                $q->whereIn('status', [BookingStatus::Cancelled, BookingStatus::Refunded])
                    ->orWhereHas('sportSession', fn ($q2) => $q2->where('date', '<', now()->toDateString()));
            })
            ->with(['sportSession', 'sportSession.coach'])
            ->get()
            ->sortByDesc(fn (Booking $b) => $b->sportSession->date)
            ->take(20);

        return view('livewire.athlete.dashboard', [
            'upcoming' => $upcoming,
            'past' => $past,
        ])->title(__('athlete.dashboard_title'));
    }
}
