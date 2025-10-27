<?php

namespace App\Console\Commands\Chat;

use Illuminate\Console\Command;
use App\Services\Chat\ChatService;

class SendChatMessageCommand extends Command
{
    protected $signature = 'chat:send 
                           {space : Chat space ID or name}
                           {message : Message to send}
                           {--thread= : Thread ID for replies}';

    protected $description = 'Send a message to Google Chat space';

    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        parent::__construct();
        $this->chatService = $chatService;
    }

    public function handle(): int
    {
        $space = $this->argument('space');
        $message = $this->argument('message');
        $threadId = $this->option('thread');

        $this->info("💬 Enviando mensaje a: {$space}");

        try {
            $result = $this->chatService->sendMessage([
                'space' => $space,
                'message' => $message,
                'thread_id' => $threadId,
            ]);

            if ($result['success']) {
                $this->info("✅ Mensaje enviado exitosamente");
                $this->line("  Message ID: {$result['message_id']}");
                if ($result['thread_id']) {
                    $this->line("  Thread ID: {$result['thread_id']}");
                }
            } else {
                $this->error("❌ Error enviando mensaje: " . $result['error']);
                return self::FAILURE;
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}