<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

/**
 * Notification de confirmation de commande
 *
 * Envoyée après validation d'une commande.
 *
 * Canaux : email + optionnel Telegram (selon préférences)
 * Queues : emails pour mail, telegram pour Telegram
 */
class OrderConfirmationNotification extends BaseNotification
{
    /**
     * Numéro de commande
     */
    public string $orderNumber;

    /**
     * Date de la commande
     */
    public string $orderDate;

    /**
     * Montant total
     */
    public float $totalAmount;

    /**
     * Articles commandés
     */
    public array|Collection $items;

    /**
     * Lien de suivi
     */
    public ?string $trackingLink;

    /**
     * Nom du client
     */
    public string $customerName;

    /**
     * Créer une nouvelle instance de notification
     */
    public function __construct(
        string $orderNumber,
        string $orderDate,
        float $totalAmount,
        array|Collection $items,
        ?string $trackingLink,
        string $customerName
    ) {
        $this->orderNumber = $orderNumber;
        $this->orderDate = $orderDate;
        $this->totalAmount = $totalAmount;
        $this->items = $items instanceof Collection ? $items->toArray() : $items;
        $this->trackingLink = $trackingLink;
        $this->customerName = $customerName;
        $this->subject = __('Confirmation de votre commande #:number', ['number' => $orderNumber]);
    }

    /**
     * Définir les canaux pour cette notification
     */
    protected function getRequestedChannels(): array
    {
        return [
            'mail',
            \App\Notifications\Channels\TelegramChannel::class,
        ];
    }

    /**
     * Obtenir les données de la notification
     */
    public function getNotificationData(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'order_date' => $this->orderDate,
            'total_amount' => $this->totalAmount,
            'items_count' => count($this->items),
            'tracking_link' => $this->trackingLink,
            'customer_name' => $this->customerName,
        ];
    }

    /**
     * Construire le message email
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->markdown('emails.order-confirmation', [
                'orderNumber' => $this->orderNumber,
                'orderDate' => $this->orderDate,
                'totalAmount' => $this->totalAmount,
                'items' => $this->items,
                'trackingLink' => $this->trackingLink,
                'customerName' => $this->customerName,
            ]);
    }

    /**
     * Message Telegram
     */
    public function toTelegram(object $notifiable): array
    {
        $message = "🛒 *Confirmation de commande*\n\n";
        $message .= "Commande : #{$this->orderNumber}\n";
        $message .= "Date : {$this->orderDate}\n";
        $message .= "Total : " . number_format($this->totalAmount, 2, ',', ' ') . " €\n\n";

        if ($this->trackingLink) {
            $message .= "📦 [Suivre ma commande]({$this->trackingLink})";
        }

        return [
            'chat_id' => $notifiable->notificationPreference?->telegram_chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];
    }
}
