<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            
            // Número único del caso
            $table->string('case_number', 20)->unique()->comment('CASO-YYYY-NNNNNN');
            
            // Identificación del empleador
            $table->string('employer_rut', 8)->nullable()->comment('RUT del empleador');
            $table->string('employer_dv', 1)->nullable()->comment('Dígito verificador');
            $table->string('employer_name')->nullable()->comment('Nombre o razón social');
            $table->string('employer_phone', 20)->nullable()->comment('Teléfono principal');
            $table->string('employer_email')->nullable()->comment('Email principal');
            
            // Gestión del caso
            $table->enum('status', [
                'pending', 'assigned', 'in_progress', 
                'pending_closure', 'resolved', 'spam'
            ])->default('pending')->comment('Estado del caso');
            
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                  ->default('normal')->comment('Prioridad del caso');
            
            // Asignación
            $table->foreignId('assigned_to')->nullable()
                  ->constrained('users')->nullOnDelete()
                  ->comment('Ejecutivo asignado');
            $table->foreignId('assigned_by')->nullable()
                  ->constrained('users')->nullOnDelete()
                  ->comment('Supervisor que asignó');
            $table->timestamp('assigned_at')->nullable()->comment('Fecha de asignación');
            
            // Canal de origen y metadata
            $table->enum('origin_channel', [
                'email', 'whatsapp', 'sms', 'phone', 'webchat', 'campaign'
            ])->comment('Canal por el que se inició el caso');
            
            $table->unsignedBigInteger('origin_communication_id')->nullable()
                  ->comment('ID de la comunicación original que generó el caso');
            
            // Campaña asociada (si aplica) - Se agregará la foreign key después
            $table->unsignedBigInteger('campaign_id')->nullable()
                  ->comment('Campaña que generó el caso');
            
            // Fechas importantes
            $table->timestamp('first_response_at')->nullable()
                  ->comment('Fecha de primera respuesta del ejecutivo');
            $table->timestamp('last_activity_at')->nullable()
                  ->comment('Última actividad en el caso');
            $table->timestamp('resolved_at')->nullable()
                  ->comment('Fecha de resolución del caso');
            
            // Notas y categorización
            $table->text('internal_notes')->nullable()
                  ->comment('Notas internas del supervisor/ejecutivo');
            $table->string('auto_category', 100)->nullable()
                  ->comment('Categorización automática del caso');
            $table->json('tags')->nullable()
                  ->comment('Etiquetas flexibles para categorización');
            
            // Métricas
            $table->integer('response_time_hours')->nullable()
                  ->comment('Tiempo hasta primera respuesta en horas');
            $table->integer('resolution_time_hours')->nullable()
                  ->comment('Tiempo total de resolución en horas');
            $table->integer('communication_count')->default(0)
                  ->comment('Contador de comunicaciones en el caso');
            
            $table->timestamps();
            
            // Índices para performance
            $table->index('case_number');
            $table->index(['employer_rut', 'employer_dv']);
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('origin_channel');
            $table->index('created_at');
            $table->index('last_activity_at');
            $table->index('assigned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
