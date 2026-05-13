<?php

declare(strict_types=1);

namespace App\Services\Maps\Google;

use App\Contracts\Maps\DirectionsUrlProviderContract;
use App\Models\SportSession;

/**
 * Directions URL provider backed by Google Maps.
 *
 * Uses coordinate-based URLs when the session has GPS coordinates;
 * falls back to a text-encoded address destination otherwise.
 */
final class GoogleDirectionsUrlProvider implements DirectionsUrlProviderContract
{
    public function getUrl(SportSession $session): string
    {
        $base = (string) config('maps.google.directions_base_url', 'https://www.google.com/maps/dir/');

        if ($session->latitude && $session->longitude) {
            return $base.'?api=1&destination='.$session->latitude.','.$session->longitude;
        }

        // No coordinates — fall back to a text destination.
        $legacyText = trim(($session->location ?? '').' '.($session->postal_code ?? '')).' Belgium';
        $text = $session->formatted_address ?? $legacyText;

        return $base.'?api=1&destination='.urlencode($text);
    }
}
