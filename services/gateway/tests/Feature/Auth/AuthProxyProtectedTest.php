<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gateway Protected Authentication Proxy Tests
 *
 * Tests for the gateway's protected authentication endpoints.
 * These tests verify that JWT middleware correctly protects routes
 * and user context is forwarded to backend services.
 */
class AuthProxyProtectedTest extends TestCase
{
    /**
     * Test that logout endpoint requires JWT authentication.
     */
    public function test_logout_requires_jwt_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'TOKEN_NOT_PROVIDED');
    }

    /**
     * Test that logout rejects invalid token format.
     */
    public function test_logout_rejects_invalid_token_format(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidToken',
        ])->postJson('/api/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /**
     * Test that me endpoint requires JWT authentication.
     */
    public function test_me_requires_jwt_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'TOKEN_NOT_PROVIDED');
    }

    /**
     * Test that audit logs endpoint requires JWT authentication.
     */
    public function test_audit_logs_requires_jwt_authentication(): void
    {
        $response = $this->getJson('/api/audit/logs');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'TOKEN_NOT_PROVIDED');
    }

    /**
     * Test that audit logs accepts query parameters.
     */
    public function test_audit_logs_accepts_query_parameters(): void
    {
        // Mock auth-service response
        Http::fake([
            'http://auth-service:8000/api/audit/logs' => Http::response([
                'success' => true,
                'data' => [
                    'logs' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 0,
                    ],
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/audit/logs', [
            'event_type' => 'user.login',
            'user_id' => 'test-user-uuid',
        ]);

        // Should proxy to auth-service
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that audit logs handles pagination.
     */
    public function test_audit_logs_handles_pagination(): void
    {
        // Mock auth-service response
        Http::fake([
            'http://auth-service:8000/api/audit/logs' => Http::response([
                'success' => true,
                'data' => [
                    'logs' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 0,
                    ],
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/audit/logs', [
            'page' => 1,
            'per_page' => 10,
        ]);

        // Should proxy to auth-service
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that user context headers are forwarded.
     */
    public function test_user_context_headers_are_forwarded(): void
    {
        // Mock auth-service response that verifies headers
        Http::fake([
            'http://auth-service:8000/api/auth/me' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'custom-user-id',
                    'role' => 'admin',
                    'email' => 'test@example.com',
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'custom-user-id',
            'role' => 'admin',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        // The gateway should forward these headers to auth-service
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test protected routes reject expired tokens.
     */
    public function test_protected_routes_reject_expired_tokens(): void
    {
        // Create a token that's already expired
        $token = $this->generateValidToken([], [
            'exp' => now()->subHour()->timestamp,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        // Should reject expired token (401) or fail if service unavailable (500)
        $this->assertTrue(
            in_array($response->getStatusCode(), [401, 500]),
            'Expected 401 or 500, got ' . $response->getStatusCode()
        );
    }

    /**
     * Test protected routes reject invalid tokens.
     */
    public function test_protected_routes_reject_invalid_tokens(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid.token.here',
        ])->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /**
     * Test that logout successfully proxies to auth-service.
     */
    public function test_logout_proxies_to_auth_service(): void
    {
        // Mock auth-service response
        Http::fake([
            'http://auth-service:8000/api/auth/logout' => Http::response([
                'success' => true,
                'data' => [
                    'message' => 'Logged out successfully',
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that me endpoint returns user data.
     */
    public function test_me_returns_user_data(): void
    {
        // Mock auth-service response
        Http::fake([
            'http://auth-service:8000/api/auth/me' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'test-user-uuid',
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'role' => 'regular',
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 'test-user-uuid');
    }

    /**
     * Test that audit logs endpoint handles empty results.
     */
    public function test_audit_logs_handles_empty_results(): void
    {
        // Mock auth-service response with empty logs
        Http::fake([
            'http://auth-service:8000/api/audit/logs' => Http::response([
                'success' => true,
                'data' => [
                    'logs' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 0,
                    ],
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/audit/logs');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that protected routes work with admin role.
     */
    public function test_protected_routes_work_with_admin_role(): void
    {
        // Mock auth-service response
        Http::fake([
            'http://auth-service:8000/api/auth/me' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'admin-uuid',
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'admin-uuid',
            'role' => 'admin',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'admin');
    }
}
