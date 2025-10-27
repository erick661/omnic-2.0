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
        // Gmail Groups - Arquitectura corporativa
        Schema::create('gmail_groups', function (Blueprint $table) {
            $table->id();
            
            // Identificación del grupo
            $table->string('group_email')->unique(); // ejecutivo.juan.perez@orpro.cl
            $table->string('group_name'); // "Juan Pérez"
            $table->string('group_type', 50); // 'personal', 'generic'
            
            // Asignación del ejecutivo responsable
            $table->unsignedBigInteger('assigned_user_id');
            
            // Control de importación
            $table->boolean('import_enabled')->default(true);
            $table->string('gmail_label')->nullable();
            
            // Estado
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Índices
            $table->index('group_email');
            $table->index('assigned_user_id');
            $table->index('group_type');
            $table->index('import_enabled');
            
            // Foreign key
            $table->foreign('assigned_user_id')->references('id')->on('users');
        });

        // Gmail Group Members - Gestión de membresías
        Schema::create('gmail_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gmail_group_id');
            
            // Miembro del grupo (casi siempre comunicaciones@orpro.cl)
            $table->string('member_email'); // comunicaciones@orpro.cl
            $table->string('member_role', 50)->default('MEMBER'); // MEMBER, MANAGER, OWNER
            
            // Control
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamp('added_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
            
            // Índices
            $table->index('gmail_group_id');
            $table->index('member_email');
            $table->unique(['gmail_group_id', 'member_email']); // Un email por grupo
            
            // Foreign key
            $table->foreign('gmail_group_id')->references('id')->on('gmail_groups')->cascadeOnDelete();
        });

        // Ahora podemos agregar foreign keys a emails
        Schema::table('emails', function (Blueprint $table) {
            $table->foreign('gmail_group_id')->references('id')->on('gmail_groups')->nullOnDelete();
            $table->foreign('parent_email_id')->references('id')->on('emails')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropForeign(['gmail_group_id']);
            $table->dropForeign(['parent_email_id']);
        });
        
        Schema::dropIfExists('gmail_group_members');
        Schema::dropIfExists('gmail_groups');
    }
};
