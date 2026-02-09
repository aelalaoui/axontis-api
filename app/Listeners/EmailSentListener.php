<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

/**
 * Listener pour logger les emails envoyés avec succès
 *
 * Écoute l'événement MessageSent et log les informations
 * sur le provider utilisé et les métriques.
 */
class EmailSentListener
{
    /**
     * Gérer l'événement MessageSent
     */
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;
            $data = $event->data;

            // Extraire les informations du message
            $to = $this->extractRecipients($message);
            $subject = $message->getSubject();

            // Détecter le provider utilisé
            $mailer = config('mail.default');

            Log::info('Email envoyé avec succès', [
                'to' => $to,
                'subject' => $subject,
                'mailer' => $mailer,
            ]);

            // Métriques (optionnel - à connecter à votre système de monitoring)
            $this->recordMetrics($mailer, 'success');

        } catch (\Exception $e) {
            Log::warning('Erreur lors du logging de l\'email envoyé', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extraire les destinataires du message
     */
    protected function extractRecipients($message): array
    {
        $recipients = [];

        // Symfony Mailer
        if (method_exists($message, 'getTo')) {
            foreach ($message->getTo() as $address) {
                $recipients[] = $address->getAddress();
            }
        }

        return $recipients;
    }

    /**
     * Enregistrer les métriques d'envoi
     */
    protected function recordMetrics(string $provider, string $status): void
    {
        // TODO: Connecter à votre système de métriques (Prometheus, DataDog, etc.)
        // Exemple avec le cache pour un compteur simple
        $key = "email_metrics:{$provider}:{$status}:" . now()->format('Y-m-d');
        $count = cache()->increment($key, 1);

        // Expirer après 30 jours
        if ($count === 1) {
            cache()->put($key, 1, now()->addDays(30));
        }
    }
}
