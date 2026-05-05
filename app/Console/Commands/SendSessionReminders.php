<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\SchedulerHeartbeat;
use App\Models\SportSession;
use App\Notifications\SessionReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class SendSessionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications to athletes for sessions starting in ~24 hours';

    public function handle(): int
    {
        $windowStart = now()->addHours(23);
        $windowEnd = now()->addHours(25);

        // Narrow by date range in the DB (cross-DB compatible), then filter by
        // the combined date+time in PHP to avoid DB-specific datetime functions.
        $sessions = SportSession::query()
            ->where('status', SessionStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->whereDate('date', '>=', $windowStart->toDateString())
            ->whereDate('date', '<=', $windowEnd->toDateString())
            ->get()
            ->filter(function (SportSession $session) use ($windowStart, $windowEnd): bool {
                $sessionStart = Carbon::parse(
                    $session->date->format('Y-m-d').' '.$session->start_time,
                );

                return $sessionStart->between($windowStart, $windowEnd);
            });

        foreach ($sessions as $session) {
            $bookings = $session->bookings()
                ->where('status', BookingStatus::Confirmed)
                ->with('athlete')
                ->get();

            foreach ($bookings as $booking) {
                $athlete = $booking->athlete;

                $athlete->notify(
                    (new SessionReminderNotification($session->id))
                        ->locale($athlete->locale ?? 'fr'),
                );
            }

            // Mark as sent via direct assignment (reminder_sent_at is in $fillable)
            $session->reminder_sent_at = now()->toDateTimeString();
            $session->save();
        }

        SchedulerHeartbeat::record('sessions:send-reminders');

        return self::SUCCESS;
    }
}
