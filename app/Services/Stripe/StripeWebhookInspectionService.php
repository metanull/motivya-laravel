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
     * Required events that happen on coaches' connected accounts. Stripe only
     * sends these to a Connect endpoint (one with an application set); a
     * platform endpoint subscribed to them receives the platform's own events.
     *
     * @var list<string>
     */
    public const CONNECT_EVENTS = [
        'account.updated',
    ];

    /**
     * Return a list of required event types that are not enabled on any active
     * Stripe webhook endpoint of the right kind. Connect events are suffixed
     * with " (Connect)". An empty array means every required event is covered.
     *
     * @return list<string>
     *
     * @throws ApiErrorException
     */
    public function missingEvents(): array
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));

        return $this->missingEventsFrom(WebhookEndpoint::all(['limit' => 100])->data);
    }

    /**
     * @param  iterable<WebhookEndpoint>  $endpoints
     * @return list<string>
     */
    public function missingEventsFrom(iterable $endpoints): array
    {
        $enabled = ['platform' => [], 'connect' => []];

        foreach ($endpoints as $endpoint) {
            if (($endpoint->status ?? '') !== 'enabled') {
                continue;
            }

            $scope = empty($endpoint->application) ? 'platform' : 'connect';

            foreach ($endpoint->enabled_events as $eventType) {
                $enabled[$scope][] = $eventType;
            }
        }

        $missing = [];

        foreach (self::REQUIRED_EVENTS as $event) {
            $isConnect = in_array($event, self::CONNECT_EVENTS, true);
            $events = $enabled[$isConnect ? 'connect' : 'platform'];

            if (! in_array('*', $events, true) && ! in_array($event, $events, true)) {
                $missing[] = $isConnect ? $event.' (Connect)' : $event;
            }
        }

        return $missing;
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
