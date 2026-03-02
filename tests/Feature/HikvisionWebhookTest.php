<?php

namespace Tests\Feature;

use App\Models\AlarmEvent;
use App\Models\Client;
use App\Models\Device;
use App\Models\Installation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HikvisionWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Device $device;
    private Installation $installation;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client avec installation et device alarm_panel
        $this->client = Client::create([
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'test-alarm@example.com',
            'country' => 'MA',
            'status' => 'active_client',
            'step' => 'email_step',
        ]);

        $this->installation = Installation::create([
            'client_uuid' => $this->client->uuid,
            'address' => '123 Test Street',
            'country' => 'MA',
            'type' => 'first_installation',
        ]);

        $this->device = Device::create([
            'brand' => 'Hikvision',
            'model' => 'DS-PWA64-L-WB',
            'category' => 'alarm_panel',
            'description' => 'Centrale alarme AX PRO',
            'installation_uuid' => $this->installation->uuid,
        ]);

        // Configurer les properties nécessaires
        $this->device->setProperty('panel_serial_number', 'Q25385404');
        $this->device->setProperty('webhook_secret', 'test-secret-key');
        $this->device->setProperty('hpp_device_id', 'hpp-test-123');
        $this->device->setProperty('arm_status', 'disarmed');
        $this->device->setProperty('connection_status', 'online');
    }

    // ─── Webhook Reception ───────────────────────────────────

    public function test_webhook_returns_202_for_valid_payload(): void
    {
        Queue::fake();

        $payload = $this->buildCidPayload(1759, 3130, 3);

        $response = $this->postJson(
            '/api/webhooks/hikvision/Q25385404',
            $payload,
            $this->buildWebhookHeaders($payload)
        );

        $response->assertStatus(202)
            ->assertJson(['status' => 'accepted']);
    }

    public function test_webhook_creates_alarm_event_in_database(): void
    {
        Queue::fake();

        $payload = $this->buildCidPayload(1759, 3130, 3);

        $this->postJson(
            '/api/webhooks/hikvision/Q25385404',
            $payload,
            $this->buildWebhookHeaders($payload)
        );

        $this->assertDatabaseHas('alarm_events', [
            'device_uuid' => $this->device->uuid,
            'installation_uuid' => $this->installation->uuid,
            'cid_code' => 1759,
            'standard_cid_code' => 3130,
            'event_type' => 'cidEvent',
            'category' => 'intrusion',
            'severity' => 'critical',
            'zone_number' => 3,
            'processed' => false,
        ]);
    }

    public function test_webhook_dispatches_process_alarm_event_job(): void
    {
        Queue::fake();

        $payload = $this->buildCidPayload(1759, 3130, 3);

        $this->postJson(
            '/api/webhooks/hikvision/Q25385404',
            $payload,
            $this->buildWebhookHeaders($payload)
        );

        Queue::assertPushedOn('alarm-events', \App\Jobs\ProcessAlarmEventJob::class);
    }

    // ─── Security ────────────────────────────────────────────

    public function test_webhook_returns_404_for_unknown_serial(): void
    {
        $response = $this->postJson(
            '/api/webhooks/hikvision/UNKNOWN_SERIAL',
            $this->buildCidPayload(1759, 3130, 1)
        );

        $response->assertStatus(404);
    }

    public function test_webhook_rejects_invalid_hmac_signature(): void
    {
        $payload = $this->buildCidPayload(1759, 3130, 1);

        $response = $this->postJson(
            '/api/webhooks/hikvision/Q25385404',
            $payload,
            ['X-Hikvision-Signature' => 'invalid-signature']
        );

        $response->assertStatus(403);
    }

    // ─── Deduplication ───────────────────────────────────────

    public function test_webhook_deduplicates_same_event_within_window(): void
    {
        Queue::fake();

        $payload = $this->buildCidPayload(1759, 3130, 3);
        $headers = $this->buildWebhookHeaders($payload);

        // Premier appel — passe
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $headers)
            ->assertStatus(202);

        // Deuxième appel — dédupliqué
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $headers)
            ->assertStatus(202);

        // Un seul event en base
        $this->assertEquals(1, AlarmEvent::where('device_uuid', $this->device->uuid)->count());
    }

    // ─── Multi-Tenant Isolation ──────────────────────────────

    public function test_events_are_scoped_to_installation(): void
    {
        Queue::fake();

        // Créer un second client avec une autre installation
        $otherClient = Client::create([
            'type' => 'individual',
            'first_name' => 'Other',
            'last_name' => 'Client',
            'email' => 'other@example.com',
            'country' => 'MA',
            'status' => 'active_client',
            'step' => 'email_step',
        ]);

        $otherInstallation = Installation::create([
            'client_uuid' => $otherClient->uuid,
            'address' => '456 Other Street',
            'country' => 'MA',
            'type' => 'first_installation',
        ]);

        // Envoyer un webhook pour le device principal
        $payload = $this->buildCidPayload(1759, 3130, 1);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload));

        // L'événement est associé à la bonne installation
        $event = AlarmEvent::where('device_uuid', $this->device->uuid)->first();
        $this->assertEquals($this->installation->uuid, $event->installation_uuid);

        // La recherche scopée à l'autre installation ne retourne rien
        $eventsForOther = AlarmEvent::forInstallation($otherInstallation->uuid)->count();
        $this->assertEquals(0, $eventsForOther);
    }

    // ─── Device Validation ───────────────────────────────────

    public function test_device_is_alarm_panel(): void
    {
        $this->assertTrue($this->device->isAlarmPanel());

        $otherDevice = Device::create([
            'brand' => 'Hikvision',
            'model' => 'DS-2CD2143G2',
            'category' => 'camera',
        ]);

        $this->assertFalse($otherDevice->isAlarmPanel());
    }

    public function test_scope_alarm_panels_filters_correctly(): void
    {
        Device::create([
            'brand' => 'Hikvision',
            'model' => 'DS-2CD2143G2',
            'category' => 'camera',
        ]);

        $panels = Device::alarmPanels()->get();
        $this->assertCount(1, $panels);
        $this->assertEquals($this->device->uuid, $panels->first()->uuid);
    }

    // ─── CID Mapping ─────────────────────────────────────────

    public function test_cid_mapping_enriches_event_correctly(): void
    {
        Queue::fake();

        // Fire event (CID 110)
        $payload = $this->buildCidPayload(110, 110, 1);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload));

        // Nettoyer la dédup cache pour le prochain test
        Cache::flush();

        $event = AlarmEvent::where('device_uuid', $this->device->uuid)->first();
        $this->assertEquals('fire', $event->category);
        $this->assertEquals('critical', $event->severity);
    }

    public function test_arming_event_does_not_have_alert_severity(): void
    {
        Queue::fake();

        // Arming event (CID 3401)
        $payload = $this->buildCidPayload(3401, 3401, null);
        $this->postJson('/api/webhooks/hikvision/Q25385404', $payload, $this->buildWebhookHeaders($payload));

        $event = AlarmEvent::where('device_uuid', $this->device->uuid)->first();
        $this->assertEquals('arming', $event->category);
        $this->assertEquals('info', $event->severity);
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function buildCidPayload(int $code, int $standardCode, ?int $zone): array
    {
        return [
            'ipAddress' => '192.168.1.100',
            'macAddress' => 'AA:BB:CC:DD:EE:FF',
            'dateTime' => now()->toIso8601String(),
            'eventType' => 'cidEvent',
            'eventState' => 'active',
            'CIDEvent' => [
                'code' => $code,
                'standardCIDcode' => $standardCode,
                'type' => 'zoneAlarm',
                'trigger' => now()->toIso8601String(),
                'zone' => $zone,
            ],
        ];
    }

    private function buildWebhookHeaders(array $payload): array
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'test-secret-key');

        return [
            'X-Hikvision-Signature' => $signature,
        ];
    }
}

