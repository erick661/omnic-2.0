<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use App\Models\ImportedEmail;
use App\Models\User;

new class extends Component
{
    public $selectedCase = null;

    public function getAuthUser(): array
    {
        $user = auth()->user();
        
        if (!$user) {
            // Fallback para desarrollo/debug - usar usuario de la DB o crear uno temporal
            return [
                'id' => 16069813, // ID del usuario autenticado en los logs
                'name' => 'Lucas Muñoz',
                'email' => 'lucas.munoz@orpro.cl',
                'avatar' => 'https://i.pravatar.cc/100?u=lucas.munoz',
            ];
        }
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => 'https://i.pravatar.cc/100?u=' . urlencode($user->email),
        ];
    }

    public function casosAgentes(): Collection
    {
        // Usar datos reales de la base de datos
        $currentUserId = $this->getAuthUser()['id'];
        
        $emails = ImportedEmail::with(['gmailGroup'])
            ->whereIn('case_status', ['assigned', 'opened', 'in_progress', 'resolved'])
            ->where('assigned_to', $currentUserId)
            ->orderBy('received_at', 'desc')
            ->get();

        return $emails->map(function ($email) {
            return [
                'id' => $email->id,
                'subject' => $email->subject,
                'status' => $this->mapCaseStatus($email->case_status),
                'assigned_at' => $email->received_at->format('Y-m-d H:i:s'),
                'have_attachment' => $email->has_attachments ?? false,
                'priority' => $this->determinePriority($email),
                'category' => $this->determineCategory($email),
                'comments' => 0, // Por ahora 0, en el futuro contar comentarios reales
                'from_email' => $email->from_email,
                'from_name' => $email->from_name,
                'group_name' => $email->gmailGroup->name ?? 'Sin grupo',
            ];
        });
    }
    
    private function mapCaseStatus(string $caseStatus): string
    {
        return match ($caseStatus) {
            'pending', 'assigned', 'opened' => 'asignado',
            'in_progress' => 'en_progreso',
            'resolved', 'closed' => 'resuelto',
            default => 'asignado'
        };
    }
    
    private function determinePriority(ImportedEmail $email): string
    {
        // Lógica básica para determinar prioridad
        if (str_contains(strtolower($email->subject), 'urgente') || 
            str_contains(strtolower($email->subject), 'crítico')) {
            return 'high';
        }
        
        if (str_contains(strtolower($email->subject), 'consulta') || 
            str_contains(strtolower($email->subject), 'info')) {
            return 'low';
        }
        
        return 'medium';
    }
    
    private function determineCategory(ImportedEmail $email): string
    {
        $subject = strtolower($email->subject);
        
        if (str_contains($subject, 'facturación') || str_contains($subject, 'billing')) {
            return 'billing';
        }
        
        if (str_contains($subject, 'cuenta') || str_contains($subject, 'account')) {
            return 'account';
        }
        
        if (str_contains($subject, 'bug') || str_contains($subject, 'error')) {
            return 'bug';
        }
        
        if (str_contains($subject, 'feature') || str_contains($subject, 'funcionalidad')) {
            return 'feature';
        }
        
        return 'support';
    }

    public function selectCase($caseId)
    {
        $this->selectedCase = $caseId;

        // Navegar a la pantalla de visualización del caso
        return redirect()->route('case.view', ['caseId' => $caseId]);
    }

    public function openChannelResponse($caseId, $channel = 'email')
    {
        $case = $this->casosAgentes()->firstWhere('id', $caseId);
        if ($case) {
            // Navegar al componente dedicado del canal
            $routeName = "case.{$channel}";

            return redirect()->route($routeName, [
                'caseId' => $caseId,
                'caseData' => $case,
            ]);
        }

        $this->dispatch('notify', 'Caso no encontrado', 'error');
    }

    public function getPriorityBadgeClass($priority): string
    {
        return match ($priority) {
            'high' => 'badge-error',
            'medium' => 'badge-warning',
            'low' => 'badge-success',
            default => 'badge-neutral'
        };
    }

    public function getCategoryBadgeClass($category): string
    {
        return match ($category) {
            'billing' => 'badge-primary',
            'account' => 'badge-secondary',
            'bug' => 'badge-error',
            'feature' => 'badge-info',
            'support' => 'badge-accent',
            default => 'badge-neutral'
        };
    }

    public function with(): array
    {
        $casosAgentes = $this->casosAgentes();

        return [
            'asignados' => $casosAgentes->filter(fn ($caso) => $caso['status'] === 'asignado'),
            'en_progreso' => $casosAgentes->filter(fn ($caso) => $caso['status'] === 'en_progreso'),
            'resueltos' => $casosAgentes->filter(fn ($caso) => $caso['status'] === 'resuelto'),
            'totalCases' => $casosAgentes->count(),
        ];
    }
}; ?>


<div>
    <x-header title="Inbox Agente" subtitle="Gestión de Casos" separator>
        <x-slot:middle class="!justify-end">
            <x-input icon="o-magnifying-glass" placeholder="Buscar casos..." class="w-64" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button icon="o-funnel" tooltip="Filtros" />
            <x-button icon="o-plus" class="btn-primary" tooltip="Nuevo caso">
                Nuevo Caso
            </x-button>
        </x-slot:actions>
    </x-header>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat 
            title="Total Casos" 
            description="Casos activos" 
            value="{{ $totalCases }}" 
            icon="o-inbox" 
            class="bg-primary/10"
        />
        <x-stat 
            title="Asignados" 
            description="Por revisar" 
            value="{{ $asignados->count() }}" 
            icon="o-clock" 
            class="bg-warning/10"
        />
        <x-stat 
            title="En Progreso" 
            description="Trabajando" 
            value="{{ $en_progreso->count() }}" 
            icon="o-cog-6-tooth" 
            class="bg-info/10"
        />
        <x-stat 
            title="Resueltos" 
            description="Completados hoy" 
            value="{{ $resueltos->count() }}" 
            icon="o-check-circle" 
            class="bg-success/10"
        />
    </div>

    {{-- Kanban Board --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        {{-- Columna: Asignados --}}
        <div class="space-y-4">
            <x-card title="Asignados" subtitle="Casos sin iniciar" class="h-full">
                <x-slot:menu>
                    <x-badge value="{{ $asignados->count() }}" class="badge-primary" />
                </x-slot:menu>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($asignados as $caso)
                        <x-card 
                            class="hover:bg-base-200 transition-all duration-200 border-l-4 border-l-warning {{ $selectedCase == $caso['id'] ? 'ring-2 ring-primary' : '' }}" 
                        >
                            {{-- Header del caso --}}
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1 cursor-pointer" wire:click="selectCase({{ $caso['id'] }})">
                                    <h4 class="font-semibold text-sm line-clamp-2">{{ $caso['subject'] }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Caso #{{ $caso['id'] }} • {{ \Carbon\Carbon::parse($caso['assigned_at'])->diffForHumans() }}
                                    </p>
                                </div>
                                @if($caso['have_attachment'])
                                    <x-icon name="o-paper-clip" class="w-4 h-4 text-gray-400" />
                                @endif
                            </div>

                            {{-- Badges y metadata --}}
                            <div class="flex flex-wrap gap-2 mb-3">
                                <x-badge 
                                    value="{{ ucfirst($caso['priority']) }}" 
                                    class="badge-xs {{ $this->getPriorityBadgeClass($caso['priority']) }}"
                                />
                                <x-badge 
                                    value="{{ ucfirst($caso['category']) }}" 
                                    class="badge-xs {{ $this->getCategoryBadgeClass($caso['category']) }}"
                                />
                            </div>

                            {{-- Footer del caso --}}
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    @if($caso['comments'] > 0)
                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                            <x-icon name="o-chat-bubble-left" class="w-3 h-3" />
                                            {{ $caso['comments'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <div wire:loading wire:target="selectCase({{ $caso['id'] }})">
                                        <x-loading class="loading-xs" />
                                    </div>
                                    
                                    {{-- Botón de Ver Caso --}}
                                    <x-button 
                                        wire:click="selectCase({{ $caso['id'] }})"
                                        label="Ver Caso"
                                        icon="o-eye"
                                        class="btn-sm btn-primary"
                                        spinner="selectCase({{ $caso['id'] }})"
                                    />
                                </div>
                            </div>
                        </x-card>
                    @empty
                        <x-card class="text-center py-8">
                            <x-icon name="o-inbox" class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                            <p class="text-gray-500">No hay casos asignados</p>
                        </x-card>
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Columna: En Progreso --}}
        <div class="space-y-4">
            <x-card title="En Progreso" subtitle="Casos activos" class="h-full">
                <x-slot:menu>
                    <x-badge value="{{ $en_progreso->count() }}" class="badge-info" />
                </x-slot:menu>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($en_progreso as $caso)
                        <x-card 
                            class="hover:bg-base-200 transition-all duration-200 border-l-4 border-l-info {{ $selectedCase == $caso['id'] ? 'ring-2 ring-primary' : '' }}" 
                        >
                            {{-- Header del caso --}}
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1 cursor-pointer" wire:click="selectCase({{ $caso['id'] }})">
                                    <h4 class="font-semibold text-sm line-clamp-2">{{ $caso['subject'] }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Caso #{{ $caso['id'] }} • {{ \Carbon\Carbon::parse($caso['assigned_at'])->diffForHumans() }}
                                    </p>
                                </div>
                                @if($caso['have_attachment'])
                                    <x-icon name="o-paper-clip" class="w-4 h-4 text-gray-400" />
                                @endif
                            </div>

                            {{-- Badges y metadata --}}
                            <div class="flex flex-wrap gap-2 mb-3">
                                <x-badge 
                                    value="{{ ucfirst($caso['priority']) }}" 
                                    class="badge-xs {{ $this->getPriorityBadgeClass($caso['priority']) }}"
                                />
                                <x-badge 
                                    value="{{ ucfirst($caso['category']) }}" 
                                    class="badge-xs {{ $this->getCategoryBadgeClass($caso['category']) }}"
                                />
                            </div>

                            {{-- Footer del caso --}}
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    @if($caso['comments'] > 0)
                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                            <x-icon name="o-chat-bubble-left" class="w-3 h-3" />
                                            {{ $caso['comments'] }}
                                        </div>
                                    @endif

                                    {{-- Avatar del agente --}}
                                    <x-avatar 
                                        image="{{ $this->getAuthUser()['avatar'] }}" 
                                        class="!w-6 !h-6"
                                        tooltip="Asignado a {{ $this->getAuthUser()['name'] }}"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <div wire:loading wire:target="selectCase({{ $caso['id'] }})">
                                        <x-loading class="loading-xs" />
                                    </div>
                                    
                                    {{-- Botón de Ver Caso --}}
                                    <x-button 
                                        wire:click="selectCase({{ $caso['id'] }})"
                                        label="Ver Caso"
                                        icon="o-eye"
                                        class="btn-sm btn-primary"
                                        spinner="selectCase({{ $caso['id'] }})"
                                    />
                                </div>
                            </div>
                        </x-card>
                    @empty
                        <x-card class="text-center py-8">
                            <x-icon name="o-cog-6-tooth" class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                            <p class="text-gray-500">No hay casos en progreso</p>
                        </x-card>
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Columna: Resueltos --}}
        <div class="space-y-4">
            <x-card title="Resueltos" subtitle="Casos completados" class="h-full">
                <x-slot:menu>
                    <x-badge value="{{ $resueltos->count() }}" class="badge-success" />
                </x-slot:menu>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($resueltos as $caso)
                        <x-card 
                            class="hover:bg-base-200 transition-all duration-200 border-l-4 border-l-success {{ $selectedCase == $caso['id'] ? 'ring-2 ring-primary' : '' }}" 
                        >
                            {{-- Header del caso --}}
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1 cursor-pointer" wire:click="selectCase({{ $caso['id'] }})">
                                    <h4 class="font-semibold text-sm line-clamp-2">{{ $caso['subject'] }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Caso #{{ $caso['id'] }} • {{ \Carbon\Carbon::parse($caso['assigned_at'])->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    @if($caso['have_attachment'])
                                        <x-icon name="o-paper-clip" class="w-4 h-4 text-gray-400" />
                                    @endif
                                    <x-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                </div>
                            </div>

                            {{-- Badges y metadata --}}
                            <div class="flex flex-wrap gap-2 mb-3">
                                <x-badge 
                                    value="{{ ucfirst($caso['priority']) }}" 
                                    class="badge-xs {{ $this->getPriorityBadgeClass($caso['priority']) }}"
                                />
                                <x-badge 
                                    value="{{ ucfirst($caso['category']) }}" 
                                    class="badge-xs {{ $this->getCategoryBadgeClass($caso['category']) }}"
                                />
                            </div>

                            {{-- Footer del caso --}}
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    @if($caso['comments'] > 0)
                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                            <x-icon name="o-chat-bubble-left" class="w-3 h-3" />
                                            {{ $caso['comments'] }}
                                        </div>
                                    @endif

                                    {{-- Avatar del agente --}}
                                    <x-avatar 
                                        image="{{ $this->getAuthUser()['avatar'] }}" 
                                        class="!w-6 !h-6"
                                        tooltip="Resuelto por {{ $this->getAuthUser()['name'] }}"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <div wire:loading wire:target="selectCase({{ $caso['id'] }})">
                                        <x-loading class="loading-xs" />
                                    </div>
                                    
                                    {{-- Botón de Ver Caso --}}
                                    <x-button 
                                        wire:click="selectCase({{ $caso['id'] }})"
                                        label="Ver Caso"
                                        icon="o-eye"
                                        class="btn-sm btn-success btn-outline"
                                        spinner="selectCase({{ $caso['id'] }})"
                                    />
                                </div>
                            </div>
                        </x-card>
                    @empty
                        <x-card class="text-center py-8">
                            <x-icon name="o-check-circle" class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                            <p class="text-gray-500">No hay casos resueltos</p>
                        </x-card>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>



    {{-- Toast notifications --}}
    <x-toast />
</div>
