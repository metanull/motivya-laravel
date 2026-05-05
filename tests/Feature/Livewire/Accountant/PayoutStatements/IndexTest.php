<?php

declare(strict_types=1);

use App\Enums\CoachPayoutStatementStatus;
use App\Livewire\Accountant\PayoutStatements\Index;
use App\Models\CoachPayoutStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('access control', function () {
    it('renders for an accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.payout-statements.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('renders for an admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('accountant.payout-statements.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('redirects accountant without 2FA', function () {
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.payout-statements.index'))
            ->assertRedirect(route('profile.edit'));
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('accountant.payout-statements.index'))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('accountant.payout-statements.index'))
            ->assertForbidden();
    });
});

describe('statements list', function () {
    it('shows all coaches\' statements', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach1 = User::factory()->coach()->create();
        $coach2 = User::factory()->coach()->create();

        CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach1->id, 'period_year' => 2025, 'period_month' => 3]);
        CoachPayoutStatement::factory()->paid()->create(['coach_id' => $coach2->id, 'period_year' => 2025, 'period_month' => 4]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSee('2025-03')
            ->assertSee('2025-04');
    });

    it('filters by status', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach->id, 'period_year' => 2025, 'period_month' => 1]);
        CoachPayoutStatement::factory()->approved()->create(['coach_id' => $coach->id, 'period_year' => 2025, 'period_month' => 2]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('filterStatus', 'approved')
            ->assertSee('2025-02')
            ->assertDontSee('2025-01');
    });

    it('filters by coach', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach1 = User::factory()->coach()->create();
        $coach2 = User::factory()->coach()->create();

        CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach1->id, 'period_year' => 2025, 'period_month' => 5]);
        CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach2->id, 'period_year' => 2025, 'period_month' => 6]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('filterCoach', (string) $coach1->id)
            ->assertSee('2025-05')
            ->assertDontSee('2025-06');
    });

    it('resets filters', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('filterStatus', 'approved')
            ->set('filterCoach', '99')
            ->call('resetFilters')
            ->assertSet('filterStatus', '')
            ->assertSet('filterCoach', '');
    });
});

describe('approve', function () {
    it('approves an invoice_submitted statement', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $statement = CoachPayoutStatement::factory()->invoiceSubmitted()->create(['coach_id' => $coach->id]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('approve', $statement->id);

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::Approved)
            ->and($statement->fresh()->approved_by)->toBe($accountant->id);
    });
});

describe('block', function () {
    it('blocks a statement with a reason', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $statement = CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach->id]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('openBlockModal', $statement->id)
            ->set('blockReason', 'Fraudulent activity detected')
            ->call('block', $statement->id);

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::Blocked);
    });

    it('requires a block reason', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $statement = CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach->id]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('openBlockModal', $statement->id)
            ->set('blockReason', '')
            ->call('block', $statement->id)
            ->assertHasErrors(['blockReason']);
    });
});

describe('markPaid', function () {
    it('marks an approved statement as paid', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $statement = CoachPayoutStatement::factory()->approved()->create(['coach_id' => $coach->id]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('markPaid', $statement->id);

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::Paid)
            ->and($statement->fresh()->paid_at)->not->toBeNull();
    });
});
