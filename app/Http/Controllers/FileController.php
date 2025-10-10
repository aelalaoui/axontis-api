<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FileController extends Controller
{
    protected $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default') === 'r2' ? 'r2' : 'public';
    }

    /**
     * Display a listing of files.
     */
    public function index(Request $request)
    {
        $query = File::query();

        // Filter by type if provided
        if ($request->has('type') && $request->type) {
            $query->byType($request->type);
        }

        // Filter by mime type category
        if ($request->has('category')) {
            switch ($request->category) {
                case 'images':
                    $query->images();
                    break;
                case 'documents':
                    $query->documents();
                    break;
                case 'videos':
                    $query->videos();
                    break;
            }
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $files = $query->orderBy('created_at', 'desc')
                      ->paginate(20)
                      ->withQueryString();

        return Inertia::render('CRM/Files/Index', [
            'files' => $files,
            'filters' => $request->only(['type', 'category', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new file.
     */
    public function create()
    {
        return Inertia::render('CRM/Files/Create');
    }

    /**
     * Store a newly created file.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:50000', // 50MB max
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:100',
            'fileable_type' => 'nullable|string|max:255',
            'fileable_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $uploadedFile = $request->file('file');

        // Generate unique filename
        $filename = Str::uuid() . '.' . $uploadedFile->getClientOriginalExtension();
        $path = 'files/' . date('Y/m/d') . '/' . $filename;

        // Store file
        $stored = Storage::disk($this->disk)->put($path, file_get_contents($uploadedFile));

        if (!$stored) {
            return back()->with('error', 'Failed to upload file.');
        }

        // Create file record
        $file = File::create([
            'fileable_type' => $request->fileable_type,
            'fileable_id' => $request->fileable_id,
            'type' => $request->type ?? 'general',
            'title' => $request->title ?? $uploadedFile->getClientOriginalName(),
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'file_size' => $uploadedFile->getSize(),
        ]);

        return redirect()->route('crm.files.show', $file)
                        ->with('success', 'File uploaded successfully.');
    }

    /**
     * Display the specified file.
     */
    public function show(File $file)
    {
        return Inertia::render('CRM/Files/Show', [
            'file' => $file->load('fileable'),
        ]);
    }

    /**
     * Show the form for editing the specified file.
     */
    public function edit(File $file)
    {
        return Inertia::render('CRM/Files/Edit', [
            'file' => $file,
        ]);
    }

    /**
     * Update the specified file.
     */
    public function update(Request $request, File $file)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $file->update([
            'title' => $request->title,
            'type' => $request->type ?? $file->type,
        ]);

        return redirect()->route('crm.files.show', $file)
                        ->with('success', 'File updated successfully.');
    }

    /**
     * Remove the specified file.
     */
    public function destroy(File $file)
    {
        try {
            // Delete physical file
            if (Storage::disk($this->disk)->exists($file->file_path)) {
                Storage::disk($this->disk)->delete($file->file_path);
            }

            // Delete database record
            $file->delete();

            return redirect()->route('crm.files.index')
                            ->with('success', 'File deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete file.');
        }
    }

    /**
     * Download the specified file.
     */
    public function download(File $file)
    {
        if (!Storage::disk($this->disk)->exists($file->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk($this->disk)->download(
            $file->file_path,
            $file->file_name
        );
    }

    /**
     * Stream/view the specified file.
     */
    public function view(File $file)
    {
        if (!Storage::disk($this->disk)->exists($file->file_path)) {
            abort(404, 'File not found.');
        }

        $content = Storage::disk($this->disk)->get($file->file_path);

        return response($content)
            ->header('Content-Type', $file->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $file->file_name . '"');
    }

    /**
     * Upload multiple files at once.
     */
    public function uploadMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'file|max:50000',
            'type' => 'nullable|string|max:100',
            'fileable_type' => 'nullable|string|max:255',
            'fileable_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $uploadedFile) {
            try {
                // Generate unique filename
                $filename = Str::uuid() . '.' . $uploadedFile->getClientOriginalExtension();
                $path = 'files/' . date('Y/m/d') . '/' . $filename;

                // Store file
                $stored = Storage::disk($this->disk)->put($path, file_get_contents($uploadedFile));

                if ($stored) {
                    // Create file record
                    $file = File::create([
                        'fileable_type' => $request->fileable_type,
                        'fileable_id' => $request->fileable_id,
                        'type' => $request->type ?? 'general',
                        'title' => $uploadedFile->getClientOriginalName(),
                        'file_name' => $uploadedFile->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $uploadedFile->getMimeType(),
                        'file_size' => $uploadedFile->getSize(),
                    ]);

                    $uploadedFiles[] = $file;
                } else {
                    $errors[] = "Failed to upload: " . $uploadedFile->getClientOriginalName();
                }
            } catch (\Exception $e) {
                $errors[] = "Error uploading " . $uploadedFile->getClientOriginalName() . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors,
        ]);
    }
}
