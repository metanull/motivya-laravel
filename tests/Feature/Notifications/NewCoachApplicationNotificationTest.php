<?php

declare(strict_types=1);

use App\Events\NewCoachApplication;
use App\Listeners\NotifyAdminsOfNewApplication;
use App\Livewire\Coach\Application;
use App\Models\CoachProfile;
use App\Models\User;
use App\Notifications\NewCoachApplicationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('New Coach Application Admin Notification', function () {

    it('sends notification to all admin users when listener handles event', function () {
        Notification::fake();

        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        User::factory()->athlete()->create(); // should not receive
        User::factory()->coach()->create();   // should not receive

        $profile = CoachProfile::factory()->pending()->create();

        $listener = new NotifyAdminsOfNewApplication;
        $listener->handle(new NewCoachApplication($profile->id));

        Notification::assertSentTo($admin1, NewCoachApplicationNotification::class);
        Notification::assertSentTo($admin2, NewCoachApplicationNotification::class);
        Notification::assertCount(2);
    });

    it('does not send notification when no admin users exist', function () {
        Notification::fake();

        User::factory()->athlete()->create();

        $profile = CoachProfile::factory()->pending()->create();

        $listener = new NotifyAdminsOfNewApplication;
        $listener->handle(new NewCoachApplication($profile->id));

        Notification::assertNothingSent();
    });

    it('notification uses mail and database channels', function () {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $profile = CoachProfile::factory()->pending()->create();

        $listener = new NotifyAdminsOfNewApplication;
        $listener->handle(new NewCoachApplication($profile->id));

        Notification::assertSentTo(
            $admin,
            NewCoachApplicationNotification::class,
            function ($notification, $channels) {
                return $channels === ['mail', 'database'];
            },
        );
    });

    it('notification mail contains applicant name and email', function () {
        $profile = CoachProfile::factory()->pending()->create();
        $admin = User::factory()->admin()->create();

        $notification = new NewCoachApplicationNotification($profile->id);
        $mail = $notification->toMail($admin);

        expect($mail->subject)->toBe(__('notifications.new_coach_application_subject'));

        $bodyText = collect($mail->introLines)->implode(' ');
        expect($bodyText)->toContain($profile->user->name);
        expect($bodyText)->toContain($profile->user->email);
    });

    it('notification toArray contains coach_profile_id', function () {
        $profile = CoachProfile::factory()->pending()->create();
        $admin = User::factory()->admin()->create();

        $notification = new NewCoachApplicationNotification($profile->id);
        $data = $notification->toArray($admin);

        expect($data['type'])->toBe('new_coach_application');
        expect($data['coach_profile_id'])->toBe($profile->id);
    });

    it('submitting coach application dispatches NewCoachApplication event', function () {
        Event::fake([NewCoachApplication::class]);

        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Application::class)
            ->set('form.specialties', ['fitness'])
            ->set('form.bio', 'Test bio')
            ->set('form.experience_level', 'beginner')
            ->call('nextStep')
            ->set('form.postal_code', '1000')
            ->set('form.country', 'BE')
            ->set('form.enterprise_number', '0123.456.789')
            ->call('nextStep')
            ->set('form.terms_accepted', true)
            ->call('submit')
            ->assertRedirect(route('home'));

        Event::assertDispatched(NewCoachApplication::class);
    });

});
