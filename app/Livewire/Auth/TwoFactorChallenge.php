<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Enums\TwoFactorMethod;
use App\Models\User;
use App\Services\EmailTwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class TwoFactorChallenge extends Component
{
    public bool $useRecoveryCode = false;

    public string $code = '';

    public string $recoveryCode = '';

    public string $method = 'totp';

    public bool $codeResent = false;

    public function mount(): void
    {
        $this->method = session('login.two_factor_method', TwoFactorMethod::Totp->value);
    }

    public function toggleRecoveryCode(): void
    {
        $this->useRecoveryCode = ! $this->useRecoveryCode;
        $this->codeResent = false;
    }

    public function verifyEmailCode(): void
    {
        $this->codeResent = false;

        $userId = session('login.id');
        if ($userId === null) {
            $this->redirect(route('login'));

            return;
        }

        $user = User::find($userId);
        if ($user === null) {
            Log::warning('Email 2FA challenge: user not found', ['user_id' => $userId]);
            $this->redirect(route('login'));

            return;
        }

        $service = app(EmailTwoFactorService::class);

        if (! $service->verify($user, $this->code)) {
            $this->addError('code', __('auth.two_factor_email_invalid_code'));

            return;
        }

        Auth::login($user, session('login.remember', false));
        session()->forget(['login.id', 'login.remember', 'login.two_factor_method']);

        $this->redirect(config('fortify.home'));
    }

    public function verifyRecoveryCode(): void
    {
        $this->codeResent = false;

        $userId = session('login.id');
        if ($userId === null) {
            $this->redirect(route('login'));

            return;
        }

        $user = User::find($userId);
        if ($user === null) {
            Log::warning('Email 2FA recovery: user not found', ['user_id' => $userId]);
            $this->redirect(route('login'));

            return;
        }

        $codes = $user->recoveryCodes();
        $matchedCode = collect($codes)->first(fn (string $c): bool => hash_equals($c, $this->recoveryCode));

        if ($matchedCode === null) {
            $this->addError('recovery_code', __('auth.two_factor_invalid_recovery_code'));

            return;
        }

        $user->replaceRecoveryCode($matchedCode);

        Auth::login($user, session('login.remember', false));
        session()->forget(['login.id', 'login.remember', 'login.two_factor_method']);

        $this->redirect(config('fortify.home'));
    }

    public function resendCode(): void
    {
        $userId = session('login.id');
        if ($userId === null) {
            $this->redirect(route('login'));

            return;
        }

        $user = User::find($userId);
        if ($user === null) {
            $this->redirect(route('login'));

            return;
        }

        app(EmailTwoFactorService::class)->generateAndSendCode($user);
        $this->codeResent = true;
    }

    public function render(): View
    {
        return view('livewire.auth.two-factor-challenge')
            ->title(__('auth.two_factor_title'));
    }
}
