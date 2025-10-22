<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OutboxEmailService;
use App\Models\OutboxEmail;

class ProcessOutboxEmails extends Command
{
    protected $signature = 'emails:send-outbox {--retry : Reintentar emails fallidos} {--stats : Solo mostrar estadísticas}';
    protected $description = 'Procesa y envía correos de la bandeja de salida';

    private OutboxEmailService $outboxService;

    public function __construct(OutboxEmailService $outboxService)
    {
        parent::__construct();
        $this->outboxService = $outboxService;
    }

    public function handle()
    {
        $this->info('📧 PROCESAMIENTO DE BANDEJA DE SALIDA');
        $this->info('===================================');
        $this->newLine();

        // Solo mostrar estadísticas si se solicita
        if ($this->option('stats')) {
            return $this->showStats();
        }

        // Reintentar emails fallidos si se solicita
        if ($this->option('retry')) {
            $this->retryFailedEmails();
        }

        // Procesar bandeja de salida principal
        $this->processOutbox();

        return 0;
    }

    private function processOutbox()
    {
        $this->info('🔄 Procesando correos pendientes...');

        try {
            $results = $this->outboxService->processOutbox();

            $this->info("📊 Resultados del procesamiento:");
            $this->line("   📧 Procesados: {$results['processed']}");
            $this->line("   ✅ Enviados: {$results['sent']}");
            $this->line("   ❌ Fallidos: {$results['failed']}");

            if (!empty($results['errors'])) {
                $this->newLine();
                $this->error("⚠️ Errores encontrados:");
                foreach ($results['errors'] as $error) {
                    $this->line("   Email ID {$error['email_id']}: {$error['error']}");
                }
            }

            if ($results['sent'] > 0) {
                $this->info("✅ {$results['sent']} correos enviados exitosamente");
            }

            if ($results['failed'] > 0) {
                $this->warn("⚠️ {$results['failed']} correos fallaron - se pueden reintentar con --retry");
            }

            if ($results['processed'] === 0) {
                $this->info("ℹ️ No hay correos pendientes para procesar");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error procesando bandeja de salida: " . $e->getMessage());
            return 1;
        }

        $this->newLine();
    }

    private function retryFailedEmails()
    {
        $this->info('🔄 Reintentando correos fallidos...');

        try {
            $results = $this->outboxService->retryFailedEmails();

            $this->info("📊 Reintentos realizados:");
            $this->line("   🔄 Correos marcados para reintento: {$results['retried']}");

            if (!empty($results['errors'])) {
                $this->error("⚠️ Errores en reintentos:");
                foreach ($results['errors'] as $error) {
                    $this->line("   Email ID {$error['email_id']}: {$error['error']}");
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Error reintentando correos: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function showStats()
    {
        try {
            $stats = $this->outboxService->getOutboxStats();

            $this->info('📊 ESTADÍSTICAS DE BANDEJA DE SALIDA');
            $this->line('=====================================');

            $this->table(
                ['Estado', 'Cantidad', 'Descripción'],
                [
                    ['⏳ Pendientes', $stats['pending'], 'Listos para enviar'],
                    ['✅ Enviados hoy', $stats['sent'], 'Enviados exitosamente hoy'],
                    ['❌ Fallidos', $stats['failed'], 'Requieren atención'],
                    ['⏰ Programados', $stats['scheduled'], 'Programados para después'],
                ]
            );

            // Mostrar algunos emails recientes
            $this->newLine();
            $this->info('📋 CORREOS RECIENTES:');
            $this->showRecentEmails();

        } catch (\Exception $e) {
            $this->error("❌ Error obteniendo estadísticas: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function showRecentEmails()
    {
        $recentEmails = OutboxEmail::with(['creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentEmails->isEmpty()) {
            $this->line('   No hay correos recientes');
            return;
        }

        $tableData = [];
        foreach ($recentEmails as $email) {
            $status = match($email->send_status) {
                'pending' => '⏳ Pendiente',
                'sending' => '📤 Enviando',
                'sent' => '✅ Enviado',
                'failed' => '❌ Fallido',
                default => $email->send_status
            };

            $tableData[] = [
                'ID' => $email->id,
                'Para' => substr($email->to_email, 0, 30),
                'Asunto' => substr($email->subject, 0, 40),
                'Estado' => $status,
                'Creado' => $email->created_at->format('H:i:s'),
                'Creador' => $email->creator ? $email->creator->name : 'Sistema',
            ];
        }

        $this->table(
            ['ID', 'Para', 'Asunto', 'Estado', 'Creado', 'Creador'],
            $tableData
        );
    }
}
