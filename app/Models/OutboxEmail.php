<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OutboxEmail extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'imported_email_id',
        'from_email',
        'from_name',
        'to_email',
        'cc_emails',
        'bcc_emails',
        'subject',
        'body_html',
        'body_text',
        'send_status',
        'scheduled_at',
        'sent_at',
        'error_message',
        'mark_as_resolved',
        'created_by',
    ];

    protected $casts = [
        'cc_emails' => 'array',
        'bcc_emails' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'mark_as_resolved' => 'boolean',
    ];

    /**
     * Correo importado al que responde (opcional)
     */
    public function importedEmail(): BelongsTo
    {
        return $this->belongsTo(ImportedEmail::class);
    }

    /**
     * Usuario que creó este correo de salida
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Adjuntos del correo de salida
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(OutboxAttachment::class);
    }

    /**
     * Scopes para estados
     */
    public function scopePending($query)
    {
        return $query->where('send_status', 'pending');
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('send_status', 'pending')
                    ->where(function($q) {
                        $q->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', now());
                    });
    }

    public function scopeSent($query)
    {
        return $query->where('send_status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('send_status', 'failed');
    }

    /**
     * Obtener información del hilo para respuestas
     */
    public function getThreadInfoAttribute(): ?array
    {
        if ($this->importedEmail) {
            return [
                'thread_id' => $this->importedEmail->gmail_thread_id,
                'in_reply_to' => $this->importedEmail->gmail_message_id,
                'original_subject' => $this->importedEmail->subject,
            ];
        }
        return null;
    }

    /**
     * Verificar si es respuesta a un correo
     */
    public function isReply(): bool
    {
        return !is_null($this->imported_email_id);
    }

    /**
     * Marcar como enviado exitosamente
     */
    public function markAsSent(string $messageId, ?string $threadId = null): void
    {
        $this->update([
            'send_status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);

        // Actualizar el caso si se marcó como resuelto
        if ($this->mark_as_resolved && $this->importedEmail) {
            $this->importedEmail->update([
                'case_status' => 'resolved',
                'marked_resolved_at' => now(),
            ]);
        }

        // Crear registro en communications para el seguimiento omnicanal
        $this->createCommunicationRecord($messageId, $threadId);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'send_status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Crear registro en la tabla communications para seguimiento omnicanal
     */
    private function createCommunicationRecord(string $messageId, ?string $threadId = null): void
    {
        // Si es respuesta, buscar o crear el caso
        $caseId = null;
        if ($this->importedEmail) {
            // Por ahora usamos el ID del imported_email como case_id
            // Más adelante se puede implementar la lógica completa de casos
            $caseId = $this->importedEmail->id;
        }

        Communication::create([
            'case_id' => $caseId,
            'channel_type' => 'email',
            'direction' => 'outbound',
            'external_id' => $messageId,
            'thread_id' => $threadId ?? $this->thread_info['thread_id'] ?? null,
            'subject' => $this->subject,
            'content_text' => $this->body_text ?? strip_tags($this->body_html),
            'content_html' => $this->body_html,
            'from_contact' => $this->from_email,
            'from_name' => $this->from_name,
            'to_contact' => $this->to_email,
            'cc_contacts' => $this->cc_emails,
            'status' => 'sent',
            'sent_at' => now(),
            'processed_at' => now(),
            'processed_by' => $this->created_by,
        ]);
    }
}
