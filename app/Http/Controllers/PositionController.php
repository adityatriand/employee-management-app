<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $positions = Position::withCount('employees')
            ->orderBy('name', 'asc')
            ->paginate(10);
        return view('positions.index', compact('positions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('positions.create');
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
            'name' => 'required|string|max:255|unique:jabatan,name',
        ], [
            'name.required' => 'Nama jabatan tidak boleh kosong',
            'name.unique' => 'Nama jabatan sudah ada',
        ]);

        Position::create($validated);

        return redirect()
            ->route('positions.index')
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
        $position = Position::with('employees')->findOrFail($id);
        return view('positions.show', compact('position'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $position = Position::findOrFail($id);
        return view('positions.edit', compact('position'));
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
        $position = Position::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:jabatan,name,' . $id . ',id',
        ], [
            'name.required' => 'Nama jabatan tidak boleh kosong',
            'name.unique' => 'Nama jabatan sudah ada',
        ]);

        $position->update($validated);

        return redirect()
            ->route('positions.index')
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
        $position = Position::findOrFail($id);

        // Check if position has employees
        if ($position->employees()->count() > 0) {
            return redirect()
                ->route('positions.index')
                ->with('error', 'Tidak dapat menghapus jabatan yang masih memiliki pegawai');
        }

        $position->delete();

        return redirect()
            ->route('positions.index')
            ->with('success', 'Data berhasil dihapus');
    }
}

