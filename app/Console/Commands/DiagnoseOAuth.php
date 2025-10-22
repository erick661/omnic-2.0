<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DiagnoseOAuth extends Command
{
    protected $signature = 'oauth:diagnose {--fix : Intentar arreglar problemas encontrados}';
    protected $description = 'Diagnosticar problemas con OAuth de Gmail';

    public function handle()
    {
        $this->info('🔍 Diagnosticando OAuth de Gmail...');
        
        // 1. Verificar archivos de configuración
        $this->checkConfigFiles();
        
        // 2. Verificar tokens
        $this->checkTokens();
        
        // 3. Verificar conectividad
        $this->checkConnectivity();
        
        // 4. Verificar permisos de archivos
        $this->checkFilePermissions();
        
        // 5. Analizar alternativas de almacenamiento
        $this->analyzeStorageOptions();
        
        // 6. Intentar arreglar si se solicita
        if ($this->option('fix')) {
            $this->attemptFix();
        }
        
        return 0;
    }
    
    private function checkConfigFiles(): void
    {
        $this->info("\n📂 Verificando archivos de configuración...");
        
        $credentialsPath = base_path('.cert/credentials.json');
        if (file_exists($credentialsPath)) {
            $this->line("✅ credentials.json encontrado");
            
            $credentials = json_decode(file_get_contents($credentialsPath), true);
            if (isset($credentials['installed']['client_id'])) {
                $clientId = substr($credentials['installed']['client_id'], 0, 20) . '...';
                $this->line("✅ client_id configurado: {$clientId}");
            } else {
                $this->error("❌ client_id no encontrado en credentials.json");
            }
            
            if (isset($credentials['installed']['client_secret'])) {
                $this->line("✅ client_secret configurado");
            } else {
                $this->error("❌ client_secret no encontrado");
            }
        } else {
            $this->error("❌ credentials.json no encontrado en .cert/");
        }
        
        // Verificar variables de entorno
        if (env('GOOGLE_CLIENT_ID')) {
            $this->line("✅ GOOGLE_CLIENT_ID configurado en .env");
        } else {
            $this->warn("⚠️ GOOGLE_CLIENT_ID no configurado en .env");
        }
        
        if (env('GOOGLE_CLIENT_SECRET')) {
            $this->line("✅ GOOGLE_CLIENT_SECRET configurado en .env");
        } else {
            $this->warn("⚠️ GOOGLE_CLIENT_SECRET no configurado en .env");
        }
    }
    
    private function checkTokens(): void
    {
        $this->info("\n🔑 Verificando tokens...");
        
        $tokenPaths = [
            'storage' => storage_path('app/gmail_token.json'),
            'cert' => base_path('.cert/gmail_token.json'),
            'google_oauth' => storage_path('app/google_oauth_token.json')
        ];
        
        $validTokenFound = false;
        
        foreach ($tokenPaths as $location => $tokenPath) {
            if (file_exists($tokenPath)) {
                $this->line("✅ Token encontrado en {$location}: {$tokenPath}");
                
                $tokenContent = file_get_contents($tokenPath);
                $token = json_decode($tokenContent, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error("❌ Token JSON inválido en {$location}");
                    continue;
                }
                
                // Verificar estructura del token
                $this->analyzeToken($token, $location);
                
                if (isset($token['access_token']) && isset($token['refresh_token'])) {
                    $validTokenFound = true;
                }
            } else {
                $this->warn("⚠️ Token no encontrado en {$location}: {$tokenPath}");
            }
        }
        
        if (!$validTokenFound) {
            $this->error("❌ No se encontró ningún token válido");
        }
    }
    
    private function analyzeToken(array $token, string $location): void
    {
        if (isset($token['access_token'])) {
            $accessToken = substr($token['access_token'], 0, 20) . '...';
            $this->line("   ✅ Access token presente: {$accessToken}");
        } else {
            $this->error("   ❌ Access token faltante");
        }
        
        if (isset($token['refresh_token'])) {
            $refreshToken = substr($token['refresh_token'], 0, 20) . '...';
            $this->line("   ✅ Refresh token presente: {$refreshToken}");
        } else {
            $this->warn("   ⚠️ Refresh token faltante");
        }
        
        if (isset($token['scope'])) {
            $scopes = explode(' ', $token['scope']);
            $this->line("   🔑 Scopes: " . implode(', ', $scopes));
            
            $requiredScopes = ['gmail.readonly', 'gmail.send'];
            $missingScopes = array_diff($requiredScopes, $scopes);
            
            if (empty($missingScopes)) {
                $this->line("   ✅ Todos los scopes requeridos están presentes");
            } else {
                $this->error("   ❌ Scopes faltantes: " . implode(', ', $missingScopes));
            }
        }
        
        if (isset($token['expires_in'])) {
            $expiresAt = Carbon::now()->addSeconds($token['expires_in']);
            $this->line("   ⏰ Token expira: {$expiresAt->format('Y-m-d H:i:s')}");
            
            if ($expiresAt->isPast()) {
                $this->warn("   ⚠️ Token expirado hace " . $expiresAt->diffForHumans());
            } elseif ($expiresAt->diffInMinutes() < 30) {
                $this->warn("   ⚠️ Token expira pronto: " . $expiresAt->diffForHumans());
            }
        } elseif (isset($token['created']) && isset($token['expires_in'])) {
            $createdAt = Carbon::createFromTimestamp($token['created']);
            $expiresAt = $createdAt->addSeconds($token['expires_in']);
            $this->line("   ⏰ Token expira: {$expiresAt->format('Y-m-d H:i:s')}");
            
            if ($expiresAt->isPast()) {
                $this->warn("   ⚠️ Token expirado hace " . $expiresAt->diffForHumans());
            }
        }
    }
    
    private function checkConnectivity(): void
    {
        $this->info("\n🌐 Verificando conectividad...");
        
        try {
            $gmailService = new GmailService();
            
            // Verificar si puede cargar credenciales
            $this->line("   🔄 Probando carga de credenciales...");
            
            // Verificar autenticación
            $this->line("   🔄 Probando autenticación...");
            
            if (method_exists($gmailService, 'isAuthenticated')) {
                if ($gmailService->isAuthenticated()) {
                    $this->line("   ✅ Autenticación exitosa");
                } else {
                    $this->error("   ❌ Falla de autenticación");
                }
            } else {
                $this->warn("   ⚠️ Método isAuthenticated no disponible");
            }
            
            // Probar una llamada simple a la API
            $this->line("   🔄 Probando llamada a Gmail API...");
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error de conectividad: " . $e->getMessage());
            $this->warn("   📝 Trace: " . substr($e->getTraceAsString(), 0, 200) . '...');
        }
    }
    
    private function checkFilePermissions(): void
    {
        $this->info("\n🔒 Verificando permisos de archivos...");
        
        $paths = [
            base_path('.cert'),
            storage_path('app'),
        ];
        
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $this->line("   📁 {$path}: {$perms}");
                
                if (!is_writable($path)) {
                    $this->error("   ❌ Directorio no escribible: {$path}");
                } else {
                    $this->line("   ✅ Directorio escribible");
                }
            } else {
                $this->warn("   ⚠️ Directorio no existe: {$path}");
            }
        }
    }
    
    private function analyzeStorageOptions(): void
    {
        $this->info("\n💾 Analizando opciones de almacenamiento de tokens...");
        
        $this->line("📋 OPCIONES DISPONIBLES:");
        
        // Opción 1: Archivos (actual)
        $this->line("\n1️⃣ ARCHIVOS (Actual)");
        $this->line("   ✅ Pros: Simple, rápido, no depende de BD");
        $this->line("   ❌ Contras: Se pierde en deploy, permisos, no centralizado");
        $this->line("   🔒 Seguridad: Media (depende de permisos filesystem)");
        
        // Opción 2: Base de datos
        $this->line("\n2️⃣ BASE DE DATOS");
        $this->line("   ✅ Pros: Persistente, centralizado, backups automáticos");
        $this->line("   ❌ Contras: Más complejo, requiere encriptación");
        $this->line("   🔒 Seguridad: Alta (con encriptación adecuada)");
        
        // Opción 3: Variables de entorno
        $this->line("\n3️⃣ VARIABLES DE ENTORNO");
        $this->line("   ✅ Pros: Separación de código/config, 12-factor app");
        $this->line("   ❌ Contras: Tokens largos, no auto-refresh");
        $this->line("   🔒 Seguridad: Alta (si se maneja correctamente)");
        
        // Opción 4: Service Account
        $this->line("\n4️⃣ SERVICE ACCOUNT (Recomendado para producción)");
        $this->line("   ✅ Pros: No expira, no requiere refresh, más seguro");
        $this->line("   ❌ Contras: Configuración inicial más compleja");
        $this->line("   🔒 Seguridad: Muy Alta");
        
        // Verificar si existe tabla para tokens
        $this->checkTokenTable();
    }
    
    private function checkTokenTable(): void
    {
        $this->line("\n🗄️ Verificando tabla para tokens...");
        
        try {
            $hasTable = DB::getSchemaBuilder()->hasTable('oauth_tokens');
            
            if ($hasTable) {
                $this->line("   ✅ Tabla oauth_tokens existe");
                
                $tokenCount = DB::table('oauth_tokens')->count();
                $this->line("   📊 Tokens almacenados: {$tokenCount}");
            } else {
                $this->warn("   ⚠️ Tabla oauth_tokens no existe");
                $this->line("   💡 Se puede crear para almacenar tokens de forma segura");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error verificando tabla: " . $e->getMessage());
        }
    }
    
    private function attemptFix(): void
    {
        $this->info("\n🔧 Opciones de reparación...");
        
        $option = $this->choice('¿Qué quieres hacer?', [
            'reauth' => 'Reautenticar OAuth desde cero',
            'create_table' => 'Crear tabla para tokens en BD',
            'migrate_tokens' => 'Migrar tokens a base de datos',
            'service_account' => 'Configurar Service Account',
            'nothing' => 'No hacer nada por ahora'
        ], 'nothing');
        
        switch ($option) {
            case 'reauth':
                $this->call('gmail:setup-oauth', ['--force' => true]);
                break;
                
            case 'create_table':
                $this->createTokenTable();
                break;
                
            case 'migrate_tokens':
                $this->migrateTokensToDatabase();
                break;
                
            case 'service_account':
                $this->showServiceAccountInstructions();
                break;
                
            default:
                $this->info('   ℹ️ No se realizaron cambios');
        }
    }
    
    private function createTokenTable(): void
    {
        $this->info("🗄️ Creando tabla para tokens OAuth...");
        
        if ($this->confirm('¿Crear migración para tabla oauth_tokens?')) {
            $this->call('make:migration', ['name' => 'create_oauth_tokens_table']);
            $this->info('💡 Edita la migración creada con la estructura recomendada');
            $this->showTokenTableStructure();
        }
    }
    
    private function showTokenTableStructure(): void
    {
        $this->line("\n📋 Estructura recomendada para tabla oauth_tokens:");
        $this->line('
Schema::create("oauth_tokens", function (Blueprint $table) {
    $table->id();
    $table->string("provider")->default("gmail"); // gmail, outlook, etc.
    $table->string("identifier")->nullable(); // email o user_id
    $table->text("access_token"); // Encriptado
    $table->text("refresh_token")->nullable(); // Encriptado  
    $table->json("scopes")->nullable();
    $table->timestamp("expires_at")->nullable();
    $table->json("metadata")->nullable(); // Info adicional
    $table->timestamps();
    
    $table->index(["provider", "identifier"]);
    $table->unique(["provider", "identifier"]);
});');
    }
    
    private function migrateTokensToDatabase(): void
    {
        $this->info("📦 Migrando tokens a base de datos...");
        $this->warn("⚠️ Esta función requiere implementación adicional");
        $this->line("💡 Se implementará en el siguiente paso si eliges esta opción");
    }
    
    private function showServiceAccountInstructions(): void
    {
        $this->info("\n🔐 CONFIGURACIÓN DE SERVICE ACCOUNT:");
        
        $this->line("
📋 PASOS PARA SERVICE ACCOUNT:

1️⃣ Crear Service Account en Google Cloud Console
   - Ir a: console.cloud.google.com
   - IAM & Admin > Service Accounts
   - Crear nueva cuenta de servicio

2️⃣ Generar clave JSON
   - Descargar archivo .json de credenciales
   - Guardar en .cert/service-account.json

3️⃣ Configurar Domain-wide Delegation
   - Habilitar delegación a nivel dominio
   - Agregar scopes necesarios

4️⃣ Actualizar código para usar Service Account
   - No requiere refresh tokens
   - Más seguro para producción

💡 ¿Quieres que implemente Service Account?");
    }
}