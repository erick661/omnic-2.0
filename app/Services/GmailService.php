<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ImportedEmail;
use App\Models\GmailGroup;

// Función auxiliar para base64url encoding
if (!function_exists('base64url_encode')) {
    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

class GmailService
{
    private Client $client;
    private Gmail $gmailService;

    public function __construct()
    {
        $this->client = new Client();
        $this->setupClient();
        $this->gmailService = new Gmail($this->client);
    }

    /**
     * Configurar el cliente de Google con Service Account
     */
    private function setupClient(): void
    {
        $this->client->setApplicationName('Omnic Email System');
        $this->client->setScopes([
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_MODIFY,
            Gmail::GMAIL_SEND
        ]);
        
        // Configurar Service Account
        $this->setupServiceAccountAuth();
    }

    /**
     * Configurar autenticación con Service Account
     */
    private function setupServiceAccountAuth(): void
    {
        try {
            // Intentar usar Application Default Credentials (recomendado)
            $keyFile = getenv('GOOGLE_APPLICATION_CREDENTIALS');
            if ($keyFile && file_exists($keyFile)) {
                $this->client->useApplicationDefaultCredentials();
                $this->client->setSubject('admin@orproverificaciones.cl'); // Email de impersonación  
                Log::info('✅ GmailService usando Application Default Credentials');
                return;
            }

            // Fallback: usar service account key path del config
            $keyPath = config('services.google.service_account_key');
            if ($keyPath && file_exists($keyPath)) {
                $this->client->setAuthConfig($keyPath);
                $this->client->setSubject('admin@orproverificaciones.cl');
                Log::info('✅ GmailService usando Service Account Key');
                return;
            }

            throw new \Exception('No se encontró configuración de Service Account válida');

        } catch (\Exception $e) {
            Log::error('❌ Error configurando Service Account: ' . $e->getMessage());
            throw new \Exception('No se pudo configurar autenticación Service Account: ' . $e->getMessage());
        }
    }


    /**
     * Verificar si está autenticado (Service Account)
     */
    public function isAuthenticated(): bool
    {
        try {
            // Con Service Account, verificar haciendo una petición simple
            $profile = $this->gmailService->users->getProfile('me');
            Log::info('✅ Service Account autenticado como: ' . $profile->getEmailAddress());
            return !empty($profile->getEmailAddress());
            
        } catch (\Exception $e) {
            Log::error('❌ Error verificando autenticación Service Account: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Importar correos nuevos de todas las etiquetas configuradas
     */
    public function importNewEmails(): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Gmail no está autenticado');
        }

        $results = [];
        $groups = GmailGroup::active()->get();

        foreach ($groups as $group) {
            try {
                $imported = $this->importEmailsForGroup($group);
                $results[] = [
                    'group' => $group->name,
                    'imported' => $imported,
                    'status' => 'success'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'group' => $group->name,
                    'imported' => 0,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                Log::error("Error importando grupo {$group->name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Importar correos para un grupo específico
     */
    private function importEmailsForGroup(GmailGroup $group): int
    {
        $query = $this->buildSearchQuery($group);
        $messages = $this->searchMessages($query);
        $imported = 0;

        foreach ($messages as $messageId) {
            if (!$this->emailAlreadyImported($messageId)) {
                $emailData = $this->getEmailData($messageId);
                if ($emailData && $this->saveImportedEmail($emailData, $group)) {
                    $imported++;
                }
            }
        }

        return $imported;
    }

    /**
     * Construir query de búsqueda para Gmail
     */
    private function buildSearchQuery(GmailGroup $group): string
    {
        $query = [];
        
        // Solo correos no leídos de los últimos 7 días
        $query[] = 'is:unread';
        $query[] = 'newer_than:7d';
        
        // Filtrar por etiqueta si está configurada
        if ($group->gmail_label) {
            $query[] = "label:{$group->gmail_label}";
        } else {
            // Si no hay etiqueta, buscar por email destino
            $query[] = "to:{$group->email}";
        }

        return implode(' ', $query);
    }

    /**
     * Buscar mensajes en Gmail
     */
    private function searchMessages(string $query): array
    {
        try {
            $response = $this->gmailService->users_messages->listUsersMessages('me', [
                'q' => $query,
                'maxResults' => 50
            ]);

            return array_column($response->getMessages() ?? [], 'id');
        } catch (\Exception $e) {
            Log::error('Error buscando mensajes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si el correo ya fue importado
     */
    private function emailAlreadyImported(string $messageId): bool
    {
        return ImportedEmail::where('gmail_message_id', $messageId)->exists();
    }

    /**
     * Obtener datos completos del correo
     */
    private function getEmailData(string $messageId): ?array
    {
        try {
            $message = $this->gmailService->users_messages->get('me', $messageId, [
                'format' => 'full'
            ]);

            $payload = $message->getPayload();
            $headers = $payload->getHeaders();

            // Extraer headers importantes
            $headerData = [];
            foreach ($headers as $header) {
                $headerData[strtolower($header->getName())] = $header->getValue();
            }

            // Obtener contenido del correo
            $bodyData = $this->extractEmailBody($payload);

            return [
                'message_id' => $messageId,
                'thread_id' => $message->getThreadId(),
                'subject' => $headerData['subject'] ?? 'Sin asunto',
                'from_email' => $this->extractEmail($headerData['from'] ?? ''),
                'from_name' => $this->extractName($headerData['from'] ?? ''),
                'to_email' => $this->extractEmail($headerData['to'] ?? ''),
                'cc_emails' => $this->extractMultipleEmails($headerData['cc'] ?? ''),
                'bcc_emails' => $this->extractMultipleEmails($headerData['bcc'] ?? ''),
                'body_html' => $bodyData['html'],
                'body_text' => $bodyData['text'],
                'received_at' => $this->parseDate($headerData['date'] ?? ''),
                'has_attachments' => $this->hasAttachments($payload),
                'raw_headers' => $headerData
            ];
        } catch (\Exception $e) {
            Log::error("Error obteniendo datos del correo {$messageId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extraer contenido del cuerpo del correo
     */
    private function extractEmailBody($payload): array
    {
        $html = '';
        $text = '';

        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                $mimeType = $part->getMimeType();
                
                if ($mimeType === 'text/html' && $part->getBody()->getData()) {
                    $html = base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                } elseif ($mimeType === 'text/plain' && $part->getBody()->getData()) {
                    $text = base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                }
            }
        } elseif ($payload->getBody()->getData()) {
            $content = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload->getBody()->getData()));
            if ($payload->getMimeType() === 'text/html') {
                $html = $content;
            } else {
                $text = $content;
            }
        }

        return ['html' => $html, 'text' => $text];
    }

    /**
     * Verificar si tiene adjuntos
     */
    private function hasAttachments($payload): bool
    {
        if (!$payload->getParts()) {
            return false;
        }

        foreach ($payload->getParts() as $part) {
            if ($part->getFilename() && strlen($part->getFilename()) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extraer email de una cadena "Nombre <email@domain.com>"
     */
    private function extractEmail(string $from): string
    {
        if (preg_match('/<(.+?)>/', $from, $matches)) {
            return $matches[1];
        }
        return trim($from);
    }

    /**
     * Extraer nombre de una cadena "Nombre <email@domain.com>"
     */
    private function extractName(string $from): ?string
    {
        if (preg_match('/^(.+?)\s*<.+?>$/', $from, $matches)) {
            return trim($matches[1], '"\'');
        }
        return null;
    }

    /**
     * Extraer múltiples emails de una cadena separada por comas
     */
    private function extractMultipleEmails(string $emails): ?array
    {
        if (empty($emails)) {
            return null;
        }

        $emailList = [];
        $parts = explode(',', $emails);
        
        foreach ($parts as $part) {
            $email = $this->extractEmail(trim($part));
            if ($email) {
                $emailList[] = $email;
            }
        }

        return empty($emailList) ? null : $emailList;
    }

    /**
     * Parsear fecha del header
     */
    private function parseDate(string $date): \DateTime
    {
        try {
            return new \DateTime($date);
        } catch (\Exception $e) {
            return new \DateTime(); // Fecha actual si no se puede parsear
        }
    }

    /**
     * Guardar correo importado en la base de datos
     */
    private function saveImportedEmail(array $emailData, GmailGroup $group): bool
    {
        try {
            $importedEmail = new ImportedEmail([
                'gmail_message_id' => $emailData['message_id'],
                'gmail_thread_id' => $emailData['thread_id'],
                'gmail_group_id' => $group->id,
                'subject' => $emailData['subject'],
                'from_email' => $emailData['from_email'],
                'from_name' => $emailData['from_name'],
                'to_email' => $emailData['to_email'],
                'cc_emails' => $emailData['cc_emails'],
                'bcc_emails' => $emailData['bcc_emails'],
                'body_html' => $emailData['body_html'],
                'body_text' => $emailData['body_text'],
                'received_at' => $emailData['received_at'],
                'has_attachments' => $emailData['has_attachments'],
                'case_status' => 'pending'
            ]);

            // Intentar auto-asignación por código de referencia
            $this->tryAutoAssignment($importedEmail);

            $importedEmail->save();
            return true;
        } catch (\Exception $e) {
            Log::error('Error guardando correo importado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Intentar auto-asignación basada en código de referencia
     */
    private function tryAutoAssignment(ImportedEmail $email): void
    {
        // Buscar código de referencia en el asunto
        $referenceCode = \App\Models\ReferenceCode::findBySubject($email->subject);
        
        if ($referenceCode) {
            $email->reference_code_id = $referenceCode->id;
            $email->assigned_to = $referenceCode->assigned_user_id;
            $email->assigned_at = now();
            $email->case_status = 'assigned';
            $email->rut_empleador = $referenceCode->rut_empleador;
            $email->dv_empleador = $referenceCode->dv_empleador;
        }
    }

    /**
     * Marcar correo como leído en Gmail
     */
    public function markAsRead(string $messageId): bool
    {
        try {
            $this->gmailService->users_messages->modify('me', $messageId, [
                'removeLabelIds' => ['UNREAD']
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error marcando correo como leído: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar correo a través de Gmail API
     */
    public function sendEmail(array $emailData): array
    {
        try {
            if (!$this->isAuthenticated()) {
                throw new \Exception('Gmail no está autenticado');
            }

            // Construir el mensaje RFC 2822
            $rawMessage = $this->buildRawMessage($emailData);
            
            // Crear objeto Message de Gmail
            $message = new Message();
            $message->setRaw(base64url_encode($rawMessage));

            // Si es respuesta, establecer threadId
            if (!empty($emailData['thread_id'])) {
                $message->setThreadId($emailData['thread_id']);
            }

            // Enviar el mensaje
            $sentMessage = $this->gmailService->users_messages->send('me', $message);

            Log::info('Email enviado exitosamente', [
                'message_id' => $sentMessage->getId(),
                'thread_id' => $sentMessage->getThreadId(),
                'to' => $emailData['to'],
                'subject' => $emailData['subject']
            ]);

            return [
                'success' => true,
                'message_id' => $sentMessage->getId(),
                'thread_id' => $sentMessage->getThreadId(),
                'sent_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Error enviando email: ' . $e->getMessage(), [
                'email_data' => $emailData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Construir mensaje RFC 2822 para Gmail API
     */
    private function buildRawMessage(array $emailData): string
    {
        $headers = [];
        
        // Headers básicos
        $headers[] = "From: {$emailData['from_name']} <{$emailData['from_email']}>";
        $headers[] = "To: {$emailData['to']}";
        
        if (!empty($emailData['cc'])) {
            $headers[] = "Cc: {$emailData['cc']}";
        }
        
        if (!empty($emailData['bcc'])) {
            $headers[] = "Bcc: {$emailData['bcc']}";
        }
        
        $headers[] = "Subject: {$emailData['subject']}";
        $headers[] = "Date: " . now()->format('D, d M Y H:i:s O');
        $headers[] = "Message-ID: <" . uniqid() . "@" . config('app.url') . ">";
        
        // Headers para respuesta
        if (!empty($emailData['in_reply_to'])) {
            $headers[] = "In-Reply-To: {$emailData['in_reply_to']}";
        }
        
        if (!empty($emailData['references'])) {
            $headers[] = "References: {$emailData['references']}";
        }

        // Content headers
        $boundary = "boundary_" . uniqid();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        // Construir cuerpo del mensaje
        $body = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Parte texto plano
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->convertHtmlToText($emailData['body']) . "\r\n\r\n";
        
        // Parte HTML
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->wrapHtmlContent($emailData['body']) . "\r\n\r\n";
        
        $body .= "--{$boundary}--\r\n";

        return $body;
    }

    /**
     * Convertir HTML a texto plano
     */
    private function convertHtmlToText(string $html): string
    {
        // Convertir saltos de línea HTML
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        
        // Convertir párrafos
        $text = str_replace(['<p>', '</p>'], ["\n", "\n"], $text);
        
        // Remover todas las etiquetas HTML
        $text = strip_tags($text);
        
        // Limpiar espacios en blanco excesivos
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        $text = trim($text);
        
        return $text;
    }

    /**
     * Envolver contenido en HTML básico
     */
    private function wrapHtmlContent(string $content): string
    {
        // Si ya tiene estructura HTML, devolverlo tal como está
        if (strpos($content, '<html') !== false) {
            return $content;
        }

        // Convertir saltos de línea a <br>
        $htmlContent = nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));

        return "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Email</title>
</head>
<body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
    <div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\">
        {$htmlContent}
    </div>
</body>
</html>";
    }

    /**
     * Obtener información del hilo para respuestas
     */
    public function getThreadInfo(string $threadId): ?array
    {
        try {
            $thread = $this->gmailService->users_threads->get('me', $threadId);
            $messages = $thread->getMessages();
            
            if (empty($messages)) {
                return null;
            }

            // Obtener el primer mensaje para extraer References
            $firstMessage = $messages[0];
            $headers = [];
            
            foreach ($firstMessage->getPayload()->getHeaders() as $header) {
                $headers[strtolower($header->getName())] = $header->getValue();
            }

            return [
                'thread_id' => $threadId,
                'message_id' => $firstMessage->getId(),
                'in_reply_to' => $headers['message-id'] ?? null,
                'references' => $headers['references'] ?? ($headers['message-id'] ?? null),
                'original_subject' => $headers['subject'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo info del hilo: " . $e->getMessage());
            return null;
        }
    }
}