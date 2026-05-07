<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Services\AddressValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Backfill normalized address fields and coordinates on sessions and coach
 * profiles that are missing a validated formatted_address.
 *
 * Dry-run is the **default** behaviour.  Pass --apply to write to the database.
 *
 * Signature options:
 *   --model=sessions       Which model to backfill: "sessions" or "coach_profiles"
 *   --dry-run              Preview what would be backfilled without writing (default)
 *   --apply                Write normalized address fields + coordinates to the DB
 *   --force                Also overwrite rows that already have formatted_address
 *   --limit=100            Maximum rows to process in one run
 *
 * Exit codes:
 *   0 — success (or dry-run with results)
 *   1 — failure (no provider configured, invalid --model, unexpected error)
 */
final class BackfillAddressPrecision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addresses:backfill
                            {--model=sessions : Which model to backfill: sessions or coach_profiles}
                            {--dry-run : Preview what would be backfilled without writing (default behavior)}
                            {--apply : Write normalized address fields and coordinates to the database}
                            {--force : Also overwrite rows that already have formatted_address + geocoding metadata}
                            {--limit=100 : Maximum rows to process in one run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill validated addresses and coordinates on sessions or coach profiles (dry-run by default)';

    public function handle(AddressValidationService $addressService): int
    {
        $model = (string) $this->option('model');
        $isApply = (bool) $this->option('apply');
        $isForce = (bool) $this->option('force');
        $limit = max(1, (int) ($this->option('limit') ?? 100));

        // Validate --model option.
        if (! in_array($model, ['sessions', 'coach_profiles'], true)) {
            $this->error("Invalid --model value '{$model}'. Use 'sessions' or 'coach_profiles'.");

            return self::FAILURE;
        }

        // Verify the geocoding provider is usable before fetching records.
        $provider = (string) config('maps.geocoding_provider', 'google');
        if ($provider === 'google') {
            $googleKey = config('maps.google_api_key');
            if (empty($googleKey)) {
                $this->error('No Google API key configured (GOOGLE_MAPS_API_KEY). Cannot backfill.');

                return self::FAILURE;
            }
        }

        // Dry-run is the default; --apply is required to write.
        $isDryRun = ! $isApply;

        if ($isDryRun) {
            $this->warn('[DRY-RUN] No changes will be written. Pass --apply to persist changes.');
        }

        return match ($model) {
            'sessions' => $this->backfillSessions($addressService, $isDryRun, $isForce, $limit),
            'coach_profiles' => $this->backfillCoachProfiles($addressService, $isDryRun, $isForce, $limit),
        };
    }

    /**
     * Backfill sport_sessions rows.
     */
    private function backfillSessions(
        AddressValidationService $addressService,
        bool $isDryRun,
        bool $isForce,
        int $limit,
    ): int {
        $query = SportSession::query()
            ->whereNotNull('location');

        if (! $isForce) {
            $query->whereNull('formatted_address');
        }

        $total = $query->count();
        $this->info("Sessions to process: {$total} (limit: {$limit})");

        $processed = 0;
        $updated = 0;
        $failed = 0;

        $query->limit($limit)->each(function (SportSession $session) use (
            $addressService,
            $isDryRun,
            &$processed,
            &$updated,
            &$failed,
        ): void {
            $processed++;

            // Build the query string from location + postal_code.
            $queryString = trim(
                ($session->location ?? '')
                .($session->postal_code ? ', '.$session->postal_code : ''),
            );

            if ($queryString === '') {
                $this->line("  [SKIP] Session #{$session->id} — no usable address text.");

                return;
            }

            $validated = $addressService->validate($queryString);

            if ($validated === null) {
                $failed++;
                $this->line("  [FAIL] Session #{$session->id} — provider returned no result for: {$queryString}");

                return;
            }

            $previousLat = $session->latitude;
            $previousLng = $session->longitude;
            $coordsChanged = (string) $previousLat !== (string) $validated->latitude
                || (string) $previousLng !== (string) $validated->longitude;

            if ($isDryRun) {
                $updated++;
                $this->line(
                    "  [DRY-RUN] Session #{$session->id} → {$validated->formattedAddress}"
                    ." [{$validated->provider}]"
                    .($coordsChanged ? ' (coords would change)' : ''),
                );

                return;
            }

            // Apply the update.
            $session->update([
                'formatted_address' => $validated->formattedAddress,
                'street_address' => $validated->streetAddress,
                'locality' => $validated->locality,
                'country' => $validated->country,
                'latitude' => $validated->latitude,
                'longitude' => $validated->longitude,
                'geocoding_provider' => $validated->provider,
                'geocoding_place_id' => $validated->providerPlaceId,
                'geocoded_at' => now(),
                'geocoding_payload' => $validated->rawPayload,
            ]);

            $updated++;

            Log::info('addresses:backfill updated session', [
                'model' => 'SportSession',
                'model_id' => $session->id,
                'provider' => $validated->provider,
                'coords_changed' => $coordsChanged,
                'formatted_address' => $validated->formattedAddress,
            ]);

            $this->line(
                "  [OK] Session #{$session->id} → {$validated->formattedAddress}"
                ." [{$validated->provider}]"
                .($coordsChanged ? ' (coords updated)' : ''),
            );
        });

        $this->newLine();
        $this->info("Processed: {$processed} | Updated: {$updated} | Failed: {$failed}");

        return self::SUCCESS;
    }

    /**
     * Backfill coach_profiles rows.
     */
    private function backfillCoachProfiles(
        AddressValidationService $addressService,
        bool $isDryRun,
        bool $isForce,
        int $limit,
    ): int {
        $query = CoachProfile::query()
            ->whereNotNull('postal_code');

        if (! $isForce) {
            $query->whereNull('formatted_address');
        }

        $total = $query->count();
        $this->info("Coach profiles to process: {$total} (limit: {$limit})");

        $processed = 0;
        $updated = 0;
        $failed = 0;

        $query->limit($limit)->each(function (CoachProfile $profile) use (
            $addressService,
            $isDryRun,
            &$processed,
            &$updated,
            &$failed,
        ): void {
            $processed++;

            // Build the query string from postal_code + country.
            $queryString = trim(
                ($profile->postal_code ?? '')
                .($profile->country ? ', '.$profile->country : ''),
            );

            if ($queryString === '') {
                $this->line("  [SKIP] Profile #{$profile->id} — no usable address text.");

                return;
            }

            $validated = $addressService->validate($queryString);

            if ($validated === null) {
                $failed++;
                $this->line("  [FAIL] Profile #{$profile->id} — provider returned no result for: {$queryString}");

                return;
            }

            $previousLat = $profile->latitude;
            $previousLng = $profile->longitude;
            $coordsChanged = (string) $previousLat !== (string) $validated->latitude
                || (string) $previousLng !== (string) $validated->longitude;

            if ($isDryRun) {
                $updated++;
                $this->line(
                    "  [DRY-RUN] Profile #{$profile->id} → {$validated->formattedAddress}"
                    ." [{$validated->provider}]"
                    .($coordsChanged ? ' (coords would change)' : ''),
                );

                return;
            }

            // Apply the update.
            $profile->update([
                'formatted_address' => $validated->formattedAddress,
                'street_address' => $validated->streetAddress,
                'locality' => $validated->locality,
                'latitude' => $validated->latitude,
                'longitude' => $validated->longitude,
                'geocoding_provider' => $validated->provider,
                'geocoding_place_id' => $validated->providerPlaceId,
                'geocoded_at' => now(),
                'geocoding_payload' => $validated->rawPayload,
            ]);

            $updated++;

            Log::info('addresses:backfill updated coach_profile', [
                'model' => 'CoachProfile',
                'model_id' => $profile->id,
                'provider' => $validated->provider,
                'coords_changed' => $coordsChanged,
                'formatted_address' => $validated->formattedAddress,
            ]);

            $this->line(
                "  [OK] Profile #{$profile->id} → {$validated->formattedAddress}"
                ." [{$validated->provider}]"
                .($coordsChanged ? ' (coords updated)' : ''),
            );
        });

        $this->newLine();
        $this->info("Processed: {$processed} | Updated: {$updated} | Failed: {$failed}");

        return self::SUCCESS;
    }
}
