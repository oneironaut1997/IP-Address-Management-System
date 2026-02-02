<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AuditController
 *
 * Handles audit log retrieval for compliance and security analysis.
 * Provides endpoints to fetch authentication event audit trails.
 */
class AuditController extends Controller
{
    /**
     * Get all audit logs with optional filtering.
     *
     * Retrieves audit logs with related user information.
     * Supports filtering by event_type and user_id.
     * Results are ordered by creation date (newest first).
     *
     * @param  Request  $request  The HTTP request with optional filters
     * @return JsonResponse The list of audit logs
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        // Apply filters if provided
        if ($request->has('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        // Order by newest first
        $query->orderBy('created_at', 'desc');

        // Paginate results (default 50 per page, max 100)
        $perPage = min($request->input('per_page', 50), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get a single audit log by ID.
     *
     * @param  string  $id  The UUID of the audit log
     * @return JsonResponse The audit log details
     */
    public function show(string $id): JsonResponse
    {
        $log = AuditLog::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Get available event types for filtering.
     *
     * Returns a list of unique event types stored in the audit logs.
     *
     * @return JsonResponse List of event types
     */
    public function eventTypes(): JsonResponse
    {
        $types = AuditLog::distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
}
