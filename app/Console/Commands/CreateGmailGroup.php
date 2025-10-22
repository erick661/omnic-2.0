<?php

namespace App\Console\Commands;

use App\Models\GmailGroup;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Console\Command;

class CreateGmailGroup extends Command
{
    protected $signature = 'gmail:create-group {email} {--user-id= : ID del usuario a asociar} {--dry-run : Solo simular}';
    protected $description = 'Crear un grupo Gmail y opcionalmente asociarlo a un usuario';

    public function handle()
    {
        $groupEmail = $this->argument('email');
        $userId = $this->option('user-id');
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('🧪 MODO DRY-RUN - Solo simulando');
        }

        $this->info("📧 Creando grupo Gmail: {$groupEmail}");
        
        try {
            // Verificar si el grupo ya existe en nuestro sistema
            $existingGroup = GmailGroup::where('email', $groupEmail)->first();
            if ($existingGroup) {
                $this->warn("⚠️  El grupo {$groupEmail} ya existe en el sistema");
                $this->info("   Asociado al usuario ID: {$existingGroup->assigned_user_id}");
                return 0;
            }

            if (!$isDryRun) {
                // TODO: Aquí se podría integrar con Google Admin SDK para crear el grupo
                // Por ahora, asumimos que el grupo existe en Gmail
                
                $this->info("✅ Simulando creación del grupo en Gmail...");
                $this->info("   - Grupo: {$groupEmail}");
                $this->info("   - Miembro único: admin@orproverificaciones.cl");
            }

            // Crear registro en nuestra base de datos
            if ($userId) {
                $user = User::find($userId);
                if (!$user) {
                    $this->error("❌ Usuario con ID {$userId} no encontrado");
                    return 1;
                }

                if (!$isDryRun) {
                    $gmailGroup = GmailGroup::create([
                        'name' => $groupEmail,
                        'email' => $groupEmail,
                        'assigned_user_id' => $userId,
                        'is_active' => true,
                        'is_generic' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $this->info("✅ Grupo creado y asociado:");
                    $this->info("   - ID: {$gmailGroup->id}");
                    $this->info("   - Usuario: {$user->name} ({$user->email})");
                    $this->info("   - Grupo: {$groupEmail}");
                } else {
                    $this->info("✅ Se crearía registro:");
                    $this->info("   - Usuario: {$user->name} ({$user->email})");
                    $this->info("   - Grupo: {$groupEmail}");
                }
            } else {
                if (!$isDryRun) {
                    $gmailGroup = GmailGroup::create([
                        'name' => $groupEmail,
                        'email' => $groupEmail,
                        'assigned_user_id' => null,
                        'is_active' => true,
                        'is_generic' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $this->info("✅ Grupo creado sin asociar a usuario:");
                    $this->info("   - ID: {$gmailGroup->id}");
                    $this->info("   - Grupo: {$groupEmail}");
                } else {
                    $this->info("✅ Se crearía grupo sin asociar a usuario");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return 1;
        }
    }
}