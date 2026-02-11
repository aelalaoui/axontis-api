<?php

namespace App\Listeners;

use App\Models\Communication;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Listener pour gérer les échecs de notification
 *
 * Écoute l'événement NotificationFailed et :
 * - Log l'échec avec les détails
 * - Crée une entrée dans communications avec le statut failed
 * - Alerte l'équipe technique si l'échec est critique
 */
class NotificationFailureAlertListener
{
    /**
     * Nombre maximum de retry avant alerte critique
     */
    protected int $maxRetries = 3;

    /**
     * Gérer l'événement NotificationFailed
     */
    public function handle(NotificationFailed $event): void
    {
        $notifiable = $event->notifiable;
        $notification = $event->notification;
        $channel = $event->channel;
        $exception = $event->data['exception'] ?? null;

        // Logger l'échec
        Log::error('Échec d\'envoi de notification', [
            'notification' => get_class($notification),
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id ?? 'N/A',
            'channel' => $channel,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);

        // Créer une entrée dans communications avec statut failed
        $this->createFailedCommunication($event);

        // Vérifier si c'est un échec critique
        $this->checkCriticalFailure($event);
    }

    /**
     * Créer une entrée de communication en échec
     */
    protected function createFailedCommunication(NotificationFailed $event): void
    {
        try {
            $notifiable = $event->notifiable;
            $notification = $event->notification;
            $exception = $event->data['exception'] ?? null;

            Communication::create([
                'communicable_type' => get_class($notifiable),
                'communicable_id' => $notifiable->id ?? $notifiable->getKey(),
                'channel' => Communication::mapChannel($event->channel),
                'direction' => Communication::DIRECTION_OUTBOUND,
                'subject' => $notification->subject ?? null,
                'message' => null,
                'sent_at' => now(),
                'status' => Communication::STATUS_FAILED,
                'notification_type' => get_class($notification),
                'failed_at' => now(),
                'retry_count' => $notification->attempts ?? 1,
                'metadata' => [
                    'error' => $exception?->getMessage() ?? 'Unknown error',
                    'error_class' => $exception ? get_class($exception) : null,
                    'channel' => $event->channel,
                    'failed_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('Impossible de créer la communication en échec', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Vérifier si c'est un échec critique et alerter l'équipe
     */
    protected function checkCriticalFailure(NotificationFailed $event): void
    {
        $notification = $event->notification;

        // Vérifier le nombre de tentatives
        $attempts = $notification->attempts ?? 1;

        if ($attempts >= $this->maxRetries) {
            $this->alertTechTeam($event);
        }
    }

    /**
     * Alerter l'équipe technique via Slack et/ou Telegram
     */
    protected function alertTechTeam(NotificationFailed $event): void
    {
        $notification = $event->notification;
        $notifiable = $event->notifiable;
        $exception = $event->data['exception'] ?? null;

        $alertMessage = sprintf(
            "🚨 Échec critique de notification\n\n" .
            "Type: %s\n" .
            "Destinataire: %s #%s\n" .
            "Canal: %s\n" .
            "Erreur: %s\n" .
            "Tentatives: %d",
            class_basename($notification),
            class_basename($notifiable),
            $notifiable->id ?? 'N/A',
            $event->channel,
            $exception?->getMessage() ?? 'Unknown',
            $notification->attempts ?? 1
        );

        // Alerte Slack
        $this->sendSlackAlert($alertMessage);

        // Alerte Telegram
        $this->sendTelegramAlert($alertMessage);
    }

    /**
     * Envoyer une alerte Slack
     */
    protected function sendSlackAlert(string $message): void
    {
        $webhook = config('services.notifications.tech_team.slack_webhook');

        if (!$webhook) {
            return;
        }

        try {
            Http::post($webhook, [
                'text' => $message,
                'username' => config('app.name') . ' Alerts',
                'icon_emoji' => ':rotating_light:',
            ]);
        } catch (\Exception $e) {
            Log::warning('Impossible d\'envoyer l\'alerte Slack', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer une alerte Telegram
     */
    protected function sendTelegramAlert(string $message): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.notifications.tech_team.telegram_chat_id');

        if (!$token || !$chatId) {
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::warning('Impossible d\'envoyer l\'alerte Telegram', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
