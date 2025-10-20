<?php

namespace App\Services;

use App\Models\ImportedEmail;
use App\Models\GmailGroup;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Log;

class MockGmailService
{
    /**
     * Simular importación de correos para desarrollo
     */
    public function importNewEmails(): array
    {
        Log::info('🧪 MockGmailService: Simulando importación de correos');
        
        $results = [];
        $groups = GmailGroup::active()->take(2)->get();
        
        foreach ($groups as $group) {
            $imported = $this->createMockEmails($group);
            $results[] = [
                'group' => $group->name,
                'imported' => $imported,
                'status' => 'success'
            ];
        }
        
        return $results;
    }
    
    /**
     * Crear correos de prueba
     */
    private function createMockEmails(GmailGroup $group): int
    {
        $mockEmails = [
            [
                'subject' => 'Consulta sobre AFP Capital - Empresa Demo SA',
                'from_email' => 'rrhh@empresademo.cl',
                'from_name' => 'Recursos Humanos Demo',
                'body_text' => 'Estimados, necesitamos información sobre el estado de cotizaciones...',
            ],
            [
                'subject' => 'RE: Cotizaciones pendientes [REF-ABC12345-AFP-CAPITAL]',
                'from_email' => 'finanzas@cliente123.cl', 
                'from_name' => 'Finanzas Cliente 123',
                'body_text' => 'Gracias por la respuesta anterior, tengo una consulta adicional...',
            ],
            [
                'subject' => 'Urgente: Problema con descuentos AFP Habitat',
                'from_email' => 'administracion@empresa456.cl',
                'from_name' => 'Administración Empresa 456',
                'body_text' => 'Hemos detectado un problema con los descuentos del mes pasado...',
            ]
        ];
        
        $imported = 0;
        
        foreach ($mockEmails as $index => $emailData) {
            // Solo crear si no existe ya un correo similar reciente
            $exists = ImportedEmail::where('subject', $emailData['subject'])
                                  ->where('created_at', '>', now()->subHour())
                                  ->exists();
            
            if (!$exists) {
                ImportedEmail::create([
                    'gmail_message_id' => 'mock_msg_' . $group->id . '_' . time() . '_' . $index,
                    'gmail_thread_id' => 'mock_thread_' . $group->id . '_' . time() . '_' . $index,
                    'gmail_group_id' => $group->id,
                    'subject' => $emailData['subject'],
                    'from_email' => $emailData['from_email'],
                    'from_name' => $emailData['from_name'],
                    'to_email' => $group->email,
                    'body_text' => $emailData['body_text'],
                    'body_html' => '<p>' . $emailData['body_text'] . '</p>',
                    'received_at' => now()->subMinutes(rand(10, 120)),
                    'imported_at' => now(),
                    'has_attachments' => rand(0, 1) === 1,
                    'priority' => ['normal', 'high'][rand(0, 1)],
                    'case_status' => 'pending'
                ]);
                
                $imported++;
            }
        }
        
        return $imported;
    }
    
    /**
     * Verificar si está "autenticado" (modo mock)
     */
    public function isAuthenticated(): bool
    {
        return SystemConfig::getValue('gmail_auth_setup') === 'test_mode';
    }
    
    /**
     * Simular marcar como leído
     */
    public function markAsRead(string $messageId): bool
    {
        Log::info("🧪 MockGmailService: Marcando como leído: {$messageId}");
        return true;
    }
}