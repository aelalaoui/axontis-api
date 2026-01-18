<?php
// app/Enums/UserRole.php

namespace App\Enums;

enum UserRole: string
{
    case CLIENT = 'client';
    case TECHNICIAN = 'technician';
    case OPERATOR = 'operator';
    case MANAGER = 'manager';
    case ADMINISTRATOR = 'administrator';

    /**
     * Obtenir le libellé en français du rôle
     */
    public function label(): string
    {
        return match($this) {
            self::CLIENT => 'Client',
            self::TECHNICIAN => 'Technicien',
            self::OPERATOR => 'Opérateur',
            self::MANAGER => 'Gestionnaire',
            self::ADMINISTRATOR => 'Administrateur',
        };
    }

    /**
     * Obtenir la description du rôle
     */
    public function description(): string
    {
        return match($this) {
            self::CLIENT => 'Accès limité aux informations personnelles et tickets',
            self::TECHNICIAN => 'Peut intervenir sur les tickets et gérer les interventions',
            self::OPERATOR => 'Gère les tickets, planifie les interventions',
            self::MANAGER => 'Supervise les équipes et génère des rapports',
            self::ADMINISTRATOR => 'Accès complet au système',
        };
    }

    /**
     * Obtenir le niveau hiérarchique du rôle (plus élevé = plus de privilèges)
     */
    public function level(): int
    {
        return match($this) {
            self::CLIENT => 1,
            self::TECHNICIAN => 2,
            self::OPERATOR => 3,
            self::MANAGER => 4,
            self::ADMINISTRATOR => 5,
        };
    }

    /**
     * Définir les permissions par rôle
     */
    public function permissions(): array
    {
        return match($this) {
            self::CLIENT => [
                'tickets.view_own',
                'tickets.create',
                'profile.view',
                'profile.edit',
            ],
            self::TECHNICIAN => [
                'tickets.view_assigned',
                'tickets.update',
                'interventions.view',
                'interventions.create',
                'interventions.update',
                'profile.view',
                'profile.edit',
            ],
            self::OPERATOR => [
                'tickets.*',
                'interventions.*',
                'clients.view',
                'technicians.view',
                'schedule.view',
                'schedule.manage',
                'reports.view_basic',
                'profile.*',
            ],
            self::MANAGER => [
                'tickets.*',
                'interventions.*',
                'clients.*',
                'technicians.*',
                'operators.view',
                'schedule.*',
                'reports.*',
                'analytics.view',
                'profile.*',
            ],
            self::ADMINISTRATOR => ['*'], // Toutes les permissions
        };
    }

    /**
     * Vérifier si le rôle possède une permission
     */
    public function can(string $permission): bool
    {
        $permissions = $this->permissions();

        // Admin a tous les droits
        if (in_array('*', $permissions)) {
            return true;
        }

        // Vérification exacte
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Support des wildcards (ex: tickets.*)
        foreach ($permissions as $p) {
            if (str_ends_with($p, '.*')) {
                $prefix = str_replace('.*', '', $p);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vérifier si le rôle a un niveau supérieur ou égal
     */
    public function hasLevelOrAbove(UserRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Obtenir la couleur associée au rôle (pour l'UI)
     */
    public function color(): string
    {
        return match($this) {
            self::CLIENT => 'gray',
            self::TECHNICIAN => 'blue',
            self::OPERATOR => 'green',
            self::MANAGER => 'purple',
            self::ADMINISTRATOR => 'red',
        };
    }

    /**
     * Obtenir l'icône associée au rôle
     */
    public function icon(): string
    {
        return match($this) {
            self::CLIENT => 'fa-user',
            self::TECHNICIAN => 'fa-tools',
            self::OPERATOR => 'fa-headset',
            self::MANAGER => 'fa-user-tie',
            self::ADMINISTRATOR => 'fa-shield-alt',
        };
    }
}
