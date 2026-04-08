<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class TwoFactorChallenge extends Component
{
    public bool $useRecoveryCode = false;

    public function toggleRecoveryCode(): void
    {
        $this->useRecoveryCode = ! $this->useRecoveryCode;
    }

    public function render(): View
    {
        return view('livewire.auth.two-factor-challenge')
            ->title(__('auth.two_factor_title'));
    }
}
