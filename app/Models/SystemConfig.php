<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'system_config';
    public $timestamps = false;
    
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected $dates = [
        'updated_at'
    ];

    /**
     * Obtener valor de configuración
     */
    public static function getValue(string $key, $default = null)
    {
        $config = self::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * Establecer valor de configuración
     */
    public static function setValue(string $key, $value, string $description = null): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Obtener valor como JSON decodificado
     */
    public static function getJsonValue(string $key, $default = null)
    {
        $value = self::getValue($key);
        return $value ? json_decode($value, true) : $default;
    }

    /**
     * Establecer valor como JSON
     */
    public static function setJsonValue(string $key, $value, string $description = null): self
    {
        return self::setValue($key, json_encode($value), $description);
    }

    /**
     * Verificar si una configuración existe
     */
    public static function exists(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Eliminar configuración
     */
    public static function remove(string $key): bool
    {
        return (bool) self::where('key', $key)->delete();
    }
}
