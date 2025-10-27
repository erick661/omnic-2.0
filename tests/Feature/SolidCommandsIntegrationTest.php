<?php

describe('SOLID Commands Test Suite', function () {
    it('can list available SOLID commands', function () {
        $result = shell_exec('php artisan list | grep -E "(email|groups|system|chat|drive)"');
        
        expect($result)->toContain('email:assign-to-agent')
            ->and($result)->toContain('email:stats')
            ->and($result)->toContain('system:test-gmail-auth')
            ->and($result)->toContain('groups:list');
    });

    it('SOLID commands show proper help information', function () {
        $result = shell_exec('php artisan email:assign-to-agent --help 2>/dev/null');
        
        expect($result)->toContain('Asignar un correo pendiente a un agente específico');
    });

    it('SOLID commands are organized in proper categories', function () {
        $commands = [
            'email:assign-to-agent' => 'SOLID: Asignar un correo',
            'email:stats' => 'Show email system statistics',
            'system:test-gmail-auth' => 'SOLID: Probar autenticación',
            'groups:list' => 'List all Gmail groups'
        ];

        foreach ($commands as $command => $expectedText) {
            $result = shell_exec("php artisan {$command} --help 2>/dev/null");
            expect($result)->toContain($expectedText);
        }
    });

    it('verifies SOLID architecture file structure exists', function () {
        $solidFiles = [
            'app/Services/Base/GoogleApiService.php',
            'app/Services/Email/EmailAssignmentService.php',
            'app/Services/Email/EmailStatsService.php',
            'app/Console/Commands/Email/AssignEmailToAgentCommand.php',
            'app/Console/Commands/System/TestGmailAuthCommand.php'
        ];

        foreach ($solidFiles as $file) {
            expect(file_exists($file))->toBeTrue("File {$file} should exist");
        }
    });

    it('SOLID services follow dependency injection pattern', function () {
        $commandFile = file_get_contents('app/Console/Commands/Email/AssignEmailToAgentCommand.php');
        
        expect($commandFile)->toContain('EmailAssignmentService $assignmentService')
            ->and($commandFile)->toContain('private EmailAssignmentService $assignmentService');
    });

    it('SOLID commands handle errors gracefully without credentials', function () {
        $result = shell_exec('php artisan system:test-gmail-auth 2>&1');
        
        // Debe mostrar mensaje de error sobre credenciales, no crash
        expect($result)->toContain('❌')
            ->and($result)->toContain('Credenciales no encontradas');
    });

    it('email stats command executes without database errors', function () {
        $result = shell_exec('php artisan email:stats --period=today 2>&1');
        
        // No debe tener errores de SQL fatales
        expect($result)->not->toContain('SQLSTATE')
            ->and($result)->not->toContain('Fatal error');
    });
});