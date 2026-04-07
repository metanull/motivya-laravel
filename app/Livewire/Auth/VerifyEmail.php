<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class VerifyEmail extends Component
{
    public function render(): View
    {
        return view('livewire.auth.verify-email', [
            'status' => session('status'),
        ])->title(__('auth.verify_title'));
    }
}
