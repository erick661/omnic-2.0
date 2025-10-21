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
        Schema::table('reference_codes', function (Blueprint $table) {
            // Vinculación con casos omnicanal
            $table->foreignId('case_id')->nullable()
                  ->constrained('cases')->nullOnDelete()
                  ->comment('Caso asociado al código de referencia')
                  ->after('id');
            
            // Canal que generó el código
            $table->enum('channel_type', ['email', 'whatsapp', 'sms', 'phone', 'webchat', 'campaign'])
                  ->nullable()
                  ->comment('Canal que generó este código de referencia')
                  ->after('case_id');
            
            // Metadatos del canal
            $table->json('channel_metadata')->nullable()
                  ->comment('Datos específicos del canal al generar el código')
                  ->after('channel_type');
            
            // Seguimiento
            $table->integer('usage_count')->default(0)
                  ->comment('Cantidad de veces que se ha usado este código')
                  ->after('channel_metadata');
            
            $table->timestamp('last_used_at')->nullable()
                  ->comment('Última vez que se usó el código')
                  ->after('usage_count');
            
            // Índices
            $table->index('case_id');
            $table->index('channel_type');
            $table->index('usage_count');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_codes', function (Blueprint $table) {
            $table->dropForeign(['case_id']);
            $table->dropIndex(['case_id']);
            $table->dropIndex(['channel_type']);
            $table->dropIndex(['usage_count']);
            $table->dropIndex(['last_used_at']);
            
            $table->dropColumn([
                'case_id',
                'channel_type',
                'channel_metadata',
                'usage_count',
                'last_used_at'
            ]);
        });
    }
};
