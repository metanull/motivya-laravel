<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Livewire\Session\Create;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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
