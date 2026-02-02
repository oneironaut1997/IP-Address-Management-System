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
            // Authenticate the user using the JWT token
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'USER_NOT_FOUND',
                        'message' => 'User not found',
                    ],
                ], 401);
            }

            // Forward user context to backend services via headers
            $request->headers->set('X-User-ID', $user->id);
            $request->headers->set('X-User-Role', $user->role ?? 'regular');

            // Also set the user on the request for any internal Laravel auth checks
            auth()->setUser($user);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Token has expired',
                ],
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_INVALID',
                    'message' => 'Token is invalid',
                ],
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_NOT_PROVIDED',
                    'message' => 'Authorization token not provided',
                ],
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Unauthorized',
                ],
            ], 401);
        }

        return $next($request);
    }
}
