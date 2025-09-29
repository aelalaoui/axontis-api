<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Communication extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'communicable_type',
        'communicable_id',
        'channel',
        'direction',
        'subject',
        'message',
        'handled_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'channel' => 'string',
        'direction' => 'string',
    ];

    // Relationships
    public function communicable(): MorphTo
    {
        return $this->morphTo();
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    // Scopes
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeEmail($query)
    {
        return $query->where('channel', 'email');
    }

    public function scopePhone($query)
    {
        return $query->where('channel', 'phone');
    }

    public function scopeSms($query)
    {
        return $query->where('channel', 'sms');
    }

    public function scopeWhatsapp($query)
    {
        return $query->where('channel', 'whatsapp');
    }

    public function scopeHandledBy($query, $userId)
    {
        return $query->where('handled_by', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sent_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('sent_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sent_at', now()->month)
                    ->whereYear('sent_at', now()->year);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getIsInboundAttribute(): bool
    {
        return $this->direction === 'inbound';
    }

    public function getIsOutboundAttribute(): bool
    {
        return $this->direction === 'outbound';
    }

    public function getIsEmailAttribute(): bool
    {
        return $this->channel === 'email';
    }

    public function getIsPhoneAttribute(): bool
    {
        return $this->channel === 'phone';
    }

    public function getIsSmsAttribute(): bool
    {
        return $this->channel === 'sms';
    }

    public function getIsWhatsappAttribute(): bool
    {
        return $this->channel === 'whatsapp';
    }

    public function getChannelIconAttribute(): string
    {
        return match($this->channel) {
            'email' => '📧',
            'phone' => '📞',
            'sms' => '💬',
            'whatsapp' => '📱',
            default => '📝'
        };
    }

    public function getDirectionIconAttribute(): string
    {
        return $this->direction === 'inbound' ? '⬇️' : '⬆️';
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->sent_at->format('d/m/Y H:i');
    }

    // Methods
    public function markAsHandled(User $user): bool
    {
        $this->handled_by = $user->id;
        return $this->save();
    }

    public function hasAttachments(): bool
    {
        return $this->files()->exists();
    }

    public function getAttachmentsCount(): int
    {
        return $this->files()->count();
    }
}