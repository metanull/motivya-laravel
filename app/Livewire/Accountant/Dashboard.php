<?php

declare(strict_types=1);

namespace App\Livewire\Accountant;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Dashboard extends Component
{
    use WithPagination;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $coachId = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $sortBy = 'issued_at';

    #[Url]
    public string $sortDir = 'desc';

    public function mount(): void
    {
        Gate::authorize('viewAny', Invoice::class);
    }

    /**
     * Trigger a financial export download by navigating to the export route.
     *
     * The browser will download the file and stay on the dashboard because the
     * response has Content-Disposition: attachment.
     */
    public function export(string $format = 'csv'): void
    {
        Gate::authorize('viewAny', Invoice::class);

        $params = array_filter([
            'format' => $format,
            'dateFrom' => $this->dateFrom ?: null,
            'dateTo' => $this->dateTo ?: null,
            'coachId' => $this->coachId ?: null,
            'type' => $this->type ?: null,
        ]);

        $this->redirect(route('accountant.export', $params), navigate: false);
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

    // ─── Summary card metrics (current calendar month) ──────────────────────

    #[Computed]
    public function summaryRevenueTtc(): int
    {
        return (int) Invoice::query()
            ->where('type', InvoiceType::Invoice)
            ->whereMonth('billing_period_start', now()->month)
            ->whereYear('billing_period_start', now()->year)
            ->sum('revenue_ttc');
    }

    #[Computed]
    public function summaryRevenueHtva(): int
    {
        return (int) Invoice::query()
            ->where('type', InvoiceType::Invoice)
            ->whereMonth('billing_period_start', now()->month)
            ->whereYear('billing_period_start', now()->year)
            ->sum('revenue_htva');
    }

    #[Computed]
    public function summaryVat(): int
    {
        return (int) Invoice::query()
            ->where('type', InvoiceType::Invoice)
            ->whereMonth('billing_period_start', now()->month)
            ->whereYear('billing_period_start', now()->year)
            ->sum('vat_amount');
    }

    /** Sum of coach_payout on all unpaid invoices (any period). */
    #[Computed]
    public function summaryPayoutPending(): int
    {
        return (int) Invoice::query()
            ->where('status', '!=', InvoiceStatus::Paid)
            ->sum('coach_payout');
    }

    #[Computed]
    public function summaryInvoicesCount(): int
    {
        return Invoice::query()
            ->where('type', InvoiceType::Invoice)
            ->whereMonth('billing_period_start', now()->month)
            ->whereYear('billing_period_start', now()->year)
            ->count();
    }

    #[Computed]
    public function summaryCreditNotesCount(): int
    {
        return Invoice::query()
            ->where('type', InvoiceType::CreditNote)
            ->whereMonth('billing_period_start', now()->month)
            ->whereYear('billing_period_start', now()->year)
            ->count();
    }

    #[Computed]
    public function summaryRefundsCount(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Refunded)
            ->whereMonth('refunded_at', now()->month)
            ->whereYear('refunded_at', now()->year)
            ->count();
    }

    /**
     * Count confirmed sessions whose end time has already passed (stuck sessions).
     * Replicates the query logic from StuckSessionsQueue for the summary card.
     */
    #[Computed]
    public function summaryStuckSessionsCount(): int
    {
        $now = now();

        return SportSession::query()
            ->where('status', SessionStatus::Confirmed)
            ->whereDate('date', '<=', $now->toDateString())
            ->get(['id', 'date', 'end_time'])
            ->filter(function (SportSession $session) use ($now): bool {
                return Carbon::parse(
                    $session->date->format('Y-m-d').' '.$session->end_time,
                )->lte($now);
            })
            ->count();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->coachId = '';
        $this->type = '';
        $this->status = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $invoices = Invoice::query()
            ->with(['coach'])
            ->when($this->dateFrom !== '', fn ($q) => $q->where('billing_period_start', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->where('billing_period_start', '<=', $this->dateTo))
            ->when($this->coachId !== '', fn ($q) => $q->where('coach_id', $this->coachId))
            ->when($this->type !== '', fn ($q) => $q->where('type', InvoiceType::from($this->type)))
            ->when($this->status !== '', fn ($q) => $q->where('status', InvoiceStatus::from($this->status)))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.accountant.dashboard', [
            'invoices' => $invoices,
        ])->title(__('accountant.dashboard_title'));
    }
}
