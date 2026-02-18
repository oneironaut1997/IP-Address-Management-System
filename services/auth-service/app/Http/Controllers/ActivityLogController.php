<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class ActivityLogController
 *
 * HTTP Controller for activity log endpoints.
 * Delegates business logic to ActivityLogService and uses API Resources
 * for consistent response formatting.
 *
 * Responsibilities:
 * - Request validation
 * - HTTP response formatting
 * - Delegating business logic to service layer
 */
class ActivityLogController extends Controller
{
    /**
     * @param  ActivityLogService  $activityLogService  The activity log service
     */
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Get all activity logs with optional filtering.
     *
     * Retrieves activity logs with related user information.
     * Supports filtering by event, user_id, and date range.
     * Results are ordered by creation date (newest first).
     *
     * @param  Request  $request  The HTTP request with optional filters
     * @return JsonResponse The list of activity logs
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->activityLogService->buildFilters($request->all());
        $perPage = (int) $request->input('per_page', 50);
        $logs = $this->activityLogService->getActivityLogs($filters, $perPage);

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
     * Get a single activity log by ID.
     *
     * @param  string  $id  The UUID of the activity log
     * @return JsonResponse The activity log details
     */
    public function show(string $id): JsonResponse
    {
        $log = $this->activityLogService->getActivityLogById($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Activity log not found.',
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
     * Returns a list of unique event types stored in the activity logs.
     *
     * @return JsonResponse List of event types
     */
    public function eventTypes(): JsonResponse
    {
        $types = $this->activityLogService->getEventTypes();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
}
