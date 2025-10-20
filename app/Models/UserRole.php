<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    protected $fillable = [
        'user_id',
        'role',
    ];

    /**
     * Usuario al que pertenece este rol
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
