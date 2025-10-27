<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ✅ SOLID - Single Responsibility: Solo maneja carteras de ejecutivos
 */
class Portfolio extends Model
{
    /** @use HasFactory<\Database\Factories\PortfolioFactory> */
    use HasFactory;

    protected $fillable = [
        'portfolio_code',
        'portfolio_name',
        'assigned_user_id',
        'rut_ranges',
        'campaign_patterns',
        'is_active',
        'description'
    ];

    protected $casts = [
        'rut_ranges' => 'array',
        'campaign_patterns' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Relación con usuario asignado
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Scope para portfolios activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Verificar si un RUT está en los rangos de este portfolio
     */
    public function containsRut(string $rut): bool
    {
        if (!$this->is_active || !$this->rut_ranges) {
            return false;
        }

        $rutNumber = (int) preg_replace('/[^0-9]/', '', $rut);

        foreach ($this->rut_ranges as $range) {
            if (str_contains($range, '-')) {
                [$min, $max] = explode('-', $range);
                if ($rutNumber >= (int) $min && $rutNumber <= (int) $max) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verificar si un código de campaña coincide con los patrones
     */
    public function matchesCampaignCode(string $code): bool
    {
        if (!$this->is_active || !$this->campaign_patterns) {
            return false;
        }

        foreach ($this->campaign_patterns as $pattern) {
            // Convertir patrón con * a regex
            $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/i';
            if (preg_match($regex, $code)) {
                return true;
            }
        }

        return false;
    }
}
