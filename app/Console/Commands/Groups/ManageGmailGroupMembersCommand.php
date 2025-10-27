<?php

namespace App\Console\Commands\Groups;

use Illuminate\Console\Command;
use App\Services\Groups\GmailGroupService;

class ManageGmailGroupMembersCommand extends Command
{
    protected $signature = 'groups:members 
                           {action : Action to perform (list, add, remove)}
                           {group_email : Gmail group email address}
                           {member_email? : Member email address (required for add/remove)}
                           {--role=member : Member role (member, manager, owner)}';

    protected $description = 'Manage Gmail group members';

    private GmailGroupService $groupService;

    public function __construct(GmailGroupService $groupService)
    {
        parent::__construct();
        $this->groupService = $groupService;
    }

    public function handle(): int
    {
        $action = $this->argument('action');
        $groupEmail = $this->argument('group_email');
        $memberEmail = $this->argument('member_email');

        $this->info("👥 Gestionando miembros del grupo: {$groupEmail}");

        try {
            switch ($action) {
                case 'list':
                    return $this->listMembers($groupEmail);
                
                case 'add':
                    if (!$memberEmail) {
                        $this->error("❌ Email del miembro requerido para agregar");
                        return self::FAILURE;
                    }
                    return $this->addMember($groupEmail, $memberEmail);
                
                case 'remove':
                    if (!$memberEmail) {
                        $this->error("❌ Email del miembro requerido para remover");
                        return self::FAILURE;
                    }
                    return $this->removeMember($groupEmail, $memberEmail);
                
                default:
                    $this->error("❌ Acción inválida: {$action}. Use: list, add, remove");
                    return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error gestionando miembros: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function listMembers(string $groupEmail): int
    {
        $this->info("📋 Listando miembros de: {$groupEmail}");
        
        $members = $this->groupService->getGroupMembers($groupEmail);
        
        if (empty($members)) {
            $this->warn("⚠️  No se encontraron miembros o el grupo no es accesible");
            return self::SUCCESS;
        }

        $this->table([
            'Email', 'Nombre', 'Rol', 'Estado'
        ], array_map(fn($member) => [
            $member['email'],
            $member['name'] ?: 'N/A',
            $member['role'],
            $member['status']
        ], $members));

        $this->info("📊 Total de miembros: " . count($members));
        
        return self::SUCCESS;
    }

    private function addMember(string $groupEmail, string $memberEmail): int
    {
        $role = $this->option('role');
        
        $this->info("➕ Agregando miembro: {$memberEmail} con rol: {$role}");

        $result = $this->groupService->addGroupMember($groupEmail, $memberEmail, $role);

        if ($result['success']) {
            $this->info("✅ Miembro agregado exitosamente");
        } else {
            $this->error("❌ Error agregando miembro: " . $result['error']);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function removeMember(string $groupEmail, string $memberEmail): int
    {
        $this->info("➖ Removiendo miembro: {$memberEmail}");

        if (!$this->confirm("¿Está seguro de remover a {$memberEmail} del grupo {$groupEmail}?")) {
            $this->info("Operación cancelada");
            return self::SUCCESS;
        }

        $result = $this->groupService->removeGroupMember($groupEmail, $memberEmail);

        if ($result['success']) {
            $this->info("✅ Miembro removido exitosamente");
        } else {
            $this->error("❌ Error removiendo miembro: " . $result['error']);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}