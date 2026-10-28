<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Point public_path() at an empty directory owned by this test. Pest runs in
     * parallel, so tests that create or remove public/storage would otherwise
     * race every other process reading the shared symlink.
     */
    protected function usePrivatePublicPath(): void
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'motivya-public-'.bin2hex(random_bytes(8));
        mkdir($path);

        $this->app->usePublicPath($path);
        $this->beforeApplicationDestroyed(fn () => (new Filesystem)->deleteDirectory($path));
    }
}
