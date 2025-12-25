<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use App\Exports\EmployeesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $query = Employee::with('position');

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

        // Get positions for filter dropdown
        $positions = Position::orderBy('name', 'asc')->get();
        
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
        
        return view('employees.index', compact('employees', 'positions', 'hasFilters', 'filterCount'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $positions = Position::orderBy('name', 'asc')->get();
        return view('employees.create', compact('positions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birth_date' => 'required|date',
            'position_id' => 'required|exists:positions,id',
            'description' => 'required|string',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'Nama pegawai tidak boleh kosong',
            'gender.required' => 'Jenis kelamin tidak boleh kosong',
            'birth_date.required' => 'Tanggal lahir tidak boleh kosong',
            'position_id.required' => 'Jabatan harus diisi',
            'position_id.exists' => 'Jabatan tidak valid',
            'description.required' => 'Keterangan tidak boleh kosong',
            'photo.required' => 'Foto tidak boleh kosong',
            'photo.image' => 'File harus berupa gambar',
            'photo.max' => 'Ukuran foto maksimal 2MB',
        ]);

        // Handle file upload to MinIO
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = \Illuminate\Support\Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'photos/' . $fileName;
            
            // Upload to MinIO
            \Illuminate\Support\Facades\Storage::disk('minio')->put($path, file_get_contents($file->getRealPath()));
            
            // Create file record
            $fileRecord = \App\Models\File::create([
                'name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => 'photo',
                'employee_id' => null, // Will be set after employee is created
                'uploaded_by' => auth()->id(),
            ]);
            
            // Store file ID in employee record (for backward compatibility)
            $validated['photo'] = $fileRecord->id;
            
            // After creating employee, link the file
            $employee = Employee::create($validated);
            $fileRecord->update(['employee_id' => $employee->id]);
            
            return redirect()
                ->route('employees.index')
                ->with('success', 'Data berhasil ditambahkan');
        }

        Employee::create($validated);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $employee = Employee::with(['position', 'files'])->findOrFail($id);
        $files = $employee->files()->orderBy('created_at', 'desc')->get();
        return view('employees.show', compact('employee', 'files'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        $positions = Position::orderBy('name', 'asc')->get();

        return view('employees.edit', compact('employee', 'positions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

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
            
            // Upload to MinIO
            \Illuminate\Support\Facades\Storage::disk('minio')->put($path, file_get_contents($file->getRealPath()));
            
            // Create file record
            $fileRecord = \App\Models\File::create([
                'name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => 'photo',
                'employee_id' => $employee->id,
                'uploaded_by' => auth()->id(),
            ]);
            
            // Store file ID in employee record (for backward compatibility)
            $validated['photo'] = $fileRecord->id;
        }

        $employee->update($validated);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data berhasil diedit');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        
        // Soft delete (photo file is kept for potential restore)
        $employee->delete();

        return redirect()
            ->route('employees.index')
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
        $query = Employee::with('position');

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
     * Export employees to PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        $employees = $this->getFilteredEmployeesQuery($request)->get();
        
        // Get filter information for PDF header
        $filters = [];
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }
        if ($request->filled('position_id')) {
            $position = Position::find($request->position_id);
            $filters['position'] = $position ? $position->name : '-';
        }
        if ($request->filled('gender')) {
            $filters['gender'] = $request->gender == 'L' ? 'Laki-Laki' : 'Perempuan';
        }
        if ($request->filled('birth_date_from') || $request->filled('birth_date_to')) {
            $filters['birth_date'] = ($request->birth_date_from ?? '-') . ' s/d ' . ($request->birth_date_to ?? '-');
        }
        if ($request->filled('created_from') || $request->filled('created_to')) {
            $filters['created_date'] = ($request->created_from ?? '-') . ' s/d ' . ($request->created_to ?? '-');
        }

        $pdf = Pdf::loadView('employees.export-pdf', [
            'employees' => $employees,
            'filters' => $filters,
            'total' => $employees->count(),
        ]);

        $filename = 'data-pegawai-' . date('Y-m-d-His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export employees to Excel
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportExcel(Request $request)
    {
        $employees = $this->getFilteredEmployeesQuery($request)->get();
        
        $filename = 'data-pegawai-' . date('Y-m-d-His') . '.xlsx';
        
        return Excel::download(new EmployeesExport($employees), $filename);
    }

    /**
     * Restore a soft-deleted employee
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $employee = Employee::withTrashed()->findOrFail($id);
        $employee->restore();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data berhasil dipulihkan');
    }
}

