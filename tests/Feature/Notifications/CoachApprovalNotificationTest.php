<?php

declare(strict_types=1);

use App\Events\CoachApproved;
use App\Events\CoachRejected;
use App\Listeners\SendCoachApprovedNotification;
use App\Listeners\SendCoachRejectedNotification;
use App\Models\CoachProfile;
use App\Notifications\CoachApprovedNotification;
use App\Notifications\CoachRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('Coach Approval Notifications', function () {

    it('sends approved notification when CoachApproved event is dispatched', function () {
        Notification::fake();

        $profile = CoachProfile::factory()->approved()->create();

        $listener = new SendCoachApprovedNotification;
        $listener->handle(new CoachApproved($profile->id));

        Notification::assertSentTo(
            $profile->user,
            CoachApprovedNotification::class,
        );
    });

    it('sends rejected notification when CoachRejected event is dispatched', function () {
        Notification::fake();

        $profile = CoachProfile::factory()->pending()->create();
        $reason = 'Missing qualifications';

        $listener = new SendCoachRejectedNotification;
        $listener->handle(new CoachRejected($profile->id, $reason));

        Notification::assertSentTo(
            $profile->user,
            CoachRejectedNotification::class,
        );
    });

    it('approved notification uses mail and database channels', function () {
        Notification::fake();

        $profile = CoachProfile::factory()->approved()->create();

        $listener = new SendCoachApprovedNotification;
        $listener->handle(new CoachApproved($profile->id));

        Notification::assertSentTo(
            $profile->user,
            CoachApprovedNotification::class,
            function ($notification, $channels) {
                return $channels === ['mail', 'database'];
            },
        );
    });

    it('rejected notification uses mail and database channels', function () {
        Notification::fake();

        $profile = CoachProfile::factory()->pending()->create();

        $listener = new SendCoachRejectedNotification;
        $listener->handle(new CoachRejected($profile->id, 'Incomplete application'));

        Notification::assertSentTo(
            $profile->user,
            CoachRejectedNotification::class,
            function ($notification, $channels) {
                return $channels === ['mail', 'database'];
            },
        );
    });

    it('approved notification mail contains correct subject', function () {
        $profile = CoachProfile::factory()->approved()->create();

        $notification = new CoachApprovedNotification($profile->id);
        $mail = $notification->toMail($profile->user);

        expect($mail->subject)->toBe(__('notifications.coach_approved_subject'));
    });

    it('approved notification mail action url points to stripe onboarding', function () {
        $profile = CoachProfile::factory()->approved()->create();

        $notification = new CoachApprovedNotification($profile->id);
        $mail = $notification->toMail($profile->user);

        expect($mail->actionUrl)->toBe(route('coach.stripe.onboard'));
    });

    it('rejected notification mail contains rejection reason', function () {
        $profile = CoachProfile::factory()->pending()->create();
        $reason = 'Missing documentation';

        $notification = new CoachRejectedNotification($profile->id, $reason);
        $mail = $notification->toMail($profile->user);

        $bodyText = collect($mail->introLines)->implode(' ');
        expect($bodyText)->toContain($reason);
    });

    it('approved notification toArray contains coach_profile_id', function () {
        $profile = CoachProfile::factory()->approved()->create();

        $notification = new CoachApprovedNotification($profile->id);
        $data = $notification->toArray($profile->user);

        expect($data['type'])->toBe('coach_approved');
        expect($data['coach_profile_id'])->toBe($profile->id);
    });

    it('rejected notification toArray contains reason', function () {
        $profile = CoachProfile::factory()->pending()->create();
        $reason = 'Not eligible';

        $notification = new CoachRejectedNotification($profile->id, $reason);
        $data = $notification->toArray($profile->user);

        expect($data['type'])->toBe('coach_rejected');
        expect($data['reason'])->toBe($reason);
    });

});
