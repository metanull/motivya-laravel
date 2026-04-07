<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Livewire\Forms\LoginForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class Login extends Component
{
    public LoginForm $form;

    public function authenticate(): void
    {
        $this->form->validate();

        if (! Auth::attempt(
            ['email' => $this->form->email, 'password' => $this->form->password],
            $this->form->remember,
        )) {
            throw ValidationException::withMessages([
                'form.email' => __('auth.failed'),
            ]);
        }

        session()->regenerate();

        $this->redirect(route('home'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.login')
            ->title(__('auth.login_title'));
    }
}
