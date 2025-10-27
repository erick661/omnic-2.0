<?php

namespace App\Console\Commands\Email;

use Illuminate\Console\Command;
use App\Models\ImportedEmail;
use App\Models\OutboxEmail;
use App\Services\Email\EmailStatsService;

class EmailStatsCommand extends Command
{
    protected $signature = 'email:stats 
                           {--period=today : Period to show (today, week, month, all)}
                           {--group= : Specific Gmail group}
                           {--agent= : Specific agent ID}';

    protected $description = 'Show email system statistics';

    private EmailStatsService $statsService;

    public function __construct(EmailStatsService $statsService)
    {
        parent::__construct();
        $this->statsService = $statsService;
    }

    public function handle(): int
    {
        $period = $this->option('period');
        $this->info("📊 Estadísticas de correos - Período: {$period}");

        try {
            $stats = $this->statsService->getStats([
                'period' => $period,
                'group' => $this->option('group'),
                'agent' => $this->option('agent'),
            ]);

            $this->displayInboxStats($stats['inbox']);
            $this->displayOutboxStats($stats['outbox']);
            $this->displayAgentStats($stats['agents']);
            $this->displayGroupStats($stats['groups']);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error obteniendo estadísticas: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function displayInboxStats(array $stats): void
    {
        $this->info("\n📥 Correos Recibidos:");
        $this->table([
            'Estado', 'Cantidad'
        ], [
            ['Pendientes', $stats['pending']],
            ['Asignados', $stats['assigned']],
            ['En Progreso', $stats['in_progress']],
            ['Resueltos', $stats['resolved']],
            ['Cerrados', $stats['closed']],
        ]);
    }

    private function displayOutboxStats(array $stats): void
    {
        $this->info("\n📤 Correos Enviados:");
        $this->table([
            'Estado', 'Cantidad'
        ], [
            ['Pendientes', $stats['pending']],
            ['Enviados', $stats['sent']],
            ['Fallidos', $stats['failed']],
            ['Programados', $stats['scheduled']],
        ]);
    }

    private function displayAgentStats(array $stats): void
    {
        if (empty($stats)) return;

        $this->info("\n👥 Estadísticas por Agente:");
        $rows = [];
        foreach ($stats as $agent) {
            $rows[] = [
                $agent['name'],
                $agent['assigned'],
                $agent['resolved'],
                $agent['response_time'] . 'h promedio'
            ];
        }
        
        $this->table([
            'Agente', 'Asignados', 'Resueltos', 'Tiempo Respuesta'
        ], $rows);
    }

    private function displayGroupStats(array $stats): void
    {
        if (empty($stats)) return;

        $this->info("\n📧 Estadísticas por Grupo:");
        $rows = [];
        foreach ($stats as $group) {
            $rows[] = [
                $group['name'],
                $group['total_emails'],
                $group['pending'],
                $group['resolved']
            ];
        }
        
        $this->table([
            'Grupo', 'Total', 'Pendientes', 'Resueltos'
        ], $rows);
    }
}