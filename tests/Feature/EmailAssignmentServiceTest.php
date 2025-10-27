<?php

use App\Services\Email\EmailAssignmentService;
use App\Models\ImportedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('EmailAssignmentService', function () {
    beforeEach(function () {
        $this->service = new EmailAssignmentService();
        
        // Crear datos de prueba
        $this->user = User::factory()->create([
            'name' => 'Test Agent',
            'email' => 'agent@test.com'
        ]);
        
        $this->email = ImportedEmail::factory()->create([
            'subject' => 'Test Email',
            'case_status' => 'pending',
            'assigned_to' => null,
            'assigned_at' => null
        ]);
    });

    it('can assign email to agent successfully', function () {
        $result = $this->service->assignEmailToAgent(
            $this->email->id,
            $this->user->id,
            'Test assignment notes'
        );

        expect($result)->toHaveKey('success', true)
            ->and($result)->toHaveKey('message')
            ->and($result['data'])->toHaveKey('email_id', $this->email->id)
            ->and($result['data'])->toHaveKey('agent_id', $this->user->id);

        // Verificar que el email fue asignado en la base de datos
        $this->email->refresh();
        expect($this->email->assigned_to)->toBe($this->user->id)
            ->and($this->email->case_status)->toBe('assigned')
            ->and($this->email->assigned_at)->not->toBeNull()
            ->and($this->email->assignment_notes)->toBe('Test assignment notes');
    });

    it('fails when email does not exist', function () {
        $result = $this->service->assignEmailToAgent(999, $this->user->id);

        expect($result)->toHaveKey('success', false)
            ->and($result)->toHaveKey('message');
    });

    it('fails when agent does not exist', function () {
        $result = $this->service->assignEmailToAgent($this->email->id, 999);

        expect($result)->toHaveKey('success', false)
            ->and($result)->toHaveKey('message');
    });

    it('fails when email is already assigned', function () {
        // Asignar el email primero
        $this->email->update([
            'assigned_to' => $this->user->id,
            'assigned_at' => now(),
            'case_status' => 'assigned'
        ]);

        $result = $this->service->assignEmailToAgent($this->email->id, $this->user->id);

        expect($result)->toHaveKey('success', false)
            ->and($result['message'])->toContain('ya está asignado');
    });

    it('can assign multiple emails at once', function () {
        $email2 = ImportedEmail::factory()->create([
            'subject' => 'Second Test Email',
            'case_status' => 'pending'
        ]);

        $emailIds = [$this->email->id, $email2->id];
        $result = $this->service->assignMultipleEmails($emailIds, $this->user->id);

        expect($result)->toHaveKey('success', true)
            ->and($result['data'])->toHaveKey('assigned_count', 2)
            ->and($result['data'])->toHaveKey('failed_count', 0);

        // Verificar ambos emails
        $this->email->refresh();
        $email2->refresh();
        
        expect($this->email->assigned_to)->toBe($this->user->id)
            ->and($email2->assigned_to)->toBe($this->user->id);
    });

    it('provides assignment statistics', function () {
        // Crear varios emails con diferentes estados
        ImportedEmail::factory()->create(['assigned_to' => $this->user->id, 'case_status' => 'assigned']);
        ImportedEmail::factory()->create(['assigned_to' => $this->user->id, 'case_status' => 'resolved']);
        ImportedEmail::factory()->create(['case_status' => 'pending']);

        $stats = $this->service->getAssignmentStats($this->user->id);

        expect($stats)->toHaveKey('agent_id', $this->user->id)
            ->and($stats)->toHaveKey('total_assigned')
            ->and($stats)->toHaveKey('pending')
            ->and($stats)->toHaveKey('resolved')
            ->and($stats['total_assigned'])->toBeGreaterThan(0);
    });

    it('validates email assignment rules', function () {
        // Email en estado 'closed' no se puede reasignar
        $closedEmail = ImportedEmail::factory()->create([
            'case_status' => 'closed',
            'assigned_to' => $this->user->id
        ]);

        $result = $this->service->assignEmailToAgent($closedEmail->id, $this->user->id);

        expect($result)->toHaveKey('success', false)
            ->and($result['message'])->toContain('cerrado');
    });

    it('handles database transactions correctly', function () {
        // Simular falla en la transacción usando un usuario inválido después de validación inicial
        $this->mock(ImportedEmail::class, function ($mock) {
            $mock->shouldReceive('findOrFail')->andReturn($this->email);
            $mock->shouldReceive('update')->andThrow(new Exception('Database error'));
        });

        expect(function () {
            $this->service->assignEmailToAgent($this->email->id, $this->user->id);
        })->toThrow(Exception::class);

        // Verificar que no se hicieron cambios parciales
        $this->email->refresh();
        expect($this->email->assigned_to)->toBeNull();
    });
});