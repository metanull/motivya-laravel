<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('viewAny', function () {

    it('allows a coach to list invoices', function () {
        $coach = User::factory()->coach()->create();

        expect($coach->can('viewAny', Invoice::class))->toBeTrue();
    });

    it('allows an accountant to list all invoices', function () {
        $accountant = User::factory()->accountant()->create();

        expect($accountant->can('viewAny', Invoice::class))->toBeTrue();
    });

    it('denies an athlete from listing invoices', function () {
        $athlete = User::factory()->athlete()->create();

        expect($athlete->can('viewAny', Invoice::class))->toBeFalse();
    });

    it('allows admin to list invoices (before bypass)', function () {
        $admin = User::factory()->admin()->create();

        expect($admin->can('viewAny', Invoice::class))->toBeTrue();
    });

});

describe('view', function () {

    it('allows a coach to view their own invoice', function () {
        $coach = User::factory()->coach()->create();
        $invoice = Invoice::factory()->create(['coach_id' => $coach->id]);

        expect($coach->can('view', $invoice))->toBeTrue();
    });

    it('denies a coach from viewing another coach invoice', function () {
        $otherCoach = User::factory()->coach()->create();
        $invoice = Invoice::factory()->create();

        expect($otherCoach->can('view', $invoice))->toBeFalse();
    });

    it('allows an accountant to view any invoice', function () {
        $accountant = User::factory()->accountant()->create();
        $invoice = Invoice::factory()->create();

        expect($accountant->can('view', $invoice))->toBeTrue();
    });

    it('allows an athlete to view an invoice for a session they have booked', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->create();
        Booking::factory()->create([
            'athlete_id' => $athlete->id,
            'sport_session_id' => $session->id,
        ]);
        $invoice = Invoice::factory()->create(['sport_session_id' => $session->id]);

        expect($athlete->can('view', $invoice))->toBeTrue();
    });

    it('denies an athlete from viewing an invoice for a session they have not booked', function () {
        $athlete = User::factory()->athlete()->create();
        $invoice = Invoice::factory()->create();

        expect($athlete->can('view', $invoice))->toBeFalse();
    });

    it('denies an athlete from viewing a subscription invoice (no session attached)', function () {
        $athlete = User::factory()->athlete()->create();
        $invoice = Invoice::factory()->create(['sport_session_id' => null]);

        expect($athlete->can('view', $invoice))->toBeFalse();
    });

    it('allows admin to view any invoice (before bypass)', function () {
        $admin = User::factory()->admin()->create();
        $invoice = Invoice::factory()->create();

        expect($admin->can('view', $invoice))->toBeTrue();
    });

});

describe('create', function () {

    it('denies a coach from creating an invoice manually', function () {
        $coach = User::factory()->coach()->create();

        expect($coach->can('create', Invoice::class))->toBeFalse();
    });

    it('denies an athlete from creating an invoice', function () {
        $athlete = User::factory()->athlete()->create();

        expect($athlete->can('create', Invoice::class))->toBeFalse();
    });

    it('denies an accountant from creating an invoice', function () {
        $accountant = User::factory()->accountant()->create();

        expect($accountant->can('create', Invoice::class))->toBeFalse();
    });

    it('allows admin to create an invoice (before bypass)', function () {
        $admin = User::factory()->admin()->create();

        expect($admin->can('create', Invoice::class))->toBeTrue();
    });

});

describe('export', function () {

    it('allows an accountant to export invoices', function () {
        $accountant = User::factory()->accountant()->create();

        expect($accountant->can('export', Invoice::class))->toBeTrue();
    });

    it('denies a coach from exporting invoices', function () {
        $coach = User::factory()->coach()->create();

        expect($coach->can('export', Invoice::class))->toBeFalse();
    });

    it('denies an athlete from exporting invoices', function () {
        $athlete = User::factory()->athlete()->create();

        expect($athlete->can('export', Invoice::class))->toBeFalse();
    });

    it('allows admin to export invoices (before bypass)', function () {
        $admin = User::factory()->admin()->create();

        expect($admin->can('export', Invoice::class))->toBeTrue();
    });

});
