<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Each screen is its own page, so only one set of partials is mounted at a time
    Route::view('/', 'screens.dashboard')->name('landing-page');
    Route::view('/defect', 'screens.defect')->name('defect');
    Route::view('/rework', 'screens.rework')->name('rework');
});