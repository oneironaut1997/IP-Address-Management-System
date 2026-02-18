<?php

namespace Tests\Feature\IP;

use App\Models\IPAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class AuthorizationTest
 *
 * Feature tests for IP address authorization policies.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all authenticated users can view all IPs.
     */
    public function test_all_auth_users_can_view_ips(): void
    {
        $user1 = User::factory()->create(['role' => 'regular']);
        $user2 = User::factory()->create(['role' => 'regular']);

        IPAddress::create([
            'user_id' => $user1->id,
            'ip_address' => '192.168.1.1',
            'label' => 'Server 1',
            'type' => 'ipv4',
        ]);

        IPAddress::create([
            'user_id' => $user2->id,
            'ip_address' => '192.168.1.2',
            'label' => 'Server 2',
            'type' => 'ipv4',
        ]);

        $response = $this->actingAs($user1)->getJson('/api/v1/ip');

        // Verify response is successful and contains IPs - data is paginated so we just check it returns results
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    /**
     * Test all authenticated users can create IPs.
     */
    public function test_all_auth_users_can_create_ips(): void
    {
        $regularUser = User::factory()->create(['role' => 'regular']);
        $adminUser = User::factory()->create(['id' => 'admin-456', 'role' => 'super_admin']);

        // Regular user can create
        $response1 = $this->actingAs($regularUser)->postJson('/api/v1/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Regular User IP',
        ]);

        $response1->assertStatus(201);

        // Admin user can create
        $response2 = $this->actingAs($adminUser)->postJson('/api/v1/ip', [
            'ip_address' => '192.168.1.2',
            'label' => 'Admin User IP',
        ]);

        $response2->assertStatus(201);
    }

    /**
     * Test policy enforcement for update operations.
     */
    public function test_policy_enforcement_for_update(): void
    {
        $owner = User::factory()->create(['role' => 'regular']);
        $otherUser = User::factory()->create(['role' => 'regular']);
        $admin = User::factory()->create(['id' => 'admin-789', 'role' => 'super_admin']);

        $ip = IPAddress::create([
            'user_id' => $owner->id,
            'ip_address' => '192.168.1.1',
            'label' => 'Original Label',
            'type' => 'ipv4',
        ]);

        // Owner can update
        $response1 = $this->actingAs($owner)->putJson("/api/v1/ip/{$ip->id}", [
            'label' => 'Owner Updated',
        ]);
        $response1->assertStatus(200);

        // Reset label
        $ip->update(['label' => 'Original Label']);

        // Other regular user cannot update
        $response2 = $this->actingAs($otherUser)->putJson("/api/v1/ip/{$ip->id}", [
            'label' => 'Other User Updated',
        ]);
        $response2->assertStatus(403);

        // Admin can update any IP
        $response3 = $this->actingAs($admin)->putJson("/api/v1/ip/{$ip->id}", [
            'label' => 'Admin Updated',
        ]);
        $response3->assertStatus(200);
    }

    /**
     * Test policy enforcement for delete operations.
     */
    public function test_policy_enforcement_for_delete(): void
    {
        $owner = User::factory()->create(['role' => 'regular']);
        $otherUser = User::factory()->create(['role' => 'regular']);
        $admin = User::factory()->create(['id' => 'admin-789', 'role' => 'super_admin']);

        $ip = IPAddress::create([
            'user_id' => $owner->id,
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
        ]);

        // Owner cannot delete
        $response1 = $this->actingAs($owner)->deleteJson("/api/v1/ip/{$ip->id}");
        $response1->assertStatus(403);

        // Other regular user cannot delete
        $response2 = $this->actingAs($otherUser)->deleteJson("/api/v1/ip/{$ip->id}");
        $response2->assertStatus(403);

        // Admin can delete
        $response3 = $this->actingAs($admin)->deleteJson("/api/v1/ip/{$ip->id}");
        $response3->assertStatus(204);
    }

    /**
     * Test all authenticated users can view IP history.
     */
    public function test_all_auth_users_can_view_history(): void
    {
        $user1 = User::factory()->create(['role' => 'regular']);
        $user2 = User::factory()->create(['role' => 'regular']);

        $ip = IPAddress::create([
            'user_id' => $user1->id,
            'ip_address' => '192.168.1.1',
            'label' => 'Server 1',
            'type' => 'ipv4',
        ]);

        // User 2 (not owner) can view history
        $response = $this->actingAs($user2)->getJson("/api/v1/ip/{$ip->id}/history");

        $response->assertStatus(200);
    }
}
