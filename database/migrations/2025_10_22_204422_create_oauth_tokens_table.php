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
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('gmail')->comment('gmail, outlook, etc.');
            $table->string('identifier')->nullable()->comment('email o user_id asociado');
            $table->text('access_token')->comment('Token encriptado');
            $table->text('refresh_token')->nullable()->comment('Refresh token encriptado');
            $table->json('scopes')->nullable()->comment('Scopes otorgados');
            $table->timestamp('expires_at')->nullable()->comment('Cuándo expira el access token');
            $table->json('metadata')->nullable()->comment('Info adicional del token');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['provider', 'identifier']);
            $table->index(['provider', 'is_active']);
            $table->unique(['provider', 'identifier'], 'oauth_provider_identifier_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
