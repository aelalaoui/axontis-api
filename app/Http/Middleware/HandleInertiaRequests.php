<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'profile_photo_url' => $request->user()->profile_photo_url,

                    // Informations de rôle
                    'role' => $request->user()->role->value,
                    'role_info' => $request->user()->role_info,

                    // Vérifications rapides des rôles
                    'is_client' => $request->user()->isClient(),
                    'is_technician' => $request->user()->isTechnician(),
                    'is_operator' => $request->user()->isOperator(),
                    'is_manager' => $request->user()->isManager(),
                    'is_administrator' => $request->user()->isAdministrator(),
                ] : null,
            ],

            // Permissions pour l'interface
            'can' => $request->user() ? [
                // Gestion des tickets
                'tickets.view_own' => $request->user()->can('tickets.view_own'),
                'tickets.view_all' => $request->user()->can('tickets.view'),
                'tickets.create' => $request->user()->can('tickets.create'),
                'tickets.update' => $request->user()->can('tickets.update'),
                'tickets.delete' => $request->user()->can('tickets.delete'),

                // Gestion des interventions
                'interventions.view' => $request->user()->can('interventions.view'),
                'interventions.create' => $request->user()->can('interventions.create'),
                'interventions.update' => $request->user()->can('interventions.update'),
                'interventions.delete' => $request->user()->can('interventions.delete'),

                // Gestion des clients
                'clients.view' => $request->user()->can('clients.view'),
                'clients.create' => $request->user()->can('clients.create'),
                'clients.update' => $request->user()->can('clients.update'),
                'clients.delete' => $request->user()->can('clients.delete'),

                // Gestion des techniciens
                'technicians.view' => $request->user()->can('technicians.view'),
                'technicians.create' => $request->user()->can('technicians.create'),
                'technicians.update' => $request->user()->can('technicians.update'),
                'technicians.delete' => $request->user()->can('technicians.delete'),

                // Planning
                'schedule.view' => $request->user()->can('schedule.view'),
                'schedule.manage' => $request->user()->can('schedule.manage'),

                // Rapports et analytics
                'reports.view_basic' => $request->user()->can('reports.view_basic'),
                'reports.view_all' => $request->user()->can('reports.view'),
                'analytics.view' => $request->user()->can('analytics.view'),

                // Administration
                'users.manage' => $request->user()->can('users.manage'),
                'settings.manage' => $request->user()->can('settings.manage'),
            ] : [],
        ];
    }
}
