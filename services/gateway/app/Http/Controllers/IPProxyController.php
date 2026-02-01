<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * IP Proxy Controller
 *
 * Proxies IP management requests to the ip-management service.
 * Handles all HTTP methods for IP CRUD operations and audit logs.
 *
 * @package App\Http\Controllers
 */
class IPProxyController extends Controller
{
    /**
     * IP service base URL
     *
     * @var string
     */
    protected string $ipServiceUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ipServiceUrl = 'http://ip-management:8000';
    }

    /**
     * Handle all IP service requests
     *
     * Generic handler that proxies any HTTP method to the IP service.
     * Supports wildcard paths for nested resources.
     *
     * @param Request $request The HTTP request
     * @param string|null $path The path to proxy (optional, supports wildcards)
     * @return Response|JsonResponse The proxied response
     */
    public function handle(Request $request, ?string $path = null): Response|JsonResponse
    {
        $method = strtolower($request->getMethod());
        $url = $this->buildUrl($path);

        // Prepare headers to forward
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $request->header('Authorization'),
            'X-User-ID' => $request->header('X-User-ID'),
            'X-User-Role' => $request->header('X-User-Role'),
        ];

        // Filter out null headers
        $headers = array_filter($headers, fn($value) => $value !== null);

        // Build the HTTP request
        $httpRequest = Http::withHeaders($headers);

        // Execute the appropriate HTTP method
        try {
            $response = match ($method) {
                'get' => $httpRequest->get($url, $request->query()),
                'post' => $httpRequest->post($url, $request->all()),
                'put' => $httpRequest->put($url, $request->all()),
                'patch' => $httpRequest->patch($url, $request->all()),
                'delete' => $httpRequest->delete($url, $request->all()),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            // Return response with the same status code
            return response()->json(
                $response->json() ?? [],
                $response->status()
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PROXY_ERROR',
                    'message' => 'Failed to proxy request to IP service',
                    'details' => $e->getMessage(),
                ],
            ], 502);
        }
    }

    /**
     * Build the target URL for the IP service
     *
     * @param string|null $path The path segment
     * @return string The complete URL
     */
    protected function buildUrl(?string $path = null): string
    {
        $baseUrl = "{$this->ipServiceUrl}/api/ip";

        if ($path) {
            // Ensure path doesn't start with a slash to avoid double slashes
            $path = ltrim($path, '/');
            return "{$baseUrl}/{$path}";
        }

        return $baseUrl;
    }
}
