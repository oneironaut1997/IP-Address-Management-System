<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIPRequest;
use App\Http\Requests\UpdateIPRequest;
use App\Http\Resources\IPAddressCollection;
use App\Http\Resources\IPAddressResource;
use App\Services\IPService;
use Auth;
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
 * Responsibilities:
 * - Request validation (via Form Requests)
 * - Authorization checks (via Policies)
 * - HTTP response formatting
 * - Delegating business logic to service layer
 */
class IPController extends Controller
{
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
        $perPage = (int) $request->input('per_page', 20);
        $ips = $this->ipService->getAllIPAddresses($perPage);

        return response()->json(
            new IPAddressCollection($ips)
        );
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
        // Get user from multiple sources - support both actingAs, auth, and headers
        $authUser = \Illuminate\Support\Facades\Auth::user();
        $requestUser = $request->user();
        $headerUserId = $request->header('X-User-ID');
        
        $userId = null;
        
        if ($authUser) {
            $userId = $authUser->id;
        } elseif ($requestUser) {
            $userId = $requestUser->id;
        } elseif ($headerUserId) {
            $userId = $headerUserId;
        }

        if (!$userId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'User not authenticated.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $result = $this->ipService->createIPAddress($request->validated(), $userId);

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
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
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
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        // Get user from multiple sources - support both actingAs, auth, and headers
        $authUser = \Illuminate\Support\Facades\Auth::user();
        $requestUser = $request->user();
        $headerUserId = $request->header('X-User-ID');
        $headerUserRole = $request->header('X-User-Role', 'regular');
        
        $userId = null;
        $userRole = 'regular';
        
        if ($authUser) {
            $userId = $authUser->id;
            $userRole = $authUser->role ?? 'regular';
        } elseif ($requestUser) {
            $userId = $requestUser->id;
            $userRole = $requestUser->role ?? 'regular';
        } elseif ($headerUserId) {
            $userId = $headerUserId;
            $userRole = $headerUserRole;
        }

        if (!$userId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'User not authenticated.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Use IPService authorization check (supports both User model and header-based auth)
        if (!$this->ipService->canUpdate($ipAddress, $userId, $userRole)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to update this IP address.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $updatedIp = $this->ipService->updateIPAddress($ipAddress, $request->validated(), $userId);

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
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        // Get user from multiple sources - support both actingAs, auth, and headers
        $authUser = \Illuminate\Support\Facades\Auth::user();
        $requestUser = $request->user();
        $headerUserId = $request->header('X-User-ID');
        $headerUserRole = $request->header('X-User-Role', 'regular');
        
        $userId = null;
        $userRole = 'regular';
        
        if ($authUser) {
            $userId = $authUser->id;
            $userRole = $authUser->role ?? 'regular';
        } elseif ($requestUser) {
            $userId = $requestUser->id;
            $userRole = $requestUser->role ?? 'regular';
        } elseif ($headerUserId) {
            $userId = $headerUserId;
            $userRole = $headerUserRole;
        }

        if (!$userId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'User not authenticated.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Use IPService authorization check (supports both User model and header-based auth)
        if (!$this->ipService->canDelete($userRole)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Only super administrators can delete IP addresses.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $this->ipService->deleteIPAddress($ipAddress, $userId);

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
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $history = $this->ipService->getIPAddressHistory($id);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
