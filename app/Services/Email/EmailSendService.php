<?php

namespace App\Services\Email;

use App\Services\Base\GoogleApiService;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;

class EmailSendService extends GoogleApiService
{
    protected array $requiredScopes = [
        Gmail::GMAIL_SEND,
        Gmail::GMAIL_MODIFY,
    ];

    private ?Gmail $gmailService = null;

    public function __construct()
    {
        parent::__construct();
        // Lazy loading: gmailService se inicializa cuando sea necesario
    }
    
    /**
     * Inicializar el servicio Gmail cuando sea necesario
     */
    protected function ensureGmailService(): void
    {
        if ($this->gmailService === null) {
            $this->authenticateClient();
            $this->gmailService = new Gmail($this->client);
        }
    }

    /**
     * Enviar email inmediatamente
     */
    public function sendEmailNow(array $emailData): array
    {
        $this->ensureGmailService();
        
        try {
            // Configurar impersonación si se especifica
            if (!empty($emailData['from_email'])) {
                $this->impersonateEmail = $emailData['from_email'];
                $this->setupClient();
                $this->authenticateClient();
            }

            // Crear mensaje
            $message = $this->createMessage($emailData);

            // Enviar
            $result = $this->makeRequest(function () use ($message) {
                return $this->gmailService->users_messages->send('me', $message);
            });

            Log::info('Email enviado', [
                'to' => $emailData['to'],
                'subject' => $emailData['subject'],
                'message_id' => $result->getId()
            ]);

            return [
                'success' => true,
                'message_id' => $result->getId(),
                'thread_id' => $result->getThreadId(),
            ];

        } catch (\Exception $e) {
            Log::error('Error enviando email', [
                'to' => $emailData['to'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Programar email para envío posterior
     */
    public function scheduleEmail(array $emailData, string $scheduledAt): array
    {
        try {
            // Por ahora, guardar en outbox_emails con scheduled_at
            // En el futuro se puede usar Gmail API schedule send
            
            $outboxService = app(\App\Services\Email\OutboxEmailService::class);
            $emailData['scheduled_at'] = $scheduledAt;
            
            $outboxEmail = $outboxService->createReply($emailData);

            return [
                'success' => true,
                'outbox_email_id' => $outboxEmail->id,
                'scheduled_at' => $scheduledAt
            ];

        } catch (\Exception $e) {
            Log::error('Error programando email', [
                'to' => $emailData['to'] ?? 'unknown',
                'scheduled_at' => $scheduledAt,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear mensaje de Gmail
     */
    private function createMessage(array $emailData): Message
    {
        $headers = [
            'To' => $emailData['to'],
            'Subject' => $emailData['subject'],
        ];

        // Configurar remitente
        $fromEmail = $emailData['from_email'] ?? $this->impersonateEmail;
        $fromName = $emailData['from_name'] ?? '';
        
        if ($fromName) {
            $headers['From'] = "\"{$fromName}\" <{$fromEmail}>";
        } else {
            $headers['From'] = $fromEmail;
        }

        // CC y BCC
        if (!empty($emailData['cc'])) {
            $headers['Cc'] = $emailData['cc'];
        }
        if (!empty($emailData['bcc'])) {
            $headers['Bcc'] = $emailData['bcc'];
        }

        // Headers para respuestas
        if (!empty($emailData['in_reply_to'])) {
            $headers['In-Reply-To'] = $emailData['in_reply_to'];
        }
        if (!empty($emailData['references'])) {
            $headers['References'] = $emailData['references'];
        }

        // Crear cuerpo del mensaje
        $body = $this->createMessageBody($emailData);

        // Combinar headers y cuerpo
        $rawMessage = '';
        foreach ($headers as $key => $value) {
            $rawMessage .= "{$key}: {$value}\r\n";
        }
        $rawMessage .= "\r\n" . $body;

        // Codificar mensaje
        $message = new Message();
        $message->setRaw($this->base64UrlEncode($rawMessage));

        // Si es respuesta, agregar thread ID
        if (!empty($emailData['thread_id'])) {
            $message->setThreadId($emailData['thread_id']);
        }

        return $message;
    }

    /**
     * Crear cuerpo del mensaje
     */
    private function createMessageBody(array $emailData): string
    {
        if (!empty($emailData['template'])) {
            return $this->renderTemplate($emailData['template'], $emailData['template_vars'] ?? []);
        }

        // Determinar tipo de contenido
        $isHtml = strpos($emailData['body'] ?? $emailData['message'], '<') !== false;

        if ($isHtml) {
            return $this->createHtmlBody($emailData['body'] ?? $emailData['message']);
        } else {
            return $this->createTextBody($emailData['body'] ?? $emailData['message']);
        }
    }

    /**
     * Crear cuerpo HTML
     */
    private function createHtmlBody(string $content): string
    {
        $boundary = 'boundary_' . uniqid();
        
        $body = "MIME-Version: 1.0\r\n";
        $body .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
        
        // Versión texto
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= strip_tags($content) . "\r\n\r\n";
        
        // Versión HTML
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $content . "\r\n\r\n";
        
        $body .= "--{$boundary}--";
        
        return $body;
    }

    /**
     * Crear cuerpo de texto plano
     */
    private function createTextBody(string $content): string
    {
        return "MIME-Version: 1.0\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n\r\n" .
               $content;
    }

    /**
     * Renderizar template
     */
    private function renderTemplate(string $template, array $vars): string
    {
        // Aquí se puede integrar con Blade u otro motor de templates
        return view("emails.{$template}", $vars)->render();
    }

    /**
     * Codificar en base64url
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Test de conexión específico
     */
    public function performConnectionTest(): array
    {
        try {
            $profile = $this->gmailService->users->getProfile('me');
            
            // Intentar obtener lista de etiquetas para verificar permisos
            $labels = $this->gmailService->users_labels->listUsersLabels('me');
            
            return [
                'success' => true,
                'message' => 'Conexión Gmail Send exitosa',
                'email_address' => $profile->getEmailAddress(),
                'labels_count' => count($labels->getLabels())
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en Gmail Send: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener información de hilo para respuestas
     */
    public function getThreadInfo(string $threadId): ?array
    {
        try {
            return $this->makeRequest(function () use ($threadId) {
                $thread = $this->gmailService->users_threads->get('me', $threadId);
                $messages = $thread->getMessages();
                
                if (empty($messages)) {
                    return null;
                }
                
                $lastMessage = end($messages);
                $headers = [];
                
                foreach ($lastMessage->getPayload()->getHeaders() as $header) {
                    $headers[$header->getName()] = $header->getValue();
                }
                
                return [
                    'thread_id' => $threadId,
                    'in_reply_to' => $headers['Message-ID'] ?? null,
                    'references' => $headers['References'] ?? null,
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo info de hilo', [
                'thread_id' => $threadId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}