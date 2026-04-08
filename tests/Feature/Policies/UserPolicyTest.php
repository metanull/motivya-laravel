<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserPolicy', function () {

    describe('viewAny', function () {
        it('allows admin to view any users', function () {
            $admin = User::factory()->admin()->create();
            expect($admin->can('viewAny', User::class))->toBeTrue();
        });

        it('denies coach from viewing any users', function () {
            $coach = User::factory()->coach()->create();
            expect($coach->can('viewAny', User::class))->toBeFalse();
        });

        it('denies athlete from viewing any users', function () {
            $athlete = User::factory()->athlete()->create();
            expect($athlete->can('viewAny', User::class))->toBeFalse();
        });

        it('denies accountant from viewing any users', function () {
            $accountant = User::factory()->accountant()->create();
            expect($accountant->can('viewAny', User::class))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows admin to view any user', function () {
            $admin = User::factory()->admin()->create();
            $other = User::factory()->athlete()->create();
            expect($admin->can('view', $other))->toBeTrue();
        });

        it('allows user to view own profile', function () {
            $user = User::factory()->athlete()->create();
            expect($user->can('view', $user))->toBeTrue();
        });

        it('denies athlete from viewing another user', function () {
            $athlete = User::factory()->athlete()->create();
            $other = User::factory()->coach()->create();
            expect($athlete->can('view', $other))->toBeFalse();
        });

        it('denies coach from viewing another user', function () {
            $coach = User::factory()->coach()->create();
            $other = User::factory()->athlete()->create();
            expect($coach->can('view', $other))->toBeFalse();
        });

        it('denies accountant from viewing another user', function () {
            $accountant = User::factory()->accountant()->create();
            $other = User::factory()->athlete()->create();
            expect($accountant->can('view', $other))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update any user', function () {
            $admin = User::factory()->admin()->create();
            $other = User::factory()->athlete()->create();
            expect($admin->can('update', $other))->toBeTrue();
        });

        it('allows user to update own profile', function () {
            $user = User::factory()->coach()->create();
            expect($user->can('update', $user))->toBeTrue();
        });

        it('denies athlete from updating another user', function () {
            $athlete = User::factory()->athlete()->create();
            $other = User::factory()->coach()->create();
            expect($athlete->can('update', $other))->toBeFalse();
        });

        it('denies coach from updating another user', function () {
            $coach = User::factory()->coach()->create();
            $other = User::factory()->athlete()->create();
            expect($coach->can('update', $other))->toBeFalse();
        });

        it('denies accountant from updating another user', function () {
            $accountant = User::factory()->accountant()->create();
            $other = User::factory()->athlete()->create();
            expect($accountant->can('update', $other))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows admin to delete any user', function () {
            $admin = User::factory()->admin()->create();
            $other = User::factory()->athlete()->create();
            expect($admin->can('delete', $other))->toBeTrue();
        });

        it('denies coach from deleting a user', function () {
            $coach = User::factory()->coach()->create();
            $other = User::factory()->athlete()->create();
            expect($coach->can('delete', $other))->toBeFalse();
        });

        it('denies athlete from deleting a user', function () {
            $athlete = User::factory()->athlete()->create();
            expect($athlete->can('delete', $athlete))->toBeFalse();
        });

        it('denies accountant from deleting a user', function () {
            $accountant = User::factory()->accountant()->create();
            $other = User::factory()->athlete()->create();
            expect($accountant->can('delete', $other))->toBeFalse();
        });
    });

    describe('promote', function () {
        it('allows admin to promote a user', function () {
            $admin = User::factory()->admin()->create();
            $other = User::factory()->athlete()->create();
            expect($admin->can('promote', $other))->toBeTrue();
        });

        it('denies coach from promoting a user', function () {
            $coach = User::factory()->coach()->create();
            $other = User::factory()->athlete()->create();
            expect($coach->can('promote', $other))->toBeFalse();
        });

        it('denies athlete from promoting a user', function () {
            $athlete = User::factory()->athlete()->create();
            expect($athlete->can('promote', $athlete))->toBeFalse();
        });

        it('denies accountant from promoting a user', function () {
            $accountant = User::factory()->accountant()->create();
            $other = User::factory()->athlete()->create();
            expect($accountant->can('promote', $other))->toBeFalse();
        });
    });
});
