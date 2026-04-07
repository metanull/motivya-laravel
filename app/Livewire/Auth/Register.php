<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Livewire\Forms\RegisterForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Livewire\Component;

final class Register extends Component
{
    public RegisterForm $form;

    public function register(CreatesNewUsers $creator): void
    {
        $this->form->validate();

        $user = $creator->create([
            'name' => $this->form->name,
            'email' => $this->form->email,
            'password' => $this->form->password,
            'password_confirmation' => $this->form->password_confirmation,
        ]);

        Auth::login($user);

        $this->redirect(route('home'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.register')
            ->title(__('auth.register_title'));
    }
}
