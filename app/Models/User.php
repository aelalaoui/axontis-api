<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    // Méthodes de vérification des rôles
    // ==========================================

    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    public function isTechnician(): bool
    {
        return $this->role === UserRole::TECHNICIAN;
    }

    public function isOperator(): bool
    {
        return $this->role === UserRole::OPERATOR;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::ADMINISTRATOR;
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(UserRole|string $role): bool
    {
        if (is_string($role)) {
            $role = UserRole::from($role);
        }

        return $this->role === $role;
    }

    /**
     * Vérifier si l'utilisateur a l'un des rôles spécifiés
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifier si l'utilisateur a un niveau de rôle égal ou supérieur
     */
    public function hasLevelOrAbove(UserRole|string $role): bool
    {
        if (is_string($role)) {
            $role = UserRole::from($role);
        }

        return $this->role->hasLevelOrAbove($role);
    }

    // ==========================================
    // Méthodes de permissions
    // ==========================================

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function can($ability, $arguments = []): bool
    {
        // Vérifier d'abord les permissions Laravel par défaut
        if (parent::can($ability, $arguments)) {
            return true;
        }

        // Puis vérifier les permissions basées sur les rôles
        return $this->role->can($ability);
    }

    /**
     * Vérifier si l'utilisateur a toutes les permissions
     */
    public function canAll(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->can($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Vérifier si l'utilisateur a au moins une des permissions
     */
    public function canAny($abilities, $arguments = []): bool
    {
        // If $abilities is an array, we can iterate
        if (is_array($abilities)) {
            foreach ($abilities as $ability) {
                if ($this->can($ability, $arguments)) {
                    return true;
                }
            }
            return false;
        }

        // Fallback to default behavior if it's a single string
        return $this->can($abilities, $arguments);
    }

    // ==========================================
    // Scopes pour les requêtes
    // ==========================================

    /**
     * Scope pour filtrer par rôle
     */
    public function scopeWithRole($query, UserRole|string $role)
    {
        if (is_string($role)) {
            $role = UserRole::from($role);
        }

        return $query->where('role', $role->value);
    }

    /**
     * Scope pour les clients
     */
    public function scopeClients($query)
    {
        return $query->where('role', UserRole::CLIENT->value);
    }

    /**
     * Scope pour les techniciens
     */
    public function scopeTechnicians($query)
    {
        return $query->where('role', UserRole::TECHNICIAN->value);
    }

    /**
     * Scope pour les opérateurs
     */
    public function scopeOperators($query)
    {
        return $query->where('role', UserRole::OPERATOR->value);
    }

    /**
     * Scope pour les managers
     */
    public function scopeManagers($query)
    {
        return $query->where('role', UserRole::MANAGER->value);
    }

    /**
     * Scope pour les administrateurs
     */
    public function scopeAdministrators($query)
    {
        return $query->where('role', UserRole::ADMINISTRATOR->value);
    }

    /**
     * Scope pour le staff (tous sauf clients)
     */
    public function scopeStaff($query)
    {
        return $query->whereNot('role', UserRole::CLIENT->value);
    }

    /**
     * Get the signatures where this user is the signer.
     */
    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable_by', 'signable_by_type', 'signable_by_uuid');
    }
}
