<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MockGmailService;
use App\Services\GmailService;
use App\Models\SystemConfig;

class ImportEmails extends Command
{
    protected $signature = 'emails:import {--mock : Usar servicio mock para desarrollo}';
    protected $description = 'Importa correos nuevos desde Gmail API usando Service Account';

    public function handle()
    {
        $this->info('📧 Iniciando importación de correos...');
        
        // Usar mock service solo si se especifica el flag --mock
        $useMock = $this->option('mock');
        
        if ($useMock) {
            $this->warn('🧪 Usando servicio MOCK para desarrollo');
            $service = new MockGmailService();
        } else {
            $this->info('🔗 Usando Gmail API real con Service Account');
            $service = new GmailService();
        }
        
        if (!$service->isAuthenticated()) {
            $this->error('❌ Gmail Service Account no está autenticado');
            if (!$useMock) {
                $this->info('💡 Verifica la configuración del Service Account');
                $this->info('   Variable GOOGLE_APPLICATION_CREDENTIALS debe estar configurada');
                $this->info('   Service Account debe tener Domain-wide Delegation habilitado');
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