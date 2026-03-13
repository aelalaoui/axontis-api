<?php

namespace App\Models;

use App\Traits\HasProperties;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    // Accessors
    public function getIsInstalledAttribute(): bool
    {
        return $this->status === 'installed';
    }

    public function getIsReturnedAttribute(): bool
    {
        return $this->status === 'returned';
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

