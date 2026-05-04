<?php

declare(strict_types=1);

use App\Enums\AuditActorType;
use App\Enums\AuditSource;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Audit\AuditContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Register a test route that exposes the current audit context so we can
    // inspect it without needing to reach into global state post-request.
    Route::middleware('web')->get('/_test/audit-context', function () {
        $resolver = app(AuditContextResolver::class);
        $ctx = $resolver->current();

        return response()->json([
            'request_id' => $ctx->requestId,
            'source' => $ctx->source->value,
            'actor_type' => $ctx->actorType->value,
            'actor_id' => $ctx->actorId,
            'actor_role' => $ctx->actorRole?->value,
            'ip_address' => $ctx->ipAddress,
            'user_agent' => $ctx->userAgent,
            'route_name' => $ctx->routeName,
        ]);
    })->name('_test.audit_context');
});

describe('CaptureAuditContext middleware', function () {

    describe('authenticated web request', function () {

        it('sets source to web', function () {
            $user = User::factory()->coach()->create();

            $response = $this->actingAs($user)->get('/_test/audit-context');

            expect($response->json('source'))->toBe(AuditSource::Web->value);
        });

        it('sets actor_type to user for authenticated requests', function () {
            $user = User::factory()->athlete()->create();

            $response = $this->actingAs($user)->get('/_test/audit-context');

            expect($response->json('actor_type'))->toBe(AuditActorType::User->value);
        });

        it('sets actor_id to the authenticated user id', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->get('/_test/audit-context');

            expect($response->json('actor_id'))->toBe($user->id);
        });

        it('sets actor_role to the authenticated user role', function () {
            $user = User::factory()->coach()->create();

            $response = $this->actingAs($user)->get('/_test/audit-context');

            expect($response->json('actor_role'))->toBe(UserRole::Coach->value);
        });

        it('sets a non-empty request_id', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->get('/_test/audit-context');

            expect($response->json('request_id'))->not->toBeEmpty();
        });

        it('reuses the X-Request-Id header if provided', function () {
            $user = User::factory()->create();
            $customId = 'my-custom-request-id-123';

            $response = $this->actingAs($user)
                ->withHeaders(['X-Request-Id' => $customId])
                ->get('/_test/audit-context');

            expect($response->json('request_id'))->toBe($customId);
        });

        it('sets the route_name', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->get('/_test/audit-context');

            expect($response->json('route_name'))->toBe('_test.audit_context');
        });

    });

    describe('guest web request', function () {

        it('sets source to web for unauthenticated requests', function () {
            $response = $this->get('/_test/audit-context');

            expect($response->json('source'))->toBe(AuditSource::Web->value);
        });

        it('sets actor_type to system for unauthenticated requests', function () {
            $response = $this->get('/_test/audit-context');

            expect($response->json('actor_type'))->toBe(AuditActorType::System->value);
        });

        it('leaves actor_id null for guest requests', function () {
            $response = $this->get('/_test/audit-context');

            expect($response->json('actor_id'))->toBeNull();
        });

        it('leaves actor_role null for guest requests', function () {
            $response = $this->get('/_test/audit-context');

            expect($response->json('actor_role'))->toBeNull();
        });

        it('still sets a non-empty request_id for guest requests', function () {
            $response = $this->get('/_test/audit-context');

            expect($response->json('request_id'))->not->toBeEmpty();
        });

    });

    describe('context reset after request', function () {

        it('resets the context after the response so subsequent calls return the default', function () {
            $resolver = app(AuditContextResolver::class);

            // Make a request to populate the context
            $user = User::factory()->create();
            $this->actingAs($user)->get('/_test/audit-context');

            // After the middleware's termination, resolver should be reset
            // (the middleware calls reset() after $next($request))
            $ctxAfter = $resolver->current();
            expect($ctxAfter->source)->toBe(AuditSource::Test);
        });

    });

});
