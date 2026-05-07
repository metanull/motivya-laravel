<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Immutable value object representing a provider-validated postal address.
 *
 * Coordinates are stored as IEEE 754 doubles (WGS-84). Monetary amounts
 * are not part of this DTO — see PayoutBreakdown for financial data.
 */
final class ValidatedAddress
{
    public function __construct(
        /** The canonical, provider-formatted address string. */
        public readonly string $formattedAddress,

        /** Street name + house number if resolved, e.g. "Rue de la Loi 16". */
        public readonly ?string $streetAddress,

        /** City / commune / locality name. */
        public readonly ?string $locality,

        /** Postal / ZIP code. */
        public readonly ?string $postalCode,

        /** ISO 3166-1 alpha-2 country code, always "BE" for accepted results. */
        public readonly string $country,

        /** WGS-84 latitude. */
        public readonly float $latitude,

        /** WGS-84 longitude. */
        public readonly float $longitude,

        /** Geocoding provider that resolved this address: "google" | "openfreemap". */
        public readonly string $provider,

        /** Provider-specific place identifier (Google place_id or Nominatim place_id). */
        public readonly ?string $providerPlaceId,

        /**
         * Truncated raw API payload for debugging.
         * Contains a '_raw' key with the first 500 characters of the JSON-encoded
         * provider response. Never stored with full API keys or PII beyond the address.
         */
        public readonly ?array $rawPayload,
    ) {}
}
