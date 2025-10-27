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
        // Agregar foreign keys para events que requieren otras tablas
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('triggered_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('causation_id')->references('id')->on('events')->nullOnDelete();
        });

        // Agregar foreign key para sessions
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['triggered_by']);
            $table->dropForeign(['causation_id']);
        });
    }
};
