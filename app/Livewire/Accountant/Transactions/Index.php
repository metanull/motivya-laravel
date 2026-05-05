<?php

declare(strict_types=1);

namespace App\Livewire\Accountant\Transactions;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AnomalyDetectorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $coachId = '';

    #[Url]
    public string $sessionStatus = '';

    #[Url]
    public string $bookingStatus = '';

    /** Anomaly flag filter — 'anomalies_only' shows only bookings with a computed anomaly flag. */
    #[Url]
    public string $anomalyFlag = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Invoice::class);
    }

    /**
     * Trigger a ledger export download by navigating to the export route.
     * The browser downloads the file and stays on the page.
     */
    public function export(string $format = 'csv'): void
    {
        Gate::authorize('viewAny', Invoice::class);

        $params = array_filter([
            'format' => $format,
            'dateFrom' => $this->dateFrom ?: null,
            'dateTo' => $this->dateTo ?: null,
            'coachId' => $this->coachId ?: null,
            'sessionStatus' => $this->sessionStatus ?: null,
            'bookingStatus' => $this->bookingStatus ?: null,
        ]);

        $this->redirect(route('accountant.transactions.export', $params), navigate: false);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function coaches(): Collection
    {
        return User::where('role', UserRole::Coach)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function resetFilters(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->coachId = '';
        $this->sessionStatus = '';
        $this->bookingStatus = '';
        $this->anomalyFlag = '';
        $this->resetPage();
    }

    public function render(AnomalyDetectorService $anomalyDetector): View
    {
        $bookings = Booking::query()
            ->with(['sportSession.coach', 'athlete', 'sportSession.invoices'])
            ->when(
                $this->dateFrom !== '',
                fn ($q) => $q->where('bookings.created_at', '>=', $this->dateFrom.' 00:00:00'),
            )
            ->when(
                $this->dateTo !== '',
                fn ($q) => $q->where('bookings.created_at', '<=', $this->dateTo.' 23:59:59'),
            )
            ->when(
                $this->coachId !== '',
                fn ($q) => $q->whereHas(
                    'sportSession',
                    fn ($sq) => $sq->where('coach_id', $this->coachId),
                ),
            )
            ->when(
                $this->sessionStatus !== '',
                fn ($q) => $q->whereHas(
                    'sportSession',
                    fn ($sq) => $sq->where('status', SessionStatus::from($this->sessionStatus)),
                ),
            )
            ->when(
                $this->bookingStatus !== '',
                fn ($q) => $q->where('status', BookingStatus::from($this->bookingStatus)),
            )
            ->when(
                $this->anomalyFlag === 'anomalies_only',
                // Story 1.5: Filter to bookings that have at least one known anomaly flag.
                fn ($q) => $q->where(function ($q): void {
                    // Confirmed + paid + missing payment intent
                    $q->where(fn ($s) => $s
                        ->where('status', BookingStatus::Confirmed->value)
                        ->where('amount_paid', '>', 0)
                        ->whereNull('stripe_payment_intent_id'),
                    )
                    // Confirmed + amount_paid = 0
                        ->orWhere(fn ($s) => $s
                            ->where('status', BookingStatus::Confirmed->value)
                            ->where('amount_paid', '<=', 0),
                        )
                    // Cancelled + paid + no refund
                        ->orWhere(fn ($s) => $s
                            ->where('status', BookingStatus::Cancelled->value)
                            ->where('amount_paid', '>', 0)
                            ->whereNull('refunded_at'),
                        );
                }),
            )
            ->orderBy('bookings.created_at', 'desc')
            ->paginate(25);

        // Story 1.5: Compute anomaly flags for each booking on the current page.
        $bookingFlags = $bookings->getCollection()->mapWithKeys(
            fn (Booking $booking): array => [$booking->id => $anomalyDetector->classifyBooking($booking)],
        );

        return view('livewire.accountant.transactions.index', [
            'bookings' => $bookings,
            'bookingFlags' => $bookingFlags,
        ])->title(__('accountant.transactions_title'));
    }
}
