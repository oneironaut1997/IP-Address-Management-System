<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIPProxyRequest;
use App\Http\Requests\UpdateIPProxyRequest;
use App\Services\IPProxyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * IP Proxy Controller
 *
 * Proxies IP management requests to the ip-management service.
 * Handles all HTTP methods for IP CRUD operations and audit logs.
 *
 * Responsibilities:
 * - Request validation (via Form Requests)
 * - HTTP response formatting
 * - Delegating proxy logic to service layer
 */
class IPProxyController extends Controller
{
    /**
     * @param  IPProxyService  $ipProxyService  The IP proxy service
     */
    public function __construct(
        protected IPProxyService $ipProxyService
    ) {}

    /**
     * Display a paginated listing of IP addresses.
     *
     * @param  Request  $request  The HTTP request
     */
    public function index(Request $request): JsonResponse
    {
        $response = $this->ipProxyService->getAllIPAddresses($request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Store a newly created IP address.
     *
     * @param  StoreIPProxyRequest  $request  Validated store request
     */
    public function store(StoreIPProxyRequest $request): JsonResponse
    {
        $response = $this->ipProxyService->createIPAddress($request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Display the specified IP address.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $response = $this->ipProxyService->getIPAddress($id, $request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Update the specified IP address.
     *
     * @param  UpdateIPProxyRequest  $request  Validated update request
     * @param  string  $id  The IP address ID
     */
    public function update(UpdateIPProxyRequest $request, string $id): JsonResponse
    {
        $response = $this->ipProxyService->updateIPAddress($id, $request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Remove the specified IP address.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $response = $this->ipProxyService->deleteIPAddress($id, $request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Get the change history for a specific IP address.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request
     */
    public function history(string $id, Request $request): JsonResponse
    {
        $response = $this->ipProxyService->getIPAddressHistory($id, $request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Get the audit log for a specific IP address.
     *
     * @param  string  $id  The IP address ID
     * @param  Request  $request  The HTTP request
     */
    public function audit(string $id, Request $request): JsonResponse
    {
        $response = $this->ipProxyService->getIPAddressAudit($id, $request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Handle all IP service requests (wildcard route).
     *
     * Generic handler that proxies any HTTP method to the IP service.
     * Supports wildcard paths for nested resources.
     *
     * @param  Request  $request  The HTTP request
     * @param  string|null  $path  The path to proxy (optional, supports wildcards)
     * @return JsonResponse The proxied response
     */
    public function handle(Request $request, ?string $path = null): JsonResponse
    {
        $method = strtolower($request->getMethod());

        try {
            $response = $this->ipProxyService->proxyRequest($method, $request, $path);

            return response()->json(
                $response->json() ?? [],
                $response->status()
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_METHOD',
                    'message' => $e->getMessage(),
                ],
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PROXY_ERROR',
                    'message' => 'Failed to proxy request to IP service',
                    'details' => $e->getMessage(),
                ],
            ], Response::HTTP_BAD_GATEWAY);
        }
    }
}
