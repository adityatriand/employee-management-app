<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of files.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $user = auth()->user();
        $query = File::where('workspace_id', $workspace->id)
            ->with(['employee', 'uploader'])
            ->orderBy('created_at', 'desc');

        // For regular users (level 0), automatically filter to their own files
        if ($user->level == 0) {
            $employee = \App\Models\Employee::where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->first();

            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                // If no employee record, return empty result
                $query->whereRaw('1 = 0');
            }
        } else {
            // For admins, allow filtering by employee
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
        }

        // Only allow additional filters for admins
        if ($user->level == 1) {
            // Filter by file type
            if ($request->filled('file_type')) {
                $query->where('file_type', $request->file_type);
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Filter standalone files (no employee) - check if employee_id is 0
            if ($request->filled('employee_id') && $request->employee_id == '0') {
                $query->whereNull('employee_id');
            }
        } else {
            // Filter by search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('file_name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%');
                });
            }
        }

        $files = $query->paginate(20)->appends($request->query());

        // Get employees for filter (scoped to workspace) - only for admins
        $employees = $user->level == 1
            ? Employee::where('workspace_id', $workspace->id)->orderBy('name', 'asc')->get()
            : collect();

        return view('files.index', compact('workspace', 'files', 'employees'));
    }

    /**
     * Show the form for creating a new file.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $employees = Employee::where('workspace_id', $workspace->id)->orderBy('name', 'asc')->get();
        $selectedEmployee = $request->get('employee_id');

        return view('files.create', compact('workspace', 'employees', 'selectedEmployee'));
    }

    /**
     * Store a newly created file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'employee_id' => 'nullable|exists:employees,id',
            'file_type' => 'required|in:document,photo',
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $mimeType = $uploadedFile->getMimeType();
        $fileSize = $uploadedFile->getSize();
        
        // Validate MIME type based on file_type
        $allowedMimeTypes = $validated['file_type'] === 'photo'
            ? ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp']
            : [
                // Documents
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/zip',
                'application/x-zip-compressed',
            ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['file' => 'Jenis file tidak diizinkan. ' . ($validated['file_type'] === 'photo' 
                    ? 'Foto harus berupa gambar (JPEG, PNG, GIF, WebP).' 
                    : 'Dokumen harus berupa PDF, Word, Excel, PowerPoint, atau file teks.')]);
        }

        // Generate unique file name
        $fileName = Str::uuid() . '_' . time() . '.' . $uploadedFile->getClientOriginalExtension();

        // Determine storage path
        $path = $validated['file_type'] === 'photo'
            ? 'photos/' . $fileName
            : 'documents/' . $fileName;

        // Upload to MinIO using workspace-specific bucket
        $workspaceDisk = $workspace->getStorageDisk();
        $workspaceDisk->put($path, file_get_contents($uploadedFile->getRealPath()));

        // Create file record
        $file = File::create([
            'name' => $validated['name'] ?? $originalName,
            'file_name' => $fileName,
            'file_path' => $path,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'file_type' => $validated['file_type'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'uploaded_by' => auth()->id(),
            'workspace_id' => $workspace->id,
        ]);

        // If it's a photo and associated with employee, update employee photo reference
        if ($validated['file_type'] === 'photo' && $validated['employee_id']) {
            $employee = Employee::where('workspace_id', $workspace->id)
                ->find($validated['employee_id']);
            if ($employee) {
                // Delete old photo file if exists
                $oldPhoto = $employee->photoFile;
                if ($oldPhoto) {
                    $oldPhoto->delete(); // Soft delete
                }
            }
        }

        $workspaceSlug = $workspace->slug;
        $redirectRoute = $validated['employee_id']
            ? route('workspace.employees.show', ['workspace' => $workspaceSlug, 'employee' => $validated['employee_id']])
            : route('workspace.files.index', ['workspace' => $workspaceSlug]);

        return redirect($redirectRoute)
            ->with('success', 'File berhasil diupload');
    }

    /**
     * Display the specified file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $file File ID or File model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $file)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get file from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $fileParam = $routeParams['file'] ?? $file;

        // If it's already a File model instance, use it; otherwise find by ID
        if ($fileParam instanceof File) {
            $file = $fileParam;
            // Verify workspace access
            if ($file->workspace_id !== $workspace->id) {
                abort(404, 'File not found');
            }
        } else {
            $file = File::where('workspace_id', $workspace->id)
                ->findOrFail((int)$fileParam);
        }

        $file->load(['employee', 'uploader']);

        // Get activity logs for this file
        $activityLogs = \App\Models\ActivityLog::where('model_type', get_class($file))
            ->where('model_id', $file->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('files.show', compact('workspace', 'file', 'activityLogs'));
    }

    /**
     * Download the specified file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $file File ID or File model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request, $file)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get file from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $fileParam = $routeParams['file'] ?? $file;

        // If it's already a File model instance, use it; otherwise find by ID
        if ($fileParam instanceof File) {
            $file = $fileParam;
            // Verify workspace access
            if ($file->workspace_id !== $workspace->id) {
                abort(404, 'File not found');
            }
        } else {
            $file = File::where('workspace_id', $workspace->id)
                ->findOrFail((int)$fileParam);
        }

        try {
            $disk = Storage::disk('minio');

            if (!$disk->exists($file->file_path)) {
                return redirect()->back()
                    ->with('error', 'File tidak ditemukan');
            }

            $mimeType = $file->mime_type ?: 'application/octet-stream';
            $fileSize = $disk->size($file->file_path);

            // Use streaming for better memory efficiency
            return response()->stream(function() use ($disk, $file) {
                $stream = $disk->readStream($file->file_path);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $file->name . '"',
                'Content-Length' => $fileSize,
            ]);
        } catch (\Exception $e) {
            Log::error('File download error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'File tidak ditemukan atau tidak dapat diakses');
        }
    }

    /**
     * Remove the specified file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $file File ID or File model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $file)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get file from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $fileParam = $routeParams['file'] ?? $file;

        // If it's already a File model instance, use it; otherwise find by ID
        if ($fileParam instanceof File) {
            $file = $fileParam;
            // Verify workspace access
            if ($file->workspace_id !== $workspace->id) {
                abort(404, 'File not found');
            }
        } else {
            $file = File::where('workspace_id', $workspace->id)
                ->findOrFail((int)$fileParam);
        }
        $employeeId = $file->employee_id;

        // Soft delete
        $file->delete();

        $workspaceSlug = $workspace->slug;
        $redirectRoute = $employeeId
            ? route('workspace.employees.show', ['workspace' => $workspaceSlug, 'employee' => $employeeId])
            : route('workspace.files.index', ['workspace' => $workspaceSlug]);

        return redirect($redirectRoute)
            ->with('success', 'File berhasil dihapus (dapat dipulihkan)');
    }

    /**
     * Restore a soft-deleted file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $file File ID or File model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $file)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get file from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $fileParam = $routeParams['file'] ?? $file;

        // If it's already a File model instance, use it; otherwise find by ID
        if ($fileParam instanceof File) {
            $file = $fileParam;
            // Verify workspace access
            if ($file->workspace_id !== $workspace->id) {
                abort(404, 'File not found');
            }
            $file = File::where('workspace_id', $workspace->id)
                ->withTrashed()
                ->findOrFail($file->id);
        } else {
            $file = File::where('workspace_id', $workspace->id)
                ->withTrashed()
                ->findOrFail((int)$fileParam);
        }
        $file->restore();

        $workspaceSlug = $workspace->slug;
        $redirectRoute = $file->employee_id
            ? route('workspace.employees.show', ['workspace' => $workspaceSlug, 'employee' => $file->employee_id])
            : route('workspace.files.index', ['workspace' => $workspaceSlug]);

        return redirect($redirectRoute)
            ->with('success', 'File berhasil dipulihkan');
    }
}

