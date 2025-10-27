<?php

use App\Console\Commands\Email\AssignEmailToAgentCommand;
use App\Services\Email\EmailAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AssignEmailToAgentCommand', function () {
    beforeEach(function () {
        // Mock del servicio
        $this->mockService = Mockery::mock(EmailAssignmentService::class);
        $this->app->instance(EmailAssignmentService::class, $this->mockService);
    });

    it('executes assignment successfully', function () {
        $emailId = 123;
        $agentId = 456;
        $notes = 'Test assignment';

        // Configurar el mock para devolver éxito
        $this->mockService
            ->shouldReceive('assignEmailToAgent')
            ->with($emailId, $agentId, $notes)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Email asignado correctamente',
                'data' => [
                    'email_id' => $emailId,
                    'agent_id' => $agentId
                ]
            ]);

        $this->artisan('email:assign-to-agent', [
            'email_id' => $emailId,
            'agent_id' => $agentId,
            '--notes' => $notes
        ])
        ->expectsOutput('✅ Email asignado correctamente')
        ->assertSuccessful();
    });

    it('handles assignment failure gracefully', function () {
        $emailId = 123;
        $agentId = 456;

        // Configurar el mock para devolver falla
        $this->mockService
            ->shouldReceive('assignEmailToAgent')
            ->with($emailId, $agentId, null)
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Email ya está asignado a otro agente'
            ]);

        $this->artisan('email:assign-to-agent', [
            'email_id' => $emailId,
            'agent_id' => $agentId
        ])
        ->expectsOutput('❌ Email ya está asignado a otro agente')
        ->assertFailed();
    });

    it('handles service exceptions', function () {
        $emailId = 123;
        $agentId = 456;

        // Configurar el mock para lanzar excepción
        $this->mockService
            ->shouldReceive('assignEmailToAgent')
            ->with($emailId, $agentId, null)
            ->once()
            ->andThrow(new Exception('Database connection failed'));

        $this->artisan('email:assign-to-agent', [
            'email_id' => $emailId,
            'agent_id' => $agentId
        ])
        ->expectsOutput('❌ Error: Database connection failed')
        ->assertFailed();
    });

    it('validates required arguments', function () {
        // Sin argumentos debe fallar
        $this->artisan('email:assign-to-agent')
            ->assertFailed();
    });

    it('accepts optional notes parameter', function () {
        $emailId = 123;
        $agentId = 456;
        $notes = 'Caso urgente - requiere atención inmediata';

        $this->mockService
            ->shouldReceive('assignEmailToAgent')
            ->with($emailId, $agentId, $notes)
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Email asignado correctamente',
                'data' => ['email_id' => $emailId, 'agent_id' => $agentId]
            ]);

        $this->artisan('email:assign-to-agent', [
            'email_id' => $emailId,
            'agent_id' => $agentId,
            '--notes' => $notes
        ])
        ->assertSuccessful();
    });

    it('follows SOLID principles with dependency injection', function () {
        // Verificar que el comando usa dependency injection
        $command = $this->app->make(AssignEmailToAgentCommand::class);
        
        $reflection = new ReflectionClass($command);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        // Debe tener un parámetro que sea EmailAssignmentService
        expect($parameters)->toHaveCount(1);
        expect($parameters[0]->getType()->getName())->toBe(EmailAssignmentService::class);
    });

    it('displays detailed results when successful', function () {
        $emailId = 123;
        $agentId = 456;

        $this->mockService
            ->shouldReceive('assignEmailToAgent')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Email asignado correctamente',
                'data' => [
                    'email_id' => $emailId,
                    'agent_id' => $agentId,
                    'assigned_at' => '2024-10-26 15:30:00',
                    'case_status' => 'assigned'
                ]
            ]);

        $this->artisan('email:assign-to-agent', [
            'email_id' => $emailId,
            'agent_id' => $agentId
        ])
        ->expectsOutput('✅ Email asignado correctamente')
        ->expectsOutputToContain("Email ID: {$emailId}")
        ->expectsOutputToContain("Agente ID: {$agentId}")
        ->assertSuccessful();
    });

    it('is properly organized in Email category', function () {
        // Verificar que el comando está en el namespace correcto
        $command = new AssignEmailToAgentCommand(
            $this->mockService
        );
        
        expect(get_class($command))->toBe('App\Console\Commands\Email\AssignEmailToAgentCommand');
    });

    afterEach(function () {
        Mockery::close();
    });
});