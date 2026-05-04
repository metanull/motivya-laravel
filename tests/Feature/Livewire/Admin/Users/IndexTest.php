<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin — User Management Index', function () {

    it('renders for admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertOk();
    });

    it('is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertForbidden();
    });

    it('lists all users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        User::factory()->athlete()->count(3)->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee($admin->email);
    });

    it('can suspend a user', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $target = User::factory()->athlete()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmSuspend', $target->id)
            ->set('suspensionReason', 'Violation of terms')
            ->call('suspend')
            ->assertHasNoErrors();

        $target->refresh();
        expect($target->isSuspended())->toBeTrue();
        expect($target->suspension_reason)->toBe('Violation of terms');
    });

    it('requires a reason to suspend', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $target = User::factory()->athlete()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmSuspend', $target->id)
            ->call('suspend')
            ->assertHasErrors(['suspensionReason']);
    });

    it('can reactivate a suspended user', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $target = User::factory()->athlete()->create([
            'suspended_at' => now(),
            'suspension_reason' => 'test',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('reactivate', $target->id)
            ->assertHasNoErrors();

        $target->refresh();
        expect($target->isSuspended())->toBeFalse();
        expect($target->suspension_reason)->toBeNull();
    });

    it('can change role of a user', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $target = User::factory()->athlete()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmChangeRole', $target->id)
            ->set('newRole', UserRole::Accountant->value)
            ->call('changeRole')
            ->assertHasNoErrors();

        $target->refresh();
        expect($target->role)->toBe(UserRole::Accountant);
    });

    it('blocks assigning Coach role directly', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $target = User::factory()->athlete()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmChangeRole', $target->id)
            ->set('newRole', UserRole::Coach->value)
            ->call('changeRole')
            ->assertHasErrors(['newRole']);
    });

    it('protects the last admin from demotion', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmChangeRole', $admin->id)
            ->set('newRole', UserRole::Athlete->value)
            ->call('changeRole')
            ->assertHasErrors(['newRole']);
    });

    it('filters by suspended status', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $suspended = User::factory()->athlete()->create([
            'suspended_at' => now(),
            'suspension_reason' => 'test',
        ]);
        $active = User::factory()->athlete()->create();

        $component = Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('statusFilter', 'suspended');

        $component->assertSee($suspended->email);
        $component->assertDontSee($active->email);
    });

    it('is accessible via route admin.users.index', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    });

});
