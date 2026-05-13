<?php

declare(strict_types=1);

namespace App\Services\Maps;

use App\Contracts\Maps\DirectionsUrlProviderContract;
use App\Models\SportSession;

/**
 * Facade service for building directions URLs.
 *
 * Delegates to the active provider (Google or Free) via the bound
 * DirectionsUrlProviderContract. The binding is resolved at construction time
 * by AppServiceProvider based on GOOGLE_MAPS_API_KEY presence.
 */
final class DirectionsUrlService
{
    public function __construct(
        private readonly DirectionsUrlProviderContract $provider,
    ) {}

    /**
     * Build a directions URL for the given sport session.
     */
    public function getUrl(SportSession $session): string
    {
        return $this->provider->getUrl($session);
    }
}
