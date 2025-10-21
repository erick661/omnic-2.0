<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GmailMetadata extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'communication_id',
        'gmail_message_id',
        'gmail_thread_id',
        'gmail_history_id',
        'gmail_labels',
        'gmail_snippet',
        'size_estimate',
        'raw_headers',
        'message_references',
        'in_reply_to',
        'eml_download_url',
        'eml_backup_path',
        'attachments_metadata',
        'sync_status',
        'last_sync_at',
        'sync_error_message',
        'is_backed_up',
        'backup_at',
    ];
    
    protected $casts = [
        'gmail_labels' => 'array',
        'raw_headers' => 'array',
        'attachments_metadata' => 'array',
        'last_sync_at' => 'datetime',
        'is_backed_up' => 'boolean',
        'backup_at' => 'datetime',
    ];
    
    /**
     * Relación con comunicación
     */
    public function communication(): BelongsTo
    {
        return $this->belongsTo(Communication::class);
    }
    
    /**
     * Scopes útiles
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }
    
    public function scopePendingSync($query)
    {
        return $query->where('sync_status', 'pending');
    }
    
    public function scopeWithErrors($query)
    {
        return $query->where('sync_status', 'error');
    }
    
    public function scopeBackedUp($query)
    {
        return $query->where('is_backed_up', true);
    }
    
    public function scopePendingBackup($query)
    {
        return $query->where('is_backed_up', false);
    }
    
    public function scopeByThread($query, string $threadId)
    {
        return $query->where('gmail_thread_id', $threadId);
    }
    
    /**
     * Métodos de negocio
     */
    public function markAsSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_sync_at' => now(),
            'sync_error_message' => null,
        ]);
    }
    
    public function markSyncError(string $errorMessage): void
    {
        $this->update([
            'sync_status' => 'error',
            'sync_error_message' => $errorMessage,
            'last_sync_at' => now(),
        ]);
    }
    
    public function markAsBackedUp(string $backupPath): void
    {
        $this->update([
            'is_backed_up' => true,
            'backup_at' => now(),
            'eml_backup_path' => $backupPath,
        ]);
    }
    
    /**
     * Verificar si necesita sincronización
     */
    public function needsSync(): bool
    {
        if ($this->sync_status === 'pending') {
            return true;
        }
        
        if ($this->sync_status === 'error') {
            // Reintentar después de 1 hora si hubo error
            return $this->last_sync_at?->addHour()->isPast() ?? true;
        }
        
        // Sincronizar cada 24 horas los que están OK
        return $this->last_sync_at?->addDay()->isPast() ?? true;
    }
    
    /**
     * Obtener etiquetas Gmail como array de strings
     */
    public function getGmailLabelsListAttribute(): array
    {
        return $this->gmail_labels ?? [];
    }
    
    /**
     * Verificar si el mensaje tiene una etiqueta específica
     */
    public function hasLabel(string $label): bool
    {
        return in_array($label, $this->gmail_labels_list);
    }
    
    /**
     * Verificar si está en la bandeja de entrada
     */
    public function isInInbox(): bool
    {
        return $this->hasLabel('INBOX');
    }
    
    /**
     * Verificar si está marcado como SPAM
     */
    public function isSpam(): bool
    {
        return $this->hasLabel('SPAM');
    }
    
    /**
     * Verificar si fue enviado
     */
    public function isSent(): bool
    {
        return $this->hasLabel('SENT');
    }
    
    /**
     * Verificar si está en borrador
     */
    public function isDraft(): bool
    {
        return $this->hasLabel('DRAFT');
    }
    
    /**
     * Obtener header específico
     */
    public function getHeader(string $headerName): ?string
    {
        $headers = $this->raw_headers ?? [];
        return $headers[$headerName] ?? $headers[strtolower($headerName)] ?? null;
    }
    
    /**
     * Obtener información de adjuntos
     */
    public function getAttachmentsInfoAttribute(): array
    {
        $attachments = $this->attachments_metadata ?? [];
        
        return array_map(function ($attachment) {
            return [
                'filename' => $attachment['filename'] ?? 'unknown',
                'size' => $attachment['size'] ?? 0,
                'mime_type' => $attachment['mimeType'] ?? 'application/octet-stream',
                'has_gmail_id' => !empty($attachment['attachmentId']),
            ];
        }, $attachments);
    }
    
    /**
     * Verificar si tiene adjuntos
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments_metadata);
    }
    
    /**
     * Obtener tamaño total de adjuntos
     */
    public function getTotalAttachmentSize(): int
    {
        $attachments = $this->attachments_metadata ?? [];
        
        return array_sum(array_column($attachments, 'size'));
    }
    
    /**
     * Generar estadísticas de thread
     */
    public static function getThreadStatistics(string $threadId): array
    {
        $metadata = self::byThread($threadId)->with('communication.case')->get();
        
        return [
            'total_messages' => $metadata->count(),
            'backed_up_count' => $metadata->where('is_backed_up', true)->count(),
            'sync_errors' => $metadata->where('sync_status', 'error')->count(),
            'cases_involved' => $metadata->pluck('communication.case.case_number')->unique()->values()->toArray(),
            'date_range' => [
                'first' => $metadata->min('created_at'),
                'last' => $metadata->max('created_at'),
            ],
            'total_size' => $metadata->sum('size_estimate'),
            'has_attachments' => $metadata->filter->hasAttachments()->count(),
        ];
    }
}
