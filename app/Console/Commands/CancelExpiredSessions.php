<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\SchedulerHeartbeat;
use App\Models\SportSession;
use App\Services\SessionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class CancelExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel published sessions that have passed the cancellation deadline without reaching minimum participants';

    public function handle(SessionService $service): int
    {
        // Configurable deadline: how many hours before the session start to trigger cancellation.
        // Defaults to 0 (cancel once the session start time has passed).
        $deadlineHours = (int) config('sessions.cancellation_deadline_hours', 0);

        $cutoff = now()->addHours($deadlineHours);

        // Narrow by date in the DB (cross-DB compatible), then filter by
        // the combined date+time in PHP to avoid DB-specific datetime functions.
        $sessions = SportSession::query()
            ->where('status', SessionStatus::Published)
            ->whereDate('date', '<=', $cutoff->toDateString())
            ->get()
            ->filter(function (SportSession $session) use ($cutoff): bool {
                $sessionStart = Carbon::parse(
                    $session->date->format('Y-m-d').' '.$session->start_time,
                );

                return $sessionStart->lte($cutoff);
            });

        foreach ($sessions as $session) {
            $service->cancel($session);
        }

        SchedulerHeartbeat::record('sessions:cancel-expired');

        return self::SUCCESS;
    }
}
