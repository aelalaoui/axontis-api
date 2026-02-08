<?php

namespace App\Listeners;

use App\Models\Communication;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

/**
 * Listener pour tracer les notifications dans la table communications
 *
 * Écoute l'événement NotificationSent et crée une entrée dans la table
 * communications pour chaque notification envoyée avec succès.
 */
class LogNotificationToCommunication
{
    /**
     * Gérer l'événement NotificationSent
     */
    public function handle(NotificationSent $event): void
    {
        try {
            $notifiable = $event->notifiable;
            $notification = $event->notification;
            $channel = $event->channel;

            // Mapper le canal Laravel vers l'enum de la table
            $mappedChannel = Communication::mapChannel($channel);

            // Extraire le sujet et le message
            $subject = $this->extractSubject($notification, $channel);
            $message = $this->extractMessage($notification, $channel, $event->response);

            // Obtenir l'ID de l'utilisateur qui a déclenché la notification
            $handledBy = null;
            if ($notification instanceof BaseNotification) {
                $handledBy = $notification->handledBy;
            }

            // Détecter le provider utilisé
            $provider = $this->detectProvider($channel, $event->response);

            // Créer l'entrée dans communications
            // Note: L'UUID est auto-généré par le trait HasUuid
            Communication::create([
                'communicable_type' => get_class($notifiable),
                'communicable_id' => $notifiable->id ?? $notifiable->getKey(),
                'channel' => $mappedChannel,
                'direction' => Communication::DIRECTION_OUTBOUND,
                'subject' => $subject,
                'message' => $message,
                'handled_by' => $handledBy,
                'sent_at' => now(),
                'status' => Communication::STATUS_SENT,
                'notification_type' => get_class($notification),
                'provider' => $provider,
                'metadata' => $this->buildMetadata($event),
            ]);

            Log::info('Communication tracée avec succès', [
                'channel' => $mappedChannel,
                'notification' => class_basename($notification),
                'notifiable_type' => class_basename($notifiable),
                'notifiable_id' => $notifiable->id ?? $notifiable->getKey(),
            ]);

        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer le processus
            Log::error('Erreur lors de la traçabilité de la communication', [
                'error' => $e->getMessage(),
                'notification' => get_class($event->notification),
            ]);
        }
    }

    /**
     * Extraire le sujet de la notification
     */
    protected function extractSubject(object $notification, string $channel): ?string
    {
        // Si c'est une BaseNotification, utiliser la propriété subject
        if ($notification instanceof BaseNotification) {
            return $notification->subject;
        }

        // Essayer d'obtenir le sujet depuis la réponse mail
        if ($channel === 'mail' && method_exists($notification, 'toMail')) {
            try {
                $mailMessage = $notification->toMail(new \stdClass());
                return $mailMessage->subject ?? null;
            } catch (\Exception $e) {
                // Ignorer
            }
        }

        return null;
    }

    /**
     * Extraire le message de la notification
     */
    protected function extractMessage(object $notification, string $channel, $response): ?string
    {
        // Pour BaseNotification, utiliser getMessageContent
        if ($notification instanceof BaseNotification) {
            return $notification->getMessageContent();
        }

        // Pour les notifications mail
        if ($channel === 'mail' && method_exists($notification, 'toMail')) {
            try {
                $mailMessage = $notification->toMail(new \stdClass());
                // Récupérer les lignes du message
                $lines = $mailMessage->introLines ?? [];
                return implode("\n", $lines);
            } catch (\Exception $e) {
                // Ignorer
            }
        }

        // Pour les autres canaux, utiliser toArray si disponible
        if (method_exists($notification, 'toArray')) {
            try {
                $data = $notification->toArray(new \stdClass());
                return json_encode($data, JSON_UNESCAPED_UNICODE);
            } catch (\Exception $e) {
                // Ignorer
            }
        }

        return null;
    }

    /**
     * Détecter le provider utilisé pour l'envoi
     */
    protected function detectProvider(string $channel, $response): ?string
    {
        if ($channel === 'mail') {
            // Essayer de détecter le mailer utilisé
            $defaultMailer = config('mail.default');

            // Pour le failover, on ne peut pas facilement savoir quel mailer a été utilisé
            // On retourne le mailer par défaut ou failover
            return $defaultMailer;
        }

        if (str_contains($channel, 'sms') || str_contains($channel, 'Sms')) {
            return config('services.sms.provider', 'twilio');
        }

        if (str_contains($channel, 'whatsapp') || str_contains($channel, 'WhatsApp')) {
            return 'twilio-whatsapp';
        }

        if (str_contains($channel, 'telegram') || str_contains($channel, 'Telegram')) {
            return 'telegram';
        }

        if ($channel === 'slack') {
            return 'slack';
        }

        return null;
    }

    /**
     * Construire les métadonnées de la notification
     */
    protected function buildMetadata(NotificationSent $event): array
    {
        $metadata = [
            'notification_id' => $event->notification->id ?? null,
            'sent_at' => now()->toIso8601String(),
            'channel_raw' => $event->channel,
        ];

        // Ajouter les données de la notification si disponibles
        if ($event->notification instanceof BaseNotification) {
            $metadata['notification_data'] = $event->notification->getNotificationData();
        }

        // Si la réponse contient des informations utiles
        if (is_array($event->response)) {
            $metadata['response'] = $event->response;
        }

        return $metadata;
    }
}
