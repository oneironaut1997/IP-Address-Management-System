<?php

namespace App\Listeners;

use App\Events\UserLoggedOut;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class LogUserLogout
 *
 * Listener for UserLoggedOut event that creates an activity log entry.
 * Uses Spatie Activity Log for consistent audit logging across services.
 */
class LogUserLogout implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * Creates an activity log entry for the logout event using Spatie Activity Log.
     */
    public function handle(UserLoggedOut $event): void
    {
        $user = $event->user;

        // Log activity using Spatie Activity Log
        activity()
            ->performedOn($user)
            ->event('auth.logout')
            ->withProperties([
                'timestamp' => now()->toIso8601String(),
            ])
            ->tap(function ($activity) use ($user) {
                $activity->causer_id = $user->id;
                $activity->causer_type = null;
            })
            ->log('User logged out successfully');
    }
}
