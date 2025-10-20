<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReferenceCode extends Model
{
    protected $fillable = [
        'rut_empleador',
        'dv_empleador',
        'producto',
        'code_hash',
        'assigned_user_id',
    ];

    /**
     * Ejecutivo asignado
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Correos que usan este código de referencia
     */
    public function importedEmails(): HasMany
    {
        return $this->hasMany(ImportedEmail::class);
    }

    /**
     * Obtiene el RUT completo con formato
     */
    public function getFormattedRutAttribute(): string
    {
        return number_format($this->rut_empleador, 0, '', '.') . '-' . $this->dv_empleador;
    }

    /**
     * Obtiene solo el RUT sin formato
     */
    public function getRutCompletoAttribute(): string
    {
        return $this->rut_empleador . $this->dv_empleador;
    }

    /**
     * Genera el código de referencia formateado
     */
    public function getFormattedCodeAttribute(): string
    {
        return "[REF-{$this->code_hash}-{$this->producto}]";
    }

    /**
     * Genera un hash único para el código
     */
    public static function generateCodeHash(string $rut, string $dv, string $producto): string
    {
        $data = $rut . $dv . $producto . now()->timestamp;
        return strtoupper(substr(md5($data), 0, 8));
    }

    /**
     * Busca por código de referencia en un asunto de correo
     */
    public static function findBySubject(string $subject): ?self
    {
        // Buscar patrón [REF-XXXXXXXX-PRODUCTO]
        if (preg_match('/\[REF-([A-Z0-9]{8})-([A-Z\-]+)\]/', $subject, $matches)) {
            $codeHash = $matches[1];
            return self::where('code_hash', $codeHash)->first();
        }
        
        return null;
    }

    /**
     * Scope para buscar por RUT
     */
    public function scopeByRut($query, string $rut, string $dv)
    {
        return $query->where('rut_empleador', $rut)
                    ->where('dv_empleador', $dv);
    }

    /**
     * Scope para buscar por producto
     */
    public function scopeByProducto($query, string $producto)
    {
        return $query->where('producto', $producto);
    }
}
