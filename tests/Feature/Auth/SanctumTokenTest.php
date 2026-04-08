<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Sanctum API Token Authentication', function () {

    it('authenticates a request with a valid Sanctum token', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/user');

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    });

    it('returns 401 for an unauthenticated request', function () {
        $this->getJson('/api/v1/user')
            ->assertUnauthorized();
    });

    it('authenticates using a plain-text token via Authorization header', function () {
        $user = User::factory()->create();

        $token = $user->createToken('test-token');

        $this->getJson('/api/v1/user', [
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    });

    it('returns 401 for an invalid bearer token', function () {
        $this->getJson('/api/v1/user', [
            'Authorization' => 'Bearer invalid-token-value',
        ])
            ->assertUnauthorized();
    });

    it('allows scoped tokens with matching abilities', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read']);

        $this->getJson('/api/v1/user')
            ->assertOk();
    });
});
