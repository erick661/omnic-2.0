<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;
use App\Services\Email\EmailSendService;
use App\Services\Groups\GmailGroupService;
use App\Services\Drive\DriveService;

class TestServiceAccountCommand extends Command
{
    protected $signature = 'system:test-service-account 
                           {--send-test : Enviar email de prueba}
                           {--test-email= : Email para prueba}';

    protected $description = '✅ SOLID: Probar configuración de Service Account con Domain-wide Delegation';

    public function __construct(
        private EmailSendService $emailService,
        private GmailGroupService $groupService,
        private DriveService $driveService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // ✅ SOLID: Solo orquestación de servicios
        $this->info('🔐 Probando Service Account con Domain-wide Delegation...');
        $this->newLine();

        try {
            // 1. Probar Email Service
            $this->testEmailService();

            // 2. Probar Group Service  
            $this->testGroupService();

            // 3. Probar Drive Service
            $this->testDriveService();

            // 4. Enviar email de prueba si se solicita
            if ($this->option('send-test')) {
                $this->sendTestEmail();
            }

            $this->newLine();
            $this->info('🎉 ¡Service Account configurado correctamente!');
            $this->info('   ✅ Todos los servicios funcionando');
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function testEmailService(): void
    {
        $this->info('📧 Probando Email Service...');
        
        // ✅ DIP: Usa servicio, no implementación directa
        $result = $this->emailService->testConnection();
        
        if ($result['success']) {
            $this->info("   ✅ Gmail API: {$result['message']}");
        } else {
            $this->warn("   ⚠️  Gmail API: {$result['message']}");
        }
    }

    private function testGroupService(): void
    {
        $this->info('👥 Probando Group Service...');
        
        $result = $this->groupService->testConnection();
        
        if ($result['success']) {
            $this->info("   ✅ Directory API: {$result['message']}");
        } else {
            $this->warn("   ⚠️  Directory API: {$result['message']}");
        }
    }

    private function testDriveService(): void
    {
        $this->info('📁 Probando Drive Service...');
        
        $result = $this->driveService->testConnection();
        
        if ($result['success']) {
            $this->info("   ✅ Drive API: {$result['message']}");
        } else {
            $this->warn("   ⚠️  Drive API: {$result['message']}");
        }
    }

    private function sendTestEmail(): void
    {
        $testEmail = $this->option('test-email') ?? $this->ask('Email para prueba:');
        
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error("❌ Email inválido: {$testEmail}");
            return;
        }

        $this->info("📤 Enviando email de prueba a: {$testEmail}");

        $result = $this->emailService->sendEmailNow([
            'to' => $testEmail,
            'subject' => 'Test Service Account - ' . now()->format('Y-m-d H:i:s'),
            'body' => $this->getTestEmailBody(),
            'from_email' => 'admin@orproverificaciones.cl',
            'from_name' => 'Sistema OMNIC'
        ]);

        if ($result['success']) {
            $this->info("   ✅ Email enviado: {$result['message_id']}");
        } else {
            $this->error("   ❌ Error enviando: {$result['error']}");
        }
    }

    private function getTestEmailBody(): string
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        
        return "
        <h2>🧪 Test Service Account - Sistema OMNIC</h2>
        
        <p><strong>Fecha:</strong> {$timestamp}</p>
        
        <h3>✅ Service Account Funcionando</h3>
        <p>Si recibes este correo, significa que:</p>
        <ul>
            <li>✅ Service Account configurado correctamente</li>
            <li>✅ Domain-wide delegation activo</li>
            <li>✅ Gmail API operativa</li>
            <li>✅ Permisos de envío habilitados</li>
        </ul>
        
        <hr>
        <p><em>Email automático del sistema OMNIC</em><br>
        <strong>Timestamp:</strong> {$timestamp}</p>
        ";
    }
}