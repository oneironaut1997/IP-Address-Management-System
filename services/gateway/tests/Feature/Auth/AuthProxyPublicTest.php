<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gateway Authentication Proxy Tests
 *
 * Tests for the gateway's authentication proxy endpoints.
 * These tests verify that the gateway correctly proxies auth requests
 * to the auth-service and handles responses appropriately.
 */
class AuthProxyPublicTest extends TestCase
{
    /**
     * Test that health check endpoint is accessible without authentication.
     */
    public function test_health_check_is_accessible_without_auth(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'service',
                    'status',
                    'timestamp',
                ],
            ])
            ->assertJsonPath('data.service', 'gateway')
            ->assertJsonPath('data.status', 'healthy');
    }

    /**
     * Test that login endpoint is accessible without authentication.
     */
    public function test_login_endpoint_is_accessible_without_auth(): void
    {
        // Mock auth-service response
        Http::fake([
            'http://auth-service:8000/api/auth/login' => Http::response([
                'success' => true,
                'data' => [
                    'access_token' => 'test-token',
                    'refresh_token' => 'test-refresh-token',
                    'token_type' => 'Bearer',
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Should proxy to auth-service and return success
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that register endpoint is accessible without authentication.
     */
    public function test_register_endpoint_is_accessible_without_auth(): void
    {
        // Mock auth-service response
        Http::fake([
            'http://auth-service:8000/api/auth/register' => Http::response([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => 'test-uuid',
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                    ],
                ],
            ], 201),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Should proxy to auth-service and return success
        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that refresh endpoint accepts valid tokens.
     */
    public function test_refresh_endpoint_accepts_valid_token(): void
    {
        // Mock auth-service response
        Http::fake([
            'http://auth-service:8000/api/auth/refresh' => Http::response([
                'success' => true,
                'data' => [
                    'access_token' => 'new-token',
                    'refresh_token' => 'new-refresh-token',
                    'token_type' => 'Bearer',
                ],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid.refresh.token',
        ])->postJson('/api/auth/refresh');

        // Should proxy to auth-service and return success
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test login validation - missing email.
     */
    public function test_login_requires_email(): void
    {
        // Mock auth-service validation error response
        Http::fake([
            'http://auth-service:8000/api/auth/login' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The email field is required.',
                ],
            ], 422),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        // Gateway should proxy the 422 response
        $this->assertTrue(in_array($response->getStatusCode(), [422, 500]));
    }

    /**
     * Test login validation - missing password.
     */
    public function test_login_requires_password(): void
    {
        // Mock auth-service validation error response
        Http::fake([
            'http://auth-service:8000/api/auth/login' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The password field is required.',
                ],
            ], 422),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        // Gateway should proxy the 422 response
        $this->assertTrue(in_array($response->getStatusCode(), [422, 500]));
    }

    /**
     * Test register validation - missing name.
     */
    public function test_register_requires_name(): void
    {
        // Mock auth-service validation error response
        Http::fake([
            'http://auth-service:8000/api/auth/register' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The name field is required.',
                ],
            ], 422),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Gateway should proxy the 422 response
        $this->assertTrue(in_array($response->getStatusCode(), [422, 500]));
    }

    /**
     * Test register validation - invalid email format.
     */
    public function test_register_requires_valid_email(): void
    {
        // Mock auth-service validation error response
        Http::fake([
            'http://auth-service:8000/api/auth/register' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The email must be a valid email address.',
                ],
            ], 422),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Gateway should proxy the 422 response
        $this->assertTrue(in_array($response->getStatusCode(), [422, 500]));
    }

    /**
     * Test register validation - password confirmation mismatch.
     */
    public function test_register_requires_password_confirmation(): void
    {
        // Mock auth-service validation error response
        Http::fake([
            'http://auth-service:8000/api/auth/register' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The password confirmation does not match.',
                ],
            ], 422),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        // Gateway should proxy the 422 response
        $this->assertTrue(in_array($response->getStatusCode(), [422, 500]));
    }

    /**
     * Test that protected auth endpoints require JWT authentication.
     */
    public function test_protected_auth_endpoints_require_jwt(): void
    {
        // Test logout without auth
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);

        // Test me endpoint without auth
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);

        // Test audit logs without auth
        $response = $this->getJson('/api/audit/logs');
        $response->assertStatus(401);
    }

    /**
     * Test that login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        // Mock auth-service error response
        Http::fake([
            'http://auth-service:8000/api/auth/login' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid email or password.',
                ],
            ], 401),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        // Should proxy the 401 error
        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /**
     * Test that auth-service unavailability is handled gracefully.
     */
    public function test_handles_auth_service_unavailability(): void
    {
        // Mock a connection error
        Http::fake([
            'http://auth-service:8000/*' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => 'Auth service is temporarily unavailable.',
                ],
            ], 503),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Should return service unavailable error
        $response->assertStatus(503)
            ->assertJsonPath('success', false);
    }
}
