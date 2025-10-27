<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;
use App\Models\SystemConfig;
use App\Models\User;
use App\Models\GmailGroup;
use App\Models\ImportedEmail;
use App\Models\ReferenceCode;

class SetupCompleteSystem extends Command
{
    protected $signature = 'setup:complete-system 
                            {--fresh : Limpiar todo antes de configurar}
                            {--with-test-data : Incluir datos de prueba}
                            {--quick : Setup rápido sin confirmaciones}';
    
    protected $description = 'Configuración completa del sistema OMNIC desde cero';

    public function handle()
    {
        $fresh = $this->option('fresh');
        $withTestData = $this->option('with-test-data');
        $quick = $this->option('quick');

        $this->info('🚀 CONFIGURACIÓN COMPLETA DEL SISTEMA OMNIC');
        $this->info('==========================================');
        $this->newLine();

        if ($fresh) {
            $this->handleFreshInstall($quick);
        }

        // 1. Verificar configuración básica
        $this->step1_CheckBasicConfig();

        // 2. Configurar usuarios y roles
        $this->step2_SetupUsers();

        // 3. Configurar grupos Gmail
        $this->step3_SetupGmailGroups();

        // 4. Configurar autenticación Gmail
        $this->step4_SetupGmailAuth();

        // 5. Crear datos de prueba si se solicita
        if ($withTestData) {
            $this->step5_CreateTestData();
        }

        // 6. Verificar que todo funciona
        $this->step6_VerifySystem();

        $this->newLine();
        $this->info('✅ CONFIGURACIÓN COMPLETA FINALIZADA');
        $this->showNextSteps();

        return 0;
    }

    private function handleFreshInstall($quick)
    {
        $this->warn('🧹 Instalación desde cero - Se eliminarán todos los datos');
        
        if (!$quick && !$this->confirm('¿Continuar con instalación fresca?')) {
            $this->error('❌ Instalación cancelada');
            exit(1);
        }

        $this->info('🗑️ Limpiando base de datos...');
        
        // Ejecutar migraciones frescas
        $this->call('migrate:fresh');
        
        $this->info('✅ Base de datos limpia');
        $this->newLine();
    }

    private function step1_CheckBasicConfig()
    {
        $this->info('📋 PASO 1: Verificando configuración básica');
        $this->line('─────────────────────────────────────────');

        // Verificar .env
        $requiredEnvVars = [
            'APP_URL' => config('app.url'),
            'DB_DATABASE' => config('database.connections.pgsql.database'),
            'GOOGLE_CLIENT_ID' => config('services.google.client_id'),
        ];

        foreach ($requiredEnvVars as $key => $value) {
            $status = $value ? '✅' : '❌';
            $this->line("{$status} {$key}: " . ($value ?: 'NO CONFIGURADO'));
        }

        // Verificar conexión a base de datos
        try {
            \DB::connection()->getPdo();
            $this->line('✅ Conexión a base de datos: OK');
        } catch (\Exception $e) {
            $this->error('❌ Error de base de datos: ' . $e->getMessage());
            exit(1);
        }

        $this->info('✅ Configuración básica verificada');
        $this->newLine();
    }

    private function step2_SetupUsers()
    {
        $this->info('👥 PASO 2: Configurando usuarios y roles');
        $this->line('──────────────────────────────────────');

        // Verificar si ya existen usuarios
        $userCount = User::count();
        
        if ($userCount > 0) {
            $this->line("📊 Ya existen {$userCount} usuarios en el sistema");
        } else {
            $this->info('🔄 Creando usuarios básicos...');
            $this->call('db:seed', ['--class' => 'EmailSystemSeeder']);
            
            $newUserCount = User::count();
            $this->line("✅ Creados {$newUserCount} usuarios");
        }

        // Mostrar estadísticas de usuarios
        $roles = User::selectRaw('role, count(*) as count')
                    ->groupBy('role')
                    ->pluck('count', 'role')
                    ->toArray();

        $this->table(
            ['Rol', 'Cantidad'],
            collect($roles)->map(fn($count, $role) => [$role, $count])->toArray()
        );

        $this->info('✅ Usuarios configurados');
        $this->newLine();
    }

    private function step3_SetupGmailGroups()
    {
        $this->info('📧 PASO 3: Configurando grupos Gmail');
        $this->line('───────────────────────────────────');

        $groupCount = GmailGroup::count();
        
        if ($groupCount > 0) {
            $this->line("📊 Ya existen {$groupCount} grupos Gmail");
        } else {
            $this->warn('⚠️ No hay grupos Gmail configurados');
            $this->line('💡 Ejecuta: php artisan gmail:setup-groups-from-csv archivo.csv');
        }

        $activeGroups = GmailGroup::active()->count();
        $this->line("✅ Grupos activos: {$activeGroups}");

        $this->info('✅ Grupos Gmail verificados');
        $this->newLine();
    }

    private function step4_SetupGmailAuth()
    {
        $this->info('🔐 PASO 4: Configurando autenticación Gmail');
        $this->line('────────────────────────────────────────');

        $authSetup = SystemConfig::getValue('gmail_auth_setup');
        
        if ($authSetup === 'test_mode') {
            $this->line('🧪 Modo de prueba activado');
        } elseif ($authSetup === 'production') {
            $this->line('🔗 Modo producción configurado');
        } else {
            $this->warn('⚠️ Autenticación no configurada');
            $this->info('🔄 Configurando modo de prueba...');
            
            $this->call('gmail:setup-test-auth');
            $this->line('✅ Modo de prueba configurado');
        }

        $this->info('✅ Autenticación Gmail verificada');
        $this->newLine();
    }

    private function step5_CreateTestData()
    {
        $this->info('🧪 PASO 5: Creando datos de prueba');
        $this->line('─────────────────────────────────');

        // Limpiar datos de prueba previos
        ImportedEmail::where('gmail_message_id', 'like', 'test_%')->delete();
        ReferenceCode::where('producto', 'like', 'AFP-%')->delete();

        // Crear datos de prueba completos
        $this->info('🔄 Ejecutando prueba completa...');
        $this->call('test:complete-email-flow', ['--mock' => true]);

        $testEmails = ImportedEmail::where('gmail_message_id', 'like', 'test_%')->count();
        $testCodes = ReferenceCode::where('producto', 'like', 'AFP-%')->count();

        $this->line("📧 Correos de prueba creados: {$testEmails}");
        $this->line("🏷️ Códigos de referencia: {$testCodes}");

        $this->info('✅ Datos de prueba creados');
        $this->newLine();
    }

    private function step6_VerifySystem()
    {
        $this->info('🔍 PASO 6: Verificando sistema completo');
        $this->line('────────────────────────────────────');

        $checks = [
            'Usuarios' => User::count() > 0,
            'Grupos Gmail' => GmailGroup::active()->count() > 0,
            'Autenticación' => SystemConfig::getValue('gmail_auth_setup') !== null,
            'Correos importados' => ImportedEmail::count() > 0,
        ];

        foreach ($checks as $item => $status) {
            $icon = $status ? '✅' : '❌';
            $this->line("{$icon} {$item}");
        }

        $allGood = !in_array(false, array_values($checks));

        if ($allGood) {
            $this->info('🎉 Sistema completamente funcional');
        } else {
            $this->warn('⚠️ Hay elementos que requieren atención');
        }

        $this->newLine();
    }

    private function showNextSteps()
    {
        $this->info('🎯 PRÓXIMOS PASOS');
        $this->line('═══════════════');
        $this->newLine();

        $baseUrl = config('app.url');

        $this->line('1️⃣ PROBAR INTERFAZ WEB:');
        $this->line("   📋 Lista de casos: {$baseUrl}/inbox");
        $this->line("   📧 Ver caso específico: {$baseUrl}/case/[ID]");
        $this->newLine();

        $this->line('2️⃣ COMANDOS ÚTILES:');
        $this->line('   📥 Importar correos: php artisan emails:import --mock');
        $this->line('   🌊 Simular tiempo real: php artisan test:simulate-live-emails');
        $this->line('   🧪 Prueba completa: php artisan test:complete-email-flow --mock');
        $this->line('   🧹 Limpiar datos: php artisan test:clean-data');
        $this->newLine();

        $this->line('3️⃣ CONFIGURACIÓN PRODUCCIÓN:');
        $this->line('   🔐 OAuth Gmail: php artisan gmail:setup-oauth');
        $this->line('   📧 Grupos desde CSV: php artisan gmail:setup-groups-from-csv archivo.csv');
        $this->line('   ⏰ Programar importación: Configurar cron cada minuto');
        $this->newLine();

        $this->info('📖 Consulta REQUERIMIENTS.md para más detalles');
        $this->newLine();
    }
}