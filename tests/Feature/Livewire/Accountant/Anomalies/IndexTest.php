<?php

declare(strict_types=1);

use App\Enums\PaymentAnomalyType;
use App\Livewire\Accountant\Anomalies\Index;
use App\Models\PaymentAnomaly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('access control', function () {
    it('renders for an accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.anomalies.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('renders for an admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('accountant.anomalies.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('redirects accountant without 2FA', function () {
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.anomalies.index'))
            ->assertRedirect(route('profile.edit'));
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('accountant.anomalies.index'))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('accountant.anomalies.index'))
            ->assertForbidden();
    });
});

describe('anomaly list', function () {
    it('shows open anomalies', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create([
            'description' => 'Test anomaly description',
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSee('Test anomaly description');
    });

    it('does not show resolved anomalies', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->resolved()->create([
            'description' => 'Already resolved anomaly',
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertDontSee('Already resolved anomaly');
    });

    it('filters by type', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        PaymentAnomaly::factory()->open()->ofType(PaymentAnomalyType::ConfirmedBookingMissingPayment)->create([
            'description' => 'Missing payment anomaly',
        ]);
        PaymentAnomaly::factory()->open()->ofType(PaymentAnomalyType::InvoiceTotalMismatch)->create([
            'description' => 'Mismatch anomaly',
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->set('filterType', 'confirmed_booking_missing_payment')
            ->assertSee('Missing payment anomaly')
            ->assertDontSee('Mismatch anomaly');
    });
});

describe('resolve', function () {
    it('resolves an open anomaly', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('openResolveModal', $anomaly->id)
            ->set('resolveReason', 'Verified in Stripe dashboard.')
            ->call('resolve', $anomaly->id);

        expect($anomaly->fresh()->resolution_status)->toBe('resolved');
    });

    it('requires a resolve reason', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('openResolveModal', $anomaly->id)
            ->set('resolveReason', '')
            ->call('resolve', $anomaly->id)
            ->assertHasErrors(['resolveReason']);
    });
});

describe('ignore', function () {
    it('ignores an open anomaly', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('openIgnoreModal', $anomaly->id)
            ->set('ignoreReason', 'Known false positive for test accounts.')
            ->call('ignore', $anomaly->id);

        expect($anomaly->fresh()->resolution_status)->toBe('ignored');
    });

    it('requires an ignore reason', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $anomaly = PaymentAnomaly::factory()->open()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->call('openIgnoreModal', $anomaly->id)
            ->set('ignoreReason', '')
            ->call('ignore', $anomaly->id)
            ->assertHasErrors(['ignoreReason']);
    });
});
