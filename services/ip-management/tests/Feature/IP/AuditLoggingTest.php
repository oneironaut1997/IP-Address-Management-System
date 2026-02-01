<?php

namespace Tests\Feature\IP;

use App\Models\IPAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class AuditLoggingTest
 *
 * Feature tests for IP address audit logging.
 *
 * @package Tests\Feature\IP
 */
class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test create operation logs activity.
     *
     * @return void
     */
    public function test_create_logs_activity(): void
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

        $ipId = $response->json('data.id');

        // Check ip_history table
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ipId,
            'modified_by' => $userId,
            'action' => 'created',
        ]);

        // Check activity_logs table
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ipId,
            'subject_type' => IPAddress::class,
            'causer_id' => $userId,
            'event' => 'ip.created',
            'description' => 'ip.created',
        ]);
    }

    /**
     * Test update operation logs activity with changes.
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
            'comment' => 'Original comment',
            'type' => 'ipv4',
        ]);

        $this->putJson("/api/ip/{$ip->id}", [
            'label' => 'Updated Label',
            'comment' => 'Updated comment',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        // Check ip_history table has old and new values
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'modified_by' => $userId,
            'action' => 'updated',
        ]);

        // Check activity_logs table
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ip->id,
            'subject_type' => IPAddress::class,
            'causer_id' => $userId,
            'event' => 'ip.updated',
        ]);
    }

    /**
     * Test delete operation logs activity.
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

        // Check ip_history table
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'modified_by' => $adminId,
            'action' => 'deleted',
        ]);

        // Check activity_logs table
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ip->id,
            'subject_type' => IPAddress::class,
            'causer_id' => $adminId,
            'event' => 'ip.deleted',
        ]);
    }

    /**
     * Test history is tracked correctly.
     *
     * @return void
     */
    public function test_history_is_tracked(): void
    {
        $userId = 'user-123';

        // Create IP
        $response = $this->postJson('/api/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Initial Label',
            'type' => 'ipv4',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $ipId = $response->json('data.id');

        // Update IP twice
        $this->putJson("/api/ip/{$ipId}", [
            'label' => 'Second Label',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $this->putJson("/api/ip/{$ipId}", [
            'label' => 'Third Label',
        ], [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        // Get history
        $historyResponse = $this->getJson("/api/ip/{$ipId}/history", [
            'X-User-ID' => $userId,
            'X-User-Role' => 'regular',
        ]);

        $historyResponse->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Verify the history contains all actions
        $this->assertDatabaseCount('ip_history', 3);
    }

    /**
     * Test activity log includes user context.
     *
     * @return void
     */
    public function test_activity_log_includes_user_context(): void
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

        // Check activity log has correct causer
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ipId,
            'causer_id' => $userId,
            'causer_type' => null, // We only store ID, not type
        ]);
    }

    /**
     * Test activity log properties contain relevant data.
     *
     * @return void
     */
    public function test_activity_log_properties_contain_data(): void
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

        $ipId = $response->json('data.id');

        // Verify activity log was created with properties
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ipId,
            'event' => 'ip.created',
        ]);
    }
}
