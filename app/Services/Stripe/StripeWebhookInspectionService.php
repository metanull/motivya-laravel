<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\WebhookEndpoint;

class StripeWebhookInspectionService
{
    /**
     * The set of Stripe event types that the application requires in order to
     * run the MVP finance and booking lifecycle correctly.
     *
     * @var list<string>
     */
    public const REQUIRED_EVENTS = [
        'checkout.session.completed',
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'charge.refunded',
        'account.updated',
        'transfer.created',
    ];

    /**
     * Return a list of required event types that are not enabled on any active
     * Stripe webhook endpoint. An empty array means every required event is
     * covered.
     *
     * @return list<string>
     *
     * @throws ApiErrorException
     */
    public function missingEvents(): array
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));

        $endpoints = WebhookEndpoint::all(['limit' => 100]);

        $enabledEvents = [];

        foreach ($endpoints->data as $endpoint) {
            if (($endpoint->status ?? '') !== 'enabled') {
                continue;
            }

            foreach ($endpoint->enabled_events as $eventType) {
                if ($eventType === '*') {
                    // Wildcard — all events are covered.
                    return [];
                }

                $enabledEvents[] = $eventType;
            }
        }

        $enabledEvents = array_unique($enabledEvents);

        return array_values(
            array_filter(
                self::REQUIRED_EVENTS,
                fn (string $event): bool => ! in_array($event, $enabledEvents, true),
            ),
        );
    }

    /**
     * Return true when every required event is covered by at least one enabled
     * Stripe webhook endpoint.
     *
     * @throws ApiErrorException
     */
    public function allRequiredEventsEnabled(): bool
    {
        return $this->missingEvents() === [];
    }
}
