<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Modèle NotificationPreference
 *
 * Gère les préférences de notification pour les clients et utilisateurs.
 * Permet de définir quels canaux activer pour chaque entité.
 *
 * @property int $id
 * @property string $uuid
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property bool $notify_email
 * @property bool $notify_sms
 * @property bool $notify_whatsapp
 * @property bool $notify_telegram
 * @property bool $notify_slack
 * @property string|null $phone_number Numéro pour SMS
 * @property string|null $whatsapp_number Numéro WhatsApp
 * @property string|null $telegram_chat_id Chat ID Telegram
 * @property string|null $slack_webhook_url Webhook Slack personnalisé
 * @property array|null $notification_types Types de notifications activés
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class NotificationPreference extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'notify_email',
        'notify_sms',
        'notify_whatsapp',
        'notify_telegram',
        'notify_slack',
        'phone_number',
        'whatsapp_number',
        'telegram_chat_id',
        'slack_webhook_url',
        'notification_types',
    ];

    protected $casts = [
        'notify_email' => 'boolean',
        'notify_sms' => 'boolean',
        'notify_whatsapp' => 'boolean',
        'notify_telegram' => 'boolean',
        'notify_slack' => 'boolean',
        'notification_types' => 'array',
    ];

    protected $attributes = [
        'notify_email' => true,
        'notify_sms' => false,
        'notify_whatsapp' => false,
        'notify_telegram' => false,
        'notify_slack' => false,
    ];

    // ==========================================
    // Relations
    // ==========================================

    /**
     * Relation polymorphique vers le client ou user
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Préférences pour les clients
     */
    public function scopeForClients(Builder $query): Builder
    {
        return $query->where('notifiable_type', Client::class);
    }

    /**
     * Préférences pour les users
     */
    public function scopeForUsers(Builder $query): Builder
    {
        return $query->where('notifiable_type', User::class);
    }

    /**
     * Préférences avec email activé
     */
    public function scopeWithEmail(Builder $query): Builder
    {
        return $query->where('notify_email', true);
    }

    /**
     * Préférences avec SMS activé
     */
    public function scopeWithSms(Builder $query): Builder
    {
        return $query->where('notify_sms', true);
    }

    /**
     * Préférences avec WhatsApp activé
     */
    public function scopeWithWhatsapp(Builder $query): Builder
    {
        return $query->where('notify_whatsapp', true);
    }

    /**
     * Préférences avec Telegram activé
     */
    public function scopeWithTelegram(Builder $query): Builder
    {
        return $query->where('notify_telegram', true);
    }

    // ==========================================
    // Méthodes
    // ==========================================

    /**
     * Vérifier si un canal est activé
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match($channel) {
            'email', 'mail' => $this->notify_email,
            'sms' => $this->notify_sms,
            'whatsapp' => $this->notify_whatsapp,
            'telegram' => $this->notify_telegram,
            'slack' => $this->notify_slack,
            default => false,
        };
    }

    /**
     * Vérifier si un type de notification est activé
     */
    public function isNotificationTypeEnabled(string $type): bool
    {
        // Si aucun type spécifié, tout est activé par défaut
        if (empty($this->notification_types)) {
            return true;
        }

        return in_array($type, $this->notification_types);
    }

    /**
     * Obtenir les canaux activés
     */
    public function getEnabledChannels(): array
    {
        $channels = [];

        if ($this->notify_email) {
            $channels[] = 'mail';
        }

        if ($this->notify_sms && $this->phone_number) {
            $channels[] = 'sms';
        }

        if ($this->notify_whatsapp && $this->whatsapp_number) {
            $channels[] = 'whatsapp';
        }

        if ($this->notify_telegram && $this->telegram_chat_id) {
            $channels[] = 'telegram';
        }

        if ($this->notify_slack && $this->slack_webhook_url) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Activer un canal
     */
    public function enableChannel(string $channel): bool
    {
        $field = $this->getChannelField($channel);

        if ($field) {
            $this->$field = true;
            return $this->save();
        }

        return false;
    }

    /**
     * Désactiver un canal
     */
    public function disableChannel(string $channel): bool
    {
        $field = $this->getChannelField($channel);

        if ($field) {
            $this->$field = false;
            return $this->save();
        }

        return false;
    }

    /**
     * Obtenir le champ correspondant à un canal
     */
    protected function getChannelField(string $channel): ?string
    {
        return match($channel) {
            'email', 'mail' => 'notify_email',
            'sms' => 'notify_sms',
            'whatsapp' => 'notify_whatsapp',
            'telegram' => 'notify_telegram',
            'slack' => 'notify_slack',
            default => null,
        };
    }

    /**
     * Mettre à jour le numéro de téléphone
     */
    public function updatePhoneNumber(string $phoneNumber): bool
    {
        $this->phone_number = $phoneNumber;
        return $this->save();
    }

    /**
     * Mettre à jour le numéro WhatsApp
     */
    public function updateWhatsappNumber(string $whatsappNumber): bool
    {
        $this->whatsapp_number = $whatsappNumber;
        return $this->save();
    }

    /**
     * Mettre à jour le chat ID Telegram
     */
    public function updateTelegramChatId(string $chatId): bool
    {
        $this->telegram_chat_id = $chatId;
        return $this->save();
    }

    /**
     * Créer ou récupérer les préférences pour une entité
     */
    public static function getOrCreateFor($notifiable): self
    {
        return self::firstOrCreate([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
        ]);
    }
}
