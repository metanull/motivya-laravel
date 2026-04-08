<?php

declare(strict_types=1);

use App\Livewire\Profile\Edit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Profile Edit Livewire Component', function () {

    it('renders the profile page for authenticated users', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeLivewire(Edit::class);
    });

    it('redirects guests to login', function () {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    });

    it('displays translated heading', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee(__('profile.heading'));
    });

    it('shows all profile sections', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee(__('profile.info_heading'))
            ->assertSee(__('profile.password_heading'))
            ->assertSee(__('profile.locale_heading'))
            ->assertSee(__('profile.twofa_heading'));
    });

    it('contains the profile information form posting to Fortify route', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee('action="'.route('user-profile-information.update').'"', escape: false)
            ->assertSee('name="name"', escape: false)
            ->assertSee('name="email"', escape: false);
    });

    it('contains the password update form posting to Fortify route', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee('action="'.route('user-password.update').'"', escape: false)
            ->assertSee('name="current_password"', escape: false)
            ->assertSee('name="password"', escape: false)
            ->assertSee('name="password_confirmation"', escape: false);
    });

    it('pre-fills the user name and email', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee('value="John Doe"', escape: false)
            ->assertSee('value="john@example.com"', escape: false);
    });

    it('shows the 2FA section with enable option', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee(__('profile.twofa_heading'))
            ->assertSee(__('profile.twofa_enable'));
    });

});

describe('Profile Edit — Locale Preference', function () {

    it('pre-fills the locale from user profile', function () {
        $user = User::factory()->create(['locale' => 'nl']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->assertSet('locale', 'nl');
    });

    it('updates the user locale preference', function () {
        $user = User::factory()->create(['locale' => 'fr']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('locale', 'en')
            ->call('updateLocale')
            ->assertHasNoErrors();

        expect($user->fresh()->locale)->toBe('en');
    });

    it('rejects invalid locale values', function () {
        $user = User::factory()->create(['locale' => 'fr']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('locale', 'de')
            ->call('updateLocale')
            ->assertHasErrors('locale');
    });

    it('updates the session locale', function () {
        $user = User::factory()->create(['locale' => 'fr']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('locale', 'nl')
            ->call('updateLocale');

        expect(session('locale'))->toBe('nl');
    });

});

describe('Profile Edit — Fortify Profile Information Update', function () {

    it('updates the user name', function () {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'user@example.com',
        ]);

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), [
                'name' => 'New Name',
                'email' => 'user@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        expect($user->fresh()->name)->toBe('New Name');
    });

    it('updates the user email and resets verification', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), [
                'name' => 'Test User',
                'email' => 'new@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();
        expect($user->email)->toBe('new@example.com');
        expect($user->email_verified_at)->toBeNull();
    });

    it('rejects duplicate email', function () {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user)
            ->put(route('user-profile-information.update'), [
                'name' => 'Test',
                'email' => 'taken@example.com',
            ])
            ->assertSessionHasErrors('email', errorBag: 'updateProfileInformation');
    });

});

describe('Profile Edit — Fortify Password Update', function () {

    it('updates the user password', function () {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword1!'),
        ]);

        $this->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'OldPassword1!',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        expect(app('hash')->check('NewPassword1!', $user->fresh()->password))->toBeTrue();
    });

    it('rejects incorrect current password', function () {
        $user = User::factory()->create([
            'password' => bcrypt('CorrectPassword1!'),
        ]);

        $this->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'WrongPassword!',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->assertSessionHasErrors('current_password', errorBag: 'updatePassword');
    });

    it('rejects mismatched password confirmation', function () {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword1!'),
        ]);

        $this->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'OldPassword1!',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'Different1!',
            ])
            ->assertSessionHasErrors('password', errorBag: 'updatePassword');
    });

});
