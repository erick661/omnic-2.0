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
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            
            // Vinculación al caso
            $table->foreignId('case_id')
                  ->constrained('cases')->onDelete('cascade')
                  ->comment('Caso al que pertenece esta comunicación');
            
            // Tipo y canal
            $table->enum('channel_type', ['email', 'whatsapp', 'sms', 'phone', 'webchat'])
                  ->comment('Canal de comunicación');
            $table->enum('direction', ['inbound', 'outbound'])
                  ->comment('Dirección de la comunicación');
            
            // Identificadores específicos por canal
            $table->string('external_id')->nullable()
                  ->comment('ID externo del canal (Gmail message_id, WhatsApp message_id, etc.)');
            $table->string('thread_id')->nullable()
                  ->comment('ID de hilo para agrupar conversaciones del mismo canal');
            
            // Contenido común
            $table->string('subject', 500)->nullable()
                  ->comment('Asunto para email, título para otros canales');
            $table->longText('content_text')->nullable()
                  ->comment('Contenido en texto plano');
            $table->longText('content_html')->nullable()
                  ->comment('Contenido en HTML');
            
            // Participantes
            $table->string('from_contact')->nullable()
                  ->comment('Email, teléfono o ID de usuario que envía');
            $table->string('from_name')->nullable()
                  ->comment('Nombre del remitente');
            $table->string('to_contact')->nullable()
                  ->comment('Email, teléfono o ID de usuario que recibe');
            $table->json('cc_contacts')->nullable()
                  ->comment('Lista de contactos en copia');
            
            // Metadatos específicos por canal
            $table->json('channel_metadata')->nullable()
                  ->comment('Datos específicos del canal (headers email, metadata WhatsApp, etc.)');
            $table->json('attachments')->nullable()
                  ->comment('Lista de adjuntos/multimedia');
            
            // Estados y fechas
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])
                  ->default('pending')
                  ->comment('Estado de la comunicación');
            $table->timestamp('received_at')->nullable()
                  ->comment('Fecha de recepción');
            $table->timestamp('sent_at')->nullable()
                  ->comment('Fecha de envío');
            $table->timestamp('delivered_at')->nullable()
                  ->comment('Fecha de entrega confirmada');
            $table->timestamp('read_at')->nullable()
                  ->comment('Fecha de lectura confirmada');
            
            // Vinculación y referencias
            $table->string('reference_code', 50)->nullable()
                  ->comment('Código de referencia para seguimiento');
            $table->foreignId('in_reply_to')->nullable()
                  ->constrained('communications')->nullOnDelete()
                  ->comment('Comunicación a la que responde');
            
            // Gestión
            $table->timestamp('processed_at')->nullable()
                  ->comment('Fecha de procesamiento');
            $table->foreignId('processed_by')->nullable()
                  ->constrained('users')->nullOnDelete()
                  ->comment('Usuario que procesó la comunicación');
            
            $table->timestamps();
            
            // Índices para performance
            $table->index('case_id');
            $table->index('channel_type');
            $table->index('direction');
            $table->index('external_id');
            $table->index('thread_id');
            $table->index('status');
            $table->index('received_at');
            $table->index('sent_at');
            $table->index('reference_code');
            $table->index(['case_id', 'channel_type']);
            $table->index(['case_id', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
