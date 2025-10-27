<?php

namespace App\Services\Email;

use App\Services\Base\GoogleApiService;
use Google\Service\Gmail;
use App\Models\ImportedEmail;
use App\Models\GmailGroup;
use Illuminate\Support\Facades\Log;

class EmailImportService extends GoogleApiService
{
    protected array $requiredScopes = [
        Gmail::GMAIL_READONLY,
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
     * Importar correos desde grupos de Gmail
     */
    public function importEmails(array $options = []): array
    {
        $this->ensureGmailService();
        
        $results = [
            'total_processed' => 0,
            'total_imported' => 0,
            'total_skipped' => 0,
            'total_errors' => 0,
            'by_group' => []
        ];

        // Obtener grupos a procesar
        $groups = $this->getGroupsToProcess($options);

        foreach ($groups as $group) {
            try {
                $this->impersonateEmail = $group->email;
                $this->setupClient();
                $this->authenticateClient();

                $groupResults = $this->importGroupEmails($group, $options);
                
                $results['total_processed'] += $groupResults['processed'];
                $results['total_imported'] += $groupResults['imported'];
                $results['total_skipped'] += $groupResults['skipped'];
                $results['total_errors'] += $groupResults['errors'];
                
                $results['by_group'][] = [
                    'group' => $group->name,
                    'processed' => $groupResults['processed'],
                    'imported' => $groupResults['imported'],
                    'skipped' => $groupResults['skipped'],
                    'errors' => $groupResults['errors']
                ];

                Log::info('Grupo procesado', [
                    'group' => $group->name,
                    'results' => $groupResults
                ]);

            } catch (\Exception $e) {
                $results['total_errors']++;
                Log::error('Error procesando grupo', [
                    'group' => $group->name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Importar correos de un grupo específico
     */
    private function importGroupEmails(GmailGroup $group, array $options = []): array
    {
        $results = ['processed' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => 0];
        
        try {
            // Construir query de búsqueda
            $query = $this->buildSearchQuery($options);
            
            // Buscar mensajes
            $messages = $this->searchMessages($query, $options['limit'] ?? 100);
            
            foreach ($messages as $message) {
                try {
                    $results['processed']++;
                    
                    // Verificar si ya existe
                    if ($this->emailExists($message->getId())) {
                        $results['skipped']++;
                        continue;
                    }
                    
                    // Obtener detalles del mensaje
                    $messageDetails = $this->getMessageDetails($message->getId());
                    
                    if ($options['dry_run'] ?? false) {
                        Log::info('DRY RUN: Importaría email', [
                            'message_id' => $message->getId(),
                            'subject' => $messageDetails['subject'] ?? 'Sin asunto'
                        ]);
                        $results['imported']++;
                        continue;
                    }
                    
                    // Crear registro en BD
                    $this->createImportedEmail($messageDetails, $group);
                    $results['imported']++;
                    
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error('Error importando mensaje', [
                        'message_id' => $message->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error importando grupo', [
                'group' => $group->name,
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
     * Construir query de búsqueda
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
     * Verificar si el email ya existe
     */
    private function emailExists(string $gmailMessageId): bool
    {
        return ImportedEmail::where('gmail_message_id', $gmailMessageId)->exists();
    }

    /**
     * Obtener grupos a procesar
     */
    private function getGroupsToProcess(array $options): \Illuminate\Database\Eloquent\Collection
    {
        $query = GmailGroup::where('is_active', true)
                          ->where('import_enabled', true);
        
        if (!empty($options['groups'])) {
            $query->whereIn('email', $options['groups']);
        }
        
        return $query->get();
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