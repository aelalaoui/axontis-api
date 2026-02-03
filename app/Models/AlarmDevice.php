<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * Modèle AlarmDevice - Centrale d'alarme Hikvision AX PRO
 *
 * Représente une centrale d'alarme physique installée chez un client.
 * Gère les credentials chiffrés et le statut de connexion.
 *
 * @property string $uuid
 * @property string|null $installation_uuid
 * @property string $name
 * @property string $serial_number
 * @property string $model
 * @property string|null $ip_address
 * @property string|null $mac_address
 * @property int $port
 * @property string|null $api_username
 * @property string|null $api_password_encrypted
 * @property string $status
 * @property string $arm_status
 * @property \Carbon\Carbon|null $last_heartbeat_at
 * @property \Carbon\Carbon|null $last_event_at
 * @property string|null $firmware_version
 * @property int $zone_count
 * @property array|null $configuration
 * @property bool $webhook_enabled
 * @property string|null $webhook_secret
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Installation|null $installation
 * @property-read \Illuminate\Database\Eloquent\Collection|AlarmEvent[] $events
 * @property-read Client|null $client
 * @property-read Contract|null $contract
 */
class AlarmDevice extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'alarm_devices';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'installation_uuid',
        'name',
        'serial_number',
        'model',
        'ip_address',
        'mac_address',
        'port',
        'api_username',
        'api_password_encrypted',
        'status',
        'arm_status',
        'last_heartbeat_at',
        'last_event_at',
        'firmware_version',
        'zone_count',
        'configuration',
        'webhook_enabled',
        'webhook_secret',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'port' => 'integer',
        'zone_count' => 'integer',
        'configuration' => 'array',
        'webhook_enabled' => 'boolean',
        'last_heartbeat_at' => 'datetime',
        'last_event_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'id',
        'api_password_encrypted',
        'webhook_secret',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'model' => 'DS-PWA64-L-WB',
        'port' => 80,
        'status' => 'unknown',
        'arm_status' => 'unknown',
        'zone_count' => 32,
        'webhook_enabled' => true,
    ];

    /**
     * Status constants
     */
    public const STATUS_ONLINE = 'online';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_ERROR = 'error';
    public const STATUS_CONFIGURING = 'configuring';
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Arm status constants
     */
    public const ARM_AWAY = 'armed_away';
    public const ARM_STAY = 'armed_stay';
    public const DISARMED = 'disarmed';
    public const ARM_UNKNOWN = 'unknown';

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the installation where this device is located.
     */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class, 'installation_uuid', 'uuid');
    }

    /**
     * Get all alarm events for this device.
     */
    public function events(): HasMany
    {
        return $this->hasMany(AlarmEvent::class, 'alarm_device_uuid', 'uuid');
    }

    /**
     * Get the client through the installation.
     */
    public function client(): ?Client
    {
        return $this->installation?->client;
    }

    /**
     * Get the contract through the installation.
     */
    public function contract(): ?Contract
    {
        return $this->installation?->contract;
    }

    // =========================================================================
    // ACCESSORS & MUTATORS
    // =========================================================================

    /**
     * Get the decrypted API password.
     */
    public function getApiPasswordAttribute(): ?string
    {
        if (empty($this->api_password_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_password_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set the encrypted API password.
     */
    public function setApiPasswordAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['api_password_encrypted'] = null;
            return;
        }

        $this->attributes['api_password_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * Get the full API URL for this device.
     */
    public function getApiUrlAttribute(): ?string
    {
        if (empty($this->ip_address)) {
            return null;
        }

        $protocol = $this->port === 443 ? 'https' : 'http';
        return "{$protocol}://{$this->ip_address}:{$this->port}";
    }

    /**
     * Check if the device is online.
     */
    public function getIsOnlineAttribute(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    /**
     * Check if the device is armed.
     */
    public function getIsArmedAttribute(): bool
    {
        return in_array($this->arm_status, [self::ARM_AWAY, self::ARM_STAY]);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope: Online devices only.
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * Scope: Offline devices only.
     */
    public function scopeOffline($query)
    {
        return $query->where('status', self::STATUS_OFFLINE);
    }

    /**
     * Scope: Devices with errors.
     */
    public function scopeWithErrors($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    /**
     * Scope: Armed devices.
     */
    public function scopeArmed($query)
    {
        return $query->whereIn('arm_status', [self::ARM_AWAY, self::ARM_STAY]);
    }

    /**
     * Scope: Disarmed devices.
     */
    public function scopeDisarmed($query)
    {
        return $query->where('arm_status', self::DISARMED);
    }

    /**
     * Scope: Devices with webhook enabled.
     */
    public function scopeWebhookEnabled($query)
    {
        return $query->where('webhook_enabled', true);
    }

    /**
     * Scope: Devices that haven't sent a heartbeat recently.
     */
    public function scopeStale($query, ?int $thresholdSeconds = null)
    {
        $threshold = $thresholdSeconds ?? config('hikvision.heartbeat.offline_threshold', 600);
        return $query->where(function ($q) use ($threshold) {
            $q->where('last_heartbeat_at', '<', now()->subSeconds($threshold))
                ->orWhereNull('last_heartbeat_at');
        });
    }

    /**
     * Scope: Filter by installation.
     */
    public function scopeForInstallation($query, string $installationUuid)
    {
        return $query->where('installation_uuid', $installationUuid);
    }

    /**
     * Scope: Search by IP or MAC.
     */
    public function scopeByNetwork($query, string $identifier)
    {
        return $query->where(function ($q) use ($identifier) {
            $q->where('ip_address', $identifier)
                ->orWhere('mac_address', strtoupper($identifier));
        });
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Find a device by MAC address.
     */
    public static function findByMac(string $macAddress): ?self
    {
        return static::where('mac_address', strtoupper($macAddress))->first();
    }

    /**
     * Find a device by IP address.
     */
    public static function findByIp(string $ipAddress): ?self
    {
        return static::where('ip_address', $ipAddress)->first();
    }

    /**
     * Find a device by serial number.
     */
    public static function findBySerial(string $serialNumber): ?self
    {
        return static::where('serial_number', $serialNumber)->first();
    }

    /**
     * Update the device status.
     */
    public function updateStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }

    /**
     * Record a heartbeat.
     */
    public function recordHeartbeat(): bool
    {
        return $this->update([
            'status' => self::STATUS_ONLINE,
            'last_heartbeat_at' => now(),
        ]);
    }

    /**
     * Record an event received.
     */
    public function recordEventReceived(): bool
    {
        return $this->update([
            'last_event_at' => now(),
            'last_heartbeat_at' => now(),
            'status' => self::STATUS_ONLINE,
        ]);
    }

    /**
     * Update the arm status.
     */
    public function updateArmStatus(string $armStatus): bool
    {
        if (!in_array($armStatus, [self::ARM_AWAY, self::ARM_STAY, self::DISARMED, self::ARM_UNKNOWN])) {
            return false;
        }

        return $this->update(['arm_status' => $armStatus]);
    }

    /**
     * Check if this device is properly configured for API calls.
     */
    public function isConfiguredForApi(): bool
    {
        return !empty($this->ip_address)
            && !empty($this->api_username)
            && !empty($this->api_password);
    }

    /**
     * Get the recent events count.
     */
    public function getRecentEventsCount(int $minutes = 60): int
    {
        return $this->events()
            ->where('triggered_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Get the recent alerts count.
     */
    public function getRecentAlertsCount(int $minutes = 60): int
    {
        return $this->events()
            ->whereNotNull('alert_uuid')
            ->where('triggered_at', '>=', now()->subMinutes($minutes))
            ->count();
    }
}
