<?php

declare(strict_types=1);

use App\Enums\AuditActorType;
use App\Enums\AuditSource;
use App\Services\Audit\AuditContext;
use App\Services\Audit\AuditContextResolver;

describe('AuditContextResolver', function () {

    beforeEach(function () {
        $this->resolver = new AuditContextResolver;
    });

    describe('current()', function () {

        it('returns a default system context when nothing has been set', function () {
            $context = $this->resolver->current();

            expect($context)->toBeInstanceOf(AuditContext::class);
            expect($context->source)->toBe(AuditSource::Test);
            expect($context->actorType)->toBe(AuditActorType::System);
            expect($context->actorId)->toBeNull();
            expect($context->actorRole)->toBeNull();
        });

        it('returns the same context object set via setFromRequest equivalent', function () {
            $context = $this->resolver->forConsole('migrate');
            expect($context)->toBeInstanceOf(AuditContext::class);
        });

    });

    describe('reset()', function () {

        it('clears the stored context so current() returns the default', function () {
            // Set something first by calling forConsole
            $consoleCtx = $this->resolver->forConsole('test:command');

            // Simulate middleware setting it
            $reflection = new ReflectionProperty(AuditContextResolver::class, 'context');
            $reflection->setValue($this->resolver, $consoleCtx);

            $this->resolver->reset();

            $default = $this->resolver->current();
            expect($default->source)->toBe(AuditSource::Test);
        });

    });

    describe('forConsole()', function () {

        it('returns a context with console source and console actor type', function () {
            $ctx = $this->resolver->forConsole('inspire');

            expect($ctx->source)->toBe(AuditSource::Console);
            expect($ctx->actorType)->toBe(AuditActorType::Console);
            expect($ctx->metadata['command'])->toBe('inspire');
            expect($ctx->actorId)->toBeNull();
            expect($ctx->actorRole)->toBeNull();
            expect($ctx->jobUuid)->toBeNull();
        });

        it('generates a non-empty request id', function () {
            $ctx = $this->resolver->forConsole('some:command');

            expect($ctx->requestId)->not->toBeEmpty();
        });

    });

    describe('forScheduler()', function () {

        it('returns a context with scheduler source and scheduler actor type', function () {
            $ctx = $this->resolver->forScheduler('emails:send-reminders');

            expect($ctx->source)->toBe(AuditSource::Scheduler);
            expect($ctx->actorType)->toBe(AuditActorType::Scheduler);
            expect($ctx->metadata['command'])->toBe('emails:send-reminders');
            expect($ctx->actorId)->toBeNull();
        });

    });

    describe('forQueue()', function () {

        it('returns a context with queue source and queue actor type', function () {
            $jobUuid = '550e8400-e29b-41d4-a716-446655440000';
            $ctx = $this->resolver->forQueue($jobUuid);

            expect($ctx->source)->toBe(AuditSource::Queue);
            expect($ctx->actorType)->toBe(AuditActorType::Queue);
            expect($ctx->jobUuid)->toBe($jobUuid);
            expect($ctx->actorId)->toBeNull();
        });

    });

    describe('forWebhook()', function () {

        it('returns a webhook context with stripe actor type for stripe provider', function () {
            $ctx = $this->resolver->forWebhook('stripe', 'evt_abc123');

            expect($ctx->source)->toBe(AuditSource::Webhook);
            expect($ctx->actorType)->toBe(AuditActorType::Stripe);
            expect($ctx->metadata['provider'])->toBe('stripe');
            expect($ctx->metadata['event_id'])->toBe('evt_abc123');
        });

        it('returns a webhook context with system actor type for unknown providers', function () {
            $ctx = $this->resolver->forWebhook('mollie', 'evt_xyz');

            expect($ctx->source)->toBe(AuditSource::Webhook);
            expect($ctx->actorType)->toBe(AuditActorType::System);
            expect($ctx->metadata['provider'])->toBe('mollie');
            expect($ctx->metadata['event_id'])->toBe('evt_xyz');
        });

        it('includes the stripe event id in metadata', function () {
            $ctx = $this->resolver->forWebhook('stripe', 'evt_stripe_123');

            expect($ctx->metadata)->toHaveKey('event_id', 'evt_stripe_123');
        });

    });

    describe('context isolation', function () {

        it('does not share state between two independent resolver instances', function () {
            $resolver1 = new AuditContextResolver;
            $resolver2 = new AuditContextResolver;

            // Set context via reflection on resolver1
            $consoleCtx = $resolver1->forConsole('cmd');
            $reflection = new ReflectionProperty(AuditContextResolver::class, 'context');
            $reflection->setValue($resolver1, $consoleCtx);

            // resolver2 should still return default context
            expect($resolver2->current()->source)->toBe(AuditSource::Test);
        });

    });

});
