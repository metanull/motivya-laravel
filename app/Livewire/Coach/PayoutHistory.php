<?php

declare(strict_types=1);

namespace App\Livewire\Coach;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class PayoutHistory extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $sortBy = 'billing_period_start';

    #[Url]
    public string $sortDir = 'desc';

    public function mount(): void
    {
        Gate::authorize('viewAny', Invoice::class);
    }

    /** @var list<string> */
    private const SORTABLE_COLUMNS = ['invoice_number', 'billing_period_start', 'revenue_htva', 'coach_payout'];

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            return;
        }

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
        $this->status = '';
        $this->resetPage();
    }

    public function downloadXml(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);

        Gate::authorize('view', $invoice);

        if ($invoice->xml_path === null) {
            return;
        }

        try {
            $url = Storage::temporaryUrl($invoice->xml_path, now()->addMinutes(5));
        } catch (\RuntimeException) {
            $url = Storage::url($invoice->xml_path);
        }

        $this->redirect($url, navigate: false);
    }

    public function render(): View
    {
        /** @var User $coach */
        $coach = auth()->user();

        $invoices = Invoice::query()
            ->where('coach_id', $coach->id)
            ->when($this->status !== '', fn ($q) => $q->where('status', InvoiceStatus::from($this->status)))
            ->orderBy(
                in_array($this->sortBy, self::SORTABLE_COLUMNS, true) ? $this->sortBy : 'billing_period_start',
                $this->sortDir === 'asc' ? 'asc' : 'desc',
            )
            ->paginate(20);

        return view('livewire.coach.payout-history', [
            'invoices' => $invoices,
        ])->title(__('coach.payout_history_title'));
    }
}
