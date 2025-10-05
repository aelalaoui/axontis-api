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
        'order_uuid',
        'device_uuid',
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
        return $this->belongsTo(Order::class, 'order_uuid', 'uuid');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_uuid', 'uuid');
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
        // Log détaillé pour debug
        \Log::info('OrderDevice::receiveQuantity called', [
            'order_device_id' => $this->id,
            'order_uuid' => $this->order_uuid,
            'device_uuid' => $this->device_uuid,
            'current_qty_received' => $this->qty_received,
            'qty_ordered' => $this->qty_ordered,
            'quantity_to_receive' => $quantity,
            'before_update' => $this->toArray()
        ]);

        if ($this->qty_received + $quantity <= $this->qty_ordered) {
            $oldQtyReceived = $this->qty_received;
            $this->qty_received += $quantity;

            // Update status based on received quantity
            if ($this->qty_received >= $this->qty_ordered) {
                $this->status = 'received';
            } else {
                $this->status = 'partially_received';
            }

            $result = $this->save();

            // Log après la mise à jour
            \Log::info('OrderDevice updated successfully', [
                'order_device_id' => $this->id,
                'old_qty_received' => $oldQtyReceived,
                'new_qty_received' => $this->qty_received,
                'new_status' => $this->status,
                'after_update' => $this->fresh()->toArray()
            ]);

            return $result;
        }

        \Log::warning('OrderDevice::receiveQuantity failed - quantity exceeds ordered', [
            'order_device_id' => $this->id,
            'qty_received' => $this->qty_received,
            'qty_ordered' => $this->qty_ordered,
            'quantity_to_receive' => $quantity
        ]);

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
