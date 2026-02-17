<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gateway Authentication Middleware
 *
 * Validates that requests come from the gateway with proper user context headers.
 * Creates a transient User model from headers for policy authorization.
 *
 * This middleware provides defense-in-depth by validating the gateway headers
 * and enabling Laravel's policy-based authorization system.
 */
class GatewayAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates gateway headers and creates a user context for authorization.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure  $next  The next middleware in the pipeline
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get user context from gateway headers
        $userId = $request->header('X-User-ID');
        $userRole = $request->header('X-User-Role');
        $userEmail = $request->header('X-User-Email');

        // Validate required headers
        if (! $userId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Missing user context. Request must come through the API gateway.',
                ],
            ], 401);
        }

        // Create a transient User model for authorization
        $user = new User();
        $user->id = $userId;
        $user->role = $userRole ?? 'regular';
        $user->email = $userEmail ?? '';

        // Set the user as the current authenticated user
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
