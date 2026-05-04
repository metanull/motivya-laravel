<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\Users\Create;
use App\Models\User;
use App\Notifications\AdminUserOnboardingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin — Create Privileged User', function () {

    it('renders for admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->assertOk();
    });

    it('is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Create::class)
            ->assertForbidden();
    });

    it('is forbidden for coaches', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->assertForbidden();
    });

    it('is forbidden for accountants', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Create::class)
            ->assertForbidden();
    });

    it('creates an accountant user and sends onboarding email', function () {
        Notification::fake();

        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('form.name', 'Test Accountant')
            ->set('form.email', 'accountant@example.com')
            ->set('form.role', UserRole::Accountant->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'accountant@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->role)->toBe(UserRole::Accountant);
        expect($user->email_verified_at)->not->toBeNull();

        Notification::assertSentTo($user, AdminUserOnboardingNotification::class);
    });

    it('creates an admin user and sends onboarding email', function () {
        Notification::fake();

        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('form.name', 'New Admin')
            ->set('form.email', 'newadmin@example.com')
            ->set('form.role', UserRole::Admin->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'newadmin@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->role)->toBe(UserRole::Admin);

        Notification::assertSentTo($user, AdminUserOnboardingNotification::class);
    });

    it('requires all fields', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email', 'form.role']);
    });

    it('rejects duplicate email', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('form.name', 'Duplicate')
            ->set('form.email', 'existing@example.com')
            ->set('form.role', UserRole::Accountant->value)
            ->call('save')
            ->assertHasErrors(['form.email']);
    });

    it('rejects athlete and coach roles', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('form.name', 'Athlete')
            ->set('form.email', 'athlete2@example.com')
            ->set('form.role', UserRole::Athlete->value)
            ->call('save')
            ->assertHasErrors(['form.role']);
    });

    it('is accessible via route admin.users.create', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk();
    });

});
