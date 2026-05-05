<?php

declare(strict_types=1);

use App\Enums\PaymentAnomalyType;
use App\Livewire\Admin\Anomalies\Index;
use App\Models\PaymentAnomaly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('access control', function () {
    it('renders for an admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.anomalies.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('redirects admin without 2FA', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.anomalies.index'))
            ->assertRedirect(route('profile.edit'));
    });

    it('denies access to accountants', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('admin.anomalies.index'))
            ->assertForbidden();
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.anomalies.index'))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('admin.anomalies.index'))
            ->assertForbidden();
    });
});

describe('anomaly list', function () {
    it('shows open anomalies', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        PaymentAnomaly::factory()->open()->create([
            'description' => 'Admin visible anomaly',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('Admin visible anomaly');
    });

    it('does not show resolved anomalies', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        PaymentAnomaly::factory()->resolved()->create([
            'description' => 'Already handled anomaly',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertDontSee('Already handled anomaly');
    });

    it('filters by type', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        PaymentAnomaly::factory()->open()->ofType(PaymentAnomalyType::CoachStripeIncomplete)->create([
            'description' => 'Stripe incomplete',
        ]);
        PaymentAnomaly::factory()->open()->ofType(PaymentAnomalyType::InvoiceTotalMismatch)->create([
            'description' => 'Invoice mismatch',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('filterType', 'coach_stripe_incomplete')
            ->assertSee('Stripe incomplete')
            ->assertDontSee('Invoice mismatch');
    });
});

describe('resolve', function () {
    it('resolves an open anomaly', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openResolveModal', $anomaly->id)
            ->set('resolveReason', 'Investigated and closed.')
            ->call('resolve', $anomaly->id);

        expect($anomaly->fresh()->resolution_status)->toBe('resolved')
            ->and($anomaly->fresh()->resolved_by)->toBe($admin->id);
    });
});

describe('ignore', function () {
    it('ignores an open anomaly', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openIgnoreModal', $anomaly->id)
            ->set('ignoreReason', 'Test environment artifact.')
            ->call('ignore', $anomaly->id);

        expect($anomaly->fresh()->resolution_status)->toBe('ignored')
            ->and($anomaly->fresh()->resolved_by)->toBe($admin->id);
    });

    it('requires an ignore reason', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openIgnoreModal', $anomaly->id)
            ->set('ignoreReason', '')
            ->call('ignore', $anomaly->id)
            ->assertHasErrors(['ignoreReason']);
    });
});
