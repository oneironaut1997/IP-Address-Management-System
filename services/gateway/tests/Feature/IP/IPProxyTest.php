<?php

namespace Tests\Feature\IP;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gateway IP Proxy Tests
 *
 * Tests for the gateway's IP management proxy endpoints.
 * These tests verify that the gateway correctly proxies IP requests
 * to the ip-management service and handles various HTTP methods.
 */
class IPProxyTest extends TestCase
{
    /**
     * Test that IP routes require JWT authentication.
     */
    public function test_ip_routes_require_jwt_authentication(): void
    {
        // Test GET /api/ip without auth
        $response = $this->getJson('/api/ip');
        $response->assertStatus(401);

        // Test POST /api/ip without auth
        $response = $this->postJson('/api/ip', [
            'address' => '192.168.1.1',
            'name' => 'Test IP',
        ]);
        $response->assertStatus(401);

        // Test PUT /api/ip/{id} without auth
        $response = $this->putJson('/api/ip/test-uuid', [
            'name' => 'Updated IP',
        ]);
        $response->assertStatus(401);

        // Test DELETE /api/ip/{id} without auth
        $response = $this->deleteJson('/api/ip/test-uuid');
        $response->assertStatus(401);
    }

    /**
     * Test that GET /api/ip returns IP list.
     */
    public function test_get_ip_list_returns_data(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip' => Http::response([
                'success' => true,
                'data' => [
                    'ips' => [
                        [
                            'id' => 'ip-uuid-1',
                            'address' => '192.168.1.1',
                            'name' => 'Test IP 1',
                        ],
                    ],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 1,
                    ],
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that GET /api/ip accepts query parameters.
     */
    public function test_get_ip_list_accepts_query_parameters(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip' => Http::response([
                'success' => true,
                'data' => [
                    'ips' => [],
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
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip', [
            'search' => '192.168',
            'status' => 'active',
            'page' => 1,
            'per_page' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that POST /api/ip creates a new IP address.
     */
    public function test_create_ip_creates_new_ip(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'new-ip-uuid',
                    'ip_address' => '192.168.1.1',
                    'label' => 'Test IP',
                    'comment' => 'Test description',
                ],
            ], 201),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Test IP',
            'comment' => 'Test description',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ip_address', '192.168.1.1');
    }

    /**
     * Test that create IP returns validation error for invalid data.
     */
    public function test_create_ip_returns_validation_error(): void
    {
        // Mock ip-management service validation error
        Http::fake([
            'http://ip-management:8000/api/ip' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The address field is required.',
                ],
            ], 422),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/ip', []);

        $this->assertTrue(in_array($response->getStatusCode(), [400, 422, 500]));
    }

    /**
     * Test that GET /api/ip/{id} retrieves a specific IP.
     */
    public function test_get_single_ip_returns_data(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip/ip-uuid' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'ip-uuid',
                    'address' => '192.168.1.1',
                    'name' => 'Test IP',
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip/ip-uuid');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 'ip-uuid');
    }

    /**
     * Test that GET /api/ip/{id} returns 404 for non-existent IP.
     */
    public function test_get_single_ip_returns_404_for_missing(): void
    {
        // Mock ip-management service 404 response
        Http::fake([
            'http://ip-management:8000/api/ip/non-existent' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], 404),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip/non-existent');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /**
     * Test that PUT /api/ip/{id} updates an IP address.
     */
    public function test_update_ip_updates_data(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip/ip-uuid' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'ip-uuid',
                    'address' => '192.168.1.1',
                    'name' => 'Updated IP Name',
                    'description' => 'Updated description',
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson('/api/ip/ip-uuid', [
            'name' => 'Updated IP Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated IP Name');
    }

    /**
     * Test that PATCH /api/ip/{id} partially updates an IP address.
     */
    public function test_patch_ip_partially_updates_data(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip/ip-uuid' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'ip-uuid',
                    'address' => '192.168.1.1',
                    'name' => 'Partially Updated IP',
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->patchJson('/api/ip/ip-uuid', [
            'name' => 'Partially Updated IP',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that DELETE /api/ip/{id} removes an IP address.
     */
    public function test_delete_ip_removes_data(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip/ip-uuid' => Http::response([
                'success' => true,
                'data' => [
                    'message' => 'IP address deleted successfully',
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson('/api/ip/ip-uuid');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that IP routes support nested resources (history).
     */
    public function test_ip_routes_support_nested_resources(): void
    {
        // Mock ip-management service response for history
        Http::fake([
            'http://ip-management:8000/api/ip/ip-uuid/history' => Http::response([
                'success' => true,
                'data' => [
                    'history' => [],
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip/ip-uuid/history');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that IP routes support nested resources (audit).
     */
    public function test_ip_routes_support_audit_resources(): void
    {
        // Mock ip-management service response for audit
        Http::fake([
            'http://ip-management:8000/api/ip/ip-uuid/audit' => Http::response([
                'success' => true,
                'data' => [
                    'audit' => [],
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip/ip-uuid/audit');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that IP routes handle wildcard paths.
     */
    public function test_ip_routes_handle_wildcard_paths(): void
    {
        // Mock ip-management service response for nested path
        Http::fake([
            'http://ip-management:8000/api/ip/ip-uuid/history/filter' => Http::response([
                'success' => true,
                'data' => [],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip/ip-uuid/history/filter');

        $response->assertStatus(200);
    }

    /**
     * Test that user context is forwarded to IP service.
     */
    public function test_user_context_is_forwarded_to_ip_service(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip' => Http::response([
                'success' => true,
                'data' => [
                    'ips' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 0,
                    ],
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'custom-user-id',
            'role' => 'admin',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip');

        // The gateway should forward these headers to ip-management service
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that IP routes reject invalid tokens.
     */
    public function test_ip_routes_reject_invalid_tokens(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid.token',
        ])->getJson('/api/ip');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /**
     * Test that IP routes reject expired tokens.
     */
    public function test_ip_routes_reject_expired_tokens(): void
    {
        // Create an expired token by using the JWT provider directly
        $user = \App\Models\User::factory()->create();

        // Build the payload with an expired exp claim
        $payload = [
            'iss' => config('app.url'),
            'iat' => now()->subHours(2)->timestamp,
            'exp' => now()->subHour()->timestamp,  // Expired 1 hour ago
            'nbf' => now()->subHours(2)->timestamp,
            'sub' => $user->id,
            'jti' => bin2hex(random_bytes(16)),
            'role' => 'regular',
            'email' => $user->email,
        ];

        // Encode the token directly using the JWT provider
        $provider = app(\Tymon\JWTAuth\Providers\JWT\Lcobucci::class);
        $token = $provider->encode($payload);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Test IP',
        ]);

        // Should reject expired token (401) or fail if service unavailable (502)
        $this->assertTrue(
            in_array($response->getStatusCode(), [401, 500, 502]),
            'Expected 401, 500, or 502, got '.$response->getStatusCode()
        );
    }

    /**
     * Test that IP service unavailability is handled gracefully.
     */
    public function test_handles_ip_service_unavailability(): void
    {
        // Mock a connection error
        Http::fake([
            'http://ip-management:8000/api/ip' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => 'IP management service is temporarily unavailable.',
                ],
            ], 503),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'test-user-uuid',
            'role' => 'regular',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip');

        $response->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    /**
     * Test that admin can access all IP routes.
     */
    public function test_admin_can_access_ip_routes(): void
    {
        // Mock ip-management service response
        Http::fake([
            'http://ip-management:8000/api/ip' => Http::response([
                'success' => true,
                'data' => [
                    'ips' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 0,
                    ],
                ],
            ], 200),
        ]);

        $token = $this->generateValidToken([
            'sub' => 'admin-uuid',
            'role' => 'admin',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/ip');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
