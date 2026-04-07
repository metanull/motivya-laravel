<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class ForgotPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public bool $linkSent = false;

    public function sendResetLink(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->linkSent = true;
            $this->dispatch('notify', type: 'success', message: __('auth.forgot_link_sent'));
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password')
            ->title(__('auth.forgot_title'));
    }
}
