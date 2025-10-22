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
    Volt::route('/agente', 'inbox.agente')->name('agente');
    
    // Rutas de componentes de canal dedicados
    Volt::route('/case/{caseId}/email', 'channels.email-response')->name('case.email');
    Volt::route('/case/{caseId}/whatsapp', 'channels.whatsapp-response')->name('case.whatsapp');
    Volt::route('/case/{caseId}/sms', 'channels.sms-response')->name('case.sms');
    Volt::route('/case/{caseId}/chat', 'channels.chat-response')->name('case.chat');
    Volt::route('/case/{caseId}/phone', 'channels.phone-response')->name('case.phone');
});

// Ruta de logout (sin middleware)
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Rutas para autenticación OAuth2 con Gmail (protegidas)
Route::middleware(['centralized.auth'])->group(function () {
    Route::get('/auth/gmail', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.gmail');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.gmail.callback');
});
