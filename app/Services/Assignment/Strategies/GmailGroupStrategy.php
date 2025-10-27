<?php

namespace App\Services\Assignment\Strategies;

use App\Models\Email;
use App\Models\GmailGroup;
use App\Services\Assignment\Contracts\AssignmentStrategyInterface;
use Illuminate\Support\Facades\Log;

/**
 * ✅ SOLID - Single Responsibility: Solo maneja asignación por Gmail Group
 * ✅ Event-Driven: Usa configuración dinámica de grupos
 */
class GmailGroupStrategy implements AssignmentStrategyInterface
{
    private string $assignmentReason = '';

    public function canHandle(Email $email): bool
    {
        return !empty($email->gmail_group_id);
    }

    public function assign(Email $email): ?int
    {
        if (!$email->gmail_group_id) {
            return null;
        }

        $gmailGroup = GmailGroup::find($email->gmail_group_id);
        
        if ($gmailGroup && $gmailGroup->assigned_user_id) {
            $this->assignmentReason = "Gmail Group: {$gmailGroup->group_name} ({$gmailGroup->group_email})";
            
            Log::info("Asignación por Gmail Group", [
                'email_id' => $email->id,
                'group_name' => $gmailGroup->group_name,
                'group_email' => $gmailGroup->group_email,
                'assigned_user' => $gmailGroup->assigned_user_id
            ]);
            
            return $gmailGroup->assigned_user_id;
        }

        return null;
    }

    public function getAssignmentReason(): string
    {
        return $this->assignmentReason;
    }

    public function getPriority(): int
    {
        return 3; // Tercera prioridad
    }
}