<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

/**
 * Classe abstraite BaseNotification
 *
 * Classe de base pour toutes les notifications de l'application.
 * Gère automatiquement :
 * - Le routage vers les queues appropriées (viaQueues)
 * - La vérification des préférences utilisateur
 * - La structure commune des notifications
 *
 * @property string $subject Sujet de la notification (pour email et traçabilité)
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Sujet de la notification
     */
    public string $subject = '';

    /**
     * ID de l'utilisateur ayant déclenché la notification (optionnel)
     */
    public ?int $handledBy = null;

    /**
     * Canaux forcés (ignore les préférences utilisateur)
     */
    protected array $forcedChannels = [];

    /**
     * Canaux par défaut si aucune préférence n'est définie
     */
    protected array $defaultChannels = ['mail'];

    /**
     * Configuration du nombre de tentatives et du délai
     */
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    /**
     * Définir les canaux pour cette notification
     *
     * Cette méthode vérifie les préférences de l'utilisateur avant de retourner les canaux.
     * Les classes concrètes peuvent surcharger getRequestedChannels() pour définir
     * les canaux souhaités.
     */
    public function via(object $notifiable): array
    {
        // Si des canaux sont forcés, les utiliser directement
        if (!empty($this->forcedChannels)) {
            return $this->forcedChannels;
        }

        // Obtenir les canaux demandés par la notification concrète
        $requestedChannels = $this->getRequestedChannels();

        // Filtrer selon les préférences utilisateur
        return $this->filterChannelsByPreferences($notifiable, $requestedChannels);
    }

    /**
     * Définir le routage vers les queues par canal
     *
     * Chaque canal est routé vers sa queue dédiée pour une gestion optimale.
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
            'slack' => 'telegram',
            'database' => 'default',
            // Classes de canaux personnalisés
            \App\Notifications\Channels\TelegramChannel::class => 'telegram',
            \App\Notifications\Channels\WhatsAppChannel::class => 'whatsapp',
            \App\Notifications\Channels\SmsChannel::class => 'sms',
        ];
    }

    /**
     * Obtenir les canaux demandés par cette notification
     *
     * À surcharger dans les classes concrètes pour définir les canaux.
     */
    protected function getRequestedChannels(): array
    {
        return $this->defaultChannels;
    }

    /**
     * Filtrer les canaux selon les préférences de l'utilisateur
     */
    protected function filterChannelsByPreferences(object $notifiable, array $channels): array
    {
        // Vérifier si le notifiable a des préférences
        if (!method_exists($notifiable, 'notificationPreference')) {
            return $channels;
        }

        $preferences = $notifiable->notificationPreference;

        // Si pas de préférences, garder les canaux demandés
        if (!$preferences) {
            return $channels;
        }

        // Filtrer les canaux selon les préférences
        return array_filter($channels, function ($channel) use ($preferences) {
            return $preferences->isChannelEnabled($this->normalizeChannel($channel));
        });
    }

    /**
     * Normaliser le nom du canal
     */
    protected function normalizeChannel(string $channel): string
    {
        // Mapper les classes de canaux vers leurs noms
        $mapping = [
            \App\Notifications\Channels\TelegramChannel::class => 'telegram',
            \App\Notifications\Channels\WhatsAppChannel::class => 'whatsapp',
            \App\Notifications\Channels\SmsChannel::class => 'sms',
        ];

        return $mapping[$channel] ?? $channel;
    }

    /**
     * Forcer l'utilisation de canaux spécifiques
     */
    public function forceChannels(array $channels): self
    {
        $this->forcedChannels = $channels;
        return $this;
    }

    /**
     * Définir qui a déclenché cette notification
     */
    public function setHandledBy(?int $userId): self
    {
        $this->handledBy = $userId;
        return $this;
    }

    /**
     * Obtenir les données de la notification pour la traçabilité
     *
     * À surcharger dans les classes concrètes.
     */
    abstract public function getNotificationData(): array;

    /**
     * Obtenir le message texte de la notification
     *
     * Utilisé pour la traçabilité dans la table communications.
     */
    public function getMessageContent(): string
    {
        $data = $this->getNotificationData();
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Créer un message email par défaut
     *
     * Les classes concrètes peuvent surcharger cette méthode.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting(__('Bonjour !'))
            ->line(__('Cette notification a été générée automatiquement.'))
            ->salutation(__('Cordialement'));
    }

    /**
     * Représentation pour stockage en base de données
     */
    public function toArray(object $notifiable): array
    {
        return array_merge(
            [
                'type' => static::class,
                'subject' => $this->subject,
            ],
            $this->getNotificationData()
        );
    }

    /**
     * Obtenir l'identifiant unique de cette notification
     */
    public function getNotificationId(): string
    {
        return $this->id ?? uniqid('notif_');
    }

    /**
     * Déterminer si la notification doit être envoyée
     *
     * Peut être surchargé pour ajouter des conditions.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        return true;
    }
}
