<?php

declare(strict_types=1);

use App\Enums\CoachProfileStatus;
use App\Enums\UserRole;
use App\Events\CoachApproved;
use App\Events\CoachRejected;
use App\Livewire\Admin\CoachApproval;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin Coach Approval', function () {

    it('renders the page for admins', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.coach-approval'))
            ->assertOk();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('admin.coach-approval'))
            ->assertForbidden();
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.coach-approval'))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $this->get(route('admin.coach-approval'))
            ->assertRedirect(route('login'));
    });

    it('lists pending coach applications', function () {
        $admin = User::factory()->admin()->create();
        $pendingProfile = CoachProfile::factory()->pending()->create();

        Livewire::actingAs($admin)
            ->test(CoachApproval::class)
            ->assertSee($pendingProfile->user->name)
            ->assertSee($pendingProfile->user->email);
    });

    it('does not list approved applications', function () {
        $admin = User::factory()->admin()->create();
        $approvedProfile = CoachProfile::factory()->approved()->create();

        Livewire::actingAs($admin)
            ->test(CoachApproval::class)
            ->assertDontSee($approvedProfile->user->email);
    });

    it('shows empty state when no pending applications', function () {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CoachApproval::class)
            ->assertSee(__('admin.no_pending_applications'));
    });

    it('approves a coach application', function () {
        Event::fake([CoachApproved::class]);

        $admin = User::factory()->admin()->create();
        $profile = CoachProfile::factory()->pending()->create();

        Livewire::actingAs($admin)
            ->test(CoachApproval::class)
            ->call('approve', $profile->id);

        $profile->refresh();
        expect($profile->status)->toBe(CoachProfileStatus::Approved);
        expect($profile->verified_at)->not->toBeNull();
        expect($profile->user->fresh()->role)->toBe(UserRole::Coach);

        Event::assertDispatched(CoachApproved::class, function ($event) use ($profile) {
            return $event->coachProfileId === $profile->id;
        });
    });

    it('rejects a coach application with reason', function () {
        Event::fake([CoachRejected::class]);

        $admin = User::factory()->admin()->create();
        $applicant = User::factory()->athlete()->create();
        $profile = CoachProfile::factory()->pending()->create(['user_id' => $applicant->id]);

        Livewire::actingAs($admin)
            ->test(CoachApproval::class)
            ->call('confirmReject', $profile->id)
            ->assertSet('rejectingProfileId', $profile->id)
            ->set('rejectionReason', 'Missing qualifications')
            ->call('reject');

        $profile->refresh();
        expect($profile->status)->toBe(CoachProfileStatus::Rejected);
        expect($profile->user->fresh()->role)->toBe(UserRole::Athlete);

        Event::assertDispatched(CoachRejected::class, function ($event) use ($profile) {
            return $event->coachProfileId === $profile->id
                && $event->reason === 'Missing qualifications';
        });
    });

    it('requires rejection reason', function () {
        $admin = User::factory()->admin()->create();
        $profile = CoachProfile::factory()->pending()->create();

        Livewire::actingAs($admin)
            ->test(CoachApproval::class)
            ->call('confirmReject', $profile->id)
            ->set('rejectionReason', '')
            ->call('reject')
            ->assertHasErrors(['rejectionReason']);
    });

    it('can cancel rejection', function () {
        $admin = User::factory()->admin()->create();
        $profile = CoachProfile::factory()->pending()->create();

        Livewire::actingAs($admin)
            ->test(CoachApproval::class)
            ->call('confirmReject', $profile->id)
            ->assertSet('rejectingProfileId', $profile->id)
            ->call('cancelReject')
            ->assertSet('rejectingProfileId', null)
            ->assertSet('rejectionReason', '');
    });

});
