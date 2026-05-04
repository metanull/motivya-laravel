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

    /**
     * Resolve coordinates from a free-text location query (postal code or municipality name).
     *
     * First attempts an exact postal code match; if that fails, performs a
     * case-insensitive LIKE search on the municipality column so that bilingual
     * names like "Bruxelles/Brussel" match either language fragment.
     *
     * Returns [latitude, longitude] or null when the input cannot be resolved.
     *
     * @return array{float, float}|null
     */
    public function resolveByLocationQuery(string $query): ?array
    {
        // Try exact postal code match first.
        $byPostalCode = $this->resolveCoordinates($query);
        if ($byPostalCode !== null) {
            return $byPostalCode;
        }

        // Try municipality name (case-insensitive, partial match for bilingual
        // names like "Bruxelles/Brussel").
        $record = PostalCodeCoordinate::whereRaw(
            'LOWER(municipality) LIKE ?',
            ['%'.strtolower($query).'%'],
        )->first();

        if ($record === null) {
            return null;
        }

        return [(float) $record->latitude, (float) $record->longitude];
    }
}
