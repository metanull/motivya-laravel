<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CoachProfileStatus;
use App\Models\CoachProfile;
use Closure;
use InvalidArgumentException;
use RuntimeException;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;

final class StripeConnectService
{
    public function __construct(
        private readonly ?Closure $createAccountUsing = null,
        private readonly ?Closure $createAccountLinkUsing = null,
    ) {}

    /**
     * Create a Stripe Express account for an approved coach.
     */
    public function createExpressAccount(CoachProfile $coach): string
    {
        if ($coach->status !== CoachProfileStatus::Approved) {
            throw new InvalidArgumentException('Only approved coaches can create a Stripe Express account.');
        }

        if (is_string($coach->stripe_account_id) && $coach->stripe_account_id !== '') {
            return $coach->stripe_account_id;
        }

        $account = $this->createStripeAccount([
            'type' => 'express',
            'country' => 'BE',
            'email' => $coach->user->email,
            'capabilities' => [
                'transfers' => ['requested' => true],
                'bancontact_payments' => ['requested' => true],
            ],
            'business_profile' => [
                'mcc' => '7941',
                'url' => route('coaches.show', $coach->user),
            ],
            'metadata' => [
                'coach_id' => (string) $coach->id,
                'enterprise_number' => (string) $coach->enterprise_number,
            ],
        ]);

        if (! isset($account->id) || ! is_string($account->id) || $account->id === '') {
            throw new RuntimeException('Stripe did not return an account identifier.');
        }

        $coach->forceFill([
            'stripe_account_id' => $account->id,
        ])->save();

        return $account->id;
    }

    /**
     * Generate a single-use Stripe onboarding link for a coach account.
     */
    public function generateOnboardingLink(CoachProfile $coach): string
    {
        if (! is_string($coach->stripe_account_id) || $coach->stripe_account_id === '') {
            throw new InvalidArgumentException('Coach profile does not have a Stripe account identifier.');
        }

        $accountLink = $this->createStripeAccountLink([
            'account' => $coach->stripe_account_id,
            'refresh_url' => route('coach.stripe.refresh'),
            'return_url' => route('coach.stripe.return'),
            'type' => 'account_onboarding',
        ]);

        if (! isset($accountLink->url) || ! is_string($accountLink->url) || $accountLink->url === '') {
            throw new RuntimeException('Stripe did not return an onboarding link URL.');
        }

        return $accountLink->url;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function createStripeAccount(array $payload): object
    {
        if ($this->createAccountUsing instanceof Closure) {
            return ($this->createAccountUsing)($payload);
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));

        return Account::create($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function createStripeAccountLink(array $payload): object
    {
        if ($this->createAccountLinkUsing instanceof Closure) {
            return ($this->createAccountLinkUsing)($payload);
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));

        return AccountLink::create($payload);
    }
}
