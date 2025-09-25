<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model',
        'stock_qty',
        'category',
        'description',
        'min_stock_level',
    ];

    protected $casts = [
        'stock_qty' => 'integer',
        'min_stock_level' => 'integer',
    ];

    protected $attributes = [
        'stock_qty' => 0,
        'min_stock_level' => 0,
    ];

    // Relationships
    public function arrivals(): HasMany
    {
        return $this->hasMany(Arrival::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_device')
                    ->withPivot([
                        'id',
                        'supplier_id',
                        'ht_price',
                        'tva_rate',
                        'tva_price',
                        'ttc_price',
                        'qty_ordered',
                        'qty_received',
                        'status',
                        'expected_delivery_date',
                        'notes'
                    ])
                    ->withTimestamps();
    }

    public function orderDevices(): HasMany
    {
        return $this->hasMany(OrderDevice::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_devices')
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
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_qty', '<=', 'min_stock_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_qty', 0);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_qty <= $this->min_stock_level;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_qty === 0;
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->brand} - {$this->model}";
    }

    public function getTotalOrderedQuantityAttribute(): int
    {
        return $this->orderDevices()
                    ->whereHas('order', function ($query) {
                        $query->whereIn('status', ['approved', 'ordered', 'partially_received']);
                    })
                    ->sum('qty_ordered');
    }

    public function getPendingOrderQuantityAttribute(): int
    {
        return $this->orderDevices()
                    ->whereHas('order', function ($query) {
                        $query->whereIn('status', ['approved', 'ordered', 'partially_received']);
                    })
                    ->get()
                    ->sum('qty_pending');
    }

    // Methods
    public function addStock(int $quantity): bool
    {
        $this->stock_qty += $quantity;
        return $this->save();
    }

    public function removeStock(int $quantity): bool
    {
        if ($this->stock_qty >= $quantity) {
            $this->stock_qty -= $quantity;
            return $this->save();
        }
        return false;
    }

    public function needsReorder(): bool
    {
        $availableStock = $this->stock_qty + $this->pending_order_quantity;
        return $availableStock <= $this->min_stock_level;
    }
}