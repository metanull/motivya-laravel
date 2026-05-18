<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\UatMailCapture;
use Illuminate\Console\Command;

final class ClearUatMailCaptures extends Command
{
    protected $signature = 'uat:mail:clear
                            {--run-id= : Clear only one UAT scenario run ID}
                            {--force : Required to delete captures}';

    protected $description = 'Clear captured UAT mails';

    public function handle(): int
    {
        if (! (bool) $this->option('force')) {
            $this->error('Refusing to delete UAT mail captures without --force.');

            return self::FAILURE;
        }

        $runId = $this->stringOption('run-id');
        $query = UatMailCapture::query()
            ->when($runId !== null, fn ($query) => $query->where('run_id', $runId));

        $count = $query->count();
        $query->delete();

        $this->info("Deleted {$count} UAT mail capture(s).".($runId !== null ? " Run ID: {$runId}." : ''));

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
