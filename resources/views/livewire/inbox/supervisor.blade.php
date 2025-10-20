<?php

use Livewire\Volt\Component;
use App\Models\ImportedEmail;
use App\Models\User;
use Illuminate\Support\Collection;

new class extends Component {
    
    public function pendingEmails(): Collection
    {
        return ImportedEmail::pending()
            ->with(['gmailGroup', 'assignedUser'])
            ->orderBy('received_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    public function assignedEmails(): Collection
    {
        return ImportedEmail::whereIn('case_status', ['assigned', 'opened', 'in_progress'])
            ->with(['gmailGroup', 'assignedUser', 'assignedByUser'])
            ->orderBy('assigned_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    public function overdueEmails(): Collection
    {
        return ImportedEmail::overdue()
            ->with(['gmailGroup', 'assignedUser'])
            ->orderBy('assigned_at', 'asc')
            ->get();
    }
    
    public function getStats(): array
    {
        return [
            'pending' => ImportedEmail::pending()->count(),
            'assigned_today' => ImportedEmail::assigned()
                ->whereDate('assigned_at', today())
                ->count(),
            'overdue' => ImportedEmail::overdue()->count(),
            'resolved_today' => ImportedEmail::resolved()
                ->whereDate('marked_resolved_at', today())
                ->count(),
        ];
    }

}; ?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Panel de Supervisor</h1>
        <p class="text-gray-600">Gestión y asignación de correos</p>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @php $stats = $this->getStats() @endphp
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pendientes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Asignados Hoy</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['assigned_today'] }}</p>
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

    {{-- Correos Pendientes --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Correos Pendientes de Asignación</h3>
            </div>
            <div class="p-6">
                @forelse($this->pendingEmails() as $email)
                    <div class="border-b border-gray-200 pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900">{{ $email->subject }}</h4>
                                <p class="text-sm text-gray-600">De: {{ $email->from_name ?? $email->from_email }}</p>
                                <p class="text-xs text-gray-500">
                                    Grupo: {{ $email->gmailGroup->name }} • 
                                    {{ $email->received_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                @if($email->priority === 'high')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Alta
                                    </span>
                                @endif
                                @if($email->has_attachments)
                                    <svg class="w-4 h-4 text-gray-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2">
                            <button class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                                Asignar
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No hay correos pendientes</p>
                @endforelse
            </div>
        </div>

        {{-- Correos Asignados --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Correos Asignados Recientes</h3>
            </div>
            <div class="p-6">
                @forelse($this->assignedEmails() as $email)
                    <div class="border-b border-gray-200 pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900">{{ $email->subject }}</h4>
                                <p class="text-sm text-gray-600">
                                    Asignado a: {{ $email->assignedUser->name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $email->time_from_assignment }} • 
                                    Estado: {{ ucfirst($email->case_status) }}
                                </p>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                @if($email->is_overdue)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Vencido
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($email->case_status === 'assigned') bg-yellow-100 text-yellow-800
                                        @elseif($email->case_status === 'in_progress') bg-blue-100 text-blue-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $email->case_status)) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No hay correos asignados recientes</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Correos Vencidos (si hay) --}}
    @if($this->overdueEmails()->count() > 0)
        <div class="mt-6 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h3 class="text-lg font-medium text-red-900">
                    ⚠️ Correos Vencidos ({{ $this->overdueEmails()->count() }})
                </h3>
            </div>
            <div class="p-6">
                @foreach($this->overdueEmails() as $email)
                    <div class="border-b border-gray-200 pb-4 mb-4 last:border-0 last:pb-0 last:mb-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900">{{ $email->subject }}</h4>
                                <p class="text-sm text-gray-600">
                                    Asignado a: {{ $email->assignedUser->name }}
                                </p>
                                <p class="text-xs text-red-600">
                                    Vencido desde: {{ $email->assigned_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                <button class="text-xs bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                    Reasignar
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
