<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $positions = Position::where('workspace_id', $workspace->id)
            ->withCount(['employees' => function($q) use ($workspace) {
                $q->where('workspace_id', $workspace->id);
            }])
            ->orderBy('name', 'asc')
            ->paginate(10);
        return view('positions.index', compact('workspace', 'positions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }
        return view('positions.create', compact('workspace'));
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
        ], [
            'name.required' => 'Nama jabatan tidak boleh kosong',
        ]);

        // Check uniqueness within workspace
        $exists = Position::where('workspace_id', $workspace->id)
            ->where('name', $validated['name'])
            ->exists();
        
        if ($exists) {
            return back()->withErrors(['name' => 'Nama jabatan sudah ada di workspace ini'])->withInput();
        }

        $validated['workspace_id'] = $workspace->id;
        Position::create($validated);

        // Clear positions cache for this workspace
        Cache::forget("positions_{$workspace->id}");
        Cache::forget("positions_count_{$workspace->id}");

        return redirect()
            ->route('workspace.positions.index', ['workspace' => $workspace->slug])
            ->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $position Position ID or Position model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $position)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get position from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $positionParam = $routeParams['position'] ?? $position;
        
        // If it's already a Position model instance, use it; otherwise find by ID
        if ($positionParam instanceof Position) {
            $position = $positionParam;
            // Verify workspace access
            if ($position->workspace_id !== $workspace->id) {
                abort(404, 'Position not found');
            }
        } else {
            $position = Position::where('workspace_id', $workspace->id)
                ->findOrFail((int)$positionParam);
        }
        
        $position->load(['employees' => function($q) use ($workspace) {
            $q->where('workspace_id', $workspace->id);
        }]);
        return view('positions.show', compact('workspace', 'position'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $position Position ID or Position model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $position)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get position from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $positionParam = $routeParams['position'] ?? $position;
        
        // If it's already a Position model instance, use it; otherwise find by ID
        if ($positionParam instanceof Position) {
            $position = $positionParam;
            // Verify workspace access
            if ($position->workspace_id !== $workspace->id) {
                abort(404, 'Position not found');
            }
        } else {
            $position = Position::where('workspace_id', $workspace->id)
                ->findOrFail((int)$positionParam);
        }
        return view('positions.edit', compact('workspace', 'position'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $position Position ID or Position model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $position)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get position from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $positionParam = $routeParams['position'] ?? $position;
        
        // If it's already a Position model instance, use it; otherwise find by ID
        if ($positionParam instanceof Position) {
            $position = $positionParam;
            // Verify workspace access
            if ($position->workspace_id !== $workspace->id) {
                abort(404, 'Position not found');
            }
        } else {
            $position = Position::where('workspace_id', $workspace->id)
                ->findOrFail((int)$positionParam);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'Nama jabatan tidak boleh kosong',
        ]);

        // Check uniqueness within workspace
        $exists = Position::where('workspace_id', $workspace->id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $position->id)
            ->exists();
        
        if ($exists) {
            return back()->withErrors(['name' => 'Nama jabatan sudah ada di workspace ini'])->withInput();
        }

        $position->update($validated);

        // Clear positions cache for this workspace
        Cache::forget("positions_{$workspace->id}");
        Cache::forget("positions_count_{$workspace->id}");

        return redirect()
            ->route('workspace.positions.index', ['workspace' => $workspace->slug])
            ->with('success', 'Data berhasil diedit');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $position Position ID or Position model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $position)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get position from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $positionParam = $routeParams['position'] ?? $position;
        
        // If it's already a Position model instance, use it; otherwise find by ID
        if ($positionParam instanceof Position) {
            $position = $positionParam;
            // Verify workspace access
            if ($position->workspace_id !== $workspace->id) {
                abort(404, 'Position not found');
            }
        } else {
            $position = Position::where('workspace_id', $workspace->id)
                ->findOrFail((int)$positionParam);
        }

        // Check if position has employees
        if ($position->employees()->where('workspace_id', $workspace->id)->count() > 0) {
            return redirect()
                ->route('workspace.positions.index', ['workspace' => $workspace->slug])
                ->with('error', 'Tidak dapat menghapus jabatan yang masih memiliki pegawai');
        }

        $position->delete();

        // Clear positions cache for this workspace
        Cache::forget("positions_{$workspace->id}");
        Cache::forget("positions_count_{$workspace->id}");

        return redirect()
            ->route('workspace.positions.index', ['workspace' => $workspace->slug])
            ->with('success', 'Data berhasil dihapus (dapat dipulihkan)');
    }

    /**
     * Restore a soft-deleted position
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $position Position ID or Position model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $position)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get position from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $positionParam = $routeParams['position'] ?? $position;
        
        // If it's already a Position model instance, use it; otherwise find by ID
        if ($positionParam instanceof Position) {
            $position = $positionParam;
            // Verify workspace access
            if ($position->workspace_id !== $workspace->id) {
                abort(404, 'Position not found');
            }
            $position = Position::where('workspace_id', $workspace->id)
                ->withTrashed()
                ->findOrFail($position->id);
        } else {
            $position = Position::where('workspace_id', $workspace->id)
                ->withTrashed()
                ->findOrFail((int)$positionParam);
        }
        $position->restore();

        // Clear positions cache for this workspace
        Cache::forget("positions_{$workspace->id}");
        Cache::forget("positions_count_{$workspace->id}");

        return redirect()
            ->route('workspace.positions.index', ['workspace' => $workspace->slug])
            ->with('success', 'Data berhasil dipulihkan');
    }
}

