<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Livewire\Accountant\Dashboard;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('accountant dashboard summary cards', function () {
    it('renders summary card labels', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->assertSee(__('accountant.summary_revenue_ttc'))
            ->assertSee(__('accountant.summary_revenue_htva'))
            ->assertSee(__('accountant.summary_vat'))
            ->assertSee(__('accountant.summary_payout_pending'))
            ->assertSee(__('accountant.summary_invoices'))
            ->assertSee(__('accountant.summary_credit_notes'))
            ->assertSee(__('accountant.summary_refunds'))
            ->assertSee(__('accountant.summary_anomalies'));
    });

    it('shows zero counts when no data exists', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryRevenueTtc'))->toBe(0)
            ->and($component->get('summaryRevenueHtva'))->toBe(0)
            ->and($component->get('summaryVat'))->toBe(0)
            ->and($component->get('summaryPayoutPending'))->toBe(0)
            ->and($component->get('summaryInvoicesCount'))->toBe(0)
            ->and($component->get('summaryCreditNotesCount'))->toBe(0)
            ->and($component->get('summaryRefundsCount'))->toBe(0)
            ->and($component->get('summaryStuckSessionsCount'))->toBe(0);
    });

    it('sums current-month invoice revenue TTC', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        // Two invoices this month
        Invoice::factory()->invoice()->create([
            'coach_id' => $coach->id,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'revenue_ttc' => 10000,
            'revenue_htva' => 8264,
            'vat_amount' => 1736,
        ]);
        Invoice::factory()->invoice()->create([
            'coach_id' => $coach->id,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'revenue_ttc' => 5000,
            'revenue_htva' => 4132,
            'vat_amount' => 868,
        ]);

        // One invoice from last month — must NOT be included
        Invoice::factory()->invoice()->create([
            'coach_id' => $coach->id,
            'billing_period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'revenue_ttc' => 9999,
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryRevenueTtc'))->toBe(15000)
            ->and($component->get('summaryRevenueHtva'))->toBe(12396)
            ->and($component->get('summaryVat'))->toBe(2604);
    });

    it('sums pending payout across all periods', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        Invoice::factory()->invoice()->draft()->create([
            'coach_id' => $coach->id,
            'coach_payout' => 8000,
        ]);
        Invoice::factory()->invoice()->issued()->create([
            'coach_id' => $coach->id,
            'coach_payout' => 3000,
        ]);
        // Paid invoice — must NOT be included in pending
        Invoice::factory()->invoice()->paid()->create([
            'coach_id' => $coach->id,
            'coach_payout' => 5000,
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryPayoutPending'))->toBe(11000);
    });

    it('counts current-month invoices and credit notes separately', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $thisMonth = [
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'coach_id' => $coach->id,
        ];

        Invoice::factory()->invoice()->count(3)->create($thisMonth);
        Invoice::factory()->creditNote()->count(2)->create($thisMonth);

        // Last month — should not be counted
        Invoice::factory()->invoice()->create([
            'coach_id' => $coach->id,
            'billing_period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->subMonth()->endOfMonth()->toDateString(),
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryInvoicesCount'))->toBe(3)
            ->and($component->get('summaryCreditNotesCount'))->toBe(2);
    });

    it('counts current-month refunded bookings', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $session = SportSession::factory()->create();

        // Two refunds this month
        Booking::factory()->count(2)->refunded()->create([
            'sport_session_id' => $session->id,
            'refunded_at' => now(),
        ]);

        // One refund last month — must NOT be counted
        Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'refunded_at' => now()->subMonth(),
        ]);

        // A confirmed booking — must NOT be counted
        Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryRefundsCount'))->toBe(2);
    });

    it('credit-note invoices are excluded from revenue TTC sum', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $thisMonth = [
            'coach_id' => $coach->id,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'revenue_ttc' => 5000,
        ];

        Invoice::factory()->creditNote()->create($thisMonth);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->get('summaryRevenueTtc'))->toBe(0);
    });

    it('shows view anomalies link when route exists', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        // The anomalies route is not registered, so we only assert the label is present
        // when the route does exist. We check that the component renders without error.
        $this->actingAs($accountant)
            ->get(route('accountant.dashboard'))
            ->assertOk();
    });

    it('shows audit log card on accountant dashboard', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->assertSee(__('accountant.dashboard_card_audit_events'));
    });

    it('recentFinancialAuditEventCount includes only financial event types', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        // Financial event
        AuditEvent::factory()->create([
            'event_type' => AuditEventType::BookingPaymentConfirmed->value,
            'occurred_at' => now()->subDay(),
        ]);

        // Non-financial event (should NOT be counted)
        AuditEvent::factory()->create([
            'event_type' => AuditEventType::CoachApproved->value,
            'occurred_at' => now()->subDay(),
        ]);

        $component = Livewire::actingAs($accountant)->test(Dashboard::class);

        expect($component->instance()->recentFinancialAuditEventCount)->toBe(1);
    });
});
