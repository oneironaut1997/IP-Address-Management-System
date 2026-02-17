<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

/**
 * Class ActivityLogService
 *
 * Service layer for activity log business logic.
 * Handles retrieval and filtering of IP management activity logs.
 *
 * This service encapsulates all activity log-related business rules and separates
 * them from HTTP concerns in the controller layer.
 */
class ActivityLogService
{
    /**
     * Default number of items per page.
     */
    protected int $defaultPerPage = 50;

    /**
     * Maximum number of items per page.
     */
    protected int $maxPerPage = 100;

    /**
     * Get all activity logs with optional filtering and pagination.
     *
     * Supports filtering by event, user_id, subject_type, subject_id,
     * log_name, and date range (from/to).
     * Results are ordered by creation date (newest first).
     *
     * @param  array  $filters  Optional filters
     * @param  int  $perPage  Number of items per page (default: 50, max: 100)
     */
    public function getActivityLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        // Clamp perPage to reasonable bounds
        $perPage = min(max($perPage, 1), $this->maxPerPage);

        $query = Activity::query();

        // Apply filters
        $this->applyFilters($query, $filters);

        // Order by newest first
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Apply filters to the activity log query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyFilters($query, array $filters): void
    {
        // Filter by event type (ip.created, ip.updated, ip.deleted)
        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        // Filter by causer's user_id
        if (! empty($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id']);
        }

        // Filter by subject type (e.g., IPAddress)
        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        // Filter by subject ID
        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        // Filter by log name
        if (! empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        // Date range filter - from date
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        // Date range filter - to date
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }
    }

    /**
     * Build filter array from request input.
     *
     * @param  array  $input  The request input array
     * @return array The filtered array containing only valid filter keys
     */
    public function buildFilters(array $input): array
    {
        $allowedFilters = ['event', 'user_id', 'subject_type', 'subject_id', 'log_name', 'from', 'to'];
        $filters = [];

        foreach ($allowedFilters as $filter) {
            if (! empty($input[$filter])) {
                $filters[$filter] = $input[$filter];
            }
        }

        return $filters;
    }
}
