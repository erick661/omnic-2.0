<?php

use App\Services\Base\GoogleApiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

// Crear una implementación concreta para testing
class TestGoogleApiService extends GoogleApiService
{
    protected array $requiredScopes = ['test.scope'];
    
    public function performConnectionTest(): array
    {
        return ['success' => true, 'message' => 'Test connection successful'];
    }
    
    // Hacer accesibles métodos protected para testing
    public function getServiceAccountCredentialsPath(): string
    {
        return parent::getServiceAccountCredentialsPath();
    }
    
    public function getTokenCacheKey(): string
    {
        return parent::getTokenCacheKey();
    }
}

describe('GoogleApiService', function () {
    beforeEach(function () {
        $this->service = new TestGoogleApiService();
        Cache::flush();
    });

    it('initializes with lazy loading pattern', function () {
        $service = new TestGoogleApiService();
        
        // El cliente debe ser null inicialmente (lazy loading)
        $reflection = new ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        
        expect($clientProperty->getValue($service))->toBeNull();
    });

    it('can check for credentials availability', function () {
        // Mock Storage para simular que no existen credenciales
        Storage::shouldReceive('exists')
            ->with('google-credentials/service-account-key.json')
            ->once()
            ->andReturn(false);

        expect($this->service->hasCredentials())->toBeFalse();

        // Mock Storage para simular que sí existen credenciales
        Storage::shouldReceive('exists')
            ->with('google-credentials/service-account-key.json')
            ->once()
            ->andReturn(true);

        expect($this->service->hasCredentials())->toBeTrue();
    });

    it('generates correct credentials path', function () {
        $path = $this->service->getServiceAccountCredentialsPath();
        expect($path)->toBe('google-credentials/service-account-key.json');
    });

    it('generates cache key with service class name', function () {
        $cacheKey = $this->service->getTokenCacheKey();
        expect($cacheKey)->toContain('TestGoogleApiService')
            ->and($cacheKey)->toContain('access_token');
    });

    it('has required properties defined', function () {
        $reflection = new ReflectionClass($this->service);
        
        expect($reflection->hasProperty('client'))->toBeTrue()
            ->and($reflection->hasProperty('cachedTokens'))->toBeTrue()
            ->and($reflection->hasProperty('isAuthenticated'))->toBeTrue()
            ->and($reflection->hasProperty('impersonateEmail'))->toBeTrue()
            ->and($reflection->hasProperty('requiredScopes'))->toBeTrue();
    });

    it('implements abstract method performConnectionTest', function () {
        $result = $this->service->performConnectionTest();
        
        expect($result)->toBeArray()
            ->and($result)->toHaveKey('success')
            ->and($result)->toHaveKey('message');
    });

    it('handles authentication errors gracefully', function () {
        // Mock Storage para que falle la búsqueda de credenciales
        Storage::shouldReceive('exists')->andReturn(false);
        
        expect($this->service->hasCredentials())->toBeFalse();
    });

    it('supports impersonation email configuration', function () {
        $email = 'admin@test.com';
        
        $reflection = new ReflectionClass($this->service);
        $impersonateProperty = $reflection->getProperty('impersonateEmail');
        $impersonateProperty->setAccessible(true);
        $impersonateProperty->setValue($this->service, $email);
        
        expect($impersonateProperty->getValue($this->service))->toBe($email);
    });

    it('defines required scopes correctly', function () {
        $reflection = new ReflectionClass($this->service);
        $scopesProperty = $reflection->getProperty('requiredScopes');
        $scopesProperty->setAccessible(true);
        
        $scopes = $scopesProperty->getValue($this->service);
        expect($scopes)->toBeArray()
            ->and($scopes)->toContain('test.scope');
    });

    it('maintains authentication state', function () {
        $reflection = new ReflectionClass($this->service);
        $authProperty = $reflection->getProperty('isAuthenticated');
        $authProperty->setAccessible(true);
        
        // Inicialmente no autenticado
        expect($authProperty->getValue($this->service))->toBeFalse();
        
        // Cambiar estado
        $authProperty->setValue($this->service, true);
        expect($authProperty->getValue($this->service))->toBeTrue();
    });

    it('has MAX_RETRIES constant defined', function () {
        $reflection = new ReflectionClass($this->service);
        expect($reflection->hasConstant('MAX_RETRIES'))->toBeTrue();
        
        $maxRetries = $reflection->getConstant('MAX_RETRIES');
        expect($maxRetries)->toBe(3);
    });
});