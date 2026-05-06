<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\RefundAuditStatus;
use App\Livewire\Admin\Refunds\Index;
use App\Models\AdminRefundAudit;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Stripe\Refund;

uses(RefreshDatabase::class);

/**
 * Helper: create a confirmed booking with a Stripe payment intent so that
 * RefundService can proceed without throwing "no payment intent" errors.
 */
function makeConfirmedPaidBooking(): Booking
{
    $coach = User::factory()->coach()->create();
    $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();

    return Booking::factory()
        ->confirmed()
        ->for($session, 'sportSession')
        ->create([
            'amount_paid' => 2500,
            'stripe_payment_intent_id' => 'pi_test_'.now()->timestamp,
        ]);
}

/**
 * Bind a mock RefundService that simulates a successful Stripe refund.
 */
function bindSuccessfulRefundService(): void
{
    app()->instance(RefundService::class, new RefundService(
        auditService: app(AuditService::class),
        createRefundUsing: function (array $payload): Refund {
            return Refund::constructFrom(['id' => 're_test_'.now()->timestamp]);
        },
    ));
}

/**
 * Bind a mock RefundService that always throws.
 */
function bindFailingRefundService(): void
{
    app()->instance(RefundService::class, new RefundService(
        auditService: app(AuditService::class),
        createRefundUsing: function (array $payload): never {
            throw new RuntimeException('Stripe refund failed (test).');
        },
    ));
}

describe('Admin — Exceptional Refund Queue', function () {

    // ── Access control ────────────────────────────────────────────────────

    it('renders for admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertOk()
            ->assertSee(__('admin.refunds_heading'));
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

    it('is accessible via route admin.refunds.index', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.refunds.index'))
            ->assertOk();
    });

    // ── Booking visibility ────────────────────────────────────────────────

    it('shows confirmed bookings', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('#'.$booking->id);
    });

    it('shows refunded bookings when filtered by refunded status', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()
            ->refunded()
            ->for($session, 'sportSession')
            ->create(['amount_paid' => 1000]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('statusFilter', 'refunded')
            ->assertSee('#'.$booking->id);
    });

    // ── Successful refund ─────────────────────────────────────────────────

    it('admin can refund a confirmed paid booking', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();
        bindSuccessfulRefundService();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Customer requested exceptional refund.')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify');

        $booking->refresh();
        expect($booking->status)->toBe(BookingStatus::Refunded);
    });

    // ── Validation ────────────────────────────────────────────────────────

    it('refund requires a reason', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', '')
            ->call('processRefund', $booking->id)
            ->assertHasErrors(['refundReason']);

        // Booking status must be unchanged
        $booking->refresh();
        expect($booking->status)->toBe(BookingStatus::Confirmed);
    });

    // ── Ineligible bookings ───────────────────────────────────────────────

    it('refund is not available for pending payment bookings', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()
            ->pendingPayment()
            ->for($session, 'sportSession')
            ->create(['amount_paid' => 0]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Test reason')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify');

        // Status must be unchanged
        $booking->refresh();
        expect($booking->status)->toBe(BookingStatus::PendingPayment);

        // Audit row must record the failure
        $audit = AdminRefundAudit::where('booking_id', $booking->id)->first();
        expect($audit)->not->toBeNull();
        expect($audit->status)->toBe(RefundAuditStatus::Failed);
    });

    it('refund is not available for already refunded bookings', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()
            ->refunded()
            ->for($session, 'sportSession')
            ->create([
                'amount_paid' => 2500,
                'stripe_payment_intent_id' => 'pi_already_refunded',
            ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Trying to double-refund')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify');

        // Status must remain Refunded — no double-refund
        $booking->refresh();
        expect($booking->status)->toBe(BookingStatus::Refunded);

        // Audit must show failed
        $audit = AdminRefundAudit::where('booking_id', $booking->id)->first();
        expect($audit)->not->toBeNull();
        expect($audit->status)->toBe(RefundAuditStatus::Failed);
    });

    // ── Audit trail ───────────────────────────────────────────────────────

    it('creates admin_refund_audit record on refund attempt', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();
        bindSuccessfulRefundService();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Audit trail test.')
            ->call('processRefund', $booking->id);

        expect(
            AdminRefundAudit::where('booking_id', $booking->id)->exists(),
        )->toBeTrue();
    });

    it('audit record status is succeeded on successful refund', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();
        bindSuccessfulRefundService();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Successful refund audit check.')
            ->call('processRefund', $booking->id);

        $audit = AdminRefundAudit::where('booking_id', $booking->id)->first();
        expect($audit)->not->toBeNull();
        expect($audit->status)->toBe(RefundAuditStatus::Succeeded);
        expect($audit->admin_id)->toBe($admin->id);
    });

    it('audit record status is failed on failed refund', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();
        bindFailingRefundService();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Failed refund audit check.')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify');

        $audit = AdminRefundAudit::where('booking_id', $booking->id)->first();
        expect($audit)->not->toBeNull();
        expect($audit->status)->toBe(RefundAuditStatus::Failed);
        expect($audit->error_message)->not->toBeNull();

        // Booking must NOT be marked as refunded
        $booking->refresh();
        expect($booking->status)->toBe(BookingStatus::Confirmed);
    });

    // Story 1.4: per-case error messages for each ineligible scenario.

    it('dispatches the already-refunded message when booking is refunded', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()
            ->refunded()
            ->for($session, 'sportSession')
            ->create(['amount_paid' => 2000, 'stripe_payment_intent_id' => 'pi_already']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Double refund attempt')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify', message: __('admin.refunds_error_already_refunded'));
    });

    it('dispatches the not-confirmed message when booking is still pending', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()
            ->pendingPayment()
            ->for($session, 'sportSession')
            ->create(['amount_paid' => 0]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Wrong status attempt')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify', message: __('admin.refunds_error_not_confirmed'));
    });

    it('dispatches the no-amount message when amount_paid is zero', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()
            ->confirmed()
            ->for($session, 'sportSession')
            ->create(['amount_paid' => 0, 'stripe_payment_intent_id' => null]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Zero amount attempt')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify', message: __('admin.refunds_error_no_amount'));
    });

    it('dispatches the missing-payment-intent message when stripe_payment_intent_id is null', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()
            ->confirmed()
            ->for($session, 'sportSession')
            ->create(['amount_paid' => 3000, 'stripe_payment_intent_id' => null]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Reconciliation needed')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify', message: __('admin.refunds_error_missing_payment_intent'));
    });

    it('dispatches the stripe-failure message when Stripe rejects the refund', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();
        bindFailingRefundService();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Stripe failure test')
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify', message: __('admin.refunds_error_stripe_failure'));
    });

    // Story 4.3: Eligibility badge and last audit display.

    it('shows eligible badge for confirmed paid booking with payment intent', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee(__('admin.refunds_badge_eligible'));
    });

    it('shows already_refunded badge for refunded booking', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        Booking::factory()->refunded()->for($session, 'sportSession')
            ->create(['amount_paid' => 2000, 'stripe_payment_intent_id' => 'pi_test']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('statusFilter', 'refunded')
            ->assertSee(__('admin.refunds_badge_already_refunded'));
    });

    it('shows missing_payment_intent badge for confirmed paid booking without payment intent', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        Booking::factory()->confirmed()->for($session, 'sportSession')
            ->create(['amount_paid' => 3000, 'stripe_payment_intent_id' => null]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee(__('admin.refunds_badge_missing_payment_intent'));
    });

    it('shows no_attempts message when booking has no refund audit', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        makeConfirmedPaidBooking();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee(__('admin.refunds_no_attempts'));
    });

    it('shows last audit status after a refund attempt', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $booking = makeConfirmedPaidBooking();
        bindSuccessfulRefundService();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmRefund', $booking->id)
            ->set('refundReason', 'Last audit display test.')
            ->call('processRefund', $booking->id);

        // After refund, show the booking with status filter to see the succeeded audit
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('statusFilter', 'refunded')
            ->assertSee(__('admin.refund_audit_status_succeeded'));
    });

    it('eligibilityBadge returns correct value for each booking state', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();

        // Eligible
        $eligible = Booking::factory()->confirmed()->for($session, 'sportSession')
            ->create(['amount_paid' => 2500, 'stripe_payment_intent_id' => 'pi_ok']);
        // Already refunded
        $refunded = Booking::factory()->refunded()->for($session, 'sportSession')
            ->create(['amount_paid' => 2500, 'stripe_payment_intent_id' => 'pi_ref']);
        // Missing payment intent
        $noPi = Booking::factory()->confirmed()->for($session, 'sportSession')
            ->create(['amount_paid' => 2500, 'stripe_payment_intent_id' => null]);
        // Unpaid
        $unpaid = Booking::factory()->confirmed()->for($session, 'sportSession')
            ->create(['amount_paid' => 0, 'stripe_payment_intent_id' => null]);

        $component = Livewire::actingAs($admin)->test(Index::class)->instance();

        expect($component->eligibilityBadge($eligible, []))->toBe('eligible')
            ->and($component->eligibilityBadge($refunded, []))->toBe('already_refunded')
            ->and($component->eligibilityBadge($noPi, ['missing_payment_intent' => true]))->toBe('missing_payment_intent')
            ->and($component->eligibilityBadge($unpaid, []))->toBe('unpaid');
    });

});
