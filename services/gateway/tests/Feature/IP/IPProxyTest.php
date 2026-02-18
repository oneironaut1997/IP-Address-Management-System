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
     * Test that GET /api/ip returns IP list.
     */
    public function test_get_ip_list_returns_data(): void
    {
        // Mock ip-management service response - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip*' => Http::response([
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

        $response = $this->getJson('/api/ip');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that GET /api/ip accepts query parameters.
     */
    public function test_get_ip_list_accepts_query_parameters(): void
    {
        // Mock ip-management service response - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip*' => Http::response([
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

        $response = $this->getJson('/api/ip', [
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
        // Mock ip-management service response - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip*' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'new-ip-uuid',
                    'ip_address' => '192.168.1.1',
                    'label' => 'Test IP',
                    'comment' => 'Test description',
                ],
            ], 201),
        ]);

        $response = $this->postJson('/api/ip', [
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
        // Mock ip-management service validation error - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip*' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The address field is required.',
                ],
            ], 422),
        ]);

        $response = $this->postJson('/api/ip', []);

        $this->assertTrue(in_array($response->getStatusCode(), [400, 422, 500]));
    }

    /**
     * Test that GET /api/ip/{id} retrieves a specific IP.
     */
    public function test_get_single_ip_returns_data(): void
    {
        // Mock ip-management service response - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip/*' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'ip-uuid',
                    'address' => '192.168.1.1',
                    'name' => 'Test IP',
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/ip/ip-uuid');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 'ip-uuid');
    }

    /**
     * Test that GET /api/ip/{id} returns 404 for non-existent IP.
     */
    public function test_get_single_ip_returns_404_for_missing(): void
    {
        // Mock ip-management service 404 response - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip/*' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'IP address not found.',
                ],
            ], 404),
        ]);

        $response = $this->getJson('/api/ip/non-existent');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /**
     * Test that PUT /api/ip/{id} updates an IP address.
     */
    public function test_update_ip_updates_data(): void
    {
        // Mock ip-management service response - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip/*' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'ip-uuid',
                    'address' => '192.168.1.1',
                    'name' => 'Updated IP Name',
                    'description' => 'Updated description',
                ],
            ], 200),
        ]);

        $response = $this->putJson('/api/ip/ip-uuid', [
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
        // Mock ip-management service response - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip/*' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'ip-uuid',
                    'address' => '192.168.1.1',
                    'name' => 'Partially Updated IP',
                ],
            ], 200),
        ]);

        $response = $this->patchJson('/api/ip/ip-uuid', [
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
        // Mock ip-management service response - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip/*' => Http::response([
                'success' => true,
                'data' => [
                    'message' => 'IP address deleted successfully',
                ],
            ], 200),
        ]);

        $response = $this->deleteJson('/api/ip/ip-uuid');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that IP routes support nested resources (history).
     */
    public function test_ip_routes_support_nested_resources(): void
    {
        // Mock ip-management service response for history - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip/*/history*' => Http::response([
                'success' => true,
                'data' => [
                    'history' => [],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/ip/ip-uuid/history');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that IP routes support nested resources (audit).
     */
    public function test_ip_routes_support_audit_resources(): void
    {
        // Mock ip-management service response for audit - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip/*/audit*' => Http::response([
                'success' => true,
                'data' => [
                    'audit' => [],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/ip/ip-uuid/audit');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test that IP routes handle wildcard paths.
     */
    public function test_ip_routes_handle_wildcard_paths(): void
    {
        // Mock ip-management service response for nested path - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/api/ip/*' => Http::response([
                'success' => true,
                'data' => [],
            ], 200),
        ]);

        // This path doesn't exist in the routes, so it should return 404
        // The gateway's route definition doesn't include this wildcard path
        $response = $this->getJson('/api/ip/ip-uuid/history/filter');

        // The route doesn't exist, so it should return 404
        $response->assertStatus(404);
    }

    /**
     * Test that IP service unavailability is handled gracefully.
     */
    public function test_handles_ip_service_unavailability(): void
    {
        // Mock a connection error - use wildcard pattern
        Http::fake([
            'http://ip-management:8000/*' => Http::response([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => 'IP management service is temporarily unavailable.',
                ],
            ], 503),
        ]);

        $response = $this->getJson('/api/ip');

        $response->assertStatus(503)
            ->assertJsonPath('success', false);
    }
}
