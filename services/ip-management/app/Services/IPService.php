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
     * Get all IP addresses with pagination.
     *
     * Uses pagination to prevent memory issues with large datasets.
     * Eager loads history relationship for N+1 query prevention.
     *
     * @param  int  $perPage  Number of items per page (default: 20, max: 100)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllIPAddresses(int $perPage = 20)
    {
        // Clamp perPage to reasonable bounds
        $perPage = min(max($perPage, 1), 100);

        return IPAddress::with('history')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
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
        return IPAddress::with('history')->find($id);
    }

    /**
     * Update an IP address.
     *
     * Tracks changes in both history table and activity log.
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

        return $ipAddress->fresh();
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
