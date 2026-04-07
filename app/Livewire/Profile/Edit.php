<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Edit extends Component
{
    public string $locale = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->locale = $user->locale ?? 'fr';
    }

    public function updateLocale(): void
    {
        $this->validate([
            'locale' => ['required', 'string', 'in:fr,en,nl'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->update(['locale' => $this->locale]);

        session(['locale' => $this->locale]);
        app()->setLocale($this->locale);

        $this->dispatch('notify', type: 'success', message: __('profile.locale_updated'));
    }

    public function render(): View
    {
        return view('livewire.profile.edit', [
            'user' => Auth::user(),
        ])->title(__('profile.title'));
    }
}
