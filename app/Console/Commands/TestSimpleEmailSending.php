<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;
use App\Models\OutboxEmail;
use App\Models\User;
use App\Models\GmailGroup;

class TestSimpleEmailSending extends Command
{
    protected $signature = 'test:simple-email-sending {--to= : Email destinatario} {--real : Usar API real}';
    protected $description = 'Prueba simple de envío de emails sin bandeja de salida';

    public function handle()
    {
        $this->info('📧 PRUEBA SIMPLE DE ENVÍO DE CORREOS');
        $this->info('==================================');
        $this->newLine();

        // Obtener destinatario
        $to = $this->option('to');
        if (!$to) {
            $to = $this->ask('Email destinatario', 'test@ejemplo.com');
        }

        // Validar email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Email inválido: ' . $to);
            return 1;
        }

        // Obtener usuario ejecutivo para enviar desde
        $user = User::where('role', 'ejecutivo')->first();
        if (!$user) {
            $this->error('❌ No hay usuarios ejecutivos configurados');
            return 1;
        }

        // Obtener grupo Gmail
        $group = GmailGroup::active()->first();
        if (!$group) {
            $this->error('❌ No hay grupos Gmail configurados');
            return 1;
        }

        $this->info("👤 Enviando desde: {$user->name} ({$group->email})");
        $this->info("📧 Enviando a: {$to}");
        $this->newLine();

        // Probar envío directo o simulado
        if ($this->option('real')) {
            return $this->testRealSending($to, $user, $group);
        } else {
            return $this->testMockSending($to, $user, $group);
        }
    }

    private function testRealSending($to, $user, $group)
    {
        $this->info('🌐 Probando envío REAL con Gmail API...');

        try {
            $gmailService = new GmailService();
            
            if (!$gmailService->isAuthenticated()) {
                $this->error('❌ Gmail no está autenticado');
                $this->info('💡 Ejecuta: php artisan gmail:setup-oauth');
                return 1;
            }

            $this->info('✅ Gmail autenticado correctamente');
            
            // Preparar datos del email
            $emailData = [
                'from_email' => $group->email,
                'from_name' => $user->name . ' ' . $user->last_name,
                'to' => $to,
                'subject' => 'Prueba OMNIC - ' . now()->format('Y-m-d H:i:s'),
                'body' => $this->generateTestEmailBody($user),
            ];

            $this->info('📤 Enviando correo...');
            $result = $gmailService->sendEmail($emailData);

            if ($result['success']) {
                // Registrar en bandeja de salida para histórico
                $outboxEmail = OutboxEmail::create([
                    'from_email' => $emailData['from_email'],
                    'from_name' => $emailData['from_name'],
                    'to_email' => $emailData['to'],
                    'subject' => $emailData['subject'],
                    'body_html' => $emailData['body'],
                    'body_text' => strip_tags($emailData['body']),
                    'send_status' => 'sent',
                    'sent_at' => now(),
                    'gmail_message_id' => $result['message_id'],
                    'gmail_thread_id' => $result['thread_id'],
                    'created_by' => $user->id,
                ]);

                $this->info('🎉 ¡Correo enviado exitosamente!');
                $this->table(
                    ['Campo', 'Valor'],
                    [
                        ['Message ID', $result['message_id']],
                        ['Thread ID', $result['thread_id']],
                        ['Enviado a', $to],
                        ['Fecha envío', $result['sent_at']],
                        ['Outbox ID', $outboxEmail->id],
                    ]
                );

                return 0;
            } else {
                $this->error('❌ Error enviando correo: ' . $result['error']);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Excepción enviando correo: ' . $e->getMessage());
            return 1;
        }
    }

    private function testMockSending($to, $user, $group)
    {
        $this->warn('🧪 Modo SIMULACIÓN - No se envía correo real');
        
        // Simular delay de envío
        $this->info('📤 Simulando envío...');
        sleep(1);
        
        // Registrar en bandeja de salida como enviado simulado
        $outboxEmail = OutboxEmail::create([
            'from_email' => $group->email,
            'from_name' => $user->name . ' ' . $user->last_name,
            'to_email' => $to,
            'subject' => 'Prueba OMNIC MOCK - ' . now()->format('Y-m-d H:i:s'),
            'body_html' => $this->generateTestEmailBody($user),
            'body_text' => strip_tags($this->generateTestEmailBody($user)),
            'send_status' => 'sent',
            'sent_at' => now(),
            'gmail_message_id' => 'mock_msg_' . time(),
            'gmail_thread_id' => 'mock_thread_' . time(),
            'created_by' => $user->id,
        ]);

        $this->info('✅ Correo "enviado" en modo simulación');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Message ID', $outboxEmail->gmail_message_id],
                ['Thread ID', $outboxEmail->gmail_thread_id],
                ['Enviado a', $to],
                ['Estado', $outboxEmail->send_status],
                ['Outbox ID', $outboxEmail->id],
            ]
        );

        return 0;
    }

    private function generateTestEmailBody($user)
    {
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #2563eb;">Prueba del Sistema OMNIC</h2>
    
    <p>Estimado/a usuario/a,</p>
    
    <p>Este es un correo de prueba del sistema OMNIC Omnicanal para verificar que el envío de correos está funcionando correctamente.</p>
    
    <div style="background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #374151;">Detalles de la prueba:</h3>
        <ul>
            <li><strong>Fecha y hora:</strong> ' . now()->format('Y-m-d H:i:s') . '</li>
            <li><strong>Usuario:</strong> ' . $user->name . ' ' . $user->last_name . '</li>
            <li><strong>Sistema:</strong> OMNIC Omnicanal</li>
            <li><strong>Método:</strong> Gmail API</li>
        </ul>
    </div>
    
    <p>Si recibe este correo, significa que:</p>
    <ul style="color: #059669;">
        <li>✅ La autenticación con Gmail API funciona</li>
        <li>✅ El sistema puede enviar correos</li>
        <li>✅ La configuración está correcta</li>
        <li>✅ El flujo completo de envío está operativo</li>
    </ul>
    
    <div style="border: 1px solid #d1d5db; padding: 10px; background-color: #fef3c7; border-radius: 5px; margin: 20px 0;">
        <p><strong>Nota:</strong> Por favor, no responda a este email ya que es solo una prueba automatizada.</p>
    </div>
    
    <p>Saludos cordiales,<br>
    <strong>' . $user->name . '</strong><br>
    Equipo OMNIC</p>
</div>';
    }
}