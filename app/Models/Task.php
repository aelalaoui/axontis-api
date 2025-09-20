<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'taskable_type',
        'taskable_id',
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
        return $this->morphTo();
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
        return $this->belongsToMany(Device::class, 'task_devices')
                    ->using(TaskDevice::class)
                    ->withPivot([
                        'id',
                        'ht_price',
                        'tva_price', 
                        'ttc_price',
                        'serial_number',
                        'inventory_number',
                        'status',
                        'assigned_date',
                        'installation_date',
                        'return_date',
                        'notes'
                    ])
                    ->withTimestamps();
    }

    public function taskDevices(): HasMany
    {
        return $this->hasMany(TaskDevice::class);
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