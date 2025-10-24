# GUÍA: MIGRAR DE OAUTH A SERVICE ACCOUNT

## 🔐 CONFIGURACIÓN DE SERVICE ACCOUNT PARA OMNIC

### PASO 1: Crear Service Account en Google Cloud Console

1. **Ir a Google Cloud Console**
   - URL: https://console.cloud.google.com
   - Proyecto: Seleccionar proyecto actual de Omnic

2. **Crear Service Account**
   ```
   IAM & Admin > Service Accounts > CREATE SERVICE ACCOUNT
   
   Nombre: omnic-email-service
   ID: omnic-email-service@tu-proyecto.iam.gserviceaccount.com  
   Descripción: Service Account para gestión de emails en Omnic
   ```

3. **Generar clave JSON**
   ```
   Actions > Manage Keys > ADD KEY > Create new key > JSON
   Descargar archivo: omnic-service-account.json
   ```

### PASO 2: Habilitar APIs necesarias

```bash
# En Google Cloud Console > APIs & Services > Library, habilitar:
- Gmail API
- Google Workspace Admin SDK
- Cloud Resource Manager API
```

### PASO 3: Configurar Domain-wide Delegation

1. **En Service Account creado:**
   ```
   Actions > Edit > Show Domain-wide Delegation
   ✅ Enable Google Workspace Domain-wide Delegation
   ```

2. **En Google Admin Console** (admin.google.com):
   ```
   Security > Access and data control > API controls > Domain-wide delegation
   
   ADD NEW:
   Client ID: [copiar de service account]
   OAuth Scopes: 
   https://www.googleapis.com/auth/gmail.readonly,
   https://www.googleapis.com/auth/gmail.send,
   https://www.googleapis.com/auth/gmail.modify,
   https://www.googleapis.com/auth/admin.directory.group,
   https://www.googleapis.com/auth/admin.directory.user.readonly
   ```

### PASO 4: Implementar en Laravel

#### 4.1 Crear nuevo Service para Service Account

```php
<?php
// app/Services/GoogleServiceAccountService.php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Log;

class GoogleServiceAccountService
{
    private Client $client;
    private string $serviceAccountPath;
    private string $impersonateEmail;

    public function __construct()
    {
        $this->serviceAccountPath = storage_path('app/google-service-account.json');
        $this->impersonateEmail = 'admin@orproverificaciones.cl'; // Usuario admin
        $this->setupClient();
    }

    private function setupClient(): void
    {
        $this->client = new Client();
        $this->client->setAuthConfig($this->serviceAccountPath);
        $this->client->setScopes([
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_SEND,
            Gmail::GMAIL_MODIFY
        ]);
        $this->client->setSubject($this->impersonateEmail);
    }

    public function getGmailService(string $userEmail = null): Gmail
    {
        if ($userEmail && $userEmail !== $this->impersonateEmail) {
            // Impersonar usuario específico
            $this->client->setSubject($userEmail);
        }
        
        return new Gmail($this->client);
    }

    public function sendEmail(array $emailData, string $asUser = null): array
    {
        $gmail = $this->getGmailService($asUser);
        
        // Construir mensaje RFC 2822
        $rawMessage = $this->buildRawMessage($emailData);
        
        $message = new \Google_Service_Gmail_Message();
        $message->setRaw($rawMessage);
        
        $result = $gmail->users_messages->send('me', $message);
        
        Log::info('Email enviado via Service Account', [
            'message_id' => $result->getId(),
            'as_user' => $asUser ?? $this->impersonateEmail
        ]);
        
        return [
            'success' => true,
            'message_id' => $result->getId(),
            'thread_id' => $result->getThreadId()
        ];
    }

    private function buildRawMessage(array $emailData): string
    {
        $boundary = uniqid(rand(), true);
        
        $headers = [
            'To: ' . $emailData['to'],
            'From: ' . ($emailData['from'] ?? $this->impersonateEmail),
            'Subject: ' . $emailData['subject'],
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary=' . $boundary
        ];
        
        if (isset($emailData['reply_to'])) {
            $headers[] = 'In-Reply-To: ' . $emailData['reply_to'];
            $headers[] = 'References: ' . $emailData['reply_to'];
        }
        
        $message = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Texto plano
        if (isset($emailData['text'])) {
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
            $message .= $emailData['text'] . "\r\n";
        }
        
        // HTML
        if (isset($emailData['html'])) {
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
            $message .= $emailData['html'] . "\r\n";
        }
        
        $message .= "--{$boundary}--";
        
        return base64url_encode($message);
    }
}
```

#### 4.2 Actualizar OutboxEmailService

```php
// En app/Services/OutboxEmailService.php - constructor
public function __construct()
{
    $authMode = config('app.gmail_auth_mode', 'oauth'); // oauth|service_account
    
    if ($authMode === 'service_account') {
        $this->gmailService = new GoogleServiceAccountService();
        Log::info('OutboxEmailService: Usando Service Account');
    } else {
        // Mantener OAuth como fallback
        $this->gmailService = new GmailService();
        Log::info('OutboxEmailService: Usando OAuth');
    }
}
```

#### 4.3 Configurar variables de entorno

```bash
# En .env añadir:
GMAIL_AUTH_MODE=service_account
GOOGLE_SERVICE_ACCOUNT_PATH=/var/www/omnic/storage/app/google-service-account.json
GOOGLE_WORKSPACE_ADMIN_EMAIL=admin@orproverificaciones.cl
```

### PASO 5: Comandos de migración

```php
// Comando para migrar de OAuth a Service Account
php artisan make:command MigrateToServiceAccount

// Comando para probar Service Account  
php artisan make:command TestServiceAccount
```

### VENTAJAS DE LA MIGRACIÓN:

✅ **Eliminación completa de tokens perdidos**
✅ **Sin expiración de credenciales** 
✅ **Acceso a cualquier usuario del workspace**
✅ **Más seguro y robusto**
✅ **Menor mantenimiento**
✅ **Mejor para entornos de producción**

### CONSIDERACIONES:

⚠️ **Permisos amplios**: Service Account tiene acceso completo
⚠️ **Configuración inicial**: Requiere acceso a Google Admin Console
⚠️ **Archivo de credenciales**: Debe protegerse como secreto crítico

### MIGRACIÓN GRADUAL:

1. **Implementar Service Account** (manteniendo OAuth como fallback)
2. **Probar en desarrollo** con usuarios específicos  
3. **Gradualmente migrar** grupos de usuarios
4. **Eventualmente deprecar** OAuth una vez confirmado funcionamiento