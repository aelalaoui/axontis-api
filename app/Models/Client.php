<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Enums\ClientStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Client extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'type',
        'company_name',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'status',
    ];

    protected $casts = [
        'type' => 'string',
        'status' => ClientStatus::class,
    ];

    protected $attributes = [
        'country' => 'Morocco',
        'status' => 'email_step',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
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
        return $this->morphMany(Signature::class, 'signable');
    }

    public function communications(): MorphMany
    {
        return $this->morphMany(Communication::class, 'communicable');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        if ($this->type === 'business') {
            return $this->company_name ?? '';
        }

        return trim($this->first_name . ' ' . $this->last_name);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active_client');
    }

    public function scopeProspects($query)
    {
        return $query->where('status', 'prospect');
    }

    public function scopeIndividuals($query)
    {
        return $query->where('type', 'individual');
    }

    public function scopeBusinesses($query)
    {
        return $query->where('type', 'business');
    }
}
