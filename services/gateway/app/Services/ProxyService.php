<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Class ProxyService
 *
 * Base service for proxying requests to backend microservices.
 * Provides common HTTP client functionality and error handling.
 *
 * This service encapsulates all proxy-related business rules and separates
 * them from HTTP concerns in the controller layer.
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
        return $this->forwardRequest(
            $this->authServiceUrl.$endpoint,
            $method,
            $request,
            $additionalHeaders
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
     */
    protected function forwardRequest(
        string $url,
        string $method,
        Request $request,
        array $additionalHeaders = []
    ): Response {
        $headers = $this->buildHeaders($request, $additionalHeaders);

        $httpRequest = Http::withHeaders($headers);

        return match (strtolower($method)) {
            'get' => $httpRequest->get($url, $request->query()),
            'post' => $httpRequest->post($url, $request->all()),
            'put' => $httpRequest->put($url, $request->all()),
            'patch' => $httpRequest->patch($url, $request->all()),
            'delete' => $httpRequest->delete($url, $request->all()),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Build headers to forward with the request.
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

        return array_merge($headers, $additionalHeaders);
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
