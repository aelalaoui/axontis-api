<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Modèle Communication
 *
 * Représente un enregistrement de communication dans le CRM.
 * Utilisé pour tracer toutes les notifications et communications
 * envoyées aux clients et utilisateurs.
 *
 * @property int $id
 * @property string $uuid
 * @property string $communicable_type
 * @property int $communicable_id
 * @property string $channel (email, phone, sms, whatsapp, other)
 * @property string $direction (inbound, outbound)
 * @property string|null $subject
 * @property string|null $message
 * @property int|null $handled_by
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property string|null $status (pending, sent, delivered, failed)
 * @property string|null $notification_type Classe de notification utilisée
 * @property string|null $provider Provider utilisé (resend, mailgun, brevo, twilio)
 * @property array|null $metadata Données supplémentaires (erreurs, tracking, etc.)
 * @property int $retry_count Nombre de tentatives
 * @property \Illuminate\Support\Carbon|null $failed_at Date d'échec définitif
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Communication extends Model
{
    use HasFactory, HasUuid;

    /**
     * Constantes pour les canaux de communication
     */
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_OTHER = 'other';

    /**
     * Constantes pour les directions
     */
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    /**
     * Constantes pour les statuts
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    /**
     * Mapping des canaux Laravel vers les valeurs enum de la table
     */
    public const CHANNEL_MAPPING = [
        'mail' => self::CHANNEL_EMAIL,
        'database' => self::CHANNEL_OTHER,
        'broadcast' => self::CHANNEL_OTHER,
        'slack' => self::CHANNEL_OTHER,
        'telegram' => self::CHANNEL_OTHER,
        'vonage' => self::CHANNEL_SMS,
        'nexmo' => self::CHANNEL_SMS,
        'twilio' => self::CHANNEL_SMS,
        'sms' => self::CHANNEL_SMS,
        'whatsapp' => self::CHANNEL_WHATSAPP,
    ];

    protected $fillable = [
        'communicable_type',
        'communicable_id',
        'channel',
        'direction',
        'subject',
        'message',
        'handled_by',
        'sent_at',
        'status',
        'notification_type',
        'provider',
        'metadata',
        'retry_count',
        'failed_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'channel' => 'string',
        'direction' => 'string',
        'metadata' => 'array',
        'retry_count' => 'integer',
    ];

    protected $attributes = [
        'direction' => self::DIRECTION_OUTBOUND,
        'status' => self::STATUS_PENDING,
        'retry_count' => 0,
    ];

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Relation polymorphique vers le client ou user concerné
     */
    public function communicable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Utilisateur ayant géré cette communication
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Fichiers attachés à cette communication
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    // ==========================================
    // Scopes - Direction
    // ==========================================

    /**
     * Filtrer les communications entrantes
     */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    /**
     * Filtrer les communications sortantes
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    // ==========================================
    // Scopes - Canal
    // ==========================================

    /**
     * Filtrer par canal spécifique
     */
    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Filtrer les emails
     */
    public function scopeEmail(Builder $query): Builder
    {
        return $query->where('channel', self::CHANNEL_EMAIL);
    }

    /**
     * Filtrer les appels téléphoniques
     */
    public function scopePhone(Builder $query): Builder
    {
        return $query->where('channel', self::CHANNEL_PHONE);
    }

    /**
     * Filtrer les SMS
     */
    public function scopeSms(Builder $query): Builder
    {
        return $query->where('channel', self::CHANNEL_SMS);
    }

    /**
     * Filtrer les WhatsApp
     */
    public function scopeWhatsapp(Builder $query): Builder
    {
        return $query->where('channel', self::CHANNEL_WHATSAPP);
    }

    /**
     * Filtrer les autres canaux (Telegram, Slack, etc.)
     */
    public function scopeOther(Builder $query): Builder
    {
        return $query->where('channel', self::CHANNEL_OTHER);
    }

    // ==========================================
    // Scopes - Entités
    // ==========================================

    /**
     * Filtrer les communications pour un client spécifique
     */
    public function scopeForClient(Builder $query, int|string $clientId = null): Builder
    {
        $query->where('communicable_type', Client::class);

        if ($clientId !== null) {
            $query->where('communicable_id', $clientId);
        }

        return $query;
    }

    /**
     * Filtrer les communications pour un user spécifique
     */
    public function scopeForUser(Builder $query, int|string $userId = null): Builder
    {
        $query->where('communicable_type', User::class);

        if ($userId !== null) {
            $query->where('communicable_id', $userId);
        }

        return $query;
    }

    // ==========================================
    // Scopes - Gestionnaire
    // ==========================================

    /**
     * Filtrer par gestionnaire
     */
    public function scopeHandledBy(Builder $query, int $userId): Builder
    {
        return $query->where('handled_by', $userId);
    }

    /**
     * Filtrer les communications non gérées (auto-envoyées)
     */
    public function scopeUnhandled(Builder $query): Builder
    {
        return $query->whereNull('handled_by');
    }

    // ==========================================
    // Scopes - Statut
    // ==========================================

    /**
     * Filtrer par statut
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Filtrer les communications réussies
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    /**
     * Filtrer les communications échouées
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Filtrer les communications en attente
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // ==========================================
    // Scopes - Dates
    // ==========================================

    /**
     * Communications d'aujourd'hui
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('sent_at', today());
    }

    /**
     * Communications de cette semaine
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('sent_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Communications de ce mois
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('sent_at', now()->month)
                    ->whereYear('sent_at', now()->year);
    }

    /**
     * Communications récentes (X derniers jours)
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    /**
     * Communications dans une plage de dates
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('sent_at', [$startDate, $endDate]);
    }

    // ==========================================
    // Scopes - Provider
    // ==========================================

    /**
     * Filtrer par provider
     */
    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    // ==========================================
    // Accessors
    // ==========================================

    public function getIsInboundAttribute(): bool
    {
        return $this->direction === self::DIRECTION_INBOUND;
    }

    public function getIsOutboundAttribute(): bool
    {
        return $this->direction === self::DIRECTION_OUTBOUND;
    }

    public function getIsEmailAttribute(): bool
    {
        return $this->channel === self::CHANNEL_EMAIL;
    }

    public function getIsPhoneAttribute(): bool
    {
        return $this->channel === self::CHANNEL_PHONE;
    }

    public function getIsSmsAttribute(): bool
    {
        return $this->channel === self::CHANNEL_SMS;
    }

    public function getIsWhatsappAttribute(): bool
    {
        return $this->channel === self::CHANNEL_WHATSAPP;
    }

    public function getIsOtherAttribute(): bool
    {
        return $this->channel === self::CHANNEL_OTHER;
    }

    /**
     * Label du canal pour affichage
     */
    public function getChannelLabelAttribute(): string
    {
        return match($this->channel) {
            self::CHANNEL_EMAIL => __('Email'),
            self::CHANNEL_PHONE => __('Téléphone'),
            self::CHANNEL_SMS => __('SMS'),
            self::CHANNEL_WHATSAPP => __('WhatsApp'),
            self::CHANNEL_OTHER => __('Autre'),
            default => __('Inconnu')
        };
    }

    /**
     * Label de la direction pour affichage
     */
    public function getDirectionLabelAttribute(): string
    {
        return match($this->direction) {
            self::DIRECTION_INBOUND => __('Entrant'),
            self::DIRECTION_OUTBOUND => __('Sortant'),
            default => __('Inconnu')
        };
    }

    /**
     * Label du statut pour affichage
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => __('En attente'),
            self::STATUS_SENT => __('Envoyé'),
            self::STATUS_DELIVERED => __('Délivré'),
            self::STATUS_FAILED => __('Échoué'),
            default => __('Inconnu')
        };
    }

    /**
     * Icône du canal
     */
    public function getChannelIconAttribute(): string
    {
        return match($this->channel) {
            self::CHANNEL_EMAIL => '📧',
            self::CHANNEL_PHONE => '📞',
            self::CHANNEL_SMS => '💬',
            self::CHANNEL_WHATSAPP => '📱',
            self::CHANNEL_OTHER => '📝',
            default => '📝'
        };
    }

    /**
     * Icône de la direction
     */
    public function getDirectionIconAttribute(): string
    {
        return $this->direction === self::DIRECTION_INBOUND ? '⬇️' : '⬆️';
    }

    /**
     * Icône du statut
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '⏳',
            self::STATUS_SENT => '✈️',
            self::STATUS_DELIVERED => '✅',
            self::STATUS_FAILED => '❌',
            default => '❓'
        };
    }

    /**
     * Couleur du badge selon le canal (pour l'interface)
     */
    public function getChannelBadgeColorAttribute(): string
    {
        return match($this->channel) {
            self::CHANNEL_EMAIL => 'blue',
            self::CHANNEL_PHONE => 'purple',
            self::CHANNEL_SMS => 'green',
            self::CHANNEL_WHATSAPP => 'emerald',
            self::CHANNEL_OTHER => 'gray',
            default => 'gray'
        };
    }

    /**
     * Couleur du badge selon le statut
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_SENT => 'blue',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_FAILED => 'red',
            default => 'gray'
        };
    }

    /**
     * Date formatée pour affichage
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->sent_at?->format('d/m/Y H:i') ?? '';
    }

    /**
     * Date relative (il y a X heures/jours)
     */
    public function getRelativeDateAttribute(): string
    {
        return $this->sent_at?->diffForHumans() ?? '';
    }

    // ==========================================
    // Méthodes statiques
    // ==========================================

    /**
     * Mapper un canal Laravel vers la valeur enum de la table
     */
    public static function mapChannel(string $laravelChannel): string
    {
        // Normaliser en minuscules
        $channel = strtolower($laravelChannel);

        // Vérifier si c'est une classe de canal custom
        if (str_contains($channel, 'whatsapp')) {
            return self::CHANNEL_WHATSAPP;
        }

        if (str_contains($channel, 'sms') || str_contains($channel, 'twilio') || str_contains($channel, 'vonage')) {
            return self::CHANNEL_SMS;
        }

        return self::CHANNEL_MAPPING[$channel] ?? self::CHANNEL_OTHER;
    }

    /**
     * Obtenir les canaux disponibles
     */
    public static function getAvailableChannels(): array
    {
        return [
            self::CHANNEL_EMAIL,
            self::CHANNEL_PHONE,
            self::CHANNEL_SMS,
            self::CHANNEL_WHATSAPP,
            self::CHANNEL_OTHER,
        ];
    }

    /**
     * Obtenir les directions disponibles
     */
    public static function getAvailableDirections(): array
    {
        return [
            self::DIRECTION_INBOUND,
            self::DIRECTION_OUTBOUND,
        ];
    }

    /**
     * Obtenir les statuts disponibles
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
        ];
    }

    // ==========================================
    // Méthodes d'instance
    // ==========================================

    /**
     * Marquer comme envoyé
     */
    public function markAsSent(?string $provider = null): bool
    {
        $this->status = self::STATUS_SENT;
        $this->sent_at = now();

        if ($provider) {
            $this->provider = $provider;
        }

        return $this->save();
    }

    /**
     * Marquer comme délivré
     */
    public function markAsDelivered(): bool
    {
        $this->status = self::STATUS_DELIVERED;
        return $this->save();
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(?string $error = null, ?string $provider = null): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->failed_at = now();
        $this->retry_count = ($this->retry_count ?? 0) + 1;

        if ($provider) {
            $this->provider = $provider;
        }

        if ($error) {
            $metadata = $this->metadata ?? [];
            $metadata['errors'] = $metadata['errors'] ?? [];
            $metadata['errors'][] = [
                'message' => $error,
                'timestamp' => now()->toIso8601String(),
                'provider' => $provider,
            ];
            $this->metadata = $metadata;
        }

        return $this->save();
    }

    /**
     * Marquer comme géré par un utilisateur
     */
    public function markAsHandled(User $user): bool
    {
        $this->handled_by = $user->id;
        return $this->save();
    }

    /**
     * Ajouter des métadonnées
     */
    public function addMetadata(array $data): bool
    {
        $this->metadata = array_merge($this->metadata ?? [], $data);
        return $this->save();
    }

    /**
     * Vérifier si des fichiers sont attachés
     */
    public function hasAttachments(): bool
    {
        return $this->files()->exists();
    }

    /**
     * Obtenir le nombre de fichiers attachés
     */
    public function getAttachmentsCount(): int
    {
        return $this->files()->count();
    }

    /**
     * Vérifier si la communication peut être renvoyée
     */
    public function canBeResent(): bool
    {
        return $this->direction === self::DIRECTION_OUTBOUND
            && $this->status === self::STATUS_FAILED
            && $this->retry_count < 5;
    }

    /**
     * Obtenir un résumé court du message
     */
    public function getMessageExcerpt(int $length = 100): string
    {
        if (!$this->message) {
            return '';
        }

        $text = strip_tags($this->message);

        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }
}
