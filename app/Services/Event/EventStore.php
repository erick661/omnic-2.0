<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class EventStore
{
    /**
     * Record a new event
     */
    public function record(
        string $eventType,
        string $aggregateType,
        ?int $aggregateId = null,
        array $eventData = [],
        array $metadata = []
    ): Event {
        // Validate event type exists
        $this->ensureEventTypeExists($eventType, $aggregateType);

        $event = Event::create([
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_data' => $eventData,
            'event_version' => 1,
            'triggered_by' => Auth::id(),
            'triggered_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'severity' => $metadata['severity'] ?? 'info',
            'process_name' => $metadata['process_name'] ?? null,
            'job_id' => $metadata['job_id'] ?? null,
            'correlation_id' => $metadata['correlation_id'] ?? null,
            'causation_id' => $metadata['causation_id'] ?? null
        ]);

        Log::info("Event recorded: {$eventType}", [
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_id' => $event->id
        ]);

        return $event;
    }

    /**
     * Record email received event
     */
    public function emailReceived(int $emailId, array $emailData): Event
    {
        return $this->record(
            'email.received',
            'email',
            $emailId,
            $emailData,
            ['process_name' => 'email_import']
        );
    }

    /**
     * Record email assigned event
     */
    public function emailAssigned(int $emailId, int $userId, ?string $reason = null): Event
    {
        return $this->record(
            'email.assigned',
            'email',
            $emailId,
            [
                'assigned_to' => $userId,
                'assigned_by' => Auth::id(),
                'reason' => $reason,
                'assigned_at' => now()->toISOString()
            ],
            ['process_name' => 'email_assignment']
        );
    }

    /**
     * Record case created event
     */
    public function caseCreated(int $caseId, array $caseData): Event
    {
        return $this->record(
            'case.created',
            'case',
            $caseId,
            $caseData,
            ['process_name' => 'case_management']
        );
    }

    /**
     * Record Gmail import events
     */
    public function gmailImportStarted(array $importConfig): Event
    {
        return $this->record(
            'gmail.import_started',
            'system',
            null,
            $importConfig,
            ['process_name' => 'gmail_import', 'severity' => 'info']
        );
    }

    public function gmailImportCompleted(array $results): Event
    {
        return $this->record(
            'gmail.import_completed',
            'system',
            null,
            $results,
            ['process_name' => 'gmail_import', 'severity' => 'info']
        );
    }

    public function gmailImportFailed(string $error, ?array $context = null): Event
    {
        return $this->record(
            'gmail.import_failed',
            'system',
            null,
            [
                'error_message' => $error,
                'context' => $context
            ],
            ['process_name' => 'gmail_import', 'severity' => 'error']
        );
    }

    /**
     * Record Gmail API errors
     */
    public function gmailApiError(string $errorType, array $errorData): Event
    {
        return $this->record(
            'gmail.api_error',
            'system',
            null,
            array_merge(['error_type' => $errorType], $errorData),
            ['process_name' => 'gmail_api', 'severity' => 'error']
        );
    }

    /**
     * Get events for aggregate
     */
    public function getEventsFor(string $aggregateType, int $aggregateId): \Illuminate\Support\Collection
    {
        return Event::forAggregate($aggregateType, $aggregateId)
                   ->orderBy('triggered_at')
                   ->get();
    }

    /**
     * Get latest event of type for aggregate
     */
    public function getLatestEvent(string $aggregateType, int $aggregateId, string $eventType): ?Event
    {
        return Event::forAggregate($aggregateType, $aggregateId)
                   ->ofType($eventType)
                   ->latest('triggered_at')
                   ->first();
    }

    /**
     * Get current state by replaying events
     */
    public function getCurrentState(string $aggregateType, int $aggregateId): array
    {
        $events = $this->getEventsFor($aggregateType, $aggregateId);
        $state = [];

        foreach ($events as $event) {
            $state = $this->applyEvent($state, $event);
        }

        return $state;
    }

    /**
     * Apply event to state (Event Sourcing projection)
     */
    private function applyEvent(array $state, Event $event): array
    {
        return match($event->event_type) {
            'email.received' => array_merge($state, [
                'status' => 'received',
                'received_at' => $event->triggered_at
            ]),
            'email.assigned' => array_merge($state, [
                'assigned_to' => $event->event_data['assigned_to'],
                'assigned_at' => $event->triggered_at,
                'status' => 'assigned'
            ]),
            'email.processed' => array_merge($state, [
                'processed_at' => $event->triggered_at,
                'status' => 'processed'
            ]),
            'case.created' => array_merge($state, [
                'case_id' => $event->event_data['case_id'] ?? null,
                'case_number' => $event->event_data['case_number'] ?? null
            ]),
            default => $state
        };
    }

    /**
     * Ensure event type exists in catalog
     */
    private function ensureEventTypeExists(string $eventType, string $aggregateType): void
    {
        EventType::firstOrCreate(
            ['event_type' => $eventType],
            [
                'aggregate_type' => $aggregateType,
                'description' => "Auto-generated event type: {$eventType}",
                'severity' => 'info',
                'is_active' => true
            ]
        );
    }

    /**
     * Record error event
     */
    public function recordError(
        string $process, 
        string $error, 
        ?array $context = null,
        ?string $aggregateType = null,
        ?int $aggregateId = null
    ): Event {
        return $this->record(
            'system.error',
            $aggregateType ?? 'system',
            $aggregateId,
            [
                'error_message' => $error,
                'context' => $context
            ],
            [
                'process_name' => $process,
                'severity' => 'error'
            ]
        );
    }
}