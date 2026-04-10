<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('viewAny', function () {
    it('allows any authenticated user', function (string $role) {
        $user = User::factory()->{$role}()->create();

        expect($user->can('viewAny', SportSession::class))->toBeTrue();
    })->with(['coach', 'athlete', 'accountant', 'admin']);
});

describe('view', function () {
    it('allows any authenticated user to view published sessions', function (string $role) {
        $user = User::factory()->{$role}()->create();
        $session = SportSession::factory()->published()->create();

        expect($user->can('view', $session))->toBeTrue();
    })->with(['coach', 'athlete', 'accountant', 'admin']);

    it('allows any authenticated user to view confirmed sessions', function (string $role) {
        $user = User::factory()->{$role}()->create();
        $session = SportSession::factory()->confirmed()->create();

        expect($user->can('view', $session))->toBeTrue();
    })->with(['coach', 'athlete', 'accountant', 'admin']);

    it('allows the owning coach to view their own draft sessions', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        expect($coach->can('view', $session))->toBeTrue();
    });

    it('denies other coaches from viewing draft sessions they do not own', function () {
        $otherCoach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create();

        expect($otherCoach->can('view', $session))->toBeFalse();
    });

    it('denies athletes from viewing draft sessions', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->draft()->create();

        expect($athlete->can('view', $session))->toBeFalse();
    });

    it('denies accountants from viewing draft sessions', function () {
        $accountant = User::factory()->accountant()->create();
        $session = SportSession::factory()->draft()->create();

        expect($accountant->can('view', $session))->toBeFalse();
    });

    it('allows admin to view draft sessions', function () {
        $admin = User::factory()->admin()->create();
        $session = SportSession::factory()->draft()->create();

        expect($admin->can('view', $session))->toBeTrue();
    });

    it('denies athletes from viewing completed sessions', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->completed()->create();

        expect($athlete->can('view', $session))->toBeFalse();
    });

    it('denies athletes from viewing cancelled sessions', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->cancelled()->create();

        expect($athlete->can('view', $session))->toBeFalse();
    });
});

describe('create', function () {
    it('allows coaches to create sessions', function () {
        $coach = User::factory()->coach()->create();

        expect($coach->can('create', SportSession::class))->toBeTrue();
    });

    it('allows admin to create sessions', function () {
        $admin = User::factory()->admin()->create();

        expect($admin->can('create', SportSession::class))->toBeTrue();
    });

    it('denies athletes from creating sessions', function () {
        $athlete = User::factory()->athlete()->create();

        expect($athlete->can('create', SportSession::class))->toBeFalse();
    });

    it('denies accountants from creating sessions', function () {
        $accountant = User::factory()->accountant()->create();

        expect($accountant->can('create', SportSession::class))->toBeFalse();
    });
});

describe('update', function () {
    it('allows the owning coach to update their draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        expect($coach->can('update', $session))->toBeTrue();
    });

    it('allows the owning coach to update their published session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create(['coach_id' => $coach->id]);

        expect($coach->can('update', $session))->toBeTrue();
    });

    it('denies the owning coach from updating a completed session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->completed()->create(['coach_id' => $coach->id]);

        expect($coach->can('update', $session))->toBeFalse();
    });

    it('denies the owning coach from updating a cancelled session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->cancelled()->create(['coach_id' => $coach->id]);

        expect($coach->can('update', $session))->toBeFalse();
    });

    it('denies other coaches from updating sessions they do not own', function () {
        $otherCoach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create();

        expect($otherCoach->can('update', $session))->toBeFalse();
    });

    it('denies athletes from updating sessions', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->draft()->create();

        expect($athlete->can('update', $session))->toBeFalse();
    });

    it('allows admin to update any draft session', function () {
        $admin = User::factory()->admin()->create();
        $session = SportSession::factory()->draft()->create();

        expect($admin->can('update', $session))->toBeTrue();
    });

    it('allows admin to update even completed sessions (admin bypass)', function () {
        $admin = User::factory()->admin()->create();
        $session = SportSession::factory()->completed()->create();

        expect($admin->can('update', $session))->toBeTrue();
    });
});

describe('delete', function () {
    it('allows the owning coach to delete their draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        expect($coach->can('delete', $session))->toBeTrue();
    });

    it('denies the owning coach from deleting a published session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create(['coach_id' => $coach->id]);

        expect($coach->can('delete', $session))->toBeFalse();
    });

    it('denies the owning coach from deleting a confirmed session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->create(['coach_id' => $coach->id]);

        expect($coach->can('delete', $session))->toBeFalse();
    });

    it('denies the owning coach from deleting a completed session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->completed()->create(['coach_id' => $coach->id]);

        expect($coach->can('delete', $session))->toBeFalse();
    });

    it('denies other coaches from deleting sessions they do not own', function () {
        $otherCoach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create();

        expect($otherCoach->can('delete', $session))->toBeFalse();
    });

    it('denies athletes from deleting sessions', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->draft()->create();

        expect($athlete->can('delete', $session))->toBeFalse();
    });

    it('allows admin to delete any session (admin bypass)', function () {
        $admin = User::factory()->admin()->create();
        $session = SportSession::factory()->published()->create();

        expect($admin->can('delete', $session))->toBeTrue();
    });
});

describe('cancel', function () {
    it('allows the owning coach to cancel their published session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create(['coach_id' => $coach->id]);

        expect($coach->can('cancel', $session))->toBeTrue();
    });

    it('allows the owning coach to cancel their confirmed session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->create(['coach_id' => $coach->id]);

        expect($coach->can('cancel', $session))->toBeTrue();
    });

    it('denies the owning coach from cancelling a draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create(['coach_id' => $coach->id]);

        expect($coach->can('cancel', $session))->toBeFalse();
    });

    it('denies the owning coach from cancelling a completed session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->completed()->create(['coach_id' => $coach->id]);

        expect($coach->can('cancel', $session))->toBeFalse();
    });

    it('denies the owning coach from cancelling an already cancelled session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->cancelled()->create(['coach_id' => $coach->id]);

        expect($coach->can('cancel', $session))->toBeFalse();
    });

    it('denies other coaches from cancelling sessions they do not own', function () {
        $otherCoach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create();

        expect($otherCoach->can('cancel', $session))->toBeFalse();
    });

    it('denies athletes from cancelling sessions', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create();

        expect($athlete->can('cancel', $session))->toBeFalse();
    });

    it('allows admin to cancel any session (admin bypass)', function () {
        $admin = User::factory()->admin()->create();
        $session = SportSession::factory()->draft()->create();

        expect($admin->can('cancel', $session))->toBeTrue();
    });
});
