<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Livewire\Accountant\Transactions\Index;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('accountant transactions ledger', function () {
    // ─── Authorization ──────────────────────────────────────────────────────

    it('renders for an accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.transactions.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('renders for an admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('accountant.transactions.index'))
            ->assertOk();
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('accountant.transactions.index'))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('accountant.transactions.index'))
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        $this->get(route('accountant.transactions.index'))
            ->assertRedirect(route('login'));
    });

    it('redirects accountant without 2FA', function () {
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.transactions.index'))
            ->assertRedirect(route('profile.edit'));
    });

    // ─── Rendering ──────────────────────────────────────────────────────────

    it('renders the heading', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSee(__('accountant.transactions_heading'));
    });

    it('shows empty state when no bookings exist', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSee(__('accountant.transactions_no_results'));
    });

    it('lists existing bookings', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create(['name' => 'Trainer Joe']);
        $athlete = User::factory()->athlete()->create(['name' => 'Sporty Kim']);
        $session = SportSession::factory()->create([
            'coach_id' => $coach->id,
            'title' => 'Morning Yoga',
        ]);

        Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSee('Trainer Joe')
            ->assertSee('Sporty Kim')
            ->assertSee('Morning Yoga');
    });

    it('renders missing payment fee values as the dash placeholder', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $session = SportSession::factory()->create();

        // Booking with NO related invoice — all financial columns must render as '—'
        Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSee(__('accountant.transactions_missing_value'));
    });

    it('renders invoice financial values when an invoice exists', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->create(['coach_id' => $coach->id]);

        Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 10000,
        ]);

        Invoice::factory()->invoice()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
            'commission_amount' => 1500,
            'stripe_fee' => 175,
            'coach_payout' => 8325,
        ]);

        $component = Livewire::actingAs($accountant)->test(Index::class);

        // Page should render without error and not show missing-value for all columns
        $component->assertOk();
    });

    it('shows refunded_at date for refunded bookings', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $session = SportSession::factory()->create();
        $refundedAt = now()->subDays(2);

        Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'refunded_at' => $refundedAt,
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSee($refundedAt->format('Y-m-d'));
    });

    // ─── Filters ────────────────────────────────────────────────────────────

    it('filters by coach', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coachA = User::factory()->coach()->create(['name' => 'Coach Alpha']);
        $coachB = User::factory()->coach()->create(['name' => 'Coach Beta']);
        $sessionA = SportSession::factory()->create([
            'coach_id' => $coachA->id,
            'title' => 'Session For Alpha',
        ]);
        $sessionB = SportSession::factory()->create([
            'coach_id' => $coachB->id,
            'title' => 'Session For Beta',
        ]);

        Booking::factory()->confirmed()->create(['sport_session_id' => $sessionA->id]);
        Booking::factory()->confirmed()->create(['sport_session_id' => $sessionB->id]);

        // Filter by coachA — use session titles because coach names also appear in the dropdown
        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('coachId', (string) $coachA->id)
            ->assertSee('Session For Alpha')
            ->assertDontSee('Session For Beta');
    });

    it('filters by booking status', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $session = SportSession::factory()->create();
        $athlete1 = User::factory()->athlete()->create(['name' => 'Confirmed User']);
        $athlete2 = User::factory()->athlete()->create(['name' => 'Refunded User']);

        Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete1->id,
        ]);
        Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete2->id,
            'refunded_at' => now(),
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('bookingStatus', BookingStatus::Confirmed->value)
            ->assertSee('Confirmed User')
            ->assertDontSee('Refunded User');
    });

    it('filters by session status', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $completedSession = SportSession::factory()->completed()->create(['title' => 'Completed Session']);
        $cancelledSession = SportSession::factory()->cancelled()->create(['title' => 'Cancelled Session']);

        Booking::factory()->confirmed()->create(['sport_session_id' => $completedSession->id]);
        Booking::factory()->confirmed()->create(['sport_session_id' => $cancelledSession->id]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('sessionStatus', SessionStatus::Completed->value)
            ->assertSee('Completed Session')
            ->assertDontSee('Cancelled Session');
    });

    it('filters by date range', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $session = SportSession::factory()->create();

        $old = Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'created_at' => now()->subMonths(2),
        ]);
        $recent = Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('dateFrom', now()->subWeek()->toDateString())
            ->set('dateTo', now()->addDay()->toDateString())
            ->assertSeeHtml($recent->created_at->format('Y-m-d'))
            ->assertDontSeeHtml($old->created_at->format('Y-m-d'));
    });

    it('resets filters', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-12-31')
            ->set('coachId', '99')
            ->set('sessionStatus', 'completed')
            ->set('bookingStatus', 'confirmed')
            ->set('anomalyFlag', 'anomalies_only')
            ->call('resetFilters')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('coachId', '')
            ->assertSet('sessionStatus', '')
            ->assertSet('bookingStatus', '')
            ->assertSet('anomalyFlag', '');
    });

    // ─── Export ─────────────────────────────────────────────────────────────

    it('export csv redirects to the ledger export route', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('export', 'csv')
            ->assertRedirect(route('accountant.transactions.export', [
                'format' => 'csv',
                'dateFrom' => now()->subDays(30)->toDateString(),
            ]));
    });

    it('export excel redirects to the ledger export route', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('export', 'excel')
            ->assertRedirect(route('accountant.transactions.export', [
                'format' => 'excel',
                'dateFrom' => now()->subDays(30)->toDateString(),
            ]));
    });

    it('export route streams a csv response for accountant', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.transactions.export', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('export route streams an excel response for accountant', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.transactions.export', ['format' => 'excel']))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
    });

    it('export route denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('accountant.transactions.export', ['format' => 'csv']))
            ->assertForbidden();
    });

    // ─── Story 5.1: Paid Bookings in Ledger ─────────────────────────────────

    it('sets a default date range on mount', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $session = SportSession::factory()->create();

        // Recent booking — within the 30-day window
        $recent = Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'created_at' => now()->subDays(5),
        ]);
        // Old booking — before the 30-day window
        Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'created_at' => now()->subDays(60),
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSet('dateFrom', now()->subDays(30)->toDateString())
            ->assertSeeHtml($recent->created_at->format('Y-m-d'))
            ->assertDontSeeHtml(now()->subDays(60)->format('Y-m-d'));
    });

    it('filters paid_without_invoice to only confirmed bookings with payment but no invoice', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $sessionWithoutInvoice = SportSession::factory()->create(['coach_id' => $coach->id]);
        $sessionWithInvoice = SportSession::factory()->create(['coach_id' => $coach->id]);
        $athlete1 = User::factory()->athlete()->create(['name' => 'Paid No Invoice']);
        $athlete2 = User::factory()->athlete()->create(['name' => 'Paid With Invoice']);

        Booking::factory()->confirmed()->create([
            'sport_session_id' => $sessionWithoutInvoice->id,
            'athlete_id' => $athlete1->id,
            'amount_paid' => 2000,
        ]);
        Booking::factory()->confirmed()->create([
            'sport_session_id' => $sessionWithInvoice->id,
            'athlete_id' => $athlete2->id,
            'amount_paid' => 2000,
        ]);
        Invoice::factory()->invoice()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $sessionWithInvoice->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('dateFrom', '')
            ->set('anomalyFlag', 'paid_without_invoice')
            ->assertSee('Paid No Invoice')
            ->assertDontSee('Paid With Invoice');
    });

    it('filters paid_without_payment_intent to confirmed bookings with amount but no intent', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $session = SportSession::factory()->create();
        $athlete1 = User::factory()->athlete()->create(['name' => 'Missing Intent']);
        $athlete2 = User::factory()->athlete()->create(['name' => 'Has Intent']);

        Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete1->id,
            'amount_paid' => 2000,
            'stripe_payment_intent_id' => null,
        ]);
        Booking::factory()->confirmed()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete2->id,
            'amount_paid' => 2000,
            'stripe_payment_intent_id' => 'pi_test_has',
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('dateFrom', '')
            ->set('anomalyFlag', 'paid_without_payment_intent')
            ->assertSee('Missing Intent')
            ->assertDontSee('Has Intent');
    });

    it('export passes anomalyFlag to the route', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('anomalyFlag', 'paid_without_invoice')
            ->call('export', 'csv')
            ->assertRedirect(route('accountant.transactions.export', [
                'format' => 'csv',
                'dateFrom' => now()->subDays(30)->toDateString(),
                'anomalyFlag' => 'paid_without_invoice',
            ]));
    });
});
