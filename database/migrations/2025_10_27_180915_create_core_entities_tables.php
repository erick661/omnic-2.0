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
        // Users - Solo datos esenciales inmutables
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('ejecutivo');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Emails - Solo datos inmutables del mensaje
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            
            // Identificadores únicos Gmail
            $table->string('gmail_message_id')->unique()->nullable();
            $table->string('gmail_thread_id')->nullable();
            
            // Datos básicos inmutables
            $table->string('direction', 20); // 'inbound', 'outbound'
            $table->text('subject')->nullable();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->json('cc_emails')->nullable();
            $table->json('bcc_emails')->nullable();
            $table->string('reply_to')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            
            // Timestamps inmutables
            $table->unsignedBigInteger('gmail_internal_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Metadatos técnicos Gmail
            $table->json('gmail_headers')->nullable();
            $table->json('gmail_labels')->nullable();
            $table->unsignedBigInteger('gmail_size_estimate')->nullable();
            $table->text('gmail_snippet')->nullable();
            $table->json('raw_headers')->nullable();
            $table->text('message_references')->nullable();
            $table->string('in_reply_to')->nullable();
            
            // Adjuntos
            $table->boolean('has_attachments')->default(false);
            
            // Relaciones básicas
            $table->unsignedBigInteger('gmail_group_id')->nullable();
            $table->unsignedBigInteger('parent_email_id')->nullable();
            
            // Índices
            $table->index('gmail_message_id');
            $table->index('gmail_thread_id');
            $table->index('direction');
            $table->index('gmail_group_id');
            $table->index('parent_email_id');
            $table->index('created_at');
            $table->index('from_email');
            $table->index('to_email');
            
            // Foreign keys (se agregarán después)
        });

        // Cases - Solo datos inmutables del caso
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 20)->unique();
            
            // Datos del empleador (inmutables)
            $table->string('employer_rut', 8)->nullable();
            $table->string('employer_dv', 1)->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employer_phone', 20)->nullable();
            $table->string('employer_email')->nullable();
            
            // Origen inmutable
            $table->string('origin_channel'); // 'email', 'phone', 'chat', etc.
            $table->unsignedBigInteger('origin_communication_id')->nullable();
            
            // Referencias inmutables
            $table->unsignedBigInteger('reference_code_id')->nullable();
            
            // Timestamp inmutable
            $table->timestamp('created_at')->useCurrent();
            
            // Índices
            $table->index('case_number');
            $table->index('employer_rut');
            $table->index('origin_channel');
            $table->index('reference_code_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('users');
    }
};
