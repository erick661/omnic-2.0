<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OutboxEmailService;
use App\Services\GmailService;
use App\Services\MockGmailService;
use App\Models\SystemConfig;
use App\Models\ImportedEmail;
use App\Models\User;

class TestEmailSending extends Command
{
    protected $signature = 'test:email-sending 
                            {--to= : Email de destino (requerido)}
                            {--case-id= : ID del caso para respuesta}
                            {--immediate : Enviar inmediatamente sin bandeja de salida}
                            {--mock : Usar servicio mock}';
                            
    protected $description = 'Prueba el envío de correos completo con Gmail API';

    private OutboxEmailService $outboxService;
    private $gmailService;

    public function __construct(OutboxEmailService $outboxService)
    {
        parent::__construct();
        $this->outboxService = $outboxService;
    }

    public function handle()
    {
        // Seleccionar servicio según opciones
        $useMock = $this->option('mock') || (SystemConfig::getValue('gmail_auth_setup') === 'test_mode');
        
        if ($useMock) {
            $this->gmailService = new MockGmailService();
        } else {
            $this->gmailService = new GmailService();
        }

        $this->info('📧 PRUEBA DE ENVÍO DE CORREOS');
        $this->info('============================');
        $this->newLine();

        $toEmail = $this->option('to');
        if (!$toEmail) {
            $toEmail = $this->ask('¿A qué email quieres enviar la prueba?');
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Email inválido');
            return 1;
        }

        // Verificar autenticación Gmail
        if (!$this->verifyGmailAuth()) {
            return 1;
        }

        $caseId = $this->option('case-id');
        $immediate = $this->option('immediate');

        if ($immediate) {
            return $this->testImmediateSend($toEmail);
        } else {
            return $this->testOutboxFlow($toEmail, $caseId);
        }
    }

    private function verifyGmailAuth(): bool
    {
        $this->info('🔐 Verificando autenticación Gmail...');

        try {
            $isAuthenticated = $this->gmailService->isAuthenticated();
            $authType = ($this->gmailService instanceof MockGmailService) ? 'Mock' : 'Real';
            
            if (!$isAuthenticated) {
                $this->error("❌ Gmail no está autenticado ({$authType})");
                if ($authType === 'Real') {
                    $this->line('💡 Ejecuta: php artisan gmail:setup-oauth');
                } else {
                    $this->line('💡 Ejecuta: php artisan gmail:setup-test-auth');
                }
                return false;
            }

            $this->info("✅ Autenticación Gmail verificada ({$authType})");
            return true;

        } catch (\Exception $e) {
            $this->error('❌ Error verificando autenticación: ' . $e->getMessage());
            return false;
        }
    }

    private function testOutboxFlow(string $toEmail, $caseId = null): int
    {
        $this->info('📤 PRUEBA: Flujo completo con bandeja de salida');
        $this->line('─────────────────────────────────────────────');

        try {
            // Paso 1: Crear email de prueba en bandeja de salida
            $this->info('1️⃣ Creando email en bandeja de salida...');

            $user = User::where('role', 'ejecutivo')->first();
            if (!$user) {
                $this->error('❌ No hay usuarios ejecutivos configurados');
                return 1;
            }

            $emailData = [
                'case_id' => $caseId,
                'from_email' => $user->email ?: 'comunicaciones@orproverificaciones.cl',
                'from_name' => $user->name ?: 'Sistema OMNIC',
                'to' => $toEmail,
                'subject' => 'Prueba de envío - ' . now()->format('Y-m-d H:i:s'),
                'message' => $this->getTestEmailContent(),
                'priority' => 'normal',
            ];

            $outboxEmail = $this->outboxService->createReply($emailData);
            $this->info("   ✅ Email creado en bandeja (ID: {$outboxEmail->id})");

            // Paso 2: Procesar bandeja de salida
            $this->info('2️⃣ Procesando bandeja de salida...');
            
            $results = $this->outboxService->processOutbox();
            
            $this->info('   📊 Resultados:');
            $this->line("      Procesados: {$results['processed']}");
            $this->line("      Enviados: {$results['sent']}");
            $this->line("      Fallidos: {$results['failed']}");

            // Paso 3: Verificar resultado
            $outboxEmail->refresh();
            
            if ($outboxEmail->send_status === 'sent') {
                $this->info('✅ EMAIL ENVIADO EXITOSAMENTE');
                $this->line("   📧 Gmail Message ID: {$outboxEmail->gmail_message_id}");
                $this->line("   🧵 Thread ID: {$outboxEmail->gmail_thread_id}");
                $this->line("   📅 Enviado: {$outboxEmail->sent_at}");
            } else {
                $this->error('❌ EMAIL NO SE PUDO ENVIAR');
                $this->line("   Estado: {$outboxEmail->send_status}");
                $this->line("   Error: {$outboxEmail->error_message}");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error en prueba de bandeja de salida: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function testImmediateSend(string $toEmail): int
    {
        $this->info('⚡ PRUEBA: Envío inmediato via Gmail API');
        $this->line('─────────────────────────────────────');

        try {
            $user = User::where('role', 'ejecutivo')->first();
            if (!$user) {
                $this->error('❌ No hay usuarios ejecutivos configurados');
                return 1;
            }

            $emailData = [
                'from_email' => $user->email ?: 'comunicaciones@orproverificaciones.cl',
                'from_name' => $user->name ?: 'Sistema OMNIC',
                'to' => $toEmail,
                'subject' => 'Prueba envío inmediato - ' . now()->format('Y-m-d H:i:s'),
                'body' => $this->getTestEmailContent(),
            ];

            $this->info('📤 Enviando email directamente...');
            
            $result = $this->gmailService->sendEmail($emailData);

            if ($result['success']) {
                $this->info('✅ EMAIL ENVIADO EXITOSAMENTE');
                $this->line("   📧 Gmail Message ID: {$result['message_id']}");
                $this->line("   🧵 Thread ID: {$result['thread_id']}");
                $this->line("   📅 Enviado: {$result['sent_at']}");
            } else {
                $this->error('❌ ERROR AL ENVIAR EMAIL');
                $this->line("   Error: {$result['error']}");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error en envío inmediato: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function getTestEmailContent(): string
    {
        $user = auth()->user() ? auth()->user()->name : 'Sistema OMNIC';
        
        return "Estimado usuario,

Este es un email de prueba del sistema OMNIC para verificar que el envío de correos está funcionando correctamente.

Detalles de la prueba:
- Fecha y hora: " . now()->format('Y-m-d H:i:s') . "
- Usuario: {$user}
- Sistema: OMNIC Omnicanal
- Método: Gmail API

Si recibe este correo, significa que:
✅ La autenticación con Gmail API funciona
✅ El sistema puede enviar correos
✅ La configuración está correcta

Por favor, no responda a este email ya que es solo una prueba automatizada.

Saludos cordiales,
Equipo OMNIC";
    }
}