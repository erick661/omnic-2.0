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
        Schema::create('phone_communications', function (Blueprint $table) {
            $table->id();
            
            // Relación con comunicación
            $table->foreignId('communication_id')
                  ->constrained('communications')->onDelete('cascade')
                  ->comment('Comunicación asociada');
            
            // Datos específicos de llamadas
            $table->string('phone_number', 20)
                  ->comment('Número de teléfono');
            $table->integer('call_duration_seconds')->nullable()
                  ->comment('Duración de la llamada en segundos');
            $table->enum('call_type', ['incoming', 'outgoing'])
                  ->comment('Tipo de llamada');
            $table->enum('call_status', ['completed', 'busy', 'no_answer', 'failed'])
                  ->comment('Estado final de la llamada');
            
            // Grabación (futuro)
            $table->string('recording_url', 500)->nullable()
                  ->comment('URL de la grabación de la llamada');
            $table->integer('recording_duration_seconds')->nullable()
                  ->comment('Duración de la grabación en segundos');
            
            // Notas de la llamada
            $table->text('call_summary')->nullable()
                  ->comment('Resumen de la llamada');
            $table->boolean('follow_up_required')->default(false)
                  ->comment('Si requiere seguimiento');
            $table->date('follow_up_date')->nullable()
                  ->comment('Fecha sugerida para seguimiento');
            
            // Metadatos adicionales
            $table->string('caller_id')->nullable()
                  ->comment('Identificador del sistema telefónico');
            $table->json('phone_metadata')->nullable()
                  ->comment('Metadatos específicos del sistema telefónico');
            
            $table->timestamps();
            
            // Índices
            $table->index('communication_id');
            $table->index('phone_number');
            $table->index('call_type');
            $table->index('call_status');
            $table->index('follow_up_required');
            $table->index('follow_up_date');
            
            // Una comunicación = Un registro telefónico
            $table->unique('communication_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_communications');
    }
};
