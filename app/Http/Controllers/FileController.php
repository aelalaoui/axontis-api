<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\FileService;
use App\Traits\ManagesFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FileController extends Controller
{
    use ManagesFiles;

    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Display a listing of files
     */
    public function index(Request $request)
    {
        $query = File::with('fileable')
            ->orderBy($request->get('sort', 'created_at'), $request->get('direction', 'desc'));

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('mime_type', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // MIME type filter
        if ($request->filled('mime_filter')) {
            $mimeFilter = $request->get('mime_filter');
            switch ($mimeFilter) {
                case 'images':
                    $query->images();
                    break;
                case 'documents':
                    $query->documents();
                    break;
                case 'videos':
                    $query->videos();
                    break;
                case 'audios':
                    $query->audios();
                    break;
            }
        }

        // Fileable type filter
        if ($request->filled('fileable_type')) {
            $query->where('fileable_type', $request->get('fileable_type'));
        }

        $files = $query->paginate(15)->withQueryString();

        // Get available types and fileable types for filters
        $types = File::distinct('type')->whereNotNull('type')->pluck('type')->sort()->values();
        $fileableTypes = File::distinct('fileable_type')->whereNotNull('fileable_type')
            ->pluck('fileable_type')
            ->map(fn($type) => class_basename($type))
            ->sort()
            ->values();

        return Inertia::render('CRM/Files/Index', [
            'files' => $files,
            'types' => $types,
            'fileableTypes' => $fileableTypes,
            'filters' => $request->only(['search', 'type', 'mime_filter', 'fileable_type', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new file
     */
    public function create()
    {
        return Inertia::render('CRM/Files/Create');
    }

    /**
     * Store a newly created file
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'documents' => 'required|array',
            'documents.*' => FileService::getFileValidationRules(),
            'type' => 'nullable|string|max:50',
            'fileable_type' => 'nullable|string',
            'fileable_id' => 'nullable|string',
        ]);

        try {
            $uploadedFiles = [];

            foreach ($validated['documents'] as $file) {
                // If no fileable specified, create orphaned file
                $fileable = null;
                if (!empty($validated['fileable_type']) && !empty($validated['fileable_id'])) {
                    $fileableClass = "App\\Models\\" . $validated['fileable_type'];
                    if (class_exists($fileableClass)) {
                        $fileable = $fileableClass::find($validated['fileable_id']);
                    }
                }

                if ($fileable) {
                    $uploadedFiles[] = $this->fileService->uploadFile(
                        $file,
                        $fileable,
                        $validated['type'] ?? 'document'
                    );
                } else {
                    // Create file record without fileable relationship
                    $disk = config('filesystems.default') === 'r2' ? 'r2' : 'public';
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $filename = Str::uuid() . '.' . $extension;
                    $path = 'orphaned/' . ($validated['type'] ?? 'document');
                    $fullPath = $path . '/' . $filename;

                    $file->storeAs($path, $filename, $disk);

                    $uploadedFiles[] = File::create([
                        'fileable_type' => null,
                        'fileable_id' => null,
                        'type' => $validated['type'] ?? 'document',
                        'title' => $originalName,
                        'file_name' => $originalName,
                        'file_path' => $fullPath,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            $count = count($uploadedFiles);
            return redirect()->route('crm.files.index')
                ->with('success', "Successfully uploaded {$count} file(s).");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to upload files: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified file
     */
    public function show(File $file)
    {
        $file->load('fileable');

        return Inertia::render('CRM/Files/Show', [
            'file' => $file,
        ]);
    }

    /**
     * Show the form for editing the specified file
     */
    public function edit(File $file)
    {
        return Inertia::render('CRM/Files/Edit', [
            'file' => $file,
        ]);
    }

    /**
     * Update the specified file
     */
    public function update(Request $request, File $file)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
        ]);

        try {
            $file->update([
                'title' => $validated['title'],
                'type' => $validated['type'] ?? $file->type,
            ]);

            return redirect()->route('crm.files.index')
                ->with('success', 'File updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update file: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified file
     */
    public function destroy(File $file)
    {
        try {
            $this->fileService->deleteFile($file);

            return redirect()->route('crm.files.index')
                ->with('success', 'File deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('crm.files.index')
                ->with('error', 'Failed to delete file: ' . $e->getMessage());
        }
    }

    /**
     * Download the specified file
     */
    public function download(File $file)
    {
        if (!$this->fileService->fileExists($file)) {
            abort(404, 'File does not exist on storage.');
        }

        try {
            $url = $this->fileService->getFileUrl($file, true);
            return redirect($url);
        } catch (\Exception $e) {
            abort(500, 'Failed to generate download URL.');
        }
    }

    /**
     * View the specified file
     */
    public function view(File $file)
    {
        if (!$this->fileService->fileExists($file)) {
            abort(404, 'File does not exist on storage.');
        }

        try {
            $url = $this->fileService->getFileUrl($file, false);
            return redirect($url);
        } catch (\Exception $e) {
            abort(500, 'Failed to generate view URL.');
        }
    }

    /**
     * Upload multiple files via AJAX
     */
    public function uploadMultiple(Request $request)
    {
        $validated = $request->validate([
            'documents' => 'required|array',
            'documents.*' => FileService::getFileValidationRules(),
            'type' => 'nullable|string|max:50',
            'fileable_type' => 'nullable|string',
            'fileable_id' => 'nullable|string',
        ]);

        try {
            $uploadedFiles = [];

            foreach ($validated['documents'] as $file) {
                $fileable = null;
                if (!empty($validated['fileable_type']) && !empty($validated['fileable_id'])) {
                    $fileableClass = "App\\Models\\" . $validated['fileable_type'];
                    if (class_exists($fileableClass)) {
                        $fileable = $fileableClass::find($validated['fileable_id']);
                    }
                }

                if ($fileable) {
                    $uploadedFiles[] = $this->fileService->uploadFile(
                        $file,
                        $fileable,
                        $validated['type'] ?? 'document'
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully.',
                'files' => $uploadedFiles,
                'count' => count($uploadedFiles)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload files: ' . $e->getMessage()
            ], 500);
        }
    }
}
