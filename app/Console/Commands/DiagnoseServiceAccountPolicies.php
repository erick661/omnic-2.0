<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\CloudResourceManager;
use Google\Service\Iam;
use Illuminate\Support\Facades\Log;

class DiagnoseServiceAccountPolicies extends Command
{
    protected $signature = 'service-account:diagnose-policies {--project-id= : Google Cloud Project ID}';
    protected $description = 'Diagnosticar políticas organizacionales que pueden bloquear Service Accounts';

    public function handle()
    {
        $this->info('🔍 Diagnosticando Políticas Organizacionales para Service Accounts...');
        
        $projectId = $this->option('project-id') ?? $this->ask('Ingresa tu Google Cloud Project ID');
        
        if (!$projectId) {
            $this->error('❌ Project ID es requerido');
            return 1;
        }

        // 1. Verificar credenciales
        $this->checkCredentials();
        
        // 2. Verificar permisos del usuario
        $this->checkUserPermissions();
        
        // 3. Listar políticas problemáticas conocidas
        $this->listProblematicPolicies($projectId);
        
        // 4. Dar recomendaciones
        $this->giveRecommendations();
        
        return 0;
    }
    
    private function checkCredentials(): void
    {
        $this->info("\n📂 Verificando credenciales...");
        
        $serviceAccountPath = config('services.google.service_account_path', 
            storage_path('app/google-credentials/google-service-account.json'));
        
        if (file_exists($serviceAccountPath)) {
            $this->line("✅ Archivo de Service Account encontrado: {$serviceAccountPath}");
            
            $credentials = json_decode(file_get_contents($serviceAccountPath), true);
            
            if (isset($credentials['client_email'])) {
                $this->line("✅ Service Account Email: {$credentials['client_email']}");
            }
            
            if (isset($credentials['project_id'])) {
                $this->line("✅ Project ID en credenciales: {$credentials['project_id']}");
            }
            
        } else {
            $this->error("❌ Archivo de Service Account no encontrado");
            $this->line("   Ruta esperada: {$serviceAccountPath}");
        }
    }
    
    private function checkUserPermissions(): void
    {
        $this->info("\n👤 Verificando permisos de usuario...");
        
        $this->line("📋 Para modificar políticas organizacionales necesitas:");
        $this->line("   ✅ Super Admin en Google Workspace");
        $this->line("   O alternativamente:");
        $this->line("   ✅ Security Admin");  
        $this->line("   ✅ Organization Policy Administrator (roles/orgpolicy.policyAdmin)");
        
        $currentUser = $this->ask('¿Cuál es tu email de administrador?', 'admin@orproverificaciones.cl');
        
        $this->line("\n🔗 Para verificar tus roles:");
        $this->line("1. Ir a: https://admin.google.com");
        $this->line("2. Directory > Admin roles");
        $this->line("3. Buscar: {$currentUser}");
        $this->line("4. Verificar roles asignados");
        
        if (!$this->confirm('¿Tienes rol Super Admin o Security Admin?')) {
            $this->warn("⚠️ Necesitarás solicitar permisos adicionales o ayuda de otro administrador");
        }
    }
    
    private function listProblematicPolicies(string $projectId): void
    {
        $this->info("\n🚫 Políticas que pueden bloquear Service Accounts:");
        
        $policies = [
            [
                'name' => 'Domain Restricted Sharing',
                'constraint' => 'constraints/iam.allowedPolicyMemberDomains',
                'description' => 'Restringe dominios que pueden acceder al proyecto',
                'solution' => 'Añadir tu dominio como excepción'
            ],
            [
                'name' => 'Service Account Creation',  
                'constraint' => 'constraints/iam.disableServiceAccountCreation',
                'description' => 'Bloquea creación de nuevos Service Accounts',
                'solution' => 'Deshabilitar para el proyecto o crear excepción'
            ],
            [
                'name' => 'Service Account Key Creation',
                'constraint' => 'constraints/iam.disableServiceAccountKeyCreation', 
                'description' => 'Bloquea creación de claves JSON para Service Accounts',
                'solution' => 'Deshabilitar temporalmente para generar claves'
            ],
            [
                'name' => 'External IP Access',
                'constraint' => 'constraints/compute.vmExternalIpAccess',
                'description' => 'Puede afectar conectividad desde servidores externos', 
                'solution' => 'Verificar que no bloquea APIs de Google'
            ]
        ];
        
        foreach ($policies as $policy) {
            $this->line("\n📋 {$policy['name']}");
            $this->line("   🔒 Constraint: {$policy['constraint']}");
            $this->line("   📝 {$policy['description']}");
            $this->line("   💡 Solución: {$policy['solution']}");
        }
        
        $this->info("\n🔗 Para verificar estas políticas:");
        $this->line("1. Ir a: https://console.cloud.google.com");
        $this->line("2. Seleccionar proyecto: {$projectId}");
        $this->line("3. IAM & Admin > Organization Policies");
        $this->line("4. Buscar cada constraint mencionada arriba");
    }
    
    private function giveRecommendations(): void
    {
        $this->info("\n💡 RECOMENDACIONES:");
        
        $this->line("\n🎯 Opción 1: Configurar excepciones específicas (MÁS SEGURO)");
        $this->line("   - Mantener políticas activas");
        $this->line("   - Crear excepciones solo para el proyecto Omnic");
        $this->line("   - Limitar scope a recursos específicos");
        
        $this->line("\n🎯 Opción 2: Deshabilitar temporalmente (MENOS SEGURO)");  
        $this->line("   - Solo durante configuración inicial");
        $this->line("   - Re-habilitar después de crear Service Account");
        $this->line("   - Documentar cambios para auditoria");
        
        $this->line("\n🎯 Opción 3: Alternativas sin Service Account");
        $this->line("   - Continuar con OAuth + BD (ya implementado)"); 
        $this->line("   - Usar Application Default Credentials");
        $this->line("   - Implementar rotación automática de tokens");
        
        $this->info("\n📞 Si no tienes permisos suficientes:");
        $this->line("   1. Contactar al Super Admin de tu organización");
        $this->line("   2. Solicitar configuración de excepciones específicas");
        $this->line("   3. O solicitar rol 'Organization Policy Administrator'");
        
        if ($this->confirm('¿Quieres ver comandos específicos para configurar políticas?')) {
            $this->showPolicyCommands();
        }
    }
    
    private function showPolicyCommands(): void
    {
        $this->info("\n🔧 COMANDOS PARA CONFIGURAR POLÍTICAS:");
        
        $this->line("\n# Via gcloud CLI (requiere permisos):");
        $this->line("gcloud resource-manager org-policies describe \\");
        $this->line("    constraints/iam.disableServiceAccountCreation \\");
        $this->line("    --project=[PROJECT-ID]");
        
        $this->line("\n# Para deshabilitar restricción temporalmente:");
        $this->line("gcloud resource-manager org-policies delete \\");
        $this->line("    constraints/iam.disableServiceAccountCreation \\"); 
        $this->line("    --project=[PROJECT-ID]");
        
        $this->line("\n# Para crear política customizada:");
        $this->line("echo 'constraint: constraints/iam.allowedPolicyMemberDomains");
        $this->line("etag: [ETAG-VALUE]");
        $this->line("listPolicy:");
        $this->line("  allowedValues:");
        $this->line("  - \"orproverificaciones.cl\"' > policy.yaml");
        $this->line("");
        $this->line("gcloud resource-manager org-policies set-policy policy.yaml --project=[PROJECT-ID]");
        
        $this->warn("\n⚠️ IMPORTANTE: Estos comandos requieren permisos de Organization Policy Administrator");
    }
}