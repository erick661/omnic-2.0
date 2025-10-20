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
        $this->client->setScopes([
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_MODIFY
        ]);

        // Método 1: Application Default Credentials (Desarrollo)
        if ($this->tryApplicationDefaultCredentials()) {
            return;
        }

        // Método 2: Workload Identity (Producción)
        if ($this->tryWorkloadIdentity()) {
            return;
        }

        // Método 3: Service Account Key (Solo si no hay alternativa)
        if ($this->tryServiceAccountKey()) {
            return;
        }

        // Método 4: OAuth Flow (UI)
        $this->setupOAuthFlow();
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
     * Configurar OAuth Flow (para UI)
     */
    private function setupOAuthFlow(): void
    {
        $this->client->setAuthConfig([
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uris' => [config('services.google.redirect_uri')]
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Cargar token de refresh si existe
        $refreshToken = SystemConfig::getValue('gmail_refresh_token');
        if ($refreshToken) {
            $this->client->refreshToken($refreshToken);
        }
        
        Log::info('⚠️ Usando OAuth Flow - requiere autenticación manual');
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
            $profile = $this->gmailService->users->getProfile('me');
            
            return [
                'authenticated' => true,
                'email' => $profile->getEmailAddress(),
                'messages_total' => $profile->getMessagesTotal(),
                'threads_total' => $profile->getThreadsTotal()
            ];
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}