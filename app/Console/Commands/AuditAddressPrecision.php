<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CoachProfile;
use App\Models\SportSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only command that reports address precision metrics for sessions
 * and coach profiles.
 *
 * Produces a table (default) or JSON listing:
 *   - total rows
 *   - validated exact addresses (formatted_address + lat/lng + provider)
 *   - legacy postal-code-only rows (postal_code present, no formatted_address)
 *   - rows missing coordinates (null lat or lng)
 *   - rows missing formatted_address
 *   - provider distribution for geocoded rows
 *
 * Exit codes:
 *   0 — command ran successfully (results may still show gaps)
 *   1 — unexpected error during data collection
 */
final class AuditAddressPrecision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addresses:audit-precision
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read-only audit of address precision across sessions and coach profiles';

    public function handle(): int
    {
        try {
            $rows = [];

            // ── Sessions ──────────────────────────────────────────────────
            $sessionTotal = SportSession::count();
            $sessionValidated = SportSession::whereNotNull('formatted_address')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereNotNull('geocoding_provider')
                ->count();
            $sessionLegacy = SportSession::whereNotNull('postal_code')
                ->whereNull('formatted_address')
                ->count();
            $sessionMissingCoords = SportSession::where(function ($q): void {
                $q->whereNull('latitude')->orWhereNull('longitude');
            })->count();
            $sessionMissingFormatted = SportSession::whereNull('formatted_address')->count();

            $rows[] = ['sessions', 'Total', $sessionTotal];
            $rows[] = ['sessions', 'Validated (exact)', $sessionValidated];
            $rows[] = ['sessions', 'Legacy (postal-code only)', $sessionLegacy];
            $rows[] = ['sessions', 'Missing coordinates', $sessionMissingCoords];
            $rows[] = ['sessions', 'Missing formatted address', $sessionMissingFormatted];

            // ── Coach profiles ────────────────────────────────────────────
            $profileTotal = CoachProfile::count();
            $profileValidated = CoachProfile::whereNotNull('formatted_address')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereNotNull('geocoding_provider')
                ->count();
            $profileLegacy = CoachProfile::whereNotNull('postal_code')
                ->whereNull('formatted_address')
                ->count();
            $profileMissingCoords = CoachProfile::where(function ($q): void {
                $q->whereNull('latitude')->orWhereNull('longitude');
            })->count();
            $profileMissingFormatted = CoachProfile::whereNull('formatted_address')->count();

            $rows[] = ['coach_profiles', 'Total', $profileTotal];
            $rows[] = ['coach_profiles', 'Validated (exact)', $profileValidated];
            $rows[] = ['coach_profiles', 'Legacy (postal-code only)', $profileLegacy];
            $rows[] = ['coach_profiles', 'Missing coordinates', $profileMissingCoords];
            $rows[] = ['coach_profiles', 'Missing formatted address', $profileMissingFormatted];

            // ── Provider distribution ─────────────────────────────────────
            $providerRows = $this->collectProviderDistribution();

            if ($this->option('json')) {
                $output = [
                    'metrics' => array_map(
                        fn ($row) => ['model' => $row[0], 'check' => $row[1], 'count' => $row[2]],
                        $rows,
                    ),
                    'providers' => $providerRows,
                ];
                $this->line((string) json_encode($output, JSON_PRETTY_PRINT));
            } else {
                $this->table(['Model', 'Check', 'Count'], $rows);

                if (! empty($providerRows)) {
                    $this->newLine();
                    $this->info('Provider distribution (rows with geocoding_provider set):');
                    $this->table(['Model', 'Provider', 'Count'], $providerRows);
                } else {
                    $this->newLine();
                    $this->line('No rows with geocoding_provider set.');
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Audit failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Collect geocoding_provider distribution for both models.
     *
     * @return array<int, array{string, string, int}>
     */
    private function collectProviderDistribution(): array
    {
        $rows = [];

        // Sessions
        $sessionProviders = DB::table('sport_sessions')
            ->select('geocoding_provider', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('geocoding_provider')
            ->groupBy('geocoding_provider')
            ->orderBy('geocoding_provider')
            ->get();

        foreach ($sessionProviders as $record) {
            $rows[] = ['sessions', (string) $record->geocoding_provider, (int) $record->cnt];
        }

        // Coach profiles
        $profileProviders = DB::table('coach_profiles')
            ->select('geocoding_provider', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('geocoding_provider')
            ->groupBy('geocoding_provider')
            ->orderBy('geocoding_provider')
            ->get();

        foreach ($profileProviders as $record) {
            $rows[] = ['coach_profiles', (string) $record->geocoding_provider, (int) $record->cnt];
        }

        return $rows;
    }
}
