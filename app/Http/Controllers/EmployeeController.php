<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $employees = Employee::with('position')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('employees.index', compact('employees'));
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
            'position_id' => 'required|exists:jabatan,id',
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

        // Handle file upload
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images'), $fileName);
            $validated['photo'] = $fileName;
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
        $employee = Employee::with('position')->findOrFail($id);
        return view('employees.show', compact('employee'));
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
            'position_id' => 'required|exists:jabatan,id',
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
            // Delete old photo
            if ($employee->photo && file_exists(public_path('images/' . $employee->photo))) {
                unlink(public_path('images/' . $employee->photo));
            }

            $file = $request->file('photo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images'), $fileName);
            $validated['photo'] = $fileName;
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

        // Delete photo file
        if ($employee->photo && file_exists(public_path('images/' . $employee->photo))) {
            unlink(public_path('images/' . $employee->photo));
        }

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data berhasil dihapus');
    }
}

