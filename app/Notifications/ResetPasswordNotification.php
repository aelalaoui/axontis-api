<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPasswordBase
{
    public function toMail($notifiable)
    {
        // Gérer le cas où $notifiable est un stdClass (pour l'extraction du sujet par le listener)
        $email = $notifiable->email;

        if (is_null($email)) {
            throw new \Exception('Cannot send email without an email address for this user id : '. $notifiable->id);
        }

        $name = $notifiable->name ?? 'Utilisateur';

        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ], false));

        $count = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour ' . $name . ',')
            ->line('Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Ce lien de réinitialisation expirera dans ' . $count . ' minutes.')
            ->line('Si vous n\'avez pas demandé de réinitialisation de mot de passe, aucune action n\'est requise.')
            ->salutation('Cordialement, ' . config('app.name'));
    }
}
