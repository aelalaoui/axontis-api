<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Signature extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'signable_type',
        'signable_id',
        'signable_by_type',
        'signable_by_id',
        'signature_file',
        'signature_type',
        'signed_at',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'metadata' => 'array',
        'signature_type' => 'string',
    ];

    // Relationships
    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function signableBy(): MorphTo
    {
        return $this->morphTo('signable_by');
    }

    // Scopes
    public function scopeDigital($query)
    {
        return $query->where('signature_type', 'digital');
    }

    public function scopeElectronic($query)
    {
        return $query->where('signature_type', 'electronic');
    }

    public function scopeHandwritten($query)
    {
        return $query->where('signature_type', 'handwritten');
    }

    public function scopeSignedToday($query)
    {
        return $query->whereDate('signed_at', today());
    }

    public function scopeSignedThisWeek($query)
    {
        return $query->whereBetween('signed_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeSignedThisMonth($query)
    {
        return $query->whereMonth('signed_at', now()->month)
                    ->whereYear('signed_at', now()->year);
    }

    // Accessors
    public function getIsDigitalAttribute(): bool
    {
        return $this->signature_type === 'digital';
    }

    public function getIsElectronicAttribute(): bool
    {
        return $this->signature_type === 'electronic';
    }

    public function getIsHandwrittenAttribute(): bool
    {
        return $this->signature_type === 'handwritten';
    }

    public function getSignatureUrlAttribute(): ?string
    {
        if (!$this->signature_file) {
            return null;
        }

        return asset('storage/' . $this->signature_file);
    }

    public function getSignerNameAttribute(): string
    {
        $signer = $this->signableBy;
        
        if (!$signer) {
            return 'Unknown Signer';
        }

        // Handle different signer types
        if ($signer instanceof User) {
            return $signer->name;
        }

        if ($signer instanceof Client) {
            return $signer->full_name;
        }

        return 'Unknown Signer';
    }

    // Methods
    public function hasValidSignature(): bool
    {
        return !empty($this->signature_file) && $this->signed_at !== null;
    }

    public function getSignatureMetadata(string $key = null)
    {
        if ($key) {
            return $this->metadata[$key] ?? null;
        }

        return $this->metadata;
    }

    public function setSignatureMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
    }
}