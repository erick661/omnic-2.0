<?php

use Livewire\Volt\Component;
use Illuminate\Support\Collection;

new class extends Component {
    
    public function items(): Collection
    {
        return collect([
            [
                'title' => 'Inbox', 
                'icon' => 'o-home',
                'link' => '', 
                'items' => [
                    ['title' => 'Supervisor', 'icon' => 'o-home', 'link' => '/supervisor', 'items' => []],
                    ['title' => 'Agente', 'icon' => 'o-home', 'link' => '/agente', 'items' => []]
                ]
            ],
        ]);
    }

    public function with(): array
    {
        return [
            'items' => $this->items(),
        ];
    }

}; ?>

<div>
    @foreach ($this->items() as $item)
            @if(count($item['items']) > 0)
                <x-menu-sub title="{{ $item['title'] }}" icon="{{ $item['icon'] }}">
                    @foreach ($item['items'] as $subItem)
                        <x-menu-item title="{{ $subItem['title'] }}" icon="{{ $subItem['icon'] }}" link="{{ $subItem['link'] }}" />
                    @endforeach
                </x-menu-sub>
            @else
                <x-menu-item title="{{ $item['title'] }}" icon="{{ $item['icon'] }}" link="{{ $item['link'] }}" />
            @endif
    @endforeach
</div>
