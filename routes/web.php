<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\StorageController;
use Illuminate\Support\Facades\Auth;

// Landing page
Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
    Route::get('/home', [HomeController::class, 'index']);

    // Public routes (all authenticated users can view)
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('employees/export/pdf', [EmployeeController::class, 'exportPdf'])->name('employees.export.pdf');
    Route::get('employees/export/excel', [EmployeeController::class, 'exportExcel'])->name('employees.export.excel');
    Route::get('positions', [PositionController::class, 'index'])->name('positions.index');
    Route::get('files', [FileController::class, 'index'])->name('files.index');
    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('assets', [AssetController::class, 'index'])->name('assets.index');

    // Storage streaming routes (for MinIO files)
    Route::get('storage/files/{file}', [StorageController::class, 'streamFile'])->name('storage.files');
    Route::get('storage/assets/{asset}/image', [StorageController::class, 'streamAssetImage'])->name('storage.assets.image');
    Route::get('storage/employees/{employee}/photo', [StorageController::class, 'streamEmployeePhoto'])->name('storage.employees.photo');

    // Admin only routes (create, edit, delete) - MUST be before parameterized routes
    Route::middleware('checkLevel')->group(function () {
        // Employee routes - specific routes first
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        // Position routes - specific routes first
        Route::get('positions/create', [PositionController::class, 'create'])->name('positions.create');
        Route::post('positions', [PositionController::class, 'store'])->name('positions.store');
        Route::get('positions/{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('positions/{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');

        // Restore routes
        Route::post('employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
        Route::post('positions/{position}/restore', [PositionController::class, 'restore'])->name('positions.restore');

        // Activity logs (admin only)
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');

        // File management (admin only) - specific routes first
        Route::get('files/create', [FileController::class, 'create'])->name('files.create');
        Route::post('files', [FileController::class, 'store'])->name('files.store');
        Route::get('files/{file}/edit', [FileController::class, 'edit'])->name('files.edit');
        Route::put('files/{file}', [FileController::class, 'update'])->name('files.update');
        Route::delete('files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
        Route::post('files/{file}/restore', [FileController::class, 'restore'])->name('files.restore');

        // Asset management (admin only) - specific routes first
        Route::get('assets/create', [AssetController::class, 'create'])->name('assets.create');
        Route::post('assets', [AssetController::class, 'store'])->name('assets.store');
        Route::get('assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
        Route::post('assets/{asset}/restore', [AssetController::class, 'restore'])->name('assets.restore');
        Route::post('assets/{asset}/assign', [AssetController::class, 'assign'])->name('assets.assign');
        Route::post('assets/{asset}/unassign', [AssetController::class, 'unassign'])->name('assets.unassign');
    });

    // Parameterized routes (show) - must be after specific routes
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('positions/{position}', [PositionController::class, 'show'])->name('positions.show');
    Route::get('files/{file}', [FileController::class, 'show'])->name('files.show');
    Route::get('assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
});

