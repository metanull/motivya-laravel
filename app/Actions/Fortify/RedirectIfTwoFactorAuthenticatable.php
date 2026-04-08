<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\TwoFactorMethod;
use App\Services\EmailTwoFactorService;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirect;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;

final class RedirectIfTwoFactorAuthenticatable extends FortifyRedirect
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $user = $this->validateCredentials($request);

        // Email 2FA: user has email method confirmed
        if ($user->two_factor_type === TwoFactorMethod::Email
            && $user->two_factor_confirmed_at !== null) {

            $request->session()->put('login.two_factor_method', TwoFactorMethod::Email->value);

            app(EmailTwoFactorService::class)->generateAndSendCode($user);

            return $this->twoFactorChallengeResponse($request, $user);
        }

        // TOTP 2FA: standard Fortify behaviour
        $hasTotpSecret = $user->two_factor_secret
            && in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user));

        if (Fortify::confirmsTwoFactorAuthentication()) {
            $hasTotpSecret = $hasTotpSecret && $user->two_factor_confirmed_at !== null;
        }

        if ($hasTotpSecret) {
            $request->session()->put('login.two_factor_method', TwoFactorMethod::Totp->value);

            return $this->twoFactorChallengeResponse($request, $user);
        }

        return $next($request);
    }
}
