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
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_email_id')
                  ->constrained('imported_emails')->onDelete('cascade');
            $table->string('original_filename');
            $table->string('stored_filename')->comment('UUID único');
            $table->string('file_path', 500)->comment('storage/attachments/');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->comment('bytes');
            $table->timestamps();
            
            $table->index('imported_email_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
