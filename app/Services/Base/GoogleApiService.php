<?php

namespace App\Services\Base;

use Google\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemConfig;

abstract class GoogleApiService
{
    protected ?Client $client = null;
    protected array $cachedTokens = [];
    protected bool $isAuthenticated = false;
    protected ?string $impersonateEmail = null;
    protected array $requiredScopes = [];
    protected const MAX_RETRIES = 3;
    
    public function __construct()
    {
        // Lazy loading: solo inicializar cuando sea necesario
    }
    
    /**
     * Inicializar el servicio cuando sea necesario
     */
    public function initialize(): void
    {
        $this->authenticateClient();
    }
    
    /**
     * Verificar si las credenciales están disponibles
     */
    public function hasCredentials(): bool
    {
        try {
            $credentialsPath = $this->getServiceAccountCredentialsPath();
            return Storage::exists($credentialsPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Configurar el cliente de Google (lazy loading)
     */
    protected function setupClient(): void
    {
        if ($this->client !== null) {
            return;
        }
        
        $this->client = new Client();
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        
        // Configurar timeout usando Guzzle HTTP client
        $httpClient = new \GuzzleHttp\Client([
            'timeout' => 120,
            'connect_timeout' => 30
        ]);
        $this->client->setHttpClient($httpClient);
    }        /**
     * Autenticar usando Service Account (lazy loading)
     */
    protected function authenticateClient(): void
    {
        if ($this->isAuthenticated) {
            return;
        }
        
        $this->setupClient();
        $this->setupServiceAccountAuth();
        $this->isAuthenticated = true;
    }

    /**
     * Configurar autenticación con Service Account
     */
    protected function setupServiceAccountAuth(): void
    {
        $credentialsPath = $this->getServiceAccountCredentialsPath();
        
        if (!Storage::exists($credentialsPath)) {
            throw new \RuntimeException("Archivo de credenciales no encontrado: {$credentialsPath}");
        }

        $credentialsJson = Storage::get($credentialsPath);
        $credentials = json_decode($credentialsJson, true);
        
        if (!$credentials) {
            throw new \RuntimeException("Credenciales de Service Account inválidas");
        }

        $this->client->setAuthConfig($credentials);
        
        // Configurar impersonación si es necesario
        if ($this->impersonateEmail) {
            $this->client->setSubject($this->impersonateEmail);
        }

        Log::info('Service Account configurado', [
            'service' => static::class,
            'client_email' => $credentials['client_email'] ?? 'unknown',
            'impersonate' => $this->impersonateEmail
        ]);
    }

    /**
     * Configurar autenticación OAuth
     */
    protected function setupOAuthAuth(): void
    {
        // Implementar OAuth si es necesario
        throw new \RuntimeException("OAuth no implementado aún");
    }

    /**
     * Obtener token de acceso con cache
     */
    protected function getAccessToken(): string
    {
        $cacheKey = $this->getTokenCacheKey();
        
        return Cache::remember($cacheKey, 3300, function () { // 55 minutos
            try {
                $token = $this->client->fetchAccessTokenWithAssertion();
                
                if (isset($token['error'])) {
                    throw new \RuntimeException("Error obteniendo token: " . $token['error']);
                }
                
                return $token['access_token'];
                
            } catch (\Exception $e) {
                Log::error('Error obteniendo access token', [
                    'service' => static::class,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Hacer petición HTTP con reintentos
     */
    protected function makeRequest(callable $request, int $maxRetries = 3): mixed
    {
        $this->authenticateClient(); // Asegurar que esté inicializado
        
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                return $request();
                
            } catch (\Google\Service\Exception $e) {
                $attempt++;
                
                if ($this->shouldRetry($e, $attempt, $maxRetries)) {
                    Log::warning('Reintentando petición Google API', [
                        'service' => static::class,
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                        'code' => $e->getCode()
                    ]);
                    
                    sleep(pow(2, $attempt)); // Backoff exponencial
                    continue;
                }
                
                throw $e;
                
            } catch (\Exception $e) {
                Log::error('Error en petición Google API', [
                    'service' => static::class,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        
        throw new \RuntimeException("Máximo número de reintentos alcanzado");
    }

    /**
     * Determinar si se debe reintentar la petición
     */
    protected function shouldRetry(\Google\Service\Exception $e, int $attempt, int $maxRetries): bool
    {
        if ($attempt >= $maxRetries) {
            return false;
        }
        
        // Reintentar en errores de rate limit o temporales
        $retryableCodes = [403, 429, 500, 502, 503, 504];
        
        return in_array($e->getCode(), $retryableCodes);
    }

    /**
     * Verificar permisos y conectividad
     */
    public function testConnection(): array
    {
        try {
            $token = $this->getAccessToken();
            
            // Hacer una petición simple para verificar permisos
            $testResult = $this->performConnectionTest();
            
            return [
                'success' => $testResult['success'],
                'message' => $testResult['message'],
                'token_valid' => !empty($token),
                'scopes' => $this->getRequiredScopes(),
                'service' => static::class
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'token_valid' => false,
                'service' => static::class
            ];
        }
    }

    /**
     * Test específico de conexión - debe ser implementado por cada servicio
     */
    abstract public function performConnectionTest(): array;

    /**
     * Obtener los scopes requeridos para este servicio
     */
    protected function getRequiredScopes(): array
    {
        return $this->requiredScopes;
    }

    /**
     * Obtener método de autenticación desde configuración
     */
    protected function getAuthMethod(): string
    {
        return SystemConfig::getValue('google_auth_method', 'service_account');
    }

    /**
     * Obtener ruta de credenciales de Service Account
     */
    protected function getServiceAccountCredentialsPath(): string
    {
        return SystemConfig::getValue(
            'service_account_credentials_path', 
            'google-credentials/service-account-key.json'
        );
    }

    /**
     * Obtener email para impersonación
     */
    protected function getImpersonateEmail(): ?string
    {
        return $this->impersonateEmail ?: SystemConfig::getValue('google_impersonate_email');
    }

    /**
     * Generar clave de cache para token
     */
    protected function getTokenCacheKey(): string
    {
        return 'google_token_' . md5(static::class . $this->impersonateEmail);
    }

    /**
     * Limpiar cache de tokens
     */
    public function clearTokenCache(): void
    {
        Cache::forget($this->getTokenCacheKey());
    }

    /**
     * Obtener información del cliente autenticado
     */
    public function getClientInfo(): array
    {
        return [
            'service' => static::class,
            'scopes' => $this->getRequiredScopes(),
            'impersonate_email' => $this->impersonateEmail,
            'auth_method' => $this->getAuthMethod(),
        ];
    }
}