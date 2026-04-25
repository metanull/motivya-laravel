<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CoachProfileStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class ComputeMonthlySubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:compute-monthly
                            {--month= : Billing month in YYYY-MM format (defaults to the previous month)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compute and store the best subscription plan for all active coaches';

    public function handle(SubscriptionService $service): int
    {
        $monthArg = $this->option('month');

        if ($monthArg !== null) {
            $month = Carbon::createFromFormat('Y-m', (string) $monthArg)->startOfMonth();
        } else {
            $month = now()->subMonth()->startOfMonth();
        }

        $coaches = User::query()
            ->where('role', UserRole::Coach->value)
            ->whereHas(
                'coachProfile',
                fn ($q) => $q->where('status', CoachProfileStatus::Approved->value),
            )
            ->with('coachProfile')
            ->get();

        foreach ($coaches as $coach) {
            $service->computeForMonth($coach, $month);
        }

        return self::SUCCESS;
    }
}
