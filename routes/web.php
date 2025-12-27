<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Auth;

// Landing page (only for guests)
Route::get('/', function () {
    return view('welcome');
})->middleware('guest')->name('welcome');

// Public authentication routes (no workspace)
Route::get('/register', function () {
    return view('auth.register');
})->middleware('guest')->name('register');

Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])
    ->middleware(['guest', 'throttle:register']);

// Workspace setup (after registration, before workspace exists)
Route::middleware(['auth'])->group(function () {
    Route::get('/workspace/setup', [WorkspaceController::class, 'create'])->name('workspace.setup');
    Route::post('/workspace/setup', [WorkspaceController::class, 'store'])->name('workspace.store');
});

// Workspace-prefixed routes
Route::prefix('{workspace}')->group(function () {
    // Workspace login routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('workspace.login');
        Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])
            ->middleware('throttle:login')
            ->name('workspace.login.post');
    });

    // Authenticated workspace routes
    Route::middleware(['auth', 'workspace'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [HomeController::class, 'index'])->name('workspace.dashboard');
        Route::get('/home', [HomeController::class, 'index'])->name('workspace.home');

        // Password change (all authenticated users)
        Route::get('/password/change', [PasswordController::class, 'showChangeForm'])->name('workspace.password.change');
        Route::post('/password/change', [PasswordController::class, 'update'])->name('workspace.password.update');

        // Profile edit (regular users only - limited fields)
        Route::get('/profile/edit', [EmployeeController::class, 'editProfile'])->name('workspace.profile.edit');
        Route::put('/profile', [EmployeeController::class, 'updateProfile'])->name('workspace.profile.update');

        // Workspace settings (admin only)
        Route::middleware('checkLevel')->group(function () {
            Route::get('/workspace/edit', [WorkspaceController::class, 'edit'])->name('workspace.edit');
            Route::put('/workspace', [WorkspaceController::class, 'update'])->name('workspace.update');
        });

        // Storage streaming routes (for MinIO files)
        Route::get('/storage/files/{file}', [StorageController::class, 'streamFile'])->name('workspace.storage.files');
        Route::get('/storage/assets/{asset}/image', [StorageController::class, 'streamAssetImage'])->name('workspace.storage.assets.image');
        Route::get('/storage/employees/{employee}/photo', [StorageController::class, 'streamEmployeePhoto'])->name('workspace.storage.employees.photo');
        Route::get('/storage/workspaces/{workspace_id}/logo', [StorageController::class, 'streamWorkspaceLogo'])->name('workspace.storage.workspaces.logo');

        // Public routes (all authenticated users can view) - index routes
        // These routes automatically filter by user's employee_id for regular users (handled in controllers)
        Route::get('/employees', [EmployeeController::class, 'index'])->name('workspace.employees.index');
        Route::get('/positions', [PositionController::class, 'index'])->name('workspace.positions.index');
        Route::get('/files', [FileController::class, 'index'])->name('workspace.files.index');
        Route::get('/files/{file}/download', [FileController::class, 'download'])->name('workspace.files.download');
        Route::get('/assets', [AssetController::class, 'index'])->name('workspace.assets.index');

        // Admin only routes (create, edit, delete) - MUST be before parameterized routes
        Route::middleware('checkLevel')->group(function () {
            // Employee routes - specific routes first (before /employees/{employee})
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('workspace.employees.create');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('workspace.employees.store');
            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('workspace.employees.edit');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('workspace.employees.update');
            Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('workspace.employees.destroy');
            Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('workspace.employees.restore');
            Route::post('/employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('workspace.employees.reset-password');
            
            // Export routes (admin only)
            Route::get('/employees/export/pdf', [EmployeeController::class, 'exportPdf'])->name('workspace.employees.export.pdf');
            Route::get('/employees/export/excel', [EmployeeController::class, 'exportExcel'])->name('workspace.employees.export.excel');
            Route::get('/employees/export/pdf/download', [EmployeeController::class, 'downloadExportPdf'])->name('workspace.employees.export.pdf.download');
            Route::get('/employees/export/excel/download', [EmployeeController::class, 'downloadExportExcel'])->name('workspace.employees.export.excel.download');

            // Settings (admin only)
            Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('workspace.settings.index');
            Route::post('/settings/password', [App\Http\Controllers\SettingsController::class, 'updatePasswordSettings'])->name('workspace.settings.password.update');

            // Position routes - specific routes first (before /positions/{position})
            Route::get('/positions/create', [PositionController::class, 'create'])->name('workspace.positions.create');
            Route::post('/positions', [PositionController::class, 'store'])->name('workspace.positions.store');
            Route::get('/positions/{position}/edit', [PositionController::class, 'edit'])->name('workspace.positions.edit');
            Route::put('/positions/{position}', [PositionController::class, 'update'])->name('workspace.positions.update');
            Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('workspace.positions.destroy');
            Route::post('/positions/{position}/restore', [PositionController::class, 'restore'])->name('workspace.positions.restore');

            // Activity logs (admin only)
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('workspace.activity-logs.index');
            Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('workspace.activity-logs.show');

            // File management (admin only) - specific routes first (before /files/{file})
            Route::get('/files/create', [FileController::class, 'create'])->name('workspace.files.create');
            Route::post('/files', [FileController::class, 'store'])->name('workspace.files.store');
            Route::get('/files/{file}/edit', [FileController::class, 'edit'])->name('workspace.files.edit');
            Route::put('/files/{file}', [FileController::class, 'update'])->name('workspace.files.update');
            Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('workspace.files.destroy');
            Route::post('/files/{file}/restore', [FileController::class, 'restore'])->name('workspace.files.restore');

            // Asset management (admin only) - specific routes first (before /assets/{asset})
            Route::get('/assets/create', [AssetController::class, 'create'])->name('workspace.assets.create');
            Route::post('/assets', [AssetController::class, 'store'])->name('workspace.assets.store');
            Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('workspace.assets.edit');
            Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('workspace.assets.update');
            Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('workspace.assets.destroy');
            Route::post('/assets/{asset}/restore', [AssetController::class, 'restore'])->name('workspace.assets.restore');
            Route::post('/assets/{asset}/assign', [AssetController::class, 'assign'])->name('workspace.assets.assign');
            Route::post('/assets/{asset}/unassign', [AssetController::class, 'unassign'])->name('workspace.assets.unassign');
        });

        // Regular user routes (limited access) - show routes MUST come after create/edit routes
        Route::middleware('auth')->group(function () {
            // Regular users can view their own employee detail
            Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('workspace.employees.show');
            Route::get('/positions/{position}', [PositionController::class, 'show'])->name('workspace.positions.show');
            Route::get('/files/{file}', [FileController::class, 'show'])->name('workspace.files.show');
            Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('workspace.assets.show');
        });
    });

    // Logout
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->middleware('auth')->name('workspace.logout');
});
