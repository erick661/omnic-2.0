<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Models\User;

class AuthController extends Controller
{
    /**
     * Callback desde intra.orpro.cl después de autenticación exitosa
     */
    public function callback(Request $request)
    {
        try {
            // El token JWT debería venir como parámetro o cookie
            $token = $request->input('token') ?? $request->cookie(config('centralized_auth.cookie_name'));
            
            if (!$token) {
                Log::error('AuthController: No token received in callback');
                return redirect('/')->withErrors(['auth' => 'No se recibió token de autenticación']);
            }

            // Validar el token
            $payload = $this->validateToken($token);
            
            // Buscar o crear usuario
            $user = $this->findOrCreateUser($payload);
            
            if (!$user) {
                Log::error('AuthController: Could not create/find user', ['payload' => $payload]);
                return redirect('/')->withErrors(['auth' => 'Error al autenticar usuario']);
            }

            // Autenticar en Laravel
            Auth::login($user);
            
            // Establecer cookie de autenticación si viene como parámetro
            if ($request->input('token')) {
                $cookie = cookie(
                    config('centralized_auth.cookie_name'),
                    $token,
                    config('centralized_auth.cookie_lifetime', 1440), // 24 horas por defecto
                    '/',
                    config('centralized_auth.cookie_domain'),
                    config('centralized_auth.cookie_secure', false),
                    true // HttpOnly
                );
                
                $response = redirect($this->getReturnUrl($request))
                    ->withCookie($cookie)
                    ->with('success', 'Autenticación exitosa');
                    
                return $response;
            }

            Log::info('AuthController: User authenticated successfully via callback', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return redirect($this->getReturnUrl($request))
                ->with('success', 'Autenticación exitosa');


            
        } catch (\Exception $e) {
            Log::error('AuthController: Unexpected error in callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/')->withErrors(['auth' => 'Error interno de autenticación']);
        }
    }

    /**
     * Logout del usuario
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        // Limpiar cookie de autenticación
        $cookie = cookie()->forget(config('centralized_auth.cookie_name'));
        
        Log::info('AuthController: User logged out');
        
        return redirect('/')
            ->withCookie($cookie)
            ->with('success', 'Sesión cerrada exitosamente');
    }

    /**
     * Validar token desde intra
     */
    private function validateToken(string $token): array
    {
        // Validación básica del formato
        if (empty($token) || strlen($token) < 10) {
            throw new \Exception('Invalid token format');
        }

        // En producción: hacer llamada HTTP a intra.orpro.cl para validar
        // Por ahora simulamos respuesta exitosa
        return [
            'email' => 'lucas.munoz@orpro.cl',
            'name' => 'Lucas Muñoz', 
            'username' => 'lucas.munoz',
            'user_id' => 1,
            'full_name' => 'Lucas Muñoz'
        ];
    }

    /**
     * Buscar o crear usuario basado en datos del token
     */
    private function findOrCreateUser(array $payload): ?User
    {
        $email = $payload['email'] ?? null;
        $username = $payload['username'] ?? null;
        $name = $payload['name'] ?? $payload['full_name'] ?? null;

        if (!$email && !$username) {
            Log::error('AuthController: No email or username in JWT payload', ['payload' => $payload]);
            return null;
        }

        // Buscar usuario existente
        $user = null;
        
        if ($email) {
            $user = User::where('email', $email)->first();
        }
        
        if (!$user && $username) {
            $user = User::where('username', $username)->first();
        }

        // Si no existe, crear nuevo usuario
        if (!$user) {
            try {
                $user = User::create([
                    'email' => $email,
                    'username' => $username ?? $email,
                    'name' => $name ?? $email,
                    'active' => true,
                    'password' => bcrypt(str()->random(32)), // Password temporal
                    'email_verified_at' => now(),
                ]);

                Log::info('AuthController: New user created from intra auth', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
            } catch (\Exception $e) {
                Log::error('AuthController: Error creating user', [
                    'error' => $e->getMessage(),
                    'email' => $email,
                    'username' => $username
                ]);
                
                return null;
            }
        } else {
            // Actualizar información si es necesario
            $user->update([
                'active' => true,
                'last_login' => now(),
            ]);
            
            Log::info('AuthController: Existing user logged in', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }

        return $user;
    }

    /**
     * Obtener URL de retorno
     */
    private function getReturnUrl(Request $request): string
    {
        $returnUrl = $request->input('return');
        
        if ($returnUrl && filter_var($returnUrl, FILTER_VALIDATE_URL)) {
            // Verificar que sea del mismo dominio
            $currentHost = $request->getHost();
            $returnHost = parse_url($returnUrl, PHP_URL_HOST);
            
            if ($returnHost === $currentHost) {
                return $returnUrl;
            }
        }
        
        return '/dashboard'; // URL por defecto después de login
    }
}
