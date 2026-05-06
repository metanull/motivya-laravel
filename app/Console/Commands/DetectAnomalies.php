<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AnomalyDetectorService;
use Illuminate\Console\Command;

final class DetectAnomalies extends Command
{
    protected $signature = 'payments:detect-anomalies';

    protected $description = 'Scan for payment anomalies and persist results to payment_anomalies table';

    public function handle(AnomalyDetectorService $service): int
    {
        $this->info('Detecting payment anomalies…');
        $service->detectAll();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
