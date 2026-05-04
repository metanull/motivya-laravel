<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\AuditActorType;
use App\Enums\AuditSource;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Resolves and stores the current audit context.
 *
 * Bound as a singleton so that the web middleware can set the context once per
 * request and any service consuming AuditService can read it without having the
 * request threaded through method signatures.
 */
final class AuditContextResolver
{
    private ?AuditContext $context = null;

    /**
     * Returns the current context, or a minimal system context if none has been set.
     */
    public function current(): AuditContext
    {
        return $this->context ?? new AuditContext(
            requestId: Str::uuid()->toString(),
            source: AuditSource::Test,
            actorType: AuditActorType::System,
            actorId: null,
            actorRole: null,
            ipAddress: null,
            userAgent: null,
            routeName: null,
            jobUuid: null,
            metadata: [],
        );
    }

    /**
     * Sets the context from the current web request.
     * Called by the CaptureAuditContext middleware for every web request.
     */
    public function setFromRequest(Request $request): void
    {
        $requestId = $request->header('X-Request-Id') ?? Str::uuid()->toString();

        $actorType = AuditActorType::System;
        $actorId = null;
        $actorRole = null;

        $user = $request->user();
        if ($user !== null) {
            $actorType = AuditActorType::User;
            $actorId = (int) $user->getKey();
            $actorRole = $user->role instanceof UserRole ? $user->role : null;
        }

        $this->context = new AuditContext(
            requestId: $requestId,
            source: AuditSource::Web,
            actorType: $actorType,
            actorId: $actorId,
            actorRole: $actorRole,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            routeName: $request->route()?->getName(),
            jobUuid: null,
            metadata: [],
        );
    }

    /**
     * Resets the stored context. Used to ensure no state leaks between requests or tests.
     */
    public function reset(): void
    {
        $this->context = null;
    }

    /**
     * Returns a context object for a console command execution.
     */
    public function forConsole(string $command): AuditContext
    {
        return new AuditContext(
            requestId: Str::uuid()->toString(),
            source: AuditSource::Console,
            actorType: AuditActorType::Console,
            actorId: null,
            actorRole: null,
            ipAddress: null,
            userAgent: null,
            routeName: null,
            jobUuid: null,
            metadata: ['command' => $command],
        );
    }

    /**
     * Returns a context object for a scheduled command execution.
     */
    public function forScheduler(string $command): AuditContext
    {
        return new AuditContext(
            requestId: Str::uuid()->toString(),
            source: AuditSource::Scheduler,
            actorType: AuditActorType::Scheduler,
            actorId: null,
            actorRole: null,
            ipAddress: null,
            userAgent: null,
            routeName: null,
            jobUuid: null,
            metadata: ['command' => $command],
        );
    }

    /**
     * Returns a context object for a queued job execution.
     */
    public function forQueue(string $jobUuid): AuditContext
    {
        return new AuditContext(
            requestId: Str::uuid()->toString(),
            source: AuditSource::Queue,
            actorType: AuditActorType::Queue,
            actorId: null,
            actorRole: null,
            ipAddress: null,
            userAgent: null,
            routeName: null,
            jobUuid: $jobUuid,
            metadata: [],
        );
    }

    /**
     * Returns a context object for an incoming webhook request.
     *
     * For Stripe webhooks, pass 'stripe' as the provider and the Stripe event id.
     */
    public function forWebhook(string $provider, string $eventId): AuditContext
    {
        $actorType = match ($provider) {
            'stripe' => AuditActorType::Stripe,
            default => AuditActorType::System,
        };

        return new AuditContext(
            requestId: Str::uuid()->toString(),
            source: AuditSource::Webhook,
            actorType: $actorType,
            actorId: null,
            actorRole: null,
            ipAddress: null,
            userAgent: null,
            routeName: null,
            jobUuid: null,
            metadata: ['provider' => $provider, 'event_id' => $eventId],
        );
    }
}
