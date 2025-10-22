<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Autenticación Centralizada ORPRO
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de autenticación centralizada usando
    | intra.orpro.cl como servidor de autenticación principal.
    |
    */

    'intra_url' => env('AUTH_INTRA_URL', 'https://intra.orpro.cl/index.php'),
    
    'sistema_name' => env('AUTH_SISTEMA_NAME', 'omnic'),
    
    'cookie_name' => env('AUTH_COOKIE_NAME', 'AUTHOMNIC'),
    
    /*
    |--------------------------------------------------------------------------
    | Token Validation
    |--------------------------------------------------------------------------
    |
    | Configuración para validar tokens desde intra.orpro.cl
    |
    */
    'validation_endpoint' => env('AUTH_VALIDATION_ENDPOINT', '/api/validate-token'),
    
    'timeout' => 10, // segundos de timeout para validación remota
    
    /*
    |--------------------------------------------------------------------------
    | Cookie Configuration
    |--------------------------------------------------------------------------
    */
    'cookie_lifetime' => 1440, // 24 horas en minutos
    'cookie_domain' => '.orpro.cl', // Dominio compartido
    'cookie_secure' => env('SESSION_SECURE_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | URLs Excluidas de Autenticación
    |--------------------------------------------------------------------------
    |
    | Rutas que no requieren autenticación centralizada
    |
    */
    'excluded_paths' => [
        'auth/logout',
        'debug-auth',
        'health',
        'up',
        'api/*',
        'livewire/*',
    ],

];