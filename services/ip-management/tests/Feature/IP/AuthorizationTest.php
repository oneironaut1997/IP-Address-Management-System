<?php

namespace Tests\Feature\IP;

use App\Models\IPAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class AuthorizationTest
 *
 * Feature tests for IP address authorization policies.
 *
 * @package Tests\Feature\IP
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all authenticated users can view all IPs.
     *
     * @return void
     */
    public function test_all_auth_users_can_view_ips(): void
    {
        $user1Id = 'user-123';
        $user2Id = 'user-456';

        IPAddress::create([
            'user_id' => $user1Id,
            'ip_address' => '192.168.1.1',
            'label' => 'Server 1',
            'type' => 'ipv4',
        ]);

        IPAddress::create([
            'user_id' => $user2Id,
            'ip_address' => '192.168.1.2',
            'label' => 'Server 2',
            'type' => 'ipv4',
        ]);

        $response = $this->getJson('/api/ip', [
            'X-User-ID' => $user1Id,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test all authenticated users can create IPs.
     *
     * @return void
     */
    public function test_all_auth_users_can_create_ips(): void
    {
        $regularUserId = 'user-123';
        $adminUserId = 'admin-456';

        // Regular user can create
        $response1 = $this->postJson('/api/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Regular User IP',
        ], [
            'X-User-ID' => $regularUserId,
            'X-User-Role' => 'regular',
        ]);

        $response1->assertStatus(201);

        // Admin user can create
        $response2 = $this->postJson('/api/ip', [
            'ip_address' => '192.168.1.2',
            'label' => 'Admin User IP',
        ], [
            'X-User-ID' => $adminUserId,
            'X-User-Role' => 'super_admin',
        ]);

        $response2->assertStatus(201);
    }

    /**
     * Test policy enforcement for update operations.
     *
     * @return void
     */
    public function test_policy_enforcement_for_update(): void
    {
        $ownerId = 'user-123';
        $otherUserId = 'user-456';
        $adminId = 'admin-789';

        $ip = IPAddress::create([
            'user_id' => $ownerId,
            'ip_address' => '192.168.1.1',
            'label' => 'Original Label',
            'type' => 'ipv4',
        ]);

        // Owner can update
        $response1 = $this->putJson("/api/ip/{$ip->id}", [
            'label' => 'Owner Updated',
        ], [
            'X-User-ID' => $ownerId,
            'X-User-Role' => 'regular',
        ]);
        $response1->assertStatus(200);

        // Reset label
        $ip->update(['label' => 'Original Label']);

        // Other regular user cannot update
        $response2 = $this->putJson("/api/ip/{$ip->id}", [
            'label' => 'Other User Updated',
        ], [
            'X-User-ID' => $otherUserId,
            'X-User-Role' => 'regular',
        ]);
        $response2->assertStatus(403);

        // Admin can update any IP
        $response3 = $this->putJson("/api/ip/{$ip->id}", [
            'label' => 'Admin Updated',
        ], [
            'X-User-ID' => $adminId,
            'X-User-Role' => 'super_admin',
        ]);
        $response3->assertStatus(200);
    }

    /**
     * Test policy enforcement for delete operations.
     *
     * @return void
     */
    public function test_policy_enforcement_for_delete(): void
    {
        $ownerId = 'user-123';
        $otherUserId = 'user-456';
        $adminId = 'admin-789';

        $ip = IPAddress::create([
            'user_id' => $ownerId,
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
        ]);

        // Owner cannot delete
        $response1 = $this->deleteJson("/api/ip/{$ip->id}", [], [
            'X-User-ID' => $ownerId,
            'X-User-Role' => 'regular',
        ]);
        $response1->assertStatus(403);

        // Other regular user cannot delete
        $response2 = $this->deleteJson("/api/ip/{$ip->id}", [], [
            'X-User-ID' => $otherUserId,
            'X-User-Role' => 'regular',
        ]);
        $response2->assertStatus(403);

        // Admin can delete
        $response3 = $this->deleteJson("/api/ip/{$ip->id}", [], [
            'X-User-ID' => $adminId,
            'X-User-Role' => 'super_admin',
        ]);
        $response3->assertStatus(204);
    }

    /**
     * Test all authenticated users can view IP history.
     *
     * @return void
     */
    public function test_all_auth_users_can_view_history(): void
    {
        $user1Id = 'user-123';
        $user2Id = 'user-456';

        $ip = IPAddress::create([
            'user_id' => $user1Id,
            'ip_address' => '192.168.1.1',
            'label' => 'Server 1',
            'type' => 'ipv4',
        ]);

        // User 2 (not owner) can view history
        $response = $this->getJson("/api/ip/{$ip->id}/history", [
            'X-User-ID' => $user2Id,
            'X-User-Role' => 'regular',
        ]);

        $response->assertStatus(200);
    }
}
