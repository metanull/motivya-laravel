<?php

declare(strict_types=1);

namespace App\Livewire\Accountant\AuditEvents;

use App\Enums\AuditActorType;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\AuditSource;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Policies\AuditEventPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    // ── Filters ───────────────────────────────────────────────────────────

    #[Url]
    public string $occurredFrom = '';

    #[Url]
    public string $occurredTo = '';

    #[Url]
    public string $eventType = '';

    #[Url]
    public string $operation = '';

    #[Url]
    public string $actorType = '';

    #[Url]
    public string $actorId = '';

    #[Url]
    public string $actorRole = '';

    #[Url]
    public string $source = '';

    #[Url]
    public string $modelType = '';

    #[Url]
    public string $modelId = '';

    #[Url]
    public string $subjectType = '';

    #[Url]
    public string $subjectId = '';

    #[Url]
    public string $subjectRelation = '';

    #[Url]
    public string $requestId = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────

    public function mount(): void
    {
        Gate::authorize('access-accountant-panel');
    }

    // ── Filter helpers ────────────────────────────────────────────────────

    public function updatedEventType(): void
    {
        $this->resetPage();
    }

    public function updatedOperation(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'occurredFrom', 'occurredTo', 'eventType', 'operation',
            'actorType', 'actorId', 'actorRole', 'source',
            'modelType', 'modelId', 'subjectType', 'subjectId',
            'subjectRelation', 'requestId',
        ]);
        $this->resetPage();
    }

    // ── Data ──────────────────────────────────────────────────────────────

    /**
     * Accountants are restricted to financial event types only.
     *
     * @return LengthAwarePaginator<AuditEvent>
     */
    #[Computed]
    public function auditEvents(): LengthAwarePaginator
    {
        $financialValues = array_map(
            fn (AuditEventType $t) => $t->value,
            AuditEventPolicy::financialTypes(),
        );

        return AuditEvent::query()
            ->whereIn('event_type', $financialValues)
            ->when($this->occurredFrom !== '', fn ($q) => $q->where('occurred_at', '>=', $this->occurredFrom))
            ->when($this->occurredTo !== '', fn ($q) => $q->where('occurred_at', '<=', $this->occurredTo.' 23:59:59'))
            ->when(
                $this->eventType !== '' && in_array($this->eventType, $financialValues, true),
                fn ($q) => $q->where('event_type', $this->eventType)
            )
            ->when($this->operation !== '', fn ($q) => $q->where('operation', $this->operation))
            ->when($this->actorType !== '', fn ($q) => $q->where('actor_type', $this->actorType))
            ->when($this->actorId !== '', fn ($q) => $q->where('actor_id', $this->actorId))
            ->when($this->actorRole !== '', fn ($q) => $q->where('actor_role', $this->actorRole))
            ->when($this->source !== '', fn ($q) => $q->where('source', $this->source))
            ->when($this->modelType !== '', fn ($q) => $q->where('model_type', 'like', '%'.$this->modelType.'%'))
            ->when($this->modelId !== '', fn ($q) => $q->where('model_id', $this->modelId))
            ->when(
                $this->subjectType !== '' || $this->subjectId !== '' || $this->subjectRelation !== '',
                function ($q): void {
                    $q->whereHas('subjects', function ($sq): void {
                        $sq->when($this->subjectType !== '', fn ($s) => $s->where('subject_type', 'like', '%'.$this->subjectType.'%'))
                            ->when($this->subjectId !== '', fn ($s) => $s->where('subject_id', $this->subjectId))
                            ->when($this->subjectRelation !== '', fn ($s) => $s->where('relation', $this->subjectRelation));
                    });
                }
            )
            ->when($this->requestId !== '', fn ($q) => $q->where('request_id', $this->requestId))
            ->withCount('subjects')
            ->orderByDesc('occurred_at')
            ->paginate(25);
    }

    /**
     * Only financial event types are exposed to accountants.
     *
     * @return list<AuditEventType>
     */
    public function eventTypeOptions(): array
    {
        return AuditEventPolicy::financialTypes();
    }

    /**
     * @return list<AuditOperation>
     */
    public function operationOptions(): array
    {
        return AuditOperation::cases();
    }

    /**
     * @return list<AuditActorType>
     */
    public function actorTypeOptions(): array
    {
        return AuditActorType::cases();
    }

    /**
     * @return list<UserRole>
     */
    public function actorRoleOptions(): array
    {
        return UserRole::cases();
    }

    /**
     * @return list<AuditSource>
     */
    public function sourceOptions(): array
    {
        return AuditSource::cases();
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.accountant.audit-events.index')
            ->title(__('accountant.audit_events_title'));
    }
}
