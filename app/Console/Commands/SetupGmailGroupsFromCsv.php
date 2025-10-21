<?php

namespace App\Console\Commands;

use App\Models\GmailGroup;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupGmailGroupsFromCsv extends Command
{
    protected $signature = 'gmail:setup-groups-from-csv {file} {--dry-run : Solo mostrar qué se creará sin ejecutar}';
    protected $description = 'Importar grupos Gmail y usuarios ejecutivos desde CSV de Google Groups';

    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->error("❌ Archivo no encontrado: {$file}");
            return 1;
        }

        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('🧪 MODO DRY-RUN - Solo mostrando qué se creará');
        }

        $this->info('📂 Procesando archivo CSV...');
        
        $csvData = array_map('str_getcsv', file($file));
        $header = array_shift($csvData); // Remover header
        
        $grupos = collect($csvData)->filter(function($row) {
            return !empty($row[1]); // Filtrar filas vacías
        })->map(function ($row) {
            return [
                'nombre' => trim($row[0]),
                'email_original' => trim($row[1]),
                'email_nuevo' => str_replace('@orpro.cl', '@orproverificaciones.cl', trim($row[1])),
                'relacion' => trim($row[2])
            ];
        });

        $this->info("📊 Encontrados {$grupos->count()} grupos en CSV");

        // 1. Crear cuenta principal de comunicaciones
        $this->createCommunicationsAccount($isDryRun);

        // 2. Crear grupos Gmail
        $this->createGmailGroups($grupos, $isDryRun);

        // 3. Crear usuarios ejecutivos
        $this->createExecutiveUsers($grupos, $isDryRun);

        if ($isDryRun) {
            $this->info('');
            $this->info('💡 Para ejecutar realmente: php artisan gmail:setup-groups-from-csv ' . $file);
        } else {
            $this->info('✅ Proceso completado exitosamente!');
        }
        
        return 0;
    }

    private function createCommunicationsAccount($isDryRun = false)
    {
        $this->info('👤 Creando cuenta principal de comunicaciones...');
        
        if ($isDryRun) {
            $this->line('  [DRY-RUN] Usuario: Sistema Comunicaciones');
            $this->line('  [DRY-RUN] Email: comunicaciones@orproverificaciones.cl');
            $this->line('  [DRY-RUN] Rol: administrador');
            return;
        }

        $user = User::updateOrCreate(
            ['email' => 'comunicaciones@orproverificaciones.cl'],
            [
                'name' => 'Sistema',
                'last_name' => 'Comunicaciones',
                'email' => 'comunicaciones@orproverificaciones.cl',
                'password' => bcrypt('temp_password_' . Str::random(8)),
                'role' => 'administrador',
                'is_active' => true,
            ]
        );

        $this->info("✅ Usuario sistema creado: {$user->email}");
    }

    private function createGmailGroups($grupos, $isDryRun = false)
    {
        $this->info('📧 Creando grupos Gmail...');
        
        foreach ($grupos as $index => $grupo) {
            if ($isDryRun) {
                $grupoNum = $index + 1;
                $this->line("  [DRY-RUN] Grupo #{$grupoNum}: {$grupo['nombre']}");
                $this->line("    Email: {$grupo['email_nuevo']}");
                $this->line("    Relación: {$grupo['relacion']}");
            } else {
                $gmailGroup = GmailGroup::updateOrCreate(
                    ['email' => $grupo['email_nuevo']],
                    [
                        'name' => $grupo['nombre'],
                        'email' => $grupo['email_nuevo'],
                        'is_active' => true,
                        'description' => "Grupo importado desde CSV - Relación: {$grupo['relacion']}"
                    ]
                );

                $this->line("  📫 {$gmailGroup->name} - {$gmailGroup->email}");
            }
        }

        if (!$isDryRun) {
            $this->info("✅ {$grupos->count()} grupos Gmail creados/actualizados");
        }
    }

    private function createExecutiveUsers($grupos, $isDryRun = false)
    {
        $this->info('👥 Creando usuarios ejecutivos...');
        
        // Filtrar solo los grupos de ejecutivos (excluyendo el grupo especial)
        $ejecutivos = $grupos->filter(function ($grupo) {
            return Str::contains($grupo['email_nuevo'], 'ejecutivo.') && 
                   !Str::contains($grupo['email_nuevo'], 'distribucion.escritos.presentados');
        });

        $this->info("👤 Ejecutivos a crear: {$ejecutivos->count()}");

        foreach ($ejecutivos as $index => $ejecutivo) {
            $emailParts = $this->parseExecutiveEmail($ejecutivo['email_nuevo']);
            
            if ($emailParts) {
                if ($isDryRun) {
                    $ejecutivoNum = $index + 1;
                    $this->line("  [DRY-RUN] Ejecutivo #{$ejecutivoNum}: {$emailParts['name']} {$emailParts['last_name']}");
                    $this->line("    Email: {$ejecutivo['email_nuevo']}");
                    $this->line("    Rol: ejecutivo");
                } else {
                    $user = User::updateOrCreate(
                        ['email' => $ejecutivo['email_nuevo']],
                        [
                            'name' => $emailParts['name'],
                            'last_name' => $emailParts['last_name'],
                            'email' => $ejecutivo['email_nuevo'],
                            'password' => bcrypt('temp_password_' . Str::random(8)),
                            'role' => 'ejecutivo',
                            'is_active' => true,
                        ]
                    );

                    $this->line("  👤 {$user->name} {$user->last_name} - {$user->email}");
                }
            } else {
                $this->warn("  ⚠️ No se pudo parsear email: {$ejecutivo['email_nuevo']}");
            }
        }

        if (!$isDryRun) {
            $this->info("✅ {$ejecutivos->count()} usuarios ejecutivos creados/actualizados");
        }
    }

    private function parseExecutiveEmail($email)
    {
        // ejecutivo.name.lastname@orproverificaciones.cl
        $localPart = explode('@', $email)[0]; // ejecutivo.name.lastname
        $parts = explode('.', $localPart); // [ejecutivo, name, lastname...]
        
        if (count($parts) >= 3) {
            array_shift($parts); // Remover 'ejecutivo'
            
            if (count($parts) == 2) {
                // Caso simple: ejecutivo.name.lastname
                return [
                    'name' => ucwords(str_replace(['_', '-'], ' ', $parts[0])),
                    'last_name' => ucwords(str_replace(['_', '-'], ' ', $parts[1]))
                ];
            } else {
                // Casos complejos: ejecutivo.name.lastname.extra o ejecutivo.juanmanuel.contreras
                $name = ucwords(str_replace(['_', '-'], ' ', $parts[0]));
                $lastName = ucwords(str_replace(['_', '-'], ' ', implode(' ', array_slice($parts, 1))));
                
                return [
                    'name' => $name,
                    'last_name' => $lastName
                ];
            }
        }
        
        return null;
    }
}