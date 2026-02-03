<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle AlarmEvent - Événement d'alarme Hikvision
 *
 * Représente un événement brut reçu d'une centrale d'alarme.
 * Stocke le payload complet et les informations de traitement.
 *
 * @property string $uuid
 * @property string|null $alarm_device_uuid
 * @property string|null $alert_uuid
 * @property string|null $source_ip
 * @property string|null $source_mac
 * @property string $event_type
 * @property int|null $cid_code
 * @property int|null $standard_cid_code
 * @property int|null $zone_number
 * @property int|null $channel_id
 * @property string $event_state
 * @property string|null $event_description
 * @property string|null $alarm_type
 * @property string|null $severity
 * @property array $raw_payload
 * @property bool $processed
 * @property \Carbon\Carbon|null $processed_at
 * @property string|null $processing_error
 * @property string|null $event_hash
 * @property \Carbon\Carbon $triggered_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read AlarmDevice|null $alarmDevice
 * @property-read Alert|null $alert
 */
class AlarmEvent extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'alarm_events';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'alarm_device_uuid',
        'alert_uuid',
        'source_ip',
        'source_mac',
        'event_type',
        'cid_code',
        'standard_cid_code',
        'zone_number',
        'channel_id',
        'event_state',
        'event_description',
        'alarm_type',
        'severity',
        'raw_payload',
        'processed',
        'processed_at',
        'processing_error',
        'event_hash',
        'triggered_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'cid_code' => 'integer',
        'standard_cid_code' => 'integer',
        'zone_number' => 'integer',
        'channel_id' => 'integer',
        'raw_payload' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'triggered_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'id',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'event_state' => 'active',
        'processed' => false,
    ];

    /**
     * Event type constants
     */
    public const TYPE_CID_EVENT = 'cidEvent';
    public const TYPE_SYSTEM_EVENT = 'systemEvent';
    public const TYPE_HEARTBEAT = 'heartbeat';
    public const TYPE_ARM_DISARM = 'armDisarm';
    public const TYPE_ZONE_STATUS = 'zoneStatus';

    /**
     * Event state constants
     */
    public const STATE_ACTIVE = 'active';
    public const STATE_INACTIVE = 'inactive';
    public const STATE_RESTORE = 'restore';

    /**
     * Alarm type constants (mapped from CID codes)
     */
    public const ALARM_INTRUSION = 'intrusion';
    public const ALARM_FIRE = 'fire';
    public const ALARM_FLOOD = 'flood';
    public const ALARM_OTHER = 'other';
    public const ALARM_SYSTEM = 'system';

    /**
     * Severity constants
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_CRITICAL = 'critical';

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the alarm device that triggered this event.
     */
    public function alarmDevice(): BelongsTo
    {
        return $this->belongsTo(AlarmDevice::class, 'alarm_device_uuid', 'uuid');
    }

    /**
     * Get the alert created from this event (if any).
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'alert_uuid', 'uuid');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Check if this event is critical.
     */
    public function getIsCriticalAttribute(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Check if this event created an alert.
     */
    public function getHasAlertAttribute(): bool
    {
        return !empty($this->alert_uuid);
    }

    /**
     * Check if this is an intrusion event.
     */
    public function getIsIntrusionAttribute(): bool
    {
        return $this->alarm_type === self::ALARM_INTRUSION;
    }

    /**
     * Check if this is a fire event.
     */
    public function getIsFireAttribute(): bool
    {
        return $this->alarm_type === self::ALARM_FIRE;
    }

    /**
     * Check if this is a system event (no alert needed).
     */
    public function getIsSystemEventAttribute(): bool
    {
        return $this->alarm_type === self::ALARM_SYSTEM;
    }

    /**
     * Get a human-readable description of the event.
     */
    public function getReadableDescriptionAttribute(): string
    {
        $parts = [];

        if ($this->event_description) {
            $parts[] = $this->event_description;
        }

        if ($this->zone_number) {
            $parts[] = "Zone {$this->zone_number}";
        }

        if ($this->cid_code) {
            $parts[] = "CID: {$this->cid_code}";
        }

        return implode(' - ', $parts) ?: 'Unknown event';
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope: Unprocessed events.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Scope: Processed events.
     */
    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    /**
     * Scope: Events with processing errors.
     */
    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('processing_error');
    }

    /**
     * Scope: Events that created alerts.
     */
    public function scopeWithAlerts($query)
    {
        return $query->whereNotNull('alert_uuid');
    }

    /**
     * Scope: Events by alarm type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alarm_type', $type);
    }

    /**
     * Scope: Critical events.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope: Events by device.
     */
    public function scopeForDevice($query, string $deviceUuid)
    {
        return $query->where('alarm_device_uuid', $deviceUuid);
    }

    /**
     * Scope: Events in time range.
     */
    public function scopeTriggeredBetween($query, $start, $end)
    {
        return $query->whereBetween('triggered_at', [$start, $end]);
    }

    /**
     * Scope: Events triggered after a date.
     */
    public function scopeTriggeredAfter($query, $date)
    {
        return $query->where('triggered_at', '>=', $date);
    }

    /**
     * Scope: Recent events.
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('triggered_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope: Events by CID code.
     */
    public function scopeByCidCode($query, int $code)
    {
        return $query->where(function ($q) use ($code) {
            $q->where('cid_code', $code)
                ->orWhere('standard_cid_code', $code);
        });
    }

    /**
     * Scope: Events from a specific zone.
     */
    public function scopeFromZone($query, int $zoneNumber)
    {
        return $query->where('zone_number', $zoneNumber);
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Create an event from a Hikvision webhook payload.
     */
    public static function createFromWebhook(array $payload, ?AlarmDevice $device = null): self
    {
        $cidEvent = $payload['CIDEvent'] ?? [];

        return static::create([
            'alarm_device_uuid' => $device?->uuid,
            'source_ip' => $payload['ipAddress'] ?? null,
            'source_mac' => $payload['macAddress'] ?? null,
            'event_type' => $payload['eventType'] ?? self::TYPE_CID_EVENT,
            'cid_code' => $cidEvent['code'] ?? null,
            'standard_cid_code' => $cidEvent['standardCIDcode'] ?? null,
            'zone_number' => $cidEvent['zone'] ?? null,
            'channel_id' => $payload['channelID'] ?? null,
            'event_state' => $payload['eventState'] ?? self::STATE_ACTIVE,
            'event_description' => $payload['eventDescription'] ?? $cidEvent['type'] ?? null,
            'raw_payload' => $payload,
            'triggered_at' => self::parseDateTime($cidEvent['trigger'] ?? $payload['dateTime'] ?? null),
            'event_hash' => self::generateHash($payload, $device),
        ]);
    }

    /**
     * Generate a hash for deduplication.
     */
    public static function generateHash(array $payload, ?AlarmDevice $device = null): string
    {
        $cidEvent = $payload['CIDEvent'] ?? [];

        $hashData = [
            'device' => $device?->uuid ?? $payload['macAddress'] ?? $payload['ipAddress'] ?? 'unknown',
            'code' => $cidEvent['code'] ?? $cidEvent['standardCIDcode'] ?? null,
            'zone' => $cidEvent['zone'] ?? null,
            'trigger' => $cidEvent['trigger'] ?? $payload['dateTime'] ?? null,
        ];

        return hash('sha256', json_encode($hashData));
    }

    /**
     * Parse a date/time string from Hikvision format.
     */
    protected static function parseDateTime(?string $dateTime): ?\Carbon\Carbon
    {
        if (empty($dateTime)) {
            return now();
        }

        try {
            return \Carbon\Carbon::parse($dateTime);
        } catch (\Exception $e) {
            return now();
        }
    }

    /**
     * Mark the event as processed.
     */
    public function markAsProcessed(?string $error = null): bool
    {
        $data = [
            'processed' => true,
            'processed_at' => now(),
        ];

        if ($error) {
            $data['processing_error'] = $error;
        }

        return $this->update($data);
    }

    /**
     * Link an alert to this event.
     */
    public function linkAlert(Alert $alert): bool
    {
        return $this->update(['alert_uuid' => $alert->uuid]);
    }

    /**
     * Set the classification from processing.
     */
    public function setClassification(?string $type, ?string $severity, ?string $description = null): bool
    {
        return $this->update([
            'alarm_type' => $type,
            'severity' => $severity,
            'event_description' => $description ?? $this->event_description,
        ]);
    }

    /**
     * Check if a duplicate event exists.
     */
    public function isDuplicate(): bool
    {
        if (empty($this->event_hash)) {
            return false;
        }

        $window = config('hikvision.events.deduplicate_window', 60);

        return static::where('event_hash', $this->event_hash)
            ->where('uuid', '!=', $this->uuid)
            ->where('triggered_at', '>=', now()->subSeconds($window))
            ->exists();
    }

    /**
     * Get similar recent events.
     */
    public function getSimilarRecentEvents(int $seconds = 60): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('alarm_device_uuid', $this->alarm_device_uuid)
            ->where('cid_code', $this->cid_code)
            ->where('uuid', '!=', $this->uuid)
            ->where('triggered_at', '>=', now()->subSeconds($seconds))
            ->get();
    }
}
