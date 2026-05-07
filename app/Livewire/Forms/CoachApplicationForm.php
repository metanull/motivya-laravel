<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

final class CoachApplicationForm extends Form
{
    /** @var list<string> */
    #[Validate('required|array|min:1')]
    public array $specialties = [];

    #[Validate('nullable|string|max:2000')]
    public string $bio = '';

    #[Validate('nullable|string|in:beginner,intermediate,advanced,expert')]
    public string $experience_level = '';

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

    #[Validate('required|string|regex:/^\d{4}\.\d{3}\.\d{3}$/')]
    public string $enterprise_number = '';

    #[Validate('accepted')]
    public bool $terms_accepted = false;

    /**
     * @return array<string, mixed>
     */
    public function toServiceArray(): array
    {
        return [
            'specialties' => $this->specialties,
            'bio' => $this->bio,
            'experience_level' => $this->experience_level,
            // Derived compatibility fields: postal_code and country come from the validated result.
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
            'enterprise_number' => $this->enterprise_number,
        ];
    }
}
