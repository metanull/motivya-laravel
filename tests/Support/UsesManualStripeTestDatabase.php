<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

trait UsesManualStripeTestDatabase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshDatabaseBase;
    }

    public function refreshDatabase(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'session.driver' => 'array',
            'mail.default' => 'array',
        ]);

        DB::purge('mysql');
        DB::purge('sqlite');

        $this->refreshDatabaseBase();
    }
}
