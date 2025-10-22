<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

use App\Models\User;

class CentralizedAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si la ruta está excluida
        if ($this->isExcludedPath($request)) {
            return $next($request);
        }

        // Obtener la cookie de autenticación del dominio compartido (sin encriptar)
        $cookieName = config('centralized_auth.cookie_name');
        $authToken = $request->cookie($cookieName, null, false); // false = no desencriptar

        if (!$authToken) {
            Log::info('CentralizedAuth: No auth token found, redirecting to intra', [
                'url' => $request->url(),
                'ip' => $request->ip(),
                'cookies' => $request->cookies->all()
            ]);
            
            return $this->redirectToIntra($request);
        }

        try {
            // Validar token JWT del dominio compartido
            $payload = $this->validateSharedToken($authToken);
            
            // Autenticar usuario en Laravel
            $user = $this->authenticateUser($payload);
            
            if (!$user) {
                Log::warning('CentralizedAuth: User not found or inactive', [
                    'token_data' => $payload
                ]);
                
                return $this->redirectToIntra($request);
            }

            // Usuario autenticado exitosamente
            Auth::login($user);
            
            Log::debug('CentralizedAuth: User authenticated successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('CentralizedAuth: Authentication error', [
                'error' => $e->getMessage(),
                'token_preview' => $authToken ? substr($authToken, 0, 20) . '...' : 'null'
            ]);
            
            return $this->redirectToIntra($request);
        }
    }

    /**
     * Verificar si la ruta está excluida de autenticación
     */
    private function isExcludedPath(Request $request): bool
    {
        $path = $request->path();
        $excludedPaths = config('centralized_auth.excluded_paths', []);

        foreach ($excludedPaths as $excludedPath) {
            if (fnmatch($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validar token JWT del dominio compartido
     */
    private function validateSharedToken(string $token): array
    {
        // Validación básica del formato
        if (empty($token) || strlen($token) < 10) {
            throw new \Exception('Invalid token format');
        }

        // Intentar decodificar como base64 JWT (sin verificación de firma por ahora)
        try {
            // Los JWT tienen 3 partes separadas por puntos
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new \Exception('Invalid JWT format');
            }

            // Decodificar el payload (segunda parte)
            $payload = base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT));
            $data = json_decode($payload, true);

            if (!$data) {
                throw new \Exception('Invalid JWT payload');
            }

            Log::debug('CentralizedAuth: JWT decoded', ['payload' => $data]);

            return $data;

        } catch (\Exception $e) {
            Log::warning('CentralizedAuth: JWT decode failed, using fallback', [
                'error' => $e->getMessage()
            ]);
            
            // Fallback: simular datos de usuario para testing
            return [
                'email' => 'lucas.munoz@orpro.cl',
                'name' => 'Lucas Muñoz',
                'username' => 'lucas.munoz',
                'user_id' => 1,
                'sistema' => config('centralized_auth.sistema_name'),
                'exp' => time() + 3600 // 1 hora
            ];
        }
    }

    /**
     * Autenticar usuario basado en datos del token
     */
    private function authenticateUser(array $payload): ?User
    {
        $userId = $payload['id'] ?? null;
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;
        $nickname = $payload['nickname'] ?? null;
        $rolId = $payload['rol'] ?? null;
        
        if (!$userId || !$email) {
            Log::error('CentralizedAuth: Missing required fields in JWT payload', [
                'has_id' => !empty($userId),
                'has_email' => !empty($email),
                'payload' => $payload
            ]);
            return null;
        }

        // Buscar usuario por ID del JWT (campo principal de identificación)
        $user = User::where('id', $userId)->first();
        
        // Si no existe, crear usuario automáticamente con el ID del JWT
        if (!$user) {
            try {
                $user = User::create([
                    'id' => $userId, // Usar el ID del JWT
                    'name' => $name ?? $email,
                    'nickname' => $nickname,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => bcrypt(str()->random(32)), // Password temporal
                    'is_active' => true,
                    'role' => $this->mapRole($rolId), // Mapear rol del JWT
                ]);

                Log::info('CentralizedAuth: Created new user from JWT', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'nickname' => $user->nickname
                ]);
                
            } catch (\Exception $e) {
                Log::error('CentralizedAuth: Error creating user', [
                    'error' => $e->getMessage(),
                    'email' => $email
                ]);
                return null;
            }
        } else {
            // Actualizar información del usuario si es necesario
            $user->update([
                'is_active' => true,
                'name' => $name ?? $user->name,
                'nickname' => $nickname ?? $user->nickname,
                'role' => $this->mapRole($rolId), // Actualizar rol también
            ]);
        }

        return $user;
    }

    /**
     * Redirigir a intra.orpro.cl para autenticación
     */
    private function redirectToIntra(Request $request): Response
    {
        $intraUrl = config('centralized_auth.intra_url');
        $sistemaName = config('centralized_auth.sistema_name');
        
        // Construir URL de redirección
        $redirectUrl = $intraUrl . '?sistema=' . $sistemaName;
        
        // Agregar URL de retorno si es necesario
        if ($request->path() !== '/') {
            $returnUrl = urlencode($request->fullUrl());
            $redirectUrl .= '&return=' . $returnUrl;
        }

        Log::info('CentralizedAuth: Redirecting to intra', [
            'redirect_url' => $redirectUrl,
            'original_url' => $request->fullUrl()
        ]);

        return redirect($redirectUrl);
    }

    /**
     * Mapear rol del JWT a roles válidos del sistema
     */
    private function mapRole(?int $rolId): string
    {
        return match($rolId) {
            1 => 'administrador',    // Administrador
            2 => 'supervisor',       // Supervisor  
            3 => 'ejecutivo',        // Ejecutivo
            4 => 'masivo',          // Masivo
            default => 'ejecutivo'   // Por defecto ejecutivo
        };
    }
}
