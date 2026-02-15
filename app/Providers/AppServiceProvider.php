<?php

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Address;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Enregistrer le transport Brevo
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn(
                    'brevo+api',
                    'default',
                    config('services.brevo.key')
                )
            );
        });

        // Gérer dynamiquement le from selon le mailer
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $this->setDynamicFrom($event);
        });
    }

    protected function setDynamicFrom(MessageSending $event): void
    {
        // Déterminer quel mailer est utilisé
        $currentMailer = config('mail.default');

        // Si on utilise failover, prendre le premier mailer
        if ($currentMailer === 'failover') {
            $mailers = config('mail.mailers.failover.mailers', []);
            $currentMailer = $mailers[0] ?? 'resend';
        }

        // Récupérer la config from du mailer
        $fromConfig = config("mail.mailers.{$currentMailer}.from");

        if (!$fromConfig || !isset($fromConfig['address'])) {
            return; // Pas de config spécifique, utiliser le from par défaut
        }

        // Modifier le from du message
        $symfonyMessage = $event->message;

        // Créer l'adresse avec ou sans nom
        $address = new Address(
            $fromConfig['address'],
            $fromConfig['name'] ?? config('mail.from.name', '')
        );

        // Remplacer le from
        $symfonyMessage->from($address);
    }
}
