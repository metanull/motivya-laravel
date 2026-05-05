<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SchedulerHeartbeat extends Model
{
    protected $fillable = [
        'command',
        'last_run_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
        ];
    }

    /**
     * Record or update a heartbeat for the given command.
     */
    public static function record(string $command): void
    {
        self::updateOrCreate(
            ['command' => $command],
            ['last_run_at' => now()],
        );
    }
}
