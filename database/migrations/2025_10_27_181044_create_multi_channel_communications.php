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
        // Communications - Tabla polimórfica central
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            
            // Polimorfismo - CLAVE DE LA ARQUITECTURA
            $table->string('channel_type', 50); // 'email', 'phone', 'whatsapp', etc.
            $table->unsignedBigInteger('channel_id');
            
            // Datos comunes a todos los canales
            $table->string('direction', 20); // 'inbound', 'outbound'
            $table->string('subject', 500)->nullable();
            $table->text('content_preview')->nullable();
            
            // Timestamps comunes (inmutables)
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Referencias comunes
            $table->unsignedBigInteger('in_reply_to')->nullable();
            $table->string('reference_code', 50)->nullable();
            
            // Índices
            $table->index('case_id');
            $table->index(['channel_type', 'channel_id']);
            $table->index('direction');
            $table->index('received_at');
            $table->index('sent_at');
            $table->index('in_reply_to');
            
            // Foreign keys
            $table->foreign('case_id')->references('id')->on('cases');
            $table->foreign('in_reply_to')->references('id')->on('communications')->nullOnDelete();
        });

        // Phone Calls
        Schema::create('phone_calls', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->string('direction', 20);
            
            // Datos específicos de llamada
            $table->integer('call_duration_seconds')->nullable();
            $table->string('caller_id', 100)->nullable();
            
            // Contenido
            $table->text('call_summary')->nullable();
            $table->text('notes')->nullable();
            
            // Timestamps inmutables
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // WhatsApp Messages
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->string('direction', 20);
            
            // Identificadores WhatsApp
            $table->string('whatsapp_id')->nullable();
            $table->string('conversation_id')->nullable();
            
            // Contenido
            $table->string('message_type', 50)->nullable();
            $table->text('content_text')->nullable();
            $table->string('media_url', 500)->nullable();
            $table->string('media_filename')->nullable();
            $table->string('media_mime_type', 100)->nullable();
            
            $table->timestamp('created_at')->useCurrent();
        });

        // SMS Messages
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->string('direction', 20);
            
            // Contenido SMS
            $table->text('content_text');
            
            // Metadatos SMS
            $table->string('sms_provider', 50)->nullable();
            $table->string('provider_message_id')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
        });

        // Web Chat Messages
        Schema::create('webchat_messages', function (Blueprint $table) {
            $table->id();
            
            // Identificación del visitante
            $table->string('visitor_id');
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            
            $table->string('direction', 20);
            
            // Contenido
            $table->text('message_text');
            $table->string('message_type', 50)->default('text');
            
            // Metadatos del chat
            $table->string('session_id')->nullable();
            $table->string('page_url', 500)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webchat_messages');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('phone_calls');
        Schema::dropIfExists('communications');
    }
};
