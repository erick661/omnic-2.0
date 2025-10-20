<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailServiceManager;
use App\Services\MockGmailService;

class TestGmailAuth extends Command
{
    protected $signature = 'gmail:test-auth {--mock : Usar servicio mock}';
    protected $description = 'Probar autenticación con Gmail API';

    public function handle()
    {
        $this->info('🔐 Probando autenticación con Gmail...');
        
        if ($this->option('mock')) {
            return $this->testMockService();
        }
        
        try {
            $gmailManager = new GmailServiceManager();
            $result = $gmailManager->testAuthentication();
            
            if ($result['authenticated']) {
                $this->info('✅ Autenticación exitosa!');
                $this->table(['Campo', 'Valor'], [
                    ['Email', $result['email']],
                    ['Total Mensajes', number_format($result['messages_total'])],
                    ['Total Hilos', number_format($result['threads_total'])]
                ]);
            } else {
                $this->error('❌ Error de autenticación: ' . $result['error']);
                $this->suggestSolutions();
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error general: ' . $e->getMessage());
            $this->suggestSolutions();
        }
        
        return 0;
    }
    
    private function testMockService(): int
    {
        $this->warn('🧪 Usando servicio MOCK para desarrollo');
        
        $mockService = new MockGmailService();
        $isAuth = $mockService->isAuthenticated();
        
        if ($isAuth) {
            $this->info('✅ Mock service configurado correctamente');
            
            // Probar importación mock
            $this->info('📧 Probando importación mock...');
            $results = $mockService->importNewEmails();
            
            $this->table(['Grupo', 'Importados', 'Estado'], 
                array_map(fn($r) => [$r['group'], $r['imported'], $r['status']], $results)
            );
        } else {
            $this->error('❌ Mock service no configurado');
            $this->info('💡 Ejecuta: php artisan gmail:setup-test-auth');
        }
        
        return 0;
    }
    
    private function suggestSolutions(): void
    {
        $this->info('');
        $this->info('💡 Opciones de configuración:');
        $this->info('');
        $this->info('1️⃣ DESARROLLO - Application Default Credentials:');
        $this->line('   gcloud auth application-default login');
        $this->line('   export GOOGLE_APPLICATION_CREDENTIALS=/path/to/key.json');
        $this->info('');
        $this->info('2️⃣ DESARROLLO - Mock Service:');
        $this->line('   php artisan gmail:test-auth --mock');
        $this->info('');
        $this->info('3️⃣ PRODUCCIÓN - Workload Identity:');
        $this->line('   Configurar Workload Identity Federation en Google Cloud');
        $this->info('');
        $this->info('4️⃣ UI OAuth (requiere ngrok):');
        $this->line('   Configurar OAuth con dominio público válido');
    }
}