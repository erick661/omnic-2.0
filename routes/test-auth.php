<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Ruta de prueba con middleware para verificar autenticación
Route::middleware(['centralized.auth'])->get('/test-auth', function (Request $request) {
    $user = Auth::user();
    
    return response()->json([
        'authenticated' => Auth::check(),
        'user' => $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'nickname' => $user->nickname,
            'email' => $user->email,
            'role' => $user->role,
        ] : null,
        'message' => 'Authentication successful!'
    ]);
});