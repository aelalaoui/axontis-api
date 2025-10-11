<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Upload and store a file
     */
    public function uploadFile(UploadedFile $file, $fileable, string $type = 'document', ?string $title = null): File
    {
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
            'fileable_id' => $fileable->id,
            'type' => $type,
            'title' => $title ?: $originalName,
            'file_name' => $originalName,
            'file_path' => $fullPath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles(array $files, $fileable, string $type = 'document'): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadFile($file, $fileable, $type);
            }
        }

        return $uploadedFiles;
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
        return "{$modelName}s/{$fileable->id}/{$type}";
    }

    /**
     * Delete a file
     */
    public function deleteFile(File $file): bool
    {
        $disk = $this->getStorageDisk();

        // Delete physical file
        if (Storage::disk($disk)->exists($file->file_path)) {
            Storage::disk($disk)->delete($file->file_path);
        }

        // Delete database record
        return $file->delete();
    }
}
