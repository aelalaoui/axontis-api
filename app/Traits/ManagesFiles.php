<?php

namespace App\Traits;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait ManagesFiles
{
    protected FileService $fileService;

    /**
     * Initialize the FileService
     */
    protected function initializeFileService(): void
    {
        if (!isset($this->fileService)) {
            $this->fileService = app(FileService::class);
        }
    }

    /**
     * Resolve model from parameter (handle both UUID strings and model instances)
     */
    protected function resolveModel($modelParam)
    {
        // If it's already a model instance, return it
        if (is_object($modelParam)) {
            return $modelParam;
        }

        // If it's a string (UUID), try to resolve it
        if (is_string($modelParam)) {
            // Get the model class from the controller
            $controllerClass = get_class($this);

            // Extract model name from controller name (e.g., ProductController -> Product)
            $modelName = str_replace('Controller', '', class_basename($controllerClass));
            $modelClass = "App\\Models\\{$modelName}";

            if (class_exists($modelClass)) {
                // Use 'id' column instead of 'uuid' since the table uses UUIDs as primary keys
                $model = $modelClass::where(function($query) use ($modelParam, $modelClass) {
                    $instance = new $modelClass;
                    if ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), 'uuid')) {
                        $query->where('uuid', $modelParam);
                    }
                    $query->orWhere('id', $modelParam);
                })->first();
                if ($model) {
                    return $model;
                }
            }
        }

        throw new \Exception("Could not resolve model from parameter: " . json_encode($modelParam));
    }

    /**
     * Upload a document for any model
     */
    public function uploadDocument(Request $request, $model): RedirectResponse|JsonResponse
    {
        $this->initializeFileService();
        $model = $this->resolveModel($model);

        $validated = $request->validate([
            'document' => 'required|' . FileService::getFileValidationRules(),
            'type' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
        ]);

        try {
            $file = $this->fileService->uploadFile(
                $request->file('document'),
                $model,
                $validated['type'] ?? 'document',
                $validated['title'] ?? null
            );

            $message = 'Document uploaded successfully.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'file' => $file
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $error = 'Failed to upload document: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error
                ], 500);
            }

            return back()->with('error', $error);
        }
    }

    /**
     * Upload multiple documents for any model
     */
    public function uploadMultipleDocuments(Request $request, $model): RedirectResponse|JsonResponse
    {
        $this->initializeFileService();
        $model = $this->resolveModel($model);

        $validated = $request->validate([
            'documents' => 'required|array',
            'documents.*' => FileService::getFileValidationRules(),
            'type' => 'nullable|string|max:50',
        ]);

        try {
            $files = $this->fileService->uploadMultipleFiles(
                $validated['documents'],
                $model,
                $validated['type'] ?? 'document'
            );

            $count = count($files);
            $message = "Successfully uploaded {$count} document(s).";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'files' => $files,
                    'count' => $count
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $error = 'Failed to upload documents: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error
                ], 500);
            }

            return back()->with('error', $error);
        }
    }

    /**
     * Delete a document for any model
     */
    public function deleteDocument(Request $request, $model, string $fileUuid): RedirectResponse|JsonResponse
    {
        $this->initializeFileService();
        $model = $this->resolveModel($model);

        $file = $model->files()->where('uuid', $fileUuid)->first();

        if (!$file) {
            $error = 'Document not found.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error
                ], 404);
            }

            return back()->with('error', $error);
        }

        try {
            $this->fileService->deleteFile($file);
            $message = 'Document deleted successfully.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $error = 'Failed to delete document: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error
                ], 500);
            }

            return back()->with('error', $error);
        }
    }

    /**
     * Delete multiple documents for any model
     */
    public function deleteMultipleDocuments(Request $request, $model): RedirectResponse|JsonResponse
    {
        $this->initializeFileService();
        $model = $this->resolveModel($model);

        $validated = $request->validate([
            'file_uuids' => 'required|array',
            'file_uuids.*' => 'string|exists:files,uuid',
        ]);

        try {
            $deletedCount = $this->fileService->deleteMultipleFiles($validated['file_uuids'], $model);
            $message = "Successfully deleted {$deletedCount} document(s).";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'deleted_count' => $deletedCount
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $error = 'Failed to delete documents: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error
                ], 500);
            }

            return back()->with('error', $error);
        }
    }

    /**
     * Rename a document for any model
     */
    public function renameDocument(Request $request, $model, string $fileUuid): RedirectResponse|JsonResponse
    {
        $this->initializeFileService();
        $model = $this->resolveModel($model);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $file = $model->files()->where('uuid', $fileUuid)->first();

        if (!$file) {
            $error = 'Document not found.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error
                ], 404);
            }

            return back()->with('error', $error);
        }

        try {
            $this->fileService->renameFile($file, $validated['title']);
            $message = 'Document renamed successfully.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'file' => $file->fresh()
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $error = 'Failed to rename document: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $error
                ], 500);
            }

            return back()->with('error', $error);
        }
    }

    /**
     * Download a document for any model
     */
    public function downloadDocument(Request $request, $model, string $fileUuid)
    {
        $this->initializeFileService();
        $model = $this->resolveModel($model);

        $file = $model->files()->where('uuid', $fileUuid)->first();

        if (!$file) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found.'
                ], 404);
            }

            abort(404, 'Document not found.');
        }

        if (!$this->fileService->fileExists($file)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist on storage.'
                ], 404);
            }

            abort(404, 'File does not exist on storage.');
        }

        try {
            $url = $this->fileService->getFileUrl($file, true);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'download_url' => $url,
                    'file' => $file
                ]);
            }

            return redirect($url);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate download URL: ' . $e->getMessage()
                ], 500);
            }

            abort(500, 'Failed to generate download URL.');
        }
    }

    /**
     * View a document for any model
     */
    public function viewDocument(Request $request, $model, string $fileUuid)
    {
        $this->initializeFileService();
        $model = $this->resolveModel($model);

        $file = $model->files()->where('uuid', $fileUuid)->first();

        if (!$file) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found.'
                ], 404);
            }

            abort(404, 'Document not found.');
        }

        if (!$this->fileService->fileExists($file)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist on storage.'
                ], 404);
            }

            abort(404, 'File does not exist on storage.');
        }

        try {
            $url = $this->fileService->getFileUrl($file, false);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'view_url' => $url,
                    'file' => $file
                ]);
            }

            return redirect($url);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate view URL: ' . $e->getMessage()
                ], 500);
            }

            abort(500, 'Failed to generate view URL.');
        }
    }

    /**
     * Get files list for any model
     */
    public function getDocuments(Request $request, $model): JsonResponse
    {
        $model = $this->resolveModel($model);
        $query = $model->files();

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search in title or filename
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $files = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }
}
