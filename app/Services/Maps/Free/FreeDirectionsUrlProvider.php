<?php

declare(strict_types=1);

namespace App\Services\Maps\Free;

use App\Contracts\Maps\DirectionsUrlProviderContract;
use App\Models\SportSession;

/**
 * Directions URL provider backed by OpenStreetMap.
 *
 * Uses coordinate-based URLs when the session has GPS coordinates;
 * falls back to a text-encoded "to" query otherwise.
 */
final class FreeDirectionsUrlProvider implements DirectionsUrlProviderContract
{
    public function getUrl(SportSession $session): string
    {
        $base = (string) config(
            'maps.free.directions_base_url',
            'https://www.openstreetmap.org/directions',
        );

        if ($session->latitude && $session->longitude) {
            return $base.'?route='.$session->latitude.','.$session->longitude;
        }

        // No coordinates — fall back to a text destination.
        $legacyText = trim(($session->location ?? '').' '.($session->postal_code ?? '')).' Belgium';
        $text = $session->formatted_address ?? $legacyText;

        return $base.'?to='.urlencode($text);
    }
}
