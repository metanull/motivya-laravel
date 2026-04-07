<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ForgotPassword extends Component
{
    public function render(): View
    {
        return view('livewire.auth.forgot-password', [
            'status' => session('status'),
        ])->title(__('auth.forgot_title'));
    }
}
