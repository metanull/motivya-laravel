<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Login extends Component
{
    public function render(): View
    {
        return view('livewire.auth.login')
            ->title(__('auth.login_title'));
    }
}
