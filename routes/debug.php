<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Ruta de prueba sin middleware para diagnosticar cookies compartidas
Route::get('/debug-auth', function (Request $request) {
    $cookieName = config('centralized_auth.cookie_name');
    $cookie = $request->cookie($cookieName, null, false); // false = no desencriptar
    
    $decoded = null;
    if ($cookie) {
        try {
            $parts = explode('.', $cookie);
            if (count($parts) === 3) {
                $payload = base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT));
                $decoded = json_decode($payload, true);
            }
        } catch (\Exception $e) {
            $decoded = ['error' => $e->getMessage()];
        }
    }
    
    return response()->json([
        'current_url' => $request->fullUrl(),
        'host' => $request->getHost(),
        'cookie_found' => !empty($cookie),
        'cookie_name' => $cookieName,
        'cookie_preview' => $cookie ? substr($cookie, 0, 50) . '...' : null,
        'decoded_payload' => $decoded,
        'all_cookies' => array_keys($request->cookies->all()),
        'config' => [
            'intra_url' => config('centralized_auth.intra_url'),
            'sistema_name' => config('centralized_auth.sistema_name'),
            'cookie_domain' => config('centralized_auth.cookie_domain'),
            'session_domain' => config('session.domain'),
        ]
    ]);
});