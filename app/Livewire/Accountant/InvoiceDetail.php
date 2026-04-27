<?php

declare(strict_types=1);

namespace App\Livewire\Accountant;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class InvoiceDetail extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        Gate::authorize('view', $invoice);

        $this->invoice = $invoice;
    }

    /**
     * Returns the expected commission rate (%) for the applied plan.
     */
    #[Computed]
    public function expectedCommissionRate(): int
    {
        return match ($this->invoice->plan_applied) {
            'active' => 20,
            'premium' => 10,
            default => 30,
        };
    }

    /**
     * Returns the expected subscription fee (cents) for the applied plan.
     */
    #[Computed]
    public function expectedSubscriptionFee(): int
    {
        return match ($this->invoice->plan_applied) {
            'active' => 3900,
            'premium' => 7900,
            default => 0,
        };
    }

    /**
     * Recomputes expected values from the stored revenue_ttc and applied plan,
     * returning an array of field names that do not match the stored values.
     *
     * @return array<string, bool> Map of field → true when a discrepancy exists.
     */
    #[Computed]
    public function discrepancies(): array
    {
        $revenueTtc = $this->invoice->revenue_ttc;
        $revenueHtva = intdiv($revenueTtc * 100 + 60, 121);

        $expectedStripeFee = (int) round($revenueTtc * 15 / 1000);
        $expectedCommissionRate = $this->expectedCommissionRate;
        $expectedCommissionAmt = (int) round($revenueHtva * $expectedCommissionRate / 100);
        $expectedSubscriptionFee = $this->expectedSubscriptionFee;
        $expectedCoachPayout = $revenueHtva - $expectedCommissionAmt - $expectedStripeFee - $expectedSubscriptionFee;
        $expectedPlatformMargin = $expectedCommissionAmt;

        return [
            'revenue_htva' => $this->invoice->revenue_htva !== $revenueHtva,
            'stripe_fee' => $this->invoice->stripe_fee !== $expectedStripeFee,
            'subscription_fee' => $this->invoice->subscription_fee !== $expectedSubscriptionFee,
            'commission_amount' => $this->invoice->commission_amount !== $expectedCommissionAmt,
            'coach_payout' => $this->invoice->coach_payout !== $expectedCoachPayout,
            'platform_margin' => $this->invoice->platform_margin !== $expectedPlatformMargin,
        ];
    }

    /**
     * Returns true when at least one stored value differs from the expected formula.
     */
    #[Computed]
    public function hasDiscrepancies(): bool
    {
        return in_array(true, $this->discrepancies, strict: true);
    }

    public function render(): View
    {
        return view('livewire.accountant.invoice-detail', [
            'invoice' => $this->invoice->load('coach'),
        ])->title(__('accountant.detail_title', ['number' => $this->invoice->invoice_number]));
    }
}
