<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class AuditController
 *
 * HTTP Controller for audit log endpoints.
 * Delegates business logic to AuditService and uses API Resources
 * for consistent response formatting.
 *
 * Responsibilities:
 * - Request validation
 * - HTTP response formatting
 * - Delegating business logic to service layer
 */
class AuditController extends Controller
{
    /**
     * @param AuditService $auditService The audit service
     */
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Get all audit logs with optional filtering.
     *
     * Retrieves audit logs with related user information.
     * Supports filtering by event, user_id, and date range.
     * Results are ordered by creation date (newest first).
     *
     * @param  Request  $request  The HTTP request with optional filters
     * @return JsonResponse The list of audit logs
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->auditService->buildFilters($request->all());
        $perPage = (int) $request->input('per_page', 50);
        $logs = $this->auditService->getAuditLogs($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => ActivityLogResource::collection($logs->items()),
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
        $log = $this->auditService->getAuditLogById($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Audit log not found.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => new ActivityLogResource($log),
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
        $types = $this->auditService->getEventTypes();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
}
