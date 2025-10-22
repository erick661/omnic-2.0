<?php

use App\Models\ImportedEmail;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;

new class extends Component
{
    public $selectedCase = null;

    public function getAuthUser(): array
    {
        // Usuario simulado para desarrollo
        return [
            'id' => 1,
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@empresa.com',
            'avatar' => 'https://i.pravatar.cc/100?u=1'
        ];
    }

   

    public function casosAgentes(): Collection
    {
        $casos = [
            ['id' => 1, 'subject' => 'Problema con facturación mensual', 'status' => 'asignado', 'assigned_at' => '2024-06-01 10:00:00', 'have_attachment' => true, 'priority' => 'high', 'category' => 'billing', 'comments' => 3],
            ['id' => 2, 'subject' => 'Error en configuración de cuenta', 'status' => 'asignado', 'assigned_at' => '2024-06-02 11:30:00', 'have_attachment' => false, 'priority' => 'medium', 'category' => 'account', 'comments' => 1],
            ['id' => 3, 'subject' => 'Solicitud de nueva funcionalidad', 'status' => 'asignado', 'assigned_at' => '2024-06-03 14:15:00', 'have_attachment' => false, 'priority' => 'low', 'category' => 'feature', 'comments' => 0],
            ['id' => 4, 'subject' => 'Bug crítico en el sistema', 'status' => 'en_progreso', 'assigned_at' => '2024-06-04 09:20:00', 'have_attachment' => true, 'priority' => 'high', 'category' => 'bug', 'comments' => 5],
            ['id' => 5, 'subject' => 'Actualización de datos personales', 'status' => 'en_progreso', 'assigned_at' => '2024-06-05 16:45:00', 'have_attachment' => false, 'priority' => 'low', 'category' => 'account', 'comments' => 2],
            ['id' => 6, 'subject' => 'Consulta sobre servicios premium', 'status' => 'resuelto', 'assigned_at' => '2024-06-06 08:30:00', 'have_attachment' => false, 'priority' => 'medium', 'category' => 'support', 'comments' => 4],
            ['id' => 7, 'subject' => 'Integración con API externa', 'status' => 'resuelto', 'assigned_at' => '2024-06-07 13:10:00', 'have_attachment' => true, 'priority' => 'high', 'category' => 'feature', 'comments' => 8],
        ];

        return collect($casos);
    }

    public function selectCase($caseId)
    {
        $this->selectedCase = $caseId;
        $this->dispatch('case-selected', $caseId);
    }

    public function openChannelResponse($caseId, $channel = 'email')
    {
        $case = $this->casosAgentes()->firstWhere('id', $caseId);
        if ($case) {
            // Navegar al componente dedicado del canal
            $routeName = "case.{$channel}";
            
            return redirect()->route($routeName, [
                'caseId' => $caseId,
                'caseData' => $case
            ]);
        }
        
        $this->dispatch('notify', 'Caso no encontrado', 'error');
    }



    public function getPriorityBadgeClass($priority): string
    {
        return match($priority) {
            'high' => 'badge-error',
            'medium' => 'badge-warning', 
            'low' => 'badge-success',
            default => 'badge-neutral'
        };
    }

    public function getCategoryBadgeClass($category): string
    {
        return match($category) {
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

                                <div class="flex items-center gap-1">
                                    <div wire:loading wire:target="selectCase({{ $caso['id'] }})">
                                        <x-loading class="loading-xs" />
                                    </div>
                                    
                                    {{-- Dropdown para canales de respuesta --}}
                                    <div class="dropdown dropdown-end">
                                        <div tabindex="0" role="button" class="btn btn-xs btn-ghost">
                                            <x-icon name="o-chat-bubble-left-ellipsis" class="w-3 h-3" />
                                        </div>
                                        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'email')">
                                                <x-icon name="o-envelope" class="w-4 h-4" />
                                                Responder por Email
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'whatsapp')">
                                                <x-icon name="o-chat-bubble-oval-left" class="w-4 h-4" />
                                                Responder por WhatsApp
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'sms')">
                                                <x-icon name="o-device-phone-mobile" class="w-4 h-4" />
                                                Responder por SMS
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'chat')">
                                                <x-icon name="o-computer-desktop" class="w-4 h-4" />
                                                Responder por Chat
                                            </a></li>
                                        </ul>
                                    </div>
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

                                <div class="flex items-center gap-1">
                                    <div wire:loading wire:target="selectCase({{ $caso['id'] }})">
                                        <x-loading class="loading-xs" />
                                    </div>
                                    
                                    {{-- Dropdown para canales de respuesta --}}
                                    <div class="dropdown dropdown-end">
                                        <div tabindex="0" role="button" class="btn btn-xs btn-ghost">
                                            <x-icon name="o-chat-bubble-left-ellipsis" class="w-3 h-3" />
                                        </div>
                                        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'email')">
                                                <x-icon name="o-envelope" class="w-4 h-4" />
                                                Responder por Email
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'whatsapp')">
                                                <x-icon name="o-chat-bubble-oval-left" class="w-4 h-4" />
                                                Responder por WhatsApp
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'sms')">
                                                <x-icon name="o-device-phone-mobile" class="w-4 h-4" />
                                                Responder por SMS
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'chat')">
                                                <x-icon name="o-computer-desktop" class="w-4 h-4" />
                                                Responder por Chat
                                            </a></li>
                                        </ul>
                                    </div>
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

                                <div class="flex items-center gap-1">
                                    <div wire:loading wire:target="selectCase({{ $caso['id'] }})">
                                        <x-loading class="loading-xs" />
                                    </div>
                                    
                                    {{-- Dropdown para ver detalles --}}
                                    <div class="dropdown dropdown-end">
                                        <div tabindex="0" role="button" class="btn btn-xs btn-ghost">
                                            <x-icon name="o-eye" class="w-3 h-3" />
                                        </div>
                                        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'email')">
                                                <x-icon name="o-envelope" class="w-4 h-4" />
                                                Ver por Email
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'whatsapp')">
                                                <x-icon name="o-chat-bubble-oval-left" class="w-4 h-4" />
                                                Ver por WhatsApp
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'sms')">
                                                <x-icon name="o-device-phone-mobile" class="w-4 h-4" />
                                                Ver por SMS
                                            </a></li>
                                            <li><a wire:click="openChannelResponse({{ $caso['id'] }}, 'chat')">
                                                <x-icon name="o-computer-desktop" class="w-4 h-4" />
                                                Ver por Chat
                                            </a></li>
                                        </ul>
                                    </div>
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
