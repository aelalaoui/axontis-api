<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification de confirmation du choix de mode d'installation.
 *
 * Envoyée après que le client a choisi :
 *  - installation par un technicien Axontis (+ paiement des frais)
 *  - auto-installation (envoi postal du matériel)
 *
 * Canal : email uniquement
 * Queue : emails
 */
class InstallationChoiceNotification extends BaseNotification
{
    public string $clientName;

    /** 'technician' | 'self' */
    public string $installationMode;

    /** Adresse de livraison (mode self uniquement) */
    public ?string $deliveryAddress;

    /** Montant des frais d'installation (mode technicien) */
    public ?float $installationFeeAmount;

    /** Devise */
    public string $currency;

    /** Date d'intervention planifiée (mode technicien uniquement, après scheduling) */
    public ?string $scheduledDate;

    /** Heure d'intervention planifiée */
    public ?string $scheduledTime;

    public function __construct(
        string  $clientName,
        string  $installationMode,
        ?string $deliveryAddress       = null,
        ?float  $installationFeeAmount = null,
        string  $currency              = 'MAD',
        ?string $scheduledDate         = null,
        ?string $scheduledTime         = null
    ) {
        $this->clientName             = $clientName;
        $this->installationMode       = $installationMode;
        $this->deliveryAddress        = $deliveryAddress;
        $this->installationFeeAmount  = $installationFeeAmount;
        $this->currency               = $currency;
        $this->scheduledDate          = $scheduledDate;
        $this->scheduledTime          = $scheduledTime;

        $this->subject = $installationMode === 'technician'
            ? __('Confirmation – Installation par un technicien Axontis')
            : __('Confirmation – Votre matériel sera livré chez vous');
    }

    protected function getRequestedChannels(): array
    {
        return ['mail'];
    }

    public function getNotificationData(): array
    {
        return [
            'client_name'             => $this->clientName,
            'installation_mode'       => $this->installationMode,
            'delivery_address'        => $this->deliveryAddress,
            'installation_fee_amount' => $this->installationFeeAmount,
            'currency'                => $this->currency,
            'scheduled_date'          => $this->scheduledDate,
            'scheduled_time'          => $this->scheduledTime,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->markdown('emails.installation-choice', [
                'clientName'            => $this->clientName,
                'installationMode'      => $this->installationMode,
                'deliveryAddress'       => $this->deliveryAddress,
                'installationFeeAmount' => $this->installationFeeAmount,
                'currency'              => $this->currency,
                'scheduledDate'         => $this->scheduledDate,
                'scheduledTime'         => $this->scheduledTime,
                'companyName'           => config('app.name'),
                'dashboardUrl'          => route('client.home'),
            ]);
    }
}

