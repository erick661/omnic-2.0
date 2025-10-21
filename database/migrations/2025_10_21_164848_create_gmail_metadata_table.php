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
        Schema::create('gmail_metadata', function (Blueprint $table) {
            $table->id();
            
            // Relación con comunicación
            $table->foreignId('communication_id')
                  ->constrained('communications')->onDelete('cascade')
                  ->comment('Comunicación asociada');
            
            // IDs únicos de Gmail
            $table->string('gmail_message_id')->unique()
                  ->comment('ID único del mensaje en Gmail API');
            $table->string('gmail_thread_id')
                  ->comment('ID del hilo de conversación en Gmail');
            $table->string('gmail_history_id')->nullable()
                  ->comment('ID de historial para sincronización incremental');
            
            // Datos específicos de Gmail
            $table->json('gmail_labels')->nullable()
                  ->comment('Etiquetas Gmail (INBOX, SENT, SPAM, etc.)');
            $table->text('gmail_snippet')->nullable()
                  ->comment('Vista previa generada por Gmail');
            $table->integer('size_estimate')->nullable()
                  ->comment('Tamaño del mensaje en bytes');
            
            // Metadatos del mensaje original
            $table->json('raw_headers')->nullable()
                  ->comment('Headers completos del email');
            $table->text('message_references')->nullable()
                  ->comment('Referencias para hilos de conversación');
            $table->string('in_reply_to')->nullable()
                  ->comment('ID del mensaje padre');
            
            // URLs y archivos para auditoría
            $table->string('eml_download_url', 500)->nullable()
                  ->comment('URL temporal para descargar EML');
            $table->string('eml_backup_path', 500)->nullable()
                  ->comment('Ruta del backup local del EML');
            $table->json('attachments_metadata')->nullable()
                  ->comment('Info de adjuntos con IDs Gmail');
            
            // Estado de sincronización
            $table->enum('sync_status', ['pending', 'synced', 'error'])
                  ->default('pending')
                  ->comment('Estado de sincronización con Gmail');
            $table->timestamp('last_sync_at')->nullable()
                  ->comment('Última sincronización exitosa');
            $table->text('sync_error_message')->nullable()
                  ->comment('Mensaje de error de sincronización');
            
            // Auditoría
            $table->boolean('is_backed_up')->default(false)
                  ->comment('Si el EML está respaldado localmente');
            $table->timestamp('backup_at')->nullable()
                  ->comment('Cuándo se respaldó el EML');
            
            $table->timestamps();
            
            // Índices para performance
            $table->index('gmail_message_id');
            $table->index('gmail_thread_id');
            $table->index('communication_id');
            $table->index('sync_status');
            $table->index('is_backed_up');
            $table->index('last_sync_at');
            
            // Una comunicación = Un registro Gmail
            $table->unique('communication_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmail_metadata');
    }
};
