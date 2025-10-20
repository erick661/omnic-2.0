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
        Schema::create('outbox_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_email_id')->nullable()
                  ->constrained('imported_emails')->nullOnDelete()
                  ->comment('Correo original que responde');
            
            // Email data
            $table->string('from_email')->comment('lucas.munoz@orpro.cl');
            $table->string('from_name')->comment('Lucas Muñoz');
            $table->string('to_email');
            $table->json('cc_emails')->nullable();
            $table->json('bcc_emails')->nullable();
            $table->text('subject')->comment('Con código de referencia si aplica');
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            
            // Send control
            $table->enum('send_status', ['pending', 'sending', 'sent', 'failed'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            
            // Case closure
            $table->boolean('mark_as_resolved')->default(false)->comment('Checkbox del ejecutivo');
            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes
            $table->index('imported_email_id');
            $table->index('send_status');
            $table->index('scheduled_at');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbox_emails');
    }
};
