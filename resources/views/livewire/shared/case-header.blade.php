<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $caseData;

    public function mount($caseData)
    {
        $this->caseData = $caseData;
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
            'billing' => 'badge-info',
            'technical' => 'badge-secondary',
            'account' => 'badge-accent',
            'support' => 'badge-primary',
            default => 'badge-neutral'
        };
    }
}; ?>

<div>
    @if($caseData)
        <x-card>
            <x-slot:menu>
                <div class="flex items-center gap-2">
                    <x-icon name="o-briefcase" class="w-5 h-5 text-primary" />
                    <span class="font-medium">Información del Caso</span>
                </div>
            </x-slot:menu>

            <div class="space-y-4">
                {{-- Título y número --}}
                <div>
                    <h3 class="font-semibold text-lg line-clamp-2">{{ $caseData['subject'] }}</h3>
                    <p class="text-sm text-gray-500">
                        Caso #{{ $caseData['id'] }} • 
                        {{ \Carbon\Carbon::parse($caseData['assigned_at'])->format('d/m/Y H:i') }}
                    </p>
                </div>

                {{-- Badges de estado --}}
                <div class="flex flex-wrap gap-2">
                    <x-badge 
                        value="{{ ucfirst($caseData['status']) }}" 
                        class="badge-outline"
                    />
                    <x-badge 
                        value="{{ ucfirst($caseData['priority']) }}" 
                        class="badge-xs {{ $this->getPriorityBadgeClass($caseData['priority']) }}"
                    />
                    <x-badge 
                        value="{{ ucfirst($caseData['category']) }}" 
                        class="badge-xs {{ $this->getCategoryBadgeClass($caseData['category']) }}"
                    />
                </div>

                {{-- Metadata adicional --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Comentarios:</span>
                        <span class="ml-1">{{ $caseData['comments'] ?? 0 }}</span>
                    </div>
                    
                    @if($caseData['have_attachment'] ?? false)
                        <div class="flex items-center gap-1">
                            <x-icon name="o-paper-clip" class="w-3 h-3 text-gray-400" />
                            <span class="text-gray-600">Con adjuntos</span>
                        </div>
                    @endif
                </div>

                {{-- Tiempo transcurrido --}}
                <div class="pt-3 border-t">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <x-icon name="o-clock" class="w-4 h-4" />
                        <span>Asignado {{ \Carbon\Carbon::parse($caseData['assigned_at'])->diffForHumans() }}</span>
                    </div>
                </div>

                {{-- Acciones rápidas --}}
                <div class="pt-3 border-t">
                    <div class="flex flex-wrap gap-2">
                        <x-button 
                            label="Ver detalles"
                            class="btn-xs btn-outline"
                            icon="o-eye"
                        />
                        <x-button 
                            label="Marcar prioridad"
                            class="btn-xs btn-outline"
                            icon="o-flag"
                        />
                        <x-button 
                            label="Transferir"
                            class="btn-xs btn-outline"
                            icon="o-arrow-right"
                        />
                    </div>
                </div>
            </div>
        </x-card>
    @else
        <x-card class="text-center py-8">
            <x-icon name="o-exclamation-triangle" class="w-12 h-12 mx-auto text-gray-300 mb-2" />
            <p class="text-gray-500">No se encontró información del caso</p>
        </x-card>
    @endif
</div>