<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class OAuthToken extends Model
{
    use HasFactory;
    
    protected $table = 'oauth_tokens';
    
    protected $fillable = [
        'provider',
        'identifier',
        'access_token',
        'refresh_token',
        'scopes',
        'expires_at',
        'metadata',
        'is_active'
    ];
    
    protected $casts = [
        'scopes' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean'
    ];
    
    /**
     * Obtener token activo para provider e identifier
     */
    public static function getActiveToken(string $provider, ?string $identifier = null): ?self
    {
        $query = static::where('provider', $provider)
                      ->where('is_active', true);
                      
        if ($identifier) {
            $query->where('identifier', $identifier);
        }
        
        return $query->latest()->first();
    }
    
    /**
     * Crear o actualizar token
     */
    public static function storeToken(
        string $provider,
        array $tokenData,
        ?string $identifier = null,
        array $metadata = []
    ): self {
        // Desactivar tokens existentes
        static::where('provider', $provider)
              ->when($identifier, fn($q) => $q->where('identifier', $identifier))
              ->update(['is_active' => false]);
              
        // Crear nuevo token
        return static::create([
            'provider' => $provider,
            'identifier' => $identifier,
            'access_token' => Crypt::encryptString($tokenData['access_token']),
            'refresh_token' => isset($tokenData['refresh_token']) 
                ? Crypt::encryptString($tokenData['refresh_token']) 
                : null,
            'scopes' => isset($tokenData['scope']) 
                ? explode(' ', $tokenData['scope']) 
                : null,
            'expires_at' => isset($tokenData['expires_in']) 
                ? Carbon::now()->addSeconds($tokenData['expires_in'])
                : null,
            'metadata' => $metadata,
            'is_active' => true
        ]);
    }
    
    /**
     * Obtener access token desencriptado
     */
    public function getDecryptedAccessToken(): string
    {
        return Crypt::decryptString($this->access_token);
    }
    
    /**
     * Obtener refresh token desencriptado
     */
    public function getDecryptedRefreshToken(): ?string
    {
        return $this->refresh_token 
            ? Crypt::decryptString($this->refresh_token) 
            : null;
    }
    
    /**
     * Obtener token completo para Google Client
     */
    public function getTokenArray(): array
    {
        $token = [
            'access_token' => $this->getDecryptedAccessToken(),
            'token_type' => 'Bearer',
        ];
        
        if ($this->refresh_token) {
            $token['refresh_token'] = $this->getDecryptedRefreshToken();
        }
        
        if ($this->expires_at) {
            $token['expires_in'] = $this->expires_at->diffInSeconds(now());
            $token['created'] = $this->created_at->timestamp;
        }
        
        if ($this->scopes) {
            $token['scope'] = implode(' ', $this->scopes);
        }
        
        return $token;
    }
    
    /**
     * Verificar si el token está expirado
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false; // Sin fecha de expiración = no expira
        }
        
        return $this->expires_at->isPast();
    }
    
    /**
     * Verificar si el token está próximo a expirar
     */
    public function isExpiringSoon(int $minutes = 30): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return $this->expires_at->diffInMinutes(now()) <= $minutes;
    }
    
    /**
     * Actualizar con nuevo access token después de refresh
     */
    public function updateWithRefreshedToken(array $newTokenData): self
    {
        $this->update([
            'access_token' => Crypt::encryptString($newTokenData['access_token']),
            'expires_at' => isset($newTokenData['expires_in']) 
                ? Carbon::now()->addSeconds($newTokenData['expires_in'])
                : $this->expires_at,
        ]);
        
        return $this->fresh();
    }
    
    /**
     * Scope para tokens activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope para provider específico
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
    
    /**
     * Scope para tokens expirados
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
    }
}