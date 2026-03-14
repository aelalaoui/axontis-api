<?php

namespace Tests\Feature;

use App\Models\AlarmEvent;
use App\Models\Client;
use App\Models\Device;
use App\Models\Installation;
use App\Models\InstallationDevice;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HikvisionWebhookTest extends TestCase
{
    use RefreshDatabase;

    private InstallationDevice $installationDevice;
    private Installation $installation;
    private Client $client;
    private Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::create([
            'type'       => 'individual',
            'first_name' => 'Test',
            'last_name'  => 'Client',
            'email'      => 'test-alarm@example.com',
            'country'    => 'MA',
            'status'     => 'active',
            'step'       => 'email_step',
        ]);

        $this->installation = Installation::create([
            'client_uuid' => $this->client->uuid,
            'address'     => '123 Test Street',
            'country'     => 'MA',
            'type'        => 'first_installation',
        ]);

        $this->device = Device::create([
            'brand'       => 'Hikvision',
            'model'       => 'DS-PWA64-L-WB',
            'category'    => 'alarm_panel',
            'description' => 'Centrale alarme AX PRO',
        ]);

        // taskable_id et task_id sont des bigint — tous les modèles ont primaryKey='uuid'
        // On doit récupérer les ids physiques via DB::table
        $task = $this->createTaskForInstallation($this->installation);

        $this->installationDevice = $this->createInstallationDevice($task, $this->device, 'Q25385404');

        $this->installationDevice->setProperty('webhook_secret', 'test-secret-key');
        $this->installationDevice->setProperty('hpp_device_id', 'hpp-test-123');
        $this->installationDevice->setProperty('arm_status', 'disarmed');
        $this->installationDevice->setProperty('connection_status', 'online');
    }

    // ─── Webhook Reception ───────────────────────────────────

    public function test_webhook_returns_202_for_valid_payload(): void
    {
        Queue::fake();
        $payload = $this->buildCidPayload(1759, 3130, 3);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload))
            ->assertStatus(202)->assertJson(['status' => 'accepted']);
    }

    public function test_webhook_creates_alarm_event_in_database(): void
    {
        Queue::fake();
        $payload = $this->buildCidPayload(1759, 3130, 3);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload));

        $this->assertDatabaseHas('alarm_events', [
            'installation_device_uuid' => $this->installationDevice->uuid,
            'installation_uuid'        => $this->installation->uuid,
            'cid_code'                 => 1759,
            'standard_cid_code'        => 3130,
            'event_type'               => 'cidEvent',
            'category'                 => 'intrusion',
            'severity'                 => 'critical',
            'zone_number'              => 3,
            'processed'                => false,
        ]);
    }

    public function test_webhook_dispatches_process_alarm_event_job(): void
    {
        Queue::fake();
        $payload = $this->buildCidPayload(1759, 3130, 3);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload));
        Queue::assertPushedOn('alarm-events', \App\Jobs\ProcessAlarmEventJob::class);
    }

    // ─── Security ────────────────────────────────────────────

    public function test_webhook_returns_404_for_unknown_serial(): void
    {
        $this->postJson('/api/webhooks/hikvision/UNKNOWN_SERIAL', $this->buildCidPayload(1759, 3130, 1))
            ->assertStatus(404);
    }

    public function test_webhook_rejects_invalid_hmac_signature(): void
    {
        $payload = $this->buildCidPayload(1759, 3130, 1);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, ['X-Hikvision-Signature' => 'invalid-signature'])
            ->assertStatus(403);
    }

    // ─── Deduplication ───────────────────────────────────────

    public function test_webhook_deduplicates_same_event_within_window(): void
    {
        Queue::fake();
        $payload = $this->buildCidPayload(1759, 3130, 3);
        $headers = $this->buildWebhookHeaders($payload);

        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $headers)->assertStatus(202);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $headers)->assertStatus(202);

        $this->assertEquals(1, AlarmEvent::where('installation_device_uuid', $this->installationDevice->uuid)->count());
    }

    // ─── Multi-Tenant Isolation ──────────────────────────────

    public function test_events_are_scoped_to_installation(): void
    {
        Queue::fake();

        $otherClient = Client::create([
            'type' => 'individual', 'first_name' => 'Other', 'last_name' => 'Client',
            'email' => 'other@example.com', 'country' => 'MA', 'status' => 'active', 'step' => 'email_step',
        ]);
        $otherInstallation = Installation::create([
            'client_uuid' => $otherClient->uuid, 'address' => '456 Other Street',
            'country' => 'MA', 'type' => 'first_installation',
        ]);

        $payload = $this->buildCidPayload(1759, 3130, 1);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload));

        $event = AlarmEvent::where('installation_device_uuid', $this->installationDevice->uuid)->first();
        $this->assertEquals($this->installation->uuid, $event->installation_uuid);
        $this->assertEquals(0, AlarmEvent::forInstallation($otherInstallation->uuid)->count());
    }

    // ─── InstallationDevice Validation ───────────────────────

    public function test_installation_device_is_alarm_panel(): void
    {
        $this->assertTrue($this->installationDevice->isAlarmPanel());

        $cameraDevice = Device::create(['brand' => 'Hikvision', 'model' => 'DS-2CD2143G2', 'category' => 'camera']);
        $task = $this->createTaskForInstallation($this->installation);
        $cameraId = $this->createInstallationDevice($task, $cameraDevice);

        $this->assertFalse($cameraId->isAlarmPanel());
    }

    public function test_scope_alarm_panels_filters_correctly(): void
    {
        $cameraDevice = Device::create(['brand' => 'Hikvision', 'model' => 'DS-2CD2143G2', 'category' => 'camera']);
        $task = $this->createTaskForInstallation($this->installation);
        $this->createInstallationDevice($task, $cameraDevice);

        $panels = InstallationDevice::alarmPanels()->get();
        $this->assertCount(1, $panels);
        $this->assertEquals($this->installationDevice->uuid, $panels->first()->uuid);
    }

    // ─── CID Mapping ─────────────────────────────────────────

    public function test_cid_mapping_enriches_event_correctly(): void
    {
        Queue::fake();
        $payload = $this->buildCidPayload(110, 110, 1);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload));
        Cache::flush();

        $event = AlarmEvent::where('installation_device_uuid', $this->installationDevice->uuid)->first();
        $this->assertEquals('fire', $event->category);
        $this->assertEquals('critical', $event->severity);
    }

    public function test_arming_event_does_not_have_alert_severity(): void
    {
        Queue::fake();
        $payload = $this->buildCidPayload(3401, 3401, null);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload));

        $event = AlarmEvent::where('installation_device_uuid', $this->installationDevice->uuid)->first();
        $this->assertEquals('arming', $event->category);
        $this->assertEquals('info', $event->severity);
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function buildCidPayload(int $code, int $standardCode, ?int $zone): array
    {
        return [
            'ipAddress' => '192.168.1.100', 'macAddress' => 'AA:BB:CC:DD:EE:FF',
            'dateTime' => now()->toIso8601String(), 'eventType' => 'cidEvent', 'eventState' => 'active',
            'CIDEvent' => [
                'code' => $code, 'standardCIDcode' => $standardCode,
                'type' => 'zoneAlarm', 'trigger' => now()->toIso8601String(), 'zone' => $zone,
            ],
        ];
    }

    private function buildWebhookHeaders(array $payload): array
    {
        return ['X-Hikvision-Signature' => hash_hmac('sha256', json_encode($payload), 'test-secret-key')];
    }

    /**
     * Crée une Task polymorphique liée à une Installation via uuid→uuid.
     */
    private function createTaskForInstallation(Installation $installation): Task
    {
        return Task::create([
            'taskable_type' => Installation::class,
            'taskable_uuid' => $installation->uuid,
            'address'       => $installation->address,
            'type'          => 'installation',
            'status'        => 'scheduled',
        ]);
    }

    /**
     * Crée un InstallationDevice via task_uuid/device_uuid (uuid → uuid).
     */
    private function createInstallationDevice(Task $task, Device $device, ?string $serialNumber = null): InstallationDevice
    {
        return InstallationDevice::create([
            'task_uuid'     => $task->uuid,
            'device_uuid'   => $device->uuid,
            'serial_number' => $serialNumber,
            'status'        => 'installed',
        ]);
    }
}

