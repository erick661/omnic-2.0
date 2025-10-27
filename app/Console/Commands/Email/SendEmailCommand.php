<?php

namespace App\Console\Commands\Email;

use Illuminate\Console\Command;
use App\Services\Email\EmailSendService;

class SendEmailCommand extends Command
{
    protected $signature = 'email:send 
                           {to : Recipient email address}
                           {subject : Email subject}
                           {--from= : Sender email (defaults to system default)}
                           {--cc=* : CC recipients}
                           {--bcc=* : BCC recipients}
                           {--body= : Email body text}
                           {--template= : Email template to use}
                           {--vars=* : Template variables in key=value format}
                           {--priority=normal : Email priority (low, normal, high)}
                           {--schedule= : Schedule for later (Y-m-d H:i:s format)}';

    protected $description = 'Send an email directly through the system';

    private EmailSendService $sendService;

    public function __construct(EmailSendService $sendService)
    {
        parent::__construct();
        $this->sendService = $sendService;
    }

    public function handle(): int
    {
        $this->info('📧 Preparando envío de correo...');

        try {
            $emailData = $this->prepareEmailData();
            
            if ($this->option('schedule')) {
                $result = $this->sendService->scheduleEmail($emailData, $this->option('schedule'));
                $this->info("⏰ Correo programado para: {$this->option('schedule')}");
            } else {
                $result = $this->sendService->sendEmailNow($emailData);
                
                if ($result['success']) {
                    $this->info("✅ Correo enviado exitosamente");
                    $this->line("  Message ID: {$result['message_id']}");
                } else {
                    $this->error("❌ Error enviando correo: {$result['error']}");
                    return self::FAILURE;
                }
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function prepareEmailData(): array
    {
        $emailData = [
            'to' => $this->argument('to'),
            'subject' => $this->argument('subject'),
            'priority' => $this->option('priority'),
        ];

        // Configurar remitente
        if ($this->option('from')) {
            $emailData['from_email'] = $this->option('from');
        }

        // CC y BCC
        if ($this->option('cc')) {
            $emailData['cc'] = implode(',', $this->option('cc'));
        }
        if ($this->option('bcc')) {
            $emailData['bcc'] = implode(',', $this->option('bcc'));
        }

        // Contenido del correo
        if ($this->option('template')) {
            $emailData['template'] = $this->option('template');
            $emailData['template_vars'] = $this->parseTemplateVars();
        } else {
            $emailData['message'] = $this->option('body') ?: $this->ask('Ingrese el contenido del correo:');
        }

        return $emailData;
    }

    private function parseTemplateVars(): array
    {
        $vars = [];
        foreach ($this->option('vars') as $var) {
            if (strpos($var, '=') !== false) {
                [$key, $value] = explode('=', $var, 2);
                $vars[trim($key)] = trim($value);
            }
        }
        return $vars;
    }
}