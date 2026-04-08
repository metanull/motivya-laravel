<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class TwoFactorAuthentication extends Component
{
    public bool $showingQrCode = false;

    public bool $showingConfirmation = false;

    public bool $showingRecoveryCodes = false;

    public string $confirmationCode = '';

    public function enableTwoFactor(): void
    {
        $this->dispatch('two-factor-enabling');
    }

    public function showRecoveryCodes(): void
    {
        $this->showingRecoveryCodes = true;
    }

    public function hideRecoveryCodes(): void
    {
        $this->showingRecoveryCodes = false;
    }

    public function disableTwoFactor(): void
    {
        $this->dispatch('two-factor-disabling');
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.profile.two-factor-authentication', [
            'user' => $user,
            'enabled' => $user->two_factor_confirmed_at !== null,
        ]);
    }
}
