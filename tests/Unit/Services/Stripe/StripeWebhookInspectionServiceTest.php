<?php

declare(strict_types=1);

use App\Services\Stripe\StripeWebhookInspectionService;
use Stripe\WebhookEndpoint;

/**
 * @param  list<string>  $events
 */
function webhookEndpoint(array $events, ?string $application = null, string $status = 'enabled'): WebhookEndpoint
{
    return WebhookEndpoint::constructFrom([
        'id' => 'we_'.bin2hex(random_bytes(4)),
        'object' => 'webhook_endpoint',
        'application' => $application,
        'enabled_events' => $events,
        'status' => $status,
    ]);
}

/**
 * @return list<string>
 */
function platformWebhookEvents(): array
{
    return [
        'checkout.session.completed',
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'charge.refunded',
        'transfer.created',
    ];
}

describe('StripeWebhookInspectionService::missingEventsFrom', function () {

    beforeEach(function () {
        $this->service = new StripeWebhookInspectionService;
    });

    it('reports nothing missing with a platform and a Connect endpoint', function () {
        $missing = $this->service->missingEventsFrom([
            webhookEndpoint(platformWebhookEvents()),
            webhookEndpoint(['account.updated'], application: 'ca_test_123'),
        ]);

        expect($missing)->toBe([]);
    });

    it('does not count account.updated on a platform endpoint', function () {
        $missing = $this->service->missingEventsFrom([
            webhookEndpoint([...platformWebhookEvents(), 'account.updated']),
        ]);

        expect($missing)->toBe(['account.updated (Connect)']);
    });

    it('does not count platform events on a Connect endpoint', function () {
        $missing = $this->service->missingEventsFrom([
            webhookEndpoint([...platformWebhookEvents(), 'account.updated'], application: 'ca_test_123'),
        ]);

        expect($missing)->toBe(platformWebhookEvents());
    });

    it('treats a wildcard as covering only its own kind of endpoint', function () {
        $missing = $this->service->missingEventsFrom([
            webhookEndpoint(['*']),
        ]);

        expect($missing)->toBe(['account.updated (Connect)']);
    });

    it('ignores disabled endpoints', function () {
        $missing = $this->service->missingEventsFrom([
            webhookEndpoint(platformWebhookEvents()),
            webhookEndpoint(['account.updated'], application: 'ca_test_123', status: 'disabled'),
        ]);

        expect($missing)->toBe(['account.updated (Connect)']);
    });
});
