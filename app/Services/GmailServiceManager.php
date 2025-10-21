<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Log;
use App\Models\SystemConfig;

class GmailServiceManager
{
    private Client $client;
    private Gmail $gmailService;

    public function __construct()
    {
        $this->client = new Client();
        $this->setupAuthentication();
        $this->gmailService = new Gmail($this->client);
    }

    /**
     * Configurar autenticación según el entorno
     */
    private function setupAuthentication(): void
    {
        $this->client->setApplicationName('Omnic Email System');
        $this->client->setScopes($this->getScopes());

        // Método 1: OAuth Flow (Preferido para desarrollo y producción)
        if ($this->tryOAuthFlow()) {
            return;
        }

        // Método 2: Application Default Credentials (Fallback)
        if ($this->tryApplicationDefaultCredentials()) {
            return;
        }

        // Método 3: Workload Identity (Producción)
        if ($this->tryWorkloadIdentity()) {
            return;
        }

        // Método 4: Service Account Key (Solo si no hay alternativa)
        if ($this->tryServiceAccountKey()) {
            return;
        }

        // Si nada funciona, lanzar excepción con instrucciones
        throw new \Exception('No se pudo configurar autenticación. Ejecuta: php artisan gmail:setup-oauth');
    }

    /**
     * Intentar usar Application Default Credentials
     */
    private function tryApplicationDefaultCredentials(): bool
    {
        try {
            $keyFile = getenv('GOOGLE_APPLICATION_CREDENTIALS');
            if ($keyFile && file_exists($keyFile)) {
                $this->client->useApplicationDefaultCredentials();
                $this->client->setSubject('orpro@orproverificaciones.cl'); // Email de impersonación
                Log::info('✅ Usando Application Default Credentials');
                return true;
            }
        } catch (\Exception $e) {
            Log::debug('❌ Application Default Credentials no disponibles: ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Intentar usar Workload Identity
     */
    private function tryWorkloadIdentity(): bool
    {
        try {
            $workloadConfig = config('services.google.workload_identity');
            if ($workloadConfig) {
                $this->client->setAuthConfig($workloadConfig);
                Log::info('✅ Usando Workload Identity');
                return true;
            }
        } catch (\Exception $e) {
            Log::debug('❌ Workload Identity no configurado: ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Intentar usar Service Account Key
     */
    private function tryServiceAccountKey(): bool
    {
        try {
            $keyPath = config('services.google.service_account_key');
            if ($keyPath && file_exists($keyPath)) {
                $this->client->setAuthConfig($keyPath);
                $this->client->setSubject('orpro@orproverificaciones.cl');
                Log::info('✅ Usando Service Account Key');
                return true;
            }
        } catch (\Exception $e) {
            Log::debug('❌ Service Account Key no disponible: ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Intentar usar OAuth Flow
     */
    private function tryOAuthFlow(): bool
    {
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Cargar tokens existentes
        $refreshToken = SystemConfig::getValue('gmail_refresh_token');
        $accessToken = SystemConfig::getValue('gmail_access_token');
        $tokenExpires = SystemConfig::getValue('gmail_token_expires');

        if ($refreshToken) {
            // Si tenemos refresh token, úsalo
            try {
                $this->client->refreshToken($refreshToken);
                Log::info('✅ Token OAuth actualizado con refresh token');
                
                // Guardar nuevo access token
                $newTokens = $this->client->getAccessToken();
                if ($newTokens && isset($newTokens['access_token'])) {
                    SystemConfig::setValue('gmail_access_token', $newTokens['access_token']);
                    if (isset($newTokens['expires_in'])) {
                        SystemConfig::setValue('gmail_token_expires', now()->addSeconds($newTokens['expires_in'])->timestamp);
                    }
                }
                return true;
            } catch (\Exception $e) {
                Log::warning('⚠️ Error al actualizar token con refresh token: ' . $e->getMessage());
            }
        }

        if ($accessToken && $tokenExpires && now()->timestamp < $tokenExpires) {
            // Si tenemos access token válido, úsalo
            $this->client->setAccessToken([
                'access_token' => $accessToken,
                'expires_in' => $tokenExpires - now()->timestamp
            ]);
            Log::info('✅ Usando access token existente');
            return true;
        }
        
        Log::debug('⚠️ OAuth no configurado o tokens expirados');
        return false;
    }

    /**
     * Verificar si está autenticado
     */
    public function isAuthenticated(): bool
    {
        try {
            $token = $this->client->getAccessToken();
            return !empty($token);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener servicio Gmail
     */
    public function getGmailService(): Gmail
    {
        return $this->gmailService;
    }

    /**
     * Probar autenticación con Gmail
     */
    public function testAuthentication(): array
    {
        try {
            // Verificar si hay tokens OAuth disponibles
            $refreshToken = SystemConfig::getValue('gmail_refresh_token');
            $accessToken = SystemConfig::getValue('gmail_access_token');
            
            if (!$refreshToken && !$accessToken) {
                return [
                    'authenticated' => false,
                    'error' => 'No hay tokens OAuth configurados. Ejecuta: php artisan gmail:setup-oauth'
                ];
            }
            
            $profile = $this->gmailService->users->getProfile('me');
            
            return [
                'authenticated' => true,
                'email' => $profile->getEmailAddress(),
                'messages_total' => $profile->getMessagesTotal(),
                'threads_total' => $profile->getThreadsTotal(),
                'auth_method' => 'OAuth2'
            ];
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'error' => $e->getMessage(),
                'suggestion' => 'Ejecuta: php artisan gmail:setup-oauth'
            ];
        }
    }

    /**
     * Obtener cliente Google autenticado
     */
    public function getAuthenticatedClient(): ?Client
    {
        if ($this->isAuthenticated()) {
            return $this->client;
        }
        
        return null;
    }

    /**
     * Obtener scopes configurados
     */
    private function getScopes(): array
    {
        return [
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.modify',
            'https://www.googleapis.com/auth/admin.directory.group',
            'https://www.googleapis.com/auth/admin.directory.group.member',
            'https://www.googleapis.com/auth/admin.directory.user.readonly',
            'https://www.googleapis.com/auth/drive.file', // Para Drive API si lo necesitas
        ];
    }
}