<?php

namespace App\Models;

use App\Enums\DeviceCategory;
use App\Traits\HasProperties;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InstallationDevice extends Pivot
{
    use HasFactory, HasUuid, HasProperties;

    protected $table = 'installation_devices';

    protected $fillable = [
        'task_id',
        'device_id',
        'serial_number',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected $attributes = [
        'status' => 'assigned',
    ];

    // Relationships
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function alarmEvents(): HasMany
    {
        return $this->hasMany(AlarmEvent::class, 'installation_device_uuid', 'uuid');
    }

    // Scopes
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInstalled($query)
    {
        return $query->where('status', 'installed');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    public function scopeReplaced($query)
    {
        return $query->where('status', 'replaced');
    }

    /**
     * Filtre sur les centrales d'alarme (Device de catégorie alarm_panel).
     */
    public function scopeAlarmPanels(Builder $query): Builder
    {
        return $query->whereHas('device', fn (Builder $q) =>
            $q->where('category', DeviceCategory::ALARM_PANEL->value)
        );
    }

    // Accessors
    public function getIsInstalledAttribute(): bool
    {
        return $this->status === 'installed';
    }

    public function getIsReturnedAttribute(): bool
    {
        return $this->status === 'returned';
    }

    /**
     * Accessor — remonte l'uuid de l'Installation parente via Task (polymorphique).
     * Charge automatiquement la relation si nécessaire.
     */
    public function getInstallationUuidAttribute(): ?string
    {
        if (!$this->relationLoaded('task')) {
            $this->load('task.taskable');
        }

        return ($this->task?->taskable instanceof Installation)
            ? $this->task->taskable->uuid
            : null;
    }

    // ─── Alarm helpers ────────────────────────────────────────────────────────

    public function isAlarmPanel(): bool
    {
        return $this->device?->category === DeviceCategory::ALARM_PANEL->value;
    }

    public function getArmStatus(): string
    {
        return $this->getProperty('arm_status', 'unknown');
    }

    public function getConnectionStatus(): string
    {
        return $this->getProperty('connection_status', 'unknown');
    }

    /**
     * Retourne le numéro de série de la centrale :
     * priorité au champ natif `serial_number`, fallback sur la property EAV.
     */
    public function getPanelSerialNumber(): ?string
    {
        return $this->serial_number ?? $this->getProperty('panel_serial_number');
    }

    public function getHppDeviceId(): ?string
    {
        return $this->getProperty('hpp_device_id');
    }

    public function getWebhookSecret(): ?string
    {
        return $this->getProperty('webhook_secret');
    }

    public function getWebhookIpWhitelist(): array
    {
        $value = $this->getProperty('webhook_ip_whitelist');

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && str_contains($value, ',')) {
            return array_map('trim', explode(',', $value));
        }

        return $value ? [$value] : [];
    }

    // Methods
    public function markAsInstalled(): bool
    {
        $this->status = 'installed';
        return $this->save();
    }

    public function markAsReturned(): bool
    {
        $this->status = 'returned';
        if ($this->device) {
            $this->device->addStock(1);
        }
        return $this->save();
    }

    public function markAsMaintenance(): bool
    {
        $this->status = 'maintenance';
        return $this->save();
    }
}

