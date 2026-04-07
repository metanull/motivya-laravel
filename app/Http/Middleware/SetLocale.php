<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * The list of supported locales.
     *
     * @var list<string>
     */
    private const SUPPORTED = ['fr', 'en', 'nl'];

    /** @var string */
    private const DEFAULT_LOCALE = 'fr';

    /**
     * Handle an incoming request.
     *
     * Detection order (per i18n-localization.instructions.md):
     * 1. Authenticated user's persisted locale preference
     * 2. Session value
     * 3. Accept-Language header
     * 4. Default: 'fr'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        // 1. Authenticated user's persisted preference
        $user = $request->user();
        if ($user !== null) {
            $userLocale = $user->getAttribute('locale');
            if ($userLocale !== null) {
                if (in_array($userLocale, self::SUPPORTED, strict: true)) {
                    return $userLocale;
                }
                Log::warning('SetLocale: authenticated user has unsupported locale', [
                    'user_id' => $user->getKey(),
                    'locale' => $userLocale,
                ]);
            }
            // Authenticated user with no locale set — fall through to session/header.
        }

        // 2. Session value
        $sessionLocale = $request->session()->get('locale');
        if ($sessionLocale !== null) {
            if (in_array($sessionLocale, self::SUPPORTED, strict: true)) {
                return $sessionLocale;
            }
            Log::warning('SetLocale: session contains unsupported locale', [
                'locale' => $sessionLocale,
            ]);
        }

        // 3. Accept-Language header
        $browserLocale = $request->getPreferredLanguage(self::SUPPORTED);
        if ($browserLocale !== null && in_array($browserLocale, self::SUPPORTED, strict: true)) {
            return $browserLocale;
        }

        // 4. Default
        return self::DEFAULT_LOCALE;
    }
}
