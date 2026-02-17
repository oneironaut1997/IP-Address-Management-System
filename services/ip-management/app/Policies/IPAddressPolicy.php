<?php

namespace App\Policies;

use App\Models\IPAddress;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class IPAddressPolicy
 *
 * Defines authorization rules for IP address operations.
 * Implements role-based access control:
 * - All authenticated users: view, create
 * - Owner or super_admin: update
 * - super_admin only: delete
 */
class IPAddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any IP addresses.
     *
     * All authenticated users can view all IP addresses.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the IP address.
     *
     * All authenticated users can view any IP address.
     */
    public function view(User $user, IPAddress $ipAddress): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create IP addresses.
     *
     * All authenticated users can create IP addresses.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the IP address.
     *
     * Only the owner or super_admin can update an IP address.
     */
    public function update(User $user, IPAddress $ipAddress): bool
    {
        // Owner can update their own IP
        if ($ipAddress->user_id === $user->id) {
            return true;
        }

        // Super admin can update any IP
        if ($user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the IP address.
     *
     * Only super_admin can delete IP addresses.
     */
    public function delete(User $user, IPAddress $ipAddress): bool
    {
        // Only super admin can delete
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can restore the IP address.
     *
     * Only super_admin can restore deleted IP addresses.
     */
    public function restore(User $user, IPAddress $ipAddress): bool
    {
        // Only super admin can restore
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the IP address.
     *
     * Only super_admin can force delete IP addresses.
     */
    public function forceDelete(User $user, IPAddress $ipAddress): bool
    {
        // Only super admin can force delete
        return $user->role === 'super_admin';
    }
}
