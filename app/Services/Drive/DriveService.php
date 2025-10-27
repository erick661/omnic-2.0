<?php

namespace App\Services\Drive;

use App\Services\Base\GoogleApiService;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;

class DriveService extends GoogleApiService
{
    protected array $requiredScopes = [
        Drive::DRIVE_FILE,
        Drive::DRIVE,
    ];

    private ?Drive $driveService = null;

    public function __construct()
    {
        parent::__construct();
        // Lazy loading: driveService se inicializa cuando sea necesario
    }
    
    /**
     * Inicializar el servicio Drive cuando sea necesario
     */
    protected function ensureDriveService(): void
    {
        if ($this->driveService === null) {
            $this->authenticateClient();
            $this->driveService = new Drive($this->client);
        }
    }

    /**
     * Listar carpetas
     */
    public function listFolders(array $options = []): array
    {
        $this->ensureDriveService();
        
        try {
            $query = "mimeType='application/vnd.google-apps.folder' and trashed=false";
            
            if (!empty($options['parent'])) {
                $query .= " and '{$options['parent']}' in parents";
            }

            return $this->makeRequest(function () use ($query, $options) {
                $response = $this->driveService->files->listFiles([
                    'q' => $query,
                    'pageSize' => $options['limit'] ?? 50,
                    'fields' => 'files(id,name,createdTime,size,owners)',
                    'orderBy' => 'name'
                ]);

                return array_map(function ($file) {
                    return [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'created_at' => $file->getCreatedTime(),
                        'size' => $file->getSize(),
                        'owner' => $file->getOwners()[0]->getDisplayName() ?? 'Unknown'
                    ];
                }, $response->getFiles() ?? []);
            });

        } catch (\Exception $e) {
            Log::error('Error listando carpetas Drive', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Crear carpeta
     */
    public function createFolder(string $name, ?string $parentId = null): array
    {
        try {
            return $this->makeRequest(function () use ($name, $parentId) {
                $folder = new DriveFile();
                $folder->setName($name);
                $folder->setMimeType('application/vnd.google-apps.folder');
                
                if ($parentId) {
                    $folder->setParents([$parentId]);
                }

                $result = $this->driveService->files->create($folder);

                Log::info('Carpeta creada en Drive', [
                    'folder_id' => $result->getId(),
                    'name' => $name,
                    'parent' => $parentId
                ]);

                return [
                    'success' => true,
                    'folder_id' => $result->getId(),
                    'name' => $result->getName(),
                    'web_view_link' => $result->getWebViewLink()
                ];
            });

        } catch (\Exception $e) {
            Log::error('Error creando carpeta', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Subir archivo
     */
    public function uploadFile(string $filePath, string $fileName, ?string $parentId = null): array
    {
        try {
            if (!file_exists($filePath)) {
                throw new \InvalidArgumentException("Archivo no encontrado: {$filePath}");
            }

            return $this->makeRequest(function () use ($filePath, $fileName, $parentId) {
                $file = new DriveFile();
                $file->setName($fileName);
                
                if ($parentId) {
                    $file->setParents([$parentId]);
                }

                $result = $this->driveService->files->create($file, [
                    'data' => file_get_contents($filePath),
                    'mimeType' => mime_content_type($filePath),
                ]);

                Log::info('Archivo subido a Drive', [
                    'file_id' => $result->getId(),
                    'name' => $fileName,
                    'size' => filesize($filePath)
                ]);

                return [
                    'success' => true,
                    'file_id' => $result->getId(),
                    'name' => $result->getName(),
                    'web_view_link' => $result->getWebViewLink()
                ];
            });

        } catch (\Exception $e) {
            Log::error('Error subiendo archivo', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test de conexión específico
     */
    public function performConnectionTest(): array
    {
        try {
            $about = $this->driveService->about->get(['fields' => 'user,storageQuota']);
            
            return [
                'success' => true,
                'message' => 'Conexión Google Drive exitosa',
                'user' => $about->getUser()->getDisplayName(),
                'storage_used' => $about->getStorageQuota()->getUsage()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en Google Drive: ' . $e->getMessage()
            ];
        }
    }
}