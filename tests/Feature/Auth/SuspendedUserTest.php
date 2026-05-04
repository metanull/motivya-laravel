<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Suspended User — Auth Block', function () {

    it('logs out and rejects a suspended user who tries to access a protected route', function () {
        $user = User::factory()->athlete()->create([
            'suspended_at' => now(),
            'suspension_reason' => 'Policy violation',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    it('allows a non-suspended user to access protected routes', function () {
        $user = User::factory()->athlete()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();
    });

    it('suspended_at and suspension_reason are stored and retrieved correctly', function () {
        $user = User::factory()->athlete()->create([
            'suspended_at' => now(),
            'suspension_reason' => 'Test reason',
        ]);

        expect($user->isSuspended())->toBeTrue();
        expect($user->suspension_reason)->toBe('Test reason');

        $user->update(['suspended_at' => null, 'suspension_reason' => null]);
        $user->refresh();

        expect($user->isSuspended())->toBeFalse();
    });

});
