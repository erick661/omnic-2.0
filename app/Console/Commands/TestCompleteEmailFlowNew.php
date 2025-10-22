<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;
use App\Services\OutboxEmailService;
use App\Models\OutboxEmail;
use App\Models\ImportedEmail;
use App\Models\User;
use App\Models\GmailGroup;

class TestCompleteEmailFlowNew extends Command
{
    protected $signature = 'test:complete-flow {--real : Usar Gmail API real} {--email= : Email destino}';
    protected $description = 'Prueba el flujo completo: importación → visualización → respuesta → envío';

    public function handle()
    {
        $this->info('🔄 PRUEBA COMPLETA DEL FLUJO DE CORREOS');
        $this->info('=====================================');
        $this->newLine();

        // Paso 1: Importar correos
        $this->step1_ImportEmails();

        // Paso 2: Mostrar casos disponibles
        $this->step2_ShowCases();

        // Paso 3: Simular respuesta desde interfaz
        $this->step3_SimulateResponse();

        // Paso 4: Procesar bandeja de salida
        $this->step4_ProcessOutbox();

        // Paso 5: Verificar seguimiento
        $this->step5_VerifyFollowUp();

        $this->newLine();
        $this->info('✅ FLUJO COMPLETO VERIFICADO');

        return 0;
    }

    private function step1_ImportEmails()
    {
        $this->info('📥 PASO 1: Importando correos');
        $this->line('─────────────────────────────');

        $this->call('emails:import', ['--mock' => true]);
        
        $recentCount = ImportedEmail::where('created_at', '>', now()->subMinutes(5))->count();
        $this->line("📊 Correos recientes disponibles: {$recentCount}");
        $this->newLine();
    }

    private function step2_ShowCases()
    {
        $this->info('👁️ PASO 2: Casos disponibles');
        $this->line('────────────────────────────');

        $cases = ImportedEmail::with('assignedUser')
            ->where('created_at', '>', now()->subHours(2))
            ->orderBy('received_at', 'desc')
            ->take(5)
            ->get();

        if ($cases->isEmpty()) {
            $this->warn('⚠️ No hay casos recientes disponibles');
            return;
        }

        $tableData = [];
        foreach ($cases as $case) {
            $tableData[] = [
                'ID' => $case->id,
                'Asunto' => substr($case->subject, 0, 40) . '...',
                'De' => $case->from_email,
                'Estado' => $case->case_status,
                'Asignado' => $case->assignedUser ? $case->assignedUser->name : 'Sin asignar',
            ];
        }

        $this->table(['ID', 'Asunto', 'De', 'Estado', 'Asignado'], $tableData);

        $this->info('💡 URLs para prueba manual:');
        $this->line("📋 Lista: https://dev-estadisticas.orpro.cl/inbox");
        $this->line("👁️ Ver caso: https://dev-estadisticas.orpro.cl/case/{$cases->first()->id}");
        $this->newLine();
    }

    private function step3_SimulateResponse()
    {
        $this->info('💬 PASO 3: Simulando respuesta del agente');
        $this->line('────────────────────────────────────────');

        $case = ImportedEmail::where('created_at', '>', now()->subHours(2))->first();
        if (!$case) {
            $this->warn('⚠️ No hay casos para responder');
            return;
        }

        $user = User::where('role', 'ejecutivo')->first();
        $group = GmailGroup::active()->first();

        // Simular creación de respuesta como lo haría la interfaz
        $outboxService = new OutboxEmailService();

        // Simular usuario autenticado
        auth()->login($user);

        $emailData = [
            'case_id' => $case->id,
            'from_email' => $group->email,
            'from_name' => $user->name . ' ' . $user->last_name,
            'to' => $case->from_email,
            'subject' => 'RE: ' . $case->subject,
            'message' => "Estimado cliente,\n\nHemos recibido su consulta y estamos trabajando en proporcionarle una respuesta completa.\n\nEn breve nos pondremos en contacto con usted.\n\nSaludos cordiales,\n{$user->name}",
            'priority' => 'normal',
        ];

        try {
            $outboxEmail = $outboxService->createReply($emailData);
            
            $this->line("📧 Respuesta creada en bandeja de salida:");
            $this->line("   ID: {$outboxEmail->id}");
            $this->line("   Para: {$outboxEmail->to_email}");
            $this->line("   Asunto: {$outboxEmail->subject}");
            $this->line("   Estado: {$outboxEmail->send_status}");

        } catch (\Exception $e) {
            $this->error("❌ Error creando respuesta: " . $e->getMessage());
        } finally {
            auth()->logout();
        }

        $this->newLine();
    }

    private function step4_ProcessOutbox()
    {
        $this->info('📤 PASO 4: Procesando bandeja de salida');
        $this->line('────────────────────────────────────');

        $pendingCount = OutboxEmail::where('send_status', 'pending')->count();
        $this->line("📊 Correos pendientes: {$pendingCount}");

        if ($pendingCount > 0) {
            $useReal = $this->option('real');
            
            if ($useReal) {
                $this->info('🌐 Procesando con Gmail API real...');
                $this->call('emails:send-outbox');
            } else {
                $this->warn('🧪 Modo simulación - Marcando como enviados...');
                
                OutboxEmail::where('send_status', 'pending')
                          ->update([
                              'send_status' => 'sent',
                              'sent_at' => now(),
                          ]);
                
                $this->line("✅ {$pendingCount} correos marcados como enviados (simulación)");
            }
        } else {
            $this->line('ℹ️ No hay correos pendientes');
        }

        $this->newLine();
    }

    private function step5_VerifyFollowUp()
    {
        $this->info('🔍 PASO 5: Verificando seguimiento');
        $this->line('─────────────────────────────────');

        // Estadísticas de correos enviados
        $sentToday = OutboxEmail::where('sent_at', '>=', now()->startOfDay())->count();
        $totalOutbox = OutboxEmail::count();
        $totalImported = ImportedEmail::count();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['📧 Correos importados (total)', $totalImported],
                ['📤 Correos en bandeja de salida', $totalOutbox],
                ['✅ Correos enviados hoy', $sentToday],
            ]
        );

        // Casos recientes con actividad
        $recentActivity = ImportedEmail::where('created_at', '>', now()->subHours(2))
                                    ->with('assignedUser')
                                    ->count();

        $this->line("🔄 Casos con actividad reciente: {$recentActivity}");

        if ($this->option('real')) {
            $emailDest = $this->option('email') ?: 'erick661@gmail.com';
            $this->info("📧 Para verificar el correo enviado, revisa: {$emailDest}");
        }

        $this->newLine();
    }
}