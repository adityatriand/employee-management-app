<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use App\Exports\EmployeesExport;
use App\Jobs\ExportEmployeesPdf;
use App\Jobs\ExportEmployeesExcel;
use App\Helpers\PasswordHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $query = Employee::where('workspace_id', $workspace->id)
            ->with('position');

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by position
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Filter by birth date range
        if ($request->filled('birth_date_from')) {
            $query->whereDate('birth_date', '>=', $request->birth_date_from);
        }
        if ($request->filled('birth_date_to')) {
            $query->whereDate('birth_date', '<=', $request->birth_date_to);
        }

        // Filter by creation date range
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Order and paginate
        $employees = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->appends($request->query());

        // Get positions for filter dropdown (scoped to workspace) - cached
        $positions = Cache::remember("positions_{$workspace->id}", 3600, function () use ($workspace) {
            return Position::where('workspace_id', $workspace->id)
                ->orderBy('name', 'asc')
                ->get();
        });
        
        // Calculate filter status
        $hasFilters = $request->filled('search') ||
                     $request->filled('position_id') ||
                     $request->filled('gender') ||
                     $request->filled('birth_date_from') ||
                     $request->filled('birth_date_to') ||
                     $request->filled('created_from') ||
                     $request->filled('created_to');
        
        $filterCount = 0;
        if ($request->filled('search')) $filterCount++;
        if ($request->filled('position_id')) $filterCount++;
        if ($request->filled('gender')) $filterCount++;
        if ($request->filled('birth_date_from') || $request->filled('birth_date_to')) $filterCount++;
        if ($request->filled('created_from') || $request->filled('created_to')) $filterCount++;
        
        return view('employees.index', compact('workspace', 'employees', 'positions', 'hasFilters', 'filterCount'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $positions = Cache::remember("positions_{$workspace->id}", 3600, function () use ($workspace) {
            return Position::where('workspace_id', $workspace->id)->orderBy('name', 'asc')->get();
        });
        
        // Get password requirements for display
        $passwordDescription = \App\Helpers\PasswordHelper::getPasswordDescription($workspace->id);
        
        return view('employees.create', compact('positions', 'workspace', 'passwordDescription'));
    }

    /**
     * Store a newly created resource in storage.
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
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'gender' => 'required|in:L,P',
            'birth_date' => 'required|date',
            'position_id' => 'required|exists:positions,id',
            'description' => 'required|string',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'Nama pegawai tidak boleh kosong',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.min' => 'Password minimal 6 karakter',
            'gender.required' => 'Jenis kelamin tidak boleh kosong',
            'birth_date.required' => 'Tanggal lahir tidak boleh kosong',
            'position_id.required' => 'Jabatan harus diisi',
            'position_id.exists' => 'Jabatan tidak valid',
            'description.required' => 'Keterangan tidak boleh kosong',
            'photo.required' => 'Foto tidak boleh kosong',
            'photo.image' => 'File harus berupa gambar',
            'photo.max' => 'Ukuran foto maksimal 2MB',
        ]);

        // Verify position belongs to workspace
        $position = \App\Models\Position::where('id', $validated['position_id'])
            ->where('workspace_id', $workspace->id)
            ->first();
        
        if (!$position) {
            return back()->withErrors(['position_id' => 'Jabatan tidak valid untuk workspace ini'])->withInput();
        }

        // Create user account if email is provided
        $user = null;
        $generatedPassword = null;
        if ($request->filled('email')) {
            // Determine password to use
            $password = null;
            if ($request->filled('password')) {
                // Admin provided custom password - validate it meets requirements
                $passwordRule = \App\Helpers\PasswordHelper::getPasswordRule($workspace->id);
                $validator = \Illuminate\Support\Facades\Validator::make(
                    ['password' => $request->password],
                    ['password' => ['required', $passwordRule]]
                );
                
                if ($validator->fails()) {
                    return back()
                        ->withErrors($validator)
                        ->withInput()
                        ->with('password_error', 'Password tidak memenuhi persyaratan. ' . \App\Helpers\PasswordHelper::getPasswordDescription($workspace->id));
                }
                
                $password = $request->password;
            } else {
                // Use default password from settings or generate one
                $defaultPassword = \App\Models\Setting::get('employee_default_password', '', $workspace->id);
                
                if (!empty($defaultPassword)) {
                    $password = $defaultPassword;
                } else {
                    // Generate password that meets requirements
                    $password = \App\Helpers\PasswordHelper::generatePassword($workspace->id);
                    $generatedPassword = $password; // Store to show to admin
                }
            }
            
            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'level' => 0, // Regular user
                'workspace_id' => $workspace->id,
            ]);
        }

        // Handle file upload to MinIO
        $fileRecord = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = \Illuminate\Support\Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'photos/' . $fileName;
            
            // Upload to MinIO using workspace-specific bucket
            $workspaceDisk = $workspace->getStorageDisk();
            $workspaceDisk->put($path, file_get_contents($file->getRealPath()));
            
            // Create file record
            $fileRecord = \App\Models\File::create([
                'name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => 'photo',
                'employee_id' => null, // Will be set after employee is created
                'workspace_id' => $workspace->id,
                'uploaded_by' => auth()->id(),
                'workspace_id' => $workspace->id,
            ]);
            
            // Store file ID in employee record (for backward compatibility)
            $validated['photo'] = $fileRecord->id;
        }
        
        // Create employee with workspace_id and user_id
        $validated['workspace_id'] = $workspace->id;
        if ($user) {
            $validated['user_id'] = $user->id;
        }
        unset($validated['email']); // Remove email from employee fillable
        
        $employee = Employee::create($validated);
        
        // Link file to employee
        if ($fileRecord) {
            $fileRecord->update(['employee_id' => $employee->id]);
        }
        
        $workspaceSlug = $workspace->slug;
        $message = 'Data berhasil ditambahkan';
        
        if ($user) {
            if ($generatedPassword) {
                // Show generated password to admin
                $message .= '. Akun pengguna telah dibuat dengan email: ' . $user->email;
                return redirect()
                    ->route('workspace.employees.index', ['workspace' => $workspaceSlug])
                    ->with('success', $message)
                    ->with('generated_password', $generatedPassword)
                    ->with('user_email', $user->email);
            } else {
                $message .= '. Akun pengguna telah dibuat dengan email: ' . $user->email . ' (password sesuai yang Anda set)';
            }
        }
        
        return redirect()
            ->route('workspace.employees.index', ['workspace' => $workspaceSlug])
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $employee Employee ID or Employee model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $employee)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get employee from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $employeeParam = $routeParams['employee'] ?? $employee;
        
        // If it's already an Employee model instance, use it; otherwise find by ID
        if ($employeeParam instanceof Employee) {
            $employee = $employeeParam;
            // Verify workspace access
            if ($employee->workspace_id !== $workspace->id) {
                abort(404, 'Employee not found');
            }
        } else {
            $employee = Employee::where('workspace_id', $workspace->id)
                ->findOrFail((int)$employeeParam);
        }
        
        // Load relationships
        $employee->load(['position', 'files', 'assets', 'user']);
        
        // Regular users can only view their own profile
        if (auth()->user()->level == 0 && $employee->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke profil ini');
        }
        
        $files = $employee->files()->orderBy('created_at', 'desc')->get();
        $assets = $employee->assets()->orderBy('assigned_date', 'desc')->get();
        return view('employees.show', compact('workspace', 'employee', 'files', 'assets'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $employee Employee ID or Employee model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $employee)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get employee from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $employeeParam = $routeParams['employee'] ?? $employee;
        
        // If it's already an Employee model instance, use it; otherwise find by ID
        if ($employeeParam instanceof Employee) {
            $employee = $employeeParam;
            // Verify workspace access
            if ($employee->workspace_id !== $workspace->id) {
                abort(404, 'Employee not found');
            }
        } else {
            $employee = Employee::where('workspace_id', $workspace->id)
                ->findOrFail((int)$employeeParam);
        }
        
        // Regular users can only edit their own profile
        if (auth()->user()->level == 0 && $employee->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit profil ini');
        }

        $positions = Position::where('workspace_id', $workspace->id)->orderBy('name', 'asc')->get();

        return view('employees.edit', compact('workspace', 'employee', 'positions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $employee Employee ID or Employee model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $employee)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get employee from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $employeeParam = $routeParams['employee'] ?? $employee;
        
        // If it's already an Employee model instance, use it; otherwise find by ID
        if ($employeeParam instanceof Employee) {
            $employee = $employeeParam;
            // Verify workspace access
            if ($employee->workspace_id !== $workspace->id) {
                abort(404, 'Employee not found');
            }
        } else {
            $employee = Employee::where('workspace_id', $workspace->id)
                ->findOrFail((int)$employeeParam);
        }
        
        // Regular users can only edit their own profile
        if (auth()->user()->level == 0 && $employee->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit profil ini');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birth_date' => 'required|date',
            'position_id' => 'required|exists:positions,id',
            'description' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'Nama pegawai tidak boleh kosong',
            'gender.required' => 'Jenis kelamin tidak boleh kosong',
            'birth_date.required' => 'Tanggal lahir tidak boleh kosong',
            'position_id.required' => 'Jabatan harus diisi',
            'position_id.exists' => 'Jabatan tidak valid',
            'description.required' => 'Keterangan tidak boleh kosong',
            'photo.image' => 'File harus berupa gambar',
            'photo.max' => 'Ukuran foto maksimal 2MB',
        ]);

        // Handle file upload if new photo is provided
        if ($request->hasFile('photo')) {
            // Delete old photo file if exists
            $oldPhoto = $employee->photoFile;
            if ($oldPhoto) {
                $oldPhoto->delete(); // Soft delete old photo
            }

            $file = $request->file('photo');
            $fileName = \Illuminate\Support\Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'photos/' . $fileName;
            
            // Upload to MinIO using workspace-specific bucket
            $workspace = $request->get('workspace');
            if (!$workspace) {
                abort(404, 'Workspace not found');
            }
            $workspaceDisk = $workspace->getStorageDisk();
            $workspaceDisk->put($path, file_get_contents($file->getRealPath()));
            
            // Create file record
            $fileRecord = \App\Models\File::create([
                'name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => 'photo',
                'employee_id' => $employee->id,
                'workspace_id' => $workspace->id,
                'uploaded_by' => auth()->id(),
                'workspace_id' => $workspace->id,
            ]);
            
            // Store file ID in employee record (for backward compatibility)
            $validated['photo'] = $fileRecord->id;
        }

        $employee->update($validated);

        $workspaceSlug = $workspace->slug;
        $workspaceSlug = $workspace->slug;
        
        // If regular user editing own profile, redirect to dashboard
        if (auth()->user()->level == 0 && $employee->user_id === auth()->id()) {
            return redirect()
                ->route('workspace.dashboard', ['workspace' => $workspaceSlug])
                ->with('success', 'Profil berhasil diperbarui');
        }
        
        return redirect()
            ->route('workspace.employees.index', ['workspace' => $workspaceSlug])
            ->with('success', 'Data berhasil diedit');
    }

    /**
     * Show the form for editing own profile (regular users only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editProfile(Request $request)
    {
        $user = auth()->user();
        
        // Only regular users can access this
        if ($user->level != 0) {
            abort(403, 'Hanya pengguna biasa yang dapat mengakses halaman ini');
        }

        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get employee record for this user
        $employee = Employee::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('employees.edit-profile', compact('workspace', 'employee'));
    }

    /**
     * Update own profile (regular users only - limited fields).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        // Only regular users can access this
        if ($user->level != 0) {
            abort(403, 'Hanya pengguna biasa yang dapat mengakses halaman ini');
        }

        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get employee record for this user
        $employee = Employee::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Only allow editing: name, gender, birth_date, and photo
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birth_date' => 'required|date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'Nama tidak boleh kosong',
            'gender.required' => 'Jenis kelamin tidak boleh kosong',
            'gender.in' => 'Jenis kelamin harus Laki-Laki (L) atau Perempuan (P)',
            'birth_date.required' => 'Tanggal lahir tidak boleh kosong',
            'birth_date.date' => 'Tanggal lahir harus berupa tanggal yang valid',
            'photo.image' => 'File harus berupa gambar',
            'photo.max' => 'Ukuran foto maksimal 2MB',
        ]);

        // Handle file upload if new photo is provided
        if ($request->hasFile('photo')) {
            // Delete old photo file if exists
            $oldPhoto = $employee->photoFile;
            if ($oldPhoto) {
                $oldPhoto->delete(); // Soft delete old photo
            }

            $file = $request->file('photo');
            $fileName = \Illuminate\Support\Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'photos/' . $fileName;
            
            // Upload to MinIO using workspace-specific bucket
            $workspaceDisk = $workspace->getStorageDisk();
            $workspaceDisk->put($path, file_get_contents($file->getRealPath()));
            
            // Create file record
            $fileRecord = \App\Models\File::create([
                'name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => 'photo',
                'employee_id' => $employee->id,
                'workspace_id' => $workspace->id,
                'uploaded_by' => auth()->id(),
            ]);
            
            // Store file ID in employee record (for backward compatibility)
            $validated['photo'] = $fileRecord->id;
        }

        $employee->update($validated);

        $workspaceSlug = $workspace->slug;
        return redirect()
            ->route('workspace.dashboard', ['workspace' => $workspaceSlug])
            ->with('success', 'Profil berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $employee)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get employee from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $employeeParam = $routeParams['employee'] ?? $employee;
        
        // If it's already an Employee model instance, use it; otherwise find by ID
        if ($employeeParam instanceof Employee) {
            $employee = $employeeParam;
            // Verify workspace access
            if ($employee->workspace_id !== $workspace->id) {
                abort(404, 'Employee not found');
            }
        } else {
            $employee = Employee::where('workspace_id', $workspace->id)
                ->findOrFail((int)$employeeParam);
        }
        
        // Soft delete (photo file is kept for potential restore)
        $employee->delete();

        return redirect()
            ->route('workspace.employees.index', ['workspace' => $workspace->slug])
            ->with('success', 'Data berhasil dihapus (dapat dipulihkan)');
    }

    /**
     * Get filtered employees query (without pagination) for export
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getFilteredEmployeesQuery(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $query = Employee::where('workspace_id', $workspace->id)->with('position');

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by position
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Filter by birth date range
        if ($request->filled('birth_date_from')) {
            $query->whereDate('birth_date', '>=', $request->birth_date_from);
        }
        if ($request->filled('birth_date_to')) {
            $query->whereDate('birth_date', '<=', $request->birth_date_to);
        }

        // Filter by creation date range
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Order
        $query->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');

        return $query;
    }

    /**
     * Export employees to PDF (background job)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportPdf(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Prepare filters
        $filters = [];
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }
        if ($request->filled('position_id')) {
            $filters['position_id'] = $request->position_id;
        }
        if ($request->filled('gender')) {
            $filters['gender'] = $request->gender;
        }
        if ($request->filled('birth_date_from')) {
            $filters['birth_date_from'] = $request->birth_date_from;
        }
        if ($request->filled('birth_date_to')) {
            $filters['birth_date_to'] = $request->birth_date_to;
        }
        if ($request->filled('created_from')) {
            $filters['created_from'] = $request->created_from;
        }
        if ($request->filled('created_to')) {
            $filters['created_to'] = $request->created_to;
        }

        // Dispatch job
        ExportEmployeesPdf::dispatch($workspace->id, $filters, auth()->id());

        return redirect()
            ->route('workspace.employees.index', ['workspace' => $workspace->slug])
            ->with('success', 'Export PDF sedang diproses. File akan tersedia untuk diunduh dalam beberapa saat.');
    }

    /**
     * Download exported PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function downloadExportPdf(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $cacheKey = "export_pdf_{$request->user()->id}_{$workspace->id}";
        $exportInfo = Cache::get($cacheKey);

        if (!$exportInfo || !Storage::disk('local')->exists($exportInfo['filename'])) {
            return redirect()
                ->route('workspace.employees.index', ['workspace' => $workspace->slug])
                ->with('error', 'File export tidak ditemukan atau sudah kedaluwarsa.');
        }

        $filePath = storage_path('app/' . $exportInfo['filename']);
        
        // Delete from cache after download
        Cache::forget($cacheKey);

        return response()->download($filePath, $exportInfo['original_name'])->deleteFileAfterSend(true);
    }

    /**
     * Export employees to Excel (background job)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportExcel(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Prepare filters
        $filters = [];
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }
        if ($request->filled('position_id')) {
            $filters['position_id'] = $request->position_id;
        }
        if ($request->filled('gender')) {
            $filters['gender'] = $request->gender;
        }
        if ($request->filled('birth_date_from')) {
            $filters['birth_date_from'] = $request->birth_date_from;
        }
        if ($request->filled('birth_date_to')) {
            $filters['birth_date_to'] = $request->birth_date_to;
        }
        if ($request->filled('created_from')) {
            $filters['created_from'] = $request->created_from;
        }
        if ($request->filled('created_to')) {
            $filters['created_to'] = $request->created_to;
        }

        // Dispatch job
        ExportEmployeesExcel::dispatch($workspace->id, $filters, auth()->id());

        return redirect()
            ->route('workspace.employees.index', ['workspace' => $workspace->slug])
            ->with('success', 'Export Excel sedang diproses. File akan tersedia untuk diunduh dalam beberapa saat.');
    }

    /**
     * Download exported Excel
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function downloadExportExcel(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $cacheKey = "export_excel_{$request->user()->id}_{$workspace->id}";
        $exportInfo = Cache::get($cacheKey);

        if (!$exportInfo || !Storage::disk('local')->exists($exportInfo['filename'])) {
            return redirect()
                ->route('workspace.employees.index', ['workspace' => $workspace->slug])
                ->with('error', 'File export tidak ditemukan atau sudah kedaluwarsa.');
        }

        $filePath = storage_path('app/' . $exportInfo['filename']);
        
        // Delete from cache after download
        Cache::forget($cacheKey);

        return response()->download($filePath, $exportInfo['original_name'])->deleteFileAfterSend(true);
    }

    /**
     * Restore a soft-deleted employee
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $employee)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get employee from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $employeeParam = $routeParams['employee'] ?? $employee;
        
        // If it's already an Employee model instance, use it; otherwise find by ID
        if ($employeeParam instanceof Employee) {
            $employee = $employeeParam;
            // Verify workspace access
            if ($employee->workspace_id !== $workspace->id) {
                abort(404, 'Employee not found');
            }
            $employee = $employee->withTrashed()->first();
        } else {
            $employee = Employee::where('workspace_id', $workspace->id)
                ->withTrashed()
                ->findOrFail((int)$employeeParam);
        }
        $employee->restore();

        return redirect()
            ->route('workspace.employees.index', ['workspace' => $workspace->slug])
            ->with('success', 'Data berhasil dipulihkan');
    }
}

