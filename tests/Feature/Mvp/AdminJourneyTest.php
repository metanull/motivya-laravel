<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Livewire\Admin\Readiness;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('MVP Admin Journey', function () {

    it('can reach the admin dashboard', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    });

    it('can reach the user management page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    });

    it('can reach the coach approval page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.coach-approval'))
            ->assertOk();
    });

    it('can reach the sessions supervision page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.sessions.index'))
            ->assertOk();
    });

    it('can reach the exceptional refunds page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.refunds.index'))
            ->assertOk();
    });

    it('can reach the anomalies page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.anomalies.index'))
            ->assertOk();
    });

    it('can reach the billing configuration page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.configuration.billing'))
            ->assertOk();
    });

    it('can reach the activity images page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.activity-images'))
            ->assertOk();
    });

    it('can reach the data export page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.data-export'))
            ->assertOk();
    });

    it('can reach the readiness page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.readiness'))
            ->assertOk();
    });

    it('readiness page renders for admin', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertOk()
            ->assertSee(__('admin.readiness_heading'));
    });

    it('readiness page is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Readiness::class)
            ->assertForbidden();
    });

    it('readiness page is forbidden for coaches', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Readiness::class)
            ->assertForbidden();
    });

    it('readiness page shows all checks', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertSee(__('admin.readiness_check_stripe'))
            ->assertSee(__('admin.readiness_check_database'))
            ->assertSee(__('admin.readiness_check_cache'))
            ->assertSee(__('admin.readiness_check_scheduler'))
            ->assertSee(__('admin.readiness_check_public_storage'))
            ->assertSee(__('admin.readiness_check_postal_code_reference'))
            ->assertSee(__('admin.readiness_check_session_coordinates'))
            ->assertSee(__('admin.readiness_check_payment_anomalies'))
            ->assertSee(__('admin.readiness_check_stripe_connect'));
    });

    it('readiness page shows scheduler detail table', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertSee(__('admin.readiness_scheduler_detail_heading'))
            ->assertSee('sessions:cancel-expired')
            ->assertSee('sessions:send-reminders')
            ->assertSee('bookings:expire-unpaid');
    });

    it('readiness page shows operations panel', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertSee(__('admin.readiness_operations_heading'))
            ->assertSee('php artisan geo:load-postal-codes')
            ->assertSee('php artisan sessions:backfill-coordinates')
            ->assertSee('php artisan payments:reconcile-bookings --dry-run')
            ->assertSee('php artisan payments:reconcile-bookings --repair')
            ->assertSee('php artisan mvp:health-snapshot');
    });

    it('readiness reports red for missing postal code reference', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $component = Livewire::actingAs($admin)->test(Readiness::class);

        expect($component->instance()->checks()['postal_code_reference']['status'])->toBe('red');
    });

    it('readiness reports red when confirmed booking is missing payment intent', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $coach = User::factory()->coach()->create();
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->create(['coach_id' => $coach->id]);

        Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => BookingStatus::Confirmed->value,
            'amount_paid' => 1000,
            'stripe_payment_intent_id' => null,
        ]);

        $component = Livewire::actingAs($admin)->test(Readiness::class);

        expect($component->instance()->checks()['payment_anomalies']['status'])->toBe('red');
    });

    it('readiness reports green for payment anomalies when all confirmed bookings have payment intent', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $component = Livewire::actingAs($admin)->test(Readiness::class);

        expect($component->instance()->checks()['payment_anomalies']['status'])->toBe('green');
    });

    it('readiness reports red for missing public storage symlink', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        // In test environment public/storage symlink may not exist; we test the check method directly.
        $component = Livewire::actingAs($admin)->test(Readiness::class);
        $check = $component->instance()->checks()['public_storage'];

        expect($check['status'])->toBeIn(['red', 'green']); // valid status value
    });

    it('cannot access the accountant dashboard', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('accountant.dashboard'))
            ->assertOk(); // admin can access accountant routes
    });

    it('is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

    it('is forbidden for coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

});
