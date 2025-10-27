<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Email extends Model
{
    protected $fillable = [
        'gmail_message_id',
        'gmail_thread_id',
        'direction',
        'subject',
        'from_email',
        'from_name',
        'to_email',
        'to_name',
        'cc_emails',
        'bcc_emails',
        'reply_to',
        'body_text',
        'body_html',
        'gmail_internal_date',
        'gmail_headers',
        'gmail_labels',
        'gmail_size_estimate',
        'gmail_snippet',
        'raw_headers',
        'message_references',
        'in_reply_to',
        'has_attachments',
        'gmail_group_id',
        'parent_email_id'
    ];

    protected $casts = [
        'cc_emails' => 'array',
        'bcc_emails' => 'array',
        'gmail_headers' => 'array',
        'gmail_labels' => 'array',
        'raw_headers' => 'array',
        'has_attachments' => 'boolean',
        'gmail_internal_date' => 'integer',
        'gmail_size_estimate' => 'integer'
    ];

    // No timestamps automáticos - solo created_at manual
    const UPDATED_AT = null;

    /**
     * Gmail Group relationship
     */
    public function gmailGroup(): BelongsTo
    {
        return $this->belongsTo(GmailGroup::class);
    }

    /**
     * Parent email (for threads/replies)
     */
    public function parentEmail(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'parent_email_id');
    }

    /**
     * Child emails (replies)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Email::class, 'parent_email_id');
    }

    /**
     * Email attachments
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    /**
     * Email queue entries
     */
    public function queueEntries(): HasMany
    {
        return $this->hasMany(EmailQueue::class);
    }

    /**
     * Communications (polymorphic)
     */
    public function communications(): MorphMany
    {
        return $this->morphMany(Communication::class, 'channel');
    }

    /**
     * Events related to this email
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'aggregate_id')
                    ->where('aggregate_type', 'email');
    }

    /**
     * Scopes
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeWithGmailId($query, string $gmailId)
    {
        return $query->where('gmail_message_id', $gmailId);
    }

    /**
     * Get current status from events (Event Sourcing)
     */
    public function getCurrentStatus(): ?string
    {
        $statusEvent = $this->events()
            ->where('event_type', 'email.status_changed')
            ->latest('triggered_at')
            ->first();

        return $statusEvent?->event_data['new_status'] ?? 'received';
    }

    /**
     * Get assigned user from events (Event Sourcing)
     */
    public function getAssignedUserId(): ?int
    {
        $assignEvent = $this->events()
            ->where('event_type', 'email.assigned')
            ->latest('triggered_at')
            ->first();

        return $assignEvent?->event_data['new_assigned_to'] ?? null;
    }

    /**
     * Check if email is processed
     */
    public function isProcessed(): bool
    {
        return $this->events()
            ->where('event_type', 'email.processed')
            ->exists();
    }
}
