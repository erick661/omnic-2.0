<?php

namespace App\Services\Email;

use App\Models\ImportedEmail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmailAssignmentService
{
    /**
     * ✅ SRP: Solo responsabilidad de asignar emails
     */
    public function assignEmailToAgent(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $email = ImportedEmail::find($data['email_id']);
            
            if (!$email) {
                throw new \InvalidArgumentException("Email con ID {$data['email_id']} no encontrado");
            }

            // Verificar que el agente existe
            $agent = User::find($data['agent_id']);
            if (!$agent) {
                throw new \InvalidArgumentException("Agente con ID {$data['agent_id']} no encontrado");
            }

            // Verificar supervisor si se proporciona
            if (!empty($data['supervisor_id'])) {
                $supervisor = User::find($data['supervisor_id']);
                if (!$supervisor) {
                    throw new \InvalidArgumentException("Supervisor con ID {$data['supervisor_id']} no encontrado");
                }
            }

            // Verificar si ya está asignado
            if ($email->case_status !== 'pending' && $email->assigned_to) {
                Log::warning('Email ya asignado', [
                    'email_id' => $email->id,
                    'current_agent' => $email->assigned_to,
                    'current_status' => $email->case_status
                ]);
            }

            // Realizar asignación
            $email->update([
                'case_status' => 'assigned',
                'assigned_to' => $data['agent_id'],
                'assigned_by' => $data['supervisor_id'] ?? null,
                'assigned_at' => now(),
                'assignment_notes' => $data['notes'] ?? 'Asignado via sistema'
            ]);

            Log::info('Email asignado exitosamente', [
                'email_id' => $email->id,
                'agent_id' => $data['agent_id'],
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'subject' => $email->subject
            ]);

            return [
                'success' => true,
                'email' => [
                    'id' => $email->id,
                    'subject' => $email->subject,
                    'assigned_to' => $email->assigned_to,
                    'assigned_by' => $email->assigned_by,
                    'assigned_at' => $email->assigned_at->format('Y-m-d H:i:s'),
                    'case_status' => $email->case_status
                ],
                'agent' => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email
                ]
            ];
        });
    }

    /**
     * Asignar múltiples emails a un agente
     */
    public function assignMultipleEmails(array $emailIds, int $agentId, ?int $supervisorId = null): array
    {
        $results = ['assigned' => 0, 'errors' => 0, 'details' => []];

        foreach ($emailIds as $emailId) {
            try {
                $this->assignEmailToAgent([
                    'email_id' => $emailId,
                    'agent_id' => $agentId,
                    'supervisor_id' => $supervisorId,
                    'notes' => 'Asignación masiva via sistema'
                ]);
                $results['assigned']++;
                $results['details'][] = ['email_id' => $emailId, 'status' => 'success'];
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'email_id' => $emailId, 
                    'status' => 'error', 
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Obtener estadísticas de asignación por agente
     */
    public function getAssignmentStats(?int $agentId = null): array
    {
        $query = ImportedEmail::query();
        
        if ($agentId) {
            $query->where('assigned_to', $agentId);
        }

        return [
            'total_assigned' => $query->whereNotNull('assigned_to')->count(),
            'pending' => $query->where('case_status', 'pending')->count(),
            'assigned' => $query->where('case_status', 'assigned')->count(),
            'in_progress' => $query->where('case_status', 'in_progress')->count(),
            'resolved' => $query->where('case_status', 'resolved')->count(),
            'by_agent' => $this->getStatsByAgent($agentId)
        ];
    }

    private function getStatsByAgent(?int $agentId): array
    {
        $query = ImportedEmail::with('assignedUser')
                              ->whereNotNull('assigned_to');
        
        if ($agentId) {
            $query->where('assigned_to', $agentId);
        }

        return $query->selectRaw('
                assigned_to,
                COUNT(*) as total,
                COUNT(CASE WHEN case_status = "assigned" THEN 1 END) as assigned,
                COUNT(CASE WHEN case_status = "in_progress" THEN 1 END) as in_progress,
                COUNT(CASE WHEN case_status = "resolved" THEN 1 END) as resolved
            ')
            ->groupBy('assigned_to')
            ->get()
            ->map(function ($stat) {
                $user = User::find($stat->assigned_to);
                return [
                    'agent_id' => $stat->assigned_to,
                    'agent_name' => $user ? $user->name : 'Usuario no encontrado',
                    'total' => $stat->total,
                    'assigned' => $stat->assigned,
                    'in_progress' => $stat->in_progress,
                    'resolved' => $stat->resolved,
                ];
            })
            ->toArray();
    }
}