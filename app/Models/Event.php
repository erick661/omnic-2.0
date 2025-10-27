<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'event_data',
        'event_version',
        'triggered_by',
        'triggered_at',
        'ip_address',
        'user_agent',
        'severity',
        'process_name',
        'job_id',
        'correlation_id',
        'causation_id',
        'processed',
        'processed_at',
        'processed_by',
        'error_code',
        'error_message',
        'stack_trace'
    ];

    protected $casts = [
        'event_data' => 'array',
        'triggered_at' => 'datetime',
        'processed_at' => 'datetime',
        'processed' => 'boolean'
    ];

    // No timestamps automáticos - usamos triggered_at
    public $timestamps = false;

    /**
     * Event Type relationship
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type', 'event_type');
    }

    /**
     * User who triggered the event
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Causation event (parent event)
     */
    public function causationEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'causation_id');
    }

    /**
     * Get the aggregate model (polymorphic)
     */
    public function aggregate()
    {
        return match($this->aggregate_type) {
            'email' => Email::find($this->aggregate_id),
            'case' => CustomerCase::find($this->aggregate_id),
            'user' => User::find($this->aggregate_id),
            'gmail_group' => GmailGroup::find($this->aggregate_id),
            default => null
        };
    }

    /**
     * Scopes
     */
    public function scopeForAggregate($query, string $type, int $id)
    {
        return $query->where('aggregate_type', $type)
                    ->where('aggregate_id', $id);
    }

    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeWithSeverity($query, array $severities)
    {
        return $query->whereIn('severity', $severities);
    }

    public function scopeTriggeredByProcess($query, string $processName)
    {
        return $query->where('process_name', $processName);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('severity', ['error', 'critical']);
    }

    /**
     * Mark event as processed
     */
    public function markAsProcessed(?string $processedBy = null): bool
    {
        return $this->update([
            'processed' => true,
            'processed_at' => now(),
            'processed_by' => $processedBy
        ]);
    }

    /**
     * Check if event is an error
     */
    public function isError(): bool
    {
        return in_array($this->severity, ['error', 'critical']);
    }
}
