<?php

declare(strict_types=1);

namespace App\Livewire\Accountant;

use App\Enums\SessionStatus;
use App\Models\SportSession;
use App\Services\SessionService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class StuckSessionsQueue extends Component
{
    public function mount(): void
    {
        // Only accountants and admins may access this queue (gate check at route level,
        // but we guard here too so the component cannot be embedded elsewhere unsafely).
        Gate::authorize('access-accountant-panel');
    }

    /**
     * Returns confirmed sessions whose end time has already passed.
     *
     * Uses the same two-step strategy as CompleteFinishedSessions: narrow by date
     * in the database, then filter the combined date+end_time in PHP to avoid
     * DB-specific datetime functions.
     *
     * @return Collection<int, SportSession>
     */
    #[Computed]
    public function stuckSessions(): Collection
    {
        $now = now();

        return SportSession::query()
            ->with(['coach', 'invoices'])
            ->where('status', SessionStatus::Confirmed)
            ->whereDate('date', '<=', $now->toDateString())
            ->orderBy('date')
            ->orderBy('end_time')
            ->get()
            ->filter(function (SportSession $session) use ($now): bool {
                $sessionEnd = Carbon::parse(
                    $session->date->format('Y-m-d').' '.$session->end_time,
                );

                return $sessionEnd->lte($now);
            })
            ->values();
    }

    /**
     * Manually complete a stuck confirmed session.
     *
     * Guarded by the `manualComplete` policy which ensures:
     *   - the session is confirmed and past its end time
     *   - the acting user is an accountant or admin
     *
     * Invoice generation is idempotent — calling complete() a second time will
     * return the existing invoice without creating a duplicate.
     */
    public function complete(int $sessionId, SessionService $service): void
    {
        $session = SportSession::findOrFail($sessionId);

        Gate::authorize('manualComplete', $session);

        try {
            $service->complete($session);
            $this->dispatch('notify', type: 'success', message: __('accountant.stuck_sessions_completed'));
        } catch (\Throwable $e) {
            Log::error('Manual session completion failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', type: 'error', message: __('accountant.stuck_sessions_complete_error'));
        }

        unset($this->stuckSessions);
    }

    public function render(): View
    {
        return view('livewire.accountant.stuck-sessions-queue');
    }
}
