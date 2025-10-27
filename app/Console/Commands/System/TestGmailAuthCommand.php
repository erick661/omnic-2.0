<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;
use App\Services\Email\EmailImportService;
use App\Services\Email\EmailSendService;
use App\Services\Groups\GmailGroupService;

class TestGmailAuthCommand extends Command
{
    protected $signature = 'system:test-gmail-auth 
                           {--service= : Servicio específico a probar (email, groups, all)}';

    protected $description = '✅ SOLID: Probar autenticación con Gmail API y servicios relacionados';

    public function __construct(
        private EmailImportService $importService,
        private EmailSendService $sendService,
        private GmailGroupService $groupService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // ✅ SOLID: Solo orquestación de servicios
        $service = $this->option('service') ?? 'all';
        
        $this->info('🔐 Probando autenticación con Gmail API...');
        $this->newLine();

        try {
            $results = [];

            if ($service === 'all' || $service === 'email') {
                $results['email'] = $this->testEmailServices();
            }

            if ($service === 'all' || $service === 'groups') {
                $results['groups'] = $this->testGroupService();
            }

            $this->displaySummary($results);

            return $this->allServicesWorking($results) ? self::SUCCESS : self::FAILURE;

        } catch (\Exception $e) {
            $this->error("❌ Error general: {$e->getMessage()}");
            $this->suggestSolutions();
            return self::FAILURE;
        }
    }

    private function testEmailServices(): array
    {
        $this->info('📧 Probando Email Services...');
        
        $results = [];
        
        // Test import service
        try {
            if (!$this->importService->hasCredentials()) {
                throw new \Exception('Credenciales no encontradas');
            }
            $this->importService->performConnectionTest();
            $results['import'] = ['success' => true, 'message' => 'Conexión exitosa'];
        } catch (\Exception $e) {
            $results['import'] = ['success' => false, 'message' => $e->getMessage()];
        }
        
        // Test send service
        try {
            if (!$this->sendService->hasCredentials()) {
                throw new \Exception('Credenciales no encontradas');
            }
            $this->sendService->performConnectionTest();
            $results['send'] = ['success' => true, 'message' => 'Conexión exitosa'];
        } catch (\Exception $e) {
            $results['send'] = ['success' => false, 'message' => $e->getMessage()];
        }

        foreach ($results as $service => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $message = $result['message'] ?? 'Sin detalles';
            $this->line("   {$status} {$service}: {$message}");
        }

        return $results;
    }

    private function testGroupService(): array
    {
        $this->info('👥 Probando Group Service...');
        
        $result = $this->groupService->testConnection();
        
        $status = $result['success'] ? '✅' : '❌';
        $this->line("   {$status} Groups: {$result['message']}");

        return ['groups' => $result];
    }

    private function displaySummary(array $results): void
    {
        $this->newLine();
        $this->info('📊 RESUMEN DE AUTENTICACIÓN:');
        $this->line('────────────────────────────');

        $table = [];
        foreach ($results as $category => $categoryResults) {
            if (is_array($categoryResults) && isset($categoryResults['groups'])) {
                // Es el resultado de grupos
                $result = $categoryResults['groups'];
                $table[] = [
                    'Groups',
                    $result['success'] ? '✅ OK' : '❌ Error',
                    $result['message']
                ];
            } else {
                // Es resultado de email services
                foreach ($categoryResults as $service => $result) {
                    $table[] = [
                        ucfirst($service),
                        $result['success'] ? '✅ OK' : '❌ Error',
                        $result['message']
                    ];
                }
            }
        }

        $this->table(['Servicio', 'Estado', 'Mensaje'], $table);
    }

    private function allServicesWorking(array $results): bool
    {
        foreach ($results as $categoryResults) {
            if (isset($categoryResults['groups'])) {
                if (!$categoryResults['groups']['success']) {
                    return false;
                }
            } else {
                foreach ($categoryResults as $result) {
                    if (!$result['success']) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    private function suggestSolutions(): void
    {
        $this->newLine();
        $this->info('💡 SOLUCIONES SUGERIDAS:');
        $this->line('────────────────────────');
        $this->info('1️⃣ Verificar Service Account:');
        $this->line('   php artisan system:test-service-account');
        $this->newLine();
        $this->info('2️⃣ Verificar credenciales:');
        $this->line('   - GOOGLE_APPLICATION_CREDENTIALS configurado');
        $this->line('   - Service Account con Domain-wide Delegation');
        $this->line('   - admin@orproverificaciones.cl como subject');
        $this->newLine();
        $this->info('3️⃣ Verificar permisos:');
        $this->line('   - Gmail API habilitada');
        $this->line('   - Directory API habilitada');
        $this->line('   - Drive API habilitada (opcional)');
    }
}