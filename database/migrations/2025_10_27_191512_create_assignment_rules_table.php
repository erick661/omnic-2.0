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
        Schema::create('assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_type'); // 'mass_campaign', 'case_code', 'gmail_group', 'rut_pattern'
            $table->string('pattern_name')->unique(); // Nombre descriptivo de la regla
            $table->text('regex_pattern')->nullable(); // Patrón regex para detectar en subject/body
            $table->integer('priority_order'); // 1 = máxima prioridad
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('config')->nullable(); // Configuración adicional específica por tipo
            $table->timestamps();
            
            // Índices para performance
            $table->index(['rule_type', 'is_active']);
            $table->index('priority_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_rules');
    }
};
