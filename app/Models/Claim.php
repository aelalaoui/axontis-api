<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claimable_type',
        'claimable_id',
        'subject',
        'description',
        'status',
        'priority',
        'assigned_to',
        'closed_at',
        'task_device_id',
        'claim_type',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'status' => 'string',
        'priority' => 'string',
        'claim_type' => 'string',
    ];

    protected $attributes = [
        'status' => 'open',
        'priority' => 'medium',
        'claim_type' => 'other',
    ];

    // Relationships
    public function claimable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function taskDevice(): BelongsTo
    {
        return $this->belongsTo(TaskDevice::class, 'task_device_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function communications(): MorphMany
    {
        return $this->morphMany(Communication::class, 'communicable');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeMedium($query)
    {
        return $query->where('priority', 'medium');
    }

    public function scopeLow($query)
    {
        return $query->where('priority', 'low');
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeByClaimType($query, $type)
    {
        return $query->where('claim_type', $type);
    }

    public function scopeWarranty($query)
    {
        return $query->where('claim_type', 'warranty');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('claim_type', 'maintenance');
    }

    public function scopeRepair($query)
    {
        return $query->where('claim_type', 'repair');
    }

    public function scopeReplacement($query)
    {
        return $query->where('claim_type', 'replacement');
    }

    // Accessors
    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'open';
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, ['closed', 'resolved', 'rejected']);
    }

    public function getIsCriticalAttribute(): bool
    {
        return $this->priority === 'critical';
    }

    public function getResolutionTimeAttribute(): ?int
    {
        if (!$this->closed_at) {
            return null;
        }

        return $this->created_at->diffInHours($this->closed_at);
    }

    public function getIsDeviceRelatedAttribute(): bool
    {
        return !is_null($this->task_device_id);
    }

    // Methods
    public function assignTo(User $user): bool
    {
        $this->assigned_to = $user->id;
        
        if ($this->status === 'open') {
            $this->status = 'in_progress';
        }

        return $this->save();
    }

    public function close(string $status = 'closed'): bool
    {
        $this->status = $status;
        $this->closed_at = now();

        return $this->save();
    }

    public function assignToDevice(TaskDevice $taskDevice): bool
    {
        $this->task_device_id = $taskDevice->id;
        return $this->save();
    }
}