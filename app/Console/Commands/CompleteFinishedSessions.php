<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\SportSession;
use App\Services\SessionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class CompleteFinishedSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:complete-finished';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark confirmed sessions as completed after their end time has passed';

    public function handle(SessionService $service): int
    {
        $now = now();

        // Narrow by date in the DB (cross-DB compatible), then filter by
        // the combined date+end_time in PHP to avoid DB-specific datetime functions.
        $sessions = SportSession::query()
            ->where('status', SessionStatus::Confirmed)
            ->whereDate('date', '<=', $now->toDateString())
            ->get()
            ->filter(function (SportSession $session) use ($now): bool {
                $sessionEnd = Carbon::parse(
                    $session->date->format('Y-m-d').' '.$session->end_time,
                );

                return $sessionEnd->lte($now);
            });

        foreach ($sessions as $session) {
            $service->complete($session);
        }

        return self::SUCCESS;
    }
}
