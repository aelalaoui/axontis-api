<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Alert extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'client_id',
        'contract_id',
        'type',
        'severity',
        'description',
        'triggered_at',
        'resolved',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
        'resolved' => 'boolean',
        'type' => 'string',
        'severity' => 'string',
    ];

    protected $attributes = [
        'resolved' => false,
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
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

    // Scopes
    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function scopeMedium($query)
    {
        return $query->where('severity', 'medium');
    }

    public function scopeLow($query)
    {
        return $query->where('severity', 'low');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getIsResolvedAttribute(): bool
    {
        return $this->resolved;
    }

    public function getIsCriticalAttribute(): bool
    {
        return $this->severity === 'critical';
    }

    public function getResponseTimeAttribute(): ?int
    {
        if (!$this->resolved || !$this->resolved_at) {
            return null;
        }

        return $this->triggered_at->diffInMinutes($this->resolved_at);
    }

    // Methods
    public function resolve(User $user = null): bool
    {
        $this->resolved = true;
        $this->resolved_at = now();

        if ($user) {
            $this->resolved_by = $user->id;
        }

        return $this->save();
    }
}