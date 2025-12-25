<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\ActivityLog;

class PasswordController extends Controller
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
     * Show the form for changing password.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function showChangeForm(Request $request)
    {
        $workspace = $request->get('workspace');
        return view('auth.change-password', compact('workspace'));
    }

    /**
     * Update the user's password.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $workspace = $request->get('workspace');
        
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Password saat ini harus diisi',
            'password.required' => 'Password baru harus diisi',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        $user = Auth::user();

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak benar']);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Log password change in activity log
        ActivityLog::create([
            'user_id' => $user->id,
            'workspace_id' => $user->workspace_id,
            'model_type' => get_class($user),
            'model_id' => $user->id,
            'action' => 'updated',
            'old_values' => ['password' => '***'], // Don't log actual password
            'new_values' => ['password' => '***'], // Don't log actual password
            'description' => "Password untuk user '{$user->name}' telah diubah",
        ]);

        $redirectRoute = $workspace 
            ? route('workspace.dashboard', ['workspace' => $workspace->slug])
            : route('home');

        return redirect($redirectRoute)
            ->with('success', 'Password berhasil diubah');
    }
}

