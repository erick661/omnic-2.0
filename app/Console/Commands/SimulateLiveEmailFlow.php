<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportedEmail;
use App\Models\GmailGroup;
use App\Models\ReferenceCode;
use App\Models\User;
use App\Models\SystemConfig;
use App\Services\MockGmailService;
use Carbon\Carbon;

class SimulateLiveEmailFlow extends Command
{
    protected $signature = 'test:simulate-live-emails 
                            {--duration=300 : Duración en segundos (default: 5 minutos)}
                            {--interval=30 : Intervalo entre correos en segundos (default: 30s)}
                            {--with-responses : Incluir respuestas automáticas de empleadores}';
    
    protected $description = 'Simula la llegada de correos en tiempo real para probar el flujo completo';

    private $isRunning = true;
    private $emailTemplates = [];
    private $companies = [];
    private $referenceCodes = [];

    public function handle()
    {
        $duration = (int) $this->option('duration');
        $interval = (int) $this->option('interval');
        $withResponses = $this->option('with-responses');

        $this->info('🌊 SIMULACIÓN DE CORREOS EN TIEMPO REAL');
        $this->info('=====================================');
        $this->line("⏱️ Duración: {$duration} segundos");
        $this->line("🔄 Intervalo: {$interval} segundos");
        $this->line("📧 Con respuestas: " . ($withResponses ? 'Sí' : 'No'));
        $this->newLine();

        $this->setupTemplatesAndData();

        // Configurar manejo de señales para parar limpiamente
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'stopSimulation']);
            pcntl_signal(SIGINT, [$this, 'stopSimulation']);
        }

        $startTime = time();
        $endTime = $startTime + $duration;
        $emailCount = 0;

        $this->info('🚀 Iniciando simulación...');
        $this->line('Presiona Ctrl+C para detener');
        $this->newLine();

        while (time() < $endTime && $this->isRunning) {
            $emailCount++;
            
            try {
                // Decidir tipo de correo a crear
                $emailType = $this->decideEmailType($withResponses, $emailCount);
                
                $email = $this->createSimulatedEmail($emailType, $emailCount);
                
                if ($email) {
                    $this->displayEmailCreated($email, $emailType);
                    $this->processAutoAssignment($email);
                    
                    // Si es respuesta, simular trabajo del agente
                    if ($emailType === 'response' && rand(1, 100) <= 60) {
                        $this->simulateAgentWork($email);
                    }
                }

            } catch (\Exception $e) {
                $this->error("❌ Error creando email: " . $e->getMessage());
            }

            // Esperar antes del siguiente correo
            for ($i = 0; $i < $interval && $this->isRunning; $i++) {
                sleep(1);
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
            }
        }

        $this->newLine();
        $this->info("✅ Simulación completada - {$emailCount} correos creados");
        $this->showFinalStats();

        return 0;
    }

    public function stopSimulation()
    {
        $this->isRunning = false;
        $this->warn("\n🛑 Deteniendo simulación...");
    }

    private function setupTemplatesAndData()
    {
        // Templates de correos
        $this->emailTemplates = [
            'new_case' => [
                [
                    'subject' => 'Consulta sobre AFP Capital - {company}',
                    'from_name' => 'Recursos Humanos',
                    'body' => 'Estimados, necesitamos información sobre el estado de cotizaciones de nuestros empleados en AFP Capital.',
                ],
                [
                    'subject' => 'Problema con descuentos AFP Habitat - {company}',
                    'from_name' => 'Finanzas',
                    'body' => 'Hemos detectado inconsistencias en los descuentos de AFP Habitat del mes pasado.',
                ],
                [
                    'subject' => 'Solicitud de informe AFP Cuprum - {company}',
                    'from_name' => 'Contabilidad',
                    'body' => 'Necesitamos el informe mensual de cotizaciones para nuestros registros contables.',
                ],
                [
                    'subject' => 'Duda sobre traspaso AFP ProVida - {company}',
                    'from_name' => 'Administración',
                    'body' => 'Tenemos un empleado que quiere traspasar su AFP y necesitamos orientación sobre el proceso.',
                ],
            ],
            'follow_up' => [
                [
                    'subject' => 'Seguimiento: Información adicional requerida {code}',
                    'body' => 'Gracias por la respuesta anterior. Necesitamos algunos detalles adicionales para completar el proceso.',
                ],
                [
                    'subject' => 'RE: Aclaración sobre documentos {code}',
                    'body' => 'Hemos revisado la documentación enviada pero aún tenemos algunas dudas específicas.',
                ],
                [
                    'subject' => 'Urgente: Plazo vencimiento {code}',
                    'body' => 'Se acerca el plazo para entregar la documentación. ¿Podrían ayudarnos a acelerar el proceso?',
                ],
            ]
        ];

        // Empresas simuladas
        $this->companies = [
            ['name' => 'Constructora Los Andes SA', 'email' => 'rrhh@losandes.cl'],
            ['name' => 'Retail Marketplace Ltda', 'email' => 'administracion@marketplace.cl'],
            ['name' => 'Industrias Metálicas SPA', 'email' => 'finanzas@metalicas.cl'],
            ['name' => 'Servicios Profesionales SCR', 'email' => 'contabilidad@serviciosprof.cl'],
            ['name' => 'Logística Express Chile', 'email' => 'operaciones@logexpress.cl'],
            ['name' => 'Tecnología Digital Innovar', 'email' => 'sistemas@innovartech.cl'],
        ];

        // Crear algunos códigos de referencia para casos existentes
        $this->createInitialReferenceCodes();
    }

    private function createInitialReferenceCodes()
    {
        $ejecutivos = User::where('role', 'ejecutivo')->take(3)->get();
        
        if ($ejecutivos->isEmpty()) {
            return;
        }

        foreach (['AFP-CAPITAL', 'AFP-HABITAT', 'AFP-CUPRUM'] as $index => $producto) {
            if ($ejecutivos->count() > $index) {
                $rutBase = 76000000 + rand(100000, 999999);
                $codeHash = ReferenceCode::generateCodeHash($rutBase, '9', $producto);
                
                $referenceCode = ReferenceCode::create([
                    'rut_empleador' => $rutBase,
                    'dv_empleador' => '9',
                    'producto' => $producto,
                    'code_hash' => $codeHash,
                    'assigned_user_id' => $ejecutivos[$index]->id,
                ]);
                
                $this->referenceCodes[] = $referenceCode;
            }
        }
    }

    private function decideEmailType($withResponses, $emailCount)
    {
        // Los primeros correos siempre son nuevos casos
        if ($emailCount <= 3) {
            return 'new_case';
        }

        // Si no hay respuestas habilitadas, siempre crear nuevos casos
        if (!$withResponses) {
            return 'new_case';
        }

        // Después del quinto correo, 70% probabilidad de respuesta
        if ($emailCount > 5 && rand(1, 100) <= 70) {
            return 'follow_up';
        }

        return 'new_case';
    }

    private function createSimulatedEmail($type, $emailCount)
    {
        $group = GmailGroup::active()->inRandomOrder()->first();
        if (!$group) {
            throw new \Exception('No hay grupos Gmail activos');
        }

        if ($type === 'new_case') {
            return $this->createNewCaseEmail($group, $emailCount);
        } else {
            return $this->createFollowUpEmail($group, $emailCount);
        }
    }

    private function createNewCaseEmail($group, $emailCount)
    {
        $template = $this->emailTemplates['new_case'][array_rand($this->emailTemplates['new_case'])];
        $company = $this->companies[array_rand($this->companies)];

        return ImportedEmail::create([
            'gmail_message_id' => 'live_sim_' . time() . '_' . $emailCount,
            'gmail_thread_id' => 'live_thread_' . time() . '_' . $emailCount,
            'gmail_group_id' => $group->id,
            'subject' => str_replace('{company}', $company['name'], $template['subject']),
            'from_email' => $company['email'],
            'from_name' => $template['from_name'] . ' - ' . $company['name'],
            'to_email' => $group->email,
            'body_html' => '<p>' . $template['body'] . '</p>',
            'body_text' => $template['body'],
            'received_at' => now(),
            'imported_at' => now(),
            'case_status' => 'pending',
            'priority' => rand(1, 100) <= 15 ? 'high' : 'normal',
        ]);
    }

    private function createFollowUpEmail($group, $emailCount)
    {
        if (empty($this->referenceCodes)) {
            return $this->createNewCaseEmail($group, $emailCount);
        }

        $referenceCode = $this->referenceCodes[array_rand($this->referenceCodes)];
        $template = $this->emailTemplates['follow_up'][array_rand($this->emailTemplates['follow_up'])];
        
        // Buscar correo previo de esta empresa
        $previousEmail = ImportedEmail::where('reference_code_id', $referenceCode->id)
                                    ->orderBy('received_at', 'desc')
                                    ->first();

        $fromEmail = $previousEmail ? $previousEmail->from_email : 'seguimiento@empresa.cl';
        $fromName = $previousEmail ? $previousEmail->from_name : 'Seguimiento Empresa';

        return ImportedEmail::create([
            'gmail_message_id' => 'live_sim_follow_' . time() . '_' . $emailCount,
            'gmail_thread_id' => $previousEmail ? $previousEmail->gmail_thread_id : 'live_thread_follow_' . time(),
            'gmail_group_id' => $group->id,
            'subject' => str_replace('{code}', $referenceCode->formatted_code, $template['subject']),
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to_email' => $group->email,
            'body_html' => '<p>' . $template['body'] . '</p>',
            'body_text' => $template['body'],
            'received_at' => now(),
            'imported_at' => now(),
            'case_status' => 'pending',
            'priority' => rand(1, 100) <= 25 ? 'high' : 'normal',
            'reference_code_id' => $referenceCode->id,
            'rut_empleador' => $referenceCode->rut_empleador,
            'dv_empleador' => $referenceCode->dv_empleador,
        ]);
    }

    private function displayEmailCreated($email, $type)
    {
        $timestamp = now()->format('H:i:s');
        $typeIcon = $type === 'new_case' ? '🆕' : '🔄';
        $priorityIcon = $email->priority === 'high' ? '🔴' : '🟡';
        
        $this->line("{$timestamp} {$typeIcon} {$priorityIcon} {$email->subject}");
        $this->line("   📧 De: {$email->from_email} → {$email->to_email}");
        
        if ($email->reference_code_id) {
            $refCode = ReferenceCode::find($email->reference_code_id);
            $this->line("   🏷️ Código: {$refCode->formatted_code}");
        }
    }

    private function processAutoAssignment($email)
    {
        if ($email->reference_code_id) {
            $referenceCode = ReferenceCode::find($email->reference_code_id);
            if ($referenceCode) {
                $email->update([
                    'assigned_to' => $referenceCode->assigned_user_id,
                    'assigned_at' => now(),
                    'case_status' => 'assigned',
                ]);

                $assignee = User::find($referenceCode->assigned_user_id);
                $this->line("   ✅ Auto-asignado a: {$assignee->name}");
            }
        }
    }

    private function simulateAgentWork($email)
    {
        // Simular que el agente responde después de un tiempo
        $responseDelay = rand(10, 60); // 10 segundos a 1 minuto
        
        $this->line("   ⏳ Simulando respuesta del agente en {$responseDelay}s...");
        
        // En un caso real, esto podría ser una tarea en cola
        sleep(min($responseDelay, 5)); // Máximo 5 segundos para la demo
        
        $email->update([
            'case_status' => 'in_progress',
        ]);
        
        $this->line("   💬 Agente respondió - Estado: in_progress");
    }

    private function showFinalStats()
    {
        $this->newLine();
        $this->info('📊 ESTADÍSTICAS FINALES');
        $this->line('═══════════════════════');

        $recentEmails = ImportedEmail::where('created_at', '>', now()->subHour())->get();
        
        $stats = [
            'total' => $recentEmails->count(),
            'pending' => $recentEmails->where('case_status', 'pending')->count(),
            'assigned' => $recentEmails->where('case_status', 'assigned')->count(),
            'in_progress' => $recentEmails->where('case_status', 'in_progress')->count(),
            'resolved' => $recentEmails->where('case_status', 'resolved')->count(),
            'high_priority' => $recentEmails->where('priority', 'high')->count(),
        ];

        $this->table(
            ['Métrica', 'Cantidad', 'Porcentaje'],
            [
                ['📧 Total correos', $stats['total'], '100%'],
                ['⏳ Pendientes', $stats['pending'], $this->percentage($stats['pending'], $stats['total'])],
                ['🎯 Asignados', $stats['assigned'], $this->percentage($stats['assigned'], $stats['total'])],
                ['🔄 En progreso', $stats['in_progress'], $this->percentage($stats['in_progress'], $stats['total'])],
                ['✅ Resueltos', $stats['resolved'], $this->percentage($stats['resolved'], $stats['total'])],
                ['🔴 Alta prioridad', $stats['high_priority'], $this->percentage($stats['high_priority'], $stats['total'])],
            ]
        );

        $this->newLine();
        $this->line('🔗 Ver resultados en: ' . config('app.url') . '/inbox');
    }

    private function percentage($part, $total)
    {
        if ($total === 0) return '0%';
        return round(($part / $total) * 100, 1) . '%';
    }
}