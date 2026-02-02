<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIPRequest;
use App\Http\Requests\UpdateIPRequest;
use App\Models\IPAddress;
use App\Models\IPHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpIP\IP;

/**
 * Class IPController
 *
 * Handles CRUD operations for IP addresses.
 * Implements authorization policies and comprehensive audit logging.
 */
class IPController extends Controller
{
    /**
     * Display a listing of all IP addresses.
     *
     * All authenticated users can view all IP addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $ips = IPAddress::with('history')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ips,
        ]);
    }

    /**
     * Store a newly created IP address.
     *
     * Validates IP format using rlanvin/php-ip library.
     * Logs activity for audit trail.
     */
    public function store(StoreIPRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Validate and detect IP type using php-ip library
        try {
            $ip = IP::create($validated['ip_address']);
            $type = $ip->getVersion() === 4 ? 'ipv4' : 'ipv6';
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid IP address format.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        // Get authenticated user from request (set by middleware)
        $userId = $request->header('X-User-ID');

        $ipAddress = IPAddress::create([
            'user_id' => $userId,
            'ip_address' => $validated['ip_address'],
            'label' => $validated['label'],
            'comment' => $validated['comment'] ?? null,
            'type' => $type,
        ]);

        // Log the creation in history table
        IPHistory::create([
            'ip_address_id' => $ipAddress->id,
            'modified_by' => $userId,
            'old_values' => null,
            'new_values' => $ipAddress->toArray(),
            'action' => 'created',
        ]);

        // Log activity using Spatie Activity Log
        activity()
            ->performedOn($ipAddress)
            ->event('ip.created')
            ->withProperties(['ip' => $ipAddress->toArray(), 'causer_id' => $userId])
            ->tap(function ($activity) use ($userId) {
                $activity->causer_id = $userId;
                $activity->causer_type = null;
            })
            ->log('ip.created');

        return response()->json([
            'success' => true,
            'data' => $ipAddress,
            'message' => 'IP address created successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified IP address.
     */
    public function show(string $id): JsonResponse
    {
        $ipAddress = IPAddress::with('history')->find($id);

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
            'data' => $ipAddress,
        ]);
    }

    /**
     * Update the specified IP address.
     *
     * Only the owner or super_admin can update an IP address.
     * Tracks changes in both history table and activity log.
     */
    public function update(UpdateIPRequest $request, string $id): JsonResponse
    {
        $ipAddress = IPAddress::find($id);

        if (! $ipAddress) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        // Authorization check
        $userId = $request->header('X-User-ID');
        $userRole = $request->header('X-User-Role');

        if ($ipAddress->user_id !== $userId && $userRole !== 'super_admin') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to update this IP address.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        // Capture old values before update
        $oldValues = $ipAddress->toArray();

        $ipAddress->update($request->validated());

        // Log the update in history table
        IPHistory::create([
            'ip_address_id' => $ipAddress->id,
            'modified_by' => $userId,
            'old_values' => $oldValues,
            'new_values' => $ipAddress->fresh()->toArray(),
            'action' => 'updated',
        ]);

        // Log activity using Spatie Activity Log
        activity()
            ->performedOn($ipAddress)
            ->event('ip.updated')
            ->withProperties([
                'old' => $oldValues,
                'new' => $ipAddress->fresh()->toArray(),
                'causer_id' => $userId,
            ])
            ->tap(function ($activity) use ($userId) {
                $activity->causer_id = $userId;
                $activity->causer_type = null;
            })
            ->log('ip.updated');

        return response()->json([
            'success' => true,
            'data' => $ipAddress,
            'message' => 'IP address updated successfully.',
        ]);
    }

    /**
     * Remove the specified IP address.
     *
     * Only super_admin can delete IP addresses.
     * Uses soft deletes for data recovery.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $ipAddress = IPAddress::find($id);

        if (! $ipAddress) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        // Authorization check - only super_admin can delete
        $userId = $request->header('X-User-ID');
        $userRole = $request->header('X-User-Role');

        if ($userRole !== 'super_admin') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Only super administrators can delete IP addresses.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $oldValues = $ipAddress->toArray();

        // Soft delete the IP address
        $ipAddress->delete();

        // Log the deletion in history table
        IPHistory::create([
            'ip_address_id' => $ipAddress->id,
            'modified_by' => $userId,
            'old_values' => $oldValues,
            'new_values' => null,
            'action' => 'deleted',
        ]);

        // Log activity using Spatie Activity Log
        activity()
            ->performedOn($ipAddress)
            ->event('ip.deleted')
            ->withProperties(['ip' => $oldValues, 'causer_id' => $userId])
            ->tap(function ($activity) use ($userId) {
                $activity->causer_id = $userId;
                $activity->causer_type = null;
            })
            ->log('ip.deleted');

        return response()->json([
            'success' => true,
            'message' => 'IP address deleted successfully.',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the change history for a specific IP address.
     */
    public function history(string $id): JsonResponse
    {
        $ipAddress = IPAddress::find($id);

        if (! $ipAddress) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $history = IPHistory::where('ip_address_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
