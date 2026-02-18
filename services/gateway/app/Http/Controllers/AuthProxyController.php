<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnifiedActivityLogCollection;
use App\Services\AuthProxyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Auth Proxy Controller
 *
 * Proxies authentication requests to the auth-service.
 * Handles login, register, refresh, logout, and user info endpoints.
 * Also provides unified audit log endpoint combining auth and IP activities.
 *
 * Responsibilities:
 * - Request validation (via Form Requests)
 * - HTTP response formatting with cookies
 * - Delegating proxy logic to service layer
 */
class AuthProxyController extends Controller
{
    /**
     * Cookie configuration constants
     */
    private const ACCESS_TOKEN_COOKIE = 'access_token';

    private const REFRESH_TOKEN_COOKIE = 'refresh_token';

    private const COOKIE_PATH = '/';

    private const COOKIE_DOMAIN = null;

    private const ACCESS_TOKEN_TTL = 60; // minutes

    private const REFRESH_TOKEN_TTL = 10080; // minutes (7 days)

    /**
     * @param  AuthProxyService  $authProxyService  The authentication proxy service
     */
    public function __construct(
        protected AuthProxyService $authProxyService
    ) {}

    /**
     * Handle user login
     *
     * Proxies login request to auth-service and returns tokens via cookies.
     *
     * @param  Request  $request  The HTTP request
     * @return JsonResponse The authentication response with cookies
     */
    public function login(Request $request): JsonResponse
    {
        $response = $this->authProxyService->login($request);
        $responseData = $response->json();

        if ($response->successful() && isset($responseData['data']['tokens'])) {
            $tokens = $responseData['data']['tokens'];
            $cookies = $this->createTokenCookies(
                $tokens['access_token'],
                $tokens['refresh_token']
            );

            $jsonResponse = response()->json($responseData, $response->status());

            foreach ($cookies as $cookie) {
                $jsonResponse->withCookie($cookie);
            }

            return $jsonResponse;
        }

        return response()->json($responseData, $response->status());
    }

    /**
     * Handle user registration
     *
     * Proxies registration request to auth-service.
     *
     * @param  Request  $request  The HTTP request
     * @return JsonResponse The registration response
     */
    public function register(Request $request): JsonResponse
    {
        $response = $this->authProxyService->register($request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Handle token refresh
     *
     * Proxies token refresh request to auth-service and returns new cookies.
     *
     * @param  Request  $request  The HTTP request
     * @return JsonResponse The new token pair with cookies
     */
    public function refresh(Request $request): JsonResponse
    {
        // Try to get refresh token from cookie first
        $refreshToken = $request->cookie(self::REFRESH_TOKEN_COOKIE);

        // If no cookie, try header (for backward compatibility)
        if (! $refreshToken) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $refreshToken = substr($authHeader, 7);
            }
        }

        // Make request with cookie if available
        if ($refreshToken) {
            $proxyResponse = $this->authProxyService->refreshTokenWithCookie($request, $refreshToken);
        } else {
            $proxyResponse = $this->authProxyService->refreshToken($request);
        }

        $responseData = $proxyResponse->json();

        if ($proxyResponse->successful() && isset($responseData['data'])) {
            $tokens = $responseData['data'];
            if (isset($tokens['access_token']) && isset($tokens['refresh_token'])) {
                $cookies = $this->createTokenCookies(
                    $tokens['access_token'],
                    $tokens['refresh_token']
                );

                $jsonResponse = response()->json($responseData, $proxyResponse->status());

                foreach ($cookies as $cookie) {
                    $jsonResponse->withCookie($cookie);
                }

                return $jsonResponse;
            }
        }

        return response()->json($responseData, $proxyResponse->status());
    }

    /**
     * Handle user logout
     *
     * Proxies logout request to auth-service and clears cookies.
     *
     * @param  Request  $request  The HTTP request with Authorization header and user context
     * @return JsonResponse The logout confirmation
     */
    public function logout(Request $request): JsonResponse
    {
        $response = $this->authProxyService->logout($request);

        $jsonResponse = response()->json(
            $response->json(),
            $response->status()
        );

        // Clear authentication cookies
        return $this->clearTokenCookies($jsonResponse);
    }

    /**
     * Get authenticated user info
     *
     * Proxies user info request to auth-service with user context.
     *
     * @param  Request  $request  The HTTP request with Authorization header and user context
     * @return JsonResponse The user data
     */
    public function me(Request $request): JsonResponse
    {
        $response = $this->authProxyService->getUserInfo($request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Get unified audit logs
     *
     * Combines audit logs from auth-service (login/logout events) and
     * IP management activities (ip.created, ip.updated, ip.deleted) into
     * a single, chronologically sorted response.
     *
     * Query Parameters:
     * - type: Filter by type ('auth', 'ip', 'all') - default: 'all'
     * - event_type: Filter by event type
     * - user_id: Filter by user ID
     * - from: Filter from date
     * - to: Filter to date
     * - page: Page number
     * - per_page: Items per page (max 100)
     *
     * @param  Request  $request  The HTTP request with Authorization header and query params
     * @return JsonResponse The unified audit logs data
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $result = $this->authProxyService->getUnifiedAuditLogs($request);

        return response()->json(
            new UnifiedActivityLogCollection($result['data'], $result['meta'])
        );
    }

    /**
     * Get IP management activity logs
     *
     * Proxies activity log request to ip-management service.
     *
     * @param  Request  $request  The HTTP request with Authorization header and query params
     * @return JsonResponse The IP activity logs data
     */
    public function ipActivities(Request $request): JsonResponse
    {
        $response = $this->authProxyService->getIPActivities($request);

        return response()->json(
            $response->json(),
            $response->status()
        );
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
     * @param  JsonResponse  $response  The response to attach cookies to
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
