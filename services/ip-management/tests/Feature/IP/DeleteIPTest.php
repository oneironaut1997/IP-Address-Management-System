<?php

namespace Tests\Feature\IP;

use App\Models\IPAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class DeleteIPTest
 *
 * Feature tests for IP address deletion endpoint.
 *
 * @package Tests\Feature\IP
 */
class DeleteIPTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test only super_admin can delete IP addresses.
     *
     * @return void
     */
    public function test_only_super_admin_can_delete(): void
    {
        $ownerId = 'user-123';
        $adminId = 'admin-456';

        $ip = IPAddress::create([
            'user_id' => $ownerId,
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
        ]);

        $response = $this->deleteJson("/api/ip/{$ip->id}", [], [
            'X-User-ID' => $adminId,
            'X-User-Role' => 'super_admin',
        ]);

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
     *
     * @return void
     */
    public function test_regular_user_cannot_delete(): void
    {
        $ownerId = 'user-123';

        $ip = IPAddress::create([
            'user_id' => $ownerId,
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
        ]);

        $response = $this->deleteJson("/api/ip/{$ip->id}", [], [
            'X-User-ID' => $ownerId,
            'X-User-Role' => 'regular',
        ]);

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
     *
     * @return void
     */
    public function test_cannot_delete_nonexistent_ip(): void
    {
        $adminId = 'admin-456';

        $response = $this->deleteJson('/api/ip/non-existent-id', [], [
            'X-User-ID' => $adminId,
            'X-User-Role' => 'super_admin',
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
     * Test delete logs activity.
     *
     * @return void
     */
    public function test_delete_logs_activity(): void
    {
        $ownerId = 'user-123';
        $adminId = 'admin-456';

        $ip = IPAddress::create([
            'user_id' => $ownerId,
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'type' => 'ipv4',
        ]);

        $this->deleteJson("/api/ip/{$ip->id}", [], [
            'X-User-ID' => $adminId,
            'X-User-Role' => 'super_admin',
        ]);

        // Check history was created
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'modified_by' => $adminId,
            'action' => 'deleted',
        ]);

        // Check activity log was created
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ip->id,
            'subject_type' => IPAddress::class,
            'causer_id' => $adminId,
            'event' => 'ip.deleted',
        ]);
    }
}
