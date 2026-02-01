<?php

namespace App\Listeners;

use App\Events\UserLoggedOut;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class LogUserLogout
 *
 * Listener for UserLoggedOut event that creates an audit log entry.
 *
 * @package App\Listeners
 */
class LogUserLogout implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * Creates an immutable audit log entry for the logout event.
     *
     * @param UserLoggedOut $event
     * @return void
     */
    public function handle(UserLoggedOut $event): void
    {
        $user = $event->user;

        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'logout',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'metadata' => [
                'timestamp' => now()->toIso8601String(),
            ],
            'session_id' => null,
        ]);
    }
}
