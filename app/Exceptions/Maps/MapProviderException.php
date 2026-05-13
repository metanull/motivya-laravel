<?php

declare(strict_types=1);

namespace App\Exceptions\Maps;

use RuntimeException;

/**
 * Thrown when a map provider operation fails or is misconfigured.
 */
final class MapProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        public readonly string $capability,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message ?: "Map provider '{$provider}' failed capability '{$capability}'.", $code, $previous);
    }
}
