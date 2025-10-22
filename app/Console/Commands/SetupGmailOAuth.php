<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Gmail;
use App\Models\SystemConfig;
use App\Models\OAuthToken;

class SetupGmailOAuth extends Command
{
    protected $signature = 'gmail:setup-oauth {--reset : Reiniciar configuración OAuth}';
    protected $description = 'Configurar autenticación OAuth2 con Gmail';

    public function handle()
    {
        if ($this->option('reset')) {
            $this->resetOAuthTokens();
        }

        $this->info('🔐 Configurando autenticación OAuth2 con Gmail...');
        $this->line('');

        // Verificar configuración básica
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');

        if (!$clientId || !$clientSecret || !$redirectUri) {
            $this->error('❌ Configuración OAuth incompleta en .env:');
            $this->line('   GOOGLE_CLIENT_ID=' . ($clientId ? '✅ Configurado' : '❌ Faltante'));
            $this->line('   GOOGLE_CLIENT_SECRET=' . ($clientSecret ? '✅ Configurado' : '❌ Faltante'));
            $this->line('   GOOGLE_REDIRECT_URI=' . ($redirectUri ? '✅ Configurado' : '❌ Faltante'));
            return 1;
        }

        $this->info('✅ Configuración OAuth encontrada:');
        $this->table(['Variable', 'Valor'], [
            ['Client ID', substr($clientId, 0, 20) . '...'],
            ['Redirect URI', $redirectUri],
            ['Client Secret', str_repeat('*', 20) . '...']
        ]);

        // Verificar si ya hay tokens
        $existingToken = OAuthToken::getActiveToken('gmail');
        if ($existingToken && !$this->option('reset')) {
            $this->warn('⚠️ Ya existe un token OAuth configurado.');
            $this->line('   📅 Creado: ' . $existingToken->created_at->format('Y-m-d H:i:s'));
            if ($existingToken->expires_at) {
                $this->line('   ⏰ Expira: ' . $existingToken->expires_at->format('Y-m-d H:i:s'));
            }
            if (!$this->confirm('¿Deseas probar la autenticación actual?', true)) {
                return 0;
            }
            return $this->testExistingAuth();
        }

        // Generar URL de autorización
        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes([
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.modify',
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/admin.directory.group',
            'https://www.googleapis.com/auth/admin.directory.group.member',
            'https://www.googleapis.com/auth/admin.directory.user.readonly',
            'https://www.googleapis.com/auth/drive.file'
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $authUrl = $client->createAuthUrl();

        $this->info('🌐 Para completar la configuración OAuth2:');
        $this->line('');
        $this->info('1️⃣ Abre tu navegador y visita:');
        $this->line('   ' . config('app.url') . '/auth/gmail');
        $this->line('');
        $this->info('2️⃣ O alternativamente, visita directamente Google:');
        $this->line('   ' . $authUrl);
        $this->line('');
        $this->info('3️⃣ Autoriza la aplicación con la cuenta: orpro@orproverificaciones.cl');
        $this->line('');
        $this->info('4️⃣ Después de autorizar, verifica con:');
        $this->line('   php artisan gmail:test-auth');

        return 0;
    }

    private function resetOAuthTokens(): void
    {
        // Desactivar tokens existentes en la nueva tabla
        OAuthToken::where('provider', 'gmail')
                  ->where('is_active', true)
                  ->update(['is_active' => false]);
        
        // También limpiar el sistema anterior por compatibilidad
        SystemConfig::setValue('gmail_refresh_token', null);
        SystemConfig::setValue('gmail_access_token', null);
        SystemConfig::setValue('gmail_token_expires', null);
        
        $this->info('🔄 Tokens OAuth reiniciados (tabla nueva y sistema anterior)');
    }

    private function testExistingAuth(): int
    {
        try {
            $refreshToken = SystemConfig::getValue('gmail_refresh_token');
            
            $client = new Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->refreshToken($refreshToken);
            
            $gmail = new Gmail($client);
            $profile = $gmail->users->getProfile('me');
            
            $this->info('✅ Autenticación existente válida:');
            $this->table(['Campo', 'Valor'], [
                ['Email', $profile->getEmailAddress()],
                ['Mensajes Totales', number_format($profile->getMessagesTotal())],
                ['Hilos Totales', number_format($profile->getThreadsTotal())]
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Token existente inválido: ' . $e->getMessage());
            $this->info('💡 Ejecuta con --reset para reconfigurar');
            return 1;
        }
    }
}