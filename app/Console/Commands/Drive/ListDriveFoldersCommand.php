<?php

namespace App\Console\Commands\Drive;

use Illuminate\Console\Command;
use App\Services\Drive\DriveService;

class ListDriveFoldersCommand extends Command
{
    protected $signature = 'drive:folders 
                           {--parent= : Parent folder ID}
                           {--limit=50 : Maximum folders to list}';

    protected $description = 'List Google Drive folders accessible to the system';

    private DriveService $driveService;

    public function __construct(DriveService $driveService)
    {
        parent::__construct();
        $this->driveService = $driveService;
    }

    public function handle(): int
    {
        $this->info('📁 Listando carpetas de Google Drive...');

        try {
            $folders = $this->driveService->listFolders([
                'parent' => $this->option('parent'),
                'limit' => (int) $this->option('limit'),
            ]);

            if (empty($folders)) {
                $this->warn('⚠️  No se encontraron carpetas');
                return self::SUCCESS;
            }

            $this->displayFolders($folders);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error listando carpetas: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function displayFolders(array $folders): void
    {
        $rows = [];
        foreach ($folders as $folder) {
            $rows[] = [
                $folder['id'],
                $folder['name'],
                $folder['created_at'],
                $folder['size'] ?? 'N/A',
                $folder['owner'] ?? 'N/A'
            ];
        }

        $this->table([
            'ID', 'Nombre', 'Creado', 'Tamaño', 'Propietario'
        ], $rows);

        $this->info("📊 Total de carpetas: " . count($folders));
    }
}