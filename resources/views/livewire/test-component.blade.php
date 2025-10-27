<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function with()
    {
        return [
            'message' => 'Test component working!'
        ];
    }
}; ?>

<div>
    <h1>{{ $message }}</h1>
    <p>Esto es un componente de prueba.</p>
</div>