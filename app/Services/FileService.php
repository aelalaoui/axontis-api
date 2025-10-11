<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FileService
{
    /**
     * Upload and store a file
     */
    public function uploadFile(UploadedFile $file, $fileable, string $type = 'document', ?string $title = null): File
    {
        try {
            $disk = $this->getStorageDisk();

            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;

            // Define storage path
            $path = $this->getStoragePath($fileable, $type);
            $fullPath = $path . '/' . $filename;

            // Store the file
            $file->storeAs($path, $filename, $disk);

            // Create file record
            return File::create([
                'fileable_type' => get_class($fileable),
                'fileable_id' => $fileable->getKey(),
                'type' => $type,
                'title' => $title ?: $originalName,
                'file_name' => $originalName,
                'file_path' => $fullPath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        } catch (\Exception $e) {
            Log::error('FileService@uploadFile failed', [
                'error' => $e->getMessage(),
                'fileable_type' => get_class($fileable),
                'fileable_id' => $fileable->getKey(),
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles(array $files, $fileable, string $type = 'document'): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $uploadedFiles[] = $this->uploadFile($file, $fileable, $type);
                } catch (\Exception $e) {
                    Log::error('FileService@uploadMultipleFiles - File upload failed', [
                        'file_name' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                    // Continue with other files even if one fails
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * Delete a file
     */
    public function deleteFile(File $file): bool
    {
        try {
            $disk = $this->getStorageDisk();

            // Delete physical file
            if (Storage::disk($disk)->exists($file->file_path)) {
                Storage::disk($disk)->delete($file->file_path);
            }

            // Delete database record
            return $file->delete();
        } catch (\Exception $e) {
            Log::error('FileService@deleteFile failed', [
                'file_id' => $file->getKey(),
                'file_path' => $file->file_path,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete multiple files
     */
    public function deleteMultipleFiles(array $fileIds, $fileable): int
    {
        $deletedCount = 0;
        $files = $fileable->files()->whereIn('uuid', $fileIds)->get();

        foreach ($files as $file) {
            try {
                if ($this->deleteFile($file)) {
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                Log::error('FileService@deleteMultipleFiles - File deletion failed', [
                    'file_id' => $file->getKey(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $deletedCount;
    }

    /**
     * Rename/Update file title
     */
    public function renameFile(File $file, string $newTitle): bool
    {
        try {
            return $file->update(['title' => $newTitle]);
        } catch (\Exception $e) {
            Log::error('FileService@renameFile failed', [
                'file_id' => $file->getKey(),
                'new_title' => $newTitle,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get file URL for viewing/downloading
     */
    public function getFileUrl(File $file, bool $forDownload = false): string
    {
        $disk = $this->getStorageDisk();

        if ($disk === 'r2') {
            // For cloud storage, return temporary URL
            return Storage::disk($disk)->temporaryUrl($file->file_path, now()->addHours(24));
        }

        // For local storage
        return Storage::disk($disk)->url($file->file_path);
    }

    /**
     * Check if file exists physically
     */
    public function fileExists(File $file): bool
    {
        $disk = $this->getStorageDisk();
        return Storage::disk($disk)->exists($file->file_path);
    }

    /**
     * Get storage disk
     */
    protected function getStorageDisk(): string
    {
        return config('filesystems.default') === 'r2' ? 'r2' : 'public';
    }

    /**
     * Get storage path for a fileable model
     */
    protected function getStoragePath($fileable, string $type): string
    {
        $modelName = strtolower(class_basename($fileable));
        $modelKey = $fileable->getKey();
        return "{$modelName}s/{$modelKey}/{$type}";
    }

    /**
     * Validate file type and size
     */
    public function validateFile(UploadedFile $file, array $allowedMimes = [], int $maxSize = 10240): array
    {
        $errors = [];

        // Check file size (in KB)
        if ($file->getSize() > ($maxSize * 1024)) {
            $errors[] = "File size must be less than " . ($maxSize / 1024) . "MB";
        }

        // Check MIME type
        if (!empty($allowedMimes) && !in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowedMimes);
        }

        return $errors;
    }

    /**
     * Get default allowed MIME types for documents
     */
    public static function getDefaultDocumentMimes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];
    }

    /**
     * Get file validation rules for Laravel validation
     */
    public static function getFileValidationRules(int $maxSize = 10240): string
    {
        $extensions = 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,gif,webp';
        return "file|mimes:{$extensions}|max:{$maxSize}";
    }
}
