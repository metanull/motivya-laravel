<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Uat\UatScenarioService;
use Illuminate\Console\Command;

final class PlayUatScenario extends Command
{
    protected $signature = 'uat:play-scenario
                            {--run-id= : Scenario run ID, defaults to uat_YYYYMMDD_HHMMSS}
                            {--coaches=5 : Number of coaches to create}
                            {--athletes=15 : Number of athletes to create}
                            {--sessions-per-coach=5 : Number of sessions per coach}
                            {--window-days=30 : Date window before/after today}
                            {--payments=simulated : Payment mode: simulated or stripe}
                            {--failed-payment-rate=10 : Percentage of bookings that should fail payment}
                            {--exceptional-refunds=2 : Number of confirmed bookings refunded by admin}
                            {--fresh : Delete prior UAT-generated scenario data first}
                            {--force : Required to run}
                            {--confirm-stripe : Required when --payments=stripe}';

    protected $description = 'Play a realistic UAT scenario with coaches, athletes, sessions, bookings, payments, refunds, and captured mail';

    public function handle(UatScenarioService $scenarioService): int
    {
        if (! (bool) $this->option('force')) {
            $this->error('Refusing to play UAT scenario without --force.');

            return self::FAILURE;
        }

        $options = [
            'run_id' => $this->stringOption('run-id') ?? 'uat_'.now()->format('Ymd_His'),
            'coaches' => $this->positiveIntegerOption('coaches', 5),
            'athletes' => $this->positiveIntegerOption('athletes', 15),
            'sessions_per_coach' => $this->positiveIntegerOption('sessions-per-coach', 5),
            'window_days' => $this->positiveIntegerOption('window-days', 30),
            'payments' => $this->stringOption('payments') ?? 'simulated',
            'failed_payment_rate' => max(1, min(90, $this->positiveIntegerOption('failed-payment-rate', 10))),
            'exceptional_refunds' => $this->positiveIntegerOption('exceptional-refunds', 2),
            'fresh' => (bool) $this->option('fresh'),
            'force' => (bool) $this->option('force'),
            'confirm_stripe' => (bool) $this->option('confirm-stripe'),
        ];

        try {
            $summary = $scenarioService->play($options);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('UAT scenario completed.');
        $this->table(
            ['Metric', 'Value'],
            collect($summary)->map(fn (int|string $value, string $key): array => [$key, $value])->values()->all(),
        );

        $this->line('Review captured mail with: php artisan uat:mail:list --run-id='.$summary['run_id']);

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function positiveIntegerOption(string $name, int $default): int
    {
        $value = $this->option($name);

        if (! is_numeric($value)) {
            return $default;
        }

        return max(1, (int) $value);
    }
}
