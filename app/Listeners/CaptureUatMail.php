<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\UatMailCapture;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;

final class CaptureUatMail
{
    public function handle(MessageSending $event): void
    {
        if (! (bool) config('mail.uat_capture.enabled', false)) {
            return;
        }

        $message = $event->message;

        UatMailCapture::create([
            'run_id' => $this->stringValue(config('uat.current_run_id')),
            'to' => $this->addresses($message->getTo()),
            'cc' => $this->addresses($message->getCc()),
            'bcc' => $this->addresses($message->getBcc()),
            'subject' => $message->getSubject(),
            'html_body' => $message->getHtmlBody(),
            'text_body' => $message->getTextBody(),
            'headers' => $this->headers($message->getHeaders()->toArray()),
            'metadata' => [
                'mailer' => config('mail.default'),
                'environment' => app()->environment(),
            ],
            'captured_at' => now(),
        ]);
    }

    /**
     * @param  list<Address>  $addresses
     * @return list<array{email: string, name: string|null}>
     */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $address): array => [
            'email' => $address->getAddress(),
            'name' => $address->getName() !== '' ? $address->getName() : null,
        ], $addresses);
    }

    /**
     * @param  array<string, list<string>>  $headers
     * @return array<string, list<string>>
     */
    private function headers(array $headers): array
    {
        return $headers;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
