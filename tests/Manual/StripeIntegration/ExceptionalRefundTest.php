<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\InvoiceType;
use App\Livewire\Admin\Refunds\Index as AdminRefundsIndex;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Livewire\Livewire;

require_once __DIR__.'/Support.php';

describe('Stripe manual exceptional reimbursement integration', function () {
    it('creates a real Stripe test-mode refund and records refunded booking state', function (): void {
        $stripe = requireLiveStripeIntegration();
        $qaRunId = manualStripeQaRunId('exceptional_refund');
        $connectedAccountId = manualStripeConnectedAccountId($qaRunId);

        $booking = manualConfirmedPaidBooking($qaRunId, $connectedAccountId, 2800);
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $originalInvoice = manualOriginalInvoice($qaRunId, $booking);

        Livewire::actingAs($admin)
            ->test(AdminRefundsIndex::class)
            ->set('refundReason', "Manual Stripe QA refund {$qaRunId}")
            ->call('processRefund', $booking->id)
            ->assertDispatched('notify');

        expect($booking->fresh()->status)->toBe(BookingStatus::Refunded)
            ->and($booking->fresh()->refunded_at)->not->toBeNull();

        postManualStripeWebhook(
            $stripe['webhook_secret'],
            "evt_{$qaRunId}_charge_refunded",
            'charge.refunded',
            [
                'payment_intent' => $booking->stripe_payment_intent_id,
                'metadata' => ['qa_run_id' => $qaRunId],
            ],
        )->assertOk()->assertJson(['status' => 'processed']);

        expect($booking->fresh()->status)->toBe(BookingStatus::Refunded);

        config(['filesystems.disks.local.root' => sys_get_temp_dir()."/motivya-stripe-qa-storage/{$qaRunId}"]);

        $creditNote = app(InvoiceService::class)->generateCreditNote($booking->fresh(), $originalInvoice);

        expect($creditNote->type)->toBe(InvoiceType::CreditNote)
            ->and($creditNote->related_invoice_id)->toBe($originalInvoice->id)
            ->and($creditNote->revenue_ttc)->toBe(2800)
            ->and($creditNote->xml_path)->not->toBeNull();

        expect(Invoice::query()->where('related_invoice_id', $originalInvoice->id)->where('type', InvoiceType::CreditNote)->exists())->toBeTrue();

        test()->actingAs($admin)->get(route('admin.refunds.index'))->assertOk();
    });
});
