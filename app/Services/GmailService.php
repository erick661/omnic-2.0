<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\SystemConfig;
use App\Models\ImportedEmail;
use App\Models\GmailGroup;

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
     * Configurar el cliente de Google
     */
    private function setupClient(): void
    {
        $this->client->setApplicationName('Omnic Email System');
        $this->client->setScopes([
            Gmail::GMAIL_READONLY,
            Gmail::GMAIL_MODIFY
        ]);
        $this->client->setAuthConfig([
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uris' => [config('services.google.redirect_uri')]
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Cargar token de refresh si existe
        $refreshToken = SystemConfig::getValue('gmail_refresh_token');
        if ($refreshToken) {
            $this->client->refreshToken($refreshToken);
        }
    }

    /**
     * Obtener URL de autorización para configuración inicial
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Procesar código de autorización y almacenar refresh token
     */
    public function handleAuthCallback(string $authCode): bool
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($authCode);
            
            if (isset($token['refresh_token'])) {
                SystemConfig::setValue('gmail_refresh_token', $token['refresh_token']);
                SystemConfig::setValue('gmail_access_token', json_encode($token));
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error en callback de Gmail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si está autenticado
     */
    public function isAuthenticated(): bool
    {
        $refreshToken = SystemConfig::getValue('gmail_refresh_token');
        return !empty($refreshToken);
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
}