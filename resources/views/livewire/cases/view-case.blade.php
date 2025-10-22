<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $caseId;

    public $caseData;

    public $communications = [];

    public $lastUsedChannel = 'email'; // Canal usado más recientemente

    public function mount($caseId)
    {
        $this->caseId = $caseId;
        $this->loadCaseData();
        $this->loadCommunications();
        $this->detectLastUsedChannel();
    }

    public function loadCaseData()
    {
        // Simulación de datos del caso
        $this->caseData = [
            'id' => $this->caseId,
            'title' => 'Problema con facturación mensual',
            'description' => 'El cliente reporta discrepancias en su facturación del mes actual. El monto facturado no corresponde con lo acordado en el contrato firmado el año pasado.',
            'status' => 'in_progress',
            'priority' => 'high',
            'category' => 'billing',
            'created_at' => '2025-10-22 09:30:00',
            'updated_at' => '2025-10-22 14:20:00',
            'assigned_to' => auth()->user()->name ?? 'Juan Pérez',
            'customer' => [
                'name' => 'María González',
                'email' => 'maria.gonzalez@empresa.com',
                'phone' => '+56912345678',
                'company' => 'Empresa Ejemplo S.A.',
                'avatar' => 'https://i.pravatar.cc/100?u=maria',
            ],
            'tags' => ['facturación', 'contrato', 'urgente'],
            'estimated_resolution' => '2025-10-23 17:00:00',
        ];
    }

    public function loadCommunications()
    {
        // Simulación de comunicaciones específicas del caso
        $this->communications = [
            [
                'id' => 1,
                'type' => 'email',
                'direction' => 'inbound',
                'from' => $this->caseData['customer']['name'],
                'from_email' => $this->caseData['customer']['email'],
                'from_avatar' => $this->caseData['customer']['avatar'],
                'subject' => 'Problema con facturación mensual',
                'content' => 'Estimados, tengo un problema con la facturación de este mes. El monto no corresponde con lo acordado en el contrato. Necesito una revisión urgente de mi cuenta.',
                'created_at' => '2025-10-22 09:30:00',
                'has_attachments' => true,
                'attachments' => ['contrato_original.pdf', 'factura_actual.pdf'],
                'priority' => 'high',
                'status' => 'open',
            ],
            [
                'id' => 2,
                'type' => 'email',
                'direction' => 'outbound',
                'from' => auth()->user()->name ?? 'Juan Pérez',
                'from_email' => 'juan.perez@orpro.cl',
                'from_avatar' => 'https://i.pravatar.cc/100?u=agent1',
                'subject' => 'Re: Problema con facturación mensual',
                'content' => 'Estimada María, gracias por contactarnos. Hemos recibido su consulta y estamos revisando su caso. Le responderemos dentro de las próximas 24 horas con una solución.',
                'created_at' => '2025-10-22 11:15:00',
                'has_attachments' => false,
                'priority' => 'high',
                'status' => 'in_progress',
            ],
            [
                'id' => 3,
                'type' => 'whatsapp',
                'direction' => 'inbound',
                'from' => $this->caseData['customer']['name'],
                'from_phone' => $this->caseData['customer']['phone'],
                'from_avatar' => $this->caseData['customer']['avatar'],
                'content' => 'Hola! Quería hacer un seguimiento del caso de facturación que envié por email esta mañana. ¿Han podido revisar los documentos?',
                'created_at' => '2025-10-22 14:20:00',
                'has_attachments' => false,
                'priority' => 'high',
                'status' => 'in_progress',
            ],
        ];
    }

    public function detectLastUsedChannel()
    {
        if (! empty($this->communications)) {
            // Ordenar por fecha más reciente
            $sorted = collect($this->communications)->sortByDesc('created_at');
            $lastComm = $sorted->first();
            $this->lastUsedChannel = $lastComm['type'];
        }
    }

    public function respondWith($channel)
    {
        return redirect()->route('case.'.$channel, ['caseId' => $this->caseId]);
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

    public function getChannelBg($type): string
    {
        return match ($type) {
            'email' => 'bg-blue-500 hover:bg-blue-600',
            'whatsapp' => 'bg-green-500 hover:bg-green-600',
            'sms' => 'bg-purple-500 hover:bg-purple-600',
            'phone' => 'bg-orange-500 hover:bg-orange-600',
            'webchat' => 'bg-indigo-500 hover:bg-indigo-600',
            default => 'bg-gray-500 hover:bg-gray-600'
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
    <x-header 
        title="Caso #{{ $caseData['id'] }}" 
        subtitle="{{ $caseData['title'] }}"
        separator 
    >
        <x-slot:actions>
            <x-button 
                label="Volver a casos" 
                link="/agente" 
                icon="o-arrow-left"
                class="btn-ghost"
            />
        </x-slot:actions>
    </x-header>

    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            
            {{-- Columna Principal: Información del Caso --}}
            <div class="xl:col-span-2 space-y-6">
                
                {{-- Resumen del Caso --}}
                <x-card>
                    <x-slot:menu>
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-2">
                                <x-icon name="o-folder-open" class="w-5 h-5 text-primary" />
                                <span class="font-medium">Información del Caso</span>
                            </div>
                            
                            @php $priorityBadge = $this->getPriorityBadge($caseData['priority']); @endphp
                            @php $statusBadge = $this->getStatusBadge($caseData['status']); @endphp
                            
                            <div class="flex gap-2">
                                <x-badge 
                                    value="{{ $priorityBadge['label'] }}" 
                                    class="{{ $priorityBadge['class'] }}"
                                />
                                <x-badge 
                                    value="{{ $statusBadge['label'] }}" 
                                    class="{{ $statusBadge['class'] }}"
                                />
                            </div>
                        </div>
                    </x-slot:menu>

                    <div class="space-y-6">
                        {{-- Información del Cliente --}}
                        <div class="flex items-start gap-4 p-4 bg-base-200 rounded-lg">
                            <x-avatar 
                                image="{{ $caseData['customer']['avatar'] }}" 
                                class="w-16 h-16"
                            />
                            <div class="flex-1">
                                <h3 class="font-bold text-lg">{{ $caseData['customer']['name'] }}</h3>
                                <p class="text-sm text-base-content/70 mb-2">{{ $caseData['customer']['company'] }}</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="flex items-center gap-2">
                                        <x-icon name="o-envelope" class="w-4 h-4 text-blue-600" />
                                        <span class="text-sm">{{ $caseData['customer']['email'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-icon name="o-phone" class="w-4 h-4 text-green-600" />
                                        <span class="text-sm">{{ $caseData['customer']['phone'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Descripción del Caso --}}
                        <div>
                            <h4 class="font-semibold mb-3">Descripción</h4>
                            <div class="prose prose-sm max-w-none">
                                <p>{{ $caseData['description'] }}</p>
                            </div>
                        </div>

                        {{-- Tags y Categoría --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium">Tags:</span>
                            @foreach($caseData['tags'] as $tag)
                                <x-badge value="{{ $tag }}" class="badge-outline badge-sm" />
                            @endforeach
                        </div>

                        {{-- Fechas importantes --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border border-base-300 rounded-lg">
                            <div>
                                <span class="text-xs font-medium text-base-content/60 block">Creado</span>
                                <span class="text-sm">{{ \Carbon\Carbon::parse($caseData['created_at'])->format('d/m/Y H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-base-content/60 block">Actualizado</span>
                                <span class="text-sm">{{ \Carbon\Carbon::parse($caseData['updated_at'])->format('d/m/Y H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-base-content/60 block">Resolución estimada</span>
                                <span class="text-sm">{{ \Carbon\Carbon::parse($caseData['estimated_resolution'])->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </x-card>

                {{-- Comunicaciones del Caso --}}
                <x-card title="Historial de Comunicaciones">
                    <div class="space-y-6">
                        @foreach($communications as $comm)
                            <div class="flex gap-4 {{ $comm['direction'] === 'outbound' ? 'flex-row-reverse' : '' }}">
                                {{-- Avatar --}}
                                <x-avatar 
                                    image="{{ $comm['from_avatar'] }}" 
                                    class="w-10 h-10 shrink-0"
                                />
                                
                                {{-- Contenido --}}
                                <div class="flex-1 min-w-0">
                                    {{-- Header del mensaje --}}
                                    <div class="flex items-center gap-2 mb-2 {{ $comm['direction'] === 'outbound' ? 'justify-end' : '' }}">
                                        <span class="font-medium text-sm">{{ $comm['from'] }}</span>
                                        <x-icon 
                                            name="{{ $this->getChannelIcon($comm['type']) }}" 
                                            class="w-4 h-4 {{ $this->getChannelColor($comm['type']) }}"
                                        />
                                        <span class="text-xs text-base-content/60">
                                            {{ \Carbon\Carbon::parse($comm['created_at'])->format('d/m H:i') }}
                                        </span>
                                        @if($comm['has_attachments'] ?? false)
                                            <x-icon name="o-paper-clip" class="w-3 h-3 text-base-content/50" />
                                        @endif
                                    </div>

                                    {{-- Asunto (para emails) --}}
                                    @if($comm['type'] === 'email' && isset($comm['subject']))
                                        <div class="text-xs font-medium text-base-content/80 mb-2 {{ $comm['direction'] === 'outbound' ? 'text-right' : '' }}">
                                            Asunto: {{ $comm['subject'] }}
                                        </div>
                                    @endif

                                    {{-- Contenido del mensaje --}}
                                    <div class="text-sm p-3 rounded-lg {{ $comm['direction'] === 'outbound' ? 'bg-primary/10 border-l-2 border-primary ml-auto max-w-lg' : 'bg-base-200 max-w-lg' }}">
                                        {{ $comm['content'] }}
                                        
                                        {{-- Adjuntos --}}
                                        @if(isset($comm['attachments']) && $comm['attachments'])
                                            <div class="mt-3 pt-3 border-t border-base-300">
                                                <div class="text-xs font-medium text-base-content/70 mb-2">Archivos adjuntos:</div>
                                                @foreach($comm['attachments'] as $attachment)
                                                    <div class="flex items-center gap-2 text-xs text-primary hover:underline cursor-pointer">
                                                        <x-icon name="o-document" class="w-3 h-3" />
                                                        {{ $attachment }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if(!$loop->last)
                                <div class="border-t border-base-300"></div>
                            @endif
                        @endforeach
                    </div>
                </x-card>

                {{-- Botones de Respuesta por Canal --}}
                <x-card>
                    <x-slot:menu>
                        <div class="flex items-center gap-2">
                            <x-icon name="o-chat-bubble-left" class="w-5 h-5 text-primary" />
                            <span class="font-medium">Responder al Cliente</span>
                        </div>
                    </x-slot:menu>

                    <div class="space-y-4">
                        <p class="text-sm text-base-content/70">
                            Selecciona el canal por el que deseas responder. 
                            <strong>{{ ucfirst($lastUsedChannel) }}</strong> fue el último canal utilizado.
                        </p>

                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            {{-- Canal principal (último usado) --}}
                            <button 
                                wire:click="respondWith('{{ $lastUsedChannel }}')"
                                class="btn {{ $this->getChannelBg($lastUsedChannel) }} text-white border-0 flex-col h-20 relative"
                            >
                                <x-icon name="{{ $this->getChannelIcon($lastUsedChannel) }}" class="w-6 h-6 mb-1" />
                                <span class="text-xs font-medium">{{ ucfirst($lastUsedChannel) }}</span>
                                <x-badge value="Último" class="badge-xs absolute -top-1 -right-1 bg-white text-primary" />
                            </button>

                            {{-- Otros canales --}}
                            @foreach(['email', 'whatsapp', 'sms', 'phone', 'webchat'] as $channel)
                                @if($channel !== $lastUsedChannel)
                                    <button 
                                        wire:click="respondWith('{{ $channel }}')"
                                        class="btn btn-outline hover:btn-primary flex-col h-20 {{ $this->getChannelColor($channel) }}"
                                    >
                                        <x-icon name="{{ $this->getChannelIcon($channel) }}" class="w-6 h-6 mb-1" />
                                        <span class="text-xs">{{ ucfirst($channel) }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        {{-- Acciones adicionales --}}
                        <div class="flex gap-3 pt-4 border-t">
                            <x-button 
                                label="Escalar caso"
                                class="btn-warning btn-outline"
                                icon="o-arrow-trending-up"
                            />
                            <x-button 
                                label="Transferir"
                                class="btn-info btn-outline"
                                icon="o-arrow-right-circle"
                            />
                            <x-button 
                                label="Cerrar caso"
                                class="btn-error btn-outline"
                                icon="o-check-circle"
                            />
                        </div>
                    </div>
                </x-card>

            </div>

            {{-- Columna Lateral: Timeline General --}}
            <div class="space-y-6">
                {{-- Timeline de todos los casos --}}
                <livewire:shared.case-timeline :case-id="$caseId" />
            </div>
        </div>
    </div>
</div>