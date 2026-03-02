<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasté quand un device passe online ou offline.
 */
class DeviceConnectionChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Device $device,
        public string $status,
        public string $installationUuid
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('installation.' . $this->installationUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.connection-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'device_uuid' => $this->device->uuid,
            'connection_status' => $this->status,
            'brand' => $this->device->brand,
            'model' => $this->device->model,
        ];
    }
}

