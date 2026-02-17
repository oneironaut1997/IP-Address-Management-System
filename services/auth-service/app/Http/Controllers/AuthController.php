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

        return response()->json(
            new AuthResponseResource([
                'user' => $result['user'],
                'tokens' => $result['tokens'],
                'message' => 'Login successful',
            ])
        );
    }

    /**
     * Logout the authenticated user.
     *
     * Invalidates the current access token and removes the associated
     * refresh token from Redis.
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

            $payload = JWTAuth::getPayload();
            $jti = $payload->get('jti');

            $this->authService->logout($user, $jti);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Refresh the access token using a refresh token.
     *
     * Validates the refresh token, generates new token pair,
     * and implements token rotation for enhanced security.
     *
     * @param  Request  $request  The HTTP request with refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $this->authService->extractBearerToken($request);

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

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => (new AuthTokenResource($result['tokens']))->toArray($request),
        ]);
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
}
