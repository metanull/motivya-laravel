<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('SportSession', function () {

    it('belongs to a coach user', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->create(['coach_id' => $coach->id]);

        expect($session->coach)->toBeInstanceOf(User::class);
        expect($session->coach->id)->toBe($coach->id);
        expect($session->coach->role)->toBe(UserRole::Coach);
    });

    it('casts activity_type to ActivityType enum', function () {
        $session = SportSession::factory()->create(['activity_type' => ActivityType::Yoga->value]);

        expect($session->activity_type)->toBeInstanceOf(ActivityType::class);
        expect($session->activity_type)->toBe(ActivityType::Yoga);
    });

    it('casts level to SessionLevel enum', function () {
        $session = SportSession::factory()->create(['level' => SessionLevel::Beginner->value]);

        expect($session->level)->toBeInstanceOf(SessionLevel::class);
        expect($session->level)->toBe(SessionLevel::Beginner);
    });

    it('casts status to SessionStatus enum', function () {
        $session = SportSession::factory()->create(['status' => SessionStatus::Published->value]);

        expect($session->status)->toBeInstanceOf(SessionStatus::class);
        expect($session->status)->toBe(SessionStatus::Published);
    });

    it('casts date to a date instance', function () {
        $session = SportSession::factory()->create(['date' => '2026-05-01']);

        expect($session->date)->toBeInstanceOf(Carbon::class);
        expect($session->date->format('Y-m-d'))->toBe('2026-05-01');
    });

    it('casts price_per_person to integer', function () {
        $session = SportSession::factory()->create(['price_per_person' => 1250]);

        expect($session->price_per_person)->toBeInt();
        expect($session->price_per_person)->toBe(1250);
    });

    it('casts participant counts to integers', function () {
        $session = SportSession::factory()->create([
            'min_participants' => 3,
            'max_participants' => 15,
            'current_participants' => 5,
        ]);

        expect($session->min_participants)->toBeInt();
        expect($session->max_participants)->toBeInt();
        expect($session->current_participants)->toBeInt();
    });

    it('stores nullable latitude and longitude', function () {
        $session = SportSession::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        expect($session->latitude)->toBeNull();
        expect($session->longitude)->toBeNull();
    });

    it('stores coordinates when provided', function () {
        $session = SportSession::factory()->withCoordinates()->create();

        expect($session->latitude)->not()->toBeNull();
        expect($session->longitude)->not()->toBeNull();
    });

    it('stores nullable cover_image_id', function () {
        $session = SportSession::factory()->create(['cover_image_id' => null]);

        expect($session->cover_image_id)->toBeNull();
    });

    it('stores nullable recurrence_group_id', function () {
        $session = SportSession::factory()->create(['recurrence_group_id' => null]);

        expect($session->recurrence_group_id)->toBeNull();
    });

    it('defaults status to draft', function () {
        $session = SportSession::factory()->create();
        $session->refresh();

        expect($session->status)->toBe(SessionStatus::Draft);
    });

    it('defaults current_participants to 0', function () {
        $session = SportSession::factory()->create();
        $session->refresh();

        expect($session->current_participants)->toBe(0);
    });
});

describe('SportSessionFactory', function () {

    it('creates a draft session by default', function () {
        $session = SportSession::factory()->create();

        expect($session->status)->toBe(SessionStatus::Draft);
    });

    it('creates a published session', function () {
        $session = SportSession::factory()->published()->create();

        expect($session->status)->toBe(SessionStatus::Published);
    });

    it('creates a confirmed session', function () {
        $session = SportSession::factory()->confirmed()->create();

        expect($session->status)->toBe(SessionStatus::Confirmed);
    });

    it('creates a completed session', function () {
        $session = SportSession::factory()->completed()->create();

        expect($session->status)->toBe(SessionStatus::Completed);
    });

    it('creates a cancelled session', function () {
        $session = SportSession::factory()->cancelled()->create();

        expect($session->status)->toBe(SessionStatus::Cancelled);
    });

    it('associates with a coach user by default', function () {
        $session = SportSession::factory()->create();

        expect($session->coach->role)->toBe(UserRole::Coach);
    });

    it('creates session with coordinates', function () {
        $session = SportSession::factory()->withCoordinates()->create();

        expect($session->latitude)->not()->toBeNull();
        expect($session->longitude)->not()->toBeNull();
    });
});
