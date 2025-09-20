<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory;

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
        ]);
    }

    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
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
        ];

        return in_array($this->mime_type, $documentMimes);
    }

    public function getIsVideoAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown size';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('files.download', $this->id);
    }

    // Methods
    public function exists(): bool
    {
        return Storage::exists($this->file_path);
    }

    public function delete(): bool
    {
        // Delete the physical file
        if ($this->exists()) {
            Storage::delete($this->file_path);
        }

        // Delete the database record
        return parent::delete();
    }

    public function getContents(): string
    {
        return Storage::get($this->file_path);
    }
}