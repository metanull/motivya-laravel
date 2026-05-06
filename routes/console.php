<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sessions:send-reminders')->hourly();
Schedule::command('sessions:cancel-expired')->hourly();
Schedule::command('sessions:complete-finished')->hourly();
Schedule::command('subscriptions:compute-monthly')->monthlyOn(1, '02:00');
Schedule::command('bookings:expire-unpaid')->everyFiveMinutes();
Schedule::command('payments:detect-anomalies')->hourly();
