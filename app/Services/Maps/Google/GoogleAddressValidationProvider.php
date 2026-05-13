<?php

declare(strict_types=1);

namespace App\Services\Maps\Google;

use App\Contracts\Maps\AddressValidationProviderContract;
use App\DataTransferObjects\ValidatedAddress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Address validation provider backed by the Google Geocoding API.
 *
 * Returns a Belgium-scoped ValidatedAddress DTO on success, or null when:
 *  - The API returns no results
 *  - The result is outside Belgium
 *  - An HTTP or parsing error occurs
 */
final class GoogleAddressValidationProvider implements AddressValidationProviderContract
{
    public function validate(string $query, string $locale, string $country): ?ValidatedAddress
    {
        try {
            $response = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->get((string) config('maps.google.geocoding_base_url'), [
                    'address' => $query.', '.$country,
                    'key' => config('maps.google.api_key'),
                    'language' => $locale,
                    'region' => strtolower($country),
                ]);

            if (! $response->successful()) {
                Log::warning('AddressValidationService: Google HTTP error.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array<string,mixed> $data */
            $data = $response->json();

            if (empty($data['results'])) {
                return null;
            }

            /** @var array<string,mixed> $result */
            $result = $data['results'][0];

            if (empty($result['geometry']['location']['lat']) || empty($result['geometry']['location']['lng'])) {
                return null;
            }

            // Reject results outside Belgium.
            $countryCode = $this->extractComponent(
                $result['address_components'] ?? [],
                'country',
                'short_name',
            );

            if ($countryCode !== 'BE') {
                return null;
            }

            $streetNumber = $this->extractComponent($result['address_components'] ?? [], 'street_number');
            $route = $this->extractComponent($result['address_components'] ?? [], 'route');
            $streetAddress = $this->buildStreetAddress($route, $streetNumber);

            $locality = $this->extractComponent($result['address_components'] ?? [], 'locality')
                          ?? $this->extractComponent($result['address_components'] ?? [], 'postal_town');
            $postalCode = $this->extractComponent($result['address_components'] ?? [], 'postal_code');

            $lat = (float) $result['geometry']['location']['lat'];
            $lng = (float) $result['geometry']['location']['lng'];

            $rawJson = json_encode($result) ?: '';

            return new ValidatedAddress(
                formattedAddress: (string) ($result['formatted_address'] ?? $query),
                streetAddress: $streetAddress,
                locality: $locality,
                postalCode: $postalCode,
                country: 'BE',
                latitude: $lat,
                longitude: $lng,
                provider: 'google',
                providerPlaceId: isset($result['place_id']) ? (string) $result['place_id'] : null,
                rawPayload: ['_raw' => substr($rawJson, 0, 500)],
            );
        } catch (\Throwable $e) {
            Log::warning('AddressValidationService: Google provider failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract a single value from a Google address_components array by type.
     *
     * @param  array<int,array<string,mixed>>  $components
     */
    private function extractComponent(
        array $components,
        string $type,
        string $field = 'long_name',
    ): ?string {
        foreach ($components as $component) {
            /** @var array<string> $types */
            $types = $component['types'] ?? [];

            if (in_array($type, $types, true)) {
                $value = $component[$field] ?? null;

                return is_string($value) ? $value : null;
            }
        }

        return null;
    }

    private function buildStreetAddress(?string $street, ?string $number): ?string
    {
        if ($street === null) {
            return null;
        }

        return $number !== null ? "{$street} {$number}" : $street;
    }
}
