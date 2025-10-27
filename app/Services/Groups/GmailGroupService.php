<?php

namespace App\Services\Groups;

use App\Services\Base\GoogleApiService;
use Google\Service\Directory;
use Google\Service\Directory\Group;
use Google\Service\Directory\Member;
use App\Models\GmailGroup;
use Illuminate\Support\Facades\Log;

class GmailGroupService extends GoogleApiService
{
    protected array $requiredScopes = [
        Directory::ADMIN_DIRECTORY_GROUP,
        Directory::ADMIN_DIRECTORY_GROUP_MEMBER,
    ];

    private ?Directory $directoryService = null;

    public function __construct()
    {
        // Para grupos, usar admin como impersonation por defecto
        $this->impersonateEmail = config('services.google.admin_email', 'admin@orproverificaciones.cl');
        
        parent::__construct();
        // Lazy loading: directoryService se inicializa cuando sea necesario
    }
    
    /**
     * Inicializar el servicio Directory cuando sea necesario
     */
    protected function ensureDirectoryService(): void
    {
        if ($this->directoryService === null) {
            $this->authenticateClient();
            $this->directoryService = new Directory($this->client);
        }
    }

    /**
     * Listar grupos configurados
     */
    public function listGroups(array $options = []): array
    {
        $this->ensureDirectoryService();
        
        $query = GmailGroup::query();

        if ($options['active_only'] ?? false) {
            $query->where('is_active', true);
        }

        $groups = $query->get();

        return $groups->map(function ($group) use ($options) {
            $groupData = [
                'id' => $group->id,
                'name' => $group->name,
                'email' => $group->email,
                'description' => $group->description,
                'is_active' => $group->is_active,
                'import_enabled' => $group->import_enabled,
                'auto_assign' => $group->auto_assign,
            ];

            if ($options['with_stats'] ?? false) {
                $groupData['stats'] = $this->getGroupStats($group);
            }

            return $groupData;
        })->toArray();
    }

    /**
     * Crear nuevo grupo
     */
    public function createGroup(array $groupData): array
    {
        try {
            // Crear en base de datos
            $group = GmailGroup::create([
                'name' => $groupData['name'],
                'email' => $groupData['email'],
                'description' => $groupData['description'] ?? null,
                'auto_assign' => $groupData['auto_assign'] ?? false,
                'import_enabled' => $groupData['import_enabled'] ?? true,
                'is_active' => true,
            ]);

            Log::info('Grupo creado', [
                'group_id' => $group->id,
                'email' => $group->email
            ]);

            return [
                'id' => $group->id,
                'name' => $group->name,
                'email' => $group->email,
                'description' => $group->description,
                'auto_assign' => $group->auto_assign,
                'import_enabled' => $group->import_enabled,
                'is_active' => $group->is_active,
            ];

        } catch (\Exception $e) {
            Log::error('Error creando grupo', [
                'email' => $groupData['email'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener miembros de un grupo
     */
    public function getGroupMembers(string $groupEmail): array
    {
        try {
            return $this->makeRequest(function () use ($groupEmail) {
                $members = $this->directoryService->members->listMembers($groupEmail);
                
                return array_map(function ($member) {
                    return [
                        'email' => $member->getEmail(),
                        'name' => $member->getName(),
                        'role' => $member->getRole(),
                        'status' => $member->getStatus(),
                        'type' => $member->getType(),
                    ];
                }, $members->getMembers() ?? []);
            });

        } catch (\Exception $e) {
            Log::warning('Error obteniendo miembros de grupo', [
                'group_email' => $groupEmail,
                'error' => $e->getMessage()
            ]);
            
            // Si falla la API, devolver array vacío
            return [];
        }
    }

    /**
     * Agregar miembro a grupo
     */
    public function addGroupMember(string $groupEmail, string $memberEmail, string $role = 'MEMBER'): array
    {
        try {
            return $this->makeRequest(function () use ($groupEmail, $memberEmail, $role) {
                $member = new Member();
                $member->setEmail($memberEmail);
                $member->setRole($role);

                $result = $this->directoryService->members->insert($groupEmail, $member);

                Log::info('Miembro agregado a grupo', [
                    'group_email' => $groupEmail,
                    'member_email' => $memberEmail,
                    'role' => $role
                ]);

                return [
                    'success' => true,
                    'member_email' => $result->getEmail(),
                    'role' => $result->getRole()
                ];
            });

        } catch (\Exception $e) {
            Log::error('Error agregando miembro a grupo', [
                'group_email' => $groupEmail,
                'member_email' => $memberEmail,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remover miembro de grupo
     */
    public function removeGroupMember(string $groupEmail, string $memberEmail): array
    {
        try {
            return $this->makeRequest(function () use ($groupEmail, $memberEmail) {
                $this->directoryService->members->delete($groupEmail, $memberEmail);

                Log::info('Miembro removido de grupo', [
                    'group_email' => $groupEmail,
                    'member_email' => $memberEmail
                ]);

                return ['success' => true];
            });

        } catch (\Exception $e) {
            Log::error('Error removiendo miembro de grupo', [
                'group_email' => $groupEmail,
                'member_email' => $memberEmail,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar acceso a Gmail para un grupo
     */
    public function verifyGmailAccess(string $groupEmail): array
    {
        try {
            // Configurar impersonación para este grupo
            $this->impersonateEmail = $groupEmail;
            $this->setupClient();
            $this->authenticateClient();

            // Crear servicio Gmail temporal
            $gmailService = new \Google\Service\Gmail($this->client);

            return $this->makeRequest(function () use ($gmailService) {
                $profile = $gmailService->users->getProfile('me');
                
                // Obtener algunos mensajes recientes para verificar acceso
                $messages = $gmailService->users_messages->listUsersMessages('me', [
                    'maxResults' => 1
                ]);

                return [
                    'success' => true,
                    'email_address' => $profile->getEmailAddress(),
                    'email_count' => $profile->getMessagesTotal(),
                    'last_activity' => $messages->getMessages() ? 'Reciente' : 'Sin actividad'
                ];
            });

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear grupo en Google Admin (opcional)
     */
    public function createGoogleGroup(array $groupData): array
    {
        try {
            return $this->makeRequest(function () use ($groupData) {
                $group = new Group();
                $group->setEmail($groupData['email']);
                $group->setName($groupData['name']);
                $group->setDescription($groupData['description'] ?? '');

                $result = $this->directoryService->groups->insert($group);

                Log::info('Grupo creado en Google Admin', [
                    'email' => $result->getEmail(),
                    'name' => $result->getName()
                ]);

                return [
                    'success' => true,
                    'group_id' => $result->getId(),
                    'email' => $result->getEmail(),
                    'name' => $result->getName()
                ];
            });

        } catch (\Exception $e) {
            Log::error('Error creando grupo en Google Admin', [
                'email' => $groupData['email'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas de un grupo
     */
    private function getGroupStats(GmailGroup $group): array
    {
        return [
            'total_emails' => $group->importedEmails()->count(),
            'pending' => $group->importedEmails()->where('case_status', 'pending')->count(),
            'assigned' => $group->importedEmails()->where('case_status', 'assigned')->count(),
            'resolved' => $group->importedEmails()->where('case_status', 'resolved')->count(),
            'last_import' => $group->importedEmails()->latest('received_at')->value('received_at'),
        ];
    }

    /**
     * Test de conexión específico
     */
    public function performConnectionTest(): array
    {
        try {
            // Probar listado de grupos
            $groups = $this->directoryService->groups->listGroups([
                'domain' => config('services.google.domain', 'orproverificaciones.cl'),
                'maxResults' => 1
            ]);

            return [
                'success' => true,
                'message' => 'Conexión Directory API exitosa',
                'groups_found' => count($groups->getGroups() ?? [])
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en Directory API: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sincronizar grupos desde Google Admin
     */
    public function syncGroupsFromGoogle(): array
    {
        try {
            $domain = config('services.google.domain', 'orproverificaciones.cl');
            
            $googleGroups = $this->makeRequest(function () use ($domain) {
                return $this->directoryService->groups->listGroups([
                    'domain' => $domain,
                    'maxResults' => 100
                ]);
            });

            $synchronized = 0;
            $errors = [];

            foreach ($googleGroups->getGroups() ?? [] as $googleGroup) {
                try {
                    GmailGroup::updateOrCreate(
                        ['email' => $googleGroup->getEmail()],
                        [
                            'name' => $googleGroup->getName(),
                            'description' => $googleGroup->getDescription(),
                            'is_active' => true,
                            'import_enabled' => false, // Deshabilitado por defecto
                        ]
                    );
                    $synchronized++;

                } catch (\Exception $e) {
                    $errors[] = [
                        'group' => $googleGroup->getEmail(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('Sincronización de grupos completada', [
                'synchronized' => $synchronized,
                'errors' => count($errors)
            ]);

            return [
                'success' => true,
                'synchronized' => $synchronized,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Log::error('Error sincronizando grupos', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}