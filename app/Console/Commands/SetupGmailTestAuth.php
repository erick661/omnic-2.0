<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemConfig;

class SetupGmailTestAuth extends Command
{
    protected $signature = 'gmail:setup-test-auth';
    protected $description = 'Configura autenticación de prueba para Gmail API';

    public function handle()
    {
        $this->info('🔧 Configurando autenticación de prueba para Gmail...');
        
        // Crear token de prueba (simulado)
        $testToken = [
            'access_token' => 'test_access_token_' . time(),
            'refresh_token' => 'test_refresh_token_' . time(),
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ];
        
        SystemConfig::setValue(
            'gmail_refresh_token', 
            $testToken['refresh_token'],
            'Token de refresh para Gmail API (PRUEBA)'
        );
        
        SystemConfig::setValue(
            'gmail_access_token', 
            json_encode($testToken),
            'Token de acceso para Gmail API (PRUEBA)'
        );
        
        SystemConfig::setValue(
            'gmail_auth_setup', 
            'test_mode',
            'Modo de autenticación de Gmail'
        );
        
        $this->info('✅ Configuración de prueba creada');
        $this->warn('⚠️  IMPORTANTE: Esto es solo para desarrollo');
        $this->warn('   Para producción necesitas configurar OAuth real');
        
        return 0;
    }
}