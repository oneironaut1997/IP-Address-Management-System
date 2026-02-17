<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class LogUserLogin
 *
 * Listener for UserLoggedIn event that creates an activity log entry.
 * Uses Spatie Activity Log for consistent audit logging across services.
 */
class LogUserLogin implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * Creates an activity log entry for the login event using Spatie Activity Log.
     */
    public function handle(UserLoggedIn $event): void
    {
        $user = $event->user;
        $session = $event->session;

        // Log activity using Spatie Activity Log
        activity()
            ->performedOn($user)
            ->event('auth.login')
            ->withProperties([
                'session_id' => $session->id,
                'token_jti' => $session->token_jti,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'timestamp' => now()->toIso8601String(),
            ])
            ->tap(function ($activity) use ($user) {
                $activity->causer_id = $user->id;
                $activity->causer_type = null;
            })
            ->log('User logged in successfully');
    }
}
