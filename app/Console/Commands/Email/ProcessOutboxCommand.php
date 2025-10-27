<?php

namespace App\Console\Commands\Email;

use Illuminate\Console\Command;
use App\Services\Email\EmailSendService;
use App\Services\Email\OutboxEmailService;

class ProcessOutboxCommand extends Command
{
    protected $signature = 'email:process-outbox 
                           {--limit=50 : Maximum emails to process}
                           {--retry : Retry failed emails}';

    protected $description = 'Process pending emails in the outbox and send them';

    private OutboxEmailService $outboxService;

    public function __construct(OutboxEmailService $outboxService)
    {
        parent::__construct();
        $this->outboxService = $outboxService;
    }

    public function handle(): int
    {
        $this->info('📤 Procesando bandeja de salida...');

        try {
            // Procesar bandeja de salida normal
            $results = $this->outboxService->processOutbox([
                'limit' => (int) $this->option('limit')
            ]);

            $this->displayResults($results);

            // Si se solicita, reintentar correos fallidos
            if ($this->option('retry')) {
                $this->info('🔄 Reintentando correos fallidos...');
                $retryResults = $this->outboxService->retryFailedEmails();
                $this->displayRetryResults($retryResults);
            }

            $this->info('✅ Procesamiento de bandeja de salida completado');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error procesando bandeja de salida: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function displayResults(array $results): void
    {
        $this->info("📊 Resultados del procesamiento:");
        $this->line("  Procesados: {$results['processed']}");
        $this->line("  Enviados: {$results['sent']}");
        $this->line("  Fallidos: {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->warn("⚠️  Errores encontrados:");
            foreach ($results['errors'] as $error) {
                $this->line("  - Email {$error['email_id']}: {$error['error']}");
            }
        }
    }

    private function displayRetryResults(array $results): void
    {
        $this->info("🔄 Resultados de reintentos:");
        $this->line("  Reintentados: {$results['retried']}");
        
        if (!empty($results['errors'])) {
            $this->warn("⚠️  Errores en reintentos:");
            foreach ($results['errors'] as $error) {
                $this->line("  - Email {$error['email_id']}: {$error['error']}");
            }
        }
    }
}