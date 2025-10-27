<?php

namespace App\Services\Email;

use App\Models\Email;
use App\Services\Event\EventStore;
use App\Services\Assignment\Contracts\AssignmentStrategyInterface;
use App\Services\Assignment\Strategies\MassCampaignStrategy;
use App\Services\Assignment\Strategies\CaseCodeStrategy;
use App\Services\Assignment\Strategies\GmailGroupStrategy;
use App\Services\Assignment\Strategies\SupervisorFallbackStrategy;
use Illuminate\Support\Facades\Log;

/**
 * ✅ SOLID - Dependency Inversion: Depende de abstracciones (interfaces), no de implementaciones
 * ✅ SOLID - Open/Closed: Abierto para extensión (nuevas estrategias), cerrado para modificación
 * ✅ Event-Driven: Registra eventos de asignación para auditabilidad
 */
class EmailAssignmentService
{
    private EventStore $eventStore;
    
    /** @var AssignmentStrategyInterface[] */
    private array $strategies;

    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
        
        // ✅ SOLID - Dependency Injection: Estrategias inyectadas automáticamente
        $this->strategies = [
            new MassCampaignStrategy(),      // Prioridad 1: Envíos masivos
            new CaseCodeStrategy(),          // Prioridad 2: Casos existentes
            new GmailGroupStrategy(),        // Prioridad 3: Gmail Groups
            new SupervisorFallbackStrategy() // Prioridad 999: Fallback
        ];
        
        // Ordenar por prioridad
        usort($this->strategies, fn($a, $b) => $a->getPriority() <=> $b->getPriority());
    }

    /**
     * ✅ SRP: SOLO responsabilidad de ejecutar estrategias de asignación
     * ✅ Event-Driven: Registra eventos para cada asignación
     */
    public function assignEmail(Email $email): void
    {
        try {
            Log::info("Iniciando asignación estratégica de email", [
                'email_id' => $email->id,
                'gmail_message_id' => $email->gmail_message_id,
                'from_email' => $email->from_email,
                'subject' => $email->subject,
                'total_strategies' => count($this->strategies)
            ]);

            $assigned = false;
            
            // ✅ Strategy Pattern: Ejecutar estrategias en orden de prioridad
            foreach ($this->strategies as $strategy) {
                if ($strategy->canHandle($email)) {
                    Log::info("Ejecutando estrategia de asignación", [
                        'email_id' => $email->id,
                        'strategy' => get_class($strategy),
                        'priority' => $strategy->getPriority()
                    ]);
                    
                    $assignedUserId = $strategy->assign($email);
                    $reason = $strategy->getAssignmentReason();
                    
                    // Registrar resultado (incluso si es null para casos existentes)
                    $this->recordAssignmentResult($email, $assignedUserId, $reason, get_class($strategy));
                    
                    $assigned = true;
                    break; // Solo ejecutamos la primera estrategia que puede manejar el email
                }
            }

            if (!$assigned) {
                $this->recordAssignmentFailure($email, 'Ninguna estrategia pudo manejar el email');
            }

        } catch (\Exception $e) {
            Log::error("Error en asignación estratégica", [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->eventStore->recordError(
                'email_assignment',
                "Error en asignación estratégica: " . $e->getMessage(),
                [
                    'email_id' => $email->id,
                    'from_email' => $email->from_email,
                    'subject' => $email->subject
                ],
                'email',
                $email->id
            );
        }
    }

    /**
     * Asignación manual por supervisor (API legacy compatibility)
     */
    public function assignEmailToAgent(Email $email, int $agentId, ?int $supervisorId = null, ?string $notes = null): void
    {
        $reason = $notes ?? "Asignación manual por supervisor ID: {$supervisorId}";
        $this->recordAssignmentResult($email, $agentId, $reason, 'ManualAssignment');
    }

    /**
     * ✅ Event-Driven: Registrar resultado de asignación
     */
    private function recordAssignmentResult(Email $email, ?int $userId, string $reason, string $strategy): void
    {
        if ($userId) {
            // Asignación exitosa a usuario específico
            $this->eventStore->emailAssigned($email->id, $userId, $reason);
            
            Log::info("Email asignado exitosamente", [
                'email_id' => $email->id,
                'assigned_to' => $userId,
                'reason' => $reason,
                'strategy' => $strategy
            ]);
        } else {
            // Caso especial: relacionado pero sin asignación (ej: caso existente)
            $this->eventStore->record(
                'email.processed',
                "Email procesado: {$reason}",
                [
                    'email_id' => $email->id,
                    'reason' => $reason,
                    'strategy' => $strategy,
                    'requires_manual_assignment' => str_contains($reason, 'supervisor')
                ],
                'email',
                $email->id
            );
            
            Log::info("Email procesado sin asignación directa", [
                'email_id' => $email->id,
                'reason' => $reason,
                'strategy' => $strategy
            ]);
        }
    }

    /**
     * Registrar fallo en asignación
     */
    private function recordAssignmentFailure(Email $email, string $reason): void
    {
        $this->eventStore->recordError(
            'email_assignment',
            "Fallo en asignación: {$reason}",
            [
                'email_id' => $email->id,
                'from_email' => $email->from_email,
                'subject' => $email->subject,
                'gmail_group_id' => $email->gmail_group_id
            ],
            'email',
            $email->id
        );
        
        Log::error("Fallo en asignación de email", [
            'email_id' => $email->id,
            'reason' => $reason
        ]);
    }
}