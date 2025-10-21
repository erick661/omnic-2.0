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
                $tableData = [
                    ['Email', $result['email']],
                    ['Total Mensajes', number_format($result['messages_total'])],
                    ['Total Hilos', number_format($result['threads_total'])]
                ];
                
                if (isset($result['auth_method'])) {
                    $tableData[] = ['Método', $result['auth_method']];
                }
                
                $this->table(['Campo', 'Valor'], $tableData);
            } else {
                $this->error('❌ Error de autenticación: ' . $result['error']);
                
                if (isset($result['suggestion'])) {
                    $this->info('💡 ' . $result['suggestion']);
                } else {
                    $this->suggestOAuthSolutions();
                }
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
    
    private function suggestOAuthSolutions(): void
    {
        $this->info('');
        $this->info('💡 Opciones de configuración OAuth2:');
        $this->info('');
        $this->info('1️⃣ CONFIGURAR OAuth2 (Recomendado):');
        $this->line('   php artisan gmail:setup-oauth');
        $this->line('   Luego visita: ' . config('app.url') . '/auth/gmail');
        $this->info('');
        $this->info('2️⃣ DESARROLLO - Mock Service:');
        $this->line('   php artisan gmail:test-auth --mock');
        $this->info('');
        $this->info('3️⃣ VERIFICAR configuración OAuth existente:');
        $this->line('   php artisan gmail:setup-oauth --reset');
        $this->info('');
    }
}