<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportedEmail;
use App\Models\ReferenceCode;

class CleanTestData extends Command
{
    protected $signature = 'test:clean-data {--all : Limpiar todos los datos de prueba} {--confirm : Confirmar sin preguntar}';
    protected $description = 'Limpia datos de prueba creados por los comandos de simulación';

    public function handle()
    {
        $cleanAll = $this->option('all');
        $confirm = $this->option('confirm');

        $this->info('🧹 LIMPIEZA DE DATOS DE PRUEBA');
        $this->info('============================');
        $this->newLine();

        if ($cleanAll) {
            $this->cleanAllTestData($confirm);
        } else {
            $this->cleanSimulationData($confirm);
        }

        return 0;
    }

    private function cleanSimulationData($confirm)
    {
        // Contar datos a eliminar
        $testEmails = ImportedEmail::where('gmail_message_id', 'like', 'test_%')
                                 ->orWhere('gmail_message_id', 'like', 'live_sim_%')
                                 ->count();
        
        $testCodes = ReferenceCode::where('producto', 'like', 'AFP-%')->count();

        $this->line("📧 Correos de prueba: {$testEmails}");
        $this->line("🏷️ Códigos de referencia: {$testCodes}");
        $this->newLine();

        if ($testEmails === 0 && $testCodes === 0) {
            $this->info('✅ No hay datos de prueba para limpiar');
            return;
        }

        if (!$confirm && !$this->confirm('¿Deseas eliminar estos datos de prueba?')) {
            $this->info('❌ Operación cancelada');
            return;
        }

        $this->info('🗑️ Eliminando datos de prueba...');

        // Eliminar correos de prueba
        $deletedEmails = ImportedEmail::where('gmail_message_id', 'like', 'test_%')
                                    ->orWhere('gmail_message_id', 'like', 'live_sim_%')
                                    ->delete();

        // Eliminar códigos de referencia de prueba
        $deletedCodes = ReferenceCode::where('producto', 'like', 'AFP-%')->delete();

        $this->info("✅ Eliminados {$deletedEmails} correos y {$deletedCodes} códigos de referencia");
    }

    private function cleanAllTestData($confirm)
    {
        $this->warn('⚠️ LIMPIEZA COMPLETA - Esto eliminará TODOS los correos y códigos');
        
        // Contar todos los datos
        $allEmails = ImportedEmail::count();
        $allCodes = ReferenceCode::count();

        $this->line("📧 Total correos: {$allEmails}");
        $this->line("🏷️ Total códigos: {$allCodes}");
        $this->newLine();

        if ($allEmails === 0 && $allCodes === 0) {
            $this->info('✅ No hay datos para limpiar');
            return;
        }

        if (!$confirm && !$this->confirm('⚠️ ¿ESTÁS SEGURO? Esto eliminará TODOS los datos de correos y códigos', false)) {
            $this->info('❌ Operación cancelada');
            return;
        }

        if (!$confirm && !$this->confirm('🚨 ÚLTIMA CONFIRMACIÓN: ¿Eliminar TODO?', false)) {
            $this->info('❌ Operación cancelada por seguridad');
            return;
        }

        $this->error('🗑️ Eliminando TODOS los datos...');

        // Eliminar todo
        ImportedEmail::truncate();
        ReferenceCode::truncate();

        $this->info("✅ Todos los datos han sido eliminados");
        $this->warn("💡 Ejecuta 'php artisan db:seed --class=EmailSystemSeeder' para recrear datos básicos");
    }
}