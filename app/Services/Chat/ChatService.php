<?php

namespace App\Services\Chat;

use App\Services\Base\GoogleApiService;
use Google\Service\HangoutsChat;
use Google\Service\HangoutsChat\Message;
use Illuminate\Support\Facades\Log;

class ChatService extends GoogleApiService
{
    protected array $requiredScopes = [
        HangoutsChat::CHAT_BOT,
    ];

    private ?HangoutsChat $chatService = null;

    public function __construct()
    {
        parent::__construct();
        // Lazy loading: chatService se inicializa cuando sea necesario
    }
    
    /**
     * Inicializar el servicio Chat cuando sea necesario
     */
    protected function ensureChatService(): void
    {
        if ($this->chatService === null) {
            $this->authenticateClient();
            $this->chatService = new HangoutsChat($this->client);
        }
    }

    /**
     * Enviar mensaje a un espacio de chat
     */
    public function sendMessage(array $messageData): array
    {
        $this->ensureChatService();
        
        try {
            $space = $messageData['space'];
            $text = $messageData['message'];
            $threadId = $messageData['thread_id'] ?? null;

            return $this->makeRequest(function () use ($space, $text, $threadId) {
                $message = new Message();
                $message->setText($text);

                $params = [];
                if ($threadId) {
                    $params['threadKey'] = $threadId;
                }

                $result = $this->chatService->spaces_messages->create($space, $message, $params);

                Log::info('Mensaje enviado a Google Chat', [
                    'space' => $space,
                    'message_id' => $result->getName(),
                    'thread_id' => $result->getThread()->getName()
                ]);

                return [
                    'success' => true,
                    'message_id' => $result->getName(),
                    'thread_id' => $result->getThread()->getName(),
                    'create_time' => $result->getCreateTime()
                ];
            });

        } catch (\Exception $e) {
            Log::error('Error enviando mensaje a Google Chat', [
                'space' => $messageData['space'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Listar espacios de chat
     */
    public function listSpaces(): array
    {
        try {
            return $this->makeRequest(function () {
                $response = $this->chatService->spaces->listSpaces();
                
                return array_map(function ($space) {
                    return [
                        'name' => $space->getName(),
                        'display_name' => $space->getDisplayName(),
                        'type' => $space->getType(),
                        'space_type' => $space->getSpaceType()
                    ];
                }, $response->getSpaces() ?? []);
            });

        } catch (\Exception $e) {
            Log::error('Error listando espacios de Chat', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Test de conexión específico
     */
    public function performConnectionTest(): array
    {
        try {
            // Intentar listar espacios para verificar conexión
            $spaces = $this->listSpaces();
            
            return [
                'success' => true,
                'message' => 'Conexión Google Chat exitosa',
                'spaces_count' => count($spaces)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en Google Chat: ' . $e->getMessage()
            ];
        }
    }
}