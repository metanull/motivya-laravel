<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PostalCodeCoordinate;

final class PostalCodeCoordinateService
{
    /**
     * Look up coordinates for a Belgian postal code.
     * Returns [latitude, longitude] or null if not found.
     *
     * @return array{float, float}|null
     */
    public function resolveCoordinates(string $postalCode): ?array
    {
        $record = PostalCodeCoordinate::where('postal_code', $postalCode)->first();

        if ($record === null) {
            return null;
        }

        return [(float) $record->latitude, (float) $record->longitude];
    }
}
