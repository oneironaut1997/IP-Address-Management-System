<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Class ActivityLogController
 *
 * Handles retrieval of activity logs from the Spatie Activity Log package.
 * Provides endpoints to query IP management activities for audit purposes.
 */
class ActivityLogController extends Controller
{
    /**
     * Get all activity logs with optional filtering.
     *
     * Retrieves activity logs with support for filtering by event type,
     * causer's user_id, subject_type, and date range.
     * Results are ordered by creation date (newest first).
     *
     * @param Request $request The HTTP request with optional filters
     * @return JsonResponse The list of activity logs
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::query();

        // Filter by event type (ip.created, ip.updated, ip.deleted)
        if ($request->has('event')) {
            $query->where('event', $request->input('event'));
        }

        // Filter by causer's user_id
        if ($request->has('user_id')) {
            $query->where('causer_id', $request->input('user_id'));
        }

        // Filter by subject type (e.g., IPAddress)
        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        // Filter by subject ID
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        // Filter by log name
        if ($request->has('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        // Date range filter - from date
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        // Date range filter - to date
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        // Order by newest first
        $query->orderBy('created_at', 'desc');

        // Paginate results (default 50 per page, max 100)
        $perPage = min($request->input('per_page', 50), 100);
        $activities = $query->paginate($perPage);

        // Transform activities to unified format
        $transformedActivities = collect($activities->items())->map(function ($activity) {
            return $this->transformActivity($activity);
        });

        return response()->json([
            'success' => true,
            'data' => $transformedActivities,
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Transform Spatie Activity to unified audit log format.
     *
     * @param Activity $activity The Spatie activity model
     * @return array The transformed activity in unified format
     */
    protected function transformActivity(Activity $activity): array
    {
        $properties = $activity->properties ?? [];

        // Extract user_id from properties or causer
        $userId = $activity->causer_id ?? $properties['causer_id'] ?? null;
        $metadata = [];

        // Flatten properties for metadata
        if (isset($properties['ip'])) {
            $metadata['ip'] = $properties['ip'];
        }
        if (isset($properties['old'])) {
            $metadata['old_values'] = $properties['old'];
        }
        if (isset($properties['new'])) {
            $metadata['new_values'] = $properties['new'];
        }
        if (isset($properties['causer_id'])) {
            $metadata['causer_id'] = $properties['causer_id'];
        }

        return [
            'id' => $activity->id,
            'type' => 'ip',
            'event_type' => $activity->event,
            'entity_type' => $this->extractEntityType($activity->subject_type),
            'entity_id' => $activity->subject_id,
            'user_id' => $userId,
            'metadata' => $metadata,
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    /**
     * Extract entity type from the subject_type class name.
     *
     * @param string|null $subjectType The fully qualified class name
     * @return string The simplified entity type
     */
    protected function extractEntityType(?string $subjectType): string
    {
        if (empty($subjectType)) {
            return 'Unknown';
        }

        // Extract just the class name from the full namespace
        $parts = explode('\\', $subjectType);
        return end($parts);
    }
}
