<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification d'envoi de facture
 *
 * Envoyée lors de l'émission d'une facture client.
 *
 * Canaux : email + optionnel SMS
 * Queues : emails pour mail, sms pour SMS
 */
class InvoiceNotification extends BaseNotification
{
    /**
     * Numéro de facture
     */
    public string $invoiceNumber;

    /**
     * Date de la facture
     */
    public string $invoiceDate;

    /**
     * Montant dû
     */
    public float $amountDue;

    /**
     * Date d'échéance
     */
    public string $dueDate;

    /**
     * URL du PDF de la facture
     */
    public ?string $pdfUrl;

    /**
     * Nom du client
     */
    public string $clientName;

    /**
     * Créer une nouvelle instance de notification
     */
    public function __construct(
        string $invoiceNumber,
        string $invoiceDate,
        float $amountDue,
        string $dueDate,
        ?string $pdfUrl,
        string $clientName
    ) {
        $this->invoiceNumber = $invoiceNumber;
        $this->invoiceDate = $invoiceDate;
        $this->amountDue = $amountDue;
        $this->dueDate = $dueDate;
        $this->pdfUrl = $pdfUrl;
        $this->clientName = $clientName;
        $this->subject = __('Facture :number - :amount€', [
            'number' => $invoiceNumber,
            'amount' => number_format($amountDue, 2, ',', ' ')
        ]);
    }

    /**
     * Définir les canaux pour cette notification
     */
    protected function getRequestedChannels(): array
    {
        return [
            'mail',
            \App\Notifications\Channels\SmsChannel::class,
        ];
    }

    /**
     * Obtenir les données de la notification
     */
    public function getNotificationData(): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'amount_due' => $this->amountDue,
            'due_date' => $this->dueDate,
            'pdf_url' => $this->pdfUrl,
            'client_name' => $this->clientName,
        ];
    }

    /**
     * Construire le message email
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->markdown('emails.invoice', [
                'invoiceNumber' => $this->invoiceNumber,
                'invoiceDate' => $this->invoiceDate,
                'amountDue' => $this->amountDue,
                'dueDate' => $this->dueDate,
                'pdfUrl' => $this->pdfUrl,
                'clientName' => $this->clientName,
            ]);

        return $mail;
    }

    /**
     * Message SMS
     */
    public function toSms(object $notifiable): array
    {
        $message = __("Facture :number - :amount€ à régler avant le :date. Consultez votre espace client.", [
            'number' => $this->invoiceNumber,
            'amount' => number_format($this->amountDue, 2, ',', ' '),
            'date' => $this->dueDate,
        ]);

        return [
            'to' => $notifiable->phone ?? $notifiable->notificationPreference?->phone_number,
            'message' => $message,
        ];
    }
}
