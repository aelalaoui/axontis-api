<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contract extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'client_id',
        'start_date',
        'due_date',
        'termination_date',
        'status',
        'monthly_amount_cents',
        'vat_rate_percentage',
        'currency',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'string',
        'termination_date' => 'date',
        'monthly_amount_cents' => 'integer',
        'vat_rate_percentage' => 'integer',
        'status' => 'string',
    ];

    protected $attributes = [
        'status' => 'pending',
        'currency' => 'MAD',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class, 'contract_uuid', 'uuid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
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
        return $this->morphMany(Signature::class, 'signable', 'signable_type', 'signable_uuid');
    }

    public function communications(): MorphMany
    {
        return $this->morphMany(Communication::class, 'communicable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeTerminated($query)
    {
        return $query->whereNotNull('termination_date');
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsTerminatedAttribute(): bool
    {
        return $this->termination_date !== null;
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->where('status', 'successful')->sum('amount');
    }

    // Calculate monthly amounts from cents
    public function getMonthlyHtAttribute(): float
    {
        return $this->monthly_amount_cents / 100;
    }

    public function getMonthlyTvaAttribute(): float
    {
        return ($this->monthly_amount_cents * $this->vat_rate_percentage) / 10000;
    }

    public function getMonthlyTtcAttribute(): float
    {
        return $this->monthly_ht + $this->monthly_tva;
    }
}
