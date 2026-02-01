<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class LogUserLogin
 *
 * Listener for UserLoggedIn event that creates an audit log entry.
 *
 * @package App\Listeners
 */
class LogUserLogin implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * Creates an immutable audit log entry for the login event.
     *
     * @param UserLoggedIn $event
     * @return void
     */
    public function handle(UserLoggedIn $event): void
    {
        $user = $event->user;
        $session = $event->session;

        AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'login',
            'entity_type' => 'Session',
            'entity_id' => $session->id,
            'metadata' => [
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'timestamp' => now()->toIso8601String(),
            ],
            'session_id' => $session->token_jti,
        ]);
    }
}
