<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Booking;
use App\Models\CoachPayoutStatement;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CoachPayoutStatementService;
use App\Services\InvoiceService;
use App\Services\SubscriptionService;
use Illuminate\Support\Carbon;
use Stripe\PaymentIntent;

require_once __DIR__.'/Support.php';

describe('Stripe manual coach billing and subscription integration', function () {
    it('computes no-charge Freemium billing and paid-plan subscription billing', function (): void {
        $stripe = requireLiveStripeIntegration();
        $qaRunId = manualStripeQaRunId('coach_billing');
        $month = Carbon::parse('2026-01-01');

        $freemiumCoach = manualStripeCoach("{$qaRunId}_free", $stripe['connected_account_id'], true);
        $premiumCoach = manualStripeCoach("{$qaRunId}_paid", $stripe['connected_account_id'], false);

        Invoice::factory()->invoice()->for($freemiumCoach, 'coach')->create([
            'billing_period_start' => '2026-01-15',
            'billing_period_end' => '2026-01-15',
            'revenue_ttc' => 30000,
            'revenue_htva' => intdiv(30000 * 100 + 60, 121),
            'vat_amount' => (int) round(intdiv(30000 * 100 + 60, 121) * 21 / 100),
            'status' => InvoiceStatus::Draft->value,
            'type' => InvoiceType::Invoice->value,
            'tax_category_code' => 'S',
        ]);

        Invoice::factory()->invoice()->for($premiumCoach, 'coach')->create([
            'billing_period_start' => '2026-01-20',
            'billing_period_end' => '2026-01-20',
            'revenue_ttc' => 176000,
            'revenue_htva' => intdiv(176000 * 100 + 60, 121),
            'vat_amount' => 0,
            'status' => InvoiceStatus::Draft->value,
            'type' => InvoiceType::Invoice->value,
            'tax_category_code' => 'E',
        ]);

        $freemiumSubscription = app(SubscriptionService::class)->computeForMonth($freemiumCoach, $month);

        expect($freemiumSubscription->applied_plan)->toBe(SubscriptionPlan::Freemium)
            ->and($freemiumSubscription->subscription_fee)->toBe(0);

        $premiumCoach->createOrGetStripeCustomer([
            'metadata' => ['qa_run_id' => $qaRunId],
        ]);
        $premiumCoach->addPaymentMethod('pm_card_visa');
        $premiumCoach->updateDefaultPaymentMethod('pm_card_visa');

        $premiumSubscription = app(SubscriptionService::class)->computeForMonth($premiumCoach, $month);

        expect($premiumSubscription->applied_plan)->toBe(SubscriptionPlan::Premium)
            ->and($premiumSubscription->subscription_fee)->toBe(7900)
            ->and($premiumSubscription->commission_rate)->toBe(10)
            ->and($premiumCoach->hasDefaultPaymentMethod())->toBeTrue();

        $subscriptionCharge = PaymentIntent::all([
            'customer' => $premiumCoach->stripe_id,
            'limit' => 5,
        ])->data[0] ?? null;

        expect($subscriptionCharge)->not->toBeNull()
            ->and($subscriptionCharge->amount)->toBe(7900)
            ->and($subscriptionCharge->status)->toBe('succeeded')
            ->and($subscriptionCharge->metadata?->subscription_id)->toBe((string) $premiumSubscription->id);

        $completedSession = manualStripeSession("{$qaRunId}_issued", $premiumCoach, [
            'date' => '2026-01-24',
            'status' => SessionStatus::Completed->value,
            'price_per_person' => 4500,
            'current_participants' => 2,
        ]);

        Booking::factory()
            ->confirmed()
            ->for($completedSession, 'sportSession')
            ->for(manualStripeAthlete("{$qaRunId}_issued_a"), 'athlete')
            ->create([
                'amount_paid' => 4500,
                'stripe_payment_intent_id' => "pi_{$qaRunId}_issued_a",
            ]);

        Booking::factory()
            ->confirmed()
            ->for($completedSession, 'sportSession')
            ->for(manualStripeAthlete("{$qaRunId}_issued_b"), 'athlete')
            ->create([
                'amount_paid' => 4500,
                'stripe_payment_intent_id' => "pi_{$qaRunId}_issued_b",
            ]);

        $issuedInvoice = app(InvoiceService::class)->generateForCompletedSession($completedSession);

        expect($issuedInvoice->type)->toBe(InvoiceType::Invoice)
            ->and($issuedInvoice->sport_session_id)->toBe($completedSession->id)
            ->and($issuedInvoice->revenue_ttc)->toBe(9000)
            ->and($issuedInvoice->xml_path)->not->toBeNull();

        $statement = app(CoachPayoutStatementService::class)->generateForCoach($premiumCoach, 2026, 1);

        expect($statement)->toBeInstanceOf(CoachPayoutStatement::class)
            ->and($statement->sessions_count)->toBeGreaterThanOrEqual(1)
            ->and($statement->paid_bookings_count)->toBeGreaterThanOrEqual(2)
            ->and($statement->revenue_ttc)->toBeGreaterThanOrEqual(9000);

        test()->actingAs($premiumCoach)->get(route('coach.payout-history'))->assertOk();

        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $admin = User::factory()->admin()->withTwoFactor()->create();

        test()->actingAs($accountant)->get(route('accountant.dashboard'))->assertOk();
        test()->actingAs($accountant)->get(route('accountant.payout-statements.index'))->assertOk();
        test()->actingAs($admin)->get(route('admin.configuration.billing'))->assertOk();
    });
});
