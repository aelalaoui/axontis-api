<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class Contract
 *
 * Represents a contractual agreement between a client and the system, handling
 * several aspects such as installations, payments, alerts, tasks, claims,
 * files, signatures, and communications. Provides utility methods for
 * financial calculations and status evaluations.
 *
 * Properties:
 * - Defines the fillable attributes to allow mass assignment.
 * - Casts specific attributes to their required data types.
 * - Sets default values for some attributes.
 *
 * Relationships:
 * - `client`: Links the contract to a single client.
 * - `installations`: Relates to multiple installations tied to the contract.
 * - `payments`: Relates to multiple payments associated with the contract.
 * - `alerts`: Relates to multiple alerts linked to the contract.
 * - `tasks`: Morph relationship for tasks associated with the contract.
 * - `claims`: Morph relationship for claims related to the contract.
 * - `files`: Morph relationship for files linked to the contract.
 * - `signatures`: Morph relationship for electronic signatures associated
 *   with the contract.
 * - `communications`: Morph relationship for communications linked
 *   to the contract.
 *
 * Scopes:
 * - `active`: Filters contracts having a status of 'active'.
 * - `signed`: Filters contracts having a status of 'signed'.
 * - `pending`: Filters contracts having a status of 'pending'.
 * - `scheduled`: Filters contracts having a status of 'scheduled'.
 * - `terminated`: Filters contracts marked as having a termination date.
 *
 * Accessors:
 * - `is_active`: Determines if the contract is active based on its status.
 * - `is_terminated`: Checks whether the contract has been terminated.
 * - `total_paid`: Calculates the total amount paid for the contract from
 *   successful payments.
 *
 * Financial Calculations:
 * - Monthly amounts:
 *   - `monthly_ht`: Calculates the monthly amount excluding taxes (HT).
 *   - `monthly_tva`: Calculates the tax value added (TVA) for the monthly
 *     amount.
 *   - `monthly_ttc`: Calculates the total monthly amount including taxes
 *     (TTC).
 * - Subscription amounts:
 *   - `subscription_ht`: Calculates the subscription price excluding taxes
 *     (HT).
 *   - `subscription_tva`: Calculates the tax value added (TVA) for the
 *     subscription price.
 *   - `subscription_ttc`: Calculates the total subscription price including
 *     taxes (TTC).
 * @property string $uuid
 * @property string $client_uuid
 * @property string $start_date
 * @property string|null $due_date
 * @property string|null $termination_date
 * @property string $status
 * @property int $monthly_amount_cents
 * @property int $subscription_price_cents
 * @property int $vat_rate_percentage
 * @property string $currency
 * @property string $description
 * @property-read bool $is_active
 * @property-read bool $is_terminated
 * @property-read float $total_paid
 * @property-read float $monthly_ht
 * @property-read float $monthly_tva
 * @property-read float $monthly_ttc
 * @property-read float $subscription_ht
 * @property-read float $subscription_tva
 * @property-read float $subscription_ttc
 * @method static active()
 * @method static signed()
 * @method static pending()
 * @method static scheduled()
 * @method static terminated()
 */
class Contract extends Model
{
    use HasUuid;

    protected $fillable = [
        'client_uuid',
        'start_date',
        'due_date',
        'termination_date',
        'status',
        'monthly_amount_cents',
        'subscription_price_cents',
        'vat_rate_percentage',
        'currency',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'string',
        'termination_date' => 'date',
        'monthly_amount_cents' => 'integer',
        'subscription_price_cents' => 'integer',
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
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class, 'contract_uuid', 'uuid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'contract_uuid', 'uuid');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'contract_uuid', 'uuid');
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
        return $query->where('status', ContractStatus::ACTIVE->value);
    }

    public function scopeSigned($query)
    {
        return $query->where('status', ContractStatus::SIGNED->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', ContractStatus::PENDING->value);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', ContractStatus::SCHEDULED->value);
    }

    public function scopeTerminated($query)
    {
        return $query->whereNotNull('termination_date');
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === ContractStatus::ACTIVE->value;
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

    // Calculate subscription price amounts from cents
    public function getSubscriptionHtAttribute(): float
    {
        return $this->subscription_price_cents / 100;
    }

    public function getSubscriptionTvaAttribute(): float
    {
        return ($this->subscription_price_cents * $this->vat_rate_percentage) / 10000;
    }

    public function getSubscriptionTtcAttribute(): float
    {
        return $this->subscription_ht + $this->subscription_tva;
    }
}
