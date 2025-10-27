<?php

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use App\Models\ImportedEmail;
use App\Models\User;

new class extends Component
{
    public function with(): array
    {
        return [
            'message' => 'Componente agente simplificado funcionando',
            'totalEmails' => ImportedEmail::count()
        ];
    }
}; ?>

<div>
    <h1>{{ $message }}</h1>
    <p>Total de emails en la base de datos: {{ $totalEmails }}</p>
</div>