<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Enums\TwoFactorMethod;
use App\Models\User;
use App\Services\EmailTwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\RecoveryCode;
use Livewire\Component;

final class TwoFactorAuthentication extends Component
{
    public bool $showingQrCode = false;

    public bool $showingConfirmation = false;

    public bool $showingRecoveryCodes = false;

    public string $confirmationCode = '';

    public bool $enablingEmail = false;

    public string $emailCode = '';

    public bool $emailCodeResent = false;

    public function enableTwoFactor(): void
    {
        $this->dispatch('two-factor-enabling');
    }

    public function showRecoveryCodes(): void
    {
        $this->showingRecoveryCodes = true;
    }

    public function hideRecoveryCodes(): void
    {
        $this->showingRecoveryCodes = false;
    }

    public function disableTwoFactor(): void
    {
        $this->dispatch('two-factor-disabling');
    }

    public function enableEmailTwoFactor(): void
    {
        /** @var User $user */
        $user = Auth::user();

        app(EmailTwoFactorService::class)->generateAndSendCode($user);

        $this->enablingEmail = true;
        $this->emailCode = '';
        $this->emailCodeResent = false;
    }

    public function confirmEmailTwoFactor(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $service = app(EmailTwoFactorService::class);

        if (! $service->verify($user, $this->emailCode)) {
            $this->addError('emailCode', __('profile.twofa_invalid_code'));

            return;
        }

        $codes = Collection::times(8, fn (): string => RecoveryCode::generate())->all();

        $user->forceFill([
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();

        $this->enablingEmail = false;
        $this->emailCode = '';
        $this->showingRecoveryCodes = true;
    }

    public function resendEmailCode(): void
    {
        /** @var User $user */
        $user = Auth::user();

        app(EmailTwoFactorService::class)->generateAndSendCode($user);

        $this->emailCodeResent = true;
    }

    public function disableEmailTwoFactor(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $user->forceFill([
            'two_factor_type' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $this->showingRecoveryCodes = false;
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $totpEnabled = $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;

        $emailEnabled = $user->two_factor_type === TwoFactorMethod::Email
            && $user->two_factor_confirmed_at !== null;

        return view('livewire.profile.two-factor-authentication', [
            'user' => $user,
            'enabled' => $totpEnabled || $emailEnabled,
            'totpEnabled' => $totpEnabled,
            'emailEnabled' => $emailEnabled,
        ]);
    }
}
