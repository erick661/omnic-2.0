<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    protected $fillable = [
        'event_type',
        'aggregate_type',
        'description',
        'severity',
        'schema_version',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Use event_type as primary key for foreign relations
    protected $primaryKey = 'event_type';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Events of this type
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'event_type', 'event_type');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAggregate($query, string $aggregateType)
    {
        return $query->where('aggregate_type', $aggregateType);
    }

    public function scopeWithSeverity($query, array $severities)
    {
        return $query->whereIn('severity', $severities);
    }

    /**
     * Get event types for specific aggregate
     */
    public static function getForAggregate(string $aggregateType): array
    {
        return static::forAggregate($aggregateType)
            ->active()
            ->pluck('event_type')
            ->toArray();
    }

    /**
     * Common event types
     */
    public static function emailEvents(): array
    {
        return [
            'email.received',
            'email.sent', 
            'email.assigned',
            'email.processed',
            'email.status_changed',
            'email.marked_as_spam',
            'email.bounced'
        ];
    }

    public static function caseEvents(): array
    {
        return [
            'case.created',
            'case.assigned',
            'case.status_changed',
            'case.resolved',
            'case.note_added'
        ];
    }

    public static function gmailEvents(): array
    {
        return [
            'gmail.auth_success',
            'gmail.auth_failed',
            'gmail.quota_exceeded',
            'gmail.import_started',
            'gmail.import_completed',
            'gmail.import_failed'
        ];
    }
}
