<?php

declare(strict_types=1);

use App\Enums\CoachProfileStatus;
use App\Enums\UserRole;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('CoachProfile', function () {

    it('belongs to a user', function () {
        $user = User::factory()->coach()->create();
        $profile = CoachProfile::factory()->create(['user_id' => $user->id]);

        expect($profile->user)->toBeInstanceOf(User::class);
        expect($profile->user->id)->toBe($user->id);
    });

    it('is accessible from user via hasOne relationship', function () {
        $user = User::factory()->coach()->create();
        $profile = CoachProfile::factory()->create(['user_id' => $user->id]);

        expect($user->coachProfile)->toBeInstanceOf(CoachProfile::class);
        expect($user->coachProfile->id)->toBe($profile->id);
    });

    it('casts status to CoachProfileStatus enum', function () {
        $profile = CoachProfile::factory()->create();

        expect($profile->status)->toBeInstanceOf(CoachProfileStatus::class);
    });

    it('casts specialties to array', function () {
        $profile = CoachProfile::factory()->create(['specialties' => ['yoga', 'running']]);

        expect($profile->specialties)->toBeArray();
        expect($profile->specialties)->toBe(['yoga', 'running']);
    });

    it('casts is_vat_subject to boolean', function () {
        $profile = CoachProfile::factory()->create(['is_vat_subject' => true]);

        expect($profile->is_vat_subject)->toBeTrue();
        expect($profile->is_vat_subject)->toBeBool();
    });

    it('casts stripe_onboarding_complete to boolean', function () {
        $profile = CoachProfile::factory()->create(['stripe_onboarding_complete' => false]);

        expect($profile->stripe_onboarding_complete)->toBeFalse();
        expect($profile->stripe_onboarding_complete)->toBeBool();
    });

    it('casts verified_at to datetime', function () {
        $profile = CoachProfile::factory()->approved()->create();

        expect($profile->verified_at)->toBeInstanceOf(Carbon::class);
    });

    it('has nullable verified_at for pending profiles', function () {
        $profile = CoachProfile::factory()->pending()->create();

        expect($profile->verified_at)->toBeNull();
    });

    it('defaults country to BE', function () {
        $profile = CoachProfile::factory()->create();

        expect($profile->country)->toBe('BE');
    });

    it('defaults stripe_onboarding_complete to false', function () {
        $profile = CoachProfile::factory()->create();
        $profile->refresh();

        expect($profile->stripe_onboarding_complete)->toBeFalse();
    });

    it('has null is_vat_subject by default (not yet reviewed by admin)', function () {
        $profile = CoachProfile::factory()->create();
        $profile->refresh();

        // null = admin has not yet captured VAT status; true/false = explicitly set
        expect($profile->is_vat_subject)->toBeNull();
    });
});

describe('CoachProfileFactory', function () {

    it('creates a pending profile by default', function () {
        $profile = CoachProfile::factory()->create();

        expect($profile->status)->toBe(CoachProfileStatus::Pending);
    });

    it('creates an approved profile with verified_at set', function () {
        $profile = CoachProfile::factory()->approved()->create();

        expect($profile->status)->toBe(CoachProfileStatus::Approved);
        expect($profile->verified_at)->not()->toBeNull();
    });

    it('creates a rejected profile', function () {
        $profile = CoachProfile::factory()->rejected()->create();

        expect($profile->status)->toBe(CoachProfileStatus::Rejected);
    });

    it('creates a vat subject profile', function () {
        $profile = CoachProfile::factory()->vatSubject()->create();

        expect($profile->is_vat_subject)->toBeTrue();
    });

    it('creates a non-vat subject profile', function () {
        $profile = CoachProfile::factory()->nonVatSubject()->create();

        expect($profile->is_vat_subject)->toBeFalse();
    });

    it('associates with a coach user by default', function () {
        $profile = CoachProfile::factory()->create();

        expect($profile->user->role)->toBe(UserRole::Coach);
    });
});
