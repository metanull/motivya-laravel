<?php

declare(strict_types=1);

namespace App\Livewire\Session;

use App\Models\SportSession;
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

    public function render(): View
    {
        $spotsRemaining = $this->sportSession->max_participants - $this->sportSession->current_participants;

        return view('livewire.session.show', [
            'spotsRemaining' => $spotsRemaining,
        ])->title($this->sportSession->title);
    }
}
