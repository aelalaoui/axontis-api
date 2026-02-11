<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSendingFailed;
use Illuminate\Support\Facades\Log;

/**
 * Listener pour le failover automatique des emails
 *
 * Note: Laravel gère nativement le failover via le transport 'failover'
 * configuré dans config/mail.php. Ce listener est principalement utilisé
 * pour le logging et le monitoring du failover.
 *
 * Le failover automatique est activé via la configuration :
 * 'failover' => [
 *     'transport' => 'failover',
 *     'mailers' => ['resend', 'mailgun', 'brevo'],
 * ]
 */
class EmailFailoverListener
{
    /**
     * Ordre des mailers pour le failover
     */
    protected array $mailerOrder = [
        'resend',
        'mailgun',
        'brevo',
    ];

    /**
     * Gérer l'événement MessageSendingFailed
     */
    public function handle(MessageSendingFailed $event): void
    {
        $exception = $event->data['exception'] ?? null;

        // Logger l'échec
        Log::warning('Échec d\'envoi email - Le transport failover va tenter le prochain mailer', [
            'mailer' => config('mail.default'),
            'error' => $exception?->getMessage() ?? 'Unknown error',
            'recipients' => $this->extractRecipients($event->message),
        ]);

        // Enregistrer les métriques d'échec
        $this->recordFailureMetrics();

        // Note: Le failover est géré automatiquement par Laravel
        // via le transport 'failover' configuré dans mail.php
        // Ce listener sert principalement au monitoring
    }

    /**
     * Extraire les destinataires du message
     */
    protected function extractRecipients($message): array
    {
        $recipients = [];

        if (method_exists($message, 'getTo')) {
            foreach ($message->getTo() as $address) {
                $recipients[] = $address->getAddress();
            }
        }

        return $recipients;
    }

    /**
     * Enregistrer les métriques d'échec
     */
    protected function recordFailureMetrics(): void
    {
        $key = 'email_failure_count:' . now()->format('Y-m-d');
        $count = cache()->increment($key, 1);

        if ($count === 1) {
            cache()->put($key, 1, now()->addDays(30));
        }

        // Alerte si trop d'échecs
        if ($count >= 10) {
            $this->alertHighFailureRate($count);
        }
    }

    /**
     * Alerter en cas de taux d'échec élevé
     */
    protected function alertHighFailureRate(int $count): void
    {
        // Éviter les alertes en boucle (une fois par heure)
        $alertKey = 'email_failure_alert:' . now()->format('Y-m-d-H');

        if (cache()->has($alertKey)) {
            return;
        }

        cache()->put($alertKey, true, now()->addHour());

        Log::critical('Taux d\'échec email élevé détecté', [
            'count' => $count,
            'date' => now()->format('Y-m-d'),
        ]);

        // TODO: Envoyer une alerte à l'équipe technique via un autre canal (Slack, Telegram)
    }
}
