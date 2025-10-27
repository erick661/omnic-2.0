<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ✅ SOLID - Single Responsibility: Solo maneja reglas de asignación configurables
 */
class AssignmentRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_type',
        'pattern_name', 
        'regex_pattern',
        'priority_order',
        'is_active',
        'description',
        'config'
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean'
    ];

    // Constantes para tipos de reglas
    public const TYPE_MASS_CAMPAIGN = 'mass_campaign';
    public const TYPE_CASE_CODE = 'case_code';
    public const TYPE_GMAIL_GROUP = 'gmail_group';
    public const TYPE_RUT_PATTERN = 'rut_pattern';

    /**
     * Scope para reglas activas ordenadas por prioridad
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->orderBy('priority_order');
    }

    /**
     * Scope por tipo de regla
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Verificar si el texto coincide con el patrón de la regla
     */
    public function matches(string $text): bool
    {
        if (!$this->is_active || !$this->regex_pattern) {
            return false;
        }

        return (bool) preg_match($this->regex_pattern, $text);
    }

    /**
     * Extraer valores del texto usando el patrón
     */
    public function extractValues(string $text): ?array
    {
        if (!$this->matches($text)) {
            return null;
        }

        preg_match($this->regex_pattern, $text, $matches);
        return $matches ?? null;
    }
}