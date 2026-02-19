<?php

namespace App\Services;

use App\Models\IPAddress;
use App\Models\IPHistory;
use PhpIP\IP;

/**
 * Class IPService
 *
 * Service layer for IP address management business logic.
 * Handles CRUD operations, validation, history tracking, and audit logging.
 *
 * This service encapsulates all IP management business rules and separates
 * them from HTTP concerns in the controller layer.
 *
 * Note: Authorization is handled by IPAddressPolicy, not this service.
 */
class IPService
{
    /**
     * Get all IP addresses with pagination and optional filtering.
     *
     * Uses pagination to prevent memory issues with large datasets.
     * Uses select() to limit columns fetched for better performance.
     * Supports search by IP address, label, or comment (partial match).
     * Supports filtering by IP type (ipv4/ipv6) and user_id.
     *
     * @param  int  $perPage  Number of items per page (default: 20, max: 100)
     * @param  array  $filters  Optional filters (search, type, user_id)
     * @return array{data: \Illuminate\Contracts\Pagination\LengthAwarePaginator, ipv4_count: int, ipv6_count: int}
     */
    public function getAllIPAddresses(int $perPage = 20, array $filters = [])
    {
        // Clamp perPage to reasonable bounds
        $perPage = min(max($perPage, 1), 100);

        $query = IPAddress::select(['id', 'user_id', 'ip_address', 'label', 'comment', 'type', 'created_at', 'updated_at'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        $this->applyFilters($query, $filters);

        $paginatedResult = $query->paginate($perPage);

        // Get type counts (total, not just current page)
        // When a type filter is applied, the count for that type equals the total
        if (! empty($filters['type'])) {
            $ipv4Count = $filters['type'] === 'ipv4' ? $paginatedResult->total() : 0;
            $ipv6Count = $filters['type'] === 'ipv6' ? $paginatedResult->total() : 0;
        } else {
            // Get counts from database for all IPs (respecting search filter if present)
            $baseQuery = IPAddress::query();
            if (! empty($filters['search'])) {
                $search = $filters['search'];
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('ip_address', 'LIKE', "%{$search}%")
                        ->orWhere('label', 'LIKE', "%{$search}%")
                        ->orWhere('comment', 'LIKE', "%{$search}%");
                });
            }
            $ipv4Count = (clone $baseQuery)->where('type', 'ipv4')->count();
            $ipv6Count = (clone $baseQuery)->where('type', 'ipv6')->count();
        }

        return [
            'data' => $paginatedResult,
            'ipv4_count' => $ipv4Count,
            'ipv6_count' => $ipv6Count,
        ];
    }

    /**
     * Apply filters to the IP address query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $filters  Filters to apply (search, type, user_id)
     */
    protected function applyFilters($query, array $filters): void
    {
        // Search by IP address, label, or comment (partial match)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'LIKE', "%{$search}%")
                    ->orWhere('label', 'LIKE', "%{$search}%")
                    ->orWhere('comment', 'LIKE', "%{$search}%");
            });
        }

        // Filter by IP type (ipv4/ipv6)
        if (! empty($filters['type']) && in_array($filters['type'], ['ipv4', 'ipv6'], true)) {
            $query->where('type', $filters['type']);
        }

        // Filter by user_id (owner)
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
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
        $allowedFilters = ['search', 'type', 'user_id'];
        $filters = [];

        foreach ($allowedFilters as $filter) {
            if (! empty($input[$filter])) {
                $filters[$filter] = $input[$filter];
            }
        }

        return $filters;
    }

    /**
     * Create a new IP address.
     *
     * Validates IP format using php-ip library.
     * Logs activity for audit trail.
     *
     * @param  array  $data  Validated IP address data
     * @param  string  $userId  The ID of the user creating the IP
     * @return array{success: bool, ip: IPAddress|null, error: array|null}
     */
    public function createIPAddress(array $data, string $userId): array
    {
        // Validate and detect IP type using php-ip library
        try {
            $ip = IP::create($data['ip_address']);
            $type = $ip->getVersion() === 4 ? 'ipv4' : 'ipv6';
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'ip' => null,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid IP address format.',
                ],
            ];
        }

        // create() already returns the model with timestamps - no need for fresh()
        $ipAddress = IPAddress::create([
            'user_id' => $userId,
            'ip_address' => $data['ip_address'],
            'label' => $data['label'],
            'comment' => $data['comment'] ?? null,
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

        return [
            'success' => true,
            'ip' => $ipAddress,
            'error' => null,
        ];
    }

    /**
     * Get an IP address by ID with its history.
     *
     * @param  string  $id  The IP address ID
     */
    public function getIPAddressById(string $id): ?IPAddress
    {
        return IPAddress::select(['id', 'user_id', 'ip_address', 'label', 'comment', 'type', 'created_at', 'updated_at', 'deleted_at'])
            ->with('history')
            ->find($id);
    }

    /**
     * Update an IP address.
     *
     * Tracks changes in both history table and activity log.
     * OPTIMIZED: Uses fresh() only once after update.
     *
     * @param  IPAddress  $ipAddress  The IP address to update
     * @param  array  $data  Validated update data
     * @param  string  $userId  The ID of the user making the update
     * @return IPAddress The updated IP address
     */
    public function updateIPAddress(IPAddress $ipAddress, array $data, string $userId): IPAddress
    {
        // Capture old values before update
        $oldValues = $ipAddress->toArray();

        $ipAddress->update($data);

        // Get fresh instance ONCE for all operations
        $freshIpAddress = $ipAddress->fresh();
        $newValues = $freshIpAddress->toArray();

        // Log the update in history table
        IPHistory::create([
            'ip_address_id' => $ipAddress->id,
            'modified_by' => $userId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'action' => 'updated',
        ]);

        // Log activity using Spatie Activity Log
        activity()
            ->performedOn($freshIpAddress)
            ->event('ip.updated')
            ->withProperties([
                'old' => $oldValues,
                'new' => $newValues,
                'causer_id' => $userId,
            ])
            ->tap(function ($activity) use ($userId) {
                $activity->causer_id = $userId;
                $activity->causer_type = null;
            })
            ->log('ip.updated');

        return $freshIpAddress;
    }

    /**
     * Delete an IP address (soft delete).
     *
     * Logs the deletion for audit trail.
     *
     * @param  IPAddress  $ipAddress  The IP address to delete
     * @param  string  $userId  The ID of the user performing the deletion
     */
    public function deleteIPAddress(IPAddress $ipAddress, string $userId): void
    {
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
    }

    /**
     * Get the change history for a specific IP address.
     *
     * @param  string  $id  The IP address ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getIPAddressHistory(string $id)
    {
        return IPHistory::where('ip_address_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if a user can update an IP address.
     *
     * Owners and super_admins can update IP addresses.
     *
     * @param  IPAddress  $ipAddress  The IP address to check
     * @param  string  $userId  The ID of the user
     * @param  string  $role  The role of the user
     * @return bool True if user can update, false otherwise
     */
    public function canUpdate(IPAddress $ipAddress, string $userId, string $role): bool
    {
        // Owner can update their own IP
        if ($ipAddress->user_id === $userId) {
            return true;
        }

        // Super admin can update any IP
        if ($role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Check if a user can delete an IP address.
     *
     * Only super_admins can delete IP addresses.
     *
     * @param  string  $role  The role of the user
     * @return bool True if user can delete, false otherwise
     */
    public function canDelete(string $role): bool
    {
        return $role === 'super_admin';
    }
}
