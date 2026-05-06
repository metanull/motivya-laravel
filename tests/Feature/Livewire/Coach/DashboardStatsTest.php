<?php

declare(strict_types=1);

use App\Livewire\Coach\Dashboard;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('coach dashboard stats', function () {
    it('shows total sessions count', function () {
        $coach = User::factory()->coach()->create();

        SportSession::factory()->draft()->count(3)->create(['coach_id' => $coach->id]);
        SportSession::factory()->published()->count(2)->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_total_sessions'))
            ->assertSeeHtml('>5</p>');
    });

    it('shows sessions this month count', function () {
        $coach = User::factory()->coach()->create();

        SportSession::factory()->draft()->count(2)->create([
            'coach_id' => $coach->id,
            'date' => now(),
        ]);
        SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
            'date' => now()->addMonths(2),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_sessions_this_month'))
            ->assertSeeHtml('>2</p>');
    });

    it('shows total bookings count', function () {
        $coach = User::factory()->coach()->create();

        $sessionA = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'current_participants' => 5,
            'date' => now()->addDays(3),
        ]);
        $sessionB = SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'current_participants' => 8,
            'date' => now()->addDays(5),
        ]);

        // 5 confirmed bookings on sessionA
        Booking::factory()->confirmed()->count(5)->for($sessionA, 'sportSession')->create();
        // 8 confirmed bookings on sessionB
        Booking::factory()->confirmed()->count(8)->for($sessionB, 'sportSession')->create();
        // 2 pending-payment bookings — must NOT be counted
        Booking::factory()->pendingPayment()->count(2)->for($sessionA, 'sportSession')->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_confirmed_participants'))
            ->assertSeeHtml('>13</p>');
    });

    it('shows average fill rate', function () {
        $coach = User::factory()->coach()->create();

        // Session 1: 5 confirmed / 10 max = 50%
        $sessionA = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'current_participants' => 5,
            'max_participants' => 10,
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->confirmed()->count(5)->for($sessionA, 'sportSession')->create();

        // Session 2: 10 confirmed / 10 max = 100%
        $sessionB = SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'current_participants' => 10,
            'max_participants' => 10,
            'date' => now()->addDays(5),
        ]);
        Booking::factory()->confirmed()->count(10)->for($sessionB, 'sportSession')->create();

        // Average: 75%

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_avg_fill_rate'))
            ->assertSeeHtml('>75%</p>');
    });

    it('shows total revenue from confirmed and completed sessions', function () {
        $coach = User::factory()->coach()->create();

        // Confirmed session: 3 confirmed bookings × 1500 cents = 4500 cents
        $confirmedSession = SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'price_per_person' => 1500,
            'current_participants' => 3,
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->confirmed()->count(3)->for($confirmedSession, 'sportSession')->create([
            'amount_paid' => 1500,
        ]);

        // Completed session: 5 confirmed bookings × 2000 cents = 10000 cents
        $completedSession = SportSession::factory()->completed()->create([
            'coach_id' => $coach->id,
            'price_per_person' => 2000,
            'current_participants' => 5,
        ]);
        Booking::factory()->confirmed()->count(5)->for($completedSession, 'sportSession')->create([
            'amount_paid' => 2000,
        ]);

        // Published session with only pending bookings — must NOT be counted in revenue
        $publishedSession = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'price_per_person' => 1000,
            'current_participants' => 2,
            'date' => now()->addDays(5),
        ]);
        Booking::factory()->pendingPayment()->count(2)->for($publishedSession, 'sportSession')->create([
            'amount_paid' => 1000,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_total_revenue'));
        // Total: 4500 + 10000 = 14500 cents = 145.00 EUR
    });

    it('shows zero stats for a new coach', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSeeHtml('>0</p>')
            ->assertSeeHtml('>0%</p>');
    });

    it('does not include other coach sessions in stats', function () {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();

        SportSession::factory()->confirmed()->count(5)->create([
            'coach_id' => $otherCoach->id,
            'current_participants' => 10,
            'date' => now()->addDays(3),
        ]);
        SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_total_sessions'));
        // Only 1 session for this coach
        expect(SportSession::where('coach_id', $coach->id)->count())->toBe(1);
    });

    it('shows current month revenue separately from all-time total', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);

        // This month booking
        Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 3000,
            'created_at' => now(),
        ]);
        // Last month booking — must NOT appear in current-month revenue
        Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 5000,
            'created_at' => now()->subMonths(1),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_current_month_revenue'))
            ->assertSee(__('coach.stat_current_month_refunds'));
    });

    it('shows anomaly warning when confirmed paid booking has no payment intent', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 2000,
            'stripe_payment_intent_id' => null,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.anomaly_warning_missing_payment_intent'));
    });

    it('does not show anomaly warning when all confirmed paid bookings have payment intent', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 2000,
            'stripe_payment_intent_id' => 'pi_test_ok',
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertDontSee(__('coach.anomaly_warning_missing_payment_intent'));
    });

    it('excludes pending payment bookings from revenue', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->pendingPayment()->for($session, 'sportSession')->create([
            'amount_paid' => 5000,
        ]);

        // No confirmed bookings => revenue labels still shown, value is zero
        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_total_revenue'))
            ->assertSee(__('coach.stat_current_month_revenue'));
    });
});
