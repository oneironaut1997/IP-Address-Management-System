<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginProxyRequest;
use App\Http\Requests\RefreshTokenProxyRequest;
use App\Http\Requests\RegisterProxyRequest;
use App\Http\Resources\UnifiedAuditLogCollection;
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
 * - HTTP response formatting
 * - Delegating proxy logic to service layer
 */
class AuthProxyController extends Controller
{
    /**
     * @param  AuthProxyService  $authProxyService  The authentication proxy service
     */
    public function __construct(
        protected AuthProxyService $authProxyService
    ) {}

    /**
     * Handle user login
     *
     * Proxies login request to auth-service and returns tokens.
     *
     * @param  LoginProxyRequest  $request  Validated login request
     * @return JsonResponse The authentication response with tokens
     */
    public function login(LoginProxyRequest $request): JsonResponse
    {
        $response = $this->authProxyService->login($request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Handle user registration
     *
     * Proxies registration request to auth-service.
     *
     * @param  RegisterProxyRequest  $request  Validated registration request
     * @return JsonResponse The registration response
     */
    public function register(RegisterProxyRequest $request): JsonResponse
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
     * Proxies token refresh request to auth-service using the refresh token.
     *
     * @param  RefreshTokenProxyRequest  $request  Validated refresh token request
     * @return JsonResponse The new token pair
     */
    public function refresh(RefreshTokenProxyRequest $request): JsonResponse
    {
        $response = $this->authProxyService->refreshToken($request);

        return response()->json(
            $response->json(),
            $response->status()
        );
    }

    /**
     * Handle user logout
     *
     * Proxies logout request to auth-service with user context.
     *
     * @param  Request  $request  The HTTP request with Authorization header and user context
     * @return JsonResponse The logout confirmation
     */
    public function logout(Request $request): JsonResponse
    {
        $response = $this->authProxyService->logout($request);

        return response()->json(
            $response->json(),
            $response->status()
        );
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
            new UnifiedAuditLogCollection($result['data'], $result['meta'])
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
}
