<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'role',
        'is_active',
        'email_alias',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Roles adicionales del usuario (para roles múltiples)
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Grupos Gmail asignados (para grupos genéricos)
     */
    public function assignedGmailGroups(): HasMany
    {
        return $this->hasMany(GmailGroup::class, 'assigned_user_id');
    }

    /**
     * Códigos de referencia asignados
     */
    public function referenceCodes(): HasMany
    {
        return $this->hasMany(ReferenceCode::class, 'assigned_user_id');
    }

    /**
     * Correos asignados a este usuario
     */
    public function assignedEmails(): HasMany
    {
        return $this->hasMany(ImportedEmail::class, 'assigned_to');
    }

    /**
     * Correos asignados por este usuario (supervisor)
     */
    public function emailsAssignedByMe(): HasMany
    {
        return $this->hasMany(ImportedEmail::class, 'assigned_by');
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role || 
               $this->userRoles()->where('role', $role)->exists();
    }

    /**
     * Obtiene todos los roles del usuario
     */
    public function getAllRoles(): array
    {
        $roles = [$this->role];
        $additionalRoles = $this->userRoles()->pluck('role')->toArray();
        
        return array_unique(array_merge($roles, $additionalRoles));
    }

    /**
     * Scope para usuarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para usuarios por rol
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role)
                    ->orWhereHas('userRoles', function ($q) use ($role) {
                        $q->where('role', $role);
                    });
    }
}
