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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject', 500);
            $table->longText('html_content');
            $table->longText('text_content')->nullable();
            $table->string('from_email')->comment('orpro@orpro.cl');
            $table->string('from_name');
            
            // Programación
            $table->enum('status', [
                'draft', 'scheduled', 'sending', 'sent', 'completed', 'failed'
            ])->default('draft');
            $table->timestamp('scheduled_at')->nullable()->comment('Dentro de horario legal Chile');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Estadísticas Brevo
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('bounced_count')->default(0);
            
            // Brevo
            $table->unsignedBigInteger('brevo_campaign_id')->nullable();
            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
