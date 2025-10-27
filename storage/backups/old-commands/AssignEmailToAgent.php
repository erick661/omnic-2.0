<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportedEmail;
use App\Models\User;

class AssignEmailToAgent extends Command
{
    protected $signature = 'email:assign {emailId : ID del email a asignar} {agentId : ID del agente} {--supervisor=1 : ID del supervisor que asigna}';
    protected $description = 'Asignar un correo pendiente a un agente específico';

    public function handle()
    {
        $emailId = $this->argument('emailId');
        $agentId = $this->argument('agentId');
        $supervisorId = $this->option('supervisor');

        $email = ImportedEmail::find($emailId);
        
        if (!$email) {
            $this->error("❌ Email con ID {$emailId} no encontrado");
            return 1;
        }

        if ($email->case_status !== 'pending') {
            $this->warn("⚠️  Email ya está asignado (Estado: {$email->case_status})");
        }

        $email->case_status = 'assigned';
        $email->assigned_to = $agentId;
        $email->assigned_by = $supervisorId;
        $email->assigned_at = now();
        $email->assignment_notes = 'Asignado via comando CLI';
        $email->save();

        $this->info("✅ Email asignado exitosamente:");
        $this->info("   📧 Asunto: {$email->subject}");
        $this->info("   👤 Agente ID: {$agentId}");
        $this->info("   👨‍💼 Supervisor ID: {$supervisorId}");
        $this->info("   📅 Fecha: " . $email->assigned_at);

        return 0;
    }
}
