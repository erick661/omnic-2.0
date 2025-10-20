<?php

use Livewire\Volt\Component;
use App\Models\ImportedEmail;
use App\Models\User;
use Illuminate\Support\Collection;

new class extends Component {
    
    public function myEmails(): Collection
    {
        return ImportedEmail::forUser(auth()->user())
            ->with(['gmailGroup', 'referenceCode'])
            ->orderBy('assigned_at', 'desc')
            ->limit(20)
            ->get();
    }
    
    public function getMyStats(): array
    {
        $user = auth()->user();
        
        return [
            'assigned' => ImportedEmail::forUser($user)
                ->where('case_status', 'assigned')
                ->count(),
            'in_progress' => ImportedEmail::forUser($user)
                ->whereIn('case_status', ['opened', 'in_progress'])
                ->count(),
            'resolved_today' => ImportedEmail::forUser($user)
                ->where('case_status', 'resolved')
                ->whereDate('marked_resolved_at', today())
                ->count(),
            'overdue' => ImportedEmail::forUser($user)
                ->overdue()
                ->count(),
        ];
    }

}; ?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Mi Bandeja de Correos</h1>
        <p class="text-gray-600">Correos asignados a {{ auth()->user()->name }}</p>
    </div>

    {{-- Estadísticas del ejecutivo --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @php $stats = $this->getMyStats() @endphp
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Asignados</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['assigned'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">En Progreso</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['in_progress'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Vencidos</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['overdue'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Resueltos Hoy</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['resolved_today'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Lista de correos --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Mis Correos Asignados</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($this->myEmails() as $email)
                <div class="p-6 hover:bg-gray-50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <h4 class="text-sm font-medium text-gray-900">{{ $email->subject }}</h4>
                                @if($email->referenceCode)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ $email->referenceCode->formatted_code }}
                                    </span>
                                @endif
                                @if($email->has_attachments)
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                @endif
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-1">
                                <strong>De:</strong> {{ $email->from_name ?? $email->from_email }}
                            </p>
                            
                            @if($email->formatted_rut)
                                <p class="text-sm text-gray-600 mb-1">
                                    <strong>RUT Empleador:</strong> {{ $email->formatted_rut }}
                                </p>
                            @endif
                            
                            <p class="text-xs text-gray-500">
                                Recibido: {{ $email->received_at->format('d/m/Y H:i') }} • 
                                Asignado: {{ $email->time_from_assignment }} • 
                                Grupo: {{ $email->gmailGroup->name }}
                            </p>

                            {{-- Preview del contenido --}}
                            <div class="mt-2">
                                <p class="text-sm text-gray-700 line-clamp-2">
                                    {{ strip_tags($email->body_text ?? $email->body_html) }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="ml-4 flex flex-col items-end space-y-2">
                            {{-- Estado --}}
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($email->case_status === 'assigned' && $email->is_overdue) bg-red-100 text-red-800
                                @elseif($email->case_status === 'assigned') bg-yellow-100 text-yellow-800
                                @elseif($email->case_status === 'opened') bg-blue-100 text-blue-800  
                                @elseif($email->case_status === 'in_progress') bg-indigo-100 text-indigo-800
                                @elseif($email->case_status === 'resolved') bg-green-100 text-green-800
                                @endif">
                                @if($email->is_overdue && in_array($email->case_status, ['assigned', 'opened', 'in_progress']))
                                    Vencido
                                @else
                                    {{ ucfirst(str_replace('_', ' ', $email->case_status)) }}
                                @endif
                            </span>

                            @if($email->priority === 'high')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Alta Prioridad
                                </span>
                            @endif

                            {{-- Acciones --}}
                            <div class="flex space-x-2">
                                @if($email->case_status === 'assigned')
                                    <button class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                                        Abrir
                                    </button>
                                @elseif(in_array($email->case_status, ['opened', 'in_progress']))
                                    <button class="text-xs bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                        Responder
                                    </button>
                                @endif
                                
                                <button class="text-xs bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">
                                    Ver
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay correos asignados</h3>
                    <p class="mt-1 text-sm text-gray-500">No tienes correos asignados en este momento.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
