<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MVP Athlete Journey', function () {

    it('can reach the athlete dashboard', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('athlete.dashboard'))
            ->assertOk();
    });

    it('can reach the athlete favourites page', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('athlete.favourites'))
            ->assertOk();
    });

    it('can view a session detail and see the booking form', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create([
            'title' => 'Athlete View Session',
        ]);

        $this->actingAs($athlete)
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('Athlete View Session');
    });

    it('can view the payment return page with a valid booking', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create();
        $booking = Booking::factory()->pendingPayment()->create([
            'athlete_id' => $athlete->id,
            'sport_session_id' => $session->id,
        ]);

        $this->actingAs($athlete)
            ->get(route('bookings.payment-return', ['booking' => $booking->id, 'status' => 'cancel']))
            ->assertOk();
    });

    it('cannot access the admin dashboard', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

    it('cannot access the coach dashboard', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('coach.dashboard'))
            ->assertForbidden();
    });

    it('cannot access the accountant dashboard', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('accountant.dashboard'))
            ->assertForbidden();
    });

    it('sees upcoming bookings on the dashboard', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->confirmed()->create([
            'title' => 'Upcoming Session',
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->confirmed()->create([
            'athlete_id' => $athlete->id,
            'sport_session_id' => $session->id,
        ]);

        $this->actingAs($athlete)
            ->get(route('athlete.dashboard'))
            ->assertOk()
            ->assertSee('Upcoming Session');
    });

});
