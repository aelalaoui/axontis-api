<?php

namespace Tests\Feature;

use App\Jobs\CreateAlertFromAlarmEventJob;
use App\Jobs\ProcessAlarmEventJob;
use App\Models\AlarmEvent;
use App\Models\Client;
use App\Models\Device;
use App\Models\Installation;
use App\Models\InstallationDevice;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessAlarmEventJobTest extends TestCase
{
    use RefreshDatabase;

    private InstallationDevice $installationDevice;
    private Installation $installation;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::create([
            'type' => 'individual', 'first_name' => 'Test', 'last_name' => 'Client',
            'email' => 'job-test@example.com', 'country' => 'MA', 'status' => 'active', 'step' => 'email_step',
        ]);

        $this->installation = Installation::create([
            'client_uuid' => $this->client->uuid, 'address' => '123 Test Street',
            'country' => 'MA', 'type' => 'first_installation',
        ]);

        $device = Device::create(['brand' => 'Hikvision', 'model' => 'DS-PWA64-L-WB', 'category' => 'alarm_panel']);

        // Récupérer les ids bigint physiques (primaryKey='uuid' sur tous les modèles)
        $task = Task::create([
            'taskable_type' => Installation::class,
            'taskable_uuid' => $this->installation->uuid,
            'address'       => $this->installation->address,
            'type'          => 'installation',
            'status'        => 'scheduled',
        ]);

        $this->installationDevice = InstallationDevice::create([
            'task_uuid'     => $task->uuid,
            'device_uuid'   => $device->uuid,
            'serial_number' => 'Q25385404',
            'status'        => 'installed',
        ]);

        $this->installationDevice->setProperty('arm_status', 'disarmed');
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function makeEvent(array $overrides = []): AlarmEvent
    {
        return AlarmEvent::create(array_merge([
            'installation_device_uuid' => $this->installationDevice->uuid,
            'installation_uuid'        => $this->installation->uuid,
            'event_type'               => 'cidEvent',
            'triggered_at'             => now(),
            'raw_payload'              => ['test' => true],
            'processed'                => false,
        ], $overrides));
    }

    // ─── Tests ───────────────────────────────────────────────

    public function test_process_event_job_marks_event_as_processed(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);
        $event = $this->makeEvent(['cid_code' => 1759, 'standard_cid_code' => 3130]);
        (new ProcessAlarmEventJob($event))->handle();

        $event->refresh();
        $this->assertTrue($event->processed);
        $this->assertNotNull($event->processed_at);
    }

    public function test_critical_event_dispatches_create_alert_job(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);
        $event = $this->makeEvent(['cid_code' => 1759, 'standard_cid_code' => 3130, 'category' => 'intrusion', 'severity' => 'critical']);
        (new ProcessAlarmEventJob($event))->handle();
        Queue::assertPushed(CreateAlertFromAlarmEventJob::class);
    }

    public function test_info_event_does_not_dispatch_create_alert_job(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);
        $event = $this->makeEvent(['cid_code' => 3401, 'category' => 'arming', 'severity' => 'info']);
        (new ProcessAlarmEventJob($event))->handle();
        Queue::assertNotPushed(CreateAlertFromAlarmEventJob::class);
    }

    public function test_arming_event_updates_installation_device_arm_status(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);
        $event = $this->makeEvent(['cid_code' => 3401]);
        (new ProcessAlarmEventJob($event))->handle();

        $this->installationDevice->refresh();
        $this->assertEquals('armed_away', $this->installationDevice->getArmStatus());
    }

    public function test_disarm_event_updates_installation_device_arm_status(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);
        $this->installationDevice->setProperty('arm_status', 'armed_away');
        $event = $this->makeEvent(['cid_code' => 1401]);
        (new ProcessAlarmEventJob($event))->handle();

        $this->installationDevice->refresh();
        $this->assertEquals('disarmed', $this->installationDevice->getArmStatus());
    }

    public function test_heartbeat_updates_connection_status(): void
    {
        Queue::fake([CreateAlertFromAlarmEventJob::class]);
        $event = $this->makeEvent(['event_type' => 'heartbeat']);
        (new ProcessAlarmEventJob($event))->handle();

        $this->installationDevice->refresh();
        $this->assertEquals('online', $this->installationDevice->getConnectionStatus());
        $this->assertNotNull($this->installationDevice->getProperty('last_heartbeat_at'));
    }

    public function test_create_alert_job_creates_alert_from_event(): void
    {
        // Créer l'event AVANT tout fake — bootHasUuid et les model events Eloquent
        // ne doivent pas être interceptés
        $event = $this->makeEvent([
            'cid_code' => 1759, 'standard_cid_code' => 3130,
            'category' => 'intrusion', 'severity' => 'critical',
            'zone_number' => 3, 'processed' => true, 'processed_at' => now(),
        ]);

        // Utiliser Queue::fake pour éviter que les jobs secondaires partent vraiment
        // Ne PAS utiliser Event::fake() car il intercepte les model events Eloquent
        // (eloquent.updating, etc.) et bloque les UPDATE SQL
        Queue::fake();

        (new CreateAlertFromAlarmEventJob($event))->handle();

        $this->assertDatabaseHas('alerts', [
            'client_uuid' => $this->client->uuid,
            'type'        => 'intrusion',
            'severity'    => 'critical',
            'resolved'    => false,
        ]);

        // Vérifier que alert_uuid est bien persisté en DB (refresh via uuid)
        $fresh = \App\Models\AlarmEvent::where('uuid', $event->uuid)->first();
        $this->assertNotNull($fresh->alert_uuid);
    }
}

