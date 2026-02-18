<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthResponseResource;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class AuthController
 *
 * HTTP Controller for authentication-related endpoints.
 * Delegates business logic to AuthService and uses API Resources
 * for consistent response formatting.
 *
 * Responsibilities:
 * - Request validation (via Form Requests)
 * - HTTP response formatting
 * - Delegating business logic to service layer
 */
class AuthController extends Controller
{
    /**
     * Cookie configuration constants
     */
    private const ACCESS_TOKEN_COOKIE = 'access_token';

    private const REFRESH_TOKEN_COOKIE = 'refresh_token';

    private const COOKIE_PATH = '/';

    private const COOKIE_DOMAIN = null; // Use default domain

    private const ACCESS_TOKEN_TTL = 60; // minutes

    private const REFRESH_TOKEN_TTL = 10080; // minutes (7 days)

    /**
     * @param  AuthService  $authService  The authentication service
     */
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user.
     *
     * Creates a new user account with the provided email and password.
     * Returns a success response upon completion.
     *
     * @param  RegisterRequest  $request  Validated registration request
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json(
            new AuthResponseResource([
                'user' => $user,
                'tokens' => null,
                'message' => 'User registered successfully',
            ]),
            Response::HTTP_CREATED
        );
    }

    /**
     * Authenticate a user and issue JWT tokens.
     *
     * Validates credentials and generates both access and refresh tokens.
     * Sets tokens as httpOnly secure cookies for enhanced security.
     *
     * @param  LoginRequest  $request  Validated login request
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->only('email', 'password'),
            $request
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $result['error'],
                    'message' => 'The provided credentials are incorrect.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $cookies = $this->createTokenCookies(
            $result['tokens']['access_token'],
            $result['tokens']['refresh_token']
        );

        $response = response()->json(
            new AuthResponseResource([
                'user' => $result['user'],
                'tokens' => $result['tokens'],
                'message' => 'Login successful',
            ])
        );

        // Attach cookies to response
        foreach ($cookies as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Logout the authenticated user.
     *
     * Invalidates the current access token and removes the associated
     * refresh token from Redis. Also clears authentication cookies.
     *
     * @param  Request  $request  The HTTP request
     */
    public function logout(Request $request): JsonResponse
    {
        $user = JWTAuth::user();

        if ($user) {
            $token = $this->authService->extractBearerToken($request);
            if ($token) {
                JWTAuth::setToken($token);
            }

            try {
                $payload = JWTAuth::getPayload();
                $jti = $payload->get('jti');
                $this->authService->logout($user, $jti);
            } catch (\Exception $e) {
                // Token might already be invalid, continue with logout
            }
        }

        $response = response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);

        // Clear authentication cookies
        return $this->clearTokenCookies($response);
    }

    /**
     * Refresh the access token using a refresh token.
     *
     * Validates the refresh token, generates new token pair,
     * implements token rotation, and sets new httpOnly cookies.
     *
     * @param  Request  $request  The HTTP request with refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        // Try to get refresh token from cookie first, then from header
        $refreshToken = $request->cookie(self::REFRESH_TOKEN_COOKIE)
            ?? $this->authService->extractBearerToken($request);

        if (! $refreshToken) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REFRESH_TOKEN_REQUIRED',
                    'message' => 'Refresh token is required.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $result = $this->authService->refreshToken($refreshToken);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $cookies = $this->createTokenCookies(
            $result['tokens']['access_token'],
            $result['tokens']['refresh_token']
        );

        $response = response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => (new AuthTokenResource($result['tokens']))->toArray($request),
        ]);

        // Attach cookies to response
        foreach ($cookies as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
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
                'user' => (new UserResource($user))->toArray(request()),
            ],
        ]);
    }

    /**
     * Create httpOnly secure cookies for tokens.
     *
     * @param  string  $accessToken  The JWT access token
     * @param  string  $refreshToken  The JWT refresh token
     * @return array Array of cookie instances
     */
    private function createTokenCookies(string $accessToken, string $refreshToken): array
    {
        $isSecure = config('app.env') === 'production';

        return [
            cookie(
                self::ACCESS_TOKEN_COOKIE,
                $accessToken,
                self::ACCESS_TOKEN_TTL,
                self::COOKIE_PATH,
                self::COOKIE_DOMAIN,
                $isSecure,
                true, // httpOnly - JavaScript cannot access
                false,
                'Lax' // SameSite policy
            ),
            cookie(
                self::REFRESH_TOKEN_COOKIE,
                $refreshToken,
                self::REFRESH_TOKEN_TTL,
                self::COOKIE_PATH,
                self::COOKIE_DOMAIN,
                $isSecure,
                true, // httpOnly - JavaScript cannot access
                false,
                'Lax' // SameSite policy
            ),
        ];
    }

    /**
     * Clear authentication cookies.
     *
     * @param  \Illuminate\Http\JsonResponse  $response  The response to attach cookies to
     */
    private function clearTokenCookies(JsonResponse $response): JsonResponse
    {
        $isSecure = config('app.env') === 'production';

        $cookies = [
            cookie(
                self::ACCESS_TOKEN_COOKIE,
                '',
                -1, // Expire immediately
                self::COOKIE_PATH,
                self::COOKIE_DOMAIN,
                $isSecure,
                true,
                false,
                'Lax'
            ),
            cookie(
                self::REFRESH_TOKEN_COOKIE,
                '',
                -1, // Expire immediately
                self::COOKIE_PATH,
                self::COOKIE_DOMAIN,
                $isSecure,
                true,
                false,
                'Lax'
            ),
        ];

        foreach ($cookies as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }
}
