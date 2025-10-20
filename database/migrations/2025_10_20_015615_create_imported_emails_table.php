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
        Schema::create('imported_emails', function (Blueprint $table) {
            $table->id();
            
            // Gmail API data
            $table->string('gmail_message_id')->unique()->comment('ID de Gmail API');
            $table->string('gmail_thread_id')->comment('Hilo de Gmail');
            $table->foreignId('gmail_group_id')
                  ->constrained('gmail_groups')->onDelete('cascade')
                  ->comment('Grupo destino');
            
            // Email data
            $table->text('subject');
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('to_email')->comment('Alias destino');
            $table->json('cc_emails')->nullable();
            $table->json('bcc_emails')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('imported_at')->useCurrent();
            $table->boolean('has_attachments')->default(false);
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            
            // Reference code and auto-assignment
            $table->foreignId('reference_code_id')->nullable()
                  ->constrained('reference_codes')->nullOnDelete();
            $table->string('rut_empleador', 8)->nullable()->comment('Extraído o asignado por ejecutivo');
            $table->string('dv_empleador', 1)->nullable();
            
            // Assignment
            $table->foreignId('assigned_to')->nullable()
                  ->constrained('users')->nullOnDelete()
                  ->comment('Ejecutivo asignado');
            $table->foreignId('assigned_by')->nullable()
                  ->constrained('users')->nullOnDelete()
                  ->comment('Supervisor que asigna');
            $table->timestamp('assigned_at')->nullable();
            $table->text('assignment_notes')->nullable()->comment('Notas del supervisor');
            
            // Case status
            $table->enum('case_status', [
                'pending', 'assigned', 'opened', 'in_progress', 
                'pending_closure', 'resolved', 'spam_marked'
            ])->default('pending');
            $table->timestamp('marked_resolved_at')->nullable();
            $table->timestamp('auto_resolved_at')->nullable()->comment('Auto-resuelto después de 2 días');
            $table->foreignId('spam_marked_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('spam_marked_at')->nullable();
            
            // Derivation to supervisor
            $table->boolean('derived_to_supervisor')->default(false);
            $table->text('derivation_notes')->nullable();
            $table->timestamp('derived_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('gmail_message_id');
            $table->index('gmail_thread_id');
            $table->index('to_email');
            $table->index('case_status');
            $table->index('assigned_to');
            $table->index(['rut_empleador', 'dv_empleador']);
            $table->index('received_at');
            $table->index('imported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_emails');
    }
};
