<?php

declare(strict_types=1);

use App\Events\CoachPayoutProcessed;
use App\Listeners\SendPayoutNotification;
use App\Models\Invoice;
use App\Notifications\PayoutProcessedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('Payout Notifications', function () {

    it('CoachPayoutProcessed event triggers SendPayoutNotification and sends to coach', function (): void {
        Notification::fake();

        $invoice = Invoice::factory()->issued()->create();

        $listener = new SendPayoutNotification;
        $listener->handle(new CoachPayoutProcessed($invoice->id));

        Notification::assertSentTo($invoice->coach, PayoutProcessedNotification::class);
    });

    it('PayoutProcessedNotification uses mail and database channels', function (): void {
        Notification::fake();

        $invoice = Invoice::factory()->issued()->create();

        $listener = new SendPayoutNotification;
        $listener->handle(new CoachPayoutProcessed($invoice->id));

        Notification::assertSentTo(
            $invoice->coach,
            PayoutProcessedNotification::class,
            function ($notification, $channels) {
                return $channels === ['mail', 'database'];
            },
        );
    });

    it('PayoutProcessedNotification mail contains correct subject', function (): void {
        $invoice = Invoice::factory()->issued()->create();

        $notification = new PayoutProcessedNotification($invoice->id);
        $mail = $notification->toMail($invoice->coach);

        expect($mail->subject)->toBe(__('notifications.payout_processed_subject'));
    });

    it('PayoutProcessedNotification mail contains payout amount', function (): void {
        $invoice = Invoice::factory()->issued()->create();

        $notification = new PayoutProcessedNotification($invoice->id);
        $mail = $notification->toMail($invoice->coach);

        $bodyText = collect($mail->introLines)->implode(' ');
        $expected = number_format($invoice->coach_payout / 100, 2, ',', '.');
        expect($bodyText)->toContain($expected);
    });

    it('PayoutProcessedNotification mail contains invoice number', function (): void {
        $invoice = Invoice::factory()->issued()->create();

        $notification = new PayoutProcessedNotification($invoice->id);
        $mail = $notification->toMail($invoice->coach);

        $bodyText = collect($mail->introLines)->implode(' ');
        expect($bodyText)->toContain($invoice->invoice_number);
    });

    it('PayoutProcessedNotification toArray contains invoice_id and type', function (): void {
        $invoice = Invoice::factory()->issued()->create();

        $notification = new PayoutProcessedNotification($invoice->id);
        $data = $notification->toArray($invoice->coach);

        expect($data['type'])->toBe('payout_processed');
        expect($data['invoice_id'])->toBe($invoice->id);
    });

    it('PayoutProcessedNotification implements ShouldQueue', function (): void {
        expect(PayoutProcessedNotification::class)
            ->toImplement(ShouldQueue::class);
    });

    it('PayoutProcessedNotification sends with coach locale and applies correct translation', function (): void {
        $invoice = Invoice::factory()->issued()->create();
        $invoice->coach->update(['locale' => 'en']);

        app()->setLocale('en');

        $notification = new PayoutProcessedNotification($invoice->id);
        $mail = $notification->toMail($invoice->coach);

        expect($mail->subject)->toBe(__('notifications.payout_processed_subject', [], 'en'));
    });

});
