<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MockGmailService;
use App\Models\SystemConfig;

class ImportEmails extends Command
{
    protected $signature = 'emails:import {--mock : Usar servicio mock para desarrollo}';
    protected $description = 'Importa correos nuevos desde Gmail API';

    public function handle()
    {
        $this->info('📧 Iniciando importación de correos...');
        
        // Usar mock service si está en modo test o se especifica flag
        $useMock = $this->option('mock') || SystemConfig::getValue('gmail_auth_setup') === 'test_mode';
        
        if ($useMock) {
            $this->warn('🧪 Usando servicio MOCK para desarrollo');
            $service = new MockGmailService();
        } else {
            $this->info('🔗 Usando Gmail API real');
            $service = new \App\Services\GmailService();
        }
        
        if (!$service->isAuthenticated()) {
            $this->error('❌ Gmail no está autenticado');
            if (!$useMock) {
                $this->info('💡 Ejecuta: php artisan gmail:setup-test-auth');
                $this->info('   O configura OAuth real con ngrok');
            }
            return 1;
        }
        
        try {
            $results = $service->importNewEmails();
            
            $this->info('📊 Resultados de importación:');
            $this->table(
                ['Grupo', 'Importados', 'Estado'],
                array_map(fn($r) => [
                    $r['group'],
                    $r['imported'],
                    $r['status'] === 'success' ? '✅ OK' : '❌ Error'
                ], $results)
            );
            
            $total = array_sum(array_column($results, 'imported'));
            $this->info("✅ Total importados: {$total} correos");
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la importación: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}