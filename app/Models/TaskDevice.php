<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskDevice extends Pivot
{
    use HasFactory, HasUuid;

    protected $table = 'task_devices';

    protected $fillable = [
        'task_id',
        'device_id',
        'ht_price',
        'tva_price',
        'ttc_price',
        'serial_number',
        'inventory_number',
        'status',
        'assigned_date',
        'installation_date',
        'return_date',
        'notes',
    ];

    protected $casts = [
        'ht_price' => 'decimal:2',
        'tva_price' => 'decimal:2',
        'ttc_price' => 'decimal:2',
        'assigned_date' => 'date',
        'installation_date' => 'date',
        'return_date' => 'date',
        'status' => 'string',
    ];

    protected $attributes = [
        'status' => 'assigned',
        'assigned_date' => null,
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($taskDevice) {
            if (!$taskDevice->assigned_date) {
                $taskDevice->assigned_date = now();
            }
        });
    }

    // Relationships
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'task_device_id');
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

    public function scopeByInventoryNumber($query, $inventoryNumber)
    {
        return $query->where('inventory_number', $inventoryNumber);
    }

    public function scopeBySerialNumber($query, $serialNumber)
    {
        return $query->where('serial_number', $serialNumber);
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

    public function getIsInMaintenanceAttribute(): bool
    {
        return $this->status === 'maintenance';
    }

    public function getDaysAssignedAttribute(): int
    {
        $endDate = $this->return_date ?? now();
        return $this->assigned_date->diffInDays($endDate);
    }

    // Methods
    public function markAsInstalled(): bool
    {
        $this->status = 'installed';
        $this->installation_date = now();
        return $this->save();
    }

    public function markAsReturned(): bool
    {
        $this->status = 'returned';
        $this->return_date = now();
        
        // Return stock to device
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

    public function markAsReplaced(): bool
    {
        $this->status = 'replaced';
        return $this->save();
    }
}