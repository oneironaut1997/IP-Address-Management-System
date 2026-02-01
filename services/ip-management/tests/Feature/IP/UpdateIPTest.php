<?php

namespace Tests\Feature\IP;

use App\Models\IPAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class UpdateIPTest
 *
 * Feature tests for IP address update endpoint.
 *
 * @package Tests\Feature\IP
 */
class UpdateIPTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test owner can update their own IP address.
     *
     * @return void
     */
    public function test_owner_can_update_own_ip(): void
    {
        $userId = 'user-123';

        $ip = IPAddress::create([
            'user_id' => $userId,
            'ip_address' => '192.168.1.1',
            'label' => 'Original Label',
            'comment' => 'Original comment',
            'type' => 'ipv4',
        ]);

        $response = $this->putJson("/api/ip/{$ip->id}", [
            'label' => 'Updated Label',
            'comment' => 'Updated comment',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'IP address updated successfully.',
            ])
            ->assertJsonPath('data.label', 'Updated Label')
            ->assertJsonPath('data.comment', 'Updated comment');

        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip->id,
            'label' => 'Updated Label',
            'comment' => 'Updated comment',
        ]);
    }

    /**
     * Test non-owner cannot update others' IP addresses.
     *
     * @return void
     */
    public function test_non_owner_cannot_update_others_ip(): void
    {
        $ownerId = 'user-123';
        $otherUserId = 'user-456';

        $ip = IPAddress::create([
            'user_id' => $ownerId,
            'ip_address' => '192.168.1.1',
            'label' => 'Original Label',
            'type' => 'ipv4',
        ]);

        $response = $this->putJson("/api/ip/{$ip->id}", [
            'label' => 'Hacked Label',
        ], [
            'X-User-ID' => $otherUserId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                ],
            ]);

        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip->id,
            'label' => 'Original Label',
        ]);
    }

    /**
     * Test super admin can update any IP address.
     *
     * @return void
     */
    public function test_super_admin_can_update_any_ip(): void
    {
        $ownerId = 'user-123';
        $adminId = 'admin-456';

        $ip = IPAddress::create([
            'user_id' => $ownerId,
            'ip_address' => '192.168.1.1',
            'label' => 'Original Label',
            'type' => 'ipv4',
        ]);

        $response = $this->putJson("/api/ip/{$ip->id}", [
            'label' => 'Admin Updated Label',
        ], [
            'X-User-ID' => $adminId,
            'X-User-Role' => 'super_admin',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.label', 'Admin Updated Label');

        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip->id,
            'label' => 'Admin Updated Label',
        ]);
    }

    /**
     * Test update fails for non-existent IP.
     *
     * @return void
     */
    public function test_cannot_update_nonexistent_ip(): void
    {
        $userId = 'user-123';

        $response = $this->putJson('/api/ip/non-existent-id', [
            'label' => 'Updated Label',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    /**
     * Test validation requires label field.
     *
     * @return void
     */
    public function test_update_requires_label(): void
    {
        $userId = 'user-123';

        $ip = IPAddress::create([
            'user_id' => $userId,
            'ip_address' => '192.168.1.1',
            'label' => 'Original Label',
            'type' => 'ipv4',
        ]);

        $response = $this->putJson("/api/ip/{$ip->id}", [
            'comment' => 'Only comment',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['label']);
    }

    /**
     * Test update logs activity with changes.
     *
     * @return void
     */
    public function test_update_logs_activity_with_changes(): void
    {
        $userId = 'user-123';

        $ip = IPAddress::create([
            'user_id' => $userId,
            'ip_address' => '192.168.1.1',
            'label' => 'Original Label',
            'type' => 'ipv4',
        ]);

        $this->putJson("/api/ip/{$ip->id}", [
            'label' => 'Updated Label',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        // Check history was created with old and new values
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'modified_by' => $userId,
            'action' => 'updated',
        ]);

        // Check activity log was created
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ip->id,
            'subject_type' => IPAddress::class,
            'causer_id' => $userId,
            'event' => 'ip.updated',
        ]);
    }
}
