<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use Illuminate\Support\Facades\Auth;


Auth::routes();

Route::group(['middleware'=>'auth'], function(){
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index']);
    Route::resource('jabatan',Controllers\JabatanController::class);
    Route::resource('pegawai',Controllers\PegawaiController::class);
});

