<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CoachProfileStatus;
use App\Enums\SessionStatus;
use App\Models\CoachProfile;
use App\Models\PaymentAnomaly;
use App\Models\PostalCodeCoordinate;
use App\Models\SchedulerHeartbeat;
use App\Models\SportSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Read-only production health snapshot.
 *
 * Reports the most critical production state at a glance:
 * scheduler heartbeats, postal-code coordinates, session GPS coverage,
 * public storage symlink, payment anomalies, and Stripe Connect status.
 *
 * Exit codes:
 *   0 — all checks pass (green or yellow)
 *   1 — one or more red blockers detected
 */
final class MvpHealthSnapshot extends Command
{
    protected $signature = 'mvp:health-snapshot
                            {--json : Output as JSON instead of table}';

    protected $description = 'Read-only production health snapshot — scheduler, coordinates, payments, Stripe Connect';

    private const CRITICAL_COMMANDS = [
        'sessions:send-reminders' => 120,
        'sessions:cancel-expired' => 120,
        'sessions:complete-finished' => 120,
        'subscriptions:compute-monthly' => 46080,
        'bookings:expire-unpaid' => 30,
    ];

    public function handle(): int
    {
        $rows = [];
        $hasBlocker = false;

        // ── 1. Database ───────────────────────────────────────────────────
        [$dbStatus, $dbMessage] = $this->checkDatabase();
        $rows[] = ['Database', $dbStatus, $dbMessage];
        if ($dbStatus === 'red') {
            $hasBlocker = true;
        }

        // ── 2. Cache ──────────────────────────────────────────────────────
        [$cacheStatus, $cacheMessage] = $this->checkCache();
        $rows[] = ['Cache', $cacheStatus, $cacheMessage];
        if ($cacheStatus === 'red') {
            $hasBlocker = true;
        }

        // ── 3. Scheduler heartbeats ───────────────────────────────────────
        foreach (self::CRITICAL_COMMANDS as $command => $windowMinutes) {
            $heartbeat = SchedulerHeartbeat::where('command', $command)->first();

            if ($heartbeat === null) {
                $rows[] = ["Scheduler: {$command}", 'red', 'Never run'];
                $hasBlocker = true;
            } elseif ($heartbeat->last_run_at->lt(now()->subMinutes($windowMinutes))) {
                $rows[] = ["Scheduler: {$command}", 'yellow', 'Stale: '.$heartbeat->last_run_at->diffForHumans()];
            } else {
                $rows[] = ["Scheduler: {$command}", 'green', 'OK: '.$heartbeat->last_run_at->diffForHumans()];
            }
        }

        // ── 4. Public storage symlink ─────────────────────────────────────
        $linkPath = public_path('storage');
        if (! is_link($linkPath)) {
            $rows[] = ['Public storage', 'red', 'Symlink missing at '.$linkPath];
            $hasBlocker = true;
        } else {
            $target = readlink($linkPath);
            if (! is_dir($target !== false ? $target : $linkPath)) {
                $rows[] = ['Public storage', 'red', 'Broken symlink → '.(string) $target];
                $hasBlocker = true;
            } else {
                $rows[] = ['Public storage', 'green', 'OK → '.(string) $target];
            }
        }

        // ── 5. Postal-code reference data ─────────────────────────────────
        $postalCount = PostalCodeCoordinate::count();
        if ($postalCount === 0) {
            $rows[] = ['Postal code reference', 'red', 'Empty — run: php artisan geo:load-postal-codes'];
            $hasBlocker = true;
        } else {
            $rows[] = ['Postal code reference', 'green', "{$postalCount} rows"];
        }

        // ── 6. Session GPS coverage ───────────────────────────────────────
        $totalSessions = SportSession::count();
        if ($totalSessions === 0) {
            $rows[] = ['Session coordinates', 'yellow', 'No sessions yet'];
        } else {
            $missingSessions = SportSession::whereNull('latitude')->orWhereNull('longitude')->count();
            if ($missingSessions === $totalSessions) {
                $rows[] = ['Session coordinates', 'red', "All {$missingSessions} sessions missing — run: php artisan sessions:backfill-coordinates"];
                $hasBlocker = true;
            } elseif ($missingSessions > 0) {
                $rows[] = ['Session coordinates', 'yellow', "{$missingSessions}/{$totalSessions} missing — run: php artisan sessions:backfill-coordinates"];
            } else {
                $rows[] = ['Session coordinates', 'green', "All {$totalSessions} sessions have coordinates"];
            }
        }

        // ── 7. Payment anomalies ──────────────────────────────────────────
        $anomalies = PaymentAnomaly::where('resolution_status', 'open')->count();
        if ($anomalies > 0) {
            $rows[] = ['Payment anomalies', 'red', "{$anomalies} open anomaly(ies) — visit /admin/anomalies to review"];
            $hasBlocker = true;
        } else {
            $rows[] = ['Payment anomalies', 'green', 'None'];
        }

        // ── 8. Stripe Connect ─────────────────────────────────────────────
        $incompleteCoaches = CoachProfile::where('status', CoachProfileStatus::Approved->value)
            ->where('stripe_onboarding_complete', false)
            ->whereHas('user.sportSessions', function ($query): void {
                $query->whereIn('status', [SessionStatus::Published->value, SessionStatus::Confirmed->value]);
            })
            ->count();
        if ($incompleteCoaches > 0) {
            $rows[] = ['Stripe Connect', 'yellow', "{$incompleteCoaches} coach(es) with active sessions not onboarded"];
        } else {
            $rows[] = ['Stripe Connect', 'green', 'All active coaches onboarded'];
        }

        // ── Output ─────────────────────────────────────────────────────────
        if ($this->option('json')) {
            $output = array_map(
                fn ($row) => ['check' => $row[0], 'status' => $row[1], 'message' => $row[2]],
                $rows
            );
            $this->line((string) json_encode($output, JSON_PRETTY_PRINT));
        } else {
            $this->table(['Check', 'Status', 'Message'], $rows);
        }

        if ($hasBlocker) {
            $this->error('One or more critical blockers detected. Review the table above.');

            return self::FAILURE;
        }

        $this->info('All critical checks passed.');

        return self::SUCCESS;
    }

    /**
     * @return array{string, string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['green', 'Connected'];
        } catch (\Throwable $e) {
            return ['red', 'Connection failed: '.$e->getMessage()];
        }
    }

    /**
     * @return array{string, string}
     */
    private function checkCache(): array
    {
        try {
            $driver = (string) config('cache.default');
            Cache::store($driver)->put('health-snapshot-check', true, 10);

            return ['green', "Driver: {$driver}"];
        } catch (\Throwable $e) {
            return ['red', 'Cache failed: '.$e->getMessage()];
        }
    }
}
