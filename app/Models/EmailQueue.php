<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailQueue extends Model
{
    protected $table = 'email_queue';
    
    protected $fillable = [
        'email_id',
        'status',
        'scheduled_at',
        'attempts',
        'max_attempts'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime'
    ];

    // Solo created_at
    const UPDATED_AT = null;

    /**
     * Email relationship
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    /**
     * Events for this queue entry
     */
    public function events()
    {
        return Event::forAggregate('email_queue', $this->id)
                   ->orderBy('triggered_at');
    }

    /**
     * Scopes
     */
    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
                    ->whereRaw('attempts < max_attempts');
    }

    /**
     * Check if can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->attempts < $this->max_attempts;
    }

    /**
     * Increment attempt counter
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
