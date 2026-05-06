<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Models\SportSession;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class SessionForm extends Form
{
    #[Validate]
    public string $activityType = '';

    #[Validate]
    public string $level = '';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

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

    // ── Schedule ─────────────────────────────────────────────────────────────

    #[Validate('required|date|after:today')]
    public string $date = '';

    #[Validate('required|date_format:H:i')]
    public string $startTime = '';

    #[Validate('required|date_format:H:i|after:startTime')]
    public string $endTime = '';

    // ── Pricing & capacity ───────────────────────────────────────────────────

    #[Validate('required|numeric|min:0.01')]
    public string $priceEuros = '';

    #[Validate('required|integer|min:1')]
    public int $minParticipants = 1;

    #[Validate('required|integer|min:1|gte:minParticipants')]
    public int $maxParticipants = 10;

    #[Validate('nullable|integer|exists:activity_images,id')]
    public ?int $coverImageId = null;

    // ── Recurrence ───────────────────────────────────────────────────────────

    #[Validate('boolean')]
    public bool $isRecurring = false;

    #[Validate('required_if:isRecurring,true|integer|min:2|max:12')]
    public int $numberOfWeeks = 2;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'activityType' => ['required', 'string', new Enum(ActivityType::class)],
            'level' => ['required', 'string', new Enum(SessionLevel::class)],
        ];
    }

    /**
     * Build the flat array consumed by SessionService::create/update/etc.
     *
     * The legacy `location` column is populated from `formattedAddress` so that
     * existing views and queries that still reference `location` continue to work.
     *
     * @return array<string, mixed>
     */
    public function toServiceArray(): array
    {
        return [
            'activity_type' => $this->activityType,
            'level' => $this->level,
            'title' => $this->title,
            'description' => $this->description,
            // Keep the legacy `location` column in sync with the validated address.
            'location' => $this->formattedAddress !== '' ? $this->formattedAddress : $this->addressQuery,
            'postal_code' => $this->postalCode ?? '',
            'formatted_address' => $this->formattedAddress !== '' ? $this->formattedAddress : null,
            'street_address' => $this->streetAddress,
            'locality' => $this->locality,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'geocoding_provider' => $this->geocodingProvider,
            'geocoding_place_id' => $this->geocodingPlaceId,
            'geocoded_at' => $this->geocodedAt,
            'geocoding_payload' => $this->geocodingPayload,
            'date' => $this->date,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'price_per_person' => (int) round((float) $this->priceEuros * 100),
            'min_participants' => $this->minParticipants,
            'max_participants' => $this->maxParticipants,
            'cover_image_id' => $this->coverImageId,
        ];
    }

    /**
     * Populate form fields from an existing SportSession.
     *
     * Backwards compatibility: sessions that pre-date address validation only
     * have `location` + `postal_code`.  In that case the addressQuery is
     * reconstructed from those legacy columns and addressValidated is set to
     * false so the coach is prompted to validate before re-publishing.
     */
    public function setFromModel(SportSession $session): void
    {
        $this->activityType = $session->activity_type->value;
        $this->level = $session->level->value;
        $this->title = $session->title;
        $this->description = $session->description ?? '';

        // Address ─────────────────────────────────────────────────────────────
        if (! empty($session->formatted_address)) {
            // New-format session: fully validated address available.
            $this->addressQuery = $session->formatted_address;
            $this->addressValidated = true;
            $this->formattedAddress = $session->formatted_address;
        } else {
            // Legacy session: reconstruct a human-readable query from the old columns.
            $this->addressQuery = $session->location
                .(! empty($session->postal_code) ? ', '.$session->postal_code : '');
            $this->addressValidated = false;
            $this->formattedAddress = '';
        }

        $this->streetAddress = $session->street_address ?? null;
        $this->locality = $session->locality ?? null;
        $this->postalCode = $session->postal_code ?? null;
        $this->country = $session->country ?? 'BE';
        $this->latitude = $session->latitude !== null ? (float) $session->latitude : null;
        $this->longitude = $session->longitude !== null ? (float) $session->longitude : null;
        $this->geocodingProvider = $session->geocoding_provider ?? null;
        $this->geocodingPlaceId = $session->geocoding_place_id ?? null;
        $this->geocodedAt = $session->geocoded_at?->toISOString();
        $this->geocodingPayload = $session->geocoding_payload;

        // Schedule ────────────────────────────────────────────────────────────
        $this->date = $session->date->format('Y-m-d');
        $this->startTime = substr((string) $session->start_time, 0, 5);
        $this->endTime = substr((string) $session->end_time, 0, 5);

        // Pricing & capacity ──────────────────────────────────────────────────
        $this->priceEuros = number_format($session->price_per_person / 100, 2, '.', '');
        $this->minParticipants = $session->min_participants;
        $this->maxParticipants = $session->max_participants;
        $this->coverImageId = $session->cover_image_id;
    }
}
