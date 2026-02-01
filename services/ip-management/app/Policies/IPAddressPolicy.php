<?php

namespace App\Policies;

use App\Models\IPAddress;

/**
 * Class IPAddressPolicy
 *
 * Defines authorization rules for IP address operations.
 * Implements role-based access control:
 * - All authenticated users: view, create
 * - Owner or super_admin: update
 * - super_admin only: delete
 *
 * @package App\Policies
 */
class IPAddressPolicy
{
    /**
     * Determine whether the user can view any IP addresses.
     *
     * All authenticated users can view all IP addresses.
     *
     * @param string $userId
     * @return bool
     */
    public function viewAny(string $userId): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the IP address.
     *
     * All authenticated users can view any IP address.
     *
     * @param string $userId
     * @param IPAddress $ipAddress
     * @return bool
     */
    public function view(string $userId, IPAddress $ipAddress): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create IP addresses.
     *
     * All authenticated users can create IP addresses.
     *
     * @param string $userId
     * @return bool
     */
    public function create(string $userId): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the IP address.
     *
     * Only the owner or super_admin can update an IP address.
     *
     * @param string $userId
     * @param IPAddress $ipAddress
     * @param string|null $userRole
     * @return bool
     */
    public function update(string $userId, IPAddress $ipAddress, ?string $userRole = null): bool
    {
        // Owner can update their own IP
        if ($ipAddress->user_id === $userId) {
            return true;
        }

        // Super admin can update any IP
        if ($userRole === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the IP address.
     *
     * Only super_admin can delete IP addresses.
     *
     * @param string $userId
     * @param IPAddress $ipAddress
     * @param string|null $userRole
     * @return bool
     */
    public function delete(string $userId, IPAddress $ipAddress, ?string $userRole = null): bool
    {
        // Only super admin can delete
        return $userRole === 'super_admin';
    }

    /**
     * Determine whether the user can restore the IP address.
     *
     * Only super_admin can restore deleted IP addresses.
     *
     * @param string $userId
     * @param IPAddress $ipAddress
     * @param string|null $userRole
     * @return bool
     */
    public function restore(string $userId, IPAddress $ipAddress, ?string $userRole = null): bool
    {
        // Only super admin can restore
        return $userRole === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the IP address.
     *
     * Only super_admin can force delete IP addresses.
     *
     * @param string $userId
     * @param IPAddress $ipAddress
     * @param string|null $userRole
     * @return bool
     */
    public function forceDelete(string $userId, IPAddress $ipAddress, ?string $userRole = null): bool
    {
        // Only super admin can force delete
        return $userRole === 'super_admin';
    }
}
