<?php

declare(strict_types=1);

use App\Enums\CoachPayoutStatementStatus;
use App\Livewire\Coach\PayoutStatements\Index;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('access control', function () {
    it('renders for a coach', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.payout-statements.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('coach.payout-statements.index'))
            ->assertForbidden();
    });

    it('denies access to accountants', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('coach.payout-statements.index'))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $this->get(route('coach.payout-statements.index'))
            ->assertRedirect(route('login'));
    });
});

describe('statements list', function () {
    it('shows payout statements for the logged-in coach only', function () {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();

        CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach->id, 'period_year' => 2025, 'period_month' => 1]);
        CoachPayoutStatement::factory()->paid()->create(['coach_id' => $otherCoach->id, 'period_year' => 2025, 'period_month' => 2]);

        Livewire::actingAs($coach)
            ->test(Index::class)
            ->assertSee('2025-01')
            ->assertDontSee('2025-02');
    });
});

describe('requestPayout', function () {
    it('transitions a draft statement to ready_for_invoice', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $statement = CoachPayoutStatement::factory()->draft()->create([
            'coach_id' => $coach->id,
            'period_year' => 2025,
            'period_month' => 5,
        ]);

        Livewire::actingAs($coach)
            ->test(Index::class)
            ->call('requestPayout', $statement->id);

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::ReadyForInvoice);
    });

    it('does not allow a coach to request payout on another coach\'s statement', function () {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();

        $statement = CoachPayoutStatement::factory()->draft()->create(['coach_id' => $otherCoach->id]);

        // The component filters by coach_id so it throws ModelNotFoundException
        expect(function () use ($coach, $statement) {
            Livewire::actingAs($coach)
                ->test(Index::class)
                ->call('requestPayout', $statement->id);
        })->toThrow(ModelNotFoundException::class);
    });
});

describe('markInvoiceSubmitted', function () {
    it('transitions a ready_for_invoice statement to invoice_submitted', function () {
        $coach = User::factory()->coach()->create();

        $statement = CoachPayoutStatement::factory()->readyForInvoice()->create([
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(Index::class)
            ->call('markInvoiceSubmitted', $statement->id);

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::InvoiceSubmitted);
    });
});
