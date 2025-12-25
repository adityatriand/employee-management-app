<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Employee;
use Illuminate\Http\Request;
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
        $query = File::with(['employee', 'uploader'])->orderBy('created_at', 'desc');

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

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

        $files = $query->paginate(20)->appends($request->query());

        // Get employees for filter
        $employees = Employee::orderBy('name', 'asc')->get();

        return view('files.index', compact('files', 'employees'));
    }

    /**
     * Show the form for creating a new file.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $employees = Employee::orderBy('name', 'asc')->get();
        $selectedEmployee = $request->get('employee_id');

        return view('files.create', compact('employees', 'selectedEmployee'));
    }

    /**
     * Store a newly created file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
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

        // Generate unique file name
        $fileName = Str::uuid() . '_' . time() . '.' . $uploadedFile->getClientOriginalExtension();
        
        // Determine storage path
        $path = $validated['file_type'] === 'photo' 
            ? 'photos/' . $fileName 
            : 'documents/' . $fileName;

        // Upload to MinIO
        Storage::disk('minio')->put($path, file_get_contents($uploadedFile->getRealPath()));

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
        ]);

        // If it's a photo and associated with employee, update employee photo reference
        if ($validated['file_type'] === 'photo' && $validated['employee_id']) {
            $employee = Employee::find($validated['employee_id']);
            if ($employee) {
                // Delete old photo file if exists
                $oldPhoto = $employee->photoFile;
                if ($oldPhoto) {
                    $oldPhoto->delete(); // Soft delete
                }
            }
        }

        $redirectRoute = $validated['employee_id'] 
            ? route('employees.show', $validated['employee_id'])
            : route('files.index');

        return redirect($redirectRoute)
            ->with('success', 'File berhasil diupload');
    }

    /**
     * Display the specified file.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $file = File::with(['employee', 'uploader'])->findOrFail($id);
        
        // Get activity logs for this file
        $activityLogs = \App\Models\ActivityLog::where('model_type', get_class($file))
            ->where('model_id', $file->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        return view('files.show', compact('file', 'activityLogs'));
    }

    /**
     * Download the specified file.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function download($id)
    {
        $file = File::findOrFail($id);

        try {
            $fileContent = Storage::disk('minio')->get($file->file_path);
            
            return response($fileContent, 200)
                ->header('Content-Type', $file->mime_type)
                ->header('Content-Disposition', 'attachment; filename="' . $file->name . '"');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'File tidak ditemukan atau tidak dapat diakses');
        }
    }

    /**
     * Remove the specified file.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $file = File::findOrFail($id);
        $employeeId = $file->employee_id;
        
        // Soft delete
        $file->delete();

        $redirectRoute = $employeeId 
            ? route('employees.show', $employeeId)
            : route('files.index');

        return redirect($redirectRoute)
            ->with('success', 'File berhasil dihapus (dapat dipulihkan)');
    }

    /**
     * Restore a soft-deleted file.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $file = File::withTrashed()->findOrFail($id);
        $file->restore();

        $redirectRoute = $file->employee_id 
            ? route('employees.show', $file->employee_id)
            : route('files.index');

        return redirect($redirectRoute)
            ->with('success', 'File berhasil dipulihkan');
    }
}

