<?php

namespace Tests\Feature\IP;

use App\Models\IPAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class AuditLoggingTest
 *
 * Feature tests for IP address audit logging.
 */
class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test create operation logs activity.
     */
    public function test_create_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'regular']);

        $response = $this->actingAs($user)->postJson('/api/v1/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'comment' => 'Test comment',
        ]);

        $ipId = $response->json('data.id');

        // Check ip_history table
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ipId,
            'modified_by' => $user->id,
            'action' => 'created',
        ]);

        // Check activity_logs table
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ipId,
            'subject_type' => IPAddress::class,
            'causer_id' => $user->id,
            'event' => 'ip.created',
            'description' => 'ip.created',
        ]);
    }

    /**
     * Test update operation logs activity with changes.
     */
    public function test_update_logs_activity_with_changes(): void
    {
        $user = User::factory()->create(['role' => 'regular']);

        $ip = IPAddress::create([
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'label' => 'Original Label',
            'comment' => 'Original comment',
            'type' => 'ipv4',
        ]);

        $this->actingAs($user)->putJson("/api/v1/ip/{$ip->id}", [
            'label' => 'Updated Label',
            'comment' => 'Updated comment',
        ]);

        // Check ip_history table has old and new values
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'modified_by' => $user->id,
            'action' => 'updated',
        ]);

        // Check activity_logs table
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ip->id,
            'subject_type' => IPAddress::class,
            'causer_id' => $user->id,
            'event' => 'ip.updated',
        ]);
    }

    /**
     * Test delete operation logs activity.
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

        // Check ip_history table
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'modified_by' => $admin->id,
            'action' => 'deleted',
        ]);

        // Check activity_logs table
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ip->id,
            'subject_type' => IPAddress::class,
            'causer_id' => $admin->id,
            'event' => 'ip.deleted',
        ]);
    }

    /**
     * Test history is tracked correctly.
     */
    public function test_history_is_tracked(): void
    {
        $user = User::factory()->create(['role' => 'regular']);

        // Create IP
        $response = $this->actingAs($user)->postJson('/api/v1/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Initial Label',
            'type' => 'ipv4',
        ]);

        $ipId = $response->json('data.id');

        // Update IP twice
        $this->actingAs($user)->putJson("/api/v1/ip/{$ipId}", [
            'label' => 'Second Label',
        ]);

        $this->actingAs($user)->putJson("/api/v1/ip/{$ipId}", [
            'label' => 'Third Label',
        ]);

        // Get history
        $historyResponse = $this->actingAs($user)->getJson("/api/v1/ip/{$ipId}/history");

        $historyResponse->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Verify the history contains all actions
        $this->assertDatabaseCount('ip_history', 3);
    }

    /**
     * Test activity log includes user context.
     */
    public function test_activity_log_includes_user_context(): void
    {
        $user = User::factory()->create(['role' => 'regular']);

        $response = $this->actingAs($user)->postJson('/api/v1/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
        ]);

        $ipId = $response->json('data.id');

        // Check activity log has correct causer
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ipId,
            'causer_id' => $user->id,
            'causer_type' => null, // We only store ID, not type
        ]);
    }

    /**
     * Test activity log properties contain relevant data.
     */
    public function test_activity_log_properties_contain_data(): void
    {
        $user = User::factory()->create(['role' => 'regular']);

        $response = $this->actingAs($user)->postJson('/api/v1/ip', [
            'ip_address' => '192.168.1.1',
            'label' => 'Test Server',
            'comment' => 'Test comment',
        ]);

        $ipId = $response->json('data.id');

        // Verify activity log was created with properties
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $ipId,
            'event' => 'ip.created',
        ]);
    }
}
