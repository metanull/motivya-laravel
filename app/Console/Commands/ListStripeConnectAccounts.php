<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CoachProfileStatus;
use App\Models\CoachProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

final class ListStripeConnectAccounts extends Command
{
    protected $signature = 'stripe:connect-accounts
                            {--json : Output JSON for scripting}
                            {--usable-only : Include only approved, onboarded profiles with an acct_ identifier}
                            {--account-id-only : Print only the recommended usable Stripe account ID}
                            {--source=all : Account source: all, database, or stripe}';

    protected $description = 'List Stripe Connect account IDs attached to coach profiles';

    public function handle(): int
    {
        $source = (string) $this->option('source');

        if (! in_array($source, ['all', 'database', 'stripe'], true)) {
            $this->error('--source must be one of: all, database, stripe.');

            return self::FAILURE;
        }

        $accounts = $this->accounts();

        if ((bool) $this->option('usable-only')) {
            $accounts = $accounts->filter(fn (array $account): bool => $account['is_usable_for_uat'])->values();
        }

        $recommended = $accounts->firstWhere('is_usable_for_uat', true)['stripe_account_id'] ?? null;

        if ((bool) $this->option('account-id-only')) {
            if (! is_string($recommended) || $recommended === '') {
                $this->error('No usable Stripe Connect account was found.');

                return self::FAILURE;
            }

            $this->line($recommended);

            return self::SUCCESS;
        }

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode([
                'accounts' => $accounts->values()->all(),
                'recommended_account_id' => $recommended,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if ($accounts->isEmpty()) {
            $this->warn('No Stripe Connect accounts found.');

            return self::SUCCESS;
        }

        $this->table([
            'User ID',
            'Source',
            'Email',
            'Name',
            'Profile ID',
            'Status',
            'Stripe Account',
            'Onboarded',
            'Usable UAT',
        ], $accounts->map(fn (array $account): array => [
            $account['user_id'],
            $account['source'],
            $account['user_email'],
            $account['user_name'],
            $account['coach_profile_id'],
            $account['status'],
            $account['stripe_account_id'] ?? '—',
            $account['stripe_onboarding_complete'] ? 'yes' : 'no',
            $account['is_usable_for_uat'] ? 'yes' : 'no',
        ])->all());

        if (is_string($recommended)) {
            $this->info("Recommended UAT account: {$recommended}");
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array{user_id: int|null, user_email: string|null, user_name: string|null, coach_profile_id: int, status: string|null, stripe_account_id: string|null, stripe_onboarding_complete: bool, is_usable_for_uat: bool}>
     */
    private function accounts(): Collection
    {
        $source = (string) $this->option('source');

        $accounts = collect();

        if ($source !== 'stripe') {
            $accounts = $accounts->concat($this->databaseAccounts());
        }

        if ($source !== 'database') {
            $accounts = $accounts->concat($this->stripeAccounts());
        }

        return $accounts
            ->unique(fn (array $account): string => (string) ($account['stripe_account_id'] ?? spl_object_id((object) $account)))
            ->values();
    }

    /**
     * @return Collection<int, array{source: string, user_id: int|null, user_email: string|null, user_name: string|null, coach_profile_id: int|null, status: string|null, stripe_account_id: string|null, stripe_onboarding_complete: bool, is_usable_for_uat: bool}>
     */
    private function databaseAccounts(): Collection
    {
        return CoachProfile::query()
            ->with('user')
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->orderByDesc('stripe_onboarding_complete')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (CoachProfile $profile): array {
                $stripeAccountId = is_string($profile->stripe_account_id) ? $profile->stripe_account_id : null;
                $isUsable = $profile->status === CoachProfileStatus::Approved
                    && $profile->stripe_onboarding_complete
                    && is_string($stripeAccountId)
                    && str_starts_with($stripeAccountId, 'acct_');

                return [
                    'source' => 'database',
                    'user_id' => $profile->user?->id,
                    'user_email' => $profile->user?->email,
                    'user_name' => $profile->user?->name,
                    'coach_profile_id' => $profile->id,
                    'status' => $profile->status?->value,
                    'stripe_account_id' => $stripeAccountId,
                    'stripe_onboarding_complete' => (bool) $profile->stripe_onboarding_complete,
                    'is_usable_for_uat' => $isUsable,
                ];
            });
    }

    /**
     * @return Collection<int, array{source: string, user_id: null, user_email: string|null, user_name: string|null, coach_profile_id: null, status: string|null, stripe_account_id: string|null, stripe_onboarding_complete: bool, is_usable_for_uat: bool}>
     */
    private function stripeAccounts(): Collection
    {
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || ! str_starts_with($secret, 'sk_test_')) {
            return collect();
        }

        try {
            Stripe::setApiKey($secret);
            $accounts = StripeAccount::all(['limit' => 100]);
        } catch (\Throwable $exception) {
            $this->warn('Stripe account discovery failed: '.$exception->getMessage());

            return collect();
        }

        return collect($accounts->data)
            ->filter(fn (StripeAccount $account): bool => is_string($account->id) && str_starts_with($account->id, 'acct_'))
            ->map(fn (StripeAccount $account): array => [
                'source' => 'stripe',
                'user_id' => null,
                'user_email' => is_string($account->email ?? null) ? $account->email : null,
                'user_name' => is_string($account->business_profile?->name ?? null) ? $account->business_profile->name : null,
                'coach_profile_id' => null,
                'status' => is_string($account->type ?? null) ? $account->type : null,
                'stripe_account_id' => $account->id,
                'stripe_onboarding_complete' => (bool) ($account->charges_enabled ?? false),
                'is_usable_for_uat' => true,
            ])
            ->values();
    }
}
