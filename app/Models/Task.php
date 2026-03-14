<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'taskable_type',
        'taskable_uuid',
        'address',
        'status',
        'type',
        'scheduled_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'status' => 'string',
        'type' => 'string',
    ];

    protected $attributes = [
        'status' => 'scheduled',
    ];

    // Relationships
    public function taskable(): MorphTo
    {
        // taskable_uuid stocke l'uuid du modèle lié (ex: installations.uuid)
        return $this->morphTo('taskable', 'taskable_type', 'taskable_uuid', 'uuid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claims(): MorphMany
    {
        return $this->morphMany(Claim::class, 'claimable');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function communications(): MorphMany
    {
        return $this->morphMany(Communication::class, 'communicable');
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'installation_devices', 'task_uuid', 'device_uuid', 'uuid', 'uuid')
                    ->withPivot(['uuid', 'serial_number', 'status', 'notes'])
                    ->withTimestamps();
    }

    public function installationDevices(): HasMany
    {
        return $this->hasMany(InstallationDevice::class, 'task_uuid', 'uuid');
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInstallation($query)
    {
        return $query->where('type', 'installation');
    }

    public function scopeSav($query)
    {
        return $query->where('type', 'sav');
    }

    public function scopeProspection($query)
    {
        return $query->where('type', 'prospection');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', today());
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', today())
                    ->whereIn('status', ['scheduled', 'in_progress']);
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->scheduled_date &&
               $this->scheduled_date->isPast() &&
               in_array($this->status, ['scheduled', 'in_progress']);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }
}
