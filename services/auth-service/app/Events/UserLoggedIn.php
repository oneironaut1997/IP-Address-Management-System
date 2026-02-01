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
 *
 * @package App\Events
 */
class UserLoggedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The authenticated user.
     *
     * @var User
     */
    public User $user;

    /**
     * The user session created during login.
     *
     * @var UserSession
     */
    public UserSession $session;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param UserSession $session
     */
    public function __construct(User $user, UserSession $session)
    {
        $this->user = $user;
        $this->session = $session;
    }
}
