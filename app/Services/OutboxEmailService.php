<?php

namespace App\Services;

use App\Models\OutboxEmail;
use App\Models\Communication;
use App\Models\CustomerCase;
use App\Models\ImportedEmail;
use App\Services\GmailService;
use App\Services\MockGmailService;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OutboxEmailService
{
    private $gmailService;

    public function __construct()
    {
        // Seleccionar servicio según configuración
        $authSetup = SystemConfig::getValue('gmail_auth_setup');
        
        if ($authSetup === 'test_mode') {
            $this->gmailService = new MockGmailService();
        } else {
            $this->gmailService = new GmailService();
        }
    }

    /**
     * Crear email de respuesta en la bandeja de salida
     */
    public function createReply(array $emailData): OutboxEmail
    {
        return DB::transaction(function () use ($emailData) {
            // Validar datos requeridos
            $this->validateEmailData($emailData);

            // Crear registro en outbox_emails
            $outboxEmail = OutboxEmail::create([
                'imported_email_id' => $emailData['case_id'] ?? null, // ID del correo original
                'from_email' => $emailData['from_email'],
                'from_name' => $emailData['from_name'],
                'to_email' => $emailData['to'],
                'cc_emails' => !empty($emailData['cc']) ? explode(',', $emailData['cc']) : null,
                'bcc_emails' => !empty($emailData['bcc']) ? explode(',', $emailData['bcc']) : null,
                'subject' => $emailData['subject'],
                'body_html' => $this->formatHtmlBody($emailData['message']),
                'body_text' => strip_tags($emailData['message']),
                'priority' => $emailData['priority'] ?? 'normal',
                'send_status' => 'pending',
                'scheduled_at' => $emailData['scheduled_at'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Crear communication record si es necesario
            if (!empty($emailData['case_id'])) {
                $this->createCommunicationRecord($outboxEmail, $emailData['case_id']);
            }

            Log::info('Email creado en bandeja de salida', [
                'outbox_email_id' => $outboxEmail->id,
                'to' => $emailData['to'],
                'subject' => $emailData['subject']
            ]);

            return $outboxEmail;
        });
    }

    /**
     * Procesar bandeja de salida y enviar correos pendientes
     */
    public function processOutbox(): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Obtener correos listos para enviar
        $pendingEmails = OutboxEmail::readyToSend()
            ->orderBy('created_at')
            ->limit(50) // Procesar máximo 50 por vez
            ->get();

        $results['processed'] = $pendingEmails->count();

        foreach ($pendingEmails as $outboxEmail) {
            try {
                $sent = $this->sendOutboxEmail($outboxEmail);
                
                if ($sent) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                }

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'email_id' => $outboxEmail->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Error procesando email de salida', [
                    'outbox_email_id' => $outboxEmail->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Procesamiento de bandeja de salida completado', $results);

        return $results;
    }

    /**
     * Enviar un email específico de la bandeja de salida
     */
    public function sendOutboxEmail(OutboxEmail $outboxEmail): bool
    {
        try {
            // Marcar como procesándose
            $outboxEmail->update(['send_status' => 'sending']);

            // Preparar datos para Gmail API
            $emailData = $this->prepareGmailData($outboxEmail);

            // Enviar a través de Gmail API
            $result = $this->gmailService->sendEmail($emailData);

            if ($result['success']) {
                // Marcar como enviado
                $outboxEmail->markAsSent(
                    $result['message_id'],
                    $result['thread_id'] ?? null
                );

                // Actualizar comunicación si existe
                if ($outboxEmail->communication) {
                    $this->updateCommunicationAfterSend($outboxEmail, $result);
                }

                // Actualizar estado del caso si es respuesta
                if ($outboxEmail->imported_email_id) {
                    $this->updateCaseStatusAfterReply($outboxEmail);
                }

                return true;
            } else {
                // Marcar como fallido
                $outboxEmail->markAsFailed($result['error']);
                return false;
            }

        } catch (\Exception $e) {
            $outboxEmail->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Preparar datos para Gmail API
     */
    private function prepareGmailData(OutboxEmail $outboxEmail): array
    {
        $emailData = [
            'from_email' => $outboxEmail->from_email,
            'from_name' => $outboxEmail->from_name,
            'to' => $outboxEmail->to_email,
            'subject' => $outboxEmail->subject,
            'body' => $outboxEmail->body_html ?: $outboxEmail->body_text,
        ];

        // Agregar CC y BCC si existen
        if (!empty($outboxEmail->cc_emails)) {
            $emailData['cc'] = implode(', ', $outboxEmail->cc_emails);
        }

        if (!empty($outboxEmail->bcc_emails)) {
            $emailData['bcc'] = implode(', ', $outboxEmail->bcc_emails);
        }

        // Si es respuesta a un correo importado, obtener info del hilo
        if ($outboxEmail->importedEmail) {
            $threadInfo = $this->gmailService->getThreadInfo($outboxEmail->importedEmail->gmail_thread_id);
            if ($threadInfo) {
                $emailData['thread_id'] = $threadInfo['thread_id'];
                $emailData['in_reply_to'] = $threadInfo['in_reply_to'];
                $emailData['references'] = $threadInfo['references'];
            }
        }

        return $emailData;
    }

    /**
     * Crear registro de comunicación
     */
    private function createCommunicationRecord(OutboxEmail $outboxEmail, int $caseId): void
    {
        // Buscar si ya existe un caso para este correo
        $case = CustomerCase::find($caseId);
        
        if (!$case) {
            // Si no existe el caso, crearlo basado en el correo importado
            if ($outboxEmail->importedEmail) {
                $case = $this->createCaseFromImportedEmail($outboxEmail->importedEmail);
            }
        }

        if ($case) {
            $communication = Communication::create([
                'case_id' => $case->id,
                'channel_type' => 'email',
                'direction' => 'outbound',
                'subject' => $outboxEmail->subject,
                'content' => $outboxEmail->body_text,
                'html_content' => $outboxEmail->body_html,
                'from_address' => $outboxEmail->from_email,
                'to_address' => $outboxEmail->to_email,
                'status' => 'pending',
                'created_by' => $outboxEmail->created_by,
            ]);

            $outboxEmail->update(['communication_id' => $communication->id]);
        }
    }

    /**
     * Actualizar comunicación después del envío
     */
    private function updateCommunicationAfterSend(OutboxEmail $outboxEmail, array $result): void
    {
        $outboxEmail->communication->update([
            'status' => 'sent',
            'external_id' => $result['message_id'],
            'sent_at' => now(),
        ]);
    }

    /**
     * Actualizar estado del caso después de responder
     */
    private function updateCaseStatusAfterReply(OutboxEmail $outboxEmail): void
    {
        if ($outboxEmail->importedEmail) {
            $importedEmail = $outboxEmail->importedEmail;
            
            // Solo actualizar si no está ya en progreso o resuelto
            if (in_array($importedEmail->case_status, ['pending', 'assigned'])) {
                $importedEmail->update([
                    'case_status' => 'in_progress'
                ]);

                Log::info('Estado del caso actualizado después de respuesta', [
                    'imported_email_id' => $importedEmail->id,
                    'new_status' => 'in_progress'
                ]);
            }
        }
    }

    /**
     * Crear caso desde correo importado
     */
    private function createCaseFromImportedEmail(ImportedEmail $importedEmail): CustomerCase
    {
        return CustomerCase::create([
            'case_number' => CustomerCase::generateCaseNumber(),
            'employer_name' => $importedEmail->from_name,
            'employer_email' => $importedEmail->from_email,
            'employer_rut' => $importedEmail->rut_empleador,
            'employer_dv' => $importedEmail->dv_empleador,
            'status' => $importedEmail->assigned_to ? 'assigned' : 'pending',
            'priority' => $importedEmail->priority ?: 'normal',
            'assigned_to' => $importedEmail->assigned_to,
            'origin_channel' => 'email',
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Validar datos del email
     */
    private function validateEmailData(array $emailData): void
    {
        $required = ['from_email', 'from_name', 'to', 'subject', 'message'];
        
        foreach ($required as $field) {
            if (empty($emailData[$field])) {
                throw new \InvalidArgumentException("Campo requerido faltante: {$field}");
            }
        }

        if (!filter_var($emailData['to'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Email de destino inválido: {$emailData['to']}");
        }
    }

    /**
     * Formatear cuerpo HTML
     */
    private function formatHtmlBody(string $message): string
    {
        // Si ya tiene HTML, devolverlo tal como está
        if (strpos($message, '<') !== false) {
            return $message;
        }

        // Convertir texto plano a HTML
        return nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Reenviar correos fallidos que pueden reintentarse
     */
    public function retryFailedEmails(): array
    {
        $failedEmails = OutboxEmail::failed()
            ->where('retry_count', '<', 3)
            ->get();

        $results = ['retried' => 0, 'errors' => []];

        foreach ($failedEmails as $email) {
            if ($email->canRetry()) {
                try {
                    $email->retry();
                    $results['retried']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'email_id' => $email->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Obtener estadísticas de la bandeja de salida
     */
    public function getOutboxStats(): array
    {
        return [
            'pending' => OutboxEmail::pending()->count(),
            'sent' => OutboxEmail::sent()->whereDate('sent_at', today())->count(),
            'failed' => OutboxEmail::failed()->count(),
            'scheduled' => OutboxEmail::where('send_status', 'pending')
                                   ->where('scheduled_at', '>', now())
                                   ->count(),
        ];
    }
}