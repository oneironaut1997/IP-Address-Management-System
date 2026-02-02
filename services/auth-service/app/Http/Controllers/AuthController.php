<?php

namespace App\Http\Controllers;

use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class AuthController
 *
 * Handles all authentication-related operations including registration,
 * login, logout, token refresh, and user profile retrieval.
 *
 * Implements JWT-based authentication with refresh token rotation
 * and Redis-based session storage for enhanced security.
 */
class AuthController extends Controller
{
    /**
     * Token time-to-live in minutes.
     * Access tokens expire after 1 hour.
     */
    protected int $accessTokenTtl = 60;

    /**
     * Refresh token time-to-live in minutes.
     * Refresh tokens expire after 7 days.
     */
    protected int $refreshTokenTtl = 10080;

    /**
     * Register a new user.
     *
     * Creates a new user account with the provided email and password.
     * Returns a success response upon completion.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'regular',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ], 201);
    }

    /**
     * Authenticate a user and issue JWT tokens.
     *
     * Validates credentials and generates both access and refresh tokens.
     * Stores the refresh token in Redis with 7-day TTL for validation.
     * Creates a user session record for audit purposes.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'The provided credentials are incorrect.',
                ],
            ], 401);
        }

        $user = JWTAuth::user();

        // Generate refresh token
        $refreshToken = JWTAuth::customClaims([
            'type' => 'refresh',
            'jti' => uniqid('refresh_', true),
        ])->fromUser($user);

        // Store refresh token in Redis with 7-day TTL
        $refreshJti = JWTAuth::setToken($refreshToken)->getPayload()->get('jti');
        Redis::setex(
            "refresh:{$refreshJti}",
            $this->refreshTokenTtl * 60,
            $user->id
        );

        // Create user session for audit
        $session = UserSession::create([
            'user_id' => $user->id,
            'token_jti' => $refreshJti,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now(),
            'expires_at' => now()->addMinutes($this->refreshTokenTtl),
        ]);

        // Fire login event for audit logging
        event(new UserLoggedIn($user, $session));

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Logout the authenticated user.
     *
     * Invalidates the current access token and removes the associated
     * refresh token from Redis. Fires logout event for audit logging.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = JWTAuth::user();

        if ($user) {
            // Parse the token from the request
            $token = $this->getTokenFromRequest($request);
            if ($token) {
                JWTAuth::setToken($token);
            }

            // Get the JTI from the current token
            $payload = JWTAuth::getPayload();
            $jti = $payload->get('jti');

            // Remove refresh token from Redis if exists
            $refreshKeys = Redis::keys('refresh:*');
            foreach ($refreshKeys as $key) {
                $keyValue = str_replace(config('database.redis.options.prefix'), '', $key);
                if (Redis::get($keyValue) === $user->id) {
                    Redis::del($keyValue);
                }
            }

            // Fire logout event for audit logging
            event(new UserLoggedOut($user));

            // Invalidate the access token
            JWTAuth::invalidate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Refresh the access token using a refresh token.
     *
     * Validates the refresh token against Redis storage, generates new
     * token pair, invalidates the old refresh token, and stores the new
     * one in Redis. Implements token rotation for enhanced security.
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            // Get refresh token from Authorization header
            $refreshToken = $this->getTokenFromRequest($request);

            if (! $refreshToken) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'REFRESH_TOKEN_REQUIRED',
                        'message' => 'Refresh token is required.',
                    ],
                ], 401);
            }

            // Parse and validate the refresh token
            $token = JWTAuth::setToken($refreshToken);
            $payload = $token->getPayload();

            // Verify this is a refresh token
            if ($payload->get('type') !== 'refresh') {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_TOKEN_TYPE',
                        'message' => 'Token is not a valid refresh token.',
                    ],
                ], 401);
            }

            $refreshJti = $payload->get('jti');

            // Validate against Redis
            $userId = Redis::get("refresh:{$refreshJti}");

            if (! $userId) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_REFRESH_TOKEN',
                        'message' => 'Refresh token is invalid or expired.',
                    ],
                ], 401);
            }

            // Get user and verify existence
            $user = User::find($userId);

            if (! $user) {
                Redis::del("refresh:{$refreshJti}");

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'USER_NOT_FOUND',
                        'message' => 'User associated with this token no longer exists.',
                    ],
                ], 401);
            }

            // Invalidate old refresh token
            Redis::del("refresh:{$refreshJti}");

            // Generate new tokens
            $newAccessToken = JWTAuth::fromUser($user);
            $newRefreshToken = JWTAuth::customClaims([
                'type' => 'refresh',
                'jti' => uniqid('refresh_', true),
            ])->fromUser($user);

            // Store new refresh token in Redis
            $newRefreshJti = JWTAuth::setToken($newRefreshToken)->getPayload()->get('jti');
            Redis::setex(
                "refresh:{$newRefreshJti}",
                $this->refreshTokenTtl * 60,
                $user->id
            );

            // Update user session
            UserSession::where('token_jti', $refreshJti)
                ->update([
                    'token_jti' => $newRefreshJti,
                    'last_activity' => now(),
                    'expires_at' => now()->addMinutes($this->refreshTokenTtl),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'access_token' => $newAccessToken,
                    'refresh_token' => $newRefreshToken,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_REFRESH_FAILED',
                    'message' => 'Could not refresh token. Please login again.',
                ],
            ], 401);
        }
    }

    /**
     * Get the authenticated user's profile.
     *
     * Returns the current user's information including id, email, and role.
     */
    public function me(): JsonResponse
    {
        $user = JWTAuth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ],
        ]);
    }

    /**
     * Extract the bearer token from the request.
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
