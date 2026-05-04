<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): mixed
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(RoleRedirectService $roleRedirectService): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user === null) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => bcrypt(Str::random(32)),
                'role' => UserRole::Athlete,
            ]);
            $user->markEmailAsVerified();
        }

        Auth::login($user, remember: true);

        return redirect()->intended($roleRedirectService->pathFor($user));
    }
}
