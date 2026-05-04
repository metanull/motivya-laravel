<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

function mockSocialiteUser(string $email = 'john@example.com', string $name = 'John Doe'): void
{
    $socialiteUser = new SocialiteUser;
    $socialiteUser->map([
        'id' => '123456789',
        'name' => $name,
        'email' => $email,
        'avatar' => null,
    ]);
    $socialiteUser->token = 'fake-token';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);
    $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    $factory = Mockery::mock(SocialiteFactory::class);
    $factory->shouldReceive('driver')->with('google')->andReturn($provider);

    app()->instance(SocialiteFactory::class, $factory);
}

describe('Google OAuth', function () {

    it('redirects to Google consent screen', function () {
        mockSocialiteUser();

        $this->get(route('auth.google.redirect'))
            ->assertRedirect();
    });

    it('creates a new user with athlete role on first OAuth login', function () {
        mockSocialiteUser('newuser@example.com', 'New User');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('athlete.dashboard'));

        $this->assertAuthenticated();

        $user = User::where('email', 'newuser@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('New User');
        expect($user->role)->toBe(UserRole::Athlete);
        expect($user->email_verified_at)->not->toBeNull();
    });

    it('logs in an existing user by email', function () {
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
            'role' => UserRole::Coach,
        ]);

        mockSocialiteUser('existing@example.com', 'Google Name');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('coach.dashboard'));

        $this->assertAuthenticatedAs($existing);

        // Should not change name or role of existing user
        $existing->refresh();
        expect($existing->name)->toBe('Existing User');
        expect($existing->role)->toBe(UserRole::Coach);
    });

    it('does not store OAuth tokens long-term', function () {
        mockSocialiteUser('newuser@example.com', 'New User');

        $this->get(route('auth.google.callback'));

        $user = User::where('email', 'newuser@example.com')->first();

        // No token columns on users table — tokens are never persisted
        expect(
            Schema::hasColumn('users', 'google_token')
        )->toBeFalse();
    });

    it('is only accessible to guests', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('auth.google.redirect'))
            ->assertRedirect(route('home'));
    });
});
