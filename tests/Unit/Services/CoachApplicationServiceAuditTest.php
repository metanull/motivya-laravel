<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\CoachProfile;
use App\Models\User;
use App\Services\CoachApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$applyData = [
    'specialties' => ['yoga'],
    'bio' => 'Experienced coach',
    'experience_level' => 'advanced',
    'postal_code' => '1000',
    'country' => 'BE',
    'enterprise_number' => 'BE0123456789',
];

describe('CoachApplicationService audit', function () use ($applyData) {
    it('records a coach.application_submitted event when a coach applies', function () use ($applyData) {
        $user = User::factory()->athlete()->create();
        $service = app(CoachApplicationService::class);

        $service->apply($user, $applyData);

        expect(
            AuditEvent::where('event_type', AuditEventType::CoachApplicationSubmitted->value)->exists()
        )->toBeTrue();
    });

    it('creates the audit event in the same transaction as the coach profile', function () use ($applyData) {
        $user = User::factory()->athlete()->create();
        $service = app(CoachApplicationService::class);

        $service->apply($user, $applyData);

        $profile = CoachProfile::where('user_id', $user->id)->firstOrFail();
        $audit = AuditEvent::where('event_type', AuditEventType::CoachApplicationSubmitted->value)->firstOrFail();

        // The audit subject should reference the newly created coach profile
        expect($audit->subjects()->where('subject_type', CoachProfile::class)->where('subject_id', $profile->id)->exists())->toBeTrue();
    });
});
