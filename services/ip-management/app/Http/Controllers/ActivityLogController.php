<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Retrieves activity logs with support for filtering by event type,
     * causer's user_id, subject_type, and date range.
     * Results are ordered by creation date (newest first).
     *
     * @param  Request  $request  The HTTP request with optional filters
     * @return JsonResponse The list of activity logs
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->activityLogService->buildFilters($request->all());
        $perPage = (int) $request->input('per_page', 50);
        $activities = $this->activityLogService->getActivityLogs($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => ActivityLogResource::collection($activities->items()),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }
}
