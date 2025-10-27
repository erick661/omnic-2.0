<?php

namespace App\Services\Assignment\Strategies;

use App\Models\Email;
use App\Models\AssignmentRule;
use App\Services\Assignment\Contracts\AssignmentStrategyInterface;
use Illuminate\Support\Facades\Log;

/**
 * ✅ SOLID - Single Responsibility: Solo maneja asignación por códigos de caso existente
 * ✅ Event-Driven: Relaciona emails con casos existentes
 */
class CaseCodeStrategy implements AssignmentStrategyInterface
{
    private string $assignmentReason = '';
    private ?string $caseCode = null;

    public function canHandle(Email $email): bool
    {
        // Buscar reglas activas de tipo case_code
        $rules = AssignmentRule::active()
                              ->byType(AssignmentRule::TYPE_CASE_CODE)
                              ->get();

        foreach ($rules as $rule) {
            $text = ($email->subject ?? '') . ' ' . ($email->body_text ?? '');
            
            if ($rule->matches($text)) {
                $matches = $rule->extractValues($text);
                $this->caseCode = $matches[1] ?? null; // Primer grupo capturado
                
                Log::info("Código de caso detectado", [
                    'email_id' => $email->id,
                    'case_code' => $this->caseCode,
                    'rule_pattern' => $rule->pattern_name
                ]);
                
                return true;
            }
        }

        return false;
    }

    public function assign(Email $email): ?int
    {
        if (!$this->caseCode) {
            return null;
        }

        // TODO: Aquí buscarías en la tabla de casos existentes
        // Por ahora, asumimos que el caso existe y retornamos null 
        // para que el email se relacione pero no se asigne a usuario específico
        
        $this->assignmentReason = "Email relacionado con caso existente: {$this->caseCode}";
        
        Log::info("Email relacionado con caso", [
            'email_id' => $email->id,
            'case_code' => $this->caseCode,
            'action' => 'linked_to_existing_case'
        ]);

        // Retornamos null porque el caso ya tiene asignación previa
        // El email se relaciona pero no cambia la asignación del caso
        return null;
    }

    public function getAssignmentReason(): string
    {
        return $this->assignmentReason;
    }

    public function getPriority(): int
    {
        return 2; // Segunda prioridad
    }
}