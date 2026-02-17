<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Class AuditService
 *
 * Service layer for audit log business logic.
 * Handles retrieval, filtering, and transformation of authentication audit logs.
 *
 * This service encapsulates all audit-related business rules and separates
 * them from HTTP concerns in the controller layer.
 *
 * Uses Spatie Activity Log for consistent audit logging across services.
 */
class AuditService
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
     * Get all audit logs with optional filtering and pagination.
     *
     * Supports filtering by event, user_id, subject_type, and date range.
     * Results are ordered by creation date (newest first).
     *
     * @param  array  $filters  Optional filters (event, user_id, subject_type, from, to)
     * @param  int  $perPage  Number of items per page (default: 50, max: 100)
     * @return LengthAwarePaginator
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator
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
     * Get a single audit log by ID.
     *
     * @param  string  $id  The UUID of the audit log
     * @return Activity|null
     */
    public function getAuditLogById(string $id): ?Activity
    {
        return Activity::find($id);
    }

    /**
     * Get available event types for filtering.
     *
     * Returns a list of unique event types stored in the audit logs.
     *
     * @return Collection
     */
    public function getEventTypes(): Collection
    {
        return Activity::distinct()
            ->orderBy('event')
            ->pluck('event');
    }

    /**
     * Apply filters to the audit log query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $filters
     * @return void
     */
    protected function applyFilters($query, array $filters): void
    {
        // Filter by event type (auth.login, auth.logout)
        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        // Filter by event_type alias for backward compatibility
        if (!empty($filters['event_type'])) {
            $query->where('event', $filters['event_type']);
        }

        // Filter by causer's user_id
        if (!empty($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id']);
        }

        // Filter by subject type
        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        // Filter by subject_type alias for backward compatibility
        if (!empty($filters['entity_type'])) {
            $query->where('subject_type', $filters['entity_type']);
        }

        // Filter by subject ID
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        // Date range filter - from date
        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        // Date range filter - to date
        if (!empty($filters['to'])) {
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
        $allowedFilters = ['event', 'event_type', 'user_id', 'subject_type', 'entity_type', 'subject_id', 'from', 'to'];
        $filters = [];

        foreach ($allowedFilters as $filter) {
            if (!empty($input[$filter])) {
                $filters[$filter] = $input[$filter];
            }
        }

        return $filters;
    }
}
