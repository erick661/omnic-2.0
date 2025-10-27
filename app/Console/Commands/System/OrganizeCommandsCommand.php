<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;

class OrganizeCommandsCommand extends Command
{
    protected $signature = 'system:organize-commands';
    protected $description = 'Reorganize existing commands into the new structure (file operations only)';

    // Mapeo de comandos existentes a las nuevas categorías
    private array $commandMap = [
        // Comandos de Email
        'Email' => [
            'ImportEmails.php',
            'ProcessOutboxEmails.php',
            'SendTestEmail.php',
            'TestEmailSending.php',
            'TestSimpleEmailSending.php',
            'SearchGroupEmails.php',
            'EmailSystemStatus.php',
            'AssignEmailToAgent.php',
        ],
        
        // Comandos de Grupos
        'Groups' => [
            'CreateGmailGroup.php',
            'CreateGoogleGroups.php',
            'ListGmailGroups.php',
            'ManageGmailGroupMembers.php',
            'ManageGoogleGroupMembers.php',
            'AddMembersToGoogleGroups.php',
            'SetupGmailGroupsFromCsv.php',
        ],
        
        // Comandos de Sistema/Setup
        'System' => [
            'SetupCompleteSystem.php',
            'SetupGmailOAuth.php',
            'SetupGmailTestAuth.php',
            'DiagnoseGooglePermissions.php',
            'DiagnoseServiceAccountPolicies.php',
        ],
        
        // Comandos de Test/Debug
        'Tests' => [
            'CleanTestData.php',
            'DiagnoseOAuth.php',
            'TestCompleteEmailFlow.php',
            'TestCompleteEmailFlowNew.php',
            'TestGmailAuth.php',
            'TestServiceAccount.php',
            'SimulateLiveEmailFlow.php',
        ],
    ];

    public function handle(): int
    {
        $this->info('🔄 Reorganizando comandos existentes...');
        
        $commandsPath = app_path('Console/Commands');
        $moved = 0;
        $errors = [];

        foreach ($this->commandMap as $category => $commands) {
            $this->info("📁 Procesando categoría: {$category}");
            
            foreach ($commands as $commandFile) {
                $sourcePath = "{$commandsPath}/{$commandFile}";
                $targetPath = "{$commandsPath}/{$category}/{$commandFile}";
                
                if (file_exists($sourcePath)) {
                    try {
                        // Crear directorio si no existe
                        $targetDir = dirname($targetPath);
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }
                        
                        // Mover archivo
                        if (rename($sourcePath, $targetPath)) {
                            $this->updateNamespace($targetPath, $category);
                            $this->line("  ✅ Movido: {$commandFile}");
                            $moved++;
                        } else {
                            $errors[] = "Error moviendo {$commandFile}";
                        }
                        
                    } catch (\Exception $e) {
                        $errors[] = "Error con {$commandFile}: " . $e->getMessage();
                    }
                } else {
                    $this->warn("  ⚠️  No encontrado: {$commandFile}");
                }
            }
        }

        // Mostrar resultados
        $this->info("\n📊 Resumen de reorganización:");
        $this->line("  Comandos movidos: {$moved}");
        $this->line("  Errores: " . count($errors));

        if (!empty($errors)) {
            $this->warn("\n⚠️  Errores encontrados:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        if ($moved > 0) {
            $this->info("\n✅ Reorganización completada");
            $this->line("Los comandos han sido organizados en las siguientes categorías:");
            foreach (array_keys($this->commandMap) as $category) {
                $this->line("  - {$category}/");
            }
            
            $this->warn("\n🔄 Recuerde ejecutar: composer dump-autoload");
        }

        return self::SUCCESS;
    }

    /**
     * Actualizar namespace en el archivo movido
     */
    private function updateNamespace(string $filePath, string $category): void
    {
        try {
            $content = file_get_contents($filePath);
            
            // Actualizar namespace
            $oldNamespace = 'namespace App\Console\Commands;';
            $newNamespace = "namespace App\Console\Commands\\{$category};";
            
            $content = str_replace($oldNamespace, $newNamespace, $content);
            
            file_put_contents($filePath, $content);
            
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Error actualizando namespace en {$filePath}: " . $e->getMessage());
        }
    }
}