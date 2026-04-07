<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Register extends Component
{
    public function render(): View
    {
        return view('livewire.auth.register')
            ->title(__('auth.register_title'));
    }
}
