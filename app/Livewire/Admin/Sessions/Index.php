<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Sessions;

use App\Enums\ActivityType;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\SportSession;
use App\Services\Audit\AuditService;
use App\Services\SessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    // ── Filter properties ─────────────────────────────────────────────────

    public string $status = '';

    public string $coachSearch = '';

    public string $activityType = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $pendingPaymentOnly = false;

    public bool $pastEndTimeOnly = false;

    // ── Action state ──────────────────────────────────────────────────────

    public ?int $cancellingSessionId = null;

    public string $cancelReason = '';

    public ?int $completingSessionId = null;

    // ── Lifecycle ─────────────────────────────────────────────────────────

    public function mount(): void
    {
        Gate::authorize('access-admin-panel');
    }

    // ── Filter updated hooks (reset pagination on any change) ─────────────

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedCoachSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActivityType(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedPendingPaymentOnly(): void
    {
        $this->resetPage();
    }

    public function updatedPastEndTimeOnly(): void
    {
        $this->resetPage();
    }

    /**
     * Reset all active filters and return to the first page.
     */
    public function resetFilters(): void
    {
        $this->status = '';
        $this->coachSearch = '';
        $this->activityType = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->pendingPaymentOnly = false;
        $this->pastEndTimeOnly = false;
        $this->resetPage();
    }

    // ── Cancel action ─────────────────────────────────────────────────────

    /**
     * Open the inline cancel form for a given session.
     */
    public function confirmCancel(int $sessionId): void
    {
        $this->cancellingSessionId = $sessionId;
        $this->cancelReason = '';
    }

    /**
     * Dismiss the inline cancel form without performing any action.
     */
    public function cancelCancelAction(): void
    {
        $this->cancellingSessionId = null;
        $this->cancelReason = '';
    }

    /**
     * Cancel a published or confirmed session as an admin.
     *
     * Validation is performed before any DB interaction so that field-level
     * errors surface in the inline form. The service call is wrapped in a
     * try/catch so that unexpected failures dispatch an error notification
     * rather than rendering an unhandled exception page.
     */
    public function cancelSession(int $sessionId, SessionService $service, AuditService $auditService): void
    {
        $this->validate([
            'cancelReason' => ['required', 'string', 'max:1000'],
        ]);

        $session = SportSession::findOrFail($sessionId);

        try {
            if (! in_array($session->status, [SessionStatus::Published, SessionStatus::Confirmed], true)) {
                throw new InvalidArgumentException('Only published or confirmed sessions can be cancelled.');
            }

            $service->cancel($session);

            $auditService->record(
                AuditEventType::SessionCancelled,
                AuditOperation::StateChange,
                $session,
                metadata: ['reason' => $this->cancelReason, 'admin_id' => auth()->id()],
            );

            $this->cancellingSessionId = null;
            $this->cancelReason = '';

            $this->dispatch('notify', type: 'success', message: __('admin.sessions_cancelled_success'));
        } catch (\Throwable $e) {
            Log::error('Admin session cancellation failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: __('admin.sessions_cancelled_error'));
        }
    }

    // ── Complete action ───────────────────────────────────────────────────

    /**
     * Manually mark a past confirmed session as completed.
     *
     * Both the "wrong status" and "not past end time" checks throw inside the
     * try/catch so the error notification is always dispatched on failure.
     */
    public function completeSession(int $sessionId, SessionService $service, AuditService $auditService): void
    {
        $session = SportSession::findOrFail($sessionId);

        try {
            if ($session->status !== SessionStatus::Confirmed) {
                throw new InvalidArgumentException('Only confirmed sessions can be completed.');
            }

            if (! $session->hasEnded()) {
                throw new InvalidArgumentException('Session has not ended yet.');
            }

            $service->complete($session);

            $auditService->record(
                AuditEventType::SessionCompleted,
                AuditOperation::StateChange,
                $session,
                metadata: ['admin_id' => auth()->id()],
            );

            $this->dispatch('notify', type: 'success', message: __('admin.sessions_completed_success'));
        } catch (\Throwable $e) {
            Log::error('Admin session completion failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: __('admin.sessions_completed_error'));
        }
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        $now = now();

        $baseQuery = SportSession::query()
            ->with(['coach.coachProfile'])
            ->withCount([
                'bookings as bookings_confirmed_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed),
                'bookings as bookings_pending_count' => fn ($q) => $q->where('status', BookingStatus::PendingPayment),
            ])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when(
                $this->coachSearch !== '',
                fn ($q) => $q->whereHas('coach', fn ($inner) => $inner->where('name', 'like', "%{$this->coachSearch}%")),
            )
            ->when($this->activityType !== '', fn ($q) => $q->where('activity_type', $this->activityType))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when(
                $this->pendingPaymentOnly,
                fn ($q) => $q->whereHas('bookings', fn ($inner) => $inner->where('status', BookingStatus::PendingPayment)),
            )
            ->orderByDesc('date');

        if ($this->pastEndTimeOnly) {
            // Narrow by date in SQL, then filter combined date+end_time in PHP to
            // avoid DB-specific datetime arithmetic (mirrors CompleteFinishedSessions).
            $all = $baseQuery
                ->whereDate('date', '<=', $now->toDateString())
                ->get()
                ->filter(fn (SportSession $session): bool => $session->hasEnded())
                ->values();

            $perPage = 10;
            $page = $this->getPage();

            $sessions = new LengthAwarePaginator(
                $all->forPage($page, $perPage),
                $all->count(),
                $perPage,
                $page,
                ['path' => Paginator::resolveCurrentPath()],
            );
        } else {
            $sessions = $baseQuery->paginate(10);
        }

        return view('livewire.admin.sessions.index', [
            'sessions' => $sessions,
            'statuses' => SessionStatus::cases(),
            'activityTypes' => ActivityType::cases(),
        ])->title(__('admin.sessions_title'));
    }
}
