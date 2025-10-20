<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmailGroup extends Model
{
    protected $fillable = [
        'name',
        'email',
        'is_active',
        'is_generic',
        'assigned_user_id',
        'gmail_label',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_generic' => 'boolean',
        ];
    }

    /**
     * Usuario asignado para grupos genéricos
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Correos importados para este grupo
     */
    public function importedEmails(): HasMany
    {
        return $this->hasMany(ImportedEmail::class);
    }

    /**
     * Scope para grupos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para grupos genéricos
     */
    public function scopeGeneric($query)
    {
        return $query->where('is_generic', true);
    }

    /**
     * Scope para grupos de ejecutivos
     */
    public function scopeExecutive($query)
    {
        return $query->where('is_generic', false);
    }
}
