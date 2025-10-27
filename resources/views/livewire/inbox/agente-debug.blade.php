<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use App\Models\ImportedEmail;
use App\Models\User;

new class extends Component
{
    public function casosAgentes(): Collection
    {
        // Probar consulta básica primero
        $emails = ImportedEmail::with(['gmailGroup'])
            ->whereIn('case_status', ['pending', 'assigned', 'opened', 'in_progress', 'resolved'])
            ->orderBy('received_at', 'desc')
            ->take(5) // Limitar para debugging
            ->get();

        return $emails->map(function ($email) {
            return [
                'id' => $email->id,
                'subject' => $email->subject,
                'status' => 'asignado', // Simplificado
                'assigned_at' => $email->received_at->format('Y-m-d H:i:s'),
                'have_attachment' => $email->has_attachments,
                'priority' => 'medium', // Simplificado
                'category' => 'support', // Simplificado
                'comments' => 0,
                'from_email' => $email->from_email,
                'from_name' => $email->from_name,
                'group_name' => $email->gmailGroup->name ?? 'Sin grupo',
            ];
        });
    }

    public function with(): array
    {
        $casosAgentes = $this->casosAgentes();

        return [
            'asignados' => $casosAgentes->filter(fn ($caso) => $caso['status'] === 'asignado'),
            'en_progreso' => collect([]), // Vacío por simplicidad
            'resueltos' => collect([]), // Vacío por simplicidad
            'totalCases' => $casosAgentes->count(),
        ];
    }
}; ?>

<div>
    <h1>Panel Agente Debug</h1>
    <p>Total casos: {{ $totalCases }}</p>
    
    <div class="mt-4">
        <h2>Casos Asignados ({{ $asignados->count() }})</h2>
        @foreach($asignados as $caso)
            <div class="border p-2 mb-2">
                <strong>ID:</strong> {{ $caso['id'] }}<br>
                <strong>Asunto:</strong> {{ $caso['subject'] }}<br>
                <strong>De:</strong> {{ $caso['from_email'] }}<br>
                <strong>Grupo:</strong> {{ $caso['group_name'] }}
            </div>
        @endforeach
    </div>
</div>