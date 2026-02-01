<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class UserLoggedOut
 *
 * Event fired when a user logs out.
 * Used for audit logging and session cleanup tracking.
 *
 * @package App\Events
 */
class UserLoggedOut
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who logged out.
     *
     * @var User
     */
    public User $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
