<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\Gmail;
use App\Models\SystemConfig;

class GoogleAuthController extends Controller
{
    /**
     * Redirigir al usuario a Google para autenticación
     */
    public function redirectToGoogle(): RedirectResponse
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->setScopes([
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.modify',
            'https://www.googleapis.com/auth/admin.directory.group',
            'https://www.googleapis.com/auth/admin.directory.group.member',
            'https://www.googleapis.com/auth/admin.directory.user.readonly',
            'https://www.googleapis.com/auth/drive.file'
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        
        $authUrl = $client->createAuthUrl();
        
        Log::info('🔀 Redirigiendo a Google OAuth', [
            'redirect_uri' => config('services.google.redirect_uri'),
            'client_id' => config('services.google.client_id')
        ]);
        
        return redirect()->away($authUrl);
    }

    /**
     * Manejar la respuesta de Google después de la autenticación
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $authCode = $request->get('code');
        $error = $request->get('error');
        
        if ($error) {
            Log::error('❌ Error en OAuth callback', ['error' => $error]);
            return redirect('/')
                ->with('error', 'Error en la autenticación con Google: ' . $error);
        }
        
        if (!$authCode) {
            Log::error('❌ No se recibió código de autorización');
            return redirect('/')
                ->with('error', 'No se recibió el código de autorización de Google');
        }
        
        try {
            $client = new Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect_uri'));
            
            // Intercambiar código por tokens
            Log::info('🔄 Intercambiando código por tokens', [
                'client_id' => config('services.google.client_id'),
                'redirect_uri' => config('services.google.redirect_uri')
            ]);
            
            $tokens = $client->fetchAccessTokenWithAuthCode($authCode);
            
            Log::info('📥 Respuesta de Google:', ['tokens' => $tokens]);
            
            if (isset($tokens['error'])) {
                $errorDescription = $tokens['error_description'] ?? $tokens['error'] ?? 'Error desconocido';
                throw new \Exception('Error al obtener tokens: ' . $errorDescription);
            }
            
            // Guardar refresh token para uso futuro
            if (isset($tokens['refresh_token'])) {
                SystemConfig::setValue('gmail_refresh_token', $tokens['refresh_token']);
                Log::info('✅ Refresh token guardado exitosamente');
            }
            
            // Guardar access token temporal
            SystemConfig::setValue('gmail_access_token', $tokens['access_token']);
            SystemConfig::setValue('gmail_token_expires', now()->addSeconds($tokens['expires_in'])->timestamp);
            
            // Probar la conexión
            $client->setAccessToken($tokens);
            $gmail = new Gmail($client);
            $profile = $gmail->users->getProfile('me');
            
            Log::info('✅ Autenticación OAuth exitosa', [
                'email' => $profile->getEmailAddress(),
                'messages_total' => $profile->getMessagesTotal()
            ]);
            
            return redirect('/')
                ->with('success', '¡Autenticación con Gmail exitosa! Email: ' . $profile->getEmailAddress());
                
        } catch (\Exception $e) {
            Log::error('❌ Error en callback OAuth', [
                'error' => $e->getMessage(),
                'auth_code' => substr($authCode, 0, 20) . '...'
            ]);
            
            return redirect('/')
                ->with('error', 'Error al procesar la autenticación: ' . $e->getMessage());
        }
    }
}