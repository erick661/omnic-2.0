<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ImportedEmail extends Model
{
    protected $fillable = [
        'gmail_message_id',
        'gmail_thread_id',
        'gmail_group_id',
        'subject',
        'from_email',
        'from_name',
        'to_email',
        'cc_emails',
        'bcc_emails',
        'body_html',
        'body_text',
        'received_at',
        'imported_at',
        'has_attachments',
        'priority',
        'reference_code_id',
        'rut_empleador',
        'dv_empleador',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'assignment_notes',
        'case_status',
        'marked_resolved_at',
        'auto_resolved_at',
        'spam_marked_by',
        'spam_marked_at',
        'derived_to_supervisor',
        'derivation_notes',
        'derived_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'imported_at' => 'datetime',
            'assigned_at' => 'datetime',
            'marked_resolved_at' => 'datetime',
            'auto_resolved_at' => 'datetime',
            'spam_marked_at' => 'datetime',
            'derived_at' => 'datetime',
            'has_attachments' => 'boolean',
            'derived_to_supervisor' => 'boolean',
            'cc_emails' => 'array',
            'bcc_emails' => 'array',
        ];
    }

    /**
     * Grupo Gmail asociado
     */
    public function gmailGroup(): BelongsTo
    {
        return $this->belongsTo(GmailGroup::class);
    }

    /**
     * Código de referencia asociado
     */
    public function referenceCode(): BelongsTo
    {
        return $this->belongsTo(ReferenceCode::class);
    }

    /**
     * Ejecutivo asignado
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Supervisor que asignó
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Usuario que marcó como spam
     */
    public function spamMarkedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spam_marked_by');
    }

    /**
     * Adjuntos del correo
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    /**
     * Obtiene el RUT completo formateado
     */
    public function getFormattedRutAttribute(): ?string
    {
        if (!$this->rut_empleador || !$this->dv_empleador) {
            return null;
        }
        
        return number_format($this->rut_empleador, 0, '', '.') . '-' . $this->dv_empleador;
    }

    /**
     * Verifica si el correo está vencido (más de 2 días sin respuesta)
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!in_array($this->case_status, ['assigned', 'opened', 'in_progress'])) {
            return false;
        }
        
        $compareDate = $this->assigned_at ?? $this->imported_at;
        return $compareDate->diffInDays(now()) > 2;
    }

    /**
     * Obtiene el tiempo transcurrido desde la asignación
     */
    public function getTimeFromAssignmentAttribute(): ?string
    {
        if (!$this->assigned_at) {
            return null;
        }
        
        return $this->assigned_at->diffForHumans();
    }

    /**
     * Asigna el correo a un ejecutivo
     */
    public function assignTo(User $user, ?User $assignedBy = null, ?string $notes = null): void
    {
        $this->update([
            'assigned_to' => $user->id,
            'assigned_by' => $assignedBy?->id,
            'assigned_at' => now(),
            'assignment_notes' => $notes,
            'case_status' => 'assigned',
        ]);
    }

    /**
     * Marca el correo como spam
     */
    public function markAsSpam(User $user): void
    {
        $this->update([
            'case_status' => 'spam_marked',
            'spam_marked_by' => $user->id,
            'spam_marked_at' => now(),
        ]);
    }

    /**
     * Deriva al supervisor
     */
    public function deriveToSupervisor(string $notes): void
    {
        $this->update([
            'derived_to_supervisor' => true,
            'derivation_notes' => $notes,
            'derived_at' => now(),
        ]);
    }

    /**
     * Intenta auto-asignar basado en código de referencia
     */
    public function tryAutoAssignment(): bool
    {
        $referenceCode = ReferenceCode::findBySubject($this->subject);
        
        if ($referenceCode) {
            $this->update([
                'reference_code_id' => $referenceCode->id,
                'rut_empleador' => $referenceCode->rut_empleador,
                'dv_empleador' => $referenceCode->dv_empleador,
                'assigned_to' => $referenceCode->assigned_user_id,
                'assigned_at' => now(),
                'case_status' => 'assigned',
            ]);
            
            return true;
        }
        
        return false;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('case_status', 'pending');
    }

    public function scopeAssigned($query)
    {
        return $query->where('case_status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('case_status', ['opened', 'in_progress']);
    }

    public function scopeResolved($query)
    {
        return $query->where('case_status', 'resolved');
    }

    public function scopeSpam($query)
    {
        return $query->where('case_status', 'spam_marked');
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('case_status', ['assigned', 'opened', 'in_progress'])
                    ->where(function ($q) {
                        $q->where('assigned_at', '<=', now()->subDays(2))
                          ->orWhere(function ($sq) {
                              $sq->whereNull('assigned_at')
                                 ->where('imported_at', '<=', now()->subDays(2));
                          });
                    });
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('assigned_to', $user->id);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('imported_at', '>=', now()->subDays($days));
    }
}
