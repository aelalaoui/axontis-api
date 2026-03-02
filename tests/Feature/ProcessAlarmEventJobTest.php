<?php

namespace Tests\Feature;

use App\Jobs\CreateAlertFromAlarmEventJob;
use App\Jobs\ProcessAlarmEventJob;
use App\Models\AlarmEvent;
use App\Models\Client;
use App\Models\Device;
use App\Models\Installation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessAlarmEventJobTest extends TestCase
{
    use RefreshDatabase;

    private Device $device;
    private Installation $installation;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::create([
            'type' => 'individual',
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'job-test@example.com',
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
            'installation_uuid' => $this->installation->uuid,
        ]);

        $this->device->setProperty('panel_serial_number', 'Q25385404');
        $this->device->setProperty('arm_status', 'disarmed');
    }

    public function test_process_event_job_marks_event_as_processed(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);

        $event = AlarmEvent::create([
            'device_uuid' => $this->device->uuid,
            'installation_uuid' => $this->installation->uuid,
            'cid_code' => 1759,
            'standard_cid_code' => 3130,
            'event_type' => 'cidEvent',
            'triggered_at' => now(),
            'raw_payload' => ['test' => true],
            'processed' => false,
        ]);

        $job = new ProcessAlarmEventJob($event);
        $job->handle();

        $event->refresh();
        $this->assertTrue($event->processed);
        $this->assertNotNull($event->processed_at);
    }

    public function test_critical_event_dispatches_create_alert_job(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);

        $event = AlarmEvent::create([
            'device_uuid' => $this->device->uuid,
            'installation_uuid' => $this->installation->uuid,
            'cid_code' => 1759,
            'standard_cid_code' => 3130,
            'event_type' => 'cidEvent',
            'category' => 'intrusion',
            'severity' => 'critical',
            'triggered_at' => now(),
            'raw_payload' => ['test' => true],
            'processed' => false,
        ]);

        $job = new ProcessAlarmEventJob($event);
        $job->handle();

        Queue::assertPushed(CreateAlertFromAlarmEventJob::class);
    }

    public function test_info_event_does_not_dispatch_create_alert_job(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);

        $event = AlarmEvent::create([
            'device_uuid' => $this->device->uuid,
            'installation_uuid' => $this->installation->uuid,
            'cid_code' => 3401,
            'event_type' => 'cidEvent',
            'category' => 'arming',
            'severity' => 'info',
            'triggered_at' => now(),
            'raw_payload' => ['test' => true],
            'processed' => false,
        ]);

        $job = new ProcessAlarmEventJob($event);
        $job->handle();

        Queue::assertNotPushed(CreateAlertFromAlarmEventJob::class);
    }

    public function test_arming_event_updates_device_arm_status(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);

        // Armement total (CID 3401)
        $event = AlarmEvent::create([
            'device_uuid' => $this->device->uuid,
            'installation_uuid' => $this->installation->uuid,
            'cid_code' => 3401,
            'event_type' => 'cidEvent',
            'triggered_at' => now(),
            'raw_payload' => ['test' => true],
            'processed' => false,
        ]);

        $job = new ProcessAlarmEventJob($event);
        $job->handle();

        $this->assertEquals('armed_away', $this->device->getArmStatus());
    }

    public function test_disarm_event_updates_device_arm_status(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);

        $this->device->setProperty('arm_status', 'armed_away');

        $event = AlarmEvent::create([
            'device_uuid' => $this->device->uuid,
            'installation_uuid' => $this->installation->uuid,
            'cid_code' => 1401,
            'event_type' => 'cidEvent',
            'triggered_at' => now(),
            'raw_payload' => ['test' => true],
            'processed' => false,
        ]);

        $job = new ProcessAlarmEventJob($event);
        $job->handle();

        $this->assertEquals('disarmed', $this->device->getArmStatus());
    }

    public function test_heartbeat_updates_connection_status(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);

        $event = AlarmEvent::create([
            'device_uuid' => $this->device->uuid,
            'installation_uuid' => $this->installation->uuid,
            'event_type' => 'heartbeat',
            'triggered_at' => now(),
            'raw_payload' => ['test' => true],
            'processed' => false,
        ]);

        $job = new ProcessAlarmEventJob($event);
        $job->handle();

        $this->assertEquals('online', $this->device->getConnectionStatus());
        $this->assertNotNull($this->device->getProperty('last_heartbeat_at'));
    }

    public function test_create_alert_job_creates_alert_from_event(): void
    {
        Event::fake();

        $event = AlarmEvent::create([
            'device_uuid' => $this->device->uuid,
            'installation_uuid' => $this->installation->uuid,
            'cid_code' => 1759,
            'standard_cid_code' => 3130,
            'event_type' => 'cidEvent',
            'category' => 'intrusion',
            'severity' => 'critical',
            'zone_number' => 3,
            'triggered_at' => now(),
            'raw_payload' => ['test' => true],
            'processed' => true,
            'processed_at' => now(),
        ]);

        $job = new CreateAlertFromAlarmEventJob($event);
        $job->handle();

        // Vérifier qu'une alerte a été créée
        $this->assertDatabaseHas('alerts', [
            'client_uuid' => $this->client->uuid,
            'type' => 'alarm_intrusion',
            'severity' => 'critical',
            'resolved' => false,
        ]);

        // Vérifier que l'event est lié à l'alerte
        $event->refresh();
        $this->assertNotNull($event->alert_uuid);
    }
}

