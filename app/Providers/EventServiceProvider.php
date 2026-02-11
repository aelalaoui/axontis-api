<?php

namespace App\Providers;

use App\Listeners\EmailSentListener;
use App\Listeners\LogNotificationToCommunication;
use App\Listeners\NotificationFailureAlertListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Authentification
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Notifications - Traçabilité et monitoring
        NotificationSent::class => [
            LogNotificationToCommunication::class,
        ],

        NotificationFailed::class => [
            NotificationFailureAlertListener::class,
        ],

        // Emails - Succès (le failover est géré automatiquement par Laravel)
        MessageSent::class => [
            EmailSentListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
