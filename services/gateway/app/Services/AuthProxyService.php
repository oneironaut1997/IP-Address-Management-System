<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;

/**
 * Class AuthProxyService
 *
 * Service layer for proxying authentication-related requests to the auth-service.
 * Handles login, register, refresh, logout, and user info operations.
 *
 * This service encapsulates all auth proxy-related business rules and separates
 * them from HTTP concerns in the controller layer.
 */
class AuthProxyService extends ProxyService
{
    /**
     * Proxy user login request to auth-service.
     *
     * @param  Request  $request  The HTTP request containing email and password
     */
    public function login(Request $request): Response
    {
        return $this->forwardToAuthService('post', '/api/auth/login', $request);
    }

    /**
     * Proxy user registration request to auth-service.
     *
     * @param  Request  $request  The HTTP request containing user registration data
     */
    public function register(Request $request): Response
    {
        return $this->forwardToAuthService('post', '/api/auth/register', $request);
    }

    /**
     * Proxy token refresh request to auth-service.
     *
     * @param  Request  $request  The HTTP request with Authorization header
     */
    public function refreshToken(Request $request): Response
    {
        return $this->forwardToAuthService('post', '/api/auth/refresh', $request);
    }

    /**
     * Proxy logout request to auth-service.
     *
     * @param  Request  $request  The HTTP request with Authorization header and user context
     */
    public function logout(Request $request): Response
    {
        return $this->forwardToAuthService('post', '/api/auth/logout', $request);
    }

    /**
     * Proxy user info request to auth-service.
     *
     * @param  Request  $request  The HTTP request with Authorization header and user context
     */
    public function getUserInfo(Request $request): Response
    {
        return $this->forwardToAuthService('get', '/api/auth/me', $request);
    }

    /**
     * Get audit logs from auth-service.
     *
     * @param  Request  $request  The HTTP request with query parameters
     */
    public function getAuditLogs(Request $request): Response
    {
        return $this->forwardToAuthService('get', '/api/audit/logs', $request);
    }

    /**
     * Get unified audit logs from both auth-service and IP management service.
     *
     * Combines audit logs from auth-service (login/logout events) and
     * IP management activities (ip.created, ip.updated, ip.deleted) into
     * a single, chronologically sorted response.
     *
     * @param  Request  $request  The HTTP request with query parameters
     * @return array{success: bool, data: array, meta: array}
     */
    public function getUnifiedAuditLogs(Request $request): array
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
            $authResponse = $this->forwardToAuthService('get', '/api/audit/logs', $request);

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
            $ipResponse = $this->forwardToIPService('get', '/api/activity/logs', $request);

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

        return [
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
        ];
    }

    /**
     * Get IP management activity logs.
     *
     * @param  Request  $request  The HTTP request with query parameters
     */
    public function getIPActivities(Request $request): Response
    {
        return $this->forwardToIPService('get', '/api/activity/logs', $request);
    }
}
