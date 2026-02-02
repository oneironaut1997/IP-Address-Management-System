<?php

namespace Tests\Feature\IP;

use App\Models\IPAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class CreateIPTest
 *
 * Feature tests for IP address creation endpoint.
 */
class CreateIPTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating an IP address with valid IPv4.
     */
    public function test_can_create_ipv4_address(): void
    {
        $userId = 'user-123';

        $response = $this->postJson('/api/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'comment' => 'Test comment',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'IP address created successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'ip_address',
                    'label',
                    'comment',
                    'type',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.ip_address', '192.168.1.1')
            ->assertJsonPath('data.label', 'Test Server')
            ->assertJsonPath('data.type', 'ipv4')
            ->assertJsonPath('data.user_id', $userId);

        $this->assertDatabaseHas('ip_addresses', [
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
            'user_id' => $userId,
        ]);
    }

    /**
     * Test creating an IP address with valid IPv6.
     */
    public function test_can_create_ipv6_address(): void
    {
        $userId = 'user-123';

        $response = $this->postJson('/api/ip', [
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'label' => 'IPv6 Server',
            'comment' => 'Test IPv6',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'ipv6')
            ->assertJsonPath('data.ip_address', '2001:0db8:85a3:0000:0000:8a2e:0370:7334');

        $this->assertDatabaseHas('ip_addresses', [
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'type' => 'ipv6',
        ]);
    }

    /**
     * Test creating an IP with invalid format fails.
     */
    public function test_cannot_create_invalid_ip(): void
    {
        $userId = 'user-123';

        $response = $this->postJson('/api/ip', [
            'ip_address' => 'invalid-ip-address',
            'label' => 'Invalid IP',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid IP address format.',
                ],
            ]);
    }

    /**
     * Test validation requires ip_address field.
     */
    public function test_requires_ip_address(): void
    {
        $userId = 'user-123';

        $response = $this->postJson('/api/ip', [
            'label' => 'Test Server',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /**
     * Test validation requires label field.
     */
    public function test_requires_label(): void
    {
        $userId = 'user-123';

        $response = $this->postJson('/api/ip', [
            'ip_address' => '192.168.1.1',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['label']);
    }

    /**
     * Test creating IP logs activity.
     */
    public function test_create_logs_activity(): void
    {
        $userId = 'user-123';

        $response = $this->postJson('/api/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $ipId = $response->json('data.id');

        // Check history was created
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ipId,
            'modified_by' => $userId,
            'action' => 'created',
        ]);

        // Check activity log was created
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ipId,
            'subject_type' => IPAddress::class,
            'causer_id' => $userId,
            'event' => 'ip.created',
        ]);
    }
}
