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
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->string('portfolio_code')->unique(); // 'TECH', 'SALES', 'LEGAL'
            $table->string('portfolio_name'); // 'Tecnología', 'Ventas', 'Legal'
            $table->unsignedBigInteger('assigned_user_id'); // Usuario asignado a esta cartera
            $table->json('rut_ranges')->nullable(); // Rangos de RUT: ["76000000-77000000", "12000000-13000000"]
            $table->json('campaign_patterns')->nullable(); // Patrones de campaña: ["TECH-*", "SYS-*"]
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('assigned_user_id')->references('id')->on('users');
            
            // Índices
            $table->index(['portfolio_code', 'is_active']);
            $table->index('assigned_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
