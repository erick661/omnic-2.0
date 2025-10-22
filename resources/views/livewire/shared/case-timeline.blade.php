<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $caseId;
    public $communications = [];

    public function mount($caseId)
    {
        $this->caseId = $caseId;
        $this->loadCommunications();
    }

    public function loadCommunications()
    {
        // Simulación de comunicaciones del caso
        $this->communications = [
            [
                'id' => 1,
                'type' => 'email',
                'direction' => 'inbound',
                'from' => 'Cliente',
                'from_avatar' => 'https://i.pravatar.cc/100?u=cliente',
                'content' => 'Estimados, tengo un problema relacionado con este tema. Podrían ayudarme a resolverlo? Es importante para mí.',
                'created_at' => '2024-06-01 09:30:00',
                'has_attachments' => false,
            ],
            [
                'id' => 2,
                'type' => 'email',
                'direction' => 'outbound',
                'from' => auth()->user()->name ?? 'Juan Pérez',
                'from_avatar' => 'https://i.pravatar.cc/100?u=agent',
                'content' => 'Gracias por contactarnos. Hemos revisado tu caso y estamos trabajando en una solución. Te mantendremos informado.',
                'created_at' => '2024-06-01 11:15:00',
                'has_attachments' => true,
            ],
            [
                'id' => 3,
                'type' => 'whatsapp',
                'direction' => 'inbound',
                'from' => 'Cliente',
                'from_avatar' => 'https://i.pravatar.cc/100?u=cliente',
                'content' => 'Hola! Quería hacer un seguimiento del caso. ¿Hay alguna actualización?',
                'created_at' => '2024-06-01 14:20:00',
                'has_attachments' => false,
            ]
        ];
    }

    public function getChannelIcon($type): string
    {
        return match($type) {
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
        return match($type) {
            'email' => 'text-blue-600',
            'whatsapp' => 'text-green-600',
            'sms' => 'text-purple-600',
            'phone' => 'text-orange-600',
            'webchat' => 'text-indigo-600',
            default => 'text-gray-600'
        };
    }
}; ?>

<div>
    <x-card>
        <x-slot:menu>
            <div class="flex items-center gap-2">
                <x-icon name="o-clock" class="w-5 h-5 text-primary" />
                <span class="font-medium">Timeline</span>
                <x-badge value="{{ count($communications) }}" class="badge-xs badge-primary" />
            </div>
        </x-slot:menu>

        <div class="space-y-4 max-h-96 overflow-y-auto">
            @forelse($communications as $comm)
                <div class="flex gap-3 {{ $comm['direction'] === 'outbound' ? 'flex-row-reverse' : '' }}">
                    {{-- Avatar --}}
                    <x-avatar 
                        image="{{ $comm['from_avatar'] }}" 
                        class="!w-10 !h-10 flex-shrink-0"
                    />
                    
                    {{-- Contenido --}}
                    <div class="flex-1 min-w-0 {{ $comm['direction'] === 'outbound' ? 'text-right' : '' }}">
                        {{-- Header del mensaje --}}
                        <div class="flex items-center gap-2 mb-2 {{ $comm['direction'] === 'outbound' ? 'justify-end' : '' }}">
                            <span class="font-medium text-sm">{{ $comm['from'] }}</span>
                            <x-icon 
                                name="{{ $this->getChannelIcon($comm['type']) }}" 
                                class="w-4 h-4 {{ $this->getChannelColor($comm['type']) }}"
                            />
                            <span class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($comm['created_at'])->format('d/m H:i') }}
                            </span>
                            @if($comm['has_attachments'])
                                <x-icon name="o-paper-clip" class="w-3 h-3 text-gray-400" />
                            @endif
                        </div>

                        {{-- Contenido del mensaje --}}
                        <div class="text-sm bg-base-200 p-3 rounded-lg {{ $comm['direction'] === 'outbound' ? 'bg-primary/10' : '' }}">
                            {{ $comm['content'] }}
                        </div>

                        {{-- Acciones rápidas --}}
                        <div class="flex gap-1 mt-2 text-xs {{ $comm['direction'] === 'outbound' ? 'justify-end' : '' }}">
                            @if($comm['direction'] === 'inbound')
                                <button class="text-primary hover:underline">Responder</button>
                                <span class="text-gray-300">•</span>
                                <button class="text-gray-500 hover:underline">Transferir</button>
                            @endif
                            <button class="text-gray-500 hover:underline">Ver detalles</button>
                        </div>
                    </div>
                </div>

                {{-- Separador temporal --}}
                @if(!$loop->last)
                    <div class="flex items-center gap-3">
                        <div class="flex-1 border-t border-base-300"></div>
                        @if($loop->index < count($communications) - 1)
                            <span class="text-xs text-gray-400 bg-base-100 px-2">
                                {{ \Carbon\Carbon::parse($communications[$loop->index + 1]['created_at'])->diffForHumans(\Carbon\Carbon::parse($comm['created_at'])) }}
                            </span>
                        @endif
                        <div class="flex-1 border-t border-base-300"></div>
                    </div>
                @endif

            @empty
                <div class="text-center py-8">
                    <x-icon name="o-chat-bubble-left" class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                    <p class="text-gray-500">No hay comunicaciones registradas</p>
                </div>
            @endforelse
        </div>

        {{-- Acciones del timeline --}}
        <div class="pt-4 border-t">
            <div class="flex gap-2">
                <x-button 
                    label="Actualizar"
                    class="btn-xs btn-outline"
                    icon="o-arrow-path"
                    wire:click="loadCommunications"
                />
                <x-button 
                    label="Ver todos"
                    class="btn-xs btn-outline"
                    icon="o-eye"
                />
            </div>
        </div>
    </x-card>
</div>