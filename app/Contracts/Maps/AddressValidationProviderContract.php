<?php

declare(strict_types=1);

namespace App\Contracts\Maps;

use App\DataTransferObjects\ValidatedAddress;

/**
 * Contract for address validation providers.
 *
 * Implementations must geocode the given query against a Belgium-scoped
 * external service and return a fully populated ValidatedAddress DTO, or
 * null when the address cannot be resolved or falls outside Belgium.
 */
interface AddressValidationProviderContract
{
    /**
     * Validate a free-text address query.
     *
     * @param  string  $query  Human-readable address (e.g. "Rue de la Loi 16, Bruxelles")
     * @param  string  $locale  BCP-47 locale hint for the provider
     * @param  string  $country  ISO 3166-1 alpha-2 country scope (default: BE)
     */
    public function validate(string $query, string $locale, string $country): ?ValidatedAddress;
}
