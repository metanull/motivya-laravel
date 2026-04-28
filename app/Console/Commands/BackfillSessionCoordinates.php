<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SportSession;
use App\Services\PostalCodeCoordinateService;
use Illuminate\Console\Command;

final class BackfillSessionCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:backfill-coordinates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill latitude/longitude on sport_sessions rows that have no coordinates';

    public function handle(PostalCodeCoordinateService $geoService): int
    {
        $total = SportSession::query()
            ->where(function ($query): void {
                $query->whereNull('latitude')->orWhereNull('longitude');
            })
            ->count();

        $updated = 0;

        SportSession::query()
            ->where(function ($query): void {
                $query->whereNull('latitude')->orWhereNull('longitude');
            })
            ->chunkById(100, function ($sessions) use ($geoService, &$updated): void {
                foreach ($sessions as $session) {
                    $coords = $geoService->resolveCoordinates($session->postal_code);

                    if ($coords !== null) {
                        $session->update([
                            'latitude' => $coords[0],
                            'longitude' => $coords[1],
                        ]);

                        $updated++;
                    }
                }
            });

        $this->info("Processed {$updated}/{$total} sessions.");

        return self::SUCCESS;
    }
}
