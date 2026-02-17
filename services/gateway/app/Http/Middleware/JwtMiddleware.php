<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * JWT Authentication Middleware
 *
 * Validates JWT tokens on incoming requests and forwards user context
 * to backend services via headers (X-User-ID, X-User-Role).
 */
class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates the JWT token from the Authorization header and extracts
     * user context to forward to backend services.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure  $next  The next middleware in the pipeline
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Log the JWT configuration
            $jwtSecret = config('jwt.secret');

            // Get the raw token
            $authHeader = $request->header('Authorization');
            if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'TOKEN_NOT_PROVIDED',
                        'message' => 'Authorization token not provided',
                    ],
                ], 401);
            }

            $tokenString = substr($authHeader, 7);

            // Try to set the token and get payload
            JWTAuth::setToken($tokenString);

            // Get payload
            $payload = JWTAuth::getPayload();

            // Manually check expiration
            $exp = $payload->get('exp');
            if ($exp && $exp < now()->timestamp) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'TOKEN_EXPIRED',
                        'message' => 'Token has expired',
                    ],
                ], 401);
            }

            // Extract user data
            $userId = $payload->get('sub');
            $userRole = $payload->get('role') ?? 'regular';
            $userEmail = $payload->get('email');

            if (! $userId) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'USER_NOT_FOUND',
                        'message' => 'User not found in token',
                    ],
                ], 401);
            }

            // Forward user context
            $request->headers->set('X-User-ID', $userId);
            $request->headers->set('X-User-Role', $userRole);
            $request->headers->set('X-User-Email', $userEmail);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Token has expired: '.$e->getMessage(),
                ],
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_INVALID',
                    'message' => 'Token is invalid: '.$e->getMessage(),
                ],
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_ERROR',
                    'message' => 'Token error: '.$e->getMessage(),
                ],
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Error: '.$e->getMessage(),
                ],
            ], 401);
        }

        return $next($request);
    }
}
