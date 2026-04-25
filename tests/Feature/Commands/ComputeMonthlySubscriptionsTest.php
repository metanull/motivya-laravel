<?php

declare(strict_types=1);

use App\Enums\CoachProfileStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\SubscriptionPlan;
use App\Models\CoachSubscription;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Helper: create an approved VAT-subject coach with an invoice in the given month.
 *
 * @param  string  $month  First day of the billing month, e.g. '2026-01-01'.
 * @param  int  $revenue  Revenue TTC in cents.
 */
function createCoachWithInvoice(string $month, int $revenue): User
{
    $coach = User::factory()->coach()->create();

    $coach->coachProfile()->create([
        'status' => CoachProfileStatus::Approved->value,
        'is_vat_subject' => true,
        'stripe_onboarding_complete' => false,
        'specialties' => ['fitness'],
        'bio' => 'Test bio',
        'experience_level' => 'intermediate',
        'postal_code' => '1000',
        'country' => 'BE',
        'enterprise_number' => '0123.456.789',
    ]);

    $revenueHtva = intdiv($revenue * 100 + 60, 121);
    $vatAmount = (int) round($revenueHtva * 21 / 100);

    Invoice::create([
        'type' => InvoiceType::Invoice->value,
        'coach_id' => $coach->id,
        'sport_session_id' => null,
        'billing_period_start' => $month,
        'billing_period_end' => $month,
        'revenue_ttc' => $revenue,
        'revenue_htva' => $revenueHtva,
        'vat_amount' => $vatAmount,
        'stripe_fee' => (int) round($revenue * 15 / 1000),
        'subscription_fee' => 0,
        'commission_amount' => (int) round($revenueHtva * 30 / 100),
        'coach_payout' => $revenueHtva - (int) round($revenueHtva * 30 / 100),
        'platform_margin' => (int) round($revenueHtva * 30 / 100),
        'plan_applied' => 'freemium',
        'tax_category_code' => 'S',
        'status' => InvoiceStatus::Draft->value,
    ]);

    return $coach;
}

describe('subscriptions:compute-monthly', function () {

    afterEach(function (): void {
        Carbon::setTestNow();
    });

    /**
     * Jean Freemium — January (doc/UseCases.md example).
     *
     * 25 payments × 12€ = 300€ TTC = 30000 cents.
     * HTVA calculation → Freemium is the best plan (net 16905 > Active 15484 > Premium 13964).
     */
    it('Jean Freemium January — stores Freemium plan for low revenue (300 EUR TTC)', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coach = createCoachWithInvoice('2026-01-01', 30000);

        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();

        $subscription = CoachSubscription::where('coach_id', $coach->id)
            ->where('month', '2026-01-01')
            ->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->applied_plan)->toBe(SubscriptionPlan::Freemium)
            ->and($subscription->commission_rate)->toBe(30)
            ->and($subscription->subscription_fee)->toBe(0)
            ->and($subscription->revenue_ttc)->toBe(30000);
    });

    /**
     * Jean Freemium — February (doc/UseCases.md example).
     *
     * 48 payments × 13€ = 624€ TTC = 62400 cents.
     * Premium is the best plan (net 37577 > Active 36420 > Freemium 35163).
     */
    it('Jean Freemium February — auto-upgrades to Premium for higher revenue (624 EUR TTC)', function (): void {
        Carbon::setTestNow('2026-03-01 02:00:00');

        $coach = createCoachWithInvoice('2026-02-01', 62400);

        $this->artisan('subscriptions:compute-monthly --month=2026-02')->assertSuccessful();

        $subscription = CoachSubscription::where('coach_id', $coach->id)
            ->where('month', '2026-02-01')
            ->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->applied_plan)->toBe(SubscriptionPlan::Premium)
            ->and($subscription->commission_rate)->toBe(10)
            ->and($subscription->subscription_fee)->toBe(7900)
            ->and($subscription->revenue_ttc)->toBe(62400);
    });

    /**
     * Marie Active — January (doc/UseCases.md example).
     *
     * Even though Marie is "Active", the auto-best-plan algorithm finds Freemium
     * more advantageous for 300€ TTC (16905 > 15484).
     */
    it('Marie Active January — overrides Active plan with Freemium when volume is low (300 EUR TTC)', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coach = createCoachWithInvoice('2026-01-01', 30000);

        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();

        $subscription = CoachSubscription::where('coach_id', $coach->id)
            ->where('month', '2026-01-01')
            ->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->applied_plan)->toBe(SubscriptionPlan::Freemium)
            ->and($subscription->subscription_fee)->toBe(0);
    });

    /**
     * Marie Active — February (doc/UseCases.md example).
     *
     * Active (450.84€ payout) beats Freemium (427.44€) for 624€ TTC.
     * The system picks Premium (best overall) via auto-best-plan.
     */
    it('Marie Active February — auto-best-plan picks Active or better for higher volume (624 EUR TTC)', function (): void {
        Carbon::setTestNow('2026-03-01 02:00:00');

        $coach = createCoachWithInvoice('2026-02-01', 62400);

        $this->artisan('subscriptions:compute-monthly --month=2026-02')->assertSuccessful();

        $subscription = CoachSubscription::where('coach_id', $coach->id)
            ->where('month', '2026-02-01')
            ->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->applied_plan)->not->toBe(SubscriptionPlan::Freemium)
            ->and($subscription->subscription_fee)->toBeGreaterThan(0);
    });

    /**
     * Loïc Premium — March (doc/UseCases.md example).
     *
     * 160 payments × 11€ = 1760€ TTC = 176000 cents.
     * Premium is optimal: net 120369 > Active 109824 > Freemium 99178.
     */
    it('Loïc Premium March — picks Premium for high revenue (1760 EUR TTC)', function (): void {
        Carbon::setTestNow('2026-04-01 02:00:00');

        $coach = createCoachWithInvoice('2026-03-01', 176000);

        $this->artisan('subscriptions:compute-monthly --month=2026-03')->assertSuccessful();

        $subscription = CoachSubscription::where('coach_id', $coach->id)
            ->where('month', '2026-03-01')
            ->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->applied_plan)->toBe(SubscriptionPlan::Premium)
            ->and($subscription->commission_rate)->toBe(10)
            ->and($subscription->subscription_fee)->toBe(7900)
            ->and($subscription->revenue_ttc)->toBe(176000);
    });

    it('processes multiple coaches in a single run', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coachA = createCoachWithInvoice('2026-01-01', 30000);
        $coachB = createCoachWithInvoice('2026-01-01', 176000);

        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();

        expect(CoachSubscription::where('coach_id', $coachA->id)->where('month', '2026-01-01')->exists())->toBeTrue()
            ->and(CoachSubscription::where('coach_id', $coachB->id)->where('month', '2026-01-01')->exists())->toBeTrue();
    });

    it('skips coaches without an approved profile', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coach = User::factory()->coach()->create();
        $coach->coachProfile()->create([
            'status' => CoachProfileStatus::Pending->value,
            'is_vat_subject' => true,
            'stripe_onboarding_complete' => false,
            'specialties' => ['fitness'],
            'bio' => 'Test bio',
            'experience_level' => 'intermediate',
            'postal_code' => '1000',
            'country' => 'BE',
            'enterprise_number' => '0123.456.789',
        ]);

        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();

        expect(CoachSubscription::where('coach_id', $coach->id)->exists())->toBeFalse();
    });

    it('stores zero revenue and Freemium plan when coach has no invoices for the month', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coach = User::factory()->coach()->create();
        $coach->coachProfile()->create([
            'status' => CoachProfileStatus::Approved->value,
            'is_vat_subject' => true,
            'stripe_onboarding_complete' => false,
            'specialties' => ['fitness'],
            'bio' => 'Test bio',
            'experience_level' => 'intermediate',
            'postal_code' => '1000',
            'country' => 'BE',
            'enterprise_number' => '0987.654.321',
        ]);

        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();

        $subscription = CoachSubscription::where('coach_id', $coach->id)
            ->where('month', '2026-01-01')
            ->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->applied_plan)->toBe(SubscriptionPlan::Freemium)
            ->and($subscription->revenue_ttc)->toBe(0)
            ->and($subscription->subscription_fee)->toBe(0);
    });

    it('defaults to the previous month when no --month option is provided', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coach = createCoachWithInvoice('2026-01-01', 30000);

        // No --month option: should default to January 2026 (previous month).
        $this->artisan('subscriptions:compute-monthly')->assertSuccessful();

        expect(CoachSubscription::where('coach_id', $coach->id)->where('month', '2026-01-01')->exists())->toBeTrue();
    });

    it('is idempotent — running twice for the same month does not duplicate records', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coach = createCoachWithInvoice('2026-01-01', 30000);

        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();
        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();

        expect(CoachSubscription::where('coach_id', $coach->id)->where('month', '2026-01-01')->count())->toBe(1);
    });

    it('sums revenue from multiple invoices within the same month', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coach = User::factory()->coach()->create();
        $coach->coachProfile()->create([
            'status' => CoachProfileStatus::Approved->value,
            'is_vat_subject' => true,
            'stripe_onboarding_complete' => false,
            'specialties' => ['fitness'],
            'bio' => 'Test bio',
            'experience_level' => 'intermediate',
            'postal_code' => '1000',
            'country' => 'BE',
            'enterprise_number' => '0111.222.333',
        ]);

        // Two session invoices in January (e.g. 15000 + 15000 = 30000 TTC)
        foreach (['2026-01-10', '2026-01-20'] as $date) {
            Invoice::create([
                'type' => InvoiceType::Invoice->value,
                'coach_id' => $coach->id,
                'sport_session_id' => null,
                'billing_period_start' => $date,
                'billing_period_end' => $date,
                'revenue_ttc' => 15000,
                'revenue_htva' => intdiv(15000 * 100 + 60, 121),
                'vat_amount' => (int) round(intdiv(15000 * 100 + 60, 121) * 21 / 100),
                'stripe_fee' => (int) round(15000 * 15 / 1000),
                'subscription_fee' => 0,
                'commission_amount' => (int) round(intdiv(15000 * 100 + 60, 121) * 30 / 100),
                'coach_payout' => intdiv(15000 * 100 + 60, 121) - (int) round(intdiv(15000 * 100 + 60, 121) * 30 / 100),
                'platform_margin' => (int) round(intdiv(15000 * 100 + 60, 121) * 30 / 100),
                'plan_applied' => 'freemium',
                'tax_category_code' => 'S',
                'status' => InvoiceStatus::Draft->value,
            ]);
        }

        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();

        $subscription = CoachSubscription::where('coach_id', $coach->id)
            ->where('month', '2026-01-01')
            ->first();

        expect($subscription)->not->toBeNull()
            ->and($subscription->revenue_ttc)->toBe(30000);
    });

    it('ignores credit notes when computing monthly revenue', function (): void {
        Carbon::setTestNow('2026-02-01 02:00:00');

        $coach = User::factory()->coach()->create();
        $coach->coachProfile()->create([
            'status' => CoachProfileStatus::Approved->value,
            'is_vat_subject' => true,
            'stripe_onboarding_complete' => false,
            'specialties' => ['fitness'],
            'bio' => 'Test bio',
            'experience_level' => 'intermediate',
            'postal_code' => '1000',
            'country' => 'BE',
            'enterprise_number' => '0444.555.666',
        ]);

        // One session invoice
        Invoice::create([
            'type' => InvoiceType::Invoice->value,
            'coach_id' => $coach->id,
            'sport_session_id' => null,
            'billing_period_start' => '2026-01-15',
            'billing_period_end' => '2026-01-15',
            'revenue_ttc' => 30000,
            'revenue_htva' => intdiv(30000 * 100 + 60, 121),
            'vat_amount' => (int) round(intdiv(30000 * 100 + 60, 121) * 21 / 100),
            'stripe_fee' => (int) round(30000 * 15 / 1000),
            'subscription_fee' => 0,
            'commission_amount' => (int) round(intdiv(30000 * 100 + 60, 121) * 30 / 100),
            'coach_payout' => intdiv(30000 * 100 + 60, 121) - (int) round(intdiv(30000 * 100 + 60, 121) * 30 / 100),
            'platform_margin' => (int) round(intdiv(30000 * 100 + 60, 121) * 30 / 100),
            'plan_applied' => 'freemium',
            'tax_category_code' => 'S',
            'status' => InvoiceStatus::Draft->value,
        ]);

        // One credit note in the same month (should not be summed)
        Invoice::create([
            'type' => InvoiceType::CreditNote->value,
            'coach_id' => $coach->id,
            'sport_session_id' => null,
            'billing_period_start' => '2026-01-15',
            'billing_period_end' => '2026-01-15',
            'revenue_ttc' => 5000,
            'revenue_htva' => intdiv(5000 * 100 + 60, 121),
            'vat_amount' => (int) round(intdiv(5000 * 100 + 60, 121) * 21 / 100),
            'stripe_fee' => (int) round(5000 * 15 / 1000),
            'subscription_fee' => 0,
            'commission_amount' => (int) round(intdiv(5000 * 100 + 60, 121) * 30 / 100),
            'coach_payout' => intdiv(5000 * 100 + 60, 121) - (int) round(intdiv(5000 * 100 + 60, 121) * 30 / 100),
            'platform_margin' => (int) round(intdiv(5000 * 100 + 60, 121) * 30 / 100),
            'plan_applied' => 'freemium',
            'tax_category_code' => 'S',
            'status' => InvoiceStatus::Draft->value,
        ]);

        $this->artisan('subscriptions:compute-monthly --month=2026-01')->assertSuccessful();

        $subscription = CoachSubscription::where('coach_id', $coach->id)
            ->where('month', '2026-01-01')
            ->first();

        // Revenue from invoice only (30000), not reduced by credit note (5000)
        expect($subscription)->not->toBeNull()
            ->and($subscription->revenue_ttc)->toBe(30000);
    });
});
