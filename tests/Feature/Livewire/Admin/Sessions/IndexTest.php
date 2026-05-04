<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Livewire\Admin\Sessions\Index;
use App\Models\AuditEvent;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin — Session Supervision', function () {

    beforeEach(function () {
        Storage::fake();
    });

    // ── Access control ────────────────────────────────────────────────────

    it('renders for admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertOk()
            ->assertSee(__('admin.sessions_heading'));
    });

    it('is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertForbidden();
    });

    it('is forbidden for coaches', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Index::class)
            ->assertForbidden();
    });

    it('is accessible via route admin.sessions.index', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.sessions.index'))
            ->assertOk();
    });

    // ── Filter tests ──────────────────────────────────────────────────────

    it('filters sessions by status', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        SportSession::factory()->published()->for($coach, 'coach')->create(['title' => 'Published Session']);
        SportSession::factory()->confirmed()->for($coach, 'coach')->create(['title' => 'Confirmed Session']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('status', SessionStatus::Published->value)
            ->assertSee('Published Session')
            ->assertDontSee('Confirmed Session');
    });

    it('filters sessions by coach name', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coachAlpha = User::factory()->coach()->create(['name' => 'Coach Alpha']);
        $coachBeta = User::factory()->coach()->create(['name' => 'Coach Beta']);

        SportSession::factory()->published()->for($coachAlpha, 'coach')->create(['title' => 'Alpha Session']);
        SportSession::factory()->published()->for($coachBeta, 'coach')->create(['title' => 'Beta Session']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('coachSearch', 'Alpha')
            ->assertSee('Alpha Session')
            ->assertDontSee('Beta Session');
    });

    // ── Cancel action ─────────────────────────────────────────────────────

    it('admin can cancel a published session with reason', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->for($coach, 'coach')->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmCancel', $session->id)
            ->set('cancelReason', 'Admin override: policy violation')
            ->call('cancelSession', $session->id)
            ->assertDispatched('notify');

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Cancelled);

        expect(
            AuditEvent::where('model_type', 'App\Models\SportSession')
                ->where('model_id', $session->id)
                ->exists(),
        )->toBeTrue();
    });

    it('admin can cancel a confirmed session with reason', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmCancel', $session->id)
            ->set('cancelReason', 'Admin override: duplicate session')
            ->call('cancelSession', $session->id)
            ->assertDispatched('notify');

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Cancelled);
    });

    it('cancel action requires a reason', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->for($coach, 'coach')->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmCancel', $session->id)
            ->set('cancelReason', '')
            ->call('cancelSession', $session->id)
            ->assertHasErrors(['cancelReason']);

        // Session status must be unchanged
        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Published);
    });

    // ── Complete action ───────────────────────────────────────────────────

    it('admin can complete a past confirmed session', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('completeSession', $session->id)
            ->assertDispatched('notify');

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Completed);

        expect(
            AuditEvent::where('model_type', 'App\Models\SportSession')
                ->where('model_id', $session->id)
                ->exists(),
        )->toBeTrue();
    });

    it('complete action fails for a future confirmed session', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('completeSession', $session->id)
            ->assertDispatched('notify');

        // Status must NOT have changed to Completed
        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Confirmed);
    });

    // ── Audit logging ─────────────────────────────────────────────────────

    it('records an audit event on cancel', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->for($coach, 'coach')->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmCancel', $session->id)
            ->set('cancelReason', 'Audit test reason')
            ->call('cancelSession', $session->id);

        expect(
            AuditEvent::where('model_type', 'App\Models\SportSession')
                ->where('model_id', $session->id)
                ->where('event_type', 'session.cancelled')
                ->exists(),
        )->toBeTrue();
    });

    it('records an audit event on complete', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('completeSession', $session->id);

        expect(
            AuditEvent::where('model_type', 'App\Models\SportSession')
                ->where('model_id', $session->id)
                ->where('event_type', 'session.completed')
                ->exists(),
        )->toBeTrue();
    });

});
