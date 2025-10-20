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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company')->nullable();
            
            // Segmentación básica
            $table->string('rut_empleador', 8)->nullable();
            $table->string('dv_empleador', 1)->nullable();
            $table->string('producto', 50)->nullable()->comment('AFP-CAPITAL, etc');
            
            // Campos adicionales
            $table->string('phone', 50)->nullable();
            $table->json('attributes')->nullable()->comment('Campos dinámicos del CSV');
            
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('brevo_contact_id')->nullable();
            
            // Importación
            $table->string('imported_from', 100)->nullable()->comment('CSV, Excel, API, etc');
            $table->timestamp('imported_at')->nullable();
            
            $table->timestamps();
            
            $table->index('email');
            $table->index(['rut_empleador', 'dv_empleador', 'producto']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
