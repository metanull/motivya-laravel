<?php

declare(strict_types=1);

use App\Livewire\Admin\Readiness;
use App\Models\User;
use App\Services\Stripe\StripeWebhookInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Bind a fake StripeWebhookInspectionService that returns the given missing events.
 *
 * @param  list<string>  $missingEvents
 */
function fakeWebhookInspection(array $missingEvents): void
{
    app()->instance(
        StripeWebhookInspectionService::class,
        new class($missingEvents) extends StripeWebhookInspectionService
        {
            public function __construct(private readonly array $events) {}

            public function missingEvents(): array
            {
                return $this->events;
            }
        },
    );
}

/**
 * Bind a fake StripeWebhookInspectionService that throws on missingEvents().
 */
function fakeWebhookInspectionThrows(string $message): void
{
    app()->instance(
        StripeWebhookInspectionService::class,
        new class($message) extends StripeWebhookInspectionService
        {
            public function __construct(private readonly string $msg) {}

            public function missingEvents(): array
            {
                throw new RuntimeException($this->msg);
            }
        },
    );
}

describe('Readiness — Stripe webhook subscription check', function () {

    it('shows green when all required events are subscribed', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        config(['services.stripe.secret' => 'sk_test_readiness']);
        fakeWebhookInspection([]);

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertSet('checks.stripe_webhook_subscriptions.status', 'green');
    });

    it('shows red when required events are missing', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        config(['services.stripe.secret' => 'sk_test_readiness']);
        fakeWebhookInspection(['checkout.session.completed', 'transfer.created']);

        $component = Livewire::actingAs($admin)->test(Readiness::class);

        expect($component->get('checks.stripe_webhook_subscriptions.status'))->toBe('red');

        $message = $component->get('checks.stripe_webhook_subscriptions.message');
        expect($message)->toContain('checkout.session.completed');
        expect($message)->toContain('transfer.created');
    });

    it('shows yellow when Stripe secret key is not configured', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        config(['services.stripe.secret' => '']);

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertSet('checks.stripe_webhook_subscriptions.status', 'yellow');
    });

    it('shows red when Stripe API call fails', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        config(['services.stripe.secret' => 'sk_test_readiness']);
        fakeWebhookInspectionThrows('Connection refused');

        $component = Livewire::actingAs($admin)->test(Readiness::class);

        expect($component->get('checks.stripe_webhook_subscriptions.status'))->toBe('red');
        expect($component->get('checks.stripe_webhook_subscriptions.message'))->toContain('Connection refused');
    });

    it('check label has a translation in all three locales', function () {
        foreach (['en', 'fr', 'nl'] as $locale) {
            $label = trans('admin.readiness_check_stripe_webhook_subscriptions', locale: $locale);
            expect($label)->not->toBe('admin.readiness_check_stripe_webhook_subscriptions');
        }
    });
});
