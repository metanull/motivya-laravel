<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

describe('access-admin-panel gate', function () {

    it('grants access to admin users', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        expect(Gate::allows('access-admin-panel'))->toBeTrue();
    });

    it('denies access to athlete users', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete);

        expect(Gate::allows('access-admin-panel'))->toBeFalse();
    });

    it('denies access to coach users', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach);

        expect(Gate::allows('access-admin-panel'))->toBeFalse();
    });

    it('denies access to accountant users', function () {
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant);

        expect(Gate::allows('access-admin-panel'))->toBeFalse();
    });
});
