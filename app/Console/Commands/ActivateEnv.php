<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ActivateEnv extends Command
{
    protected $signature = 'env:activate
                            {profile : Environment profile to activate: uat or production}
                            {--shared-path= : Directory containing .env.uat and .env.production}
                            {--force : Back up and replace a regular .env file if one exists}';

    protected $description = 'Activate a shared environment file by switching the shared .env symlink';

    public function handle(): int
    {
        $profile = (string) $this->argument('profile');
        if (! in_array($profile, ['uat', 'production'], true)) {
            $this->error('Profile must be either "uat" or "production".');

            return self::FAILURE;
        }

        $sharedPath = $this->sharedPath();
        if (! File::isDirectory($sharedPath)) {
            $this->error("Shared path does not exist: {$sharedPath}");

            return self::FAILURE;
        }

        $target = $sharedPath.DIRECTORY_SEPARATOR.".env.{$profile}";
        $active = $sharedPath.DIRECTORY_SEPARATOR.'.env';

        if (! File::exists($target)) {
            $this->error("Target env file does not exist: {$target}");

            return self::FAILURE;
        }

        if (File::exists($active) && ! is_link($active)) {
            if (! (bool) $this->option('force')) {
                $this->error("Refusing to replace regular env file: {$active}. Re-run with --force to back it up first.");

                return self::FAILURE;
            }

            $backup = $active.'.backup.'.now()->format('YmdHis');
            File::move($active, $backup);
            $this->warn("Backed up existing .env to {$backup}");
        }

        if (is_link($active) || File::exists($active)) {
            File::delete($active);
        }

        if (! symlink($target, $active)) {
            $this->error("Failed to activate {$target} as {$active}.");

            return self::FAILURE;
        }

        $this->info("Activated {$profile} env: {$active} -> {$target}");
        $this->warn('Run these from /opt/motivya/current after activation: php artisan optimize:clear && php artisan config:cache');

        return self::SUCCESS;
    }

    private function sharedPath(): string
    {
        $option = $this->option('shared-path');

        if (is_string($option) && trim($option) !== '') {
            return rtrim(trim($option), DIRECTORY_SEPARATOR);
        }

        return dirname(base_path('.env'));
    }
}
