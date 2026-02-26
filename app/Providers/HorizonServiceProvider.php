<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Notifications Horizon (alertes quand les queues sont longues, etc.)
        // Horizon::routeMailNotificationsTo('admin@axontis.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#alerts');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     * Only administrators can access the Horizon dashboard.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if (! $user) {
                return false;
            }

            return $user->role === UserRole::ADMINISTRATOR;
        });
    }
}
