<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\AuthController;

// Rutas protegidas con middleware de autenticación centralizada
Route::middleware(['centralized.auth'])->group(function () {
    Volt::route('/', 'users.index');
    Volt::route('/dashboard', 'dashboard.index')->name('dashboard');
    Volt::route('/supervisor', 'inbox.supervisor');
    Volt::route('/agente', 'inbox.agente');
});

// Ruta de logout (sin middleware)
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Rutas para autenticación OAuth2 con Gmail (protegidas)
Route::middleware(['centralized.auth'])->group(function () {
    Route::get('/auth/gmail', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.gmail');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.gmail.callback');
});
