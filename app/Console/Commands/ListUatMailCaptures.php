<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\UatMailCapture;
use Illuminate\Console\Command;

final class ListUatMailCaptures extends Command
{
    protected $signature = 'uat:mail:list
                            {--run-id= : Filter by UAT scenario run ID}
                            {--limit=25 : Maximum rows to display}
                            {--json : Output JSON for scripting}';

    protected $description = 'List captured UAT mails';

    public function handle(): int
    {
        $limit = max(1, min(200, (int) $this->option('limit')));
        $runId = $this->stringOption('run-id');

        $captures = UatMailCapture::query()
            ->when($runId !== null, fn ($query) => $query->where('run_id', $runId))
            ->latest('captured_at')
            ->limit($limit)
            ->get();

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode(['mails' => $captures->toArray()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if ($captures->isEmpty()) {
            $this->warn('No UAT mail captures found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Run ID', 'To', 'Subject', 'Captured At'],
            $captures->map(fn (UatMailCapture $capture): array => [
                $capture->id,
                $capture->run_id ?? '—',
                collect($capture->to ?? [])->pluck('email')->implode(', '),
                $capture->subject ?? '—',
                $capture->captured_at?->toDateTimeString() ?? '—',
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
