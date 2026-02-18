<?php

namespace Tests\Feature\IP;

use App\Models\IPAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class DeleteIPTest
 *
 * Feature tests for IP address deletion endpoint.
 */
class DeleteIPTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test only super_admin can delete IP addresses.
     */
    public function test_only_super_admin_can_delete(): void
    {
        $owner = User::factory()->create(['role' => 'regular']);
        $admin = User::factory()->create(['id' => 'admin-456', 'role' => 'super_admin']);

        $ip = IPAddress::create([
            'user_id' => $owner->id,
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/ip/{$ip->id}");

        $response->assertStatus(204);

        // Check soft delete worked
        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip->id,
        ]);

        $this->assertSoftDeleted('ip_addresses', [
            'id' => $ip->id,
        ]);
    }

    /**
     * Test regular user cannot delete IP addresses.
     */
    public function test_regular_user_cannot_delete(): void
    {
        $user = User::factory()->create(['role' => 'regular']);

        $ip = IPAddress::create([
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/ip/{$ip->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Only super administrators can delete IP addresses.',
                ],
            ]);

        // Check IP was not deleted
        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test delete fails for non-existent IP.
     */
    public function test_cannot_delete_nonexistent_ip(): void
    {
        $admin = User::factory()->create(['id' => 'admin-456', 'role' => 'super_admin']);

        $response = $this->actingAs($admin)->deleteJson('/api/v1/ip/non-existent-id');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    /**
     * Test delete logs activity.
     */
    public function test_delete_logs_activity(): void
    {
        $owner = User::factory()->create(['role' => 'regular']);
        $admin = User::factory()->create(['id' => 'admin-456', 'role' => 'super_admin']);

        $ip = IPAddress::create([
            'user_id' => $owner->id,
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
        ]);

        $this->actingAs($admin)->deleteJson("/api/v1/ip/{$ip->id}");

        // Check history was created
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'modified_by' => $admin->id,
            'action' => 'deleted',
        ]);

        // Check activity log was created
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ip->id,
            'subject_type' => IPAddress::class,
            'causer_id' => $admin->id,
            'event' => 'ip.deleted',
        ]);
    }
}
