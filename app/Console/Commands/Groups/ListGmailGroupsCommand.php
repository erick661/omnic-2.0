<?php

namespace App\Console\Commands\Groups;

use Illuminate\Console\Command;
use App\Services\Groups\GmailGroupService;

class ListGmailGroupsCommand extends Command
{
    protected $signature = 'groups:list 
                           {--active-only : Show only active groups}
                           {--with-stats : Include email statistics}';

    protected $description = 'List all Gmail groups configured in the system';

    private GmailGroupService $groupService;

    public function __construct(GmailGroupService $groupService)
    {
        parent::__construct();
        $this->groupService = $groupService;
    }

    public function handle(): int
    {
        $this->info('📧 Listando grupos de Gmail...');

        try {
            $options = [
                'active_only' => $this->option('active-only'),
                'with_stats' => $this->option('with-stats'),
            ];

            $groups = $this->groupService->listGroups($options);

            if (empty($groups)) {
                $this->warn('⚠️  No se encontraron grupos configurados');
                return self::SUCCESS;
            }

            $this->displayGroups($groups, $options['with_stats']);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error listando grupos: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function displayGroups(array $groups, bool $withStats): void
    {
        $headers = ['ID', 'Nombre', 'Email', 'Estado'];
        
        if ($withStats) {
            $headers = array_merge($headers, ['Total Correos', 'Pendientes', 'Resueltos']);
        }

        $rows = [];
        foreach ($groups as $group) {
            $row = [
                $group['id'],
                $group['name'],
                $group['email'],
                $group['is_active'] ? '✅ Activo' : '❌ Inactivo'
            ];

            if ($withStats) {
                $row = array_merge($row, [
                    $group['stats']['total_emails'] ?? 0,
                    $group['stats']['pending'] ?? 0,
                    $group['stats']['resolved'] ?? 0
                ]);
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);
        $this->info("📊 Total de grupos: " . count($groups));
    }
}