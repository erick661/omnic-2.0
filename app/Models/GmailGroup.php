<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmailGroup extends Model
{
    protected $fillable = [
        'group_email',
        'group_name', 
        'group_type',
        'assigned_user_id',
        'import_enabled',
        'gmail_label',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'import_enabled' => 'boolean'
    ];

    /**
     * Usuario asignado responsable del grupo
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Emails asociados a este grupo
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'gmail_group_id');
    }

    /**
     * Miembros del grupo Gmail
     */
    public function members(): HasMany
    {
        return $this->hasMany(GmailGroupMember::class);
    }

    /**
     * Eventos relacionados con este grupo
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'aggregate_id')
                    ->where('aggregate_type', 'gmail_group');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeImportEnabled($query)
    {
        return $query->where('import_enabled', true);
    }

    public function scopePersonal($query)
    {
        return $query->where('group_type', 'personal');
    }

    public function scopeGeneric($query)
    {
        return $query->where('group_type', 'generic');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    /**
     * Check if group should be imported
     */
    public function shouldImport(): bool
    {
        return $this->is_active && $this->import_enabled;
    }

    /**
     * Get the primary member email (usually comunicaciones@orpro.cl)
     */
    public function getPrimaryMemberEmail(): ?string
    {
        return $this->members()
            ->where('is_active', true)
            ->where('member_role', 'MEMBER')
            ->first()
            ?->member_email;
    }
}
