<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIPRequest;
use App\Http\Requests\UpdateIPRequest;
use App\Http\Resources\IPAddressCollection;
use App\Http\Resources\IPAddressResource;
use App\Services\IPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class IPController
 *
 * HTTP Controller for IP address management endpoints.
 * Delegates business logic to IPService and uses API Resources
 * for consistent response formatting.
 *
 * Uses InteractsWithUserContext trait for consistent user extraction.
 *
 * Responsibilities:
 * - Request validation (via Form Requests)
 * - Authorization checks (via IPService)
 * - HTTP response formatting
 * - Delegating business logic to service layer
 */
class IPController extends Controller
{
    use InteractsWithUserContext;

    /**
     * @param  IPService  $ipService  The IP management service
     */
    public function __construct(
        protected IPService $ipService
    ) {}

    /**
     * Display a paginated listing of IP addresses.
     *
     * All authenticated users can view all IP addresses.
     * Supports pagination via 'per_page' query parameter (default: 20, max: 100).
     *
     * @param  Request  $request  The HTTP request
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $ips = $this->ipService->getAllIPAddresses($perPage);

        return response()->json([
            'success' => true,
            'data' => new IPAddressCollection($ips),
            'meta' => [
                'current_page' => $ips->currentPage(),
                'per_page' => $ips->perPage(),
                'total' => $ips->total(),
                'last_page' => $ips->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created IP address.
     *
     * Validates IP format using rlanvin/php-ip library.
     * Logs activity for audit trail.
     *
     * @param  StoreIPRequest  $request  Validated store request
     */
    public function store(StoreIPRequest $request): JsonResponse
    {
        $context = $this->requireUserContext($request);

        if (! $context) {
            return $this->unauthorizedResponse();
        }

        $result = $this->ipService->createIPAddress($request->validated(), $context['user_id']);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'success' => true,
            'data' => new IPAddressResource($result['ip']),
            'message' => 'IP address created successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified IP address.
     *
     * @param  string  $id  The IP address ID
     */
    public function show(string $id): JsonResponse
    {
        $ipAddress = $this->ipService->getIPAddressById($id);

        if (! $ipAddress) {
            return $this->notFoundResponse('IP address');
        }

        return response()->json([
            'success' => true,
            'data' => new IPAddressResource($ipAddress),
        ]);
    }

    /**
     * Update the specified IP address.
     *
     * Only the owner or super_admin can update an IP address.
     * Tracks changes in both history table and activity log.
     *
     * @param  UpdateIPRequest  $request  Validated update request
     * @param  string  $id  The IP address ID
     */
    public function update(UpdateIPRequest $request, string $id): JsonResponse
    {
        $ipAddress = $this->ipService->getIPAddressById($id);

        if (! $ipAddress) {
            return $this->notFoundResponse('IP address');
        }

        $context = $this->requireUserContext($request);

        if (! $context) {
            return $this->unauthorizedResponse();
        }

        // Use IPService authorization check
        if (! $this->ipService->canUpdate($ipAddress, $context['user_id'], $context['role'])) {
            return $this->forbiddenResponse('You do not have permission to update this IP address.');
        }

        $updatedIp = $this->ipService->updateIPAddress($ipAddress, $request->validated(), $context['user_id']);

        return response()->json([
            'success' => true,
            'data' => new IPAddressResource($updatedIp),
            'message' => 'IP address updated successfully.',
        ]);
    }

    /**
     * Remove the specified IP address.
     *
     * Only super_admin can delete IP addresses.
     * Uses soft deletes for data recovery.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $id  The IP address ID
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $ipAddress = $this->ipService->getIPAddressById($id);

        if (! $ipAddress) {
            return $this->notFoundResponse('IP address');
        }

        $context = $this->requireUserContext($request);

        if (! $context) {
            return $this->unauthorizedResponse();
        }

        // Use IPService authorization check
        if (! $this->ipService->canDelete($context['role'])) {
            return $this->forbiddenResponse('Only super administrators can delete IP addresses.');
        }

        $this->ipService->deleteIPAddress($ipAddress, $context['user_id']);

        return response()->json([
            'success' => true,
            'message' => 'IP address deleted successfully.',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the change history for a specific IP address.
     *
     * @param  string  $id  The IP address ID
     */
    public function history(string $id): JsonResponse
    {
        $ipAddress = $this->ipService->getIPAddressById($id);

        if (! $ipAddress) {
            return $this->notFoundResponse('IP address');
        }

        $history = $this->ipService->getIPAddressHistory($id);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
