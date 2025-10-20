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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['administrador', 'supervisor', 'ejecutivo', 'masivo'])
                  ->default('ejecutivo')
                  ->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('email_alias')->nullable()->after('is_active')
                  ->comment('lucas.munoz@orpro.cl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'email_alias']);
        });
    }
};
