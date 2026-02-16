<?php

namespace App\Notifications;

/**
 * Notification de code de vérification SMS
 *
 * Envoyée pour la double authentification ou vérification de numéro.
 *
 * Canaux : SMS uniquement
 * Queue : sms
 */
class SmsVerificationNotification extends BaseNotification
{
    /**
     * Code de vérification
     */
    public string $code;

    /**
     * Durée de validité en minutes
     */
    public int $expiresInMinutes;

    /**
     * Nom de l'utilisateur
     */
    public string $userName;

    /**
     * Créer une nouvelle instance de notification
     */
    public function __construct(
        string $code,
        int $expiresInMinutes = 10,
        ?string $userName = null
    ) {
        $this->code = $code;
        $this->expiresInMinutes = $expiresInMinutes;
        $this->userName = $userName ?? '';
        $this->subject = __('Code de vérification');
    }

    /**
     * Définir les canaux pour cette notification
     */
    protected function getRequestedChannels(): array
    {
        return [
            \App\Notifications\Channels\SmsChannel::class,
        ];
    }

    /**
     * Ne pas filtrer par préférences - les SMS de vérification sont obligatoires
     */
    public function via(object $notifiable): array
    {
        return $this->getRequestedChannels();
    }

    /**
     * Obtenir les données de la notification
     */
    public function getNotificationData(): array
    {
        return [
            'code' => $this->code,
            'expires_in_minutes' => $this->expiresInMinutes,
            'user_name' => $this->userName,
        ];
    }

    /**
     * Message SMS
     */
    public function toSms(object $notifiable): array
    {
        $appName = config('app.name');

        $message = __(':app - Votre code de vérification : :code. Valide :minutes min.', [
            'app' => $appName,
            'code' => $this->code,
            'minutes' => $this->expiresInMinutes,
        ]);

        return [
            'to' => $notifiable->phone ?? $notifiable->notificationPreference?->phone_number,
            'message' => $message,
        ];
    }

    /**
     * Obtenir le contenu du message pour la traçabilité
     * Note: On masque partiellement le code pour la sécurité
     */
    public function getMessageContent(): string
    {
        return __('Code de vérification envoyé. Validité : :minutes minutes.', [
            'minutes' => $this->expiresInMinutes,
        ]);
    }
}
