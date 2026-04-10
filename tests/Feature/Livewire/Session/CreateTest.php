<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use App\Livewire\Session\Create;
use App\Models\ActivityImage;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('session creation', function () {
    it('renders the form for coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.sessions.create'))
            ->assertOk();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('coach.sessions.create'))
            ->assertForbidden();
    });

    it('denies access to unauthenticated users', function () {
        $this->get(route('coach.sessions.create'))
            ->assertRedirect(route('login'));
    });

    it('creates a session with valid data', function () {
        $coach = User::factory()->coach()->create();
        $futureDate = now()->addDays(7)->format('Y-m-d');

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Yoga->value)
            ->set('form.level', SessionLevel::Beginner->value)
            ->set('form.title', 'Morning Yoga Flow')
            ->set('form.description', 'A relaxing morning session')
            ->set('form.location', 'Parc du Cinquantenaire')
            ->set('form.postalCode', '1000')
            ->set('form.date', $futureDate)
            ->set('form.startTime', '09:00')
            ->set('form.endTime', '10:00')
            ->set('form.priceEuros', '15.00')
            ->set('form.minParticipants', 3)
            ->set('form.maxParticipants', 15)
            ->call('save')
            ->assertDispatched('notify')
            ->assertRedirect();

        $session = SportSession::first();
        expect($session)->not->toBeNull();
        expect($session->coach_id)->toBe($coach->id);
        expect($session->activity_type)->toBe(ActivityType::Yoga);
        expect($session->level)->toBe(SessionLevel::Beginner);
        expect($session->title)->toBe('Morning Yoga Flow');
        expect($session->price_per_person)->toBe(1500);
        expect($session->status)->toBe(SessionStatus::Draft);
        expect($session->current_participants)->toBe(0);
    });

    it('creates a session with a cover image', function () {
        $coach = User::factory()->coach()->create();
        $image = ActivityImage::factory()->forActivity(ActivityType::Yoga)->create();
        $futureDate = now()->addDays(7)->format('Y-m-d');

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Yoga->value)
            ->set('form.level', SessionLevel::Intermediate->value)
            ->set('form.title', 'Yoga with Cover')
            ->set('form.location', 'Studio')
            ->set('form.postalCode', '1050')
            ->set('form.date', $futureDate)
            ->set('form.startTime', '10:00')
            ->set('form.endTime', '11:00')
            ->set('form.priceEuros', '20.00')
            ->set('form.minParticipants', 2)
            ->set('form.maxParticipants', 10)
            ->set('form.coverImageId', $image->id)
            ->call('save')
            ->assertRedirect();

        $session = SportSession::first();
        expect($session->cover_image_id)->toBe($image->id);
    });

    it('validates required fields', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->call('save')
            ->assertHasErrors([
                'form.activityType',
                'form.level',
                'form.title',
                'form.location',
                'form.postalCode',
                'form.date',
                'form.startTime',
                'form.endTime',
                'form.priceEuros',
            ]);
    });

    it('validates end time is after start time', function () {
        $coach = User::factory()->coach()->create();
        $futureDate = now()->addDays(7)->format('Y-m-d');

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Yoga->value)
            ->set('form.level', SessionLevel::Beginner->value)
            ->set('form.title', 'Test')
            ->set('form.location', 'Place')
            ->set('form.postalCode', '1000')
            ->set('form.date', $futureDate)
            ->set('form.startTime', '10:00')
            ->set('form.endTime', '09:00')
            ->set('form.priceEuros', '10.00')
            ->set('form.minParticipants', 1)
            ->set('form.maxParticipants', 5)
            ->call('save')
            ->assertHasErrors(['form.endTime']);
    });

    it('validates max participants >= min participants', function () {
        $coach = User::factory()->coach()->create();
        $futureDate = now()->addDays(7)->format('Y-m-d');

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Yoga->value)
            ->set('form.level', SessionLevel::Beginner->value)
            ->set('form.title', 'Test')
            ->set('form.location', 'Place')
            ->set('form.postalCode', '1000')
            ->set('form.date', $futureDate)
            ->set('form.startTime', '09:00')
            ->set('form.endTime', '10:00')
            ->set('form.priceEuros', '10.00')
            ->set('form.minParticipants', 10)
            ->set('form.maxParticipants', 5)
            ->call('save')
            ->assertHasErrors(['form.maxParticipants']);
    });

    it('validates date is in the future', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Yoga->value)
            ->set('form.level', SessionLevel::Beginner->value)
            ->set('form.title', 'Test')
            ->set('form.location', 'Place')
            ->set('form.postalCode', '1000')
            ->set('form.date', now()->subDay()->format('Y-m-d'))
            ->set('form.startTime', '09:00')
            ->set('form.endTime', '10:00')
            ->set('form.priceEuros', '10.00')
            ->set('form.minParticipants', 1)
            ->set('form.maxParticipants', 5)
            ->call('save')
            ->assertHasErrors(['form.date']);
    });

    it('validates postal code format', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.postalCode', '999')
            ->call('save')
            ->assertHasErrors(['form.postalCode']);

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.postalCode', '0100')
            ->call('save')
            ->assertHasErrors(['form.postalCode']);
    });

    it('converts price from euros to cents', function () {
        $coach = User::factory()->coach()->create();
        $futureDate = now()->addDays(7)->format('Y-m-d');

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Running->value)
            ->set('form.level', SessionLevel::Advanced->value)
            ->set('form.title', 'Trail Run')
            ->set('form.location', 'Forêt de Soignes')
            ->set('form.postalCode', '1170')
            ->set('form.date', $futureDate)
            ->set('form.startTime', '07:00')
            ->set('form.endTime', '09:00')
            ->set('form.priceEuros', '25.50')
            ->set('form.minParticipants', 5)
            ->set('form.maxParticipants', 20)
            ->call('save')
            ->assertRedirect();

        expect(SportSession::first()->price_per_person)->toBe(2550);
    });

    it('shows cover images filtered by activity type', function () {
        $coach = User::factory()->coach()->create();
        $yogaImage = ActivityImage::factory()->forActivity(ActivityType::Yoga)->create();
        $runningImage = ActivityImage::factory()->forActivity(ActivityType::Running)->create();

        Livewire::actingAs($coach)
            ->test(Create::class)
            ->set('form.activityType', ActivityType::Yoga->value)
            ->assertViewHas('coverImages', fn ($images) => $images->count() === 1 && $images->first()->id === $yogaImage->id);
    });
});
