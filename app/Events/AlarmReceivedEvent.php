<?php

namespace App\Events;

use App\Models\AlarmEvent;
use App\Models\Alert;
use App\Models\Device;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasté quand une alerte critique est créée depuis un événement alarme.
 */
class AlarmReceivedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Alert $alert,
        public Device $device,
        public AlarmEvent $alarmEvent
    ) {}

    /**
     * Canal privé scopé par installation.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('installation.' . $this->alarmEvent->installation_uuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alarm.received';
    }

    public function broadcastWith(): array
    {
        return [
            'alert' => [
                'uuid' => $this->alert->uuid,
                'type' => $this->alert->type,
                'severity' => $this->alert->severity,
                'description' => $this->alert->description,
                'triggered_at' => $this->alert->triggered_at?->toIso8601String(),
            ],
            'device' => [
                'uuid' => $this->device->uuid,
                'brand' => $this->device->brand,
                'model' => $this->device->model,
                'serial_number' => $this->device->getPanelSerialNumber(),
            ],
            'event' => [
                'uuid' => $this->alarmEvent->uuid,
                'category' => $this->alarmEvent->category,
                'severity' => $this->alarmEvent->severity,
                'zone_number' => $this->alarmEvent->zone_number,
                'zone_name' => $this->alarmEvent->zone_name,
                'cid_code' => $this->alarmEvent->cid_code,
            ],
        ];
    }
}

