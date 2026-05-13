<?php

declare(strict_types=1);

namespace App\Contracts\Maps;

use App\Models\SportSession;

/**
 * Contract for directions URL providers.
 *
 * Implementations must construct a provider-specific URL that opens a
 * navigation app/website with the given sport session as the destination.
 */
interface DirectionsUrlProviderContract
{
    /**
     * Build a directions URL for the given session.
     *
     * Prefer GPS coordinates when available; fall back to a text address.
     */
    public function getUrl(SportSession $session): string;
}
