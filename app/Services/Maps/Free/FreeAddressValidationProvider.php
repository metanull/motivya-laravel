<?php

declare(strict_types=1);

namespace App\Services\Maps\Free;

use App\Contracts\Maps\AddressValidationProviderContract;
use App\DataTransferObjects\ValidatedAddress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Address validation provider backed by a Nominatim-compatible endpoint.
 *
 * Returns a Belgium-scoped ValidatedAddress DTO on success, or null when:
 *  - The API returns no results
 *  - The result country_code is not 'be'
 *  - An HTTP or parsing error occurs
 */
final class FreeAddressValidationProvider implements AddressValidationProviderContract
{
    public function validate(string $query, string $locale, string $country): ?ValidatedAddress
    {
        try {
            $baseUrl = (string) config(
                'maps.free.geocoding_base_url',
                'https://nominatim.openstreetmap.org/search',
            );
            $apiKey = config('maps.free.geocoding_api_key');

            $request = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->acceptJson();

            if (is_string($apiKey) && $apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
            }

            $response = $request->get($baseUrl, [
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 1,
                'countrycodes' => 'be',
                'limit' => 1,
            ]);

            if (! $response->successful()) {
                Log::warning('AddressValidationService: OpenFreeMap HTTP error.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array<int,array<string,mixed>> $data */
            $data = $response->json();

            if (empty($data) || ! isset($data[0])) {
                return null;
            }

            /** @var array<string,mixed> $result */
            $result = $data[0];

            /** @var array<string,mixed> $address */
            $address = $result['address'] ?? [];

            // Reject results outside Belgium.
            $countryCode = strtolower((string) ($address['country_code'] ?? ''));

            if ($countryCode !== 'be') {
                return null;
            }

            $houseNumber = isset($address['house_number']) ? (string) $address['house_number'] : null;
            $road = isset($address['road']) ? (string) $address['road'] : null;
            $streetAddress = $this->buildStreetAddress($road, $houseNumber);

            $locality = isset($address['city']) ? (string) $address['city']
                        : (isset($address['town']) ? (string) $address['town']
                        : (isset($address['village']) ? (string) $address['village'] : null));
            $postalCode = isset($address['postcode']) ? (string) $address['postcode'] : null;

            $lat = (float) ($result['lat'] ?? 0.0);
            $lng = (float) ($result['lon'] ?? 0.0);

            $rawJson = json_encode($result) ?: '';
            $placeId = isset($result['place_id']) ? (string) $result['place_id'] : null;

            return new ValidatedAddress(
                formattedAddress: (string) ($result['display_name'] ?? $query),
                streetAddress: $streetAddress,
                locality: $locality,
                postalCode: $postalCode,
                country: 'BE',
                latitude: $lat,
                longitude: $lng,
                provider: 'openfreemap',
                providerPlaceId: $placeId,
                rawPayload: ['_raw' => substr($rawJson, 0, 500)],
            );
        } catch (\Throwable $e) {
            Log::warning('AddressValidationService: OpenFreeMap provider failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildStreetAddress(?string $street, ?string $number): ?string
    {
        if ($street === null) {
            return null;
        }

        return $number !== null ? "{$street} {$number}" : $street;
    }
}
