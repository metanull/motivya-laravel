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
use Stripe\Checkout\Session;
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
            ->assertDontSeeHtml('wire:click="openConfirmModal"')
            ->assertSee(__('bookings.only_athletes_can_book'));
    });

    it('redirects unverified athlete to verification notice when booking', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_booking_unverified',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $athlete = User::factory()->athlete()->unverified()->create();

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertRedirect(route('verification.notice'))
            ->assertDispatched('notify');
    });

    it('hides the book button for unverified athletes', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_booking_unverified_btn',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $athlete = User::factory()->athlete()->unverified()->create();

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->assertDontSeeHtml('wire:click="openConfirmModal"')
            ->assertSee(__('auth.booking_requires_verified_email'));
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

describe('booking confirmation modal (story 6.2)', function () {
    it('openConfirmModal sets showConfirmModal to true and does not create a booking', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_modal_test',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('openConfirmModal')
            ->assertSet('showConfirmModal', true);

        expect(Booking::query()->where('sport_session_id', $session->id)->count())->toBe(0);
    });

    it('openConfirmModal redirects unverified athlete to verification notice', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_modal_unverified',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $athlete = User::factory()->athlete()->unverified()->create();

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('openConfirmModal')
            ->assertRedirect(route('verification.notice'))
            ->assertDispatched('notify');
    });

    it('confirmBook via book() creates booking and redirects to Stripe', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_confirm_book',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => 3000,
        ]);
        $athlete = User::factory()->athlete()->create();

        app()->instance(
            PaymentService::class,
            new PaymentService(
                createCheckoutSessionUsing: fn (array $payload): Session => Session::constructFrom([
                    'id' => 'cs_confirm_book',
                    'url' => 'https://checkout.stripe.com/pay/cs_confirm_book',
                ]),
            ),
        );

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertRedirect('https://checkout.stripe.com/pay/cs_confirm_book');

        expect(Booking::query()->where('sport_session_id', $session->id)->first())->not->toBeNull();
    });

    it('guest sees login and register CTAs instead of book button', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_guest_cta',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create();

        // Test as a guest (no actingAs)
        Livewire::test(Book::class, ['sportSession' => $session])
            ->assertSee(__('bookings.guest_login_cta'))
            ->assertSee(__('bookings.guest_register_cta'))
            ->assertDontSeeHtml('wire:click="openConfirmModal"');
    });
});
