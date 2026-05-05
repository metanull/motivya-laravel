<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\PostalCodeCoordinatesSeeder;
use Illuminate\Console\Command;

final class LoadPostalCodes extends Command
{
    protected $signature = 'geo:load-postal-codes';

    protected $description = 'Load Belgian postal-code to coordinate reference data (idempotent, production-safe). No demo accounts are created.';

    public function handle(): int
    {
        $this->info('Loading Belgian postal-code coordinates...');

        $seeder = new PostalCodeCoordinatesSeeder;
        $seeder->run();

        $this->info('Postal-code coordinates loaded successfully.');

        return Command::SUCCESS;
    }
}
