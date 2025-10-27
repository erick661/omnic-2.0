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
        // Email Queue - Ultra simplificado
        Schema::create('email_queue', function (Blueprint $table) {
            $table->id();
            
            // SOLO referencia - SIN duplicación
            $table->unsignedBigInteger('email_id');
            
            // Control de envío mínimo
            $table->string('status', 50)->default('queued');
            $table->timestamp('scheduled_at')->useCurrent();
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            
            $table->timestamp('created_at')->useCurrent();
            
            // Índices
            $table->index('email_id');
            $table->index('status');
            $table->index('scheduled_at');
            
            // Foreign key
            $table->foreign('email_id')->references('id')->on('emails')->cascadeOnDelete();
        });

        // Email Attachments
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_id');
            
            // Datos inmutables del archivo
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('file_path', 500);
            
            // Drive
            $table->string('drive_file_id')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            // Índices
            $table->index('email_id');
            $table->index('drive_file_id');
            
            // Foreign key
            $table->foreign('email_id')->references('id')->on('emails')->cascadeOnDelete();
        });

        // Contacts
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company')->nullable();
            $table->string('rut_empleador', 8)->nullable();
            $table->string('dv_empleador', 1)->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('email');
            $table->index('rut_empleador');
        });

        // Reference Codes
        Schema::create('reference_codes', function (Blueprint $table) {
            $table->id();
            $table->string('rut_empleador', 8);
            $table->string('dv_empleador', 1);
            $table->string('producto', 50);
            $table->string('code_hash')->unique();
            $table->unsignedBigInteger('assigned_user_id');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('code_hash');
            $table->index('rut_empleador');
            $table->index('assigned_user_id');
            
            // Foreign key
            $table->foreign('assigned_user_id')->references('id')->on('users');
        });

        // Email Templates
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->text('body_text')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Foreign key
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // System Config
        Schema::create('system_config', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
        });

        // Agregar foreign key para cases
        Schema::table('cases', function (Blueprint $table) {
            $table->foreign('reference_code_id')->references('id')->on('reference_codes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['reference_code_id']);
        });
        
        Schema::dropIfExists('system_config');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('reference_codes');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('email_attachments');
        Schema::dropIfExists('email_queue');
    }
};
