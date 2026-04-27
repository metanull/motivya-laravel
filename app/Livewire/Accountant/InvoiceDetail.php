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
    /**
     * Commission rate (%) and subscription fee (cents) keyed by plan name.
     *
     * Mirrors the tier definitions in PayoutService so the accountant view
     * can independently verify stored values against the expected formula.
     *
     * @var array<string, array{commission_rate: int, subscription_fee: int}>
     */
    private const PLANS = [
        'freemium' => ['commission_rate' => 30, 'subscription_fee' => 0],
        'active' => ['commission_rate' => 20, 'subscription_fee' => 3900],
        'premium' => ['commission_rate' => 10, 'subscription_fee' => 7900],
    ];

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
        return self::PLANS[$this->invoice->plan_applied ?? 'freemium']['commission_rate'] ?? 30;
    }

    /**
     * Returns the expected subscription fee (cents) for the applied plan.
     */
    #[Computed]
    public function expectedSubscriptionFee(): int
    {
        return self::PLANS[$this->invoice->plan_applied ?? 'freemium']['subscription_fee'] ?? 0;
    }

    /**
     * Returns the expected revenue HTVA (cents) derived from the stored TTC amount.
     *
     * Uses the Belgian standard VAT rate of 21%: intdiv(ttc * 100 + 60, 121).
     */
    #[Computed]
    public function expectedRevenueHtva(): int
    {
        return intdiv($this->invoice->revenue_ttc * 100 + 60, 121);
    }

    /**
     * Returns the expected Stripe fee (cents): 1.5% of revenue TTC, rounded half-up.
     */
    #[Computed]
    public function expectedStripeFee(): int
    {
        return (int) round($this->invoice->revenue_ttc * 15 / 1000);
    }

    /**
     * Returns the expected commission amount (cents) based on HTVA and the plan rate.
     */
    #[Computed]
    public function expectedCommissionAmount(): int
    {
        return (int) round($this->expectedRevenueHtva * $this->expectedCommissionRate / 100);
    }

    /**
     * Returns the expected coach payout (cents) after all deductions.
     */
    #[Computed]
    public function expectedCoachPayout(): int
    {
        return $this->expectedRevenueHtva
            - $this->expectedCommissionAmount
            - $this->expectedStripeFee
            - $this->expectedSubscriptionFee;
    }

    /**
     * Returns the expected platform margin (cents): equals the commission amount.
     */
    #[Computed]
    public function expectedPlatformMargin(): int
    {
        return $this->expectedCommissionAmount;
    }

    /**
     * Compares each stored value against the expected formula result.
     *
     * @return array<string, bool> Map of field name → true when a discrepancy exists.
     */
    #[Computed]
    public function discrepancies(): array
    {
        return [
            'revenue_htva' => $this->invoice->revenue_htva !== $this->expectedRevenueHtva,
            'stripe_fee' => $this->invoice->stripe_fee !== $this->expectedStripeFee,
            'subscription_fee' => $this->invoice->subscription_fee !== $this->expectedSubscriptionFee,
            'commission_amount' => $this->invoice->commission_amount !== $this->expectedCommissionAmount,
            'coach_payout' => $this->invoice->coach_payout !== $this->expectedCoachPayout,
            'platform_margin' => $this->invoice->platform_margin !== $this->expectedPlatformMargin,
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
