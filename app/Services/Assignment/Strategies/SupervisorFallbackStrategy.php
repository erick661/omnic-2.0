<?php

namespace App\Services\Assignment\Strategies;

use App\Models\Email;
use App\Services\Assignment\Contracts\AssignmentStrategyInterface;
use Illuminate\Support\Facades\Log;

/**
 * ✅ SOLID - Single Responsibility: Solo maneja fallback a supervisor
 * ✅ Event-Driven: Registra emails que requieren asignación manual
 */
class SupervisorFallbackStrategy implements AssignmentStrategyInterface
{
    private string $assignmentReason = '';

    public function canHandle(Email $email): bool
    {
        // Esta estrategia siempre puede manejar emails (es el fallback)
        return true;
    }

    public function assign(Email $email): ?int
    {
        // No asignamos a usuario específico, dejamos para supervisor
        $this->assignmentReason = 'Requiere asignación manual por supervisor - sin reglas coincidentes';
        
        Log::warning("Email requiere asignación manual", [
            'email_id' => $email->id,
            'from_email' => $email->from_email,
            'subject' => $email->subject,
            'reason' => 'No hay reglas de asignación automática coincidentes'
        ]);
        
        // Retornamos null - el supervisor debe asignar manualmente
        return null;
    }

    public function getAssignmentReason(): string
    {
        return $this->assignmentReason;
    }

    public function getPriority(): int
    {
        return 999; // Mínima prioridad (último recurso)
    }
}