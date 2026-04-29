<?php

declare(strict_types=1);

use App\Livewire\Booking\Book;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Stripe\Checkout\Session as CheckoutSession;

uses(RefreshDatabase::class);

describe('booking widget', function () {
    it('creates a booking and redirects to the Stripe Checkout URL', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_booking_test',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => 2500,
        ]);
        $athlete = User::factory()->athlete()->create();

        app()->instance(PaymentService::class, new PaymentService(
            createCheckoutSessionUsing: fn (array $payload): CheckoutSession => CheckoutSession::constructFrom([
                'id' => 'cs_booking_redirect',
                'url' => 'https://checkout.stripe.com/pay/cs_booking_redirect',
            ]),
        ));

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertRedirect('https://checkout.stripe.com/pay/cs_booking_redirect');

        $booking = Booking::query()->where('sport_session_id', $session->id)->first();

        expect($booking)->not->toBeNull();
        expect($booking?->athlete_id)->toBe($athlete->id);
        expect($booking?->stripe_checkout_session_id)->toBe('cs_booking_redirect');
        expect($session->fresh()->current_participants)->toBe(1);
    });

    it('shows the booking action only to athletes', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_booking_test',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->create([
            'coach_id' => User::factory()->coach()->create()->id,
        ]);

        Livewire::actingAs($coach)
            ->test(Book::class, ['sportSession' => $session])
            ->assertDontSeeHtml('wire:click="book"')
            ->assertSee(__('bookings.only_athletes_can_book'));
    });

    it('reports booking domain errors without creating duplicate records', function (SportSession $session, User $athlete, int $expectedCount) {
        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertDispatched('notify');

        expect(Booking::query()->where('sport_session_id', $session->id)->count())->toBe($expectedCount);
    })->with([
        'session full' => function (): array {
            $coach = User::factory()->coach()->create();
            CoachProfile::factory()->approved()->for($coach)->create([
                'stripe_account_id' => 'acct_booking_full',
                'stripe_onboarding_complete' => true,
            ]);

            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->for($coach, 'coach')->create([
                'current_participants' => 2,
                'max_participants' => 2,
            ]);

            return [$session, $athlete, 0];
        },
        'already booked' => function (): array {
            $coach = User::factory()->coach()->create();
            CoachProfile::factory()->approved()->for($coach)->create([
                'stripe_account_id' => 'acct_booking_existing',
                'stripe_onboarding_complete' => true,
            ]);

            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->published()->for($coach, 'coach')->create([
                'current_participants' => 1,
            ]);

            Booking::factory()->pendingPayment()->for($session, 'sportSession')->for($athlete, 'athlete')->create();

            return [$session, $athlete, 1];
        },
        'session not bookable' => function (): array {
            $coach = User::factory()->coach()->create();
            CoachProfile::factory()->approved()->for($coach)->create([
                'stripe_account_id' => 'acct_booking_unavailable',
                'stripe_onboarding_complete' => true,
            ]);

            $athlete = User::factory()->athlete()->create();
            $session = SportSession::factory()->cancelled()->for($coach, 'coach')->create();

            return [$session, $athlete, 0];
        },
    ]);
});
