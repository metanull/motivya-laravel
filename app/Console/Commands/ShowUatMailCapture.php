<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\UatMailCapture;
use Illuminate\Console\Command;

final class ShowUatMailCapture extends Command
{
    protected $signature = 'uat:mail:show
                            {id : Captured mail ID}
                            {--json : Output JSON for scripting}';

    protected $description = 'Show a captured UAT mail';

    public function handle(): int
    {
        $capture = UatMailCapture::find((int) $this->argument('id'));

        if ($capture === null) {
            $this->error('Captured mail not found.');

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($capture->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Mail #{$capture->id}");
        $this->line('Run ID: '.($capture->run_id ?? '—'));
        $this->line('To: '.collect($capture->to ?? [])->pluck('email')->implode(', '));
        $this->line('CC: '.collect($capture->cc ?? [])->pluck('email')->implode(', '));
        $this->line('BCC: '.collect($capture->bcc ?? [])->pluck('email')->implode(', '));
        $this->line('Subject: '.($capture->subject ?? '—'));
        $this->line('Captured At: '.($capture->captured_at?->toDateTimeString() ?? '—'));
        $this->newLine();
        $this->line('Text body:');
        $this->line($capture->text_body ?? '—');
        $this->newLine();
        $this->line('HTML body:');
        $this->line($capture->html_body ?? '—');

        return self::SUCCESS;
    }
}
