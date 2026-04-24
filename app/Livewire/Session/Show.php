<?php

declare(strict_types=1);

namespace App\Livewire\Session;

use App\Models\SportSession;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Show extends Component
{
    public SportSession $sportSession;

    public function mount(SportSession $sportSession): void
    {
        Gate::authorize('view', $sportSession);

        $this->sportSession = $sportSession->load(['coach', 'coverImage']);
    }

    public function toggleFavourite(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        Gate::authorize('favourite', $this->sportSession);

        $user->favouriteSessions()->toggle($this->sportSession->getKey());

        $isFavourited = $user->favouriteSessions()
            ->where('sport_session_id', $this->sportSession->getKey())
            ->exists();

        $this->dispatch(
            'notify',
            type: 'success',
            message: $isFavourited
                ? __('athlete.favourite_added')
                : __('athlete.favourite_removed'),
        );
    }

    public function render(): View
    {
        $spotsRemaining = $this->sportSession->max_participants - $this->sportSession->current_participants;

        $user = auth()->user();
        $isFavourited = $user instanceof User && $user->can('favourite', $this->sportSession)
            ? $user->favouriteSessions()
                ->where('sport_session_id', $this->sportSession->getKey())
                ->exists()
            : false;

        return view('livewire.session.show', [
            'spotsRemaining' => $spotsRemaining,
            'isFavourited' => $isFavourited,
        ])->title($this->sportSession->title);
    }
}
