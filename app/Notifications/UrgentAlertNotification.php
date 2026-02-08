<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

/**
 * Notification d'alerte urgente
 *
 * Envoyée aux administrateurs pour les alertes critiques.
 *
 * Canaux : email + Slack + Telegram (multi-canaux simultanés)
 * Queues : emails pour mail, telegram pour Slack et Telegram
 */
class UrgentAlertNotification extends BaseNotification
{
    /**
     * Titre de l'alerte
     */
    public string $alertTitle;

    /**
     * Message de l'alerte
     */
    public string $alertMessage;

    /**
     * Niveau de sévérité (info, warning, critical)
     */
    public string $severity;

    /**
     * URL d'action
     */
    public ?string $actionUrl;

    /**
     * Couleurs selon la sévérité
     */
    protected array $severityColors = [
        'info' => '#3498db',
        'warning' => '#f39c12',
        'critical' => '#e74c3c',
    ];

    /**
     * Créer une nouvelle instance de notification
     */
    public function __construct(
        string $alertTitle,
        string $alertMessage,
        string $severity = 'warning',
        ?string $actionUrl = null
    ) {
        $this->alertTitle = $alertTitle;
        $this->alertMessage = $alertMessage;
        $this->severity = $severity;
        $this->actionUrl = $actionUrl;
        $this->subject = __('[:severity] :title', [
            'severity' => strtoupper($severity),
            'title' => $alertTitle
        ]);

        // Les alertes urgentes sont prioritaires
        $this->tries = 5;
        $this->backoff = [5, 10, 30, 60, 120];
    }

    /**
     * Définir les canaux pour cette notification
     *
     * Les alertes sont envoyées sur tous les canaux sans vérification des préférences
     */
    protected function getRequestedChannels(): array
    {
        return [
            'mail',
            'slack',
            \App\Notifications\Channels\TelegramChannel::class,
        ];
    }

    /**
     * Forcer les canaux pour les alertes (ignorer les préférences)
     */
    public function via(object $notifiable): array
    {
        // Les alertes urgentes ignorent les préférences utilisateur
        return $this->getRequestedChannels();
    }

    /**
     * Obtenir les données de la notification
     */
    public function getNotificationData(): array
    {
        return [
            'alert_title' => $this->alertTitle,
            'alert_message' => $this->alertMessage,
            'severity' => $this->severity,
            'action_url' => $this->actionUrl,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Construire le message email
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->markdown('emails.urgent-alert', [
                'alertTitle' => $this->alertTitle,
                'alertMessage' => $this->alertMessage,
                'severity' => $this->severity,
                'actionUrl' => $this->actionUrl,
                'timestamp' => now()->format('d/m/Y H:i:s'),
            ]);

        // Marquer comme important pour les alertes critiques
        if ($this->severity === 'critical') {
            $mail->priority(1);
        }

        return $mail;
    }

    /**
     * Message Slack
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $emoji = match($this->severity) {
            'info' => ':information_source:',
            'warning' => ':warning:',
            'critical' => ':rotating_light:',
            default => ':bell:',
        };

        $message = (new SlackMessage)
            ->from(config('app.name'), $emoji)
            ->content($emoji . ' *' . strtoupper($this->severity) . '* : ' . $this->alertTitle)
            ->attachment(function ($attachment) {
                $attachment
                    ->title($this->alertTitle, $this->actionUrl)
                    ->content($this->alertMessage)
                    ->color($this->severityColors[$this->severity] ?? '#95a5a6')
                    ->footer('Alerte système')
                    ->timestamp(now());
            });

        return $message;
    }

    /**
     * Message Telegram
     */
    public function toTelegram(object $notifiable): array
    {
        $emoji = match($this->severity) {
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'critical' => '🚨',
            default => '🔔',
        };

        $message = "{$emoji} *ALERTE {$this->severity}*\n\n";
        $message .= "*{$this->alertTitle}*\n\n";
        $message .= $this->alertMessage . "\n\n";
        $message .= "🕐 " . now()->format('d/m/Y H:i:s');

        if ($this->actionUrl) {
            $message .= "\n\n[👉 Voir les détails]({$this->actionUrl})";
        }

        // Pour les alertes, utiliser le chat ID de l'équipe technique
        $chatId = $notifiable->notificationPreference?->telegram_chat_id
            ?? config('services.notifications.tech_team.telegram_chat_id');

        return [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];
    }
}
