<?php

declare(strict_types=1);

namespace App\Services\Maps;

use App\Contracts\Maps\HealthProbeProviderContract;
use App\Enums\MapProvider;

/**
 * Health service for the active map provider.
 *
 * Used by the admin Readiness check and MvpHealthSnapshot command.
 * Returns structured probe results that callers can convert to status rows.
 *
 * This service exists as a workaround for the restricted Readiness.php file:
 *
 * @admin-tools should call this from Readiness.php instead of the old
 * checkGoogleMapsKey() / checkGeocodingCache() methods.
 */
final class MapProviderHealthService
{
    public function __construct(
        private readonly MapProviderResolver $resolver,
        private readonly HealthProbeProviderContract $probe,
    ) {}

    /**
     * Return the name of the currently active provider.
     */
    public function activeProviderName(): string
    {
        return $this->resolver->resolve()->value;
    }

    /**
     * Run all provider capability probes.
     *
     * @return array<int, array{capability: string, status: 'ok'|'fail', message: string}>
     */
    public function probe(): array
    {
        return $this->probe->probe();
    }

    /**
     * Return a single summary status for the map provider health.
     *
     * - 'ok'   — all probes passed
     * - 'warn' — at least one probe failed but not API-key-related
     * - 'fail' — API key absent for Google, or geocoding URL missing for Free
     *
     * @return array{status: 'ok'|'warn'|'fail', message: string}
     */
    public function summary(): array
    {
        $results = $this->probe->probe();
        $provider = $this->resolver->resolve();

        $failed = array_filter($results, fn (array $r): bool => $r['status'] === 'fail');

        if (empty($failed)) {
            $name = $provider === MapProvider::Google ? 'Google Maps Platform' : 'Free stack (OpenFreeMap + Nominatim)';

            return ['status' => 'ok', 'message' => "Map provider OK: {$name}"];
        }

        // If the API key itself is missing for Google, that's a hard failure.
        if ($provider === MapProvider::Google) {
            foreach ($failed as $f) {
                if ($f['capability'] === 'api_key_present') {
                    return ['status' => 'fail', 'message' => $f['message']];
                }
            }
        }

        // Other failures (connectivity, format) are warnings.
        $messages = array_column($failed, 'message');

        return ['status' => 'warn', 'message' => implode('; ', $messages)];
    }
}
