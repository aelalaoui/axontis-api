<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The invitation token.
     */
    protected string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'user.setup-password',
            now()->addDays(7),
            ['token' => $this->token, 'email' => $notifiable->email]
        );

        return (new MailMessage)
            ->subject('Invitation à rejoindre Axontis')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Vous avez été invité(e) à rejoindre la plateforme Axontis.')
            ->line('Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous et définir votre mot de passe.')
            ->action('Configurer mon mot de passe', $url)
            ->line('Ce lien expirera dans 7 jours.')
            ->line('Si vous n\'avez pas demandé cette invitation, vous pouvez ignorer cet email.')
            ->salutation('L\'équipe Axontis');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
        ];
    }
}

