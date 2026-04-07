<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * The list of supported locales.
     *
     * @var list<string>
     */
    private const array SUPPORTED = ['fr', 'en', 'nl'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // getAttribute() returns null when the column does not yet exist on the model,
        // so this chain degrades gracefully before the locale migration is added.
        $locale = $request->user()?->getAttribute('locale')
            ?? $request->session()->get('locale')
            ?? $request->getPreferredLanguage(self::SUPPORTED)
            ?? 'fr';

        if (! in_array($locale, self::SUPPORTED, strict: true)) {
            $locale = 'fr';
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
