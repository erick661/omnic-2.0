<?php

namespace App\Console\Commands;

use App\Models\GmailGroup;
use App\Services\GmailServiceManager;
use Illuminate\Console\Command;
use Google\Service\Directory;
use Google\Service\Directory\Group;
use Google\Service\Directory\Member;

class CreateGoogleGroups extends Command
{
    protected $signature = 'gmail:create-google-groups {--dry-run} {--force} {--skip-members : No agregar miembros automáticamente}';
    protected $description = 'Crear grupos en Google Workspace desde los grupos locales';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        if ($isDryRun) {
            $this->info('🔍 MODO DRY-RUN - No se crearán grupos reales');
        } else {
            $this->info('🚀 Creando grupos en Google Workspace...');
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

        // Obtener grupos locales
        $grupos = GmailGroup::where('is_active', true)->get();
        $this->info("📊 Encontrados {$grupos->count()} grupos locales");

        if (!$isDryRun && !$force) {
            if (!$this->confirm('¿Continuar con la creación de grupos en Google Workspace?')) {
                $this->info('❌ Operación cancelada');
                return 0;
            }
        }

        $successful = 0;
        $errors = 0;
        $exists = 0;

        try {
            $directoryService = $isDryRun ? null : $this->getDirectoryService();
            
            foreach ($grupos as $grupo) {
                try {
                    if ($isDryRun) {
                        $this->line("  📫 [DRY-RUN] Crearía: {$grupo->name} - {$grupo->email}");
                        $this->line("    [DRY-RUN] Agregaría miembro: comunicaciones@orproverificaciones.cl");
                        $successful++;
                    } else {
                        $result = $this->createGoogleGroup($directoryService, $grupo);
                        
                        if ($result === 'exists') {
                            $this->line("  ℹ️  Ya existe: {$grupo->email}");
                            $exists++;
                        } else {
                            $this->line("  ✅ Creado: {$grupo->name} - {$grupo->email}");
                            $successful++;
                        }
                    }
                } catch (\Exception $e) {
                    $this->line("  ❌ Error: {$grupo->email} - {$e->getMessage()}");
                    $errors++;
                }
                
                // Pequeña pausa para evitar rate limiting
                if (!$isDryRun) {
                    usleep(250000); // 0.25 segundos
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Error al configurar Directory Service: {$e->getMessage()}");
            $this->info("💡 Verifica que tengas permisos de administrador y que Admin SDK esté habilitado");
            return 1;
        }

        // Resumen
        $this->info("📊 RESUMEN:");
        $this->info("  ✅ Exitosos: {$successful}");
        if ($exists > 0) {
            $this->info("  ℹ️  Ya existían: {$exists}");
        }
        if ($errors > 0) {
            $this->warn("  ⚠️  Errores: {$errors}");
        }

        if ($isDryRun) {
            $this->info('💡 Para ejecutar realmente: php artisan gmail:create-google-groups');
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

    private function createGoogleGroup(Directory $service, GmailGroup $gmailGroup)
    {
        try {
            // Verificar si el grupo ya existe
            try {
                $existingGroup = $service->groups->get($gmailGroup->email);
                // Si llegamos aquí, el grupo existe
                if (!$this->option('skip-members')) {
                    $this->line("    ℹ️  Grupo ya existe, verificando miembro...");
                    $this->addMemberToGroup($service, $gmailGroup->email, 'comunicaciones@orproverificaciones.cl');
                } else {
                    $this->line("    ℹ️  Grupo ya existe (sin verificar miembros)");
                }
                return 'exists';
            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() !== 404) {
                    throw $e; // Error diferente a "no encontrado"
                }
                // El grupo no existe, proceder a crearlo
            }

            // Crear el grupo
            $group = new Group();
            $group->setEmail($gmailGroup->email);
            $group->setName($gmailGroup->name);
            $group->setDescription($gmailGroup->description ?: "Grupo importado desde OMNIC - {$gmailGroup->name}");

            $createdGroup = $service->groups->insert($group);

            // Agregar miembro solo si no se especifica --skip-members
            if (!$this->option('skip-members')) {
                // Pequeña pausa antes de agregar miembro
                usleep(500000); // 0.5 segundos
                
                $this->addMemberToGroup($service, $gmailGroup->email, 'comunicaciones@orproverificaciones.cl');
            }

            return $createdGroup;

        } catch (\Google\Service\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();
            
            if ($errorCode === 409) {
                return 'exists'; // El grupo ya existe
            } elseif ($errorCode === 400) {
                throw new \Exception("Error de validación: {$errorMessage}");
            } elseif ($errorCode === 403) {
                throw new \Exception("Sin permisos para crear el grupo: {$errorMessage}");
            } else {
                throw new \Exception("Google API Error: {$errorMessage} (Code: {$errorCode})");
            }
        }
    }

    private function addMemberToGroup(Directory $service, $groupEmail, $memberEmail)
    {
        try {
            // Verificar si el miembro ya existe
            try {
                $service->members->get($groupEmail, $memberEmail);
                $this->line("    ℹ️  Miembro {$memberEmail} ya existe en el grupo");
                return;
            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() !== 404 && $e->getCode() !== 400) {
                    throw $e;
                }
                // El miembro no existe o hay un error de formato, proceder a agregarlo
            }

            // Crear el miembro con todos los campos requeridos
            $member = new Member();
            $member->setEmail($memberEmail);
            $member->setRole('MEMBER');
            $member->setType('USER');
            
            // Establecer delivery_settings para evitar errores
            $member->setDeliverySettings('ALL_MAIL');

            $service->members->insert($groupEmail, $member);
            $this->line("    ✅ Miembro {$memberEmail} agregado al grupo");
            
        } catch (\Google\Service\Exception $e) {
            // Capturar errores específicos comunes
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();
            
            if ($errorCode === 409) {
                $this->line("    ℹ️  Miembro {$memberEmail} ya existe en el grupo");
            } elseif ($errorCode === 400 && strpos($errorMessage, 'memberKey') !== false) {
                $this->line("    ⚠️  Error de memberKey para {$memberEmail} - El usuario puede no existir en el dominio");
            } elseif ($errorCode === 403) {
                $this->line("    ⚠️  Sin permisos para agregar {$memberEmail} al grupo");
            } else {
                $this->line("    ⚠️  No se pudo agregar miembro {$memberEmail}: {$errorMessage} (Code: {$errorCode})");
            }
        } catch (\Exception $e) {
            $this->line("    ⚠️  Error general agregando {$memberEmail}: {$e->getMessage()}");
        }
    }
}