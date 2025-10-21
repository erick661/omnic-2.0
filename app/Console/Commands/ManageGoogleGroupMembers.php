<?php

namespace App\Console\Commands;

use App\Models\GmailGroup;
use App\Services\GmailServiceManager;
use Illuminate\Console\Command;
use Google\Service\Directory;

class ManageGoogleGroupMembers extends Command
{
    protected $signature = 'gmail:manage-group-members 
                            {action : Acción a realizar (list, remove, info)}
                            {--group= : Email específico del grupo}
                            {--email= : Email del miembro (para remove)}
                            {--all-groups : Aplicar a todos los grupos}';

    protected $description = 'Gestionar miembros de grupos de Google Workspace (listar, remover, info)';

    public function handle()
    {
        $action = $this->argument('action');
        $specificGroup = $this->option('group');
        $memberEmail = $this->option('email');
        $allGroups = $this->option('all-groups');

        // Validar acción
        $validActions = ['list', 'remove', 'info'];
        if (!in_array($action, $validActions)) {
            $this->error("❌ Acción inválida. Debe ser una de: " . implode(', ', $validActions));
            return 1;
        }

        // Verificar parámetros según la acción
        if (!$specificGroup && !$allGroups) {
            $this->error('❌ Debes especificar --group=EMAIL o --all-groups');
            return 1;
        }

        if ($action === 'remove' && !$memberEmail) {
            $this->error('❌ Para remover necesitas especificar --email=EMAIL');
            return 1;
        }

        // Verificar autenticación OAuth
        try {
            $gmailService = app(GmailServiceManager::class);
            if (!$gmailService->isAuthenticated()) {
                $this->error('❌ No hay autenticación OAuth configurada. Ejecuta: php artisan gmail:setup-oauth');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error al verificar autenticación: ' . $e->getMessage());
            return 1;
        }

        // Obtener grupos
        $query = GmailGroup::where('is_active', true);
        if ($specificGroup) {
            $query->where('email', $specificGroup);
        }
        $grupos = $query->get();

        if ($grupos->isEmpty()) {
            if ($specificGroup) {
                $this->error("❌ No se encontró el grupo: {$specificGroup}");
            } else {
                $this->error('❌ No se encontraron grupos activos');
            }
            return 1;
        }

        try {
            $directoryService = $this->getDirectoryService();
            
            switch ($action) {
                case 'list':
                    $this->listMembers($directoryService, $grupos);
                    break;
                case 'remove':
                    $this->removeMembers($directoryService, $grupos, $memberEmail);
                    break;
                case 'info':
                    $this->showGroupInfo($directoryService, $grupos);
                    break;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function getDirectoryService()
    {
        $gmailServiceManager = app(GmailServiceManager::class);
        $client = $gmailServiceManager->getAuthenticatedClient();
        
        if (!$client) {
            throw new \Exception('No se pudo obtener cliente autenticado');
        }
        
        return new Directory($client);
    }

    private function listMembers(Directory $service, $grupos)
    {
        $this->info('👥 Listando miembros de grupos:');

        foreach ($grupos as $grupo) {
            try {
                $this->line('');
                $this->line("📫 Grupo: {$grupo->name} ({$grupo->email})");

                $members = $service->members->listMembers($grupo->email);
                
                if ($members->getMembers()) {
                    $memberData = [];
                    foreach ($members->getMembers() as $member) {
                        $memberData[] = [
                            'Email' => $member->getEmail(),
                            'Rol' => $member->getRole(),
                            'Tipo' => $member->getType(),
                            'Estado' => $member->getStatus() ?: 'ACTIVE'
                        ];
                    }
                    
                    $this->table(['Email', 'Rol', 'Tipo', 'Estado'], $memberData);
                } else {
                    $this->line('  ℹ️  No hay miembros en este grupo');
                }

            } catch (\Exception $e) {
                $this->line("  ❌ Error listando {$grupo->email}: {$e->getMessage()}");
            }
        }
    }

    private function removeMembers(Directory $service, $grupos, string $memberEmail)
    {
        $this->warn("🗑️  Removiendo {$memberEmail} de grupos...");

        if (!$this->confirm("¿Estás seguro de remover {$memberEmail} de los grupos seleccionados?")) {
            $this->info('❌ Operación cancelada');
            return;
        }

        $removed = 0;
        $notFound = 0;
        $errors = 0;

        foreach ($grupos as $grupo) {
            try {
                $service->members->delete($grupo->email, $memberEmail);
                $this->line("  ✅ Removido de: {$grupo->email}");
                $removed++;

            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() === 404) {
                    $this->line("  ℹ️  No era miembro de: {$grupo->email}");
                    $notFound++;
                } else {
                    $this->line("  ❌ Error en {$grupo->email}: {$e->getMessage()}");
                    $errors++;
                }
            }

            usleep(200000); // Pausa para rate limiting
        }

        $this->info("📊 RESUMEN:");
        $this->info("  ✅ Removidos: {$removed}");
        $this->info("  ℹ️  No encontrados: {$notFound}");
        if ($errors > 0) {
            $this->warn("  ⚠️  Errores: {$errors}");
        }
    }

    private function showGroupInfo(Directory $service, $grupos)
    {
        $this->info('ℹ️  Información de grupos:');

        foreach ($grupos as $grupo) {
            try {
                $this->line('');
                $googleGroup = $service->groups->get($grupo->email);
                $members = $service->members->listMembers($grupo->email);
                
                $memberCount = $members->getMembers() ? count($members->getMembers()) : 0;

                $this->table(['Campo', 'Valor'], [
                    ['Nombre', $googleGroup->getName()],
                    ['Email', $googleGroup->getEmail()],
                    ['Descripción', $googleGroup->getDescription() ?: 'Sin descripción'],
                    ['Miembros', $memberCount],
                    ['Creado en Google', $googleGroup->getId() ? 'Sí' : 'No'],
                ]);

            } catch (\Exception $e) {
                $this->line("❌ Error obteniendo info de {$grupo->email}: {$e->getMessage()}");
            }
        }
    }
}