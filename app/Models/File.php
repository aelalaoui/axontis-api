<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'fileable_type',
        'fileable_id',
        'type',
        'title',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = [
        'is_image',
        'is_document',
        'is_video',
        'formatted_size',
        'url',
        'download_url',
    ];

    /**
     * Get the storage disk to use
     */
    protected function getStorageDisk(): string
    {
        return config('filesystems.default') === 'r2' ? 'r2' : 'public';
    }

    // Relationships
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }

    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    public function scopeAudios($query)
    {
        return $query->where('mime_type', 'like', 'audio/%');
    }

    // Accessors
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getIsDocumentAttribute(): bool
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        return in_array($this->mime_type, $documentMimes);
    }

    public function getIsVideoAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    public function getIsAudioAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'audio/');
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Taille inconnue';
        }

        $bytes = $this->file_size;
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getUrlAttribute(): string
    {
        $disk = $this->getStorageDisk();

        if ($disk === 'r2') {
            // For R2, generate a public URL if configured
            $baseUrl = config('filesystems.disks.r2.url');
            return $baseUrl ? $baseUrl . '/' . $this->file_path : Storage::disk($disk)->url($this->file_path);
        }

        return Storage::disk($disk)->url($this->file_path);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('crm.files.download', $this->uuid);
    }

    public function getViewUrlAttribute(): string
    {
        return route('crm.files.view', $this->uuid);
    }

    public function getThumbnailAttribute(): ?string
    {
        if (!$this->is_image) {
            return null;
        }

        // For images, return the same URL as thumbnail
        // In a real implementation, you might want to generate thumbnails
        return $this->url;
    }

    // Methods
    public function exists(): bool
    {
        return Storage::disk($this->getStorageDisk())->exists($this->file_path);
    }

    public function delete(): bool
    {
        // Delete the physical file
        if ($this->exists()) {
            Storage::disk($this->getStorageDisk())->delete($this->file_path);
        }

        // Delete the database record
        return parent::delete();
    }

    public function getContents(): string
    {
        return Storage::disk($this->getStorageDisk())->get($this->file_path);
    }

    /**
     * Generate a temporary URL for private files (useful for R2)
     */
    public function getTemporaryUrl(int $minutes = 60): string
    {
        $disk = $this->getStorageDisk();

        if ($disk === 'r2') {
            return Storage::disk($disk)->temporaryUrl($this->file_path, now()->addMinutes($minutes));
        }

        return $this->url;
    }

    /**
     * Get file extension
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get file icon based on type
     */
    public function getIconAttribute(): string
    {
        if ($this->is_image) {
            return 'fas fa-image';
        } elseif ($this->is_video) {
            return 'fas fa-video';
        } elseif ($this->is_audio) {
            return 'fas fa-music';
        } elseif (str_contains($this->mime_type, 'pdf')) {
            return 'fas fa-file-pdf';
        } elseif (str_contains($this->mime_type, 'word') || str_contains($this->mime_type, 'document')) {
            return 'fas fa-file-word';
        } elseif (str_contains($this->mime_type, 'excel') || str_contains($this->mime_type, 'sheet')) {
            return 'fas fa-file-excel';
        } elseif (str_contains($this->mime_type, 'powerpoint') || str_contains($this->mime_type, 'presentation')) {
            return 'fas fa-file-powerpoint';
        } else {
            return 'fas fa-file';
        }
    }
}
