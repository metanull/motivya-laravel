<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use App\Livewire\Session\Create;
use App\Models\SportSession;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('recurring session creation via service', function () {
    it('creates the correct number of sessions', function () {
        $coach = User::factory()->coach()->create();
        $service = app(SessionService::class);
        $futureDate = now()->addDays(7)->format('Y-m-d');

        $sessions = $service->createRecurring($coach, [
            'activity_type' => ActivityType::Yoga->value,
            'level' => SessionLevel::Beginner->value,
            'title' => 'Weekly Yoga',
            'description' => null,
            'location' => 'Park',
            'postal_code' => '1000',
            'date' => $futureDate,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'price_per_person' => 1500,
            'min_participants' => 3,
            'max_participants' => 15,
            'cover_image_id' => null,
        ], 4);

        expect($sessions)->toHaveCount(4);
        expect(SportSession::count())->toBe(4);
    });

    it('generates correct weekly dates', function () {
        $coach = User::factory()->coach()->create();
        $service = app(SessionService::class);
        $baseDate = now()->addDays(7);

        $sessions = $service->createRecurring($coach, [
            'activity_type' => ActivityType::Running->value,
            'level' => SessionLevel::Advanced->value,
            'title' => 'Weekly Run',
            'location' => 'Forest',
            'postal_code' => '1170',
            'date' => $baseDate->format('Y-m-d'),
            'start_time' => '07:00',
            'end_time' => '08:30',
            'price_per_person' => 1000,
            'min_participants' => 2,
            'max_participants' => 10,
        ], 3);

        expect($sessions[0]->date->format('Y-m-d'))->toBe($baseDate->format('Y-m-d'));
        expect($sessions[1]->date->format('Y-m-d'))->toBe($baseDate->copy()->addWeek()->format('Y-m-d'));
        expect($sessions[2]->date->format('Y-m-d'))->toBe($baseDate->copy()->addWeeks(2)->format('Y-m-d'));
    });

    it('shares a recurrence group id', function () {
        $coach = User::factory()->coach()->create();
        $service = app(SessionService::class);

        $sessions = $service->createRecurring($coach, [
            'activity_type' => ActivityType::Cardio->value,
            'level' => SessionLevel::Intermediate->value,
            'title' => 'Weekly Cardio',
            'location' => 'Gym',
            'postal_code' => '1050',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '19:00',
            'price_per_person' => 2000,
            'min_participants' => 5,
            'max_participants' => 20,
        ], 5);

        $groupId = $sessions->first()->recurrence_group_id;
        expect($groupId)->not->toBeNull();

        foreach ($sessions as $session) {
            expect($session->recurrence_group_id)->toBe($groupId);
        }
    });

    it('creates each session in draft status', function () {
        $coach = User::factory()->coach()->create();
        $service = app(SessionService::class);

        $sessions = $service->createRecurring($coach, [
            'activity_type' => ActivityType::Tennis->value,
            'level' => SessionLevel::Beginner->value,
            'title' => 'Tennis',
            'location' => 'Court',
            'postal_code' => '1200',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'price_per_person' => 3000,
            'min_participants' => 2,
            'max_participants' => 4,
        ], 3);

        foreach ($sessions as $session) {
            expect($session->status)->toBe(SessionStatus::Draft);
            expect($session->current_participants)->toBe(0);
        }
    });

    it('creates each session as independent (own id)', function () {
        $coach = User::factory()->coach()->create();
        $service = app(SessionService::class);

        $sessions = $service->createRecurring($coach, [
            'activity_type' => ActivityType::Dance->value,
            'level' => SessionLevel::Intermediate->value,
            'title' => 'Dance',
            'location' => 'Studio',
            'postal_code' => '1000',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '20:00',
            'end_time' => '21:30',
            'price_per_person' => 1800,
            'min_participants' => 4,
            'max_participants' => 16,
        ], 4);

        $ids = $sessions->pluck('id')->unique();
        expect($ids)->toHaveCount(4);
    });
});

describe('recurring session creation via Livewire', function () {
    it('creates recurring sessions via the form', function () {
        $coach = User::factory()->coach()->create();
        $futureDate = now()->addDays(7)->format('Y-m-d');

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Yoga->value)
            ->set('form.level', SessionLevel::Beginner->value)
            ->set('form.title', 'Weekly Yoga')
            ->set('form.location', 'Park')
            ->set('form.postalCode', '1000')
            ->set('form.date', $futureDate)
            ->set('form.startTime', '09:00')
            ->set('form.endTime', '10:00')
            ->set('form.priceEuros', '15.00')
            ->set('form.minParticipants', 3)
            ->set('form.maxParticipants', 15)
            ->set('form.isRecurring', true)
            ->set('form.numberOfWeeks', 4)
            ->call('save')
            ->assertDispatched('notify')
            ->assertRedirect();

        expect(SportSession::count())->toBe(4);

        $groupId = SportSession::first()->recurrence_group_id;
        expect($groupId)->not->toBeNull();
        expect(SportSession::where('recurrence_group_id', $groupId)->count())->toBe(4);
    });

    it('creates a single session when recurring is unchecked', function () {
        $coach = User::factory()->coach()->create();
        $futureDate = now()->addDays(7)->format('Y-m-d');

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Yoga->value)
            ->set('form.level', SessionLevel::Beginner->value)
            ->set('form.title', 'One-off Yoga')
            ->set('form.location', 'Park')
            ->set('form.postalCode', '1000')
            ->set('form.date', $futureDate)
            ->set('form.startTime', '09:00')
            ->set('form.endTime', '10:00')
            ->set('form.priceEuros', '15.00')
            ->set('form.minParticipants', 3)
            ->set('form.maxParticipants', 15)
            ->set('form.isRecurring', false)
            ->call('save')
            ->assertRedirect();

        expect(SportSession::count())->toBe(1);
        expect(SportSession::first()->recurrence_group_id)->toBeNull();
    });

    it('shows the recurring options when checkbox is checked', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->assertDontSee(__('sessions.number_of_weeks'))
            ->set('form.isRecurring', true)
            ->assertSee(__('sessions.number_of_weeks'));
    });
});
