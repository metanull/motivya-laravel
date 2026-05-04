<?php

declare(strict_types=1);

namespace App\Services\Audit;

/**
 * Redacts sensitive field values from arrays before they are persisted or logged.
 *
 * Sensitive fields are identified by an exact name match or by a name suffix
 * (e.g. any key ending in `_token` or `_secret`).
 */
final class FieldRedactor
{
    private const REDACTED = '[REDACTED]';

    /**
     * Exact field names that must always be redacted.
     *
     * @var list<string>
     */
    private const EXACT_FIELDS = [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_temp_secret',
        'stripe_secret',
        'stripe_webhook_secret',
        'oauth_token',
        'oauth_refresh_token',
        'api_token',
        'personal_access_token',
    ];

    /**
     * Suffixes that indicate a field is sensitive.
     *
     * @var list<string>
     */
    private const SENSITIVE_SUFFIXES = [
        '_token',
        '_secret',
    ];

    /**
     * Recursively redact sensitive values from the given array.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function redact(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $result[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $result[$key] = $this->redact($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function isSensitive(string $key): bool
    {
        $lower = strtolower($key);

        if (in_array($lower, self::EXACT_FIELDS, strict: true)) {
            return true;
        }

        foreach (self::SENSITIVE_SUFFIXES as $suffix) {
            if (str_ends_with($lower, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
