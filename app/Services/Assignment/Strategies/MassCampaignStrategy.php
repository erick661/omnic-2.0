<?php

namespace App\Services\Assignment\Strategies;

use App\Models\Email;
use App\Models\AssignmentRule;
use App\Models\Portfolio;
use App\Services\Assignment\Contracts\AssignmentStrategyInterface;
use Illuminate\Support\Facades\Log;

/**
 * ✅ SOLID - Single Responsibility: Solo maneja asignación por códigos de envío masivo
 * ✅ Event-Driven: Registra eventos específicos de asignación por campaña
 */
class MassCampaignStrategy implements AssignmentStrategyInterface
{
    private string $assignmentReason = '';
    private ?string $campaignCode = null;

    public function canHandle(Email $email): bool
    {
        // Buscar reglas activas de tipo mass_campaign
        $rules = AssignmentRule::active()
                              ->byType(AssignmentRule::TYPE_MASS_CAMPAIGN)
                              ->get();

        foreach ($rules as $rule) {
            $text = ($email->subject ?? '') . ' ' . ($email->body_text ?? '');
            
            if ($rule->matches($text)) {
                $matches = $rule->extractValues($text);
                $this->campaignCode = $matches[1] ?? null; // Primer grupo capturado
                
                Log::info("Código de campaña detectado", [
                    'email_id' => $email->id,
                    'campaign_code' => $this->campaignCode,
                    'rule_pattern' => $rule->pattern_name
                ]);
                
                return true;
            }
        }

        return false;
    }

    public function assign(Email $email): ?int
    {
        if (!$this->campaignCode) {
            return null;
        }

        // Buscar portfolio que maneje este código de campaña
        $portfolio = Portfolio::active()
                            ->get()
                            ->first(function($p) {
                                return $p->matchesCampaignCode($this->campaignCode);
                            });

        if ($portfolio) {
            $this->assignmentReason = "Envío masivo - Código: {$this->campaignCode}, Cartera: {$portfolio->portfolio_name}";
            
            Log::info("Asignación por envío masivo", [
                'email_id' => $email->id,
                'campaign_code' => $this->campaignCode,
                'portfolio' => $portfolio->portfolio_code,
                'assigned_user' => $portfolio->assigned_user_id
            ]);
            
            return $portfolio->assigned_user_id;
        }

        return null;
    }

    public function getAssignmentReason(): string
    {
        return $this->assignmentReason;
    }

    public function getPriority(): int
    {
        return 1; // Máxima prioridad
    }
}