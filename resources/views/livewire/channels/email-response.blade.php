<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $caseId;
    public $caseData;
    public $to = '';
    public $subject = '';
    public $message = '';
    public $attachments = [];
    public $cc = '';
    public $bcc = '';
    public $priority = 'normal';
    public $template = null;

    public function mount($caseId, $caseData = null)
    {
        $this->caseId = $caseId;
        $this->caseData = $caseData;
        
        if ($caseData) {
            $this->to = 'cliente@empresa.com'; // Simular email del cliente
            $this->subject = 'Re: ' . $caseData['subject'];
            $this->loadEmailTemplate();
        }
    }

    public function loadEmailTemplate()
    {
        $this->message = "Estimado cliente,\n\n";
        $this->message .= "Gracias por contactarnos. Hemos revisado tu caso y estamos trabajando en una solución.\n";
        $this->message .= "Te mantendremos informado del progreso.\n\n";
        $this->message .= "Si tienes alguna pregunta adicional, no dudes en contactarnos.\n\n";
        $this->message .= "Saludos cordiales,\n";
        $this->message .= auth()->user()->name ?? 'Equipo de Soporte';
    }

    public function addAttachment()
    {
        // Lógica para agregar archivos adjuntos
    }

    public function removeAttachment($index)
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function sendEmail()
    {
        $this->validate([
            'to' => 'required|email',
            'subject' => 'required|min:5',
            'message' => 'required|min:10',
        ]);

        try {
            // Crear email en bandeja de salida usando el servicio
            $outboxService = app(\App\Services\OutboxEmailService::class);
            
            $emailData = [
                'case_id' => $this->caseId,
                'from_email' => auth()->user()->email ?? 'comunicaciones@orproverificaciones.cl',
                'from_name' => auth()->user()->name ?? 'Sistema OMNIC',
                'to' => $this->to,
                'cc' => $this->cc,
                'bcc' => $this->bcc,
                'subject' => $this->subject,
                'message' => $this->message,
                'priority' => $this->priority,
            ];

            $outboxEmail = $outboxService->createReply($emailData);

            logger()->info('Email creado en bandeja de salida', [
                'outbox_email_id' => $outboxEmail->id,
                'case_id' => $this->caseId,
                'to' => $this->to,
                'subject' => $this->subject
            ]);

            $this->dispatch('email-queued', $this->caseId);
            $this->dispatch('notify', 'Email agregado a bandeja de salida - Se enviará automáticamente', 'success');
            
            return redirect()->route('agente');

        } catch (\Exception $e) {
            logger()->error('Error creando email de salida', [
                'error' => $e->getMessage(),
                'case_id' => $this->caseId
            ]);

            $this->dispatch('notify', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function saveDraft()
    {
        // Lógica para guardar borrador
        $this->dispatch('notify', 'Borrador guardado', 'info');
    }

    public function loadTemplate($type)
    {
        switch ($type) {
            case 'request_info':
                $this->subject = 'Re: Información adicional requerida - Caso #' . $this->caseId;
                $this->message = "Estimado cliente,\n\n";
                $this->message .= "Hemos recibido su consulta y necesitamos información adicional para poder ayudarle de la mejor manera.\n\n";
                $this->message .= "Por favor, proporcione los siguientes datos:\n";
                $this->message .= "• [Especificar información necesaria]\n";
                $this->message .= "• [Detalles adicionales]\n\n";
                $this->message .= "Quedamos a la espera de su respuesta.\n\n";
                $this->message .= "Atentamente,\n" . (auth()->user()->name ?? 'Equipo de Soporte');
                break;

            case 'confirm_resolution':
                $this->subject = 'Re: Confirmación de resolución - Caso #' . $this->caseId;
                $this->message = "Estimado cliente,\n\n";
                $this->message .= "Nos complace informarle que hemos resuelto su consulta.\n\n";
                $this->message .= "La solución implementada es:\n";
                $this->message .= "[Describir la solución]\n\n";
                $this->message .= "Si considera que su problema ha sido resuelto satisfactoriamente, puede confirmar el cierre del caso respondiendo a este email.\n\n";
                $this->message .= "Si tiene alguna duda adicional, no dude en contactarnos.\n\n";
                $this->message .= "Saludos cordiales,\n" . (auth()->user()->name ?? 'Equipo de Soporte');
                break;

            case 'escalate':
                $this->subject = 'Re: Escalamiento a supervisor - Caso #' . $this->caseId;
                $this->message = "Estimado cliente,\n\n";
                $this->message .= "Su caso ha sido escalado a nuestro equipo de supervisión para una revisión más detallada.\n\n";
                $this->message .= "Un supervisor se pondrá en contacto con usted dentro de las próximas 24 horas para revisar su situación.\n\n";
                $this->message .= "Apreciamos su paciencia mientras trabajamos en una solución integral.\n\n";
                $this->message .= "Atentamente,\n" . (auth()->user()->name ?? 'Equipo de Soporte');
                break;

            case 'close_case':
                $this->subject = 'Re: Cierre del caso #' . $this->caseId;
                $this->message = "Estimado cliente,\n\n";
                $this->message .= "Esperamos que su consulta haya sido resuelta satisfactoriamente.\n\n";
                $this->message .= "Procedemos a cerrar este caso. Si en el futuro necesita asistencia adicional, no dude en contactarnos.\n\n";
                $this->message .= "Gracias por confiar en nuestro servicio.\n\n";
                $this->message .= "Saludos cordiales,\n" . (auth()->user()->name ?? 'Equipo de Soporte');
                break;

            default:
                $this->loadEmailTemplate();
                break;
        }

        $this->dispatch('notify', 'Plantilla cargada', 'success');
    }
}; ?>

<div>
    <x-header 
        title="Responder por Email" 
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
            
            {{-- Columna Principal: Formulario de Email --}}
            <div class="xl:col-span-2">
                <x-card>
                    <x-slot:menu>
                        <div class="flex items-center gap-2">
                            <x-icon name="o-envelope" class="w-5 h-5 text-primary" />
                            <span class="font-medium">Redactar Email</span>
                        </div>
                    </x-slot:menu>

                    <div class="space-y-6">
                        {{-- Destinatarios --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input 
                                label="Para:" 
                                wire:model="to"
                                placeholder="destinatario@ejemplo.com"
                                icon="o-envelope"
                                hint="Email del destinatario principal"
                            />
                            
                            <x-input 
                                label="CC (opcional):" 
                                wire:model="cc"
                                placeholder="copia@ejemplo.com"
                                icon="o-users"
                            />
                        </div>

                        <x-input 
                            label="BCC (opcional):" 
                            wire:model="bcc"
                            placeholder="copia-oculta@ejemplo.com"
                            icon="o-eye-slash"
                        />

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <x-input 
                                    label="Asunto:" 
                                    wire:model="subject"
                                    placeholder="Ingresa el asunto del email"
                                    icon="o-document-text"
                                />
                            </div>
                            
                            <x-select 
                                label="Prioridad:"
                                wire:model="priority"
                                :options="[
                                    ['id' => 'low', 'name' => 'Baja'],
                                    ['id' => 'normal', 'name' => 'Normal'],
                                    ['id' => 'high', 'name' => 'Alta'],
                                    ['id' => 'urgent', 'name' => 'Urgente']
                                ]"
                                option-value="id"
                                option-label="name"
                            />
                        </div>

                        {{-- Editor de Mensaje --}}
                        <div>
                            <x-textarea 
                                label="Mensaje:" 
                                wire:model="message"
                                placeholder="Escribe tu mensaje aquí..."
                                rows="15"
                                hint="Mínimo 10 caracteres"
                            />
                            
                            {{-- Barra de herramientas de formato --}}
                            <div class="mt-2 flex flex-wrap gap-2 p-3 bg-base-200 rounded-lg">
                                <x-button icon="o-bold" class="btn-xs btn-ghost" tooltip="Negrita (Ctrl+B)" />
                                <x-button icon="o-italic" class="btn-xs btn-ghost" tooltip="Cursiva (Ctrl+I)" />
                                <x-button icon="o-underline" class="btn-xs btn-ghost" tooltip="Subrayado" />
                                <div class="divider divider-horizontal"></div>
                                <x-button icon="o-link" class="btn-xs btn-ghost" tooltip="Insertar enlace" />
                                <x-button icon="o-list-bullet" class="btn-xs btn-ghost" tooltip="Lista con viñetas" />
                                <x-button icon="o-numbered-list" class="btn-xs btn-ghost" tooltip="Lista numerada" />
                                <div class="divider divider-horizontal"></div>
                                <x-button wire:click="loadEmailTemplate" icon="o-document-duplicate" class="btn-xs btn-outline" tooltip="Cargar plantilla" />
                            </div>
                        </div>

                        {{-- Gestión de Adjuntos --}}
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Archivos adjuntos:</span>
                                <x-button 
                                    icon="o-paper-clip" 
                                    class="btn-xs btn-outline"
                                    wire:click="addAttachment"
                                >
                                    Adjuntar archivo
                                </x-button>
                            </label>
                            
                            @if(count($attachments) > 0)
                                <div class="space-y-2">
                                    @foreach($attachments as $index => $attachment)
                                        <div class="flex items-center justify-between p-3 border border-base-300 rounded">
                                            <div class="flex items-center gap-2">
                                                <x-icon name="o-document" class="w-4 h-4" />
                                                <span class="text-sm">archivo_{{ $index + 1 }}.pdf</span>
                                                <x-badge value="2.5MB" class="badge-xs" />
                                            </div>
                                            <x-button 
                                                wire:click="removeAttachment({{ $index }})"
                                                icon="o-trash" 
                                                class="btn-xs btn-ghost text-error"
                                            />
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 mt-2">No hay archivos adjuntos</p>
                            @endif
                        </div>

                        {{-- Acciones principales --}}
                        <div class="flex flex-wrap gap-3 pt-4 border-t">
                            <x-button 
                                wire:click="sendEmail"
                                label="Enviar Email" 
                                class="btn-primary flex-1 md:flex-initial"
                                icon="o-paper-airplane"
                                spinner="sendEmail"
                            />
                            
                            <x-button 
                                wire:click="saveDraft"
                                label="Guardar Borrador" 
                                class="btn-outline"
                                icon="o-bookmark"
                                spinner="saveDraft"
                            />
                            
                            <div class="dropdown dropdown-end">
                                <div tabindex="0" role="button" class="btn btn-outline btn-sm">
                                    <x-icon name="o-ellipsis-horizontal" class="w-4 h-4" />
                                </div>
                                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow">
                                    <li><a><x-icon name="o-clock" class="w-4 h-4" />Programar envío</a></li>
                                    <li><a><x-icon name="o-document-duplicate" class="w-4 h-4" />Usar plantilla</a></li>
                                    <li><a><x-icon name="o-eye" class="w-4 h-4" />Vista previa</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- Columna Lateral: Información del Caso y Timeline --}}
            <div class="space-y-6">
                {{-- Información del Caso --}}
                <livewire:shared.case-header :case-data="$caseData" />
                
                {{-- Timeline de Comunicaciones --}}
                <livewire:shared.case-timeline :case-id="$caseId" />

                {{-- Plantillas rápidas --}}
                <x-card title="Respuestas rápidas">
                    <div class="space-y-2">
                        <x-button 
                            label="Solicitar más información"
                            class="btn-sm btn-outline w-full justify-start"
                            wire:click="loadTemplate('request_info')"
                        />
                        <x-button 
                            label="Confirmar resolución"
                            class="btn-sm btn-outline w-full justify-start"
                            wire:click="loadTemplate('confirm_resolution')"
                        />
                        <x-button 
                            label="Escalamiento a supervisor"
                            class="btn-sm btn-outline w-full justify-start"
                            wire:click="loadTemplate('escalate')"
                        />
                        <x-button 
                            label="Cerrar caso"
                            class="btn-sm btn-outline w-full justify-start"
                            wire:click="loadTemplate('close_case')"
                        />
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</div>