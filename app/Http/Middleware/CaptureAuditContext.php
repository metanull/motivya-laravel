<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Audit\AuditContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captures audit context at the start of every web request.
 *
 * This middleware populates the AuditContextResolver singleton so that any
 * service calling AuditService::record() during this request automatically
 * picks up the correct actor, source, request id, and routing metadata.
 */
final class CaptureAuditContext
{
    public function __construct(
        private readonly AuditContextResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->resolver->setFromRequest($request);

        $response = $next($request);

        // Reset after the response so no context leaks into background jobs
        // or subsequent requests that may reuse the same process.
        $this->resolver->reset();

        return $response;
    }
}
