<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTwoFactorEnabled
{
    /**
     * Roles that require two-factor authentication to be enabled.
     *
     * @var list<UserRole>
     */
    private const REQUIRED_ROLES = [
        UserRole::Admin,
        UserRole::Accountant,
    ];

    /**
     * Handle an incoming request.
     *
     * Redirects admin and accountant users to the 2FA setup page
     * if they have not enabled two-factor authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! in_array($user->role, self::REQUIRED_ROLES, true)) {
            return $next($request);
        }

        if ($user->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', __('auth.two_factor_required'));
    }
}
