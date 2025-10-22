<?php

namespace App\Console\Commands;

use App\Models\ImportedEmail;
use App\Models\OutboxEmail;
use App\Models\CustomerCase;
use App\Models\Communication;
use App\Models\GmailGroup;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmailSystemStatus extends Command
{
    protected $signature = 'emails:status {--user-id= : Filtrar por ID de usuario específico}';
    protected $description = 'Mostrar estado general del sistema de emails';

    public function handle()
    {
        $userId = $this->option('user-id');
        
        $this->info('📊 ESTADO DEL SISTEMA DE EMAILS');
        $this->info('=' . str_repeat('=', 50));
        
        // 1. Usuarios y grupos
        $this->showUsersAndGroups($userId);
        
        // 2. Emails importados
        $this->showImportedEmails($userId);
        
        // 3. Emails en cola de envío
        $this->showOutboxEmails($userId);
        
        // 4. Casos de clientes
        $this->showCustomerCases($userId);
        
        // 5. Comunicaciones
        $this->showCommunications($userId);
        
        // 6. Estado OAuth
        $this->showOAuthStatus();
        
        return 0;
    }

    private function showUsersAndGroups($userId = null)
    {
        $this->info("\n👥 USUARIOS Y GRUPOS");
        $this->info('-' . str_repeat('-', 30));
        
        $query = User::with('assignedGmailGroups');
        if ($userId) {
            $query->where('id', $userId);
        }
        
        $users = $query->get();
        
        foreach ($users as $user) {
            $groups = $user->assignedGmailGroups;
            $this->info("👤 {$user->name} ({$user->email}) - ID: {$user->id}");
            
            if ($groups->count() > 0) {
                foreach ($groups as $group) {
                    $status = $group->is_active ? '✅' : '❌';
                    $this->info("   📧 {$status} {$group->email}");
                }
            } else {
                $this->warn("   ⚠️  Sin grupos asignados");
            }
        }
        
        if (!$userId) {
            $totalGroups = GmailGroup::count();
            $activeGroups = GmailGroup::where('is_active', true)->count();
            $this->info("\n📊 Total grupos: {$totalGroups} (Activos: {$activeGroups})");
        }
    }

    private function showImportedEmails($userId = null)
    {
        $this->info("\n📥 EMAILS IMPORTADOS");
        $this->info('-' . str_repeat('-', 30));
        
        $query = ImportedEmail::query();
        if ($userId) {
            $query->where('assigned_to', $userId);
        }
        
        $statusCounts = $query->select('case_status', DB::raw('count(*) as count'))
                             ->groupBy('case_status')
                             ->pluck('count', 'case_status');
                             
        foreach ($statusCounts as $status => $count) {
            $icon = match($status) {
                'pending' => '⏳',
                'assigned' => '👤',
                'opened' => '📂',
                'in_progress' => '🔄',
                'pending_closure' => '⏰',
                'resolved' => '✅',
                'spam_marked' => '🗑️',
                default => '❓'
            };
            $this->info("{$icon} {$status}: {$count}");
        }
        
        if ($statusCounts->isEmpty()) {
            $this->warn('⚠️  No hay emails importados');
        } else {
            $total = $statusCounts->sum();
            $this->info("📊 Total: {$total} emails");
        }
    }

    private function showOutboxEmails($userId = null)
    {
        $this->info("\n📤 EMAILS EN COLA DE ENVÍO");
        $this->info('-' . str_repeat('-', 30));
        
        $query = OutboxEmail::query();
        if ($userId) {
            // Filtrar por emails creados por el usuario
            $query->where('created_by', $userId);
        }
        
        $statusCounts = $query->select('send_status', DB::raw('count(*) as count'))
                             ->groupBy('send_status')
                             ->pluck('count', 'send_status');
                             
        foreach ($statusCounts as $status => $count) {
            $icon = match($status) {
                'pending' => '⏳',
                'sending' => '📤',
                'sent' => '✅',
                'failed' => '❌',
                default => '❓'
            };
            $this->info("{$icon} {$status}: {$count}");
        }
        
        if ($statusCounts->isEmpty()) {
            $this->warn('⚠️  No hay emails en cola');
        } else {
            $total = $statusCounts->sum();
            $this->info("📊 Total: {$total} emails");
        }
    }

    private function showCustomerCases($userId = null)
    {
        $this->info("\n📁 CASOS DE CLIENTES");
        $this->info('-' . str_repeat('-', 30));
        
        try {
            $query = CustomerCase::query();
            if ($userId) {
                $query->where('assigned_user_id', $userId);
            }
            
            $statusCounts = $query->select('status', DB::raw('count(*) as count'))
                                 ->groupBy('status')
                                 ->pluck('count', 'status');
        } catch (\Exception $e) {
            $this->warn("⚠️  Error consultando casos: " . $e->getMessage());
            return;
        }
                             
        foreach ($statusCounts as $status => $count) {
            $icon = match($status) {
                'open' => '📂',
                'in_progress' => '🔄',
                'resolved' => '✅',
                'closed' => '🔒',
                default => '❓'
            };
            $this->info("{$icon} {$status}: {$count}");
        }
        
        if ($statusCounts->isEmpty()) {
            $this->warn('⚠️  No hay casos registrados');
        } else {
            $total = $statusCounts->sum();
            $this->info("📊 Total: {$total} casos");
            
            // Mostrar últimos 3 casos
            $recentCases = CustomerCase::orderBy('created_at', 'desc')
                                     ->limit(3);
            if ($userId) {
                $recentCases->where('assigned_user_id', $userId);
            }
            $recentCases = $recentCases->get();
            
            if ($recentCases->count() > 0) {
                $this->info("\n📋 Últimos casos:");
                foreach ($recentCases as $case) {
                    $this->info("   {$case->case_number} - {$case->status} - {$case->created_at->format('Y-m-d H:i')}");
                }
            }
        }
    }

    private function showCommunications($userId = null)
    {
        $this->info("\n💬 COMUNICACIONES");
        $this->info('-' . str_repeat('-', 30));
        
        try {
            $query = Communication::query();
            if ($userId) {
                $query->whereHas('customerCase', function($q) use ($userId) {
                    $q->where('assigned_user_id', $userId);
                });
            }
            
            $typeCounts = $query->select('type', DB::raw('count(*) as count'))
                               ->groupBy('type')
                               ->pluck('count', 'type');
        } catch (\Exception $e) {
            $this->warn("⚠️  Tabla de comunicaciones no disponible o sin datos");
            return;
        }
                           
        foreach ($typeCounts as $type => $count) {
            $icon = match($type) {
                'email_inbound' => '📥',
                'email_outbound' => '📤',
                'note' => '📝',
                default => '💬'
            };
            $this->info("{$icon} {$type}: {$count}");
        }
        
        if ($typeCounts->isEmpty()) {
            $this->warn('⚠️  No hay comunicaciones registradas');
        } else {
            $total = $typeCounts->sum();
            $this->info("📊 Total: {$total} comunicaciones");
        }
    }

    private function showOAuthStatus()
    {
        $this->info("\n🔐 ESTADO OAUTH");
        $this->info('-' . str_repeat('-', 30));
        
        try {
            // Primero verificar base de datos
            $oauthToken = \App\Models\OAuthToken::getActiveToken('gmail');
            
            if ($oauthToken) {
                $this->info("✅ Token OAuth encontrado en base de datos");
                $this->info("   📅 Creado: {$oauthToken->created_at->format('Y-m-d H:i:s')}");
                
                if ($oauthToken->expires_at) {
                    if ($oauthToken->isExpired()) {
                        $this->warn("   ⚠️ Token expirado: {$oauthToken->expires_at->format('Y-m-d H:i:s')}");
                    } elseif ($oauthToken->isExpiringSoon()) {
                        $this->warn("   ⚠️ Token expira pronto: {$oauthToken->expires_at->format('Y-m-d H:i:s')}");
                    } else {
                        $this->info("   ⏰ Expira: {$oauthToken->expires_at->format('Y-m-d H:i:s')}");
                    }
                }
                
                if ($oauthToken->scopes) {
                    $this->info("   🔑 Scopes: " . implode(', ', $oauthToken->scopes));
                    
                    $requiredScopes = ['gmail.readonly', 'gmail.send'];
                    $hasScope = array_map(fn($s) => str_contains(implode(' ', $oauthToken->scopes), $s), $requiredScopes);
                    
                    if (array_reduce($hasScope, fn($carry, $item) => $carry && $item, true)) {
                        $this->info("   ✅ Todos los scopes requeridos presentes");
                    } else {
                        $this->error("   ❌ Faltan scopes requeridos");
                    }
                }
                
                return;
            }
            
            // Fallback: verificar archivos (sistema anterior)
            $tokenPath = storage_path('app/google_oauth_token.json');
            
            if (file_exists($tokenPath)) {
                $token = json_decode(file_get_contents($tokenPath), true);
                
                if (isset($token['access_token'])) {
                    $this->info("✅ Token de acceso: Presente");
                    
                    if (isset($token['expires_in'])) {
                        $expiresAt = now()->addSeconds($token['expires_in']);
                        $this->info("⏰ Expira: {$expiresAt->format('Y-m-d H:i:s')}");
                    }
                    
                    if (isset($token['scope'])) {
                        $scopes = explode(' ', $token['scope']);
                        $this->info("🔑 Scopes:");
                        foreach ($scopes as $scope) {
                            $this->info("   - {$scope}");
                        }
                        
                        // Verificar scopes críticos
                        $requiredScopes = ['gmail.readonly', 'gmail.send'];
                        $missingScopes = array_diff($requiredScopes, $scopes);
                        
                        if (empty($missingScopes)) {
                            $this->info("✅ Todos los scopes requeridos están presentes");
                        } else {
                            $this->error("❌ Scopes faltantes: " . implode(', ', $missingScopes));
                        }
                    }
                } else {
                    $this->error("❌ Token inválido o corrupto");
                }
            } else {
                $this->error("❌ Archivo de token no encontrado");
                $this->info("💡 Ejecutar: php artisan gmail:setup-oauth");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error verificando OAuth: {$e->getMessage()}");
        }
    }
}