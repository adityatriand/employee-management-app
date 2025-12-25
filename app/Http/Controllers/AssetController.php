<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\AssetAssignment;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetController extends Controller
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

        $user = auth()->user();
        $query = Asset::where('workspace_id', $workspace->id)
            ->with(['assignedEmployee', 'assigner'])
            ->orderBy('created_at', 'desc');

        // For regular users (level 0), automatically filter to their assigned assets
        if ($user->level == 0) {
            $employee = Employee::where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->first();

            if ($employee) {
                $query->where('assigned_to', $employee->id);
            } else {
                // If no employee record, return empty result
                $query->whereRaw('1 = 0');
            }
        } else {
            // For admins, allow filters

            // Filters
            if ($request->filled('asset_type')) {
                $query->where('asset_type', $request->asset_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }

            if ($request->filled('department')) {
                $query->where('department', $request->department);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('asset_tag', 'like', '%' . $search . '%')
                    ->orWhere('serial_number', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        $assets = $query->paginate(15);

        // Only get filter data for admins
        if ($user->level == 1) {
            $employees = Employee::where('workspace_id', $workspace->id)->orderBy('name')->get();

            // Get filter counts
            $hasFilters = $request->filled(['search', 'asset_type', 'status', 'assigned_to', 'department']);
            $filterCount = collect($request->only(['search', 'asset_type', 'status', 'assigned_to', 'department']))->filter()->count();

            // Asset types for filter (scoped to workspace)
            $assetTypes = Asset::where('workspace_id', $workspace->id)
                ->distinct()
                ->pluck('asset_type')
                ->sort()
                ->values();
        } else {
            $employees = collect();
            $hasFilters = false;
            $filterCount = 0;
            $assetTypes = collect();
        }

        return view('assets.index', compact('workspace', 'assets', 'employees', 'assetTypes', 'hasFilters', 'filterCount'));
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

        $employees = Employee::where('workspace_id', $workspace->id)->orderBy('name')->get();
        $selectedEmployeeId = $request->query('employee_id');
        $assetTag = Asset::generateAssetTag();

        return view('assets.create', compact('workspace', 'employees', 'selectedEmployeeId', 'assetTag'));
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
            'asset_tag' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'asset_type' => 'required|string|max:50',
            'serial_number' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,assigned,maintenance,retired,lost',
            'current_location' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:100',
            'warranty_expiry' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:employees,id',
            'assigned_date' => 'nullable|date|required_with:assigned_to',
        ], [
            'name.required' => 'Nama aset tidak boleh kosong',
            'asset_type.required' => 'Tipe aset harus diisi',
            'status.required' => 'Status harus diisi',
            'assigned_to.exists' => 'Pegawai tidak valid',
            'assigned_date.required_with' => 'Tanggal penugasan harus diisi jika aset ditugaskan',
        ]);

        // Generate asset tag if not provided
        if (empty($validated['asset_tag'])) {
            $validated['asset_tag'] = Asset::generateAssetTag();
        }

        // Handle image upload to MinIO
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'assets/' . $fileName;

            $workspaceDisk = $workspace->getStorageDisk();
            $workspaceDisk->put($path, file_get_contents($file->getRealPath()));

            // Create file record for the image
            File::create([
                'name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => 'document',
                'category' => 'asset_image',
                'description' => 'Foto aset untuk ' . $validated['name'],
                'employee_id' => null,
                'workspace_id' => $workspace->id,
                'uploaded_by' => auth()->id(),
                'workspace_id' => $workspace->id,
            ]);

            $validated['image'] = $path;
        }

        // Set assigned_by if asset is assigned
        if (!empty($validated['assigned_to'])) {
            $validated['assigned_by'] = auth()->id();
        }

        $validated['workspace_id'] = $workspace->id;
        $asset = Asset::create($validated);

        // Create assignment record if assigned
        if (!empty($validated['assigned_to'])) {
            AssetAssignment::create([
                'asset_id' => $asset->id,
                'employee_id' => $validated['assigned_to'],
                'assigned_by' => auth()->id(),
                'assigned_at' => $validated['assigned_date'] ?? now(),
                'notes' => 'Aset ditugaskan saat pembuatan',
            ]);
        }

        return redirect()
            ->route('workspace.assets.index', ['workspace' => $workspace->slug])
            ->with('success', 'Aset berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $asset Asset ID or Asset model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $asset)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get asset from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $assetParam = $routeParams['asset'] ?? $asset;

        // If it's already an Asset model instance, use it; otherwise find by ID
        if ($assetParam instanceof Asset) {
            $asset = $assetParam;
            // Verify workspace access
            if ($asset->workspace_id !== $workspace->id) {
                abort(404, 'Asset not found');
            }
        } else {
            $asset = Asset::where('workspace_id', $workspace->id)
                ->findOrFail((int)$assetParam);
        }

        $asset->load(['assignedEmployee', 'assigner', 'assignments.employee', 'assignments.assigner', 'assignments.returner']);

        // Get activity logs for this asset
        $activityLogs = \App\Models\ActivityLog::where('model_type', get_class($asset))
            ->where('model_id', $asset->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('assets.show', compact('workspace', 'asset', 'activityLogs'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $asset = Asset::where('workspace_id', $workspace->id)->findOrFail($id);
        $employees = Employee::where('workspace_id', $workspace->id)->orderBy('name')->get();

        return view('assets.edit', compact('workspace', 'asset', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $asset Asset ID or Asset model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $asset)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get asset from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $assetParam = $routeParams['asset'] ?? $asset;

        // If it's already an Asset model instance, use it; otherwise find by ID
        if ($assetParam instanceof Asset) {
            $asset = $assetParam;
            // Verify workspace access
            if ($asset->workspace_id !== $workspace->id) {
                abort(404, 'Asset not found');
            }
        } else {
            $asset = Asset::where('workspace_id', $workspace->id)
                ->findOrFail((int)$assetParam);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_tag' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'asset_type' => 'required|string|max:50',
            'serial_number' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,assigned,maintenance,retired,lost',
            'current_location' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:100',
            'warranty_expiry' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'Nama aset tidak boleh kosong',
            'asset_type.required' => 'Tipe aset harus diisi',
            'status.required' => 'Status harus diisi',
        ]);

        // Handle image update to MinIO
        if ($request->hasFile('image')) {
            $workspaceDisk = $workspace->getStorageDisk();

            // Delete old image from MinIO if it exists
            if ($asset->image && $workspaceDisk->exists($asset->image)) {
                $workspaceDisk->delete($asset->image);
                // Also soft delete the old file record if it exists
                File::where('file_path', $asset->image)->where('workspace_id', $workspace->id)->delete();
            }

            $file = $request->file('image');
            $fileName = Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'assets/' . $fileName;

            $workspaceDisk->put($path, file_get_contents($file->getRealPath()));

            // Create new file record for the updated image
            File::create([
                'name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_type' => 'document',
                'category' => 'asset_image',
                'description' => 'Foto aset terbaru untuk ' . $validated['name'],
                'employee_id' => null,
                'uploaded_by' => auth()->id(),
                'workspace_id' => $workspace->id,
            ]);

            $validated['image'] = $path;
        }

        // Check asset_tag uniqueness within workspace
        if ($request->filled('asset_tag') && $validated['asset_tag'] !== $asset->asset_tag) {
            $exists = Asset::where('workspace_id', $workspace->id)
                ->where('asset_tag', $validated['asset_tag'])
                ->where('id', '!=', $asset->id)
                ->exists();
            if ($exists) {
                return back()->withErrors(['asset_tag' => 'Asset tag sudah digunakan di workspace ini'])->withInput();
            }
        }

        $asset->update($validated);

        return redirect()
            ->route('workspace.assets.index', ['workspace' => $workspace->slug])
            ->with('success', 'Aset berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $asset Asset ID or Asset model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $asset)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get asset from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $assetParam = $routeParams['asset'] ?? $asset;

        // If it's already an Asset model instance, use it; otherwise find by ID
        if ($assetParam instanceof Asset) {
            $asset = $assetParam;
            // Verify workspace access
            if ($asset->workspace_id !== $workspace->id) {
                abort(404, 'Asset not found');
            }
        } else {
            $asset = Asset::where('workspace_id', $workspace->id)
                ->findOrFail((int)$assetParam);
        }

        // Check if asset is currently assigned
        if ($asset->status === 'assigned' && $asset->assigned_to) {
            return redirect()
                ->route('workspace.assets.index', ['workspace' => $workspace->slug])
                ->with('error', 'Tidak dapat menghapus aset yang sedang ditugaskan. Kembalikan aset terlebih dahulu.');
        }

        // Soft delete the asset
        $asset->delete();

        return redirect()
            ->route('workspace.assets.index', ['workspace' => $workspace->slug])
            ->with('success', 'Aset berhasil dihapus (dapat dipulihkan)');
    }

    /**
     * Restore the specified soft-deleted resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $asset Asset ID or Asset model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $asset)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get asset from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $assetParam = $routeParams['asset'] ?? $asset;

        // If it's already an Asset model instance, use it; otherwise find by ID
        if ($assetParam instanceof Asset) {
            $asset = $assetParam;
            // Verify workspace access
            if ($asset->workspace_id !== $workspace->id) {
                abort(404, 'Asset not found');
            }
            $asset = Asset::where('workspace_id', $workspace->id)
                ->withTrashed()
                ->findOrFail($asset->id);
        } else {
            $asset = Asset::where('workspace_id', $workspace->id)
                ->withTrashed()
                ->findOrFail((int)$assetParam);
        }
        $asset->restore();

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'workspace_id' => $asset->workspace_id,
            'model_type' => get_class($asset),
            'model_id' => $asset->id,
            'action' => 'restored',
            'description' => 'Aset "' . $asset->name . '" dipulihkan.',
        ]);

        return redirect()
            ->route('workspace.assets.index', ['workspace' => $workspace->slug])
            ->with('success', 'Aset berhasil dipulihkan');
    }

    /**
     * Assign asset to employee.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $asset Asset ID or Asset model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function assign(Request $request, $asset)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get asset from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $assetParam = $routeParams['asset'] ?? $asset;

        // If it's already an Asset model instance, use it; otherwise find by ID
        if ($assetParam instanceof Asset) {
            $asset = $assetParam;
            // Verify workspace access
            if ($asset->workspace_id !== $workspace->id) {
                abort(404, 'Asset not found');
            }
        } else {
            $asset = Asset::where('workspace_id', $workspace->id)
                ->findOrFail((int)$assetParam);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'required|date',
            'notes' => 'nullable|string',
        ], [
            'employee_id.required' => 'Pegawai harus dipilih',
            'employee_id.exists' => 'Pegawai tidak valid',
            'assigned_date.required' => 'Tanggal penugasan harus diisi',
        ]);

        // Verify employee belongs to workspace
        $employee = Employee::where('id', $validated['employee_id'])
            ->where('workspace_id', $workspace->id)
            ->first();
        if (!$employee) {
            return back()->withErrors(['employee_id' => 'Pegawai tidak valid untuk workspace ini'])->withInput();
        }

        // Check if asset is available
        if ($asset->status !== 'available' && $asset->status !== 'assigned') {
            return redirect()
                ->back()
                ->with('error', 'Aset tidak dapat ditugaskan. Status: ' . $asset->status_label);
        }

        // If already assigned to someone else, unassign first
        if ($asset->assigned_to && $asset->assigned_to != $validated['employee_id']) {
            // Return the previous assignment
            $previousAssignment = AssetAssignment::where('asset_id', $asset->id)
                ->whereNull('returned_at')
                ->first();

            if ($previousAssignment) {
                $previousAssignment->update([
                    'returned_at' => now(),
                    'returned_by' => auth()->id(),
                    'notes' => $previousAssignment->notes . ' | Dikembalikan karena penugasan baru',
                ]);
            }
        }

        // Update asset
        $asset->update([
            'status' => 'assigned',
            'assigned_to' => $validated['employee_id'],
            'assigned_date' => $validated['assigned_date'],
            'assigned_by' => auth()->id(),
        ]);

        // Create assignment record
        AssetAssignment::create([
            'asset_id' => $asset->id,
            'employee_id' => $validated['employee_id'],
            'assigned_by' => auth()->id(),
            'assigned_at' => $validated['assigned_date'],
            'notes' => $validated['notes'] ?? 'Aset ditugaskan',
        ]);

        return redirect()
            ->route('workspace.assets.show', ['workspace' => $workspace->slug, 'asset' => $asset->id])
            ->with('success', 'Aset berhasil ditugaskan');
    }

    /**
     * Unassign/return asset from employee.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $asset Asset ID or Asset model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function unassign(Request $request, $asset)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Get asset from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $assetParam = $routeParams['asset'] ?? $asset;

        // If it's already an Asset model instance, use it; otherwise find by ID
        if ($assetParam instanceof Asset) {
            $asset = $assetParam;
            // Verify workspace access
            if ($asset->workspace_id !== $workspace->id) {
                abort(404, 'Asset not found');
            }
        } else {
            $asset = Asset::where('workspace_id', $workspace->id)
                ->findOrFail((int)$assetParam);
        }

        if (!$asset->assigned_to) {
            return redirect()
                ->back()
                ->with('error', 'Aset tidak sedang ditugaskan');
        }

        // Update assignment record
        $assignment = AssetAssignment::where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->first();

        if ($assignment) {
            $assignment->update([
                'returned_at' => now(),
                'returned_by' => auth()->id(),
            ]);
        }

        // Update asset
        $asset->update([
            'status' => 'available',
            'assigned_to' => null,
            'assigned_date' => null,
            'assigned_by' => null,
        ]);

        return redirect()
            ->route('workspace.assets.show', ['workspace' => $workspace->slug, 'asset' => $asset->id])
            ->with('success', 'Aset berhasil dikembalikan');
    }
}

