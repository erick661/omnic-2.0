<?php

use App\Services\Email\EmailStatsService;
use App\Models\ImportedEmail;
use App\Models\OutboxEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

describe('EmailStatsService', function () {
    beforeEach(function () {
        $this->service = new EmailStatsService();
        
        // Crear usuarios de prueba
        $this->agent1 = User::factory()->create(['name' => 'Agent 1']);
        $this->agent2 = User::factory()->create(['name' => 'Agent 2']);
        
        // Fecha base para pruebas
        $this->today = Carbon::today();
        $this->yesterday = Carbon::yesterday();
    });

    it('calculates inbox stats correctly', function () {
        // Crear emails con diferentes estados
        ImportedEmail::factory()->create([
            'case_status' => 'pending',
            'received_at' => $this->today
        ]);
        
        ImportedEmail::factory()->create([
            'case_status' => 'assigned',
            'assigned_to' => $this->agent1->id,
            'assigned_at' => $this->today,
            'received_at' => $this->today
        ]);
        
        ImportedEmail::factory()->create([
            'case_status' => 'resolved',
            'assigned_to' => $this->agent1->id,
            'assigned_at' => $this->today,
            'marked_resolved_at' => $this->today->copy()->addHours(2),
            'received_at' => $this->today
        ]);

        $stats = $this->service->getStats(['period' => 'today']);
        $inbox = $stats['inbox'];

        expect($inbox)->toHaveKey('total', 3)
            ->and($inbox)->toHaveKey('pending', 1)
            ->and($inbox)->toHaveKey('assigned', 1)
            ->and($inbox)->toHaveKey('resolved', 1)
            ->and($inbox)->toHaveKey('avg_resolution_hours');
    });

    it('calculates outbox stats correctly', function () {
        // Crear emails de salida con diferentes estados
        OutboxEmail::factory()->create([
            'status' => 'pending',
            'created_at' => $this->today
        ]);
        
        OutboxEmail::factory()->create([
            'status' => 'sent',
            'created_at' => $this->today
        ]);
        
        OutboxEmail::factory()->create([
            'status' => 'failed',
            'created_at' => $this->today
        ]);

        $stats = $this->service->getStats(['period' => 'today']);
        $outbox = $stats['outbox'];

        expect($outbox)->toHaveKey('total', 3)
            ->and($outbox)->toHaveKey('pending', 1)
            ->and($outbox)->toHaveKey('sent', 1)
            ->and($outbox)->toHaveKey('failed', 1);
    });

    it('calculates agent statistics correctly', function () {
        // Crear emails asignados a diferentes agentes
        ImportedEmail::factory()->create([
            'assigned_to' => $this->agent1->id,
            'case_status' => 'assigned',
            'assigned_at' => $this->today,
            'received_at' => $this->today
        ]);
        
        ImportedEmail::factory()->create([
            'assigned_to' => $this->agent1->id,
            'case_status' => 'resolved',
            'assigned_at' => $this->today,
            'marked_resolved_at' => $this->today->copy()->addHours(1),
            'received_at' => $this->today
        ]);
        
        ImportedEmail::factory()->create([
            'assigned_to' => $this->agent2->id,
            'case_status' => 'assigned',
            'assigned_at' => $this->today,
            'received_at' => $this->today
        ]);

        $stats = $this->service->getStats(['period' => 'today']);
        $agents = $stats['agents'];

        expect($agents)->toHaveCount(2);
        
        $agent1Stats = collect($agents)->firstWhere('agent_id', $this->agent1->id);
        expect($agent1Stats)->not->toBeNull()
            ->and($agent1Stats['total_assigned'])->toBe(2)
            ->and($agent1Stats['total_resolved'])->toBe(1)
            ->and($agent1Stats['pending'])->toBe(1)
            ->and($agent1Stats['agent_name'])->toBe('Agent 1');
    });

    it('filters by period correctly', function () {
        // Crear emails en diferentes fechas
        ImportedEmail::factory()->create([
            'case_status' => 'pending',
            'received_at' => $this->today
        ]);
        
        ImportedEmail::factory()->create([
            'case_status' => 'pending',
            'received_at' => $this->yesterday
        ]);

        // Test período 'today'
        $todayStats = $this->service->getStats(['period' => 'today']);
        expect($todayStats['inbox']['total'])->toBe(1);

        // Test período 'all'
        $allStats = $this->service->getStats(['period' => 'all']);
        expect($allStats['inbox']['total'])->toBe(2);
    });

    it('filters by specific agent', function () {
        // Crear emails asignados a diferentes agentes
        ImportedEmail::factory()->create([
            'assigned_to' => $this->agent1->id,
            'case_status' => 'assigned',
            'received_at' => $this->today
        ]);
        
        ImportedEmail::factory()->create([
            'assigned_to' => $this->agent2->id,
            'case_status' => 'assigned',
            'received_at' => $this->today
        ]);

        $stats = $this->service->getStats([
            'period' => 'today',
            'agent' => $this->agent1->id
        ]);

        expect($stats['inbox']['total'])->toBe(1);
    });

    it('filters by specific group', function () {
        // Crear emails de diferentes grupos
        ImportedEmail::factory()->create([
            'group_email' => 'group1@test.com',
            'case_status' => 'pending',
            'received_at' => $this->today
        ]);
        
        ImportedEmail::factory()->create([
            'group_email' => 'group2@test.com',
            'case_status' => 'pending',
            'received_at' => $this->today
        ]);

        $stats = $this->service->getStats([
            'period' => 'today',
            'group' => 'group1@test.com'
        ]);

        expect($stats['inbox']['total'])->toBe(1);
    });

    it('calculates average resolution time correctly', function () {
        // Email que se resolvió en 2 horas
        ImportedEmail::factory()->create([
            'assigned_to' => $this->agent1->id,
            'case_status' => 'resolved',
            'assigned_at' => $this->today->copy()->setTime(10, 0),
            'marked_resolved_at' => $this->today->copy()->setTime(12, 0),
            'received_at' => $this->today
        ]);
        
        // Email que se resolvió en 4 horas
        ImportedEmail::factory()->create([
            'assigned_to' => $this->agent1->id,
            'case_status' => 'resolved',
            'assigned_at' => $this->today->copy()->setTime(9, 0),
            'marked_resolved_at' => $this->today->copy()->setTime(13, 0),
            'received_at' => $this->today
        ]);

        $stats = $this->service->getStats(['period' => 'today']);
        
        // Promedio debería ser 3 horas
        expect($stats['inbox']['avg_resolution_hours'])->toBe(3.0);
    });

    it('handles week period filter correctly', function () {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // Email dentro de la semana
        ImportedEmail::factory()->create([
            'case_status' => 'pending',
            'received_at' => $startOfWeek->copy()->addDays(2)
        ]);
        
        // Email fuera de la semana
        ImportedEmail::factory()->create([
            'case_status' => 'pending',
            'received_at' => $startOfWeek->copy()->subDays(1)
        ]);

        $stats = $this->service->getStats(['period' => 'week']);
        expect($stats['inbox']['total'])->toBe(1);
    });

    it('handles month period filter correctly', function () {
        $startOfMonth = Carbon::now()->startOfMonth();
        
        // Email dentro del mes
        ImportedEmail::factory()->create([
            'case_status' => 'pending',
            'received_at' => $startOfMonth->copy()->addDays(10)
        ]);
        
        // Email fuera del mes
        ImportedEmail::factory()->create([
            'case_status' => 'pending',
            'received_at' => $startOfMonth->copy()->subDays(1)
        ]);

        $stats = $this->service->getStats(['period' => 'month']);
        expect($stats['inbox']['total'])->toBe(1);
    });

    it('provides complete stats structure', function () {
        $stats = $this->service->getStats(['period' => 'today']);

        expect($stats)->toHaveKeys(['inbox', 'outbox', 'agents'])
            ->and($stats['inbox'])->toHaveKeys(['total', 'pending', 'assigned', 'resolved', 'closed'])
            ->and($stats['outbox'])->toHaveKeys(['total', 'pending', 'sent', 'failed']);
    });
});