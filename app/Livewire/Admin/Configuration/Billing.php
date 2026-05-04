<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Configuration;

use App\Services\PayoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Billing extends Component
{
    public function mount(): void
    {
        Gate::authorize('access-admin-panel');
    }

    /**
     * Returns the subscription plan definitions sourced from PayoutService.
     *
     * Reading directly from PayoutService ensures this page always reflects
     * the values the payout algorithm actually uses, with no duplication risk.
     *
     * @return array<string, array{commission_rate: int, subscription_fee: int}>
     */
    #[Computed]
    public function plans(): array
    {
        return app(PayoutService::class)->planDefinitions();
    }

    /**
     * Returns the applicable VAT rates by coach VAT status.
     *
     * 21% applies to VAT-subject coaches (standard Belgian rate).
     * 0% applies to coaches operating under the franchise regime (art. 56bis CTVA).
     *
     * @return array<string, int>
     */
    #[Computed]
    public function vatRates(): array
    {
        return [
            'subject' => 21,
            'franchise' => 0,
        ];
    }

    /**
     * Returns the estimated Stripe fee rate as a percentage float.
     *
     * Sourced from deploy-time config (STRIPE_COMMISSION_RATE env var).
     * Defaults to 1.5% when not explicitly configured.
     */
    #[Computed]
    public function stripeFeeRate(): float
    {
        return (float) config('services.stripe.commission_rate', 1.5);
    }

    public function render(): View
    {
        return view('livewire.admin.configuration.billing')
            ->title(__('admin.billing_config_title'));
    }
}
