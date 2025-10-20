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
        Schema::create('gmail_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('contactenos@orpro.cl');
            $table->string('email')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_generic')->default(false)->comment('true para contactenos@orpro.cl');
            $table->foreignId('assigned_user_id')->nullable()
                  ->constrained('users')->nullOnDelete()
                  ->comment('Para grupos genéricos');
            $table->string('gmail_label')->nullable()->comment('Etiqueta en Gmail');
            $table->timestamps();
            
            $table->index(['is_active', 'is_generic']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmail_groups');
    }
};
