<?php

namespace App\Console\Commands;

use App\Models\GmailGroup;
use App\Services\GmailServiceManager;
use Illuminate\Console\Command;
use Google\Service\Directory;
use Google\Service\Directory\Member;

class AddMembersToGoogleGroups extends Command
{
    protected $signature = 'gmail:add-members-to-groups 
                            {--email=admin@orproverificaciones.cl : Email del miembro a agregar}
                            {--group= : Email específico del grupo (opcional, por defecto todos)}
                            {--dry-run : Solo mostrar qué se haría sin ejecutar}
                            {--force : No pedir confirmación}
                            {--role=MEMBER : Rol del miembro (MEMBER, MANAGER, OWNER)}';

    protected $description = 'Agregar miembro específico a grupos de Google Workspace';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');
        $memberEmail = $this->option('email');
        $specificGroup = $this->option('group');
        $memberRole = strtoupper($this->option('role'));

        // Validar rol
        $validRoles = ['MEMBER', 'MANAGER', 'OWNER'];
        if (!in_array($memberRole, $validRoles)) {
            $this->error("❌ Rol inválido. Debe ser uno de: " . implode(', ', $validRoles));
            return 1;
        }

        if ($isDryRun) {
            $this->info('🔍 MODO DRY-RUN - No se agregarán miembros reales');
        } else {
            $this->info('👥 Agregando miembros a grupos de Google Workspace...');
        }

        $this->info("📧 Miembro a agregar: {$memberEmail}");
        $this->info("🏷️  Rol: {$memberRole}");

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

        $this->info("📊 Encontrados {$grupos->count()} grupos");

        if (!$isDryRun && !$force) {
            $action = $specificGroup ? "al grupo {$specificGroup}" : "a {$grupos->count()} grupos";
            if (!$this->confirm("¿Agregar {$memberEmail} como {$memberRole} {$action}?")) {
                $this->info('❌ Operación cancelada');
                return 0;
            }
        }

        $successful = 0;
        $alreadyExists = 0;
        $errors = 0;

        try {
            $directoryService = $isDryRun ? null : $this->getDirectoryService();

            foreach ($grupos as $grupo) {
                try {
                    if ($isDryRun) {
                        $this->line("  👤 [DRY-RUN] Agregaría {$memberEmail} como {$memberRole} a: {$grupo->email}");
                        $successful++;
                    } else {
                        $result = $this->addMemberToGroup($directoryService, $grupo->email, $memberEmail, $memberRole);
                        
                        if ($result === 'exists') {
                            $this->line("  ℹ️  {$memberEmail} ya es miembro de: {$grupo->email}");
                            $alreadyExists++;
                        } elseif ($result === 'added') {
                            $this->line("  ✅ {$memberEmail} agregado a: {$grupo->email}");
                            $successful++;
                        }
                    }
                } catch (\Exception $e) {
                    $this->line("  ❌ Error en {$grupo->email}: {$e->getMessage()}");
                    $errors++;
                }

                // Pausa para evitar rate limiting
                if (!$isDryRun && $grupos->count() > 10) {
                    usleep(200000); // 0.2 segundos
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Error al configurar Directory Service: {$e->getMessage()}");
            return 1;
        }

        // Resumen
        $this->info('📊 RESUMEN:');
        $this->info("  ✅ Agregados exitosamente: {$successful}");
        if ($alreadyExists > 0) {
            $this->info("  ℹ️  Ya existían: {$alreadyExists}");
        }
        if ($errors > 0) {
            $this->warn("  ⚠️  Errores: {$errors}");
        }

        if ($isDryRun) {
            $this->info('💡 Para ejecutar realmente:');
            $command = 'php artisan gmail:add-members-to-groups --email=' . $memberEmail;
            if ($specificGroup) {
                $command .= ' --group=' . $specificGroup;
            }
            if ($memberRole !== 'MEMBER') {
                $command .= ' --role=' . $memberRole;
            }
            $this->line("   {$command}");
        } else {
            $this->info('🎉 ¡Proceso completado!');
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

    private function addMemberToGroup(Directory $service, string $groupEmail, string $memberEmail, string $role): string
    {
        try {
            // Verificar si el miembro ya existe
            try {
                $existingMember = $service->members->get($groupEmail, $memberEmail);
                
                // Si existe, verificar si necesita actualizar el rol
                if ($existingMember->getRole() !== $role) {
                    $existingMember->setRole($role);
                    $service->members->patch($groupEmail, $memberEmail, $existingMember);
                    $this->line("    🔄 Rol actualizado de {$existingMember->getRole()} a {$role}");
                    return 'added';
                }
                
                return 'exists';
                
            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() !== 404 && $e->getCode() !== 400) {
                    throw $e;
                }
                // El miembro no existe, proceder a agregarlo
            }

            // Crear el miembro
            $member = new Member();
            $member->setEmail($memberEmail);
            $member->setRole($role);
            $member->setType('USER');
            
            // Configuraciones adicionales para evitar errores
            $member->setDeliverySettings('ALL_MAIL');

            $service->members->insert($groupEmail, $member);
            
            return 'added';

        } catch (\Google\Service\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();

            if ($errorCode === 409) {
                return 'exists';
            } elseif ($errorCode === 400) {
                if (strpos($errorMessage, 'memberKey') !== false) {
                    throw new \Exception("El usuario {$memberEmail} no existe en el dominio Google Workspace");
                } elseif (strpos($errorMessage, 'duplicate') !== false) {
                    return 'exists';
                } else {
                    throw new \Exception("Error de validación: {$errorMessage}");
                }
            } elseif ($errorCode === 403) {
                throw new \Exception("Sin permisos para agregar miembros al grupo");
            } elseif ($errorCode === 404) {
                throw new \Exception("El grupo no existe o no se tiene acceso");
            } else {
                throw new \Exception("Error de Google API: {$errorMessage} (Código: {$errorCode})");
            }
        }
    }
}