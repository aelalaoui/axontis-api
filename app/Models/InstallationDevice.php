<?php

namespace App\Models;

use App\Enums\DeviceCategory;
use App\Traits\HasProperties;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallationDevice extends Model
{
    use HasFactory, HasUuid, HasProperties;

    protected $table = 'installation_devices';

    // HasUuid : $primaryKey = 'uuid', $incrementing = false, $keyType = 'string'

    protected $fillable = [
        'task_uuid',
        'device_uuid',
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

    // ─── Relationships ────────────────────────────────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_uuid', 'uuid');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_uuid', 'uuid');
    }

    public function alarmEvents(): HasMany
    {
        return $this->hasMany(AlarmEvent::class, 'installation_device_uuid', 'uuid');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAssigned($query) { return $query->where('status', 'assigned'); }
    public function scopeInstalled($query) { return $query->where('status', 'installed'); }
    public function scopeReturned($query) { return $query->where('status', 'returned'); }
    public function scopeMaintenance($query) { return $query->where('status', 'maintenance'); }
    public function scopeReplaced($query) { return $query->where('status', 'replaced'); }

    public function scopeAlarmPanels(Builder $query): Builder
    {
        return $query->whereHas('device', fn (Builder $q) =>
            $q->where('category', DeviceCategory::ALARM_PANEL->value)
        );
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getIsInstalledAttribute(): bool { return $this->status === 'installed'; }
    public function getIsReturnedAttribute(): bool { return $this->status === 'returned'; }

    /**
     * Remonte l'uuid de l'Installation parente via Task (relation polymorphique).
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
        if (!$this->relationLoaded('device')) {
            $this->load('device');
        }
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

    public function getPanelSerialNumber(): ?string
    {
        return $this->serial_number ?? $this->getProperty('panel_serial_number');
    }

    public function getHppDeviceId(): ?string { return $this->getProperty('hpp_device_id'); }
    public function getWebhookSecret(): ?string { return $this->getProperty('webhook_secret'); }

    public function getWebhookIpWhitelist(): array
    {
        $value = $this->getProperty('webhook_ip_whitelist');
        if (is_array($value)) return $value;
        if (is_string($value) && str_contains($value, ',')) {
            return array_map('trim', explode(',', $value));
        }
        return $value ? [$value] : [];
    }

    // ─── Methods ──────────────────────────────────────────────────────────────

    public function markAsInstalled(): bool { $this->status = 'installed'; return $this->save(); }
    public function markAsMaintenance(): bool { $this->status = 'maintenance'; return $this->save(); }

    public function markAsReturned(): bool
    {
        $this->status = 'returned';
        if ($this->device) $this->device->addStock(1);
        return $this->save();
    }
}

