<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Communication extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'case_id',
        'channel_type',
        'direction',
        'external_id',
        'thread_id',
        'subject',
        'content_text',
        'content_html',
        'from_contact',
        'from_name',
        'to_contact',
        'cc_contacts',
        'channel_metadata',
        'attachments',
        'status',
        'received_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'reference_code',
        'in_reply_to',
        'processed_at',
        'processed_by',
    ];
    
    protected $casts = [
        'cc_contacts' => 'array',
        'channel_metadata' => 'array',
        'attachments' => 'array',
        'received_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
    
    /**
     * Relación con caso
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CustomerCase::class, 'case_id');
    }
    
    /**
     * Usuario que procesó la comunicación
     */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
    
    /**
     * Comunicación padre (si es una respuesta)
     */
    public function parentCommunication(): BelongsTo
    {
        return $this->belongsTo(Communication::class, 'in_reply_to');
    }
    
    /**
     * Respuestas a esta comunicación
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Communication::class, 'in_reply_to')
                   ->orderBy('received_at');
    }
    
    /**
     * Metadatos específicos de Gmail (si aplica)
     */
    public function gmailMetadata(): HasOne
    {
        return $this->hasOne(GmailMetadata::class, 'communication_id');
    }
    
    /**
     * Metadatos específicos de teléfono (si aplica)
     */
    public function phoneMetadata(): HasOne
    {
        return $this->hasOne(PhoneCommunication::class, 'communication_id');
    }
    
    /**
     * Scopes útiles
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }
    
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }
    
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel_type', $channel);
    }
    
    public function scopeEmail($query)
    {
        return $query->where('channel_type', 'email');
    }
    
    public function scopeWhatsApp($query)
    {
        return $query->where('channel_type', 'whatsapp');
    }
    
    public function scopeSms($query)
    {
        return $query->where('channel_type', 'sms');
    }
    
    public function scopePhone($query)
    {
        return $query->where('channel_type', 'phone');
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }
    
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }
    
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }
    
    public function scopeByThread($query, string $threadId)
    {
        return $query->where('thread_id', $threadId);
    }
    
    public function scopeWithAttachments($query)
    {
        return $query->whereNotNull('attachments')
                    ->where('attachments', '!=', '[]');
    }
    
    /**
     * Métodos de negocio
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
    
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }
    
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }
    
    public function markAsProcessed(User $user): void
    {
        $this->update([
            'processed_at' => now(),
            'processed_by' => $user->id,
        ]);
    }
    
    /**
     * Verificar si es una respuesta
     */
    public function isReply(): bool
    {
        return !is_null($this->in_reply_to);
    }
    
    /**
     * Verificar si tiene adjuntos
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }
    
    /**
     * Obtener contenido apropiado (HTML o texto)
     */
    public function getContentAttribute(): string
    {
        return $this->content_html ?: $this->content_text ?: '';
    }
    
    /**
     * Obtener resumen corto del contenido
     */
    public function getSummaryAttribute(): string
    {
        $content = strip_tags($this->content);
        return \Str::limit($content, 150);
    }
    
    /**
     * Verificar si la comunicación está atrasada para respuesta
     */
    public function isOverdueForResponse(): bool
    {
        if ($this->direction !== 'inbound' || $this->status === 'read') {
            return false;
        }
        
        $slaHours = match($this->channel_type) {
            'email' => 24,
            'whatsapp' => 4,
            'sms' => 2,
            'phone' => 0, // Las llamadas no tienen SLA de respuesta
            'webchat' => 1,
            default => 24
        };
        
        return $this->received_at->addHours($slaHours)->isPast();
    }
    
    /**
     * Generar código de referencia
     */
    public function generateReferenceCode(): string
    {
        // Formato: CANAL-CASO-RANDOM
        $caseNumber = $this->case->case_number;
        $channel = strtoupper(substr($this->channel_type, 0, 2));
        $random = strtoupper(\Str::random(4));
        
        return "{$channel}-{$caseNumber}-{$random}";
    }
    
    /**
     * Boot del modelo para eventos automáticos
     */
    protected static function boot()
    {
        parent::boot();
        
        // Al crear una comunicación, actualizar el contador del caso
        static::created(function ($communication) {
            $communication->case->incrementCommunicationCount();
        });
        
        // Al crear una respuesta (outbound), marcar caso como en progreso
        static::created(function ($communication) {
            if ($communication->direction === 'outbound' && $communication->case->status === 'assigned') {
                $communication->case->markAsInProgress();
            }
        });
    }
}
