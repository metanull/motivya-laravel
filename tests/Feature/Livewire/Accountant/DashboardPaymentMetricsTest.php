<?php

declare(strict_types=1);

use App\Enums\CoachPayoutStatementStatus;
use App\Livewire\Accountant\Dashboard;
use App\Models\Booking;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\StripeTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('accountant dashboard — collected payment and transfer metrics', function () {

    it('shows zero collected payments and transfers when no data exists', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryCollectedPaymentsAmount'))->toBe(0);
        expect($component->get('summaryCollectedPaymentsCount'))->toBe(0);
        expect($component->get('summaryTransfersCount'))->toBe(0);
        expect($component->get('summaryDraftPayoutStatementsCount'))->toBe(0);
    });

    it('sums amount_paid on confirmed bookings updated this month', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();

        Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 3000,
            'updated_at' => now(),
        ]);

        Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 2000,
            'updated_at' => now(),
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryCollectedPaymentsAmount'))->toBe(5000);
        expect($component->get('summaryCollectedPaymentsCount'))->toBe(2);
    });

    it('counts StripeTransfer records created this month', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        StripeTransfer::create([
            'stripe_transfer_id' => 'tr_abc1',
            'amount' => 1200,
            'currency' => 'eur',
            'status' => 'created',
            'created_at' => now(),
        ]);

        StripeTransfer::create([
            'stripe_transfer_id' => 'tr_abc2',
            'amount' => 800,
            'currency' => 'eur',
            'status' => 'created',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryTransfersCount'))->toBe(2);
    });

    it('does not count transfers from previous months', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $transfer = StripeTransfer::create([
            'stripe_transfer_id' => 'tr_old_1',
            'amount' => 500,
            'currency' => 'eur',
            'status' => 'created',
        ]);

        // Move the created_at to the previous month to simulate an old transfer.
        DB::table('stripe_transfers')
            ->where('id', $transfer->id)
            ->update(['created_at' => now()->subMonth()]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryTransfersCount'))->toBe(0);
    });

    it('counts draft coach payout statements', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        CoachPayoutStatement::factory()->forPeriod(now()->year, now()->month)->create([
            'coach_id' => $coach->id,
            'status' => CoachPayoutStatementStatus::Draft,
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryDraftPayoutStatementsCount'))->toBe(1);
    });

    it('does not count non-draft payout statements', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        CoachPayoutStatement::factory()->forPeriod(now()->year, now()->month)->create([
            'coach_id' => $coach->id,
            'status' => CoachPayoutStatementStatus::InvoiceSubmitted,
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryDraftPayoutStatementsCount'))->toBe(0);
    });

    it('renders new summary card labels', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->assertSee(__('accountant.summary_collected_payments'))
            ->assertSee(__('accountant.summary_transfers'))
            ->assertSee(__('accountant.summary_draft_payout_statements'));
    });
});
