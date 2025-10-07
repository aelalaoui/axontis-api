<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\HasProperties;
use App\Enums\ClientStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * App\Models\Client
 *
 * @property string $uuid
 * @property string|null $user_id
 * @property string $type
 * @property string|null $company_name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $country
 * @property ClientStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contract> $contracts
 * @property-read int|null $contracts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Alert> $alerts
 * @property-read int|null $alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Claim> $claims
 * @property-read int|null $claims_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\File> $files
 * @property-read int|null $files_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Signature> $signatures
 * @property-read int|null $signatures_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Communication> $communications
 * @property-read int|null $communications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Property> $properties
 * @property-read int|null $properties_count
 * @property-read string $full_name
 */
class Client extends Model
{
    use HasFactory, HasUuid, HasProperties;

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
