<?php

namespace App\Models;

use App\Enums\AlarmEventCategory;
use App\Enums\AlarmEventSeverity;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlarmEvent extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'alarm_events';

    protected $fillable = [
        'uuid',
        'installation_device_uuid',
        'installation_uuid',
        'cid_code',
        'standard_cid_code',
        'event_type',
        'category',
        'severity',
        'zone_number',
        'zone_name',
        'triggered_at',
        'source_ip',
        'raw_payload',
        'processed',
        'processed_at',
        'alert_uuid',
    ];

    protected $casts = [
        'cid_code' => 'integer',
        'standard_cid_code' => 'integer',
        'zone_number' => 'integer',
        'triggered_at' => 'datetime',
        'processed_at' => 'datetime',
        'raw_payload' => 'array',
        'processed' => 'boolean',
    ];

    protected $attributes = [
        'processed' => false,
    ];

    // ─── Relations ───────────────────────────────────────────

    /**
     * Unité installée (centrale d'alarme physique) ayant émis cet événement.
     */
    public function installationDevice(): BelongsTo
    {
        return $this->belongsTo(InstallationDevice::class, 'installation_device_uuid', 'uuid');
    }


    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class, 'installation_uuid', 'uuid');
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'alert_uuid', 'uuid');
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', AlarmEventSeverity::CRITICAL->value);
    }

    public function scopeUnprocessed(Builder $query): Builder
    {
        return $query->where('processed', false);
    }

    public function scopeForInstallation(Builder $query, string $uuid): Builder
    {
        return $query->where('installation_uuid', $uuid);
    }

    public function scopeForDevice(Builder $query, string $uuid): Builder
    {
        return $query->where('installation_device_uuid', $uuid);
    }

    public function scopeInPeriod(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('triggered_at', [$from, $to]);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));
    }

    // ─── Accessors ───────────────────────────────────────────

    public function getCategoryLabelAttribute(): ?string
    {
        if (!$this->category) {
            return null;
        }

        return AlarmEventCategory::tryFrom($this->category)?->label();
    }

    public function getSeverityLabelAttribute(): ?string
    {
        if (!$this->severity) {
            return null;
        }

        return AlarmEventSeverity::tryFrom($this->severity)?->label();
    }

    public function getIsCriticalAttribute(): bool
    {
        return $this->severity === AlarmEventSeverity::CRITICAL->value;
    }

    public function getHasAlertAttribute(): bool
    {
        return $this->alert_uuid !== null;
    }
}

