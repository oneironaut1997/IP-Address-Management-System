<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

/**
 * Stateless JWT Authentication Middleware
 *
 * Validates JWT tokens WITHOUT database lookup.
 * Extracts user information directly from JWT claims.
 * This is appropriate for microservices architecture where
 * the auth service issues tokens and other services validate them.
 */
class StatelessJwtAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorizedResponse('Authorization token not provided');
        }

        try {
            // Parse token without database validation
            $payload = JWTAuth::setToken($token)->getPayload();
            
            // Extract user information from JWT claims
            $userId = $payload->get('sub');
            $role = $payload->get('role', 'regular');
            $email = $payload->get('email');
            
            if (!$userId) {
                return $this->unauthorizedResponse('Invalid token: missing subject');
            }

            // Set user context in request attributes for controllers
            $request->attributes->set('user_id', $userId);
            $request->attributes->set('user_role', $role);
            $request->attributes->set('user_email', $email);
            
            // Also set for Laravel Auth facade compatibility
            $request->attributes->set('jwt_payload', $payload);

        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token has expired');
        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token is invalid');
        } catch (JWTException $e) {
            return $this->unauthorizedResponse('Token could not be parsed: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * Extract JWT token from Authorization header.
     *
     * @param  Request  $request
     * @return string|null
     */
    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return null;
        }

        // Expect format: "Bearer <token>"
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Return unauthorized JSON response.
     *
     * @param  string  $message
     * @return Response
     */
    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message,
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
