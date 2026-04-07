<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password')
            ->title(__('auth.reset_title'));
    }
}
