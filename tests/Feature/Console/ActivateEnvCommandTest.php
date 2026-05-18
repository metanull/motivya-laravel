<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('Symlink activation behavior is covered on Linux CI/UAT hosts.');
    }
});

describe('env:activate', function () {
    it('activates UAT by switching the shared .env symlink', function (): void {
        $directory = storage_path('framework/testing/env-activate-'.bin2hex(random_bytes(4)));
        File::makeDirectory($directory, 0775, true);
        File::put($directory.'/.env.uat', "APP_ENV=uat\n");
        File::put($directory.'/.env.production', "APP_ENV=production\n");

        $this->artisan("env:activate uat --shared-path={$directory}")
            ->assertSuccessful();

        expect(is_link($directory.'/.env'))->toBeTrue()
            ->and(readlink($directory.'/.env'))->toBe($directory.'/.env.uat');
    });

    it('refuses to replace a regular .env file without force', function (): void {
        $directory = storage_path('framework/testing/env-activate-'.bin2hex(random_bytes(4)));
        File::makeDirectory($directory, 0775, true);
        File::put($directory.'/.env.uat', "APP_ENV=uat\n");
        File::put($directory.'/.env', "APP_ENV=production\n");

        $this->artisan("env:activate uat --shared-path={$directory}")
            ->assertFailed();

        expect(is_link($directory.'/.env'))->toBeFalse()
            ->and(File::get($directory.'/.env'))->toBe("APP_ENV=production\n");
    });

    it('backs up and replaces a regular .env file when forced', function (): void {
        $directory = storage_path('framework/testing/env-activate-'.bin2hex(random_bytes(4)));
        File::makeDirectory($directory, 0775, true);
        File::put($directory.'/.env.uat', "APP_ENV=uat\n");
        File::put($directory.'/.env', "APP_ENV=production\n");

        $this->artisan("env:activate uat --shared-path={$directory} --force")
            ->assertSuccessful();

        expect(is_link($directory.'/.env'))->toBeTrue()
            ->and(File::glob($directory.'/.env.backup.*'))->not->toBeEmpty();
    });
});
