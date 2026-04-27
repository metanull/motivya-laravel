<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PayoutProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $invoiceId,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = Invoice::findOrFail($this->invoiceId);

        $amount = number_format($invoice->coach_payout / 100, 2, ',', '.');

        return (new MailMessage)
            ->subject(__('notifications.payout_processed_subject'))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.payout_processed_body', ['amount' => '€ '.$amount]))
            ->line(__('notifications.payout_processed_invoice', ['invoice_number' => $invoice->invoice_number]))
            ->action(__('notifications.view_dashboard'), route('coach.payout-history'))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_processed',
            'invoice_id' => $this->invoiceId,
        ];
    }
}
