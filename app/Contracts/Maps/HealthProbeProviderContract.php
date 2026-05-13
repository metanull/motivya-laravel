<?php

declare(strict_types=1);

namespace App\Contracts\Maps;

/**
 * Contract for map provider health probes.
 *
 * Implementations check whether all required capabilities for their provider
 * are properly configured and reachable.
 */
interface HealthProbeProviderContract
{
    /**
     * Execute all capability probes for this provider.
     *
     * @return array<int, array{capability: string, status: 'ok'|'fail', message: string}>
     */
    public function probe(): array;
}
