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
        Schema::create('reference_codes', function (Blueprint $table) {
            $table->id();
            $table->string('rut_empleador', 8)->comment('Sin puntos ni guión');
            $table->string('dv_empleador', 1)->comment('Dígito verificador');
            $table->string('producto', 50)->comment('AFP-CAPITAL, etc');
            $table->string('code_hash')->unique()->comment('Código codificado');
            $table->foreignId('assigned_user_id')
                  ->constrained('users')->onDelete('cascade')
                  ->comment('Ejecutivo asignado');
            $table->timestamps();
            
            $table->unique(['rut_empleador', 'dv_empleador', 'producto']);
            $table->index('code_hash');
            $table->index('assigned_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_codes');
    }
};
