<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\MinioBucketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    /**
     * Show the workspace setup form (after registration).
     */
    public function create()
    {
        // Check if user already has a workspace
        if (Auth::user()->workspace_id) {
            return redirect()->route('workspace.dashboard', ['workspace' => Auth::user()->workspace->slug]);
        }

        return view('workspace.create');
    }

    /**
     * Store a newly created workspace.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:workspaces,name'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = Auth::user();

        // Check if user already has a workspace
        if ($user->workspace_id) {
            return redirect()->route('workspace.dashboard', ['workspace' => $user->workspace->slug])
                ->with('error', 'Anda sudah memiliki workspace');
        }

        // Generate unique slug
        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Handle logo upload
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoName = Str::uuid() . '.' . $logoFile->getClientOriginalExtension();
            // Workspace logos are stored in the default MinIO bucket
            Storage::disk('minio')->put('workspaces/logos/' . $logoName, file_get_contents($logoFile->getRealPath()));
            $logoPath = 'workspaces/logos/' . $logoName;
        }

        // Create workspace
        $workspace = Workspace::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'logo' => $logoPath,
            'owner_id' => $user->id,
        ]);

        // Create MinIO bucket for this workspace
        try {
            $bucketService = new MinioBucketService();
            $bucketName = $bucketService->getBucketName($slug);
            if ($bucketService->createBucket($bucketName)) {
                \Log::info("MinIO bucket '{$bucketName}' created for workspace '{$workspace->name}'");
            } else {
                \Log::warning("Failed to create MinIO bucket '{$bucketName}' for workspace '{$workspace->name}'");
            }
        } catch (\Exception $e) {
            \Log::error("Error creating MinIO bucket for workspace: " . $e->getMessage());
            // Continue even if bucket creation fails - it can be created manually later
        }

        // Update user to be admin and assign to workspace
        $user->update([
            'workspace_id' => $workspace->id,
            'level' => 1, // Admin
        ]);

        return redirect()->route('workspace.dashboard', ['workspace' => $workspace->slug])
            ->with('success', 'Workspace berhasil dibuat!');
    }

    /**
     * Show the form for editing the workspace.
     */
    public function edit(Request $request)
    {
        $workspace = $request->get('workspace'); // Set by WorkspaceMiddleware
        
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Only workspace owner/admin can edit
        if (Auth::user()->workspace_id !== $workspace->id || Auth::user()->level != 1) {
            abort(403, 'Unauthorized');
        }

        return view('workspace.edit', compact('workspace'));
    }

    /**
     * Update the workspace.
     */
    public function update(Request $request)
    {
        $workspace = $request->get('workspace'); // Set by WorkspaceMiddleware
        
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        // Only workspace owner/admin can update
        if (Auth::user()->workspace_id !== $workspace->id || Auth::user()->level != 1) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:workspaces,name,' . $workspace->id],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        // Handle logo upload
        $logoPath = $workspace->logo; // Keep existing logo by default
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($workspace->logo) {
                try {
                    Storage::disk('minio')->delete($workspace->logo);
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete old workspace logo: ' . $e->getMessage());
                }
            }

            // Upload new logo
            $logoFile = $request->file('logo');
            $logoName = Str::uuid() . '.' . $logoFile->getClientOriginalExtension();
            // Workspace logos are stored in the default MinIO bucket
            Storage::disk('minio')->put('workspaces/logos/' . $logoName, file_get_contents($logoFile->getRealPath()));
            $logoPath = 'workspaces/logos/' . $logoName;
        }

        // Update workspace (slug is not editable as it's used in URLs)
        $workspace->update([
            'name' => $validated['name'],
            'logo' => $logoPath,
        ]);

        return redirect()->route('workspace.dashboard', ['workspace' => $workspace->slug])
            ->with('success', 'Workspace berhasil diupdate!');
    }
}

