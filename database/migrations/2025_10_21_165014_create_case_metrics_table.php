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
        Schema::create('case_metrics', function (Blueprint $table) {
            $table->id();
            
            // Relación con caso
            $table->foreignId('case_id')
                  ->constrained('cases')->onDelete('cascade')
                  ->comment('Caso asociado');
            
            // Métricas por canal
            $table->integer('email_count')->default(0)
                  ->comment('Cantidad de comunicaciones por email');
            $table->integer('whatsapp_count')->default(0)
                  ->comment('Cantidad de comunicaciones por WhatsApp');
            $table->integer('sms_count')->default(0)
                  ->comment('Cantidad de comunicaciones por SMS');
            $table->integer('phone_count')->default(0)
                  ->comment('Cantidad de comunicaciones telefónicas');
            $table->integer('webchat_count')->default(0)
                  ->comment('Cantidad de comunicaciones por chat web');
            
            // Tiempos de respuesta por canal (en horas)
            $table->decimal('avg_email_response_hours', 8, 2)->nullable()
                  ->comment('Tiempo promedio de respuesta por email');
            $table->decimal('avg_whatsapp_response_hours', 8, 2)->nullable()
                  ->comment('Tiempo promedio de respuesta por WhatsApp');
            $table->decimal('avg_sms_response_hours', 8, 2)->nullable()
                  ->comment('Tiempo promedio de respuesta por SMS');
            $table->decimal('avg_phone_response_hours', 8, 2)->nullable()
                  ->comment('Tiempo promedio para llamadas');
            
            // Canal preferido (basado en patrones de uso)
            $table->enum('preferred_channel', ['email', 'whatsapp', 'sms', 'phone', 'webchat'])
                  ->nullable()
                  ->comment('Canal preferido del empleador');
            $table->timestamp('preferred_channel_detected_at')->nullable()
                  ->comment('Cuándo se detectó la preferencia');
            
            // Métricas de satisfacción (futuro)
            $table->integer('satisfaction_score')->nullable()
                  ->comment('Puntuación de satisfacción (1-10)');
            $table->timestamp('satisfaction_collected_at')->nullable()
                  ->comment('Cuándo se recopiló la satisfacción');
            
            // Métricas de eficiencia
            $table->decimal('total_interaction_time_hours', 10, 2)->nullable()
                  ->comment('Tiempo total de interacción');
            $table->integer('escalation_count')->default(0)
                  ->comment('Cantidad de escalaciones al supervisor');
            $table->boolean('sla_breach')->default(false)
                  ->comment('Si se incumplió el SLA');
            $table->timestamp('sla_breach_at')->nullable()
                  ->comment('Cuándo se incumplió el SLA');
            
            $table->timestamps();
            
            // Índices
            $table->index('case_id');
            $table->index('preferred_channel');
            $table->index('satisfaction_score');
            $table->index('sla_breach');
            $table->index('updated_at');
            
            // Un caso = Un registro de métricas
            $table->unique('case_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_metrics');
    }
};
