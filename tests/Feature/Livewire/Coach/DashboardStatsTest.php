<?php

declare(strict_types=1);

use App\Livewire\Coach\Dashboard;
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

        SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'current_participants' => 5,
            'date' => now()->addDays(3),
        ]);
        SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'current_participants' => 8,
            'date' => now()->addDays(5),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_total_bookings'))
            ->assertSeeHtml('>13</p>');
    });

    it('shows average fill rate', function () {
        $coach = User::factory()->coach()->create();

        // Session 1: 5/10 = 50%
        SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'current_participants' => 5,
            'max_participants' => 10,
            'date' => now()->addDays(3),
        ]);
        // Session 2: 10/10 = 100%
        SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'current_participants' => 10,
            'max_participants' => 10,
            'date' => now()->addDays(5),
        ]);
        // Average: 75%

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stat_avg_fill_rate'))
            ->assertSeeHtml('>75%</p>');
    });

    it('shows total revenue from confirmed and completed sessions', function () {
        $coach = User::factory()->coach()->create();

        // Confirmed: 1500 cents * 3 participants = 4500 cents = 45.00 EUR
        SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'price_per_person' => 1500,
            'current_participants' => 3,
            'date' => now()->addDays(3),
        ]);
        // Completed: 2000 cents * 5 participants = 10000 cents = 100.00 EUR
        SportSession::factory()->completed()->create([
            'coach_id' => $coach->id,
            'price_per_person' => 2000,
            'current_participants' => 5,
        ]);
        // Published (not counted): 1000 * 2 = 2000
        SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'price_per_person' => 1000,
            'current_participants' => 2,
            'date' => now()->addDays(5),
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
});
