<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (no authentication required)
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->name('api.register');

// Protected API routes (require Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    // Get authenticated user
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // File streaming routes (MinIO access) - require token
    Route::get('/storage/files/{file}', [StorageController::class, 'streamFile'])->name('api.storage.files');
    Route::get('/storage/assets/{asset}/image', [StorageController::class, 'streamAssetImage'])->name('api.storage.assets.image');
    Route::get('/storage/employees/{employee}/photo', [StorageController::class, 'streamEmployeePhoto'])->name('api.storage.employees.photo');
    Route::get('/storage/workspaces/{workspace_id}/logo', [StorageController::class, 'streamWorkspaceLogo'])->name('api.storage.workspaces.logo');

    // Workspace-scoped API routes
    Route::prefix('workspaces/{workspace}')->middleware('workspace')->group(function () {
        // Public read routes (all authenticated users can access)
        // These routes automatically filter by user's employee_id for regular users (handled in controllers)
        Route::get('/employees', [EmployeeController::class, 'index'])->name('api.workspace.employees.index');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('api.workspace.employees.show');

        Route::get('/positions', [PositionController::class, 'index'])->name('api.workspace.positions.index');
        Route::get('/positions/{position}', [PositionController::class, 'show'])->name('api.workspace.positions.show');

        Route::get('/files', [FileController::class, 'index'])->name('api.workspace.files.index');
        Route::get('/files/{file}', [FileController::class, 'show'])->name('api.workspace.files.show');
        Route::get('/files/{file}/download', [FileController::class, 'download'])->name('api.workspace.files.download');

        Route::get('/assets', [AssetController::class, 'index'])->name('api.workspace.assets.index');
        Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('api.workspace.assets.show');

        // Admin-only API routes (create, update, delete)
        Route::middleware('checkLevel')->group(function () {
            // Employees API (admin only)
            Route::post('/employees', [EmployeeController::class, 'store'])->name('api.workspace.employees.store');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('api.workspace.employees.update');
            Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('api.workspace.employees.destroy');
            Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('api.workspace.employees.restore');

            // Positions API (admin only)
            Route::post('/positions', [PositionController::class, 'store'])->name('api.workspace.positions.store');
            Route::put('/positions/{position}', [PositionController::class, 'update'])->name('api.workspace.positions.update');
            Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('api.workspace.positions.destroy');
            Route::post('/positions/{position}/restore', [PositionController::class, 'restore'])->name('api.workspace.positions.restore');

            // Files API (admin only)
            Route::post('/files', [FileController::class, 'store'])->name('api.workspace.files.store');
            Route::put('/files/{file}', [FileController::class, 'update'])->name('api.workspace.files.update');
            Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('api.workspace.files.destroy');
            Route::post('/files/{file}/restore', [FileController::class, 'restore'])->name('api.workspace.files.restore');

            // Assets API (admin only)
            Route::post('/assets', [AssetController::class, 'store'])->name('api.workspace.assets.store');
            Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('api.workspace.assets.update');
            Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('api.workspace.assets.destroy');
            Route::post('/assets/{asset}/restore', [AssetController::class, 'restore'])->name('api.workspace.assets.restore');
            Route::post('/assets/{asset}/assign', [AssetController::class, 'assign'])->name('api.workspace.assets.assign');
            Route::post('/assets/{asset}/unassign', [AssetController::class, 'unassign'])->name('api.workspace.assets.unassign');
        });
    });
});
