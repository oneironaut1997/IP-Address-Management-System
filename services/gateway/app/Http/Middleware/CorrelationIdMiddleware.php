<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Correlation ID Middleware
 *
 * Adds a unique correlation ID to each request for distributed tracing.
 * The correlation ID is:
 * - Generated if not present in request headers
 * - Propagated to downstream services via headers
 * - Included in all responses
 *
 * This enables tracing requests across multiple services in the
 * microservices architecture.
 */
class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get or generate correlation ID - case-insensitive
        $correlationId = $this->getCorrelationId($request);

        // Add to request attributes for downstream access
        $request->attributes->set('correlation_id', $correlationId);

        // Generate request ID if not present
        $requestId = $this->getRequestId($request);
        $request->attributes->set('request_id', $requestId);

        // Process the request
        $response = $next($request);

        // Add correlation ID to response headers
        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /**
     * Get correlation ID from request or generate new one
     */
    private function getCorrelationId(Request $request): string
    {
        // Check existing headers for correlation ID (case-insensitive)
        $headers = $request->headers;

        // Check multiple possible header names
        foreach (['X-Correlation-ID', 'X-Request-ID', 'X-Trace-ID'] as $headerName) {
            if ($headers->has($headerName)) {
                return $headers->get($headerName);
            }
        }

        // Generate new correlation ID if not present
        return Str::uuid()->toString();
    }

    /**
     * Get request ID from request or generate new one
     */
    private function getRequestId(Request $request): string
    {
        $headers = $request->headers;

        foreach (['X-Request-ID', 'X-Correlation-ID', 'X-Trace-ID'] as $headerName) {
            if ($headers->has($headerName)) {
                return $headers->get($headerName);
            }
        }

        return Str::uuid()->toString();
    }
}
