<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Auth Proxy Controller
 *
 * Proxies authentication requests to the auth-service.
 * Handles login, register, refresh, logout, and user info endpoints.
 * Also provides unified audit log endpoint combining auth and IP activities.
 */
class AuthProxyController extends Controller
{
    /**
     * Auth service base URL
     */
    protected string $authServiceUrl;

    /**
     * IP Management service base URL
     */
    protected string $ipServiceUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authServiceUrl = 'http://auth-service:8000';
        $this->ipServiceUrl = 'http://ip-management:8000';
    }

    /**
     * Handle user login
     *
     * Proxies login request to auth-service and returns tokens.
     *
     * @param  Request  $request  The HTTP request containing email and password
     * @return JsonResponse The authentication response with tokens
     */
    public function login(Request $request): JsonResponse
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$this->authServiceUrl}/api/auth/login", $request->all());

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
     * @param  Request  $request  The HTTP request containing user registration data
     * @return JsonResponse The registration response
     */
    public function register(Request $request): JsonResponse
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$this->authServiceUrl}/api/auth/register", $request->all());

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
     * @param  Request  $request  The HTTP request with Authorization header
     * @return JsonResponse The new token pair
     */
    public function refresh(Request $request): JsonResponse
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $request->header('Authorization'),
        ])->post("{$this->authServiceUrl}/api/auth/refresh");

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
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $request->header('Authorization'),
            'X-User-ID' => $request->header('X-User-ID'),
            'X-User-Role' => $request->header('X-User-Role'),
        ])->post("{$this->authServiceUrl}/api/auth/logout");

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
     * @param Request $request The HTTP request with Authorization header and user context
     * @return JsonResponse The user data
     */
    public function me(Request $request): JsonResponse
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $request->header('Authorization'),
            'X-User-ID' => $request->header('X-User-ID'),
            'X-User-Role' => $request->header('X-User-Role'),
        ])->get("{$this->authServiceUrl}/api/auth/me");

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
     * @param Request $request The HTTP request with Authorization header and query params
     * @return JsonResponse The unified audit logs data
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $type = $request->input('type', 'all');
        $perPage = min($request->input('per_page', 50), 100);
        $page = $request->input('page', 1);

        $authLogs = [];
        $ipLogs = [];
        $authCount = 0;
        $ipCount = 0;

        // Fetch authentication logs from auth-service
        if ($type === 'auth' || $type === 'all') {
            $authResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $request->header('Authorization'),
                'X-User-ID' => $request->header('X-User-ID'),
                'X-User-Role' => $request->header('X-User-Role'),
            ])->get("{$this->authServiceUrl}/api/audit/logs", [
                'event_type' => $request->input('event_type'),
                'user_id' => $request->input('user_id'),
                'entity_type' => $request->input('entity_type'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if ($authResponse->successful()) {
                $authData = $authResponse->json();
                $authLogs = $authData['data'] ?? [];
                $authCount = $authData['meta']['total'] ?? count($authLogs);

                // Add type field to auth logs
                $authLogs = array_map(function ($log) {
                    $log['type'] = 'auth';
                    return $log;
                }, $authLogs);
            }
        }

        // Fetch IP activities from ip-management service
        if ($type === 'ip' || $type === 'all') {
            $ipResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $request->header('Authorization'),
                'X-User-ID' => $request->header('X-User-ID'),
                'X-User-Role' => $request->header('X-User-Role'),
            ])->get("{$this->ipServiceUrl}/api/activity/logs", [
                'event' => $request->input('event_type'),
                'user_id' => $request->input('user_id'),
                'subject_type' => $request->input('entity_type'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if ($ipResponse->successful()) {
                $ipData = $ipResponse->json();
                $ipLogs = $ipData['data'] ?? [];
                $ipCount = $ipData['meta']['total'] ?? count($ipLogs);
            }
        }

        // Merge logs and sort by created_at (newest first)
        $allLogs = array_merge($authLogs, $ipLogs);
        usort($allLogs, function ($a, $b) {
            $dateA = strtotime($a['created_at'] ?? 0);
            $dateB = strtotime($b['created_at'] ?? 0);
            return $dateB - $dateA;
        });

        // Calculate totals based on type filter
        $total = match ($type) {
            'auth' => $authCount,
            'ip' => $ipCount,
            default => $authCount + $ipCount,
        };

        return response()->json([
            'success' => true,
            'data' => $allLogs,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'auth_count' => $authCount,
                'ip_count' => $ipCount,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Get IP management activity logs
     *
     * Proxies activity log request to ip-management service.
     *
     * @param Request $request The HTTP request with Authorization header and query params
     * @return JsonResponse The IP activity logs data
     */
    public function ipActivities(Request $request): JsonResponse
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $request->header('Authorization'),
            'X-User-ID' => $request->header('X-User-ID'),
            'X-User-Role' => $request->header('X-User-Role'),
        ])->get("{$this->ipServiceUrl}/api/activity/logs", $request->query());

        return response()->json(
            $response->json(),
            $response->status()
        );
    }
}
