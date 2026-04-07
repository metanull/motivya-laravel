<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Accepts one or more role slugs as middleware parameters and aborts
     * with 403 if the authenticated user's role is not in the allowed list.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = array_map(
            fn (string $role) => UserRole::from($role),
            $roles,
        );

        if (! in_array($request->user()?->role, $allowedRoles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
