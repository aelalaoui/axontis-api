<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification de bienvenue
 *
 * Envoyée aux nouveaux utilisateurs/clients lors de leur inscription.
 *
 * Canaux : email uniquement
 * Queue : emails
 */
class WelcomeNotification extends BaseNotification
{
    /**
     * Nom de l'utilisateur
     */
    public string $userName;

    /**
     * Lien d'activation du compte
     */
    public string $activationLink;

    /**
     * Nom de l'entreprise
     */
    public string $companyName;

    /**
     * Créer une nouvelle instance de notification
     */
    public function __construct(
        string $userName,
        string $activationLink,
        ?string $companyName = null
    ) {
        $this->userName = $userName;
        $this->activationLink = $activationLink;
        $this->companyName = $companyName ?? config('app.name');
        $this->subject = __('Bienvenue sur :company !', ['company' => $this->companyName]);
    }

    /**
     * Définir les canaux pour cette notification
     */
    protected function getRequestedChannels(): array
    {
        return ['mail'];
    }

    /**
     * Obtenir les données de la notification
     */
    public function getNotificationData(): array
    {
        return [
            'user_name' => $this->userName,
            'activation_link' => $this->activationLink,
            'company_name' => $this->companyName,
        ];
    }

    /**
     * Construire le message email
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->markdown('emails.welcome', [
                'userName' => $this->userName,
                'activationLink' => $this->activationLink,
                'companyName' => $this->companyName,
            ]);
    }
}
