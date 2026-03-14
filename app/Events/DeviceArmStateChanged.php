<?php

namespace App\Events;

use App\Models\InstallationDevice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasté quand le statut d'armement d'une centrale change.
 */
class DeviceArmStateChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public InstallationDevice $installationDevice,
        public string $newStatus,
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
        return 'device.arm-state-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'device_uuid' => $this->installationDevice->uuid,   // uuid de l'unité installée
            'arm_status' => $this->newStatus,
            'brand' => $this->installationDevice->device?->brand,
            'model' => $this->installationDevice->device?->model,
        ];
    }
}
