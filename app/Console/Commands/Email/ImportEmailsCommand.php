<?php

namespace App\Console\Commands\Email;

use Illuminate\Console\Command;
use App\Services\Email\EmailImportService;
use Illuminate\Support\Facades\Log;

class ImportEmailsCommand extends Command
{
    protected $signature = 'email:import 
                           {--group=* : Specific Gmail groups to import from}
                           {--days=7 : Number of days back to import}
                           {--limit=100 : Maximum emails to import per group}
                           {--dry-run : Run without making changes}';

    protected $description = 'Import emails from Gmail groups into the system';

    private EmailImportService $importService;

    public function __construct(EmailImportService $importService)
    {
        parent::__construct();
        $this->importService = $importService;
    }

    public function handle(): int
    {
        $this->info('🔄 Iniciando importación de correos...');

        $options = [
            'groups' => $this->option('group'),
            'days' => (int) $this->option('days'),
            'limit' => (int) $this->option('limit'),
            'dry_run' => $this->option('dry-run'),
        ];

        try {
            $results = $this->importService->importEmails($options);

            $this->displayResults($results);

            if ($results['total_imported'] > 0) {
                $this->info("✅ Importación completada: {$results['total_imported']} correos importados");
                return self::SUCCESS;
            } else {
                $this->warn("⚠️  No se importaron correos nuevos");
                return self::SUCCESS;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error durante la importación: " . $e->getMessage());
            Log::error('Email import failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    private function displayResults(array $results): void
    {
        $this->table([
            'Grupo', 'Procesados', 'Importados', 'Omitidos', 'Errores'
        ], $results['by_group'] ?? []);

        $this->info("📊 Resumen:");
        $this->line("  Total procesados: {$results['total_processed']}");
        $this->line("  Total importados: {$results['total_imported']}");
        $this->line("  Total omitidos: {$results['total_skipped']}");
        $this->line("  Total errores: {$results['total_errors']}");
    }
}