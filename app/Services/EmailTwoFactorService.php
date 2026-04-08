<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class EmailTwoFactorService
{
    private const CODE_LENGTH = 6;

    private const CODE_TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function generateAndSendCode(User $user): void
    {
        $code = $this->generateCode();

        Cache::put(
            $this->codeKey($user),
            $code,
            now()->addMinutes(self::CODE_TTL_MINUTES),
        );

        Cache::put(
            $this->attemptsKey($user),
            0,
            now()->addMinutes(self::CODE_TTL_MINUTES),
        );

        $user->notify(new TwoFactorCodeNotification($code));

        Log::info('Email 2FA code sent', ['user_id' => $user->id]);
    }

    public function verify(User $user, string $code): bool
    {
        $attemptsKey = $this->attemptsKey($user);
        $attempts = (int) Cache::get($attemptsKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Log::warning('Email 2FA max attempts exceeded', ['user_id' => $user->id]);

            return false;
        }

        Cache::increment($attemptsKey);

        $storedCode = Cache::get($this->codeKey($user));

        if ($storedCode === null) {
            Log::info('Email 2FA code expired or not found', ['user_id' => $user->id]);

            return false;
        }

        if (! hash_equals((string) $storedCode, $code)) {
            Log::info('Email 2FA code mismatch', [
                'user_id' => $user->id,
                'attempts' => $attempts + 1,
            ]);

            return false;
        }

        // Code is valid — clear it to prevent reuse
        Cache::forget($this->codeKey($user));
        Cache::forget($attemptsKey);

        return true;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, (int) (10 ** self::CODE_LENGTH - 1)), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function codeKey(User $user): string
    {
        return "2fa_email_code:{$user->id}";
    }

    private function attemptsKey(User $user): string
    {
        return "2fa_email_attempts:{$user->id}";
    }
}
