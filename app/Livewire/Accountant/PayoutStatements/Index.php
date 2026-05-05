<?php

declare(strict_types=1);

namespace App\Livewire\Accountant\PayoutStatements;

use App\Enums\CoachPayoutStatementStatus;
use App\Enums\UserRole;
use App\Models\CoachPayoutStatement;
use App\Models\User;
use App\Services\CoachPayoutStatementService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterCoach = '';

    public ?int $blockingStatementId = null;

    public string $blockReason = '';

    public function mount(): void
    {
        Gate::authorize('access-accountant-panel');
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

    /**
     * @return LengthAwarePaginator<CoachPayoutStatement>
     */
    #[Computed]
    public function statements(): LengthAwarePaginator
    {
        return CoachPayoutStatement::with(['coach'])
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', CoachPayoutStatementStatus::from($this->filterStatus)))
            ->when($this->filterCoach !== '', fn ($q) => $q->where('coach_id', $this->filterCoach))
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate(20);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function statusOptions(): array
    {
        return array_map(
            fn (CoachPayoutStatementStatus $s) => ['value' => $s->value, 'label' => $s->label()],
            CoachPayoutStatementStatus::cases(),
        );
    }

    public function approve(int $statementId, CoachPayoutStatementService $service): void
    {
        Gate::authorize('access-accountant-panel');

        /** @var User $approver */
        $approver = auth()->user();
        $statement = CoachPayoutStatement::findOrFail($statementId);

        try {
            $service->approve($statement, $approver);
            $this->dispatch('notify', type: 'success', message: __('accountant.payout_statements_approve_success'));
        } catch (InvalidArgumentException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }

        unset($this->statements);
    }

    public function openBlockModal(int $statementId): void
    {
        $this->blockingStatementId = $statementId;
        $this->blockReason = '';
    }

    public function block(int $statementId, CoachPayoutStatementService $service): void
    {
        Gate::authorize('access-accountant-panel');

        $this->validate(['blockReason' => 'required|string|min:3']);

        /** @var User $approver */
        $approver = auth()->user();
        $statement = CoachPayoutStatement::findOrFail($statementId);

        try {
            $service->block($statement, $approver, $this->blockReason);
            $this->dispatch('notify', type: 'success', message: __('accountant.payout_statements_block_success'));
        } catch (InvalidArgumentException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }

        $this->blockingStatementId = null;
        $this->blockReason = '';
        unset($this->statements);
    }

    public function markPaid(int $statementId, CoachPayoutStatementService $service): void
    {
        Gate::authorize('access-accountant-panel');

        /** @var User $approver */
        $approver = auth()->user();
        $statement = CoachPayoutStatement::findOrFail($statementId);

        try {
            $service->markPaid($statement, $approver);
            $this->dispatch('notify', type: 'success', message: __('accountant.payout_statements_paid_success'));
        } catch (InvalidArgumentException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }

        unset($this->statements);
    }

    public function resetFilters(): void
    {
        $this->filterStatus = '';
        $this->filterCoach = '';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.accountant.payout-statements.index')
            ->title(__('accountant.payout_statements_title'));
    }
}
