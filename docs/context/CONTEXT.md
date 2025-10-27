# CONTEXTO DE DESARROLLO - OMNIC 2.0

## 📋 Información General
- **Proyecto:** OMNIC 2.0 - Sistema de gestión de correos
- **Framework:** Laravel 11 + Livewire Volt + TailwindCSS
- **Base de Datos:** PostgreSQL
- **Usuario Autenticado:** Lucas Muñoz (ID: 16069813, lucas.munoz@orpro.cl)
- **Ambiente:** Desarrollo en https://dev-estadisticas.orpro.cl
- **Fecha Última Actualización:** 2025-10-26

## 🗄️ Estructura de Base de Datos

### Tabla: imported_emails
```sql
- id (bigint, primary key)
- gmail_message_id (string) - ID único del mensaje en Gmail
- gmail_thread_id (string) - ID del hilo de Gmail
- gmail_group_id (bigint) - FK a gmail_groups
- subject (string) - Asunto del correo
- from_email (string) - Email del remitente
- from_name (string) - Nombre del remitente
- to_email (string) - Email destinatario
- cc_emails (text) - Correos en copia
- bcc_emails (text) - Correos en copia oculta
- body_html (text) - Cuerpo HTML
- body_text (text) - Cuerpo texto plano
- received_at (timestamp) - Fecha de recepción
- imported_at (timestamp) - Fecha de importación
- has_attachments (boolean) - Tiene adjuntos
- priority (string) - Prioridad del caso
- reference_code_id (bigint) - Código de referencia
- rut_empleador (string) - RUT empleador
- dv_empleador (string) - DV empleador
- assigned_to (bigint) - Usuario asignado
- assigned_by (bigint) - Usuario que asignó
- assigned_at (timestamp) - Fecha de asignación
- assignment_notes (text) - Notas de asignación
- case_status (string) - Estado: pending, assigned, opened, in_progress, resolved, closed
- marked_resolved_at (timestamp) - Fecha resolución
- auto_resolved_at (timestamp) - Fecha auto-resolución
- spam_marked_by (bigint) - Usuario que marcó spam
- spam_marked_at (timestamp) - Fecha marcado spam
- derived_to_supervisor (boolean) - Derivado a supervisor
- derivation_notes (text) - Notas de derivación
- derived_at (timestamp) - Fecha derivación
```

### Tabla: gmail_groups
```sql
- id (bigint, primary key)
- name (string) - Nombre del grupo (ej: "Ejecutivo Lucas Muñoz")
- email (string) - Email del grupo (ej: "ejecutivo.lucas.munoz@orproverificaciones.cl")
- is_active (boolean) - Estado activo
- gmail_label (string) - Etiqueta Gmail asociada
```

### Relaciones
- ImportedEmail belongsTo GmailGroup (gmail_group_id)

## 🏗️ Arquitectura del Sistema

### Modelos Principales
- **ImportedEmail** (`app/Models/ImportedEmail.php`)
  - Relación: `gmailGroup()`
  - Scopes: `pending()`, `assigned()`, `resolved()`, `overdue()`
  
- **GmailGroup** (`app/Models/GmailGroup.php`)
  - Relación: `importedEmails()`
  - Scope: `active()`

### 🏗️ Nueva Arquitectura de Servicios (Reorganizada)

**Servicios Base:**
- `App\Services\Base\GoogleApiService`: Clase base para todas las APIs de Google
  - Manejo de Service Account
  - Sistema de reintentos y cache de tokens
  - Configuración centralizada de scopes y autenticación

**Servicios de Email:**
- `App\Services\Email\EmailImportService`: Importación de correos desde Gmail
- `App\Services\Email\EmailSendService`: Envío de correos a través de Gmail API
- `App\Services\Email\OutboxEmailService`: Gestión de bandeja de salida
- `App\Services\Email\EmailStatsService`: Estadísticas y reportes de correos

**Servicios de Grupos:**
- `App\Services\Groups\GmailGroupService`: Gestión de grupos de Gmail

**Servicios Adicionales (Base creada):**
- `App\Services\Drive\DriveService`: Gestión de Google Drive
- `App\Services\Chat\ChatService`: Integración con Google Chat

### 📁 Nueva Estructura de Comandos

**Comandos de Email:** `app/Console/Commands/Email/`
- `ImportEmailsCommand`: Importación avanzada de correos
- `ProcessOutboxCommand`: Procesamiento de bandeja de salida
- `SendEmailCommand`: Envío directo de correos
- `EmailStatsCommand`: Estadísticas del sistema

**Comandos de Grupos:** `app/Console/Commands/Groups/`
- `ListGmailGroupsCommand`: Listado de grupos
- `CreateGmailGroupCommand`: Creación de grupos
- `ManageGmailGroupMembersCommand`: Gestión de miembros

**Comandos de Sistema:** `app/Console/Commands/System/`
- `OrganizeCommandsCommand`: Reorganización de comandos existentes

**Comandos Base:** `app/Console/Commands/Drive/` y `app/Console/Commands/Chat/`
- Estructura base para futuras funcionalidades

### Servicios Legacy (A migrar)
- **GmailService** (`app/Services/GmailService.php`) - Service Account, domain-wide delegation

### Comandos Artisan
- `php artisan emails:import` - Importar correos nuevos
- `php artisan gmail:send-test {email}` - Enviar email de prueba
- `php artisan gmail:group-members add {grupo} --user={usuario}` - Gestión grupos
- `php artisan email:assign {emailId} {agentId}` - Asignar correo a agente

## 🖥️ Componentes de Interfaz

### Panel Agente (`/resources/views/livewire/inbox/agente.blade.php`)
**Estado:** ✅ FUNCIONANDO
**URL:** https://dev-estadisticas.orpro.cl/debug/agente

**Funcionalidad:**
- Muestra correos asignados al usuario autenticado
- Filtro por `assigned_to = 16069813` (Lucas Muñoz)
- Estados: asignados, en_progreso, resueltos
- Kanban board con 3 columnas

**Métodos principales:**
```php
getAuthUser() - Usuario autenticado real
casosAgentes() - Consulta correos asignados
mapCaseStatus() - Mapea estados de BD a UI
determinePriority() - Calcula prioridad automática
determineCategory() - Categoriza por asunto
```

### Panel Supervisor (`/resources/views/livewire/inbox/supervisor.blade.php`)
**Estado:** ✅ FUNCIONANDO
**URL:** https://dev-estadisticas.orpro.cl/debug/supervisor

**Funcionalidad:**
- Estadísticas generales
- Correos pendientes de asignación
- Correos asignados recientes
- Correos vencidos

## 📧 Configuración Gmail

### Service Account
- **Archivo:** `/var/www/omnic/storage/app/google-credentials/google-service-account.json`
- **Variable:** `GOOGLE_APPLICATION_CREDENTIALS`
- **Impersonation:** admin@orproverificaciones.cl
- **Domain:** orproverificaciones.cl

### APIs Habilitadas
- Gmail API
- Google Directory API (para gestión de grupos)

### Grupos Gmail Importantes
- `ejecutivo.lucas.munoz@orproverificaciones.cl` - Grupo de Lucas
- Total grupos: ~48 grupos activos

## 🔧 Estado Actual del Sistema

### ✅ Funcionando Correctamente
1. **Autenticación Service Account** - Gmail API operativa
2. **Importación de correos** - 22 correos en BD
3. **Panel Agente** - 6 correos asignados a Lucas visibles
4. **Panel Supervisor** - Estadísticas y asignación funcional
5. **Comandos CLI** - Todos operativos

### 📊 Datos Actuales
- **Usuario Activo:** Lucas Muñoz (ID: 16069813)
- **Correos Totales:** 22 en imported_emails
- **Correos Asignados a Lucas:** 6 correos
- **Correo de Prueba:** "Consulta sobre verificación de empresa ABC" (ID: 25) ✅ VISIBLE

### 🎯 Correo Específico Verificado
**ID:** 25
**Asunto:** "Consulta sobre verificación de empresa ABC"
**De:** lucas.munoz@orpro.cl
**Estado:** assigned
**Asignado a:** 16069813 (Lucas Muñoz)
**Grupo:** ejecutivo.lucas.munoz@orproverificaciones.cl
**Visible en Panel:** ✅ SÍ

## 🛠️ Configuración Técnica

### Variables de Entorno Críticas
```env
APP_URL=https://dev-estadisticas.orpro.cl
DB_CONNECTION=pgsql
DB_DATABASE=laravel_db
GOOGLE_APPLICATION_CREDENTIALS=/var/www/omnic/storage/app/google-credentials/google-service-account.json
GOOGLE_WORKSPACE_ADMIN_EMAIL=admin@orproverificaciones.cl
AUTH_INTRA_URL=https://intra.orpro.cl/index.php
```

### Rutas de Debug (Sin Autenticación)
```php
/debug/agente - Panel agente
/debug/supervisor - Panel supervisor
/debug/test - Componente de prueba
```

## 🔄 Flujo de Trabajo Actual

1. **Gmail** → Service Account importa correos → `imported_emails` table
2. **Supervisor** → Asigna correos → `assigned_to` field
3. **Agente** → Ve correos asignados → Panel Kanban
4. **Agente** → Cambia estados → `case_status` field

## 🚨 Problemas Resueltos Recientemente

### ❌ Usuario Simulado vs Real
**Problema:** Componente usaba usuario ficticio (ID: 1)
**Solución:** Actualizado a usuario real (ID: 16069813)
**Resultado:** Correos ahora visibles en panel

### ❌ Filtro de Correos Incorrecto
**Problema:** Consulta no filtraba por usuario asignado
**Solución:** Agregado `->where('assigned_to', $currentUserId)`
**Resultado:** Solo correos del usuario actual

### ❌ Estados de Caso Inconsistentes
**Problema:** Mapeo de estados BD → UI confuso
**Solución:** Método `mapCaseStatus()` con lógica clara
**Resultado:** Estados consistentes en interfaz

## 📝 Comandos de Verificación Útiles

```bash
# Verificar correos asignados
php artisan tinker --execute="
echo App\Models\ImportedEmail::where('assigned_to', 16069813)->count();
"

# Ver estado de autenticación Gmail
php artisan gmail:test-auth

# Importar correos nuevos
php artisan emails:import

# Enviar email de prueba
php artisan gmail:send-test ejecutivo.lucas.munoz@orproverificaciones.cl
```

## 🎯 Próximos Desarrollos Potenciales

1. **Sistema de Comentarios** - Agregar tabla `email_comments`
2. **Notificaciones Real-time** - WebSockets o Pusher
3. **Reportes y Métricas** - Dashboard de estadísticas
4. **Plantillas de Respuesta** - Templates automáticos
5. **Escalamiento Automático** - Reglas de derivación

---

**Última Actualización:** 2025-10-26 por Copilot
**Estado General:** ✅ SISTEMA OPERATIVO Y FUNCIONAL