<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkLevel'); // Admin only
    }

    /**
     * Show the settings form
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $passwordRequirements = Setting::getPasswordRequirements($workspace->id);
        $defaultPassword = Setting::get('employee_default_password', '', $workspace->id);

        return view('settings.index', compact('workspace', 'passwordRequirements', 'defaultPassword'));
    }

    /**
     * Update password settings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePasswordSettings(Request $request)
    {
        $workspace = $request->get('workspace');
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }

        $validated = $request->validate([
            'password_min_length' => 'required|integer|min:6|max:32',
            'password_require_uppercase' => 'boolean',
            'password_require_lowercase' => 'boolean',
            'password_require_numbers' => 'boolean',
            'password_require_symbols' => 'boolean',
            'employee_default_password' => 'nullable|string',
        ], [
            'password_min_length.required' => 'Panjang password minimum harus diisi',
            'password_min_length.integer' => 'Panjang password harus berupa angka',
            'password_min_length.min' => 'Panjang password minimum adalah 6 karakter',
            'password_min_length.max' => 'Panjang password maksimum adalah 32 karakter',
        ]);

        // Validate default password if provided (must meet current requirements)
        if ($request->filled('employee_default_password')) {
            $passwordRule = \App\Helpers\PasswordHelper::getPasswordRule($workspace->id);
            $passwordValidator = \Illuminate\Support\Facades\Validator::make(
                ['password' => $request->employee_default_password],
                ['password' => ['required', $passwordRule]]
            );
            
            if ($passwordValidator->fails()) {
                return back()
                    ->withErrors($passwordValidator)
                    ->withInput()
                    ->with('password_error', 'Password default tidak memenuhi persyaratan. ' . \App\Helpers\PasswordHelper::getPasswordDescription($workspace->id));
            }
        }

        // Update settings for this workspace
        Setting::set('password_min_length', $validated['password_min_length'], $workspace->id, 'integer');
        Setting::set('password_require_uppercase', $request->has('password_require_uppercase') ? 1 : 0, $workspace->id, 'boolean');
        Setting::set('password_require_lowercase', $request->has('password_require_lowercase') ? 1 : 0, $workspace->id, 'boolean');
        Setting::set('password_require_numbers', $request->has('password_require_numbers') ? 1 : 0, $workspace->id, 'boolean');
        Setting::set('password_require_symbols', $request->has('password_require_symbols') ? 1 : 0, $workspace->id, 'boolean');
        
        // Update default password (empty means auto-generate)
        $defaultPassword = $request->filled('employee_default_password') ? $request->employee_default_password : '';
        Setting::set('employee_default_password', $defaultPassword, $workspace->id, 'string');

        return redirect()
            ->route('workspace.settings.index', ['workspace' => $workspace->slug])
            ->with('success', 'Pengaturan password berhasil diperbarui');
    }
}

