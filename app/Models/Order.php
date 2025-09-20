<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'type',
        'status',
        'requested_by',
        'approved_by',
        'supplier_id',
        'quotation_file_id',
        'order_date',
        'expected_delivery_date',
        'total_ht',
        'total_tva',
        'total_ttc',
        'notes',
        'priority',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'draft',
        'priority' => 'normal',
    ];

    // Boot method to generate order number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    // Relationships
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function quotationFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'quotation_file_id');
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'order_device')
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

    public function arrivals(): HasMany
    {
        return $this->hasMany(Arrival::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getIsApprovedAttribute(): bool
    {
        return !is_null($this->approved_by);
    }

    public function getCanBeOrderedAttribute(): bool
    {
        return $this->status === 'approved' && !is_null($this->supplier_id);
    }

    // Methods
    public static function generateOrderNumber(): string
    {
        $date = Carbon::now();
        $prefix = 'ORD-' . $date->format('Y') . $date->format('m');
        
        // Get the last order number for this month
        $lastOrder = self::where('order_number', 'like', $prefix . '%')
                         ->orderBy('order_number', 'desc')
                         ->first();
        
        if ($lastOrder) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastOrder->order_number, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }
        
        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    public function approve(User $approver): bool
    {
        $this->status = 'approved';
        $this->approved_by = $approver->id;
        return $this->save();
    }

    public function markAsOrdered(): bool
    {
        $this->status = 'ordered';
        $this->order_date = Carbon::now();
        return $this->save();
    }

    public function markAsCompleted(): bool
    {
        $this->status = 'completed';
        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }

    public function calculateTotals(): void
    {
        $totalHt = 0;
        $totalTva = 0;
        
        foreach ($this->devices as $device) {
            $qty = $device->pivot->qty_ordered;
            $totalHt += $device->pivot->ht_price * $qty;
            $totalTva += $device->pivot->tva_price * $qty;
        }
        
        $this->total_ht = $totalHt;
        $this->total_tva = $totalTva;
        $this->total_ttc = $totalHt + $totalTva;
        $this->save();
    }
}