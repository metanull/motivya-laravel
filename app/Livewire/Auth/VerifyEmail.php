<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class VerifyEmail extends Component
{
    public bool $resent = false;

    public function resend(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirect(route('home'), navigate: true);

            return;
        }

        $user->sendEmailVerificationNotification();
        $this->resent = true;
        $this->dispatch('notify', type: 'success', message: __('auth.verify_resent'));
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('home'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.verify-email')
            ->title(__('auth.verify_title'));
    }
}
