<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CoachProfileStatus;
use App\Enums\UserRole;
use App\Models\SchedulerHeartbeat;
use App\Models\User;
use App\Services\CoachPayoutStatementService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class GenerateMonthlyPayoutStatements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payout-statements:generate-monthly
                            {--year= : The year of the period to generate (default: previous month)}
                            {--month= : The month (1-12) of the period to generate (default: previous month)}
                            {--coach= : Limit generation to a single coach by user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate (or refresh draft) monthly payout statements for coaches with completed paid sessions.';

    public function handle(CoachPayoutStatementService $service): int
    {
        [$year, $month] = $this->resolvePeriod();

        if ($year === null || $month === null) {
            return self::FAILURE;
        }

        $coachId = $this->option('coach');

        $query = User::query()
            ->where('role', UserRole::Coach->value)
            ->whereHas(
                'coachProfile',
                fn ($q) => $q->where('status', CoachProfileStatus::Approved->value),
            )
            ->with('coachProfile');

        if ($coachId !== null) {
            $query->where('id', (int) $coachId);
        }

        $coaches = $query->get();

        $generated = 0;
        $skipped = 0;

        foreach ($coaches as $coach) {
            try {
                $service->generateForCoach($coach, $year, $month);
                $generated++;
            } catch (InvalidArgumentException) {
                $skipped++;
            }
        }

        $this->info("Payout statements generated: {$generated}, skipped: {$skipped} for {$year}-{$month}.");

        SchedulerHeartbeat::record('payout-statements:generate-monthly');

        return self::SUCCESS;
    }

    /**
     * Resolve the target period from options, defaulting to the previous calendar month.
     *
     * @return array{int|null, int|null}
     */
    private function resolvePeriod(): array
    {
        $yearOpt = $this->option('year');
        $monthOpt = $this->option('month');

        if ($yearOpt !== null || $monthOpt !== null) {
            $year = $yearOpt !== null ? (int) $yearOpt : null;
            $month = $monthOpt !== null ? (int) $monthOpt : null;

            if ($year === null || $year < 2020 || $year > 2100) {
                $this->error('Invalid year. Provide --year=YYYY (e.g. --year=2026).');

                return [null, null];
            }

            if ($month === null || $month < 1 || $month > 12) {
                $this->error('Invalid month. Provide --month=M where M is between 1 and 12.');

                return [null, null];
            }

            return [$year, $month];
        }

        $previous = Carbon::now()->subMonth();

        return [$previous->year, $previous->month];
    }
}
