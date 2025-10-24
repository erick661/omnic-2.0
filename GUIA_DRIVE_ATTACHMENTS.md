# GESTIÓN DE ATTACHMENTS VÍA GOOGLE DRIVE

## 📎 ARQUITECTURA DE ATTACHMENTS

### Estructura de carpetas recomendada:
```
📁 Omnic Email Attachments/
├── 📁 2025/
│   ├── 📁 01-Enero/
│   │   ├── 📁 lucas.munoz/
│   │   │   ├── 📄 CASO-2025-000001_documento.pdf
│   │   │   └── 📄 CASO-2025-000001_imagen.jpg
│   │   └── 📁 otro.usuario/
│   └── 📁 02-Febrero/
├── 📁 Temp/ (archivos temporales)
└── 📁 Shared/ (archivos compartidos entre usuarios)
```

## 🔐 SCOPES NECESARIOS PARA DRIVE

### Scopes mínimos:
- `https://www.googleapis.com/auth/drive.file` - Solo archivos creados por la app
- `https://www.googleapis.com/auth/drive.readonly` - Leer archivos existentes

### Scopes completos (recomendados):
- `https://www.googleapis.com/auth/drive` - Acceso completo para gestión de carpetas
- `https://www.googleapis.com/auth/drive.metadata` - Metadatos de archivos

## 🏗️ IMPLEMENTACIÓN TÉCNICA

### 1. Service para Drive Integration

```php
<?php
// app/Services/GoogleDriveService.php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private Client $client;
    private Drive $driveService;
    private string $rootFolderId;

    public function __construct()
    {
        $this->setupClient();
        $this->driveService = new Drive($this->client);
        $this->rootFolderId = $this->ensureRootFolder();
    }

    private function setupClient(): void
    {
        $this->client = new Client();
        $this->client->setAuthConfig(config('services.google.service_account_path'));
        $this->client->setScopes([
            Drive::DRIVE,
            Drive::DRIVE_FILE
        ]);
        $this->client->setSubject(config('services.google.admin_email'));
    }

    public function uploadAttachment(
        string $filePath, 
        string $fileName, 
        string $caseNumber,
        string $userEmail
    ): array {
        try {
            // Crear estructura de carpetas si no existe
            $yearFolder = $this->ensureYearFolder();
            $monthFolder = $this->ensureMonthFolder($yearFolder);
            $userFolder = $this->ensureUserFolder($monthFolder, $userEmail);

            // Preparar archivo
            $fileMetadata = new DriveFile([
                'name' => "{$caseNumber}_{$fileName}",
                'parents' => [$userFolder]
            ]);

            // Subir archivo
            $result = $this->driveService->files->create(
                $fileMetadata,
                [
                    'data' => file_get_contents($filePath),
                    'mimeType' => mime_content_type($filePath),
                    'uploadType' => 'multipart'
                ]
            );

            // Configurar permisos (lectura para el dominio)
            $this->setFilePermissions($result->getId());

            Log::info('Attachment subido a Drive', [
                'file_id' => $result->getId(),
                'case_number' => $caseNumber,
                'user_email' => $userEmail
            ]);

            return [
                'success' => true,
                'file_id' => $result->getId(),
                'web_view_link' => $result->getWebViewLink(),
                'download_link' => $this->getDownloadLink($result->getId())
            ];

        } catch (\Exception $e) {
            Log::error('Error subiendo attachment', [
                'error' => $e->getMessage(),
                'file_name' => $fileName,
                'case_number' => $caseNumber
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function downloadAttachment(string $fileId, string $localPath): bool
    {
        try {
            $response = $this->driveService->files->get($fileId, [
                'alt' => 'media'
            ]);

            file_put_contents($localPath, $response->getBody());
            return true;

        } catch (\Exception $e) {
            Log::error('Error descargando attachment', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function shareWithUser(string $fileId, string $userEmail): bool
    {
        try {
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'user',
                'role' => 'reader',
                'emailAddress' => $userEmail
            ]);

            $this->driveService->permissions->create($fileId, $permission);
            return true;

        } catch (\Exception $e) {
            Log::error('Error compartiendo archivo', [
                'file_id' => $fileId,
                'user_email' => $userEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function ensureRootFolder(): string
    {
        $folderName = 'Omnic Email Attachments';
        
        // Buscar si ya existe
        $response = $this->driveService->files->listFiles([
            'q' => "name='{$folderName}' and mimeType='application/vnd.google-apps.folder'",
            'fields' => 'files(id, name)'
        ]);

        if (!empty($response->getFiles())) {
            return $response->getFiles()[0]->getId();
        }

        // Crear carpeta raíz
        $folderMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        $folder = $this->driveService->files->create($folderMetadata);
        
        // Compartir con el dominio
        $this->shareFolderWithDomain($folder->getId());
        
        return $folder->getId();
    }

    private function ensureYearFolder(): string
    {
        $year = date('Y');
        return $this->ensureSubfolder($this->rootFolderId, $year);
    }

    private function ensureMonthFolder(string $yearFolderId): string
    {
        $month = date('m-F');
        return $this->ensureSubfolder($yearFolderId, $month);
    }

    private function ensureUserFolder(string $monthFolderId, string $userEmail): string
    {
        $userName = explode('@', $userEmail)[0];
        return $this->ensureSubfolder($monthFolderId, $userName);
    }

    private function ensureSubfolder(string $parentId, string $folderName): string
    {
        // Buscar si ya existe
        $response = $this->driveService->files->listFiles([
            'q' => "name='{$folderName}' and '{$parentId}' in parents and mimeType='application/vnd.google-apps.folder'",
            'fields' => 'files(id, name)'
        ]);

        if (!empty($response->getFiles())) {
            return $response->getFiles()[0]->getId();
        }

        // Crear subcarpeta
        $folderMetadata = new DriveFile([
            'name' => $folderName,
            'parents' => [$parentId],
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        $folder = $this->driveService->files->create($folderMetadata);
        return $folder->getId();
    }

    private function setFilePermissions(string $fileId): void
    {
        // Compartir con el dominio (lectura)
        $domainPermission = new \Google\Service\Drive\Permission([
            'type' => 'domain',
            'role' => 'reader',
            'domain' => config('services.google.workspace_domain')
        ]);

        $this->driveService->permissions->create($fileId, $domainPermission);
    }

    private function shareFolderWithDomain(string $folderId): void
    {
        $domainPermission = new \Google\Service\Drive\Permission([
            'type' => 'domain',
            'role' => 'reader',
            'domain' => config('services.google.workspace_domain')
        ]);

        $this->driveService->permissions->create($folderId, $domainPermission);
    }

    private function getDownloadLink(string $fileId): string
    {
        return "https://drive.google.com/uc?id={$fileId}&export=download";
    }

    public function getFileInfo(string $fileId): array
    {
        try {
            $file = $this->driveService->files->get($fileId, [
                'fields' => 'id,name,size,mimeType,createdTime,modifiedTime,webViewLink'
            ]);

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'created_at' => $file->getCreatedTime(),
                'modified_at' => $file->getModifiedTime(),
                'web_view_link' => $file->getWebViewLink()
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo info de archivo', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
```

### 2. Modelo para Attachments

```php
<?php
// app/Models/EmailAttachment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAttachment extends Model
{
    protected $fillable = [
        'imported_email_id',
        'outbox_email_id',
        'original_filename',
        'stored_filename',
        'mime_type',
        'size_bytes',
        'drive_file_id',
        'drive_web_link',
        'storage_type', // 'drive', 'local', 's3'
        'is_accessible'
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_accessible' => 'boolean'
    ];

    public function importedEmail(): BelongsTo
    {
        return $this->belongsTo(ImportedEmail::class);
    }

    public function outboxEmail(): BelongsTo
    {
        return $this->belongsTo(OutboxEmail::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        if ($this->storage_type === 'drive' && $this->drive_file_id) {
            return "https://drive.google.com/uc?id={$this->drive_file_id}&export=download";
        }

        return route('attachments.download', $this->id);
    }

    public function getViewUrlAttribute(): string
    {
        if ($this->drive_web_link) {
            return $this->drive_web_link;
        }

        return route('attachments.view', $this->id);
    }
}
```

### 3. Migración para Attachments

```php
<?php
// database/migrations/create_email_attachments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('imported_email_id')->nullable()
                  ->constrained('imported_emails')->onDelete('cascade');
            $table->foreignId('outbox_email_id')->nullable()
                  ->constrained('outbox_emails')->onDelete('cascade');
            
            // Información del archivo
            $table->string('original_filename');
            $table->string('stored_filename')->nullable();
            $table->string('mime_type');
            $table->bigInteger('size_bytes');
            
            // Integración Drive
            $table->string('drive_file_id')->nullable()->unique();
            $table->string('drive_web_link', 500)->nullable();
            
            // Configuración
            $table->enum('storage_type', ['drive', 'local', 's3'])->default('drive');
            $table->boolean('is_accessible')->default(true);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['imported_email_id', 'storage_type']);
            $table->index(['outbox_email_id', 'storage_type']);
            $table->index('drive_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
```

## ⚙️ CONFIGURACIÓN ADICIONAL

### Variables de entorno (.env):
```bash
# Drive Integration
GOOGLE_DRIVE_ROOT_FOLDER_NAME="Omnic Email Attachments"
GOOGLE_WORKSPACE_DOMAIN=orproverificaciones.cl
ATTACHMENT_STORAGE_TYPE=drive
MAX_ATTACHMENT_SIZE=25000000  # 25MB
ALLOWED_ATTACHMENT_TYPES=pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,txt,zip
```

### Configuración services.php:
```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH'),
    'admin_email' => env('GOOGLE_WORKSPACE_ADMIN_EMAIL'),
    'workspace_domain' => env('GOOGLE_WORKSPACE_DOMAIN'),
],
```

## 🚀 COMANDOS ADICIONALES

### Comando para probar Drive:
```bash
php artisan make:command TestDriveIntegration
```

### Comando para migrar attachments existentes:
```bash
php artisan make:command MigrateAttachmentsToDrive
```

## 🔒 CONSIDERACIONES DE SEGURIDAD

### Permisos recomendados:
1. **Carpeta raíz**: Solo lectura para el dominio
2. **Carpetas de usuario**: Lectura para usuario específico + admin
3. **Archivos individuales**: Permisos heredados de carpeta padre

### Validaciones necesarias:
1. **Tipo de archivo**: Validar extensiones permitidas
2. **Tamaño**: Límite máximo por archivo y por caso
3. **Contenido**: Escaneo de virus/malware si es posible
4. **Acceso**: Solo usuarios autorizados pueden ver attachments

### Logging y auditoría:
- Log todas las subidas/descargas de archivos
- Tracking de quién accede a qué archivos
- Alertas por archivos sospechosos

## 📋 CHECKLIST IMPLEMENTACIÓN

### Fase 1 - Configuración básica:
- [ ] Añadir scopes de Drive a Domain-wide Delegation
- [ ] Crear GoogleDriveService
- [ ] Migración y modelo EmailAttachment
- [ ] Variables de entorno configuradas

### Fase 2 - Integración con emails:
- [ ] Modificar GmailService para extraer attachments
- [ ] Actualizar OutboxEmailService para adjuntar archivos
- [ ] Crear controladores para download/view

### Fase 3 - Interfaz usuario:
- [ ] Vista para attachments en emails
- [ ] Upload de attachments en respuestas
- [ ] Galería/lista de attachments por caso

### Fase 4 - Optimización:
- [ ] Caché de metadatos de archivos
- [ ] Compresión automática de imágenes
- [ ] Limpieza automática de archivos temporales