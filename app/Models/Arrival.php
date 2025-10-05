<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Arrival extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'device_id',
        'order_id',
        'ht_price',
        'tva_price',
        'ttc_price',
        'qty',
        'order_number',
        'supplier',
        'arrival_date',
        'invoice_number',
        'notes',
        'status',
    ];

    protected $casts = [
        'ht_price' => 'decimal:2',
        'tva_price' => 'decimal:2',
        'ttc_price' => 'decimal:2',
        'qty' => 'integer',
        'arrival_date' => 'date',
        'status' => 'string',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    // Relationships
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'uuid');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'uuid');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeStocked($query)
    {
        return $query->where('status', 'stocked');
    }

    public function scopeBySupplier($query, $supplier)
    {
        return $query->where('supplier', $supplier);
    }

    public function scopeByOrderNumber($query, $orderNumber)
    {
        return $query->where('order_number', $orderNumber);
    }

    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    // Accessors
    public function getTotalValueAttribute(): float
    {
        return $this->ttc_price * $this->qty;
    }

    public function getUnitTtcPriceAttribute(): float
    {
        return $this->ttc_price;
    }

    // Methods
    public function markAsReceived(): bool
    {
        $this->status = 'received';
        return $this->save();
    }

    public function markAsVerified(): bool
    {
        $this->status = 'verified';
        return $this->save();
    }

    public function markAsStocked(): bool
    {
        $this->status = 'stocked';

        // Update device stock quantity
        if ($this->device) {
            $this->device->addStock($this->qty);
        }

        return $this->save();
    }

    /**
     * Update order device received quantity
     */
    public function updateOrderDeviceQuantity(): bool
    {
        if ($this->order_id && $this->device_id) {
            $orderDevice = OrderDevice::where('order_uuid', $this->order_id)
                                    ->where('device_uuid', $this->device_id)
                                    ->first();

            if ($orderDevice) {
                return $orderDevice->receiveQuantity($this->qty);
            }
        }

        return false;
    }

    /**
     * Check if this arrival completes the order device
     */
    public function completesOrderDevice(): bool
    {
        if ($this->order_id && $this->device_id) {
            $orderDevice = OrderDevice::where('order_uuid', $this->order_id)
                                    ->where('device_uuid', $this->device_id)
                                    ->first();

            if ($orderDevice) {
                $totalReceived = $orderDevice->qty_received + $this->qty;
                return $totalReceived >= $orderDevice->qty_ordered;
            }
        }

        return false;
    }

    /**
     * Get remaining quantity to receive for this device in the order
     */
    public function getRemainingQuantityToReceive(): int
    {
        if ($this->order_id && $this->device_id) {
            $orderDevice = OrderDevice::where('order_uuid', $this->order_id)
                                    ->where('device_uuid', $this->device_id)
                                    ->first();

            if ($orderDevice) {
                return $orderDevice->qty_pending;
            }
        }

        return 0;
    }

    public function createFromOrder(Order $order, Device $device, int $quantity, array $additionalData = []): self
    {
        $orderDevice = OrderDevice::where('order_id', $order->id)
                                 ->where('device_id', $device->id)
                                 ->first();

        if (!$orderDevice) {
            throw new \Exception('Order device not found');
        }

        return self::create(array_merge([
            'device_id' => $device->id,
            'order_id' => $order->id,
            'ht_price' => $orderDevice->ht_price,
            'tva_price' => $orderDevice->tva_price,
            'ttc_price' => $orderDevice->ttc_price,
            'qty' => $quantity,
            'order_number' => $order->order_number,
            'supplier' => $order->supplier->name ?? 'Unknown',
            'arrival_date' => now(),
        ], $additionalData));
    }
}
