<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public function mount()
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return redirect('/');
        }
    }
    
    public function logout()
    {
        return redirect()->route('auth.logout');
    }
}; ?>

<div>
    <x-header title="Dashboard - Sistema Omnicanal ORPRO" />

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Información del usuario -->
        <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Bienvenido, {{ Auth::user()->name }}
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            {{ Auth::user()->email }} • Último acceso: {{ Auth::user()->last_login?->diffForHumans() ?? 'Primera vez' }}
                        </p>
                    </div>
                    <div class="mt-5 sm:mt-0 sm:ml-6 sm:flex-shrink-0 sm:flex sm:items-center">
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <x-button 
                                type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-white"
                                icon="o-arrow-right-on-rectangle"
                            >
                                Cerrar Sesión
                            </x-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-icon name="o-envelope" class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Casos Activos
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ \App\Models\CustomerCase::where('status', 'open')->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-icon name="o-chat-bubble-left-right" class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Comunicaciones Hoy
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ \App\Models\Communication::whereDate('created_at', today())->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-icon name="o-user-group" class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Usuarios Activos
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ \App\Models\User::where('active', true)->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-icon name="o-clock" class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Tiempo Promedio
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    2.4 hrs
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enlaces rápidos -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <x-card title="Gestión de Usuarios" subtitle="Administrar usuarios del sistema">
                <x-slot:menu>
                    <x-button link="/" icon="o-users" class="btn-primary">
                        Ver Usuarios
                    </x-button>
                </x-slot:menu>
            </x-card>

            <x-card title="Supervisión" subtitle="Monitor de casos y comunicaciones">
                <x-slot:menu>
                    <x-button link="/supervisor" icon="o-eye" class="btn-secondary">
                        Panel Supervisor
                    </x-button>
                </x-slot:menu>
            </x-card>

            <x-card title="Atención de Casos" subtitle="Interface de agente">
                <x-slot:menu>
                    <x-button link="/agente" icon="o-chat-bubble-left-ellipsis" class="btn-accent">
                        Panel Agente
                    </x-button>
                </x-slot:menu>
            </x-card>
        </div>
    </div>
</div>