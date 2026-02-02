<?php

namespace App\Events;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class UserLoggedIn
 *
 * Event fired when a user successfully logs in.
 * Used for audit logging and session tracking.
 */
class UserLoggedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The authenticated user.
     */
    public User $user;

    /**
     * The user session created during login.
     */
    public UserSession $session;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, UserSession $session)
    {
        $this->user = $user;
        $this->session = $session;
    }
}
