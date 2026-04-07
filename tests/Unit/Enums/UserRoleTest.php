<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserRole', function () {

    it('has the correct backed string values', function () {
        expect(UserRole::Coach->value)->toBe('coach');
        expect(UserRole::Athlete->value)->toBe('athlete');
        expect(UserRole::Accountant->value)->toBe('accountant');
        expect(UserRole::Admin->value)->toBe('admin');
    });

    it('can be created from a string value', function () {
        expect(UserRole::from('coach'))->toBe(UserRole::Coach);
        expect(UserRole::from('athlete'))->toBe(UserRole::Athlete);
        expect(UserRole::from('accountant'))->toBe(UserRole::Accountant);
        expect(UserRole::from('admin'))->toBe(UserRole::Admin);
    });

    it('lists all four cases', function () {
        expect(UserRole::cases())->toHaveCount(4);
    });

    it('is cast correctly on the User model', function () {
        $user = User::factory()->create(['role' => UserRole::Coach->value]);

        expect($user->role)->toBe(UserRole::Coach);
        expect($user->role)->toBeInstanceOf(UserRole::class);
    });

    it('defaults to athlete on the User model', function () {
        $user = User::factory()->create();

        expect($user->role)->toBe(UserRole::Athlete);
    });

});
