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
        // Event Types - Catálogo maestro de eventos
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 100)->unique();
            $table->string('aggregate_type', 50);
            $table->text('description');
            $table->string('severity', 20)->default('info');
            $table->integer('schema_version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['aggregate_type', 'event_type']);
        });

        // Events - Event Store unificado
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            
            // Identificación del evento
            $table->string('event_type', 100);
            $table->string('aggregate_type', 50);
            $table->unsignedBigInteger('aggregate_id')->nullable();
            
            // Datos del evento
            $table->json('event_data');
            $table->integer('event_version')->default(1);
            
            // Metadatos
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamp('triggered_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Contexto
            $table->string('severity', 20)->default('info');
            $table->string('process_name', 100)->nullable();
            $table->string('job_id')->nullable();
            $table->string('correlation_id')->nullable();
            $table->unsignedBigInteger('causation_id')->nullable();
            
            // Procesamiento
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->string('processed_by', 100)->nullable();
            
            // Datos técnicos para errores
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->text('stack_trace')->nullable();
            
            // Índices optimizados para event sourcing
            $table->index(['aggregate_type', 'aggregate_id', 'triggered_at']);
            $table->index(['event_type', 'triggered_at']);
            $table->index(['severity', 'triggered_at']);
            $table->index('triggered_by');
            $table->index('correlation_id');
            $table->index('process_name');
            $table->index(['processed', 'triggered_at']);
            
            // Foreign keys (causation_id se creará después que la tabla esté lista)
            $table->foreign('event_type')->references('event_type')->on('event_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_types');
    }
};
