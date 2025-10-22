<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $caseId;
    public $caseData;

    public function mount($caseId, $caseData = null)
    {
        $this->caseId = $caseId;
        $this->caseData = $caseData;
    }
}; ?>

<div>
    <x-header 
        title="Gestionar Llamada Telefónica" 
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

    <div class="max-w-4xl mx-auto">
        <div class="text-center py-24">
            <x-icon name="o-phone" class="w-24 h-24 mx-auto text-gray-300 mb-6" />
            <h2 class="text-2xl font-bold text-gray-700 mb-4">Gestión Telefónica en Construcción</h2>
            <p class="text-gray-600 mb-8 max-w-md mx-auto">
                Esta funcionalidad estará disponible próximamente. 
                Podrás gestionar llamadas telefónicas y registrar notas desde aquí.
            </p>
            
            <div class="space-y-4">
                <livewire:shared.case-header :case-data="$caseData" />
            </div>
        </div>
    </div>
</div>