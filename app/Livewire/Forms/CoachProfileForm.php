<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\CoachProfile;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class CoachProfileForm extends Form
{
    /** @var array<int, string> */
    #[Validate('required|array|min:1')]
    public array $specialties = [];

    #[Validate('required|string|max:2000')]
    public string $bio = '';

    #[Validate('required|string|in:beginner,intermediate,advanced,expert')]
    public string $experienceLevel = '';

    // ── Address ──────────────────────────────────────────────────────────────

    /** Free-text address query typed by the coach. */
    #[Validate('required|string|min:5|max:500')]
    public string $addressQuery = '';

    /**
     * True once the address has been validated via a geocoding provider.
     * Not subject to Livewire attribute validation; checked programmatically.
     */
    public bool $addressValidated = false;

    /** Canonical formatted address returned by the provider. */
    public string $formattedAddress = '';

    public ?string $streetAddress = null;

    public ?string $locality = null;

    /** Postal code extracted from the validated address (nullable for locations without one). */
    public ?string $postalCode = null;

    public string $country = 'BE';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?string $geocodingProvider = null;

    public ?string $geocodingPlaceId = null;

    /** ISO-8601 timestamp string; stored as string to survive Livewire serialisation. */
    public ?string $geocodedAt = null;

    /** @var array<string, mixed>|null */
    public ?array $geocodingPayload = null;

    // ─────────────────────────────────────────────────────────────────────────

    #[Validate('nullable|string|max:50')]
    public string $enterpriseNumber = '';

    /**
     * @return array<string, mixed>
     */
    public function toServiceArray(): array
    {
        return [
            'specialties' => $this->specialties,
            'bio' => $this->bio,
            'experience_level' => $this->experienceLevel,
            // Derived compatibility field: postal_code comes from the validated result.
            'postal_code' => $this->postalCode ?? '',
            'formatted_address' => $this->formattedAddress !== '' ? $this->formattedAddress : null,
            'street_address' => $this->streetAddress,
            'locality' => $this->locality,
            'country' => $this->country !== '' ? $this->country : 'BE',
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'geocoding_provider' => $this->geocodingProvider,
            'geocoding_place_id' => $this->geocodingPlaceId,
            'geocoded_at' => $this->geocodedAt,
            'geocoding_payload' => $this->geocodingPayload,
            'enterprise_number' => $this->enterpriseNumber,
        ];
    }

    public function setFromModel(CoachProfile $profile): void
    {
        $this->specialties = $profile->specialties ?? [];
        $this->bio = $profile->bio ?? '';
        $this->experienceLevel = $profile->experience_level ?? '';
        $this->enterpriseNumber = $profile->enterprise_number ?? '';

        // Address ─────────────────────────────────────────────────────────────
        if (! empty($profile->formatted_address)) {
            // New-format profile: fully validated address available.
            $this->addressQuery = $profile->formatted_address;
            $this->addressValidated = true;
            $this->formattedAddress = $profile->formatted_address;
        } else {
            // Legacy profile: reconstruct a human-readable query from the old columns.
            $this->addressQuery = ! empty($profile->postal_code)
                ? ($profile->postal_code.(! empty($profile->country) ? ', '.$profile->country : ''))
                : '';
            $this->addressValidated = false;
            $this->formattedAddress = '';
        }

        $this->streetAddress = $profile->street_address ?? null;
        $this->locality = $profile->locality ?? null;
        $this->postalCode = $profile->postal_code ?? null;
        $this->country = $profile->country ?? 'BE';
        $this->latitude = $profile->latitude !== null ? (float) $profile->latitude : null;
        $this->longitude = $profile->longitude !== null ? (float) $profile->longitude : null;
        $this->geocodingProvider = $profile->geocoding_provider ?? null;
        $this->geocodingPlaceId = $profile->geocoding_place_id ?? null;
        $this->geocodedAt = $profile->geocoded_at?->toISOString();
        $this->geocodingPayload = $profile->geocoding_payload;
    }
}
