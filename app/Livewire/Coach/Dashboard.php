<?php

declare(strict_types=1);

namespace App\Livewire\Coach;

use App\Enums\SessionStatus;
use App\Models\SportSession;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Dashboard extends Component
{
    public string $tab = 'upcoming';

    public function publishSession(int $sessionId, SessionService $service): void
    {
        $session = SportSession::findOrFail($sessionId);
        Gate::authorize('update', $session);

        $service->publish($session);

        $this->dispatch('notify', type: 'success', message: __('sessions.published'));
    }

    public function cancelSession(int $sessionId, SessionService $service): void
    {
        $session = SportSession::findOrFail($sessionId);
        Gate::authorize('cancel', $session);

        $service->cancel($session);

        $this->dispatch('notify', type: 'success', message: __('sessions.cancelled'));
    }

    public function deleteSession(int $sessionId, SessionService $service): void
    {
        $session = SportSession::findOrFail($sessionId);
        Gate::authorize('delete', $session);

        $service->delete($session);

        $this->dispatch('notify', type: 'success', message: __('sessions.deleted'));
    }

    public function render(): View
    {
        /** @var User $coach */
        $coach = auth()->user();

        $upcoming = SportSession::where('coach_id', $coach->id)
            ->whereIn('status', [SessionStatus::Published, SessionStatus::Confirmed])
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $drafts = SportSession::where('coach_id', $coach->id)
            ->where('status', SessionStatus::Draft)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $past = SportSession::where('coach_id', $coach->id)
            ->whereIn('status', [SessionStatus::Completed, SessionStatus::Cancelled])
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->limit(20)
            ->get();

        return view('livewire.coach.dashboard', [
            'upcoming' => $upcoming,
            'drafts' => $drafts,
            'past' => $past,
        ])->title(__('coach.dashboard_title'));
    }
}
