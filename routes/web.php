<?php

use Livewire\Volt\Volt;

Volt::route('/', 'users.index');
Volt::route('/supervisor', 'inbox.supervisor');
Volt::route('/agente', 'inbox.agente');
