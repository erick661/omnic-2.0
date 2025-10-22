<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $caseId;

    public $communications = [];

    public $allCases = true; // Si mostrar todos los casos o solo el actual

    public $perPage = 10;

    public $currentPage = 1;

    public $hasMore = true;

    public function mount($caseId)
    {
        $this->caseId = $caseId;
        $this->loadInitialCommunications();
    }

    public function loadInitialCommunications()
    {
        $this->communications = $this->generateCommunications($this->currentPage);
        $this->hasMore = count($this->communications) >= $this->perPage;
    }

    public function loadMore()
    {
        $this->currentPage++;
        $newCommunications = $this->generateCommunications($this->currentPage);

        if (empty($newCommunications)) {
            $this->hasMore = false;

            return;
        }

        $this->communications = array_merge($this->communications, $newCommunications);
        $this->hasMore = count($newCommunications) >= $this->perPage;
    }

    public function toggleScope()
    {
        $this->allCases = ! $this->allCases;
        $this->currentPage = 1;
        $this->communications = [];
        $this->loadInitialCommunications();
    }

    private function generateCommunications($page)
    {
        // Simulación de comunicaciones de múltiples casos
        $allCommunications = [
            // Caso Actual
            [
                'id' => 1,
                'case_id' => $this->caseId,
                'case_title' => 'Problema con facturación mensual',
                'type' => 'email',
                'direction' => 'inbound',
                'from' => 'María González',
                'from_email' => 'maria.gonzalez@empresa.com',
                'from_avatar' => 'https://i.pravatar.cc/100?u=maria',
                'content' => 'Estimados, tengo un problema con la facturación de este mes. El monto no corresponde con lo acordado en el contrato.',
                'created_at' => '2025-10-22 09:30:00',
                'has_attachments' => true,
                'priority' => 'high',
                'status' => 'open',
            ],
            [
                'id' => 2,
                'case_id' => $this->caseId,
                'case_title' => 'Problema con facturación mensual',
                'type' => 'email',
                'direction' => 'outbound',
                'from' => auth()->user()->name ?? 'Juan Pérez',
                'from_email' => 'juan.perez@orpro.cl',
                'from_avatar' => 'https://i.pravatar.cc/100?u=agent1',
                'content' => 'Gracias por contactarnos, María. Hemos revisado su caso y estamos trabajando en una solución. Le enviaremos una respuesta dentro de las próximas 24 horas.',
                'created_at' => '2025-10-22 11:15:00',
                'has_attachments' => false,
                'priority' => 'high',
                'status' => 'in_progress',
            ],
            [
                'id' => 3,
                'case_id' => $this->caseId,
                'case_title' => 'Problema con facturación mensual',
                'type' => 'whatsapp',
                'direction' => 'inbound',
                'from' => 'María González',
                'from_phone' => '+56912345678',
                'from_avatar' => 'https://i.pravatar.cc/100?u=maria',
                'content' => 'Hola! Quería hacer un seguimiento del caso de facturación. ¿Hay alguna actualización?',
                'created_at' => '2025-10-22 14:20:00',
                'has_attachments' => false,
                'priority' => 'high',
                'status' => 'in_progress',
            ],
            // Otros casos (solo si allCases = true)
            [
                'id' => 4,
                'case_id' => '12346',
                'case_title' => 'Consulta sobre servicios premium',
                'type' => 'email',
                'direction' => 'inbound',
                'from' => 'Carlos Ruiz',
                'from_email' => 'carlos.ruiz@cliente.com',
                'from_avatar' => 'https://i.pravatar.cc/100?u=carlos',
                'content' => 'Buenos días, estoy interesado en conocer más sobre sus servicios premium. ¿Podrían enviarme información detallada?',
                'created_at' => '2025-10-22 08:45:00',
                'has_attachments' => false,
                'priority' => 'medium',
                'status' => 'open',
            ],
            [
                'id' => 5,
                'case_id' => '12347',
                'case_title' => 'Bug crítico en el sistema',
                'type' => 'phone',
                'direction' => 'inbound',
                'from' => 'Ana López',
                'from_phone' => '+56987654321',
                'from_avatar' => 'https://i.pravatar.cc/100?u=ana',
                'content' => 'Llamada urgente: Sistema caído desde las 07:00. Necesitamos solución inmediata.',
                'created_at' => '2025-10-22 07:30:00',
                'has_attachments' => false,
                'priority' => 'urgent',
                'status' => 'escalated',
            ],
            [
                'id' => 6,
                'case_id' => '12348',
                'case_title' => 'Solicitud de capacitación',
                'type' => 'sms',
                'direction' => 'outbound',
                'from' => 'Sistema Automático',
                'from_phone' => '+56900000000',
                'from_avatar' => 'https://i.pravatar.cc/100?u=system',
                'content' => 'Recordatorio: Su sesión de capacitación está programada para mañana a las 15:00. Confirme su asistencia.',
                'created_at' => '2025-10-21 16:00:00',
                'has_attachments' => false,
                'priority' => 'low',
                'status' => 'resolved',
            ],
            [
                'id' => 7,
                'case_id' => '12349',
                'case_title' => 'Integración API externa',
                'type' => 'webchat',
                'direction' => 'inbound',
                'from' => 'Roberto Silva',
                'from_email' => 'roberto.silva@tech.com',
                'from_avatar' => 'https://i.pravatar.cc/100?u=roberto',
                'content' => 'Hola, necesito ayuda para integrar nuestra API con su sistema. Tengo algunos errores de autenticación.',
                'created_at' => '2025-10-21 13:22:00',
                'has_attachments' => true,
                'priority' => 'medium',
                'status' => 'in_progress',
            ],
            [
                'id' => 8,
                'case_id' => '12350',
                'case_title' => 'Renovación de contrato',
                'type' => 'email',
                'direction' => 'inbound',
                'from' => 'Patricia Morales',
                'from_email' => 'patricia.morales@corp.cl',
                'from_avatar' => 'https://i.pravatar.cc/100?u=patricia',
                'content' => 'Estimados, mi contrato vence el próximo mes. Me gustaría conocer las opciones de renovación y posibles descuentos.',
                'created_at' => '2025-10-21 10:15:00',
                'has_attachments' => false,
                'priority' => 'medium',
                'status' => 'open',
            ],
        ];

        // Filtrar por caso actual si allCases = false
        if (! $this->allCases) {
            $allCommunications = array_filter($allCommunications, function ($comm) {
                return $comm['case_id'] == $this->caseId;
            });
        }

        // Ordenar por fecha (más recientes primero)
        usort($allCommunications, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Paginar
        $offset = ($page - 1) * $this->perPage;

        return array_slice($allCommunications, $offset, $this->perPage);
    }

    public function getChannelIcon($type): string
    {
        return match ($type) {
            'email' => 'o-envelope',
            'whatsapp' => 'o-chat-bubble-oval-left',
            'sms' => 'o-device-phone-mobile',
            'phone' => 'o-phone',
            'webchat' => 'o-computer-desktop',
            default => 'o-chat-bubble-left'
        };
    }

    public function getChannelColor($type): string
    {
        return match ($type) {
            'email' => 'text-blue-600',
            'whatsapp' => 'text-green-600',
            'sms' => 'text-purple-600',
            'phone' => 'text-orange-600',
            'webchat' => 'text-indigo-600',
            default => 'text-gray-600'
        };
    }

    public function getPriorityBadge($priority): array
    {
        return match ($priority) {
            'urgent' => ['class' => 'badge-error', 'label' => 'Urgente'],
            'high' => ['class' => 'badge-warning', 'label' => 'Alta'],
            'medium' => ['class' => 'badge-info', 'label' => 'Media'],
            'low' => ['class' => 'badge-success', 'label' => 'Baja'],
            default => ['class' => 'badge-ghost', 'label' => 'Normal']
        };
    }

    public function getStatusBadge($status): array
    {
        return match ($status) {
            'open' => ['class' => 'badge-primary', 'label' => 'Abierto'],
            'in_progress' => ['class' => 'badge-info', 'label' => 'En Progreso'],
            'escalated' => ['class' => 'badge-warning', 'label' => 'Escalado'],
            'resolved' => ['class' => 'badge-success', 'label' => 'Resuelto'],
            'closed' => ['class' => 'badge-ghost', 'label' => 'Cerrado'],
            default => ['class' => 'badge-ghost', 'label' => 'Desconocido']
        };
    }
}; ?>

<div>
    <x-card>
        <x-slot:menu>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-icon name="o-clock" class="w-5 h-5 text-primary" />
                    <span class="font-medium">Timeline Comunicaciones</span>
                    <x-badge value="{{ count($communications) }}" class="badge-xs badge-primary" />
                </div>
                
                {{-- Toggle para mostrar todos los casos --}}
                <x-toggle 
                    wire:model.live="allCases" 
                    wire:change="toggleScope"
                    class="toggle-sm toggle-primary" 
                    hint="{{ $allCases ? 'Todos los casos' : 'Solo caso actual' }}"
                />
            </div>
        </x-slot:menu>

        <div class="space-y-1 max-h-[500px] overflow-y-auto" id="timeline-container">
            @php
                $timelineItems = collect($communications)->map(function($comm) {
                    $priorityBadge = $this->getPriorityBadge($comm['priority']);
                    $statusBadge = $this->getStatusBadge($comm['status']);
                    
                    return [
                        'id' => $comm['id'],
                        'icon' => $this->getChannelIcon($comm['type']),
                        'title' => $comm['from'],
                        'subtitle' => $comm['case_title'] ?? 'Sin título',
                        'description' => $comm['content'],
                        'avatar' => $comm['from_avatar'],
                        'time' => \Carbon\Carbon::parse($comm['created_at'])->format('H:i'),
                        'date' => \Carbon\Carbon::parse($comm['created_at'])->format('d/m'),
                        'pending' => $comm['direction'] === 'inbound' && in_array($comm['status'], ['open', 'in_progress']),
                        'first' => $loop->first ?? false,
                        'last' => $loop->last ?? false,
                        'type' => $comm['type'],
                        'direction' => $comm['direction'],
                        'case_id' => $comm['case_id'],
                        'has_attachments' => $comm['has_attachments'],
                        'priority' => $priorityBadge,
                        'status' => $statusBadge,
                    ];
                });
            @endphp

            {{-- Timeline usando Mary UI --}}
            <x-timeline>
                @foreach($timelineItems as $item)
                    <x-timeline-item 
                        :icon="$item['icon']" 
                        :pending="$item['pending']"
                        :first="$item['first']"
                        :last="$item['last']"
                        class="{{ $this->getChannelColor($item['type']) }}"
                    >
                        {{-- Header del item --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <x-avatar 
                                    image="{{ $item['avatar'] }}" 
                                    class="w-8 h-8 shrink-0"
                                />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-medium text-sm">{{ $item['title'] }}</span>
                                        @if($allCases && $item['case_id'] != $caseId)
                                            <x-badge 
                                                value="#{{ $item['case_id'] }}" 
                                                class="badge-xs badge-outline"
                                            />
                                        @endif
                                        <x-badge 
                                            value="{{ $item['priority']['label'] }}" 
                                            class="badge-xs {{ $item['priority']['class'] }}"
                                        />
                                        <x-badge 
                                            value="{{ $item['status']['label'] }}" 
                                            class="badge-xs {{ $item['status']['class'] }}"
                                        />
                                    </div>
                                    
                                    @if($allCases)
                                        <p class="text-xs text-gray-600 truncate">{{ $item['subtitle'] }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="text-right shrink-0">
                                <div class="text-xs text-gray-500">{{ $item['time'] }}</div>
                                <div class="text-xs text-gray-400">{{ $item['date'] }}</div>
                            </div>
                        </div>

                        {{-- Contenido del mensaje --}}
                        <div class="mt-3">
                            <div class="text-sm p-3 rounded-lg {{ $item['direction'] === 'outbound' ? 'bg-primary/10 border-l-2 border-primary' : 'bg-base-200' }}">
                                {{ $item['description'] }}
                                
                                @if($item['has_attachments'])
                                    <div class="flex items-center gap-1 mt-2 pt-2 border-t border-base-300">
                                        <x-icon name="o-paper-clip" class="w-3 h-3 text-gray-400" />
                                        <span class="text-xs text-gray-500">Tiene adjuntos</span>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Acciones rápidas --}}
                            <div class="flex gap-3 mt-2">
                                @if($item['direction'] === 'inbound' && in_array($item['status']['label'], ['Abierto', 'En Progreso']))
                                    <button class="text-xs text-primary hover:underline">
                                        <x-icon name="o-arrow-uturn-left" class="w-3 h-3 inline mr-1" />
                                        Responder
                                    </button>
                                @endif
                                
                                @if($item['case_id'] != $caseId)
                                    <button 
                                        class="text-xs text-info hover:underline"
                                        onclick="window.location.href='/case/{{ $item['case_id'] }}/{{ $item['type'] }}'"
                                    >
                                        <x-icon name="o-arrow-top-right-on-square" class="w-3 h-3 inline mr-1" />
                                        Ver caso
                                    </button>
                                @endif
                                
                                <button class="text-xs text-gray-500 hover:underline">
                                    <x-icon name="o-eye" class="w-3 h-3 inline mr-1" />
                                    Detalles
                                </button>
                            </div>
                        </div>
                    </x-timeline-item>
                @endforeach
            </x-timeline>

            {{-- Mensaje cuando no hay comunicaciones --}}
            @if(empty($communications))
                <div class="text-center py-12">
                    <x-icon name="o-chat-bubble-left" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                    <h3 class="font-medium text-gray-600 mb-2">No hay comunicaciones</h3>
                    <p class="text-sm text-gray-500">
                        {{ $allCases ? 'No se encontraron comunicaciones en el sistema' : 'No hay comunicaciones para este caso' }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Acciones del timeline --}}
        <div class="pt-4 border-t mt-4">
            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    <x-button 
                        label="Actualizar"
                        class="btn-xs btn-outline"
                        icon="o-arrow-path"
                        wire:click="loadInitialCommunications"
                        spinner="loadInitialCommunications"
                    />
                    
                    @if($allCases)
                        <x-button 
                            label="Solo este caso"
                            class="btn-xs btn-outline"
                            icon="o-funnel"
                            wire:click="toggleScope"
                        />
                    @else
                        <x-button 
                            label="Ver todos"
                            class="btn-xs btn-outline"
                            icon="o-squares-2x2"
                            wire:click="toggleScope"
                        />
                    @endif
                </div>
                
                {{-- Botón de cargar más --}}
                @if($hasMore)
                    <x-button 
                        label="Cargar más"
                        class="btn-xs btn-primary"
                        icon="o-chevron-down"
                        wire:click="loadMore"
                        spinner="loadMore"
                    />
                @endif
            </div>
            
            {{-- Indicador de total --}}
            <div class="text-center mt-3">
                <span class="text-xs text-gray-500">
                    Mostrando {{ count($communications) }} comunicaciones
                    @if(!$hasMore)
                        (todas las disponibles)
                    @endif
                </span>
            </div>
        </div>
    </x-card>
</div>