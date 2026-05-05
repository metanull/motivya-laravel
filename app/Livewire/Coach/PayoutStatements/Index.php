<?php

declare(strict_types=1);

namespace App\Livewire\Coach\PayoutStatements;

use App\Models\CoachPayoutStatement;
use App\Models\User;
use App\Services\CoachPayoutStatementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('access-coach-panel');
    }

    /**
     * @return Collection<int, CoachPayoutStatement>
     */
    #[Computed]
    public function statements(): Collection
    {
        /** @var User $coach */
        $coach = auth()->user();

        return CoachPayoutStatement::where('coach_id', $coach->id)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit(24)
            ->get();
    }

    /**
     * Coach requests payout for a draft statement.
     */
    public function requestPayout(int $statementId, CoachPayoutStatementService $service): void
    {
        /** @var User $coach */
        $coach = auth()->user();

        /** @var CoachPayoutStatement $statement */
        $statement = CoachPayoutStatement::where('id', $statementId)
            ->where('coach_id', $coach->id)
            ->firstOrFail();

        try {
            $service->requestPayout($statement);
            $this->dispatch('notify', type: 'success', message: __('coach.payout_statement_request_success'));
        } catch (InvalidArgumentException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }

        unset($this->statements);
    }

    /**
     * Coach marks invoice as submitted for a ready_for_invoice statement.
     */
    public function markInvoiceSubmitted(int $statementId, CoachPayoutStatementService $service): void
    {
        /** @var User $coach */
        $coach = auth()->user();

        /** @var CoachPayoutStatement $statement */
        $statement = CoachPayoutStatement::where('id', $statementId)
            ->where('coach_id', $coach->id)
            ->firstOrFail();

        try {
            $service->markInvoiceSubmitted($statement);
            $this->dispatch('notify', type: 'success', message: __('coach.payout_statement_submit_success'));
        } catch (InvalidArgumentException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }

        unset($this->statements);
    }

    public function render(): View
    {
        return view('livewire.coach.payout-statements.index')
            ->title(__('coach.payout_statement_heading'));
    }
}
