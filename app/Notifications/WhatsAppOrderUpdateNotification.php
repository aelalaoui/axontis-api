<?php

namespace App\Notifications;

/**
 * Notification de mise à jour de commande via WhatsApp
 *
 * Envoyée pour informer le client de l'évolution de sa commande.
 *
 * Canaux : WhatsApp uniquement
 * Queue : whatsapp
 */
class WhatsAppOrderUpdateNotification extends BaseNotification
{
    /**
     * Numéro de commande
     */
    public string $orderNumber;

    /**
     * Statut de la commande
     */
    public string $status;

    /**
     * URL de suivi
     */
    public ?string $trackingUrl;

    /**
     * Mapping des statuts vers emojis
     */
    protected array $statusEmojis = [
        'pending' => '⏳',
        'confirmed' => '✅',
        'processing' => '🔧',
        'shipped' => '📦',
        'in_delivery' => '🚚',
        'delivered' => '🎉',
        'cancelled' => '❌',
    ];

    /**
     * Créer une nouvelle instance de notification
     */
    public function __construct(
        string $orderNumber,
        string $status,
        ?string $trackingUrl = null
    ) {
        $this->orderNumber = $orderNumber;
        $this->status = $status;
        $this->trackingUrl = $trackingUrl;
        $this->subject = __('Commande #:number - :status', [
            'number' => $orderNumber,
            'status' => $status
        ]);
    }

    /**
     * Définir les canaux pour cette notification
     */
    protected function getRequestedChannels(): array
    {
        return [
            \App\Notifications\Channels\WhatsAppChannel::class,
        ];
    }

    /**
     * Obtenir les données de la notification
     */
    public function getNotificationData(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'status' => $this->status,
            'tracking_url' => $this->trackingUrl,
        ];
    }

    /**
     * Message WhatsApp
     */
    public function toWhatsApp(object $notifiable): array
    {
        $emoji = $this->statusEmojis[$this->status] ?? '📋';
        $appName = config('app.name');

        $message = "{$emoji} *{$appName}*\n\n";
        $message .= __("Mise à jour de votre commande") . "\n\n";
        $message .= __("Commande : #:number", ['number' => $this->orderNumber]) . "\n";
        $message .= __("Statut : :status", ['status' => $this->getStatusLabel()]) . "\n";

        if ($this->trackingUrl) {
            $message .= "\n" . __("📍 Suivre : :url", ['url' => $this->trackingUrl]);
        }

        $message .= "\n\n" . __("Merci pour votre confiance !");

        return [
            'to' => $notifiable->phone ?? $notifiable->notificationPreference?->whatsapp_number,
            'message' => $message,
            'type' => 'text',
        ];
    }

    /**
     * Obtenir le label du statut en français
     */
    protected function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => __('En attente'),
            'confirmed' => __('Confirmée'),
            'processing' => __('En préparation'),
            'shipped' => __('Expédiée'),
            'in_delivery' => __('En livraison'),
            'delivered' => __('Livrée'),
            'cancelled' => __('Annulée'),
            default => $this->status,
        };
    }
}
