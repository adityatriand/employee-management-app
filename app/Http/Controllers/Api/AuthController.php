<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Helpers\PasswordHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle API login request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'workspace_slug' => 'required|string|exists:workspaces,slug',
        ]);

        $workspace = Workspace::where('slug', $request->workspace_slug)->first();
        
        if (!$workspace) {
            return response()->json([
                'message' => 'Workspace not found'
            ], 404);
        }

        // Check if user exists and belongs to workspace
        $user = User::where('email', $request->email)
            ->where('workspace_id', $workspace->id)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all existing tokens (optional - for security)
        // $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->makeHidden(['password', 'remember_token']),
            'workspace' => $workspace,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Handle API registration request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', PasswordHelper::getPasswordRule()],
            'workspace_name' => ['required', 'string', 'max:255', 'unique:workspaces,name'],
            'workspace_slug' => ['required', 'string', 'max:255', 'unique:workspaces,slug', 'regex:/^[a-z0-9-]+$/'],
            'workspace_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ], [
            'workspace_slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'password.required' => 'Password harus diisi',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'level' => 1, // Admin (workspace owner)
        ]);

        // Create workspace
        $logoPath = null;
        if ($request->hasFile('workspace_logo')) {
            $logoFile = $request->file('workspace_logo');
            $logoName = \Illuminate\Support\Str::uuid() . '.' . $logoFile->getClientOriginalExtension();
            \Illuminate\Support\Facades\Storage::disk('minio')->put('workspaces/logos/' . $logoName, file_get_contents($logoFile->getRealPath()));
            $logoPath = 'workspaces/logos/' . $logoName;
        }

        $workspace = Workspace::create([
            'name' => $request->workspace_name,
            'slug' => $request->workspace_slug,
            'logo' => $logoPath,
            'owner_id' => $user->id,
        ]);

        // Update user with workspace
        $user->update(['workspace_id' => $workspace->id]);

        // Create MinIO bucket for workspace
        try {
            $bucketService = new \App\Services\MinioBucketService();
            $bucketName = $bucketService->getBucketName($workspace->slug);
            $bucketService->createBucket($bucketName);
        } catch (\Exception $e) {
            \Log::error("Error creating MinIO bucket for workspace '{$workspace->slug}': " . $e->getMessage());
        }

        // Create token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->makeHidden(['password', 'remember_token']),
            'workspace' => $workspace,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Handle logout request (revoke token)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->makeHidden(['password', 'remember_token']),
            'workspace' => $request->user()->workspace,
        ]);
    }
}

