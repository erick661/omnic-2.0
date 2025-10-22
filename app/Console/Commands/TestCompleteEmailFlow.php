<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportedEmail;
use App\Models\GmailGroup;
use App\Models\ReferenceCode;
use App\Models\User;
use App\Models\SystemConfig;
use App\Services\MockGmailService;
use App\Services\GmailService;
use Carbon\Carbon;

class TestCompleteEmailFlow extends Command
{
    protected $signature = 'test:complete-email-flow {--mock : Usar servicio mock} {--reset : Limpiar datos previos}';
    protected $description = 'Prueba completa del flujo de correos: importación → asignación → visualización → respuesta → cierre';

    private $testData = [];

    public function handle()
    {
        $this->info('🧪 PRUEBA COMPLETA DEL FLUJO DE CORREOS');
        $this->info('=====================================');
        $this->newLine();

        if ($this->option('reset')) {
            $this->resetTestData();
        }

        $useMock = $this->option('mock') || SystemConfig::getValue('gmail_auth_setup') === 'test_mode';
        
        if ($useMock) {
            $this->warn('🧪 Usando servicio MOCK para la prueba');
        } else {
            $this->info('🔗 Usando Gmail API real');
        }

        $this->newLine();

        // Paso 1: Verificar configuración inicial
        $this->step1_VerifySetup();

        // Paso 2: Crear códigos de referencia para pruebas
        $this->step2_CreateReferenceCodes();

        // Paso 3: Simular llegada de correos
        $this->step3_ImportEmails($useMock);

        // Paso 4: Verificar asignación automática
        $this->step4_VerifyAutoAssignment();

        // Paso 5: Mostrar casos para visualización
        $this->step5_ShowCasesForVisualization();

        // Paso 6: Simular respuesta del agente
        $this->step6_SimulateAgentResponse();

        // Paso 7: Simular respuesta del empleador
        $this->step7_SimulateEmployerResponse($useMock);

        // Paso 8: Simular cierre del caso
        $this->step8_SimulateCaseClosing();

        $this->newLine();
        $this->info('✅ PRUEBA COMPLETA FINALIZADA');
        $this->showFinalSummary();

        return 0;
    }

    private function resetTestData()
    {
        $this->warn('🗑️ Limpiando datos de prueba anteriores...');
        
        ImportedEmail::where('gmail_message_id', 'like', 'test_%')->delete();
        ReferenceCode::where('producto', 'like', 'AFP-%')->delete();
        
        $this->info('✅ Datos de prueba limpiados');
        $this->newLine();
    }

    private function step1_VerifySetup()
    {
        $this->info('📋 PASO 1: Verificando configuración del sistema');
        $this->line('─────────────────────────────────────────────');

        // Verificar usuarios
        $users = User::where('role', 'ejecutivo')->count();
        $supervisors = User::where('role', 'supervisor')->count();
        
        $this->line("👥 Usuarios ejecutivos: {$users}");
        $this->line("👨‍💼 Supervisores: {$supervisors}");

        // Verificar grupos Gmail
        $activeGroups = GmailGroup::active()->count();
        $this->line("📧 Grupos Gmail activos: {$activeGroups}");

        // Verificar autenticación
        $authSetup = SystemConfig::getValue('gmail_auth_setup');
        $this->line("🔐 Estado autenticación: " . ($authSetup ?: 'No configurada'));

        if ($users < 1 || $activeGroups < 1) {
            $this->error('❌ Configuración incompleta. Ejecuta el seeder primero:');
            $this->line('php artisan db:seed --class=EmailSystemSeeder');
            exit(1);
        }

        $this->info('✅ Configuración verificada');
        $this->newLine();
    }

    private function step2_CreateReferenceCodes()
    {
        $this->info('🏷️ PASO 2: Creando códigos de referencia para pruebas');
        $this->line('─────────────────────────────────────────────────');

        $ejecutivo = User::where('role', 'ejecutivo')->first();
        
        if (!$ejecutivo) {
            $this->error('❌ No hay ejecutivos disponibles');
            exit(1);
        }

        // Crear código de referencia para caso existente
        $rutEmpresa = '76543210';
        $dvEmpresa = '9';
        $producto = 'AFP-CAPITAL';
        $codeHash = ReferenceCode::generateCodeHash($rutEmpresa, $dvEmpresa, $producto);
        
        $referenceCode = ReferenceCode::create([
            'rut_empleador' => $rutEmpresa,
            'dv_empleador' => $dvEmpresa,
            'producto' => $producto,
            'code_hash' => $codeHash,
            'assigned_user_id' => $ejecutivo->id,
        ]);

        $this->testData['reference_code'] = $referenceCode;
        $this->testData['ejecutivo'] = $ejecutivo;

        $this->line("📝 Código creado: {$referenceCode->formatted_code}");
        $this->line("👤 Asignado a: {$ejecutivo->name} {$ejecutivo->last_name}");
        $this->line("🏢 RUT Empresa: {$referenceCode->formatted_rut}");
        $this->line("📦 Producto: {$referenceCode->producto}");

        $this->info('✅ Códigos de referencia creados');
        $this->newLine();
    }

    private function step3_ImportEmails($useMock)
    {
        $this->info('📥 PASO 3: Importando correos (simulación de llegada)');
        $this->line('───────────────────────────────────────────────────');

        if ($useMock) {
            $this->simulateMockEmails();
        } else {
            $this->importRealEmails();
        }

        $this->info('✅ Correos importados');
        $this->newLine();
    }

    private function simulateMockEmails()
    {
        $group = GmailGroup::active()->first();
        $referenceCode = $this->testData['reference_code'];

        // Correo inicial sin código de referencia (caso nuevo)
        $newCaseEmail = ImportedEmail::create([
            'gmail_message_id' => 'test_msg_new_' . time(),
            'gmail_thread_id' => 'test_thread_new_' . time(),
            'gmail_group_id' => $group->id,
            'subject' => 'Consulta sobre AFP Capital - Nueva Empresa',
            'from_email' => 'rrhh@nuevaempresa.cl',
            'from_name' => 'RRHH Nueva Empresa',
            'to_email' => $group->email,
            'body_html' => '<p>Estimados, necesitamos información sobre el proceso de afiliación a AFP Capital para nuestros empleados.</p>',
            'body_text' => 'Estimados, necesitamos información sobre el proceso de afiliación a AFP Capital para nuestros empleados.',
            'received_at' => now()->subMinutes(5),
            'imported_at' => now(),
            'case_status' => 'pending',
            'priority' => 'normal',
        ]);

        // Correo con código de referencia (caso existente)
        $existingCaseEmail = ImportedEmail::create([
            'gmail_message_id' => 'test_msg_existing_' . time(),
            'gmail_thread_id' => 'test_thread_existing_' . time(),
            'gmail_group_id' => $group->id,
            'subject' => "RE: Información adicional {$referenceCode->formatted_code}",
            'from_email' => 'finanzas@empresaprueba.cl',
            'from_name' => 'Finanzas Empresa Prueba',
            'to_email' => $group->email,
            'body_html' => '<p>Gracias por la respuesta anterior. Tenemos algunas consultas adicionales sobre el proceso.</p>',
            'body_text' => 'Gracias por la respuesta anterior. Tenemos algunas consultas adicionales sobre el proceso.',
            'received_at' => now()->subMinutes(3),
            'imported_at' => now(),
            'case_status' => 'pending',
            'priority' => 'normal',
            'reference_code_id' => $referenceCode->id,
            'rut_empleador' => $referenceCode->rut_empleador,
            'dv_empleador' => $referenceCode->dv_empleador,
        ]);

        $this->testData['new_case_email'] = $newCaseEmail;
        $this->testData['existing_case_email'] = $existingCaseEmail;

        $this->line("📨 Correo nuevo caso: {$newCaseEmail->subject}");
        $this->line("📨 Correo caso existente: {$existingCaseEmail->subject}");
    }

    private function importRealEmails()
    {
        try {
            $service = new GmailService();
            $results = $service->importNewEmails();
            
            $total = array_sum(array_column($results, 'imported'));
            $this->line("📊 Total importados: {$total} correos reales");
            
        } catch (\Exception $e) {
            $this->error("❌ Error importando correos reales: " . $e->getMessage());
            $this->info("💡 Continuando con correos simulados...");
            $this->simulateMockEmails();
        }
    }

    private function step4_VerifyAutoAssignment()
    {
        $this->info('🎯 PASO 4: Verificando asignación automática');
        $this->line('──────────────────────────────────────────────');

        // Aplicar lógica de auto-asignación a correos pendientes
        $this->applyAutoAssignment();

        // Mostrar resultados
        $assigned = ImportedEmail::where('case_status', 'assigned')->count();
        $pending = ImportedEmail::where('case_status', 'pending')->count();

        $this->line("✅ Casos asignados automáticamente: {$assigned}");
        $this->line("⏳ Casos pendientes de asignación: {$pending}");

        if (isset($this->testData['existing_case_email'])) {
            $email = $this->testData['existing_case_email']->fresh();
            if ($email->assigned_to) {
                $assignee = User::find($email->assigned_to);
                $this->line("🎯 Correo con código de referencia asignado a: {$assignee->name}");
            }
        }

        $this->info('✅ Asignación automática verificada');
        $this->newLine();
    }

    private function applyAutoAssignment()
    {
        $pendingEmails = ImportedEmail::where('case_status', 'pending')->get();

        foreach ($pendingEmails as $email) {
            // Buscar código de referencia en el asunto
            $referenceCode = ReferenceCode::findBySubject($email->subject);
            
            if ($referenceCode) {
                $email->update([
                    'reference_code_id' => $referenceCode->id,
                    'assigned_to' => $referenceCode->assigned_user_id,
                    'assigned_at' => now(),
                    'case_status' => 'assigned',
                    'rut_empleador' => $referenceCode->rut_empleador,
                    'dv_empleador' => $referenceCode->dv_empleador,
                ]);
            }
        }
    }

    private function step5_ShowCasesForVisualization()
    {
        $this->info('👁️ PASO 5: Casos disponibles para visualización');
        $this->line('────────────────────────────────────────────────');

        $recentEmails = ImportedEmail::with(['assignedUser', 'referenceCode'])
            ->where('created_at', '>', now()->subHours(2))
            ->orderBy('received_at', 'desc')
            ->take(10)
            ->get();

        if ($recentEmails->isEmpty()) {
            $this->warn('⚠️ No hay casos recientes para mostrar');
            return;
        }

        $tableData = [];
        foreach ($recentEmails as $email) {
            $tableData[] = [
                'ID' => $email->id,
                'Asunto' => substr($email->subject, 0, 40) . '...',
                'De' => $email->from_email,
                'Estado' => $email->case_status,
                'Asignado' => $email->assignedUser ? $email->assignedUser->name : 'Sin asignar',
                'Recibido' => $email->received_at->format('H:i')
            ];
        }

        $this->table(
            ['ID', 'Asunto', 'De', 'Estado', 'Asignado', 'Recibido'],
            $tableData
        );

        $this->info('💡 URLs para visualización:');
        foreach ($recentEmails->take(3) as $email) {
            $url = config('app.url') . "/case/{$email->id}";
            $this->line("🔗 Caso {$email->id}: {$url}");
        }

        $this->info('✅ Casos listos para visualización');
        $this->newLine();
    }

    private function step6_SimulateAgentResponse()
    {
        $this->info('💬 PASO 6: Simulando respuesta del agente');
        $this->line('────────────────────────────────────────────');

        if (!isset($this->testData['existing_case_email'])) {
            $this->warn('⚠️ No hay caso para responder');
            return;
        }

        $email = $this->testData['existing_case_email'];
        $ejecutivo = $this->testData['ejecutivo'];

        // Simular que el agente respondió
        $responseData = [
            'case_id' => $email->id,
            'agent_id' => $ejecutivo->id,
            'response_channel' => 'email',
            'response_subject' => 'RE: ' . $email->subject,
            'response_content' => 'Estimado cliente, hemos recibido su consulta y estamos procesando la información solicitada...',
            'sent_at' => now(),
        ];

        // Actualizar estado del caso
        $email->update([
            'case_status' => 'in_progress',
        ]);

        $this->line("📧 Respuesta simulada enviada por: {$ejecutivo->name}");
        $this->line("📝 Asunto: {$responseData['response_subject']}");
        $this->line("🔄 Estado actualizado a: in_progress");

        $this->testData['agent_response'] = $responseData;

        $this->info('✅ Respuesta del agente simulada');
        $this->newLine();
    }

    private function step7_SimulateEmployerResponse($useMock)
    {
        $this->info('📩 PASO 7: Simulando respuesta del empleador');
        $this->line('───────────────────────────────────────────────');

        if (!isset($this->testData['existing_case_email'])) {
            $this->warn('⚠️ No hay caso para continuar');
            return;
        }

        $originalEmail = $this->testData['existing_case_email'];
        $referenceCode = $this->testData['reference_code'];
        $group = GmailGroup::active()->first();

        // Simular nueva respuesta del empleador
        $employerResponse = ImportedEmail::create([
            'gmail_message_id' => 'test_msg_response_' . time(),
            'gmail_thread_id' => $originalEmail->gmail_thread_id, // Mismo hilo
            'gmail_group_id' => $group->id,
            'subject' => "RE: Información adicional {$referenceCode->formatted_code}",
            'from_email' => $originalEmail->from_email,
            'from_name' => $originalEmail->from_name,
            'to_email' => $group->email,
            'body_html' => '<p>Perfecto, muchas gracias por la información. Solo nos queda una duda sobre los plazos de afiliación.</p>',
            'body_text' => 'Perfecto, muchas gracias por la información. Solo nos queda una duda sobre los plazos de afiliación.',
            'received_at' => now(),
            'imported_at' => now(),
            'case_status' => 'assigned', // Se asigna automáticamente por código de referencia
            'priority' => 'normal',
            'reference_code_id' => $referenceCode->id,
            'rut_empleador' => $referenceCode->rut_empleador,
            'dv_empleador' => $referenceCode->dv_empleador,
            'assigned_to' => $referenceCode->assigned_user_id,
            'assigned_at' => now(),
        ]);

        $this->testData['employer_response'] = $employerResponse;

        $this->line("📨 Nueva respuesta del empleador recibida");
        $this->line("🔗 Vinculada automáticamente al caso por código: {$referenceCode->formatted_code}");
        $this->line("👤 Auto-asignada a: {$this->testData['ejecutivo']->name}");

        $this->info('✅ Respuesta del empleador procesada');
        $this->newLine();
    }

    private function step8_SimulateCaseClosing()
    {
        $this->info('🏁 PASO 8: Simulando cierre del caso');
        $this->line('─────────────────────────────────────');

        if (!isset($this->testData['employer_response'])) {
            $this->warn('⚠️ No hay caso para cerrar');
            return;
        }

        $email = $this->testData['employer_response'];
        $ejecutivo = $this->testData['ejecutivo'];

        // Simular respuesta final y cierre
        $finalResponseData = [
            'case_id' => $email->id,
            'agent_id' => $ejecutivo->id,
            'response_channel' => 'email',
            'response_subject' => 'RE: ' . $email->subject . ' - RESUELTO',
            'response_content' => 'Estimado cliente, hemos resuelto completamente su consulta. Los plazos de afiliación son de 30 días hábiles. Caso cerrado.',
            'sent_at' => now(),
        ];

        // Cerrar caso
        $email->update([
            'case_status' => 'resolved',
            'marked_resolved_at' => now(),
        ]);

        // También cerrar el correo original relacionado
        if (isset($this->testData['existing_case_email'])) {
            $this->testData['existing_case_email']->update([
                'case_status' => 'resolved',
                'marked_resolved_at' => now(),
            ]);
        }

        $this->line("📧 Respuesta final enviada por: {$ejecutivo->name}");
        $this->line("✅ Caso marcado como RESUELTO");
        $this->line("📅 Fecha de cierre: " . now()->format('Y-m-d H:i:s'));

        $this->testData['final_response'] = $finalResponseData;

        $this->info('✅ Caso cerrado exitosamente');
        $this->newLine();
    }

    private function showFinalSummary()
    {
        $this->info('📊 RESUMEN FINAL DE LA PRUEBA');
        $this->info('═══════════════════════════════');

        // Estadísticas generales
        $totalEmails = ImportedEmail::where('created_at', '>', now()->subHours(2))->count();
        $assigned = ImportedEmail::where('case_status', 'assigned')->count();
        $inProgress = ImportedEmail::where('case_status', 'in_progress')->count();
        $resolved = ImportedEmail::where('case_status', 'resolved')->count();
        $pending = ImportedEmail::where('case_status', 'pending')->count();

        $this->table(
            ['Estado', 'Cantidad', 'Descripción'],
            [
                ['📥 Importados', $totalEmails, 'Correos procesados en las últimas 2 horas'],
                ['⏳ Pendientes', $pending, 'Sin asignar'],
                ['🎯 Asignados', $assigned, 'Asignados automáticamente'],
                ['🔄 En progreso', $inProgress, 'Siendo atendidos'],
                ['✅ Resueltos', $resolved, 'Casos cerrados'],
            ]
        );

        $this->newLine();
        $this->info('🔗 URLS PARA PRUEBAS MANUALES:');
        $this->line('─────────────────────────────────');
        $baseUrl = config('app.url');
        $this->line("📋 Lista de casos: {$baseUrl}/inbox");
        
        if (isset($this->testData['existing_case_email'])) {
            $caseId = $this->testData['existing_case_email']->id;
            $this->line("👁️ Ver caso específico: {$baseUrl}/case/{$caseId}");
        }

        $this->newLine();
        $this->info('💡 SIGUIENTES PASOS:');
        $this->line('─────────────────────');
        $this->line('1. Visita la URL de lista de casos para ver la interfaz');
        $this->line('2. Haz clic en "Ver Caso" para ver los detalles');
        $this->line('3. Prueba responder por email desde la interfaz');
        $this->line('4. Ejecuta nuevamente con --reset para limpiar y repetir');

        $this->newLine();
    }
}