<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseGooglePermissions extends Command
{
    protected $signature = 'google:diagnose-permissions {--project-id= : Google Cloud Project ID}';
    protected $description = 'Diagnosticar permisos y políticas de Google Cloud para Service Account';

    public function handle()
    {
        $projectId = $this->option('project-id');
        
        $this->info('🔍 DIAGNÓSTICO DE PERMISOS GOOGLE CLOUD');
        $this->info('=' . str_repeat('=', 50));
        
        $this->checkRequiredRoles();
        $this->provideGcloudCommands($projectId);
        $this->showManualSteps();
        
        return 0;
    }
    
    private function checkRequiredRoles(): void
    {
        $this->info("\n🔑 ROLES REQUERIDOS PARA CONFIGURAR SERVICE ACCOUNT");
        $this->info('-' . str_repeat('-', 50));
        
        $requiredRoles = [
            'roles/orgpolicy.policyAdmin' => 'Organization Policy Administrator',
            'roles/iam.securityAdmin' => 'Security Admin', 
            'roles/resourcemanager.organizationAdmin' => 'Organization Administrator',
            'roles/iam.serviceAccountAdmin' => 'Service Account Admin'
        ];
        
        $this->line("Para configurar Service Account con Domain-wide Delegation necesitas UNO de estos roles:");
        
        foreach ($requiredRoles as $role => $description) {
            $this->line("   ✅ {$description} ({$role})");
        }
        
        $this->warn("\n⚠️ ALTERNATIVAS si no puedes obtener estos roles:");
        $this->line("   1. Pedir a otro Super Admin que configure las políticas");
        $this->line("   2. Usar OAuth tradicional (como lo teníamos antes)");
        $this->line("   3. Solicitar acceso temporal para configuración inicial");
    }
    
    private function provideGcloudCommands(?string $projectId): void
    {
        $this->info("\n🖥️ COMANDOS GCLOUD PARA VERIFICAR PERMISOS");
        $this->info('-' . str_repeat('-', 50));
        
        if (!$projectId) {
            $this->warn("⚠️ Proporciona --project-id para comandos específicos");
            $this->line("Ejemplo: php artisan google:diagnose-permissions --project-id=omnic-email-system");
        }
        
        $this->line("📋 Comandos útiles:");
        
        // Listar organizaciones
        $this->line("\n1️⃣ Listar organizaciones disponibles:");
        $this->line("   gcloud organizations list");
        
        // Verificar permisos actuales
        $this->line("\n2️⃣ Verificar tus permisos actuales:");
        if ($projectId) {
            $this->line("   gcloud projects get-iam-policy {$projectId} \\");
            $this->line("     --flatten=\"bindings[].members\" \\");
            $this->line("     --format=\"table(bindings.role)\" \\");
            $this->line("     --filter=\"bindings.members:admin@orproverificaciones.cl\"");
        } else {
            $this->line("   gcloud projects get-iam-policy [PROJECT_ID] \\");
            $this->line("     --flatten=\"bindings[].members\" \\");
            $this->line("     --format=\"table(bindings.role)\" \\");
            $this->line("     --filter=\"bindings.members:TU_EMAIL\"");
        }
        
        // Verificar políticas organizacionales
        $this->line("\n3️⃣ Listar políticas organizacionales:");
        $this->line("   gcloud resource-manager org-policies list --organization=[ORG_ID]");
        
        // Auto-asignar rol si tienes permisos
        $this->line("\n4️⃣ Auto-asignarte el rol (si tienes permisos):");
        if ($projectId) {
            $this->line("   gcloud projects add-iam-policy-binding {$projectId} \\");
            $this->line("     --member=\"user:admin@orproverificaciones.cl\" \\");
            $this->line("     --role=\"roles/orgpolicy.policyAdmin\"");
        } else {
            $this->line("   gcloud projects add-iam-policy-binding [PROJECT_ID] \\");
            $this->line("     --member=\"user:TU_EMAIL\" \\");
            $this->line("     --role=\"roles/orgpolicy.policyAdmin\"");
        }
    }
    
    private function showManualSteps(): void
    {
        $this->info("\n📋 PASOS MANUALES EN GOOGLE ADMIN CONSOLE");
        $this->info('-' . str_repeat('-', 50));
        
        $this->line("Si no puedes usar gcloud CLI, sigue estos pasos:");
        
        $this->line("\n🔗 Método 1: Google Admin Console");
        $this->line("   1. Ir a: https://admin.google.com/ac/roles");
        $this->line("   2. Buscar tu usuario: admin@orproverificaciones.cl");
        $this->line("   3. Verificar que tienes rol 'Super Admin'");
        $this->line("   4. Si no, solicitar a otro administrador");
        
        $this->line("\n🔗 Método 2: Google Cloud Console");  
        $this->line("   1. Ir a: https://console.cloud.google.com/iam-admin/iam");
        $this->line("   2. Seleccionar tu proyecto");
        $this->line("   3. Buscar tu email en la lista");
        $this->line("   4. Click en ✏️ (Edit) junto a tu usuario");
        $this->line("   5. Añadir rol: Organization Policy Administrator");
        $this->line("   6. Guardar cambios");
        
        $this->line("\n🔗 Método 3: Verificación de organización");
        $this->line("   1. Ir a: https://console.cloud.google.com/cloud-resource-manager");
        $this->line("   2. Verificar que tu dominio aparece como organización");
        $this->line("   3. Si no aparece, necesitas configurar Cloud Identity primero");
        
        $this->warn("\n⚠️ IMPORTANTE:");
        $this->line("   Si no puedes realizar estos cambios, considera usar OAuth tradicional");
        $this->line("   que ya implementamos y funciona perfectamente para tu caso de uso.");
    }
}