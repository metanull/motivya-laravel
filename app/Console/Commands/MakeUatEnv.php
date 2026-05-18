<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CoachProfileStatus;
use App\Models\CoachProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MakeUatEnv extends Command
{
    protected $signature = 'env:make-uat
                            {--path= : Destination .env.uat path}
                            {--from= : Source env file path}
                            {--stripe-connected-account=auto : auto, an explicit acct_ ID, or blank}
                            {--force : Overwrite an existing destination file}
                            {--print : Print generated content instead of writing a file}';

    protected $description = 'Generate a non-destructive UAT environment file';

    public function handle(): int
    {
        $destination = $this->pathOption('path') ?? base_path('.env.uat');
        $source = $this->pathOption('from') ?? $this->defaultSourcePath();
        $printOnly = (bool) $this->option('print');

        if (! File::exists($source)) {
            $this->error("Source env file not found: {$source}");

            return self::FAILURE;
        }

        if (! $printOnly && File::exists($destination) && ! (bool) $this->option('force')) {
            $this->error("Destination already exists: {$destination}. Re-run with --force to overwrite it.");

            return self::FAILURE;
        }

        $content = File::get($source);
        $values = $this->parseEnv($content);

        if (! $this->validateStripeTestMode($values)) {
            return self::FAILURE;
        }

        $stripeAccountId = $this->resolveStripeAccountId((string) $this->option('stripe-connected-account'));
        if ($stripeAccountId === false) {
            return self::FAILURE;
        }

        $content = $this->upsertEnvValue($content, 'APP_ENV', 'uat');
        $content = $this->upsertEnvValue($content, 'APP_DEBUG', 'false');
        $content = $this->upsertEnvValue($content, 'MOTIVYA_DEPLOY_PROFILE', 'uat');
        $content = $this->upsertEnvValue($content, 'MOTIVYA_STRIPE_LIVE_TESTS', '1');
        $content = $this->upsertEnvValue($content, 'MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID', $stripeAccountId ?? '');

        if ($printOnly) {
            $this->line($content);

            return self::SUCCESS;
        }

        $directory = dirname($destination);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0775, true);
        }

        File::put($destination, $content);
        $this->info("Generated UAT env file: {$destination}");

        if ($stripeAccountId === null || $stripeAccountId === '') {
            $this->warn('No usable Stripe Connect account was written. Set MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID before running manual Stripe UAT tests.');
        } else {
            $this->info('Wrote MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID from the selected usable coach profile.');
        }

        return self::SUCCESS;
    }

    private function defaultSourcePath(): string
    {
        $envPath = base_path('.env');

        return File::exists($envPath) ? $envPath : base_path('.env.example');
    }

    private function pathOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @return array<string, string>
     */
    private function parseEnv(string $content): array
    {
        $values = [];

        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim(trim($value), "\"'");
        }

        return $values;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function validateStripeTestMode(array $values): bool
    {
        $rules = [
            'STRIPE_KEY' => 'pk_test_',
            'STRIPE_SECRET' => 'sk_test_',
            'STRIPE_WEBHOOK_SECRET' => 'whsec_',
        ];

        foreach ($rules as $key => $prefix) {
            $value = $values[$key] ?? '';

            if ($value !== '' && ! str_starts_with($value, $prefix)) {
                $this->error("{$key} must be blank or a test-mode value beginning with {$prefix} for UAT env generation.");

                return false;
            }

            if ($value === '') {
                $this->warn("{$key} is blank in the source env file; fill it in {$prefix}... form before running Stripe UAT tests.");
            }
        }

        return true;
    }

    private function resolveStripeAccountId(string $option): string|false|null
    {
        $option = trim($option);

        if ($option === 'auto') {
            $profile = CoachProfile::query()
                ->where('status', CoachProfileStatus::Approved->value)
                ->where('stripe_onboarding_complete', true)
                ->where('stripe_account_id', '!=', null)
                ->where('stripe_account_id', 'like', 'acct_%')
                ->orderBy('id', 'asc')
                ->first();

            return is_string($profile?->stripe_account_id) ? $profile->stripe_account_id : null;
        }

        if ($option === '') {
            return null;
        }

        if (! str_starts_with($option, 'acct_')) {
            $this->error('--stripe-connected-account must be auto, blank, or a Stripe account ID beginning with acct_.');

            return false;
        }

        return $option;
    }

    private function upsertEnvValue(string $content, string $key, string $value): string
    {
        $line = $key.'='.$this->formatEnvValue($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content) === 1) {
            return (string) preg_replace($pattern, $line, $content, 1);
        }

        return rtrim($content).PHP_EOL.$line.PHP_EOL;
    }

    private function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return preg_match('/\s|#|=/', $value) === 1
            ? '"'.str_replace('"', '\\"', $value).'"'
            : $value;
    }
}
