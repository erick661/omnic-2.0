<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class CustomerCase extends Model
{
    use HasFactory;
    
    protected $table = 'cases';
    
    protected $fillable = [
        'case_number',
        'employer_rut',
        'employer_dv',
        'employer_name',
        'employer_phone',
        'employer_email',
        'status',
        'priority',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'origin_channel',
        'origin_communication_id',
        'campaign_id',
        'first_response_at',
        'last_activity_at',
        'resolved_at',
        'internal_notes',
        'auto_category',
        'tags',
        'response_time_hours',
        'resolution_time_hours',
        'communication_count',
    ];
    
    protected $casts = [
        'tags' => 'array',
        'assigned_at' => 'datetime',
        'first_response_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
    
    /**
     * Generar número de caso único
     */
    public static function generateCaseNumber(): string
    {
        $year = date('Y');
        $lastCase = self::where('case_number', 'like', "CASO-{$year}-%")
                       ->orderBy('case_number', 'desc')
                       ->first();
        
        if ($lastCase) {
            $lastNumber = (int) substr($lastCase->case_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('CASO-%s-%06d', $year, $newNumber);
    }
    
    /**
     * Relación con comunicaciones
     */
    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class, 'case_id')
                   ->orderBy('received_at', 'desc');
    }
    
    /**
     * Comunicaciones por canal
     */
    public function emailCommunications(): HasMany
    {
        return $this->communications()->where('channel_type', 'email');
    }
    
    public function whatsappCommunications(): HasMany
    {
        return $this->communications()->where('channel_type', 'whatsapp');
    }
    
    public function smsCommunications(): HasMany
    {
        return $this->communications()->where('channel_type', 'sms');
    }
    
    public function phoneCommunications(): HasMany
    {
        return $this->communications()->where('channel_type', 'phone');
    }
    
    /**
     * Última comunicación
     */
    public function lastCommunication()
    {
        return $this->hasOne(Communication::class, 'case_id')
                   ->latest('received_at');
    }
    
    /**
     * Primera comunicación (origen del caso)
     */
    public function firstCommunication()
    {
        return $this->hasOne(Communication::class, 'case_id')
                   ->oldest('received_at');
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
     * Métricas del caso
     */
    public function metrics(): HasOne
    {
        return $this->hasOne(CaseMetrics::class, 'case_id');
    }
    
    /**
     * Códigos de referencia asociados
     */
    public function referenceCodes(): HasMany
    {
        return $this->hasMany(ReferenceCode::class, 'case_id');
    }
    
    /**
     * Campaña que generó el caso (si aplica)
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
    
    /**
     * Scopes útiles
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }
    
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
    
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
    
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('origin_channel', $channel);
    }
    
    public function scopeByExecutive($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }
    
    /**
     * Métodos de negocio
     */
    public function markAsAssigned(User $executive, User $supervisor, ?string $notes = null): void
    {
        $this->update([
            'status' => 'assigned',
            'assigned_to' => $executive->id,
            'assigned_by' => $supervisor->id,
            'assigned_at' => now(),
            'internal_notes' => $notes ? 
                ($this->internal_notes ? $this->internal_notes . "\n\n" . $notes : $notes) : 
                $this->internal_notes,
        ]);
        
        $this->updateActivity();
    }
    
    public function markAsInProgress(): void
    {
        $this->update(['status' => 'in_progress']);
        $this->updateActivity();
        
        // Si es la primera vez que se marca como en progreso, registrar primera respuesta
        if (!$this->first_response_at) {
            $this->update(['first_response_at' => now()]);
            $this->calculateResponseTime();
        }
    }
    
    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
        $this->calculateResolutionTime();
        $this->updateActivity();
    }
    
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
    
    public function incrementCommunicationCount(): void
    {
        $this->increment('communication_count');
        $this->updateActivity();
    }
    
    /**
     * Calcular tiempo de respuesta
     */
    private function calculateResponseTime(): void
    {
        if ($this->first_response_at && $this->created_at) {
            $hours = $this->created_at->diffInHours($this->first_response_at);
            $this->update(['response_time_hours' => $hours]);
        }
    }
    
    /**
     * Calcular tiempo de resolución
     */
    private function calculateResolutionTime(): void
    {
        if ($this->resolved_at && $this->created_at) {
            $hours = $this->created_at->diffInHours($this->resolved_at);
            $this->update(['resolution_time_hours' => $hours]);
        }
    }
    
    /**
     * Obtener RUT completo
     */
    public function getFullRutAttribute(): ?string
    {
        if ($this->employer_rut && $this->employer_dv) {
            return $this->employer_rut . '-' . $this->employer_dv;
        }
        return null;
    }
    
    /**
     * Verificar si el caso está atrasado (SLA)
     */
    public function isOverdue(): bool
    {
        if ($this->status === 'resolved') {
            return false;
        }
        
        $slaHours = match($this->priority) {
            'urgent' => 2,
            'high' => 8,
            'normal' => 24,
            'low' => 48,
            default => 24
        };
        
        return $this->created_at->addHours($slaHours)->isPast();
    }
    
    /**
     * Obtener canal preferido basado en comunicaciones
     */
    public function getPreferredChannel(): ?string
    {
        return $this->communications()
                   ->selectRaw('channel_type, COUNT(*) as count')
                   ->groupBy('channel_type')
                   ->orderBy('count', 'desc')
                   ->first()?->channel_type;
    }
}
