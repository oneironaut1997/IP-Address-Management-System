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
 */
class AuthProxyController extends Controller
{
    /**
     * Auth service base URL
     */
    protected string $authServiceUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authServiceUrl = 'http://auth-service:8000';
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
     * @param  Request  $request  The HTTP request with Authorization header and user context
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
}
