<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDevice extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'order_device';

    protected $fillable = [
        'order_id',
        'device_id',
        'supplier_id',
        'ht_price',
        'tva_rate',
        'tva_price',
        'ttc_price',
        'qty_ordered',
        'qty_received',
        'status',
        'expected_delivery_date',
        'notes',
    ];

    protected $casts = [
        'ht_price' => 'decimal:2',
        'tva_rate' => 'decimal:2',
        'tva_price' => 'decimal:2',
        'ttc_price' => 'decimal:2',
        'qty_ordered' => 'integer',
        'qty_received' => 'integer',
        'expected_delivery_date' => 'date',
    ];

    protected $attributes = [
        'status' => 'pending',
        'qty_received' => 0,
        'tva_rate' => 20.00,
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'uuid');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'uuid');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'uuid');
    }

    // Accessors
    public function getQtyPendingAttribute(): int
    {
        return $this->qty_ordered - $this->qty_received;
    }

    public function getTotalHtAttribute(): float
    {
        return $this->ht_price * $this->qty_ordered;
    }

    public function getTotalTvaAttribute(): float
    {
        return $this->tva_price * $this->qty_ordered;
    }

    public function getTotalTtcAttribute(): float
    {
        return $this->ttc_price * $this->qty_ordered;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->qty_received >= $this->qty_ordered;
    }

    // Methods
    public function calculatePrices(): void
    {
        $this->tva_price = ($this->ht_price * $this->tva_rate) / 100;
        $this->ttc_price = $this->ht_price + $this->tva_price;
        $this->save();
    }

    public function receiveQuantity(int $quantity): bool
    {
        if ($this->qty_received + $quantity <= $this->qty_ordered) {
            $this->qty_received += $quantity;
            
            // Update status based on received quantity
            if ($this->qty_received >= $this->qty_ordered) {
                $this->status = 'received';
            } else {
                $this->status = 'partially_received';
            }
            
            return $this->save();
        }
        
        return false;
    }

    public function markAsOrdered(): bool
    {
        $this->status = 'ordered';
        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }
}