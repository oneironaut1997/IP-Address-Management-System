<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class ProxyService
 *
 * Base service for proxying requests to backend microservices.
 * Provides common HTTP client functionality and error handling.
 *
 * This service encapsulates all proxy-related business rules and separates
 * them from HTTP concerns in the controller layer.
 *
 * Features:
 * - Correlation ID propagation for distributed tracing
 * - Structured logging for debugging
 * - Cookie-based authentication forwarding
 */
class ProxyService
{
    /**
     * Auth service base URL
     */
    protected string $authServiceUrl;

    /**
     * IP Management service base URL
     */
    protected string $ipServiceUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authServiceUrl = config('services.auth.url', 'http://auth-service:8000');
        $this->ipServiceUrl = config('services.ip_management.url', 'http://ip-management:8000');
    }

    /**
     * Forward a request to the auth service.
     *
     * @param  string  $method  HTTP method (get, post, put, patch, delete)
     * @param  string  $endpoint  API endpoint (e.g., '/api/auth/login')
     * @param  Request  $request  The original request
     * @param  array  $additionalHeaders  Additional headers to forward
     */
    public function forwardToAuthService(
        string $method,
        string $endpoint,
        Request $request,
        array $additionalHeaders = []
    ): Response {
        $this->logRequest('auth-service', $method, $endpoint, $request);

        return $this->forwardRequest(
            $this->authServiceUrl.$endpoint,
            $method,
            $request,
            $additionalHeaders
        );
    }

    /**
     * Forward a request to the auth service with a refresh token cookie.
     *
     * @param  string  $method  HTTP method
     * @param  string  $endpoint  API endpoint
     * @param  Request  $request  The original request
     * @param  string  $refreshToken  The refresh token to send as cookie
     */
    public function forwardToAuthServiceWithCookie(
        string $method,
        string $endpoint,
        Request $request,
        string $refreshToken
    ): Response {
        return $this->forwardRequest(
            $this->authServiceUrl.$endpoint,
            $method,
            $request,
            [],
            $refreshToken
        );
    }

    /**
     * Forward a request to the IP management service.
     *
     * @param  string  $method  HTTP method (get, post, put, patch, delete)
     * @param  string  $endpoint  API endpoint (e.g., '/api/ip')
     * @param  Request  $request  The original request
     * @param  array  $additionalHeaders  Additional headers to forward
     */
    public function forwardToIPService(
        string $method,
        string $endpoint,
        Request $request,
        array $additionalHeaders = []
    ): Response {
        $this->logRequest('ip-management', $method, $endpoint, $request);

        return $this->forwardRequest(
            $this->ipServiceUrl.$endpoint,
            $method,
            $request,
            $additionalHeaders
        );
    }

    /**
     * Forward a request to any service.
     *
     * @param  string  $url  Full URL to forward to
     * @param  string  $method  HTTP method
     * @param  Request  $request  The original request
     * @param  array  $additionalHeaders  Additional headers to forward
     * @param  string|null  $refreshToken  Optional refresh token cookie
     */
    protected function forwardRequest(
        string $url,
        string $method,
        Request $request,
        array $additionalHeaders = [],
        ?string $refreshToken = null
    ): Response {
        $headers = $this->buildHeaders($request, $additionalHeaders);

        $httpRequest = Http::withHeaders($headers);

        // Add refresh token as cookie if provided
        if ($refreshToken) {
            $httpRequest = $httpRequest->withCookies(
                ['refresh_token' => $refreshToken],
                parse_url($url, PHP_URL_HOST) ?? 'localhost'
            );
        }

        $response = match (strtolower($method)) {
            'get' => $httpRequest->get($url, $request->query()),
            'post' => $httpRequest->post($url, $request->all()),
            'put' => $httpRequest->put($url, $request->all()),
            'patch' => $httpRequest->patch($url, $request->all()),
            'delete' => $httpRequest->delete($url, $request->all()),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        return $response;
    }

    /**
     * Build headers to forward with the request.
     *
     * Includes correlation IDs for distributed tracing.
     *
     * @param  Request  $request  The original request
     * @param  array  $additionalHeaders  Additional headers to include
     */
    protected function buildHeaders(Request $request, array $additionalHeaders = []): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Forward authorization header if present
        if ($request->hasHeader('Authorization')) {
            $headers['Authorization'] = $request->header('Authorization');
        }

        // Forward user context headers if present (set by JWT middleware)
        if ($request->hasHeader('X-User-ID')) {
            $headers['X-User-ID'] = $request->header('X-User-ID');
        }

        if ($request->hasHeader('X-User-Role')) {
            $headers['X-User-Role'] = $request->header('X-User-Role');
        }

        if ($request->hasHeader('X-User-Email')) {
            $headers['X-User-Email'] = $request->header('X-User-Email');
        }

        // Forward correlation IDs for distributed tracing
        $correlationId = $request->attributes->get('correlation_id');
        if ($correlationId) {
            $headers['X-Correlation-ID'] = $correlationId;
        }

        $requestId = $request->attributes->get('request_id');
        if ($requestId) {
            $headers['X-Request-ID'] = $requestId;
        }

        return array_merge($headers, $additionalHeaders);
    }

    /**
     * Log outgoing proxy request (structured logging)
     */
    protected function logRequest(string $service, string $method, string $endpoint, Request $request): void
    {
        $correlationId = $request->attributes->get('correlation_id', 'N/A');
        $userId = $request->attributes->get('user_id', 'anonymous');

        Log::channel('gateway')->info('Proxying request', [
            'service' => $service,
            'method' => $method,
            'endpoint' => $endpoint,
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Get the auth service URL.
     */
    public function getAuthServiceUrl(): string
    {
        return $this->authServiceUrl;
    }

    /**
     * Get the IP service URL.
     */
    public function getIPServiceUrl(): string
    {
        return $this->ipServiceUrl;
    }
}
