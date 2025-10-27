<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmailGroupMember extends Model
{
    protected $fillable = [
        'gmail_group_id',
        'member_email',
        'member_role',
        'is_active',
        'added_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'added_at' => 'datetime'
    ];

    // Custom timestamps
    const CREATED_AT = 'added_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Gmail Group relationship
     */
    public function gmailGroup(): BelongsTo
    {
        return $this->belongsTo(GmailGroup::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('member_role', $role);
    }

    public function scopeMembers($query)
    {
        return $query->where('member_role', 'MEMBER');
    }

    public function scopeManagers($query)
    {
        return $query->where('member_role', 'MANAGER');
    }

    /**
     * Check if member is active
     */
    public function isActiveMember(): bool
    {
        return $this->is_active && $this->gmailGroup->is_active;
    }
}
