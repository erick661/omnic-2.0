<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;

class TestServiceAccount extends Command
{
    protected $signature = 'service-account:test {--user-email= : Email del usuario a impersonar} {--send-test : Enviar email de prueba}';
    protected $description = 'Probar configuración de Service Account con Domain-wide Delegation';

    public function handle()
    {
        $this->info('🔐 Probando Service Account con Domain-wide Delegation...');
        $this->line('');

        // 1. Verificar configuración
        if (!$this->verifyConfiguration()) {
            return 1;
        }

        // 2. Probar autenticación básica
        if (!$this->testAuthentication()) {
            return 1;
        }

        // 3. Probar Gmail API
        if (!$this->testGmailAPI()) {
            return 1;
        }

        // 4. Probar Drive API
        if (!$this->testDriveAPI()) {
            return 1;
        }

        // 5. Enviar email de prueba si se solicita
        if ($this->option('send-test')) {
            $this->testEmailSending();
        }

        $this->info('');
        $this->info('🎉 ¡Service Account configurado correctamente!');
        $this->info('   ✅ Domain-wide Delegation funcionando');
        $this->info('   ✅ Gmail API operativa');
        $this->info('   ✅ Drive API operativa');
        
        return 0;
    }

    private function verifyConfiguration(): bool
    {
        $this->info('📋 Verificando configuración...');
        
        $serviceAccountPath = config('services.google.service_account_path') ?? env('GOOGLE_SERVICE_ACCOUNT_PATH');
        $adminEmail = config('services.google.admin_email') ?? env('GOOGLE_WORKSPACE_ADMIN_EMAIL');
        $domain = config('services.google.workspace_domain') ?? env('GOOGLE_WORKSPACE_DOMAIN');
        $clientId = env('GOOGLE_SERVICE_ACCOUNT_CLIENT_ID');

        $checks = [
            'Service Account Path' => $serviceAccountPath,
            'Admin Email' => $adminEmail,
            'Workspace Domain' => $domain,
            'Client ID' => $clientId
        ];

        foreach ($checks as $name => $value) {
            if ($value) {
                $this->line("   ✅ {$name}: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value));
            } else {
                $this->error("   ❌ {$name}: No configurado");
                return false;
            }
        }

        // Verificar archivo JSON
        if (!file_exists($serviceAccountPath)) {
            $this->error("   ❌ Archivo Service Account no encontrado: {$serviceAccountPath}");
            return false;
        }

        $this->line("   ✅ Archivo Service Account encontrado");

        // Verificar contenido JSON
        $json = json_decode(file_get_contents($serviceAccountPath), true);
        if (!$json || !isset($json['client_email'])) {
            $this->error("   ❌ Archivo Service Account inválido");
            return false;
        }

        $this->line("   ✅ Service Account Email: {$json['client_email']}");
        
        return true;
    }

    private function testAuthentication(): bool
    {
        $this->info('🔑 Probando autenticación...');

        try {
            $client = new Client();
            $serviceAccountPath = env('GOOGLE_SERVICE_ACCOUNT_PATH');
            $adminEmail = env('GOOGLE_WORKSPACE_ADMIN_EMAIL');

            $client->setAuthConfig($serviceAccountPath);
            $client->setScopes([
                Gmail::GMAIL_READONLY,
                Gmail::GMAIL_SEND,
                Drive::DRIVE_READONLY
            ]);
            $client->setSubject($adminEmail);

            // Obtener token de acceso
            $accessToken = $client->fetchAccessTokenWithAssertion();

            if (isset($accessToken['error'])) {
                $this->error("   ❌ Error de autenticación: {$accessToken['error']}");
                if (isset($accessToken['error_description'])) {
                    $this->error("   📝 Descripción: {$accessToken['error_description']}");
                }
                return false;
            }

            $this->line("   ✅ Token de acceso obtenido");
            $this->line("   ⏰ Expira en: " . ($accessToken['expires_in'] ?? 'N/A') . " segundos");

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Excepción durante autenticación: {$e->getMessage()}");
            
            // Diagnósticos adicionales
            $this->warn("   🔍 Posibles causas:");
            $this->warn("      • Domain-wide Delegation no autorizado en Admin Console");
            $this->warn("      • Client ID incorrecto en Admin Console");
            $this->warn("      • Scopes no coinciden con los autorizados");
            $this->warn("      • Usuario admin no tiene permisos suficientes");
            
            return false;
        }
    }

    private function testGmailAPI(): bool
    {
        $this->info('📧 Probando Gmail API...');

        try {
            $client = $this->createClient();
            $gmail = new Gmail($client);

            $userEmail = $this->option('user-email') ?? env('GOOGLE_WORKSPACE_ADMIN_EMAIL');
            
            // Impersonar usuario específico
            $client->setSubject($userEmail);
            
            // Obtener perfil del usuario
            $profile = $gmail->users->getProfile('me');
            
            $this->line("   ✅ Conectado como: {$profile->getEmailAddress()}");
            $this->line("   📊 Total mensajes: {$profile->getMessagesTotal()}");
            $this->line("   📊 Total hilos: {$profile->getThreadsTotal()}");

            // Probar listar mensajes (primeros 5)
            $messages = $gmail->users_messages->listUsersMessages('me', [
                'maxResults' => 5
            ]);

            if ($messages->getMessages()) {
                $this->line("   ✅ Puede listar mensajes: " . count($messages->getMessages()) . " encontrados");
            } else {
                $this->warn("   ⚠️ No se encontraron mensajes (normal si es cuenta nueva)");
            }

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Error en Gmail API: {$e->getMessage()}");
            
            // Verificar scopes específicos
            $this->warn("   🔍 Verificar scopes en Admin Console:");
            $this->warn("      • https://www.googleapis.com/auth/gmail.readonly");
            $this->warn("      • https://www.googleapis.com/auth/gmail.send");
            
            return false;
        }
    }

    private function testDriveAPI(): bool
    {
        $this->info('💾 Probando Drive API...');

        try {
            $client = $this->createClient();
            $drive = new Drive($client);

            // Impersonar admin
            $client->setSubject(env('GOOGLE_WORKSPACE_ADMIN_EMAIL'));

            // Listar archivos (primeros 5)
            $files = $drive->files->listFiles([
                'pageSize' => 5,
                'fields' => 'files(id,name,mimeType)'
            ]);

            $this->line("   ✅ Conectado a Drive");
            
            if ($files->getFiles()) {
                $this->line("   📁 Archivos encontrados: " . count($files->getFiles()));
                
                foreach ($files->getFiles() as $file) {
                    $this->line("      • {$file->getName()} ({$file->getMimeType()})");
                }
            } else {
                $this->warn("   ⚠️ No se encontraron archivos en Drive");
            }

            // Probar crear carpeta de prueba
            $folderName = 'Test Omnic - ' . date('Y-m-d H:i:s');
            
            $folderMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            $folder = $drive->files->create($folderMetadata);
            $this->line("   ✅ Carpeta de prueba creada: {$folder->getName()}");
            
            // Eliminar carpeta de prueba
            $drive->files->delete($folder->getId());
            $this->line("   ✅ Carpeta de prueba eliminada");

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Error en Drive API: {$e->getMessage()}");
            
            $this->warn("   🔍 Verificar scopes en Admin Console:");
            $this->warn("      • https://www.googleapis.com/auth/drive");
            $this->warn("      • https://www.googleapis.com/auth/drive.file");
            
            return false;
        }
    }

    private function testEmailSending(): void
    {
        $this->info('📤 Probando envío de email...');

        try {
            $client = $this->createClient();
            $gmail = new Gmail($client);

            $userEmail = $this->option('user-email') ?? env('GOOGLE_WORKSPACE_ADMIN_EMAIL');
            $client->setSubject($userEmail);

            // Construir mensaje de prueba
            $to = $this->ask('¿A qué email enviar la prueba?', 'lucas.munoz@orpro.cl');
            
            $subject = 'Prueba Service Account - ' . date('Y-m-d H:i:s');
            $body = "Este es un email de prueba enviado mediante Service Account con Domain-wide Delegation.\n\n";
            $body .= "Configuración:\n";
            $body .= "- Service Account funcionando ✅\n";
            $body .= "- Domain-wide Delegation activo ✅\n";
            $body .= "- Enviado desde: {$userEmail}\n";
            $body .= "- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
            $body .= "Sistema Omnic 2.0";

            $rawMessage = $this->buildEmailMessage($to, $userEmail, $subject, $body);
            
            $message = new \Google\Service\Gmail\Message();
            $message->setRaw($rawMessage);

            $result = $gmail->users_messages->send('me', $message);

            $this->line("   ✅ Email enviado exitosamente");
            $this->line("   📧 Message ID: {$result->getId()}");
            $this->line("   📧 Thread ID: {$result->getThreadId()}");
            $this->line("   📬 Destinatario: {$to}");

        } catch (\Exception $e) {
            $this->error("   ❌ Error enviando email: {$e->getMessage()}");
        }
    }

    private function createClient(): Client
    {
        $client = new Client();
        $client->setAuthConfig(env('GOOGLE_SERVICE_ACCOUNT_PATH'));
        $client->setScopes([
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_SEND,
            Gmail::GMAIL_MODIFY,
            Drive::DRIVE,
            Drive::DRIVE_FILE,
            'https://www.googleapis.com/auth/admin.directory.group',
            'https://www.googleapis.com/auth/admin.directory.user.readonly'
        ]);
        
        return $client;
    }

    private function buildEmailMessage(string $to, string $from, string $subject, string $body): string
    {
        $headers = [
            'To: ' . $to,
            'From: ' . $from,
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=utf-8',
            'Content-Transfer-Encoding: base64'
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= base64_encode($body);

        return base64_encode($message);
    }
}