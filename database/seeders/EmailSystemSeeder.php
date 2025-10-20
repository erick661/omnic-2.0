<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserRole;
use App\Models\GmailGroup;
use App\Models\ReferenceCode;
use App\Models\ImportedEmail;
use Illuminate\Support\Facades\Hash;

class EmailSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear usuarios base
        $admin = User::create([
            'name' => 'Administrador Sistema',
            'email' => 'admin@orpro.cl',
            'password' => Hash::make('admin123'),
            'role' => 'administrador',
            'is_active' => true,
        ]);

        $supervisor = User::create([
            'name' => 'María González',
            'email' => 'maria.gonzalez@orpro.cl',
            'password' => Hash::make('supervisor123'),
            'role' => 'supervisor',
            'is_active' => true,
        ]);

        // Supervisor también tiene rol masivo
        UserRole::create([
            'user_id' => $supervisor->id,
            'role' => 'masivo',
        ]);

        $ejecutivo1 = User::create([
            'name' => 'Lucas Muñoz',
            'email' => 'lucas.munoz@orpro.cl',
            'password' => Hash::make('ejecutivo123'),
            'role' => 'ejecutivo',
            'email_alias' => 'lucas.munoz@orpro.cl',
            'is_active' => true,
        ]);

        $ejecutivo2 = User::create([
            'name' => 'Ana Silva',
            'email' => 'ana.silva@orpro.cl',
            'password' => Hash::make('ejecutivo123'),
            'role' => 'ejecutivo',
            'email_alias' => 'ana.silva@orpro.cl',
            'is_active' => true,
        ]);

        $masivoUser = User::create([
            'name' => 'Carlos Pérez',
            'email' => 'carlos.perez@orpro.cl',
            'password' => Hash::make('masivo123'),
            'role' => 'masivo',
            'is_active' => true,
        ]);

        // 2. Crear grupos Gmail
        $contactenos = GmailGroup::create([
            'name' => 'Contacto General',
            'email' => 'contactenos@orpro.cl',
            'is_active' => true,
            'is_generic' => true,
            'assigned_user_id' => $supervisor->id,
            'gmail_label' => 'contactenos',
        ]);

        $grupoLucas = GmailGroup::create([
            'name' => 'Lucas Muñoz',
            'email' => 'lucas.munoz@orpro.cl',
            'is_active' => true,
            'is_generic' => false,
            'gmail_label' => 'lucas-munoz',
        ]);

        $grupoAna = GmailGroup::create([
            'name' => 'Ana Silva',
            'email' => 'ana.silva@orpro.cl',
            'is_active' => true,
            'is_generic' => false,
            'gmail_label' => 'ana-silva',
        ]);

        // 3. Crear códigos de referencia
        $refCode1 = ReferenceCode::create([
            'rut_empleador' => '12345678',
            'dv_empleador' => '9',
            'producto' => 'AFP-CAPITAL',
            'code_hash' => ReferenceCode::generateCodeHash('12345678', '9', 'AFP-CAPITAL'),
            'assigned_user_id' => $ejecutivo1->id,
        ]);

        $refCode2 = ReferenceCode::create([
            'rut_empleador' => '87654321',
            'dv_empleador' => '0',
            'producto' => 'AFP-HABITAT',
            'code_hash' => ReferenceCode::generateCodeHash('87654321', '0', 'AFP-HABITAT'),
            'assigned_user_id' => $ejecutivo2->id,
        ]);

        // 4. Crear correos de prueba
        $email1 = ImportedEmail::create([
            'gmail_message_id' => 'gmail_msg_001',
            'gmail_thread_id' => 'gmail_thread_001',
            'gmail_group_id' => $contactenos->id,
            'subject' => 'Consulta sobre AFP Capital - Empresa ABC SA',
            'from_email' => 'rrhh@empresaabc.cl',
            'from_name' => 'Recursos Humanos ABC',
            'to_email' => 'contactenos@orpro.cl',
            'body_html' => '<p>Estimados, necesitamos información sobre el estado de los empleados en AFP Capital.</p>',
            'body_text' => 'Estimados, necesitamos información sobre el estado de los empleados en AFP Capital.',
            'received_at' => now()->subHours(2),
            'imported_at' => now()->subHours(1),
            'priority' => 'normal',
            'case_status' => 'pending',
        ]);

        $email2 = ImportedEmail::create([
            'gmail_message_id' => 'gmail_msg_002',
            'gmail_thread_id' => 'gmail_thread_002',
            'gmail_group_id' => $grupoLucas->id,
            'subject' => 'RE: Consulta AFP - [REF-' . $refCode1->code_hash . '-AFP-CAPITAL]',
            'from_email' => 'rrhh@empresaabc.cl',
            'from_name' => 'Recursos Humanos ABC',
            'to_email' => 'lucas.munoz@orpro.cl',
            'body_html' => '<p>Estimado Lucas, gracias por la respuesta anterior. Tengo una consulta adicional...</p>',
            'body_text' => 'Estimado Lucas, gracias por la respuesta anterior. Tengo una consulta adicional...',
            'received_at' => now()->subMinutes(30),
            'imported_at' => now()->subMinutes(15),
            'reference_code_id' => $refCode1->id,
            'rut_empleador' => '12345678',
            'dv_empleador' => '9',
            'assigned_to' => $ejecutivo1->id,
            'assigned_at' => now()->subMinutes(10),
            'case_status' => 'assigned',
            'priority' => 'normal',
        ]);

        $email3 = ImportedEmail::create([
            'gmail_message_id' => 'gmail_msg_003',
            'gmail_thread_id' => 'gmail_thread_003',
            'gmail_group_id' => $grupoAna->id,
            'subject' => 'Urgente: Problema con cotizaciones AFP Habitat',
            'from_email' => 'finanzas@empresa123.cl',
            'from_name' => 'Finanzas Empresa 123',
            'to_email' => 'ana.silva@orpro.cl',
            'body_html' => '<p><strong>URGENTE:</strong> Tenemos un problema con las cotizaciones del mes pasado...</p>',
            'body_text' => 'URGENTE: Tenemos un problema con las cotizaciones del mes pasado...',
            'received_at' => now()->subDays(3),
            'imported_at' => now()->subDays(3),
            'assigned_to' => $ejecutivo2->id,
            'assigned_by' => $supervisor->id,
            'assigned_at' => now()->subDays(2),
            'case_status' => 'in_progress',
            'priority' => 'high',
        ]);

        // Correo vencido (más de 2 días)
        $email4 = ImportedEmail::create([
            'gmail_message_id' => 'gmail_msg_004',
            'gmail_thread_id' => 'gmail_thread_004',
            'gmail_group_id' => $contactenos->id,
            'subject' => 'Consulta general sobre servicios',
            'from_email' => 'contacto@cliente.cl',
            'from_name' => 'Cliente Potencial',
            'to_email' => 'contactenos@orpro.cl',
            'body_html' => '<p>Buenos días, me gustaría conocer más sobre sus servicios...</p>',
            'body_text' => 'Buenos días, me gustaría conocer más sobre sus servicios...',
            'received_at' => now()->subDays(5),
            'imported_at' => now()->subDays(5),
            'assigned_to' => $ejecutivo1->id,
            'assigned_by' => $supervisor->id,
            'assigned_at' => now()->subDays(4),
            'case_status' => 'assigned',
            'priority' => 'normal',
        ]);

        $this->command->info('✅ Datos de prueba creados exitosamente:');
        $this->command->info("- 5 usuarios (admin, supervisor+masivo, 2 ejecutivos, 1 masivo)");
        $this->command->info("- 3 grupos Gmail (contactenos + 2 ejecutivos)");
        $this->command->info("- 2 códigos de referencia");
        $this->command->info("- 4 correos de prueba con diferentes estados");
        $this->command->info("\n🔑 Credenciales de acceso:");
        $this->command->info("Admin: admin@orpro.cl / admin123");
        $this->command->info("Supervisor: maria.gonzalez@orpro.cl / supervisor123");
        $this->command->info("Ejecutivo 1: lucas.munoz@orpro.cl / ejecutivo123");
        $this->command->info("Ejecutivo 2: ana.silva@orpro.cl / ejecutivo123");
        $this->command->info("Masivo: carlos.perez@orpro.cl / masivo123");
    }
}
