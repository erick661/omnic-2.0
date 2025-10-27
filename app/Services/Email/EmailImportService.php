<?php

namespace App\Services\Email;

use App\Services\Base\GoogleApiService;
use App\Services\Event\EventStore;
use Google\Service\Gmail;
use App\Models\Email;
use App\Models\GmailGroup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmailImportService extends GoogleApiService
{
    protected array $requiredScopes = [
        Gmail::GMAIL_READONLY,
        Gmail::GMAIL_MODIFY,
    ];

    private ?Gmail $gmailService = null;
    private EventStore $eventStore;

    public function __construct(EventStore $eventStore)
    {
        parent::__construct();
        $this->eventStore = $eventStore;
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
     * Configurar cuenta principal para importación
     * Usa comunicaciones@orpro.cl como cuenta principal
     */
    private function setupPrimaryAccount(): void
    {
        // La cuenta principal debe estar configurada en system_config o usar default
        $primaryAccount = config('mail.gmail.primary_account', 'comunicaciones@orpro.cl');
        
        $this->impersonateEmail = $primaryAccount;
        $this->setupClient();
        $this->authenticateClient();
        
        Log::info("Configurada cuenta principal para importación", [
            'account' => $primaryAccount
        ]);
    }

    /**
     * Importar correos desde grupos de Gmail - Arquitectura Event-First
     */
    public function importEmails(array $options = []): array
    {
        // Record import started event
        $correlationId = uniqid('import_');
        $this->eventStore->gmailImportStarted([
            'correlation_id' => $correlationId,
            'options' => $options,
            'started_at' => now()->toISOString()
        ]);

        try {
            $this->ensureGmailService();
            
            $results = [
                'total_processed' => 0,
                'total_imported' => 0,
                'total_skipped' => 0,
                'total_errors' => 0,
                'by_group' => [],
                'correlation_id' => $correlationId
            ];

            // Obtener grupos a procesar (nueva arquitectura)
            $groups = $this->getGroupsToProcess($options);

            if (empty($groups)) {
                Log::warning('No hay grupos habilitados para importar');
                return $results;
            }

            // Configurar impersonation con cuenta principal
            $this->setupPrimaryAccount();

            foreach ($groups as $group) {
                try {
                    Log::info("Procesando grupo: {$group->group_name}", [
                        'group_email' => $group->group_email,
                        'assigned_user' => $group->assigned_user_id
                    ]);

                    $groupResults = $this->importGroupEmails($group, $options, $correlationId);
                    
                    $results['total_processed'] += $groupResults['processed'];
                    $results['total_imported'] += $groupResults['imported'];
                    $results['total_skipped'] += $groupResults['skipped'];
                    $results['total_errors'] += $groupResults['errors'];
                    
                    $results['by_group'][] = [
                        'group' => $group->group_name,
                        'group_email' => $group->group_email,
                        'processed' => $groupResults['processed'],
                        'imported' => $groupResults['imported'],
                        'skipped' => $groupResults['skipped'],
                        'errors' => $groupResults['errors']
                    ];

                } catch (\Exception $e) {
                    $results['total_errors']++;
                    
                    $this->eventStore->recordError(
                        'gmail_import', 
                        "Error procesando grupo {$group->group_name}: " . $e->getMessage(),
                        ['group_id' => $group->id, 'correlation_id' => $correlationId]
                    );

                    Log::error('Error procesando grupo', [
                        'group' => $group->group_name,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId
                    ]);
                }
            }

            // Record successful completion
            $this->eventStore->gmailImportCompleted($results);

            return $results;

        } catch (\Exception $e) {
            $this->eventStore->gmailImportFailed($e->getMessage(), [
                'correlation_id' => $correlationId,
                'options' => $options
            ]);
            throw $e;
        }
    }

    /**
     * Importar correos de un grupo específico - Event-First Architecture
     */
    private function importGroupEmails(GmailGroup $group, array $options = [], string $correlationId = null): array
    {
        $results = ['processed' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => 0];
        
        try {
            // Construir query de búsqueda específica para este grupo
            $query = $this->buildSearchQueryForGroup($group, $options);
            
            // Buscar mensajes dirigidos a este grupo
            $messages = $this->searchMessages($query, $options['limit'] ?? 100);
            
            Log::info("Mensajes encontrados para grupo", [
                'group' => $group->group_name,
                'count' => count($messages),
                'query' => $query
            ]);

            foreach ($messages as $message) {
                try {
                    $results['processed']++;
                    
                    // Verificar si ya existe (nueva arquitectura)
                    if ($this->emailExistsInNewArchitecture($message->getId())) {
                        $results['skipped']++;
                        Log::debug("Email ya existe, omitiendo", [
                            'gmail_message_id' => $message->getId()
                        ]);
                        continue;
                    }
                    
                    // Obtener detalles completos del mensaje
                    $messageDetails = $this->getMessageDetails($message->getId());
                    
                    if ($options['dry_run'] ?? false) {
                        Log::info('DRY RUN: Importaría email', [
                            'message_id' => $message->getId(),
                            'subject' => $messageDetails['subject'] ?? 'Sin asunto',
                            'group' => $group->group_name,
                            'to' => $messageDetails['to'] ?? ''
                        ]);
                        $results['imported']++;
                        continue;
                    }
                    
                    // Crear email inmutable + eventos (nueva arquitectura)
                    $email = $this->createEmailWithEvents($messageDetails, $group, $correlationId);
                    
                    if ($email) {
                        $results['imported']++;
                        Log::info("Email importado exitosamente", [
                            'email_id' => $email->id,
                            'gmail_message_id' => $email->gmail_message_id,
                            'group' => $group->group_name
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $results['errors']++;
                    
                    $this->eventStore->recordError(
                        'email_import',
                        "Error importando mensaje {$message->getId()}: " . $e->getMessage(),
                        [
                            'gmail_message_id' => $message->getId(),
                            'group_id' => $group->id,
                            'correlation_id' => $correlationId
                        ]
                    );

                    Log::error('Error importando mensaje', [
                        'message_id' => $message->getId(),
                        'group' => $group->group_name,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error importando grupo', [
                'group' => $group->group_name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Buscar mensajes en Gmail
     */
    private function searchMessages(string $query, int $limit = 100): array
    {
        return $this->makeRequest(function () use ($query, $limit) {
            $response = $this->gmailService->users_messages->listUsersMessages('me', [
                'q' => $query,
                'maxResults' => $limit
            ]);
            
            return $response->getMessages() ?? [];
        });
    }

    /**
     * Obtener detalles de un mensaje
     */
    private function getMessageDetails(string $messageId): array
    {
        return $this->makeRequest(function () use ($messageId) {
            $message = $this->gmailService->users_messages->get('me', $messageId, [
                'format' => 'full'
            ]);
            
            return $this->parseMessageDetails($message);
        });
    }

    /**
     * Parsear detalles del mensaje
     */
    private function parseMessageDetails($message): array
    {
        $headers = [];
        foreach ($message->getPayload()->getHeaders() as $header) {
            $headers[$header->getName()] = $header->getValue();
        }

        return [
            'gmail_message_id' => $message->getId(),
            'gmail_thread_id' => $message->getThreadId(),
            'subject' => $headers['Subject'] ?? 'Sin asunto',
            'from_email' => $this->extractEmail($headers['From'] ?? ''),
            'from_name' => $this->extractName($headers['From'] ?? ''),
            'to_email' => $this->extractEmail($headers['To'] ?? ''),
            'received_at' => $this->parseDate($headers['Date'] ?? ''),
            'body_text' => $this->extractTextBody($message),
            'body_html' => $this->extractHtmlBody($message),
            'labels' => $message->getLabelIds(),
            'snippet' => $message->getSnippet(),
        ];
    }

    /**
     * Crear registro de correo importado
     */
    /**
     * Crear ImportedEmail (método legacy)
     */
    private function createImportedEmail(array $messageDetails, GmailGroup $group): ImportedEmail
    {
        return ImportedEmail::create([
            'gmail_message_id' => $messageDetails['gmail_message_id'],
            'gmail_thread_id' => $messageDetails['gmail_thread_id'],
            'gmail_group_id' => $group->id,
            'subject' => $messageDetails['subject'],
            'from_email' => $messageDetails['from_email'],
            'from_name' => $messageDetails['from_name'],
            'to_email' => $messageDetails['to_email'],
            'received_at' => $messageDetails['received_at'],
            'body_text' => $messageDetails['body_text'],
            'body_html' => $messageDetails['body_html'],
            'snippet' => $messageDetails['snippet'],
            'labels' => $messageDetails['labels'],
            'case_status' => 'pending',
            'priority' => $this->determinePriority($messageDetails),
        ]);
    }

    /**
     * Crear Email inmutable + evento email.received (SOLO IMPORTACIÓN - SRP)
     * La asignación y lógica de negocio se maneja en servicios separados
     */
    private function createEmailWithEvents(array $messageDetails, GmailGroup $group, ?string $correlationId = null): ?Email
    {
        return DB::transaction(function () use ($messageDetails, $group, $correlationId) {
            try {
                // 1. Crear email inmutable
                $email = Email::create([
                    'gmail_message_id' => $messageDetails['gmail_message_id'],
                    'gmail_thread_id' => $messageDetails['gmail_thread_id'],
                    'direction' => 'inbound', // Todos los imports son inbound
                    'subject' => $messageDetails['subject'],
                    'from_email' => $messageDetails['from_email'],
                    'from_name' => $messageDetails['from_name'],
                    'to_email' => $messageDetails['to_email'],
                    'to_name' => $messageDetails['to_name'] ?? null,
                    'cc_emails' => $messageDetails['cc_emails'] ?? null,
                    'bcc_emails' => $messageDetails['bcc_emails'] ?? null,
                    'reply_to' => $messageDetails['reply_to'] ?? null,
                    'body_text' => $messageDetails['body_text'],
                    'body_html' => $messageDetails['body_html'],
                    'gmail_internal_date' => $messageDetails['gmail_internal_date'] ?? null,
                    'gmail_headers' => $messageDetails['gmail_headers'] ?? null,
                    'gmail_labels' => $messageDetails['labels'] ?? null,
                    'gmail_size_estimate' => $messageDetails['size_estimate'] ?? null,
                    'gmail_snippet' => $messageDetails['snippet'],
                    'raw_headers' => $messageDetails['raw_headers'] ?? null,
                    'message_references' => $messageDetails['message_references'] ?? null,
                    'in_reply_to' => $messageDetails['in_reply_to'] ?? null,
                    'has_attachments' => $messageDetails['has_attachments'] ?? false,
                    'gmail_group_id' => $group->id,
                    'parent_email_id' => $this->findParentEmailId($messageDetails),
                    'created_at' => now()
                ]);

                // 2. Registrar evento: email.received
                $this->eventStore->emailReceived($email->id, [
                    'gmail_message_id' => $messageDetails['gmail_message_id'],
                    'from_email' => $messageDetails['from_email'],
                    'subject' => $messageDetails['subject'],
                    'gmail_group_id' => $group->id,
                    'group_name' => $group->group_name,
                    'group_email' => $group->group_email,
                    'correlation_id' => $correlationId,
                    'received_at' => $messageDetails['received_at'] ?? now()->toISOString()
                ]);

                // 3. La asignación será manejada por EmailAssignmentService 
                // mediante listener del evento email.received (SOLID - SRP)

                return $email;

            } catch (\Exception $e) {
                Log::error("Error creando email con eventos", [
                    'gmail_message_id' => $messageDetails['gmail_message_id'] ?? 'unknown',
                    'group_id' => $group->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Encontrar email padre para hilos de conversación
     */
    private function findParentEmailId(array $messageDetails): ?int
    {
        // Buscar por In-Reply-To header o Message-ID references
        if (!empty($messageDetails['in_reply_to'])) {
            $parent = Email::where('gmail_message_id', $messageDetails['in_reply_to'])->first();
            if ($parent) {
                return $parent->id;
            }
        }

        // Buscar por thread ID
        if (!empty($messageDetails['gmail_thread_id'])) {
            $parent = Email::where('gmail_thread_id', $messageDetails['gmail_thread_id'])
                          ->where('direction', 'inbound')
                          ->orderBy('created_at')
                          ->first();
            if ($parent && $parent->gmail_message_id !== $messageDetails['gmail_message_id']) {
                return $parent->id;
            }
        }

        return null;
    }

        /**
     * Construir query de búsqueda de Gmail (método legacy)
     */
    private function buildSearchQuery(array $options): string
    {
        $queryParts = [];
        
        // Fecha
        if (isset($options['days'])) {
            $date = now()->subDays($options['days'])->format('Y/m/d');
            $queryParts[] = "after:{$date}";
        }
        
        // Solo correos no leídos si se especifica
        if ($options['unread_only'] ?? false) {
            $queryParts[] = 'is:unread';
        }
        
        // Excluir spam y trash
        $queryParts[] = '-in:spam';
        $queryParts[] = '-in:trash';
        
        return implode(' ', $queryParts);
    }

    /**
     * Construir query específica para un grupo Gmail
     */
    private function buildSearchQueryForGroup(GmailGroup $group, array $options): string
    {
        $queryParts = [];
        
        // CLAVE: Filtrar por emails dirigidos a este grupo específico
        $queryParts[] = "to:{$group->group_email}";
        
        // Fecha
        if (isset($options['days'])) {
            $date = now()->subDays($options['days'])->format('Y/m/d');
            $queryParts[] = "after:{$date}";
        }
        
        // Solo correos no leídos si se especifica
        if ($options['unread_only'] ?? false) {
            $queryParts[] = 'is:unread';
        }
        
        // Excluir spam y trash
        $queryParts[] = '-in:spam';
        $queryParts[] = '-in:trash';
        
        // Si el grupo tiene un label específico, agregarlo
        if ($group->gmail_label) {
            $queryParts[] = "label:{$group->gmail_label}";
        }
        
        $query = implode(' ', $queryParts);
        
        Log::debug("Query construida para grupo", [
            'group' => $group->group_name,
            'group_email' => $group->group_email,
            'query' => $query
        ]);
        
        return $query;
    }

    /**
     * Verificar si el email ya existe (método legacy)
     */
    private function emailExists(string $gmailMessageId): bool
    {
        return ImportedEmail::where('gmail_message_id', $gmailMessageId)->exists();
    }

    /**
     * Verificar si el email ya existe en nueva arquitectura
     */
    private function emailExistsInNewArchitecture(string $gmailMessageId): bool
    {
        return Email::withGmailId($gmailMessageId)->exists();
    }

    /**
     * Obtener grupos a procesar - Nueva arquitectura
     */
    private function getGroupsToProcess(array $options): \Illuminate\Database\Eloquent\Collection
    {
        $query = GmailGroup::active()
                          ->importEnabled()
                          ->with(['assignedUser', 'members']);
        
        // Si se especifican grupos específicos, filtrar por group_email
        if (!empty($options['groups'])) {
            $query->whereIn('group_email', $options['groups']);
        }
        
        $groups = $query->get();
        
        Log::info('Grupos encontrados para importar', [
            'total' => $groups->count(),
            'groups' => $groups->pluck('group_email', 'id')->toArray()
        ]);
        
        return $groups;
    }

    /**
     * Test de conexión específico
     */
    public function performConnectionTest(): array
    {
        try {
            $profile = $this->gmailService->users->getProfile('me');
            
            return [
                'success' => true,
                'message' => 'Conexión Gmail exitosa',
                'email_address' => $profile->getEmailAddress(),
                'messages_total' => $profile->getMessagesTotal()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error conectando con Gmail: ' . $e->getMessage()
            ];
        }
    }

    // Métodos auxiliares para parsing...
    private function extractEmail(string $header): string
    {
        preg_match('/<([^>]+)>/', $header, $matches);
        return $matches[1] ?? trim($header);
    }

    private function extractName(string $header): string
    {
        if (preg_match('/^(.*?)\s*<[^>]+>$/', $header, $matches)) {
            return trim($matches[1], '"');
        }
        return '';
    }

    private function parseDate(string $date): \Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }

    private function extractTextBody($message): ?string
    {
        // Implementar extracción de texto
        return null;
    }

    private function extractHtmlBody($message): ?string
    {
        // Implementar extracción de HTML
        return null;
    }

    private function determinePriority(array $messageDetails): string
    {
        // Lógica para determinar prioridad
        return 'normal';
    }
}