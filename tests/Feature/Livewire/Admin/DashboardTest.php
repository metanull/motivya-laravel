<?php

declare(strict_types=1);

use App\Livewire\Admin\Dashboard;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin — Dashboard', function () {

    it('renders for admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee(__('admin.dashboard_heading'));
    });

    it('is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Dashboard::class)
            ->assertForbidden();
    });

    it('is forbidden for coaches', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertForbidden();
    });

    it('is accessible via route admin.dashboard', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    });

    it('shows user count on dashboard', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        User::factory()->athlete()->count(4)->create();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertSee(__('admin.dashboard_card_users'));
    });

    it('shows audit log card on admin dashboard', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertSee(__('admin.dashboard_card_audit_events'));
    });

    it('recentAuditEventCount returns count of events from last 7 days', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        AuditEvent::factory()->count(3)->create([
            'occurred_at' => now()->subDays(2),
        ]);
        AuditEvent::factory()->count(2)->create([
            'occurred_at' => now()->subDays(10),
        ]);

        $component = Livewire::actingAs($admin)->test(Dashboard::class);

        expect($component->instance()->recentAuditEventCount)->toBe(3);
    });

});
