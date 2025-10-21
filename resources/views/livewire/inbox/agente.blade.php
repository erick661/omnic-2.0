<?php

use App\Models\ImportedEmail;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;

new class extends Component
{
    /* public function myEmails(): Collection
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
    } */

}; ?>


<div>
    <x-header title="Bandeja de Agente" subtitle="Casos Asignados" separator>
        <x-slot class="!justify-end">
            <x-input icon="o-bolt" placeholder="Search..." />
        </x-slot>
        <x-slot>
            <x-button icon="o-funnel" />
            <x-button icon="o-plus" class="btn-primary" />
        </x-slot>
    </x-header>
</div>
