<?php

namespace Tests\Unit\Services;

use App\Models\IPAddress;
use App\Models\IPHistory;
use App\Services\IPService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class IPServiceTest
 *
 * Unit tests for IPService business logic.
 */
class IPServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IPService $ipService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ipService = new IPService;
    }

    /**
     * Test getting all IP addresses returns ordered collection.
     */
    public function test_get_all_ip_addresses_returns_ordered_collection(): void
    {
        $ip1 = IPAddress::factory()->create(['created_at' => now()->subDay()]);
        $ip2 = IPAddress::factory()->create(['created_at' => now()]);
        $ip3 = IPAddress::factory()->create(['created_at' => now()->subDays(2)]);

        $result = $this->ipService->getAllIPAddresses();

        $this->assertCount(3, $result);
        // Should be ordered by created_at desc
        $this->assertEquals($ip2->id, $result->first()->id);
        $this->assertEquals($ip3->id, $result->last()->id);
    }

    /**
     * Test creating IP address with valid IPv4.
     */
    public function test_create_ip_address_with_valid_ipv4(): void
    {
        $data = [
            'ip_address' => '192.168.1.1',
            'label' => 'Test IP',
            'comment' => 'Test comment',
        ];

        $result = $this->ipService->createIPAddress($data, 'user-uuid-123');

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(IPAddress::class, $result['ip']);
        $this->assertEquals('192.168.1.1', $result['ip']->ip_address);
        $this->assertEquals('ipv4', $result['ip']->type);
        $this->assertEquals('user-uuid-123', $result['ip']->user_id);

        // Assert history was created
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $result['ip']->id,
            'action' => 'created',
            'modified_by' => 'user-uuid-123',
        ]);
    }

    /**
     * Test creating IP address with valid IPv6.
     */
    public function test_create_ip_address_with_valid_ipv6(): void
    {
        $data = [
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'label' => 'Test IPv6',
            'comment' => 'Test IPv6 comment',
        ];

        $result = $this->ipService->createIPAddress($data, 'user-uuid-123');

        $this->assertTrue($result['success']);
        $this->assertEquals('ipv6', $result['ip']->type);
    }

    /**
     * Test creating IP address with invalid format returns error.
     */
    public function test_create_ip_address_with_invalid_format_returns_error(): void
    {
        $data = [
            'ip_address' => 'invalid-ip-address',
            'label' => 'Test IP',
            'comment' => 'Test comment',
        ];

        $result = $this->ipService->createIPAddress($data, 'user-uuid-123');

        $this->assertFalse($result['success']);
        $this->assertNull($result['ip']);
        $this->assertEquals('VALIDATION_ERROR', $result['error']['code']);
        $this->assertEquals('Invalid IP address format.', $result['error']['message']);
    }

    /**
     * Test getting IP address by ID returns model with history.
     */
    public function test_get_ip_address_by_id_returns_model_with_history(): void
    {
        $ip = IPAddress::factory()->create();
        IPHistory::factory()->create(['ip_address_id' => $ip->id]);

        $result = $this->ipService->getIPAddressById($ip->id);

        $this->assertInstanceOf(IPAddress::class, $result);
        $this->assertEquals($ip->id, $result->id);
        $this->assertTrue($result->relationLoaded('history'));
    }

    /**
     * Test getting IP address by non-existent ID returns null.
     */
    public function test_get_ip_address_by_non_existent_id_returns_null(): void
    {
        $result = $this->ipService->getIPAddressById('non-existent-id');

        $this->assertNull($result);
    }

    /**
     * Test updating IP address creates history record.
     */
    public function test_update_ip_address_creates_history_record(): void
    {
        $ip = IPAddress::factory()->create([
            'label' => 'Original Label',
            'comment' => 'Original Comment',
        ]);

        $updateData = [
            'label' => 'Updated Label',
            'comment' => 'Updated Comment',
        ];

        $result = $this->ipService->updateIPAddress($ip, $updateData, 'user-uuid-123');

        $this->assertEquals('Updated Label', $result->label);
        $this->assertEquals('Updated Comment', $result->comment);

        // Assert history was created
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'action' => 'updated',
            'modified_by' => 'user-uuid-123',
        ]);

        // Verify old values were recorded
        $history = IPHistory::where('ip_address_id', $ip->id)->first();
        $this->assertEquals('Original Label', $history->old_values['label']);
        $this->assertEquals('Updated Label', $history->new_values['label']);
    }

    /**
     * Test deleting IP address soft deletes and creates history.
     */
    public function test_delete_ip_address_soft_deletes_and_creates_history(): void
    {
        $ip = IPAddress::factory()->create();

        $this->ipService->deleteIPAddress($ip, 'user-uuid-123');

        // Assert soft deleted
        $this->assertSoftDeleted('ip_addresses', ['id' => $ip->id]);

        // Assert history was created
        $this->assertDatabaseHas('ip_history', [
            'ip_address_id' => $ip->id,
            'action' => 'deleted',
            'modified_by' => 'user-uuid-123',
        ]);
    }

    /**
     * Test getting IP address history returns ordered collection.
     */
    public function test_get_ip_address_history_returns_ordered_collection(): void
    {
        $ip = IPAddress::factory()->create();
        IPHistory::factory()->create([
            'ip_address_id' => $ip->id,
            'created_at' => now()->subDay(),
        ]);
        IPHistory::factory()->create([
            'ip_address_id' => $ip->id,
            'created_at' => now(),
        ]);

        $result = $this->ipService->getIPAddressHistory($ip->id);

        $this->assertCount(2, $result);
        // Should be ordered by created_at desc
        $this->assertTrue($result->first()->created_at->isAfter($result->last()->created_at));
    }

    /**
     * Test can update returns true for owner.
     */
    public function test_can_update_returns_true_for_owner(): void
    {
        $ip = IPAddress::factory()->create(['user_id' => 'user-uuid-123']);

        $result = $this->ipService->canUpdate($ip, 'user-uuid-123', 'regular');

        $this->assertTrue($result);
    }

    /**
     * Test can update returns true for super admin.
     */
    public function test_can_update_returns_true_for_super_admin(): void
    {
        $ip = IPAddress::factory()->create(['user_id' => 'other-user-uuid']);

        $result = $this->ipService->canUpdate($ip, 'super-admin-uuid', 'super_admin');

        $this->assertTrue($result);
    }

    /**
     * Test can update returns false for non-owner non-admin.
     */
    public function test_can_update_returns_false_for_non_owner_non_admin(): void
    {
        $ip = IPAddress::factory()->create(['user_id' => 'other-user-uuid']);

        $result = $this->ipService->canUpdate($ip, 'user-uuid-123', 'regular');

        $this->assertFalse($result);
    }

    /**
     * Test can delete returns true for super admin.
     */
    public function test_can_delete_returns_true_for_super_admin(): void
    {
        $result = $this->ipService->canDelete('super_admin');

        $this->assertTrue($result);
    }

    /**
     * Test can delete returns false for non-super admin.
     */
    public function test_can_delete_returns_false_for_non_super_admin(): void
    {
        $result = $this->ipService->canDelete('regular');

        $this->assertFalse($result);
    }
}
