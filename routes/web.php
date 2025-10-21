<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;

Volt::route('/', 'users.index');
Volt::route('/supervisor', 'inbox.supervisor');
Volt::route('/agente', 'inbox.agente');

// Rutas para autenticación OAuth2 con Gmail
Route::get('/auth/gmail', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.gmail');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.gmail.callback');
