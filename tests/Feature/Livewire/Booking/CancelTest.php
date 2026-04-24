<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Livewire\Booking\Cancel;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('booking cancellation widget', function () {
    it('shows refund eligibility and cancels a confirmed booking after confirmation', function () {
        Carbon::setTestNow(Carbon::create(2026, 4, 24, 12, 0, 0));

        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->confirmed()->create([
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '12:00:00',
            'current_participants' => 2,
            'min_participants' => 2,
        ]);
        $booking = Booking::factory()
            ->confirmed()
            ->for($session, 'sportSession')
            ->for($athlete, 'athlete')
            ->create();

        Livewire::actingAs($athlete)
            ->test(Cancel::class, ['sportSession' => $session])
            ->assertSee(__('bookings.cancel_refund_eligible_notice', ['hours' => 48]))
            ->call('confirmCancellation')
            ->assertSee(__('bookings.cancel_confirmation_title'))
            ->call('processCancellation')
            ->assertDispatched('notify');

        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
        expect($session->fresh()->current_participants)->toBe(1);

        Carbon::setTestNow();
    });

    it('shows when a published-session cancellation is outside the refund window', function () {
        Carbon::setTestNow(Carbon::create(2026, 4, 24, 12, 0, 0));

        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create([
            'date' => now()->addHours(23)->addMinutes(30)->toDateString(),
            'start_time' => now()->addHours(23)->addMinutes(30)->format('H:i:s'),
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()
            ->pendingPayment()
            ->for($session, 'sportSession')
            ->for($athlete, 'athlete')
            ->create();

        Livewire::actingAs($athlete)
            ->test(Cancel::class, ['sportSession' => $session])
            ->assertSee(__('bookings.cancel_refund_ineligible_notice', ['hours' => 24]))
            ->call('confirmCancellation')
            ->call('processCancellation')
            ->assertDispatched('notify');

        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);

        Carbon::setTestNow();
    });
});
