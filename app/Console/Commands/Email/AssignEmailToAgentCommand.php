<?php

namespace App\Console\Commands\Email;

use Illuminate\Console\Command;
use App\Services\Email\EmailAssignmentService;

class AssignEmailToAgentCommand extends Command
{
    protected $signature = 'email:assign-to-agent 
                           {emailId : ID del email a asignar} 
                           {agentId : ID del agente} 
                           {--supervisor=1 : ID del supervisor que asigna}
                           {--notes= : Notas de asignación}';

    protected $description = '✅ SOLID: Asignar un correo pendiente a un agente específico';

    public function __construct(
        private EmailAssignmentService $assignmentService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // ✅ SOLID: Solo orquestación, sin lógica de negocio
        $emailId = (int) $this->argument('emailId');
        $agentId = (int) $this->argument('agentId');
        $supervisorId = (int) $this->option('supervisor');
        $notes = $this->option('notes');

        $this->info("📧 Asignando correo ID {$emailId} al agente ID {$agentId}...");

        try {
            // ✅ DIP: Depende de abstracción (servicio), no implementación
            $result = $this->assignmentService->assignEmailToAgent([
                'email_id' => $emailId,
                'agent_id' => $agentId,
                'supervisor_id' => $supervisorId,
                'notes' => $notes ?: 'Asignado via comando CLI'
            ]);

            // ✅ SRP: Solo responsabilidad de mostrar resultados
            $this->displayResult($result);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function displayResult(array $result): void
    {
        $email = $result['email'];
        
        $this->info("✅ Email asignado exitosamente:");
        $this->table(['Campo', 'Valor'], [
            ['ID Email', $email['id']],
            ['Asunto', $email['subject']],
            ['Agente ID', $email['assigned_to']],
            ['Supervisor ID', $email['assigned_by']],
            ['Fecha Asignación', $email['assigned_at']],
            ['Estado', $email['case_status']],
        ]);
    }
}