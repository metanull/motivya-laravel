<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Livewire\Coach\Dashboard;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('coach dashboard', function () {
    it('renders for a coach', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.dashboard'))
            ->assertOk();
    });

    it('does not render for an athlete', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('coach.dashboard'))
            ->assertForbidden();
    });

    it('shows upcoming published sessions', function () {
        $coach = User::factory()->coach()->create();
        $upcoming = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'title' => 'Upcoming Yoga',
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee('Upcoming Yoga');
    });

    it('shows upcoming confirmed sessions', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'title' => 'Confirmed Run',
            'date' => now()->addDays(5),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee('Confirmed Run');
    });

    it('shows draft sessions in drafts tab', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
            'title' => 'My Draft Session',
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->set('tab', 'drafts')
            ->assertSee('My Draft Session');
    });

    it('shows past completed sessions in past tab', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->completed()->create([
            'coach_id' => $coach->id,
            'title' => 'Old Completed Session',
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->set('tab', 'past')
            ->assertSee('Old Completed Session');
    });

    it('shows past cancelled sessions in past tab', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->cancelled()->create([
            'coach_id' => $coach->id,
            'title' => 'Old Cancelled Session',
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->set('tab', 'past')
            ->assertSee('Old Cancelled Session');
    });

    it('does not show other coaches sessions', function () {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();

        SportSession::factory()->published()->create([
            'coach_id' => $otherCoach->id,
            'title' => 'Not My Session',
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertDontSee('Not My Session');
    });

    it('can publish a draft session', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'stripe_account_id' => 'acct_test',
            'stripe_onboarding_complete' => true,
        ]);
        $session = SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->call('publishSession', $session->id)
            ->assertDispatched('notify');

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Published);
    });

    it('can cancel a published session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->call('cancelSession', $session->id)
            ->assertDispatched('notify');

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Cancelled);
    });

    it('can delete a draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->call('deleteSession', $session->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('sport_sessions', ['id' => $session->id]);
    });

    it('shows empty state for upcoming when no sessions', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.no_upcoming'));
    });
});

describe('coach dashboard onboarding checklist', function () {
    it('shows checklist panel when items are incomplete', function () {
        $coach = User::factory()->coach()->create();
        // No coach profile at all → all items incomplete
        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.onboarding_checklist_heading'))
            ->assertSet('showChecklist', true);
    });

    it('checklist shows profile-approved item as incomplete when pending', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->pending()->create(['user_id' => $coach->id]);

        $component = Livewire::actingAs($coach)->test(Dashboard::class);

        $items = $component->instance()->checklistItems();
        $profileApprovedItem = collect($items)->firstWhere('label', __('coach.onboarding_item_profile_approved'));

        expect($profileApprovedItem)->not->toBeNull();
        expect($profileApprovedItem['done'])->toBeFalse();
    });

    it('checklist shows profile-approved item as done when approved', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        $component = Livewire::actingAs($coach)->test(Dashboard::class);

        $items = $component->instance()->checklistItems();
        $item = collect($items)->firstWhere('label', __('coach.onboarding_item_profile_approved'));

        expect($item['done'])->toBeTrue();
    });

    it('checklist shows vat-captured item as incomplete when null', function () {
        $coach = User::factory()->coach()->create();
        // Create profile without setting is_vat_subject (should be null after migration)
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'is_vat_subject' => null,
        ]);

        $component = Livewire::actingAs($coach)->test(Dashboard::class);

        $items = $component->instance()->checklistItems();
        $item = collect($items)->firstWhere('label', __('coach.onboarding_item_vat_captured'));

        expect($item['done'])->toBeFalse();
    });

    it('checklist shows vat-captured item as done when explicitly set', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $component = Livewire::actingAs($coach)->test(Dashboard::class);

        $items = $component->instance()->checklistItems();
        $item = collect($items)->firstWhere('label', __('coach.onboarding_item_vat_captured'));

        expect($item['done'])->toBeTrue();
    });

    it('checklist shows stripe item as done when onboarding complete', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'stripe_account_id' => 'acct_test',
            'stripe_onboarding_complete' => true,
        ]);

        $component = Livewire::actingAs($coach)->test(Dashboard::class);

        $items = $component->instance()->checklistItems();
        $item = collect($items)->firstWhere('label', __('coach.onboarding_item_stripe_ready'));

        expect($item['done'])->toBeTrue();
    });

    it('checklist shows published-session item as done when future published session exists', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);

        $component = Livewire::actingAs($coach)->test(Dashboard::class);

        $items = $component->instance()->checklistItems();
        $item = collect($items)->firstWhere('label', __('coach.onboarding_item_published_session'));

        expect($item['done'])->toBeTrue();
    });

    it('checklist shows published-session item as incomplete when no future published sessions', function () {
        $coach = User::factory()->coach()->create();
        // Draft session does not count
        SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        $component = Livewire::actingAs($coach)->test(Dashboard::class);

        $items = $component->instance()->checklistItems();
        $item = collect($items)->firstWhere('label', __('coach.onboarding_item_published_session'));

        expect($item['done'])->toBeFalse();
    });

    it('checklist shows cover-image item as done when a session has a cover image', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
            'cover_image_id' => 1,
        ]);

        $component = Livewire::actingAs($coach)->test(Dashboard::class);

        $items = $component->instance()->checklistItems();
        $item = collect($items)->firstWhere('label', __('coach.onboarding_item_cover_image'));

        expect($item['done'])->toBeTrue();
    });

    it('toggleChecklist toggles showChecklist from true to false', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSet('showChecklist', true)
            ->call('toggleChecklist')
            ->assertSet('showChecklist', false);
    });

    it('toggleChecklist toggles showChecklist from false to true', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->set('showChecklist', false)
            ->call('toggleChecklist')
            ->assertSet('showChecklist', true);
    });

    it('checklist auto-collapses at mount when all items are complete', function () {
        $coach = User::factory()->coach()->create();

        // Create a fully completed onboarding state:
        CoachProfile::factory()->approved()->vatSubject()->create([
            'user_id' => $coach->id,
            'specialties' => ['fitness'],
            'bio' => 'Experienced coach',
            'experience_level' => 'expert',
            'postal_code' => '1000',
            'enterprise_number' => '0123.456.789',
            'stripe_account_id' => 'acct_test123',
            'stripe_onboarding_complete' => true,
        ]);

        // Published future session with cover image
        SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(5),
            'cover_image_id' => 1,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSet('showChecklist', false);
    });

    it('checklist stays expanded at mount when some items are incomplete', function () {
        $coach = User::factory()->coach()->create();
        // No coach profile → no items done
        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSet('showChecklist', true);
    });
});

describe('coach dashboard publish guard (story 5.2)', function () {
    it('dispatches error notify when publishing without stripe onboarding', function () {
        $coach = User::factory()->coach()->create();
        // No stripe onboarding
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'stripe_account_id' => null,
            'stripe_onboarding_complete' => false,
        ]);
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->call('publishSession', $session->id)
            ->assertDispatched('notify', type: 'error');

        // Session must remain draft
        expect($session->fresh()->status)->toBe(SessionStatus::Draft);
    });

    it('dispatches success notify and publishes when stripe onboarding is complete', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'stripe_account_id' => 'acct_done',
            'stripe_onboarding_complete' => true,
        ]);
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->call('publishSession', $session->id)
            ->assertDispatched('notify', type: 'success');

        expect($session->fresh()->status)->toBe(SessionStatus::Published);
    });

    it('shows published-without-stripe warning when coach has published sessions but stripe is incomplete', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'stripe_account_id' => null,
            'stripe_onboarding_complete' => false,
        ]);
        SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.published_sessions_not_stripe_ready'));
    });

    it('does not show published-without-stripe warning when stripe is complete', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'stripe_account_id' => 'acct_done',
            'stripe_onboarding_complete' => true,
        ]);
        SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertDontSee(__('coach.published_sessions_not_stripe_ready'));
    });
});

describe('coach dashboard stats (story 5.3)', function () {
    it('fill rate is based on confirmed paid bookings only, excluding pending holds', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $coach->id,
            'stripe_account_id' => 'acct_test',
            'stripe_onboarding_complete' => true,
        ]);

        // Session with 10 spots: 5 confirmed + 3 pending → confirmed fill = 50%
        $session = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'max_participants' => 10,
            'current_participants' => 8, // total held
        ]);

        $athletes = User::factory()->athlete()->count(8)->create();

        // 5 confirmed bookings
        $athletes->take(5)->each(
            fn ($a) => Booking::factory()->confirmed()->for($session, 'sportSession')->for($a, 'athlete')->create()
        );

        // 3 pending payment holds
        $athletes->skip(5)->each(
            fn ($a) => Booking::factory()->pendingPayment()->for($session, 'sportSession')->for($a, 'athlete')->create()
        );

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertViewHas('avgFillRate', 50);
    });

    it('revenue stat excludes pending, cancelled and refunded bookings', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create(['coach_id' => $coach->id]);

        $athletes = User::factory()->athlete()->count(4)->create();

        // Only this one counts toward revenue
        Booking::factory()->confirmed()->for($session, 'sportSession')->for($athletes[0], 'athlete')->create(['amount_paid' => 2500]);
        // These should not
        Booking::factory()->pendingPayment()->for($session, 'sportSession')->for($athletes[1], 'athlete')->create(['amount_paid' => 2500]);
        Booking::factory()->cancelled()->for($session, 'sportSession')->for($athletes[2], 'athlete')->create(['amount_paid' => 2500]);
        Booking::factory()->refunded()->for($session, 'sportSession')->for($athletes[3], 'athlete')->create(['amount_paid' => 2500]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertViewHas('totalRevenueCents', 2500);
    });

    it('shows pending holds count separately from confirmed participants', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create(['coach_id' => $coach->id]);

        $athletes = User::factory()->athlete()->count(3)->create();

        Booking::factory()->confirmed()->for($session, 'sportSession')->for($athletes[0], 'athlete')->create();
        Booking::factory()->pendingPayment()->for($session, 'sportSession')->for($athletes[1], 'athlete')->create();
        Booking::factory()->pendingPayment()->for($session, 'sportSession')->for($athletes[2], 'athlete')->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertViewHas('totalConfirmedParticipants', 1)
            ->assertViewHas('totalPendingPaymentHolds', 2);
    });
});
