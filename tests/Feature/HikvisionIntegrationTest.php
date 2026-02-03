<?php

namespace Tests\Feature;

use App\Models\AlarmDevice;
use App\Models\AlarmEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests pour l'intégration Hikvision AX PRO.
 */
class HikvisionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Health check endpoint is accessible.
     */
    public function test_health_check_returns_ok(): void
    {
        $response = $this->getJson('/api/webhooks/hikvision/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    /**
     * Test: Webhook receives and stores a valid alarm event.
     */
    public function test_webhook_receives_valid_alarm_event(): void
    {
        Queue::fake();

        // Create a device first
        $device = AlarmDevice::create([
            'name' => 'Test Central',
            'serial_number' => 'TEST-001',
            'model' => 'DS-PWA64-L-WB',
            'ip_address' => '192.168.1.100',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'status' => 'online',
        ]);

        $payload = [
            'ipAddress' => '192.168.1.100',
            'macAddress' => 'AA:BB:CC:DD:EE:FF',
            'eventType' => 'cidEvent',
            'eventState' => 'active',
            'dateTime' => now()->toIso8601String(),
            'CIDEvent' => [
                'code' => 1759,
                'standardCIDcode' => 3130,
                'type' => 'zoneAlarm',
                'trigger' => now()->toIso8601String(),
                'zone' => 1,
            ],
        ];

        $response = $this->postJson('/api/webhooks/hikvision/alarm', $payload);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'message' => 'Event received and queued for processing',
            ])
            ->assertJsonStructure([
                'event_uuid',
                'processing_time_ms',
            ]);

        // Verify event was created
        $this->assertDatabaseHas('alarm_events', [
            'event_type' => 'cidEvent',
            'cid_code' => 1759,
            'zone_number' => 1,
            'alarm_device_uuid' => $device->uuid,
        ]);

        // Verify job was dispatched
        Queue::assertPushed(\App\Jobs\ProcessAlarmEventJob::class);
    }

    /**
     * Test: Webhook handles unknown device gracefully.
     */
    public function test_webhook_handles_unknown_device(): void
    {
        Queue::fake();

        $payload = [
            'ipAddress' => '10.0.0.1',
            'macAddress' => '11:22:33:44:55:66',
            'eventType' => 'cidEvent',
            'eventState' => 'active',
            'CIDEvent' => [
                'code' => 110,
                'zone' => 1,
            ],
        ];

        $response = $this->postJson('/api/webhooks/hikvision/alarm', $payload);

        // Should still accept the event
        $response->assertStatus(202);

        // Event stored without device link
        $this->assertDatabaseHas('alarm_events', [
            'source_mac' => '11:22:33:44:55:66',
            'alarm_device_uuid' => null,
        ]);
    }

    /**
     * Test: Webhook rejects invalid payload.
     */
    public function test_webhook_rejects_invalid_payload(): void
    {
        $payload = [
            // Missing eventType
            'ipAddress' => '192.168.1.100',
        ];

        $response = $this->postJson('/api/webhooks/hikvision/alarm', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['eventType']);
    }

    /**
     * Test: Duplicate events are detected.
     */
    public function test_duplicate_events_are_detected(): void
    {
        Queue::fake();

        $device = AlarmDevice::create([
            'name' => 'Test Central',
            'serial_number' => 'TEST-002',
            'mac_address' => 'AA:BB:CC:DD:EE:01',
        ]);

        $payload = [
            'macAddress' => 'AA:BB:CC:DD:EE:01',
            'eventType' => 'cidEvent',
            'eventState' => 'active',
            'dateTime' => '2026-02-03T10:00:00+00:00',
            'CIDEvent' => [
                'code' => 1759,
                'zone' => 1,
                'trigger' => '2026-02-03T10:00:00+00:00',
            ],
        ];

        // First request
        $this->postJson('/api/webhooks/hikvision/alarm', $payload)
            ->assertStatus(202);

        // Second identical request
        $response = $this->postJson('/api/webhooks/hikvision/alarm', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Duplicate event acknowledged',
            ]);
    }

    /**
     * Test: AlarmDevice can be created via API.
     */
    public function test_alarm_device_can_be_created(): void
    {
        $this->actingAsManager();

        $data = [
            'name' => 'New Alarm Central',
            'serial_number' => 'DS-PWA64-L-WB-12345',
            'model' => 'DS-PWA64-L-WB',
            'ip_address' => '192.168.1.50',
            'mac_address' => 'AA:BB:CC:DD:EE:02',
            'port' => 80,
            'api_username' => 'admin',
            'api_password' => 'password123',
        ];

        $response = $this->postJson('/api/alarm-devices', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Device created successfully',
            ]);

        $this->assertDatabaseHas('alarm_devices', [
            'name' => 'New Alarm Central',
            'serial_number' => 'DS-PWA64-L-WB-12345',
        ]);
    }

    /**
     * Test: AlarmDevice list with filters.
     */
    public function test_alarm_device_list_with_filters(): void
    {
        $this->actingAsManager();

        AlarmDevice::create([
            'name' => 'Online Device',
            'serial_number' => 'ONLINE-001',
            'status' => 'online',
        ]);

        AlarmDevice::create([
            'name' => 'Offline Device',
            'serial_number' => 'OFFLINE-001',
            'status' => 'offline',
        ]);

        // Filter by status
        $response = $this->getJson('/api/alarm-devices?status=online');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }

    /**
     * Test: AlarmEvent statistics.
     */
    public function test_alarm_event_statistics(): void
    {
        $this->actingAsManager();

        $device = AlarmDevice::create([
            'name' => 'Stats Device',
            'serial_number' => 'STATS-001',
        ]);

        // Create some events
        AlarmEvent::create([
            'alarm_device_uuid' => $device->uuid,
            'event_type' => 'cidEvent',
            'cid_code' => 1759,
            'alarm_type' => 'intrusion',
            'severity' => 'critical',
            'raw_payload' => [],
            'triggered_at' => now(),
        ]);

        AlarmEvent::create([
            'alarm_device_uuid' => $device->uuid,
            'event_type' => 'cidEvent',
            'cid_code' => 110,
            'alarm_type' => 'fire',
            'severity' => 'critical',
            'raw_payload' => [],
            'triggered_at' => now(),
        ]);

        $response = $this->getJson('/api/alarm-events/stats?period=day');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'period',
                'data' => [
                    'total_events',
                    'by_type',
                    'by_severity',
                ],
            ]);
    }

    /**
     * Helper: Act as a manager user.
     */
    protected function actingAsManager(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => 'manager',
        ]);

        $this->actingAs($user);
    }
}
