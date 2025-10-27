<?php

namespace App\Console\Commands\Groups;

use Illuminate\Console\Command;
use App\Services\Groups\GmailGroupService;

class CreateGmailGroupCommand extends Command
{
    protected $signature = 'groups:create 
                           {name : Group name}
                           {email : Group email address}
                           {--description= : Group description}
                           {--auto-assign : Enable auto-assignment of emails}
                           {--import-enabled=true : Enable email import for this group}';

    protected $description = 'Create a new Gmail group for email management';

    private GmailGroupService $groupService;

    public function __construct(GmailGroupService $groupService)
    {
        parent::__construct();
        $this->groupService = $groupService;
    }

    public function handle(): int
    {
        $groupData = [
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'description' => $this->option('description'),
            'auto_assign' => $this->option('auto-assign'),
            'import_enabled' => $this->option('import-enabled') === 'true',
        ];

        $this->info("📧 Creando grupo de Gmail: {$groupData['name']}");

        try {
            // Validar email
            if (!filter_var($groupData['email'], FILTER_VALIDATE_EMAIL)) {
                $this->error("❌ Email inválido: {$groupData['email']}");
                return self::FAILURE;
            }

            // Crear grupo
            $group = $this->groupService->createGroup($groupData);

            $this->info("✅ Grupo creado exitosamente");
            $this->displayGroupInfo($group);

            // Verificar acceso a Gmail
            if ($this->confirm('¿Desea verificar el acceso a este grupo en Gmail?', true)) {
                $this->verifyGmailAccess($group);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error creando grupo: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function displayGroupInfo(array $group): void
    {
        $this->table([
            'Campo', 'Valor'
        ], [
            ['ID', $group['id']],
            ['Nombre', $group['name']],
            ['Email', $group['email']],
            ['Descripción', $group['description'] ?: 'N/A'],
            ['Auto-asignación', $group['auto_assign'] ? 'Habilitada' : 'Deshabilitada'],
            ['Importación', $group['import_enabled'] ? 'Habilitada' : 'Deshabilitada'],
            ['Estado', $group['is_active'] ? 'Activo' : 'Inactivo'],
        ]);
    }

    private function verifyGmailAccess(array $group): void
    {
        $this->info("🔍 Verificando acceso a Gmail para: {$group['email']}");
        
        try {
            $result = $this->groupService->verifyGmailAccess($group['email']);
            
            if ($result['success']) {
                $this->info("✅ Acceso verificado correctamente");
                $this->line("  - Correos encontrados: {$result['email_count']}");
                $this->line("  - Última actividad: {$result['last_activity']}");
            } else {
                $this->warn("⚠️  Problema de acceso: {$result['error']}");
                $this->line("Asegúrese de que la cuenta de servicio tenga acceso a este grupo");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error verificando acceso: " . $e->getMessage());
        }
    }
}