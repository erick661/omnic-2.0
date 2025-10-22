<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $caseId;
    public $caseData;
    public $message = '';
    public $phoneNumber = '';
    public $messageType = 'text'; // text, template, media

    public function mount($caseId, $caseData = null)
    {
        $this->caseId = $caseId;
        $this->caseData = $caseData;
        
        if ($caseData) {
            $this->phoneNumber = '+56912345678'; // Simular teléfono del cliente
        }
    }

    public function sendWhatsApp()
    {
        $this->validate([
            'phoneNumber' => 'required',
            'message' => 'required|min:1',
        ]);

        // Lógica para enviar WhatsApp
        logger()->info('WhatsApp enviado', [
            'case_id' => $this->caseId,
            'phone' => $this->phoneNumber,
            'message' => $this->message
        ]);

        $this->dispatch('whatsapp-sent', $this->caseId);
        $this->dispatch('notify', 'Mensaje de WhatsApp enviado', 'success');
        
        return redirect()->route('agente');
    }

    public function loadTemplate($template)
    {
        $templates = [
            'greeting' => 'Hola! Gracias por contactarnos. ¿En qué podemos ayudarte?',
            'follow_up' => 'Hola! Te contactamos para hacer seguimiento a tu consulta. ¿Tienes alguna pregunta adicional?',
            'resolution' => 'Hola! Hemos resuelto tu caso. ¿Podrías confirmar si todo está funcionando correctamente?',
            'escalation' => 'Hola! Hemos escalado tu caso a nuestro equipo especializado. Te contactarán pronto.',
        ];

        $this->message = $templates[$template] ?? '';
    }
}; ?>

<div>
    <x-header 
        title="Responder por WhatsApp" 
        subtitle="Caso #{{ $this->caseId }} • {{ $this->caseData['subject'] ?? 'Sin asunto' }}"
        separator 
    >
        <x-slot:actions>
            <x-button 
                label="Volver" 
                link="/agente" 
                icon="o-arrow-left"
                class="btn-ghost"
            />
        </x-slot:actions>
    </x-header>

    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            
            {{-- Columna Principal: Interface WhatsApp --}}
            <div class="xl:col-span-2">
                <x-card>
                    <x-slot:menu>
                        <div class="flex items-center gap-2">
                            <x-icon name="o-chat-bubble-oval-left" class="w-5 h-5 text-green-600" />
                            <span class="font-medium">WhatsApp Business</span>
                        </div>
                    </x-slot:menu>

                    <div class="space-y-6">
                        {{-- Información del contacto --}}
                        <div class="flex items-center gap-4 p-4 bg-green-50 rounded-lg border border-green-200">
                            <x-avatar 
                                image="https://i.pravatar.cc/100?u=cliente" 
                                class="!w-12 !h-12"
                            />
                            <div class="flex-1">
                                <h3 class="font-semibold">Cliente</h3>
                                <p class="text-sm text-green-700">{{ $phoneNumber }}</p>
                                <div class="flex items-center gap-1 text-xs text-green-600 mt-1">
                                    <x-icon name="o-check-circle" class="w-3 h-3" />
                                    <span>WhatsApp Business verificado</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <x-badge value="En línea" class="badge-success badge-xs" />
                                <p class="text-xs text-green-600 mt-1">Última vez: hace 2 min</p>
                            </div>
                        </div>

                        {{-- Simulación de chat --}}
                        <div class="bg-chat-pattern bg-gray-50 p-4 rounded-lg border min-h-64">
                            <div class="space-y-4">
                                {{-- Mensaje del cliente (entrada) --}}
                                <div class="flex justify-start">
                                    <div class="bg-white p-3 rounded-lg shadow-sm border max-w-xs">
                                        <p class="text-sm">{{ $caseData['subject'] ?? 'Necesito ayuda con mi consulta' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ \Carbon\Carbon::parse($caseData['assigned_at'] ?? now())->format('H:i') }}
                                            <x-icon name="o-check" class="w-3 h-3 inline ml-1" />
                                        </p>
                                    </div>
                                </div>

                                {{-- Indicador de escritura --}}
                                <div class="flex justify-start" x-data="{ typing: true }" x-show="typing">
                                    <div class="bg-white p-3 rounded-lg shadow-sm border">
                                        <div class="flex items-center gap-1">
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Vista previa del mensaje a enviar --}}
                                @if($message)
                                    <div class="flex justify-end">
                                        <div class="bg-green-500 text-white p-3 rounded-lg shadow-sm max-w-xs">
                                            <p class="text-sm">{{ $message }}</p>
                                            <div class="flex items-center justify-end gap-1 mt-1">
                                                <p class="text-xs text-green-100">
                                                    {{ now()->format('H:i') }}
                                                </p>
                                                <x-icon name="o-clock" class="w-3 h-3 text-green-200" />
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Área de composición --}}
                        <div class="space-y-4">
                            <x-textarea 
                                wire:model.live="message"
                                placeholder="Escribe tu mensaje..."
                                rows="4"
                                class="resize-none"
                            />

                            {{-- Herramientas de WhatsApp --}}
                            <div class="flex flex-wrap gap-2 p-3 bg-green-50 rounded-lg">
                                <x-button 
                                    icon="o-face-smile" 
                                    class="btn-xs btn-ghost" 
                                    tooltip="Emojis" 
                                />
                                <x-button 
                                    icon="o-paper-clip" 
                                    class="btn-xs btn-ghost" 
                                    tooltip="Adjuntar archivo" 
                                />
                                <x-button 
                                    icon="o-camera" 
                                    class="btn-xs btn-ghost" 
                                    tooltip="Enviar foto" 
                                />
                                <x-button 
                                    icon="o-microphone" 
                                    class="btn-xs btn-ghost" 
                                    tooltip="Mensaje de voz" 
                                />
                                <div class="divider divider-horizontal"></div>
                                <x-button 
                                    icon="o-document-duplicate" 
                                    class="btn-xs btn-outline" 
                                    tooltip="Usar plantilla"
                                />
                            </div>

                            {{-- Botón de envío --}}
                            <div class="flex gap-3">
                                <x-button 
                                    wire:click="sendWhatsApp"
                                    label="Enviar Mensaje" 
                                    class="btn-success flex-1"
                                    icon="o-paper-airplane"
                                    spinner="sendWhatsApp"
                                />
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- Columna Lateral --}}
            <div class="space-y-6">
                {{-- Información del Caso --}}
                <livewire:shared.case-header :case-data="$caseData" />
                
                {{-- Timeline de Comunicaciones --}}
                <livewire:shared.case-timeline :case-id="$caseId" />

                {{-- Plantillas rápidas de WhatsApp --}}
                <x-card title="Respuestas rápidas">
                    <div class="space-y-2">
                        <x-button 
                            label="Saludo inicial"
                            class="btn-sm btn-outline w-full justify-start"
                            wire:click="loadTemplate('greeting')"
                        />
                        <x-button 
                            label="Seguimiento"
                            class="btn-sm btn-outline w-full justify-start"
                            wire:click="loadTemplate('follow_up')"
                        />
                        <x-button 
                            label="Confirmación de resolución"
                            class="btn-sm btn-outline w-full justify-start"
                            wire:click="loadTemplate('resolution')"
                        />
                        <x-button 
                            label="Escalamiento"
                            class="btn-sm btn-outline w-full justify-start"
                            wire:click="loadTemplate('escalation')"
                        />
                    </div>
                </x-card>

                {{-- Información del contacto --}}
                <x-card title="Información del contacto">
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="font-medium text-gray-600">Teléfono:</span>
                            <p>{{ $phoneNumber }}</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Estado:</span>
                            <p class="flex items-center gap-1">
                                <x-icon name="o-signal" class="w-3 h-3 text-green-600" />
                                En línea
                            </p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Último mensaje:</span>
                            <p>Hace 5 minutos</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Zona horaria:</span>
                            <p>Chile (UTC-3)</p>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</div>