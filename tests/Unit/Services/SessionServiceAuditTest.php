<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SessionService audit', function () {
    it('records a session.created event when creating a session', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_onboarding_complete' => true]);
        $service = app(SessionService::class);

        $service->create($coach, [
            'activity_type' => 'yoga',
            'level' => 'beginner',
            'title' => 'Morning Yoga',
            'location' => 'Parc du Cinquantenaire',
            'postal_code' => '1000',
            'date' => now()->addWeek()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'price_per_person' => 1500,
            'min_participants' => 2,
            'max_participants' => 10,
        ]);

        expect(
            AuditEvent::where('event_type', AuditEventType::SessionCreated->value)->exists()
        )->toBeTrue();
    });

    it('records a session.updated event when updating a session', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_onboarding_complete' => true]);
        $session = SportSession::factory()->draft()->for($coach, 'coach')->create();
        $service = app(SessionService::class);

        $service->update($session, [
            'activity_type' => 'yoga',
            'level' => 'beginner',
            'title' => 'Updated Yoga',
            'location' => 'Parc du Cinquantenaire',
            'postal_code' => '1000',
            'date' => now()->addWeek()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'price_per_person' => 2000,
            'min_participants' => 2,
            'max_participants' => 10,
        ]);

        expect(
            AuditEvent::where('event_type', AuditEventType::SessionUpdated->value)->exists()
        )->toBeTrue();
    });

    it('records a session.published event when publishing a session', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_onboarding_complete' => true]);
        $session = SportSession::factory()->draft()->for($coach, 'coach')->create([
            'title' => 'Yoga',
            'location' => 'Parc',
            'postal_code' => '1000',
            'date' => now()->addWeek(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'price_per_person' => 1500,
            'min_participants' => 2,
            'max_participants' => 10,
        ]);
        $service = app(SessionService::class);

        $service->publish($session);

        expect(
            AuditEvent::where('event_type', AuditEventType::SessionPublished->value)->exists()
        )->toBeTrue();
    });

    it('records a session.cancelled event with reason metadata', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $service = app(SessionService::class);

        $service->cancel($session, 'Bad weather');

        $audit = AuditEvent::where('event_type', AuditEventType::SessionCancelled->value)->firstOrFail();

        expect($audit->metadata['reason'])->toBe('Bad weather');
    });

    it('records a session.cancelled event without reason when no reason given', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $service = app(SessionService::class);

        $service->cancel($session);

        expect(
            AuditEvent::where('event_type', AuditEventType::SessionCancelled->value)->exists()
        )->toBeTrue();
    });

    it('records a session.completed event when completing a session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $service = app(SessionService::class);

        $service->complete($session);

        expect(
            AuditEvent::where('event_type', AuditEventType::SessionCompleted->value)->exists()
        )->toBeTrue();
    });

    it('records a session.deleted event before deleting a draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->for($coach, 'coach')->create();
        $sessionId = $session->id;
        $service = app(SessionService::class);

        $service->delete($session);

        expect(SportSession::find($sessionId))->toBeNull()
            ->and(AuditEvent::where('event_type', AuditEventType::SessionDeleted->value)->exists())->toBeTrue();
    });

    it('records one session.created event per session in createRecurring', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_onboarding_complete' => true]);
        $service = app(SessionService::class);

        $service->createRecurring($coach, [
            'activity_type' => 'yoga',
            'level' => 'beginner',
            'title' => 'Weekly Yoga',
            'location' => 'Parc',
            'postal_code' => '1000',
            'date' => now()->addWeek()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'price_per_person' => 1500,
            'min_participants' => 2,
            'max_participants' => 10,
        ], numberOfWeeks: 3);

        expect(
            AuditEvent::where('event_type', AuditEventType::SessionCreated->value)->count()
        )->toBe(3);
    });
});
