<?php

namespace App\Services;

use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class AuthService
 *
 * Service layer for authentication-related business logic.
 * Handles user registration, login, logout, token refresh, and profile management.
 *
 * This service encapsulates all authentication business rules and separates
 * them from HTTP concerns in the controller layer.
 */
class AuthService
{
    /**
     * Access token time-to-live in minutes.
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
     * Creates a new user account with the provided data.
     * Assigns the default 'regular' role to new users.
     *
     * @param  array  $data  User data containing email and password
     * @return User The newly created user instance
     */
    public function register(array $data): User
    {
        return User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'regular',
        ]);
    }

    /**
     * Authenticate a user and issue JWT tokens.
     *
     * Validates credentials against stored user data.
     * Generates both access and refresh tokens.
     * Stores the refresh token in Redis for validation.
     * Creates a user session record for audit purposes.
     *
     * @param  array  $credentials  Contains 'email' and 'password'
     * @param  Request  $request  The HTTP request for IP and user agent info
     * @return array{success: bool, user: User|null, tokens: array|null, error: string|null}
     */
    public function login(array $credentials, Request $request): array
    {
        if (! $token = JWTAuth::attempt($credentials)) {
            return [
                'success' => false,
                'user' => null,
                'tokens' => null,
                'error' => 'INVALID_CREDENTIALS',
            ];
        }

        $user = JWTAuth::user();

        // Generate refresh token with unique identifier
        $refreshToken = JWTAuth::customClaims([
            'type' => 'refresh',
            'jti' => uniqid('refresh_', true),
        ])->fromUser($user);

        // Store refresh token in Redis with TTL
        $refreshJti = JWTAuth::setToken($refreshToken)->getPayload()->get('jti');
        Redis::setex(
            "refresh:{$refreshJti}",
            $this->refreshTokenTtl * 60,
            $user->id
        );

        // Also store in user's token set for efficient logout
        Redis::sadd("user:{$user->id}:refresh_tokens", $refreshJti);
        // Set expiry on the set to match refresh token TTL
        Redis::expire("user:{$user->id}:refresh_tokens", $this->refreshTokenTtl * 60);

        // Create user session for audit trail
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

        return [
            'success' => true,
            'user' => $user,
            'tokens' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ],
            'error' => null,
        ];
    }

    /**
     * Logout the authenticated user.
     *
     * Invalidates the current access token and removes the associated
     * refresh tokens from Redis. Uses Redis sets for O(1) lookups
     * instead of O(N) key scanning.
     * Fires logout event for audit logging.
     *
     * @param  User  $user  The authenticated user
     * @param  string|null  $jti  The JWT ID from the current token
     */
    public function logout(User $user, ?string $jti = null): void
    {
        // Get all refresh tokens for this user from the set (O(1) operation)
        $userTokenSet = "user:{$user->id}:refresh_tokens";
        $refreshJtis = Redis::smembers($userTokenSet);

        // Delete each refresh token
        foreach ($refreshJtis as $refreshJti) {
            Redis::del("refresh:{$refreshJti}");
        }

        // Delete the user's token set
        Redis::del($userTokenSet);

        // Fire logout event for audit logging
        event(new UserLoggedOut($user));

        // Invalidate the access token
        JWTAuth::invalidate();
    }

    /**
     * Refresh the access token using a refresh token.
     *
     * Validates the refresh token against Redis storage.
     * Generates new token pair (access + refresh).
     * Invalidates the old refresh token.
     * Stores the new refresh token in Redis.
     *
     * @param  string  $refreshToken  The refresh token from Authorization header
     * @return array{success: bool, tokens: array|null, error: array|null}
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            // Parse and validate the refresh token
            $token = JWTAuth::setToken($refreshToken);
            $payload = $token->getPayload();

            // Verify this is a refresh token
            if ($payload->get('type') !== 'refresh') {
                return [
                    'success' => false,
                    'tokens' => null,
                    'error' => [
                        'code' => 'INVALID_TOKEN_TYPE',
                        'message' => 'Token is not a valid refresh token.',
                    ],
                ];
            }

            $refreshJti = $payload->get('jti');

            // Validate against Redis
            $userId = Redis::get("refresh:{$refreshJti}");

            if (! $userId) {
                return [
                    'success' => false,
                    'tokens' => null,
                    'error' => [
                        'code' => 'INVALID_REFRESH_TOKEN',
                        'message' => 'Refresh token is invalid or expired.',
                    ],
                ];
            }

            // Get user and verify existence
            $user = User::find($userId);

            if (! $user) {
                Redis::del("refresh:{$refreshJti}");

                return [
                    'success' => false,
                    'tokens' => null,
                    'error' => [
                        'code' => 'USER_NOT_FOUND',
                        'message' => 'User associated with this token no longer exists.',
                    ],
                ];
            }

            // Invalidate old refresh token
            Redis::del("refresh:{$refreshJti}");
            // Remove from user's token set
            Redis::srem("user:{$user->id}:refresh_tokens", $refreshJti);

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

            // Add to user's token set for efficient logout
            Redis::sadd("user:{$user->id}:refresh_tokens", $newRefreshJti);
            Redis::expire("user:{$user->id}:refresh_tokens", $this->refreshTokenTtl * 60);

            // Update user session
            UserSession::where('token_jti', $refreshJti)
                ->update([
                    'token_jti' => $newRefreshJti,
                    'last_activity' => now(),
                    'expires_at' => now()->addMinutes($this->refreshTokenTtl),
                ]);

            return [
                'success' => true,
                'tokens' => [
                    'access_token' => $newAccessToken,
                    'refresh_token' => $newRefreshToken,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                ],
                'error' => null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'tokens' => null,
                'error' => [
                    'code' => 'TOKEN_REFRESH_FAILED',
                    'message' => 'Could not refresh token. Please login again.',
                ],
            ];
        }
    }

    /**
     * Get the authenticated user's profile.
     *
     * Returns the user's information including id, email, and role.
     *
     * @param  User  $user  The authenticated user
     * @return array The user profile data
     */
    public function getUserProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Extract the bearer token from the request.
     *
     * @param  Request  $request  The HTTP request
     * @return string|null The bearer token or null if not present
     */
    public function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
