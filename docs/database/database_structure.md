# Estructura de Base de Datos - OMNIC 2.0

## Resumen General
- **Motor de Base de Datos**: PostgreSQL
- **Total de Tablas**: 29 tablas
- **Schema**: public

## Categorización de Tablas

### 1. Sistema Laravel/Framework
- `cache` - Cache del sistema
- `cache_locks` - Bloqueos de cache
- `failed_jobs` - Jobs fallidos
- `job_batches` - Batches de jobs
- `jobs` - Cola de trabajos
- `migrations` - Historial de migraciones
- `password_reset_tokens` - Tokens de reseteo de contraseña
- `sessions` - Sesiones de usuario

### 2. Gestión de Usuarios y Autenticación
- `users` - Usuarios del sistema
- `user_roles` - Roles de usuarios
- `oauth_tokens` - Tokens OAuth (Gmail/Google)

### 3. Contactos y Listas
- `contacts` - Contactos principales
- `contact_lists` - Listas de contactos
- `contact_list_members` - Miembros de listas de contactos

### 4. Comunicaciones y Casos
- `cases` - Casos de atención al cliente
- `case_metrics` - Métricas de casos
- `communications` - Comunicaciones generales
- `phone_communications` - Comunicaciones telefónicas específicas

### 5. Gestión de Email
- `imported_emails` - Emails importados desde Gmail
- `outbox_emails` - Emails de salida
- `email_attachments` - Adjuntos de emails importados
- `outbox_attachments` - Adjuntos de emails de salida
- `gmail_groups` - Grupos de Gmail monitoreados
- `gmail_metadata` - Metadatos específicos de Gmail

### 6. Campañas y Marketing
- `campaigns` - Campañas generales
- `campaign_sends` - Envíos de campañas
- `email_campaigns` - Campañas de email específicas

### 7. Sistema de Referencias
- `reference_codes` - Códigos de referencia para seguimiento

### 8. Configuración del Sistema
- `system_config` - Configuraciones del sistema

## Análisis Detallado por Tabla

### Tabla: `users`
**Propósito**: Gestión de usuarios del sistema
**Campos Clave**:
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `name` (VARCHAR 255, NOT NULL)
- `email` (VARCHAR 255, NOT NULL)
- `role` (VARCHAR 255, DEFAULT 'ejecutivo')
- `is_active` (BOOLEAN, DEFAULT true)
- `email_alias` (VARCHAR 255, NULLABLE)
- `nickname` (VARCHAR 255, NULLABLE)

### Tabla: `contacts`
**Propósito**: Almacena información de contactos
**Campos Clave**:
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `email` (VARCHAR 255, NOT NULL)
- `first_name`, `last_name` (VARCHAR 255, NULLABLE)
- `company` (VARCHAR 255, NULLABLE)
- `rut_empleador` (VARCHAR 8, NULLABLE)
- `dv_empleador` (VARCHAR 1, NULLABLE)
- `producto` (VARCHAR 50, NULLABLE)
- `phone` (VARCHAR 50, NULLABLE)
- `attributes` (JSON, NULLABLE)
- `is_active` (BOOLEAN, DEFAULT true)
- `brevo_contact_id` (BIGINT, NULLABLE)

### Tabla: `cases`
**Propósito**: Gestión de casos de atención al cliente
**Campos Clave**:
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `case_number` (VARCHAR 20, NOT NULL, UNIQUE)
- `employer_rut` (VARCHAR 8, NULLABLE)
- `employer_dv` (VARCHAR 1, NULLABLE)
- `employer_name`, `employer_phone`, `employer_email` (NULLABLE)
- `status` (VARCHAR 255, DEFAULT 'pending')
- `priority` (VARCHAR 255, DEFAULT 'normal')
- `assigned_to`, `assigned_by` (BIGINT, NULLABLE) - Referencias a users
- `origin_channel` (VARCHAR 255, NOT NULL)
- `origin_communication_id` (BIGINT, NULLABLE)
- `campaign_id` (BIGINT, NULLABLE)
- `auto_category` (VARCHAR 100, NULLABLE)
- `tags` (JSON, NULLABLE)
- `communication_count` (INTEGER, DEFAULT 0)

### Tabla: `communications`
**Propósito**: Comunicaciones generales del sistema
**Campos Clave**:
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `case_id` (BIGINT, NOT NULL) - FK a cases
- `channel_type` (VARCHAR 255, NOT NULL)
- `direction` (VARCHAR 255, NOT NULL)
- `external_id` (VARCHAR 255, NULLABLE) - ID externo (ej: Gmail ID)
- `thread_id` (VARCHAR 255, NULLABLE)
- `subject` (VARCHAR 500, NULLABLE)
- `content_text`, `content_html` (TEXT, NULLABLE)
- `from_contact`, `to_contact` (VARCHAR 255, NULLABLE)
- `cc_contacts`, `attachments` (JSON, NULLABLE)
- `status` (VARCHAR 255, DEFAULT 'pending')
- `reference_code` (VARCHAR 50, NULLABLE)
- `in_reply_to` (BIGINT, NULLABLE) - Auto-referencia

### Tabla: `imported_emails`
**Propósito**: Emails importados específicamente desde Gmail
**Campos Clave**:
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `gmail_message_id` (VARCHAR 255, NOT NULL, UNIQUE)
- `gmail_thread_id` (VARCHAR 255, NOT NULL)
- `gmail_group_id` (BIGINT, NOT NULL) - FK a gmail_groups
- `subject` (TEXT, NOT NULL)
- `from_email`, `to_email` (VARCHAR 255, NOT NULL)
- `from_name` (VARCHAR 255, NULLABLE)
- `cc_emails`, `bcc_emails` (JSON, NULLABLE)
- `body_html`, `body_text` (TEXT, NULLABLE)
- `received_at` (TIMESTAMP, NOT NULL)
- `imported_at` (TIMESTAMP, DEFAULT NOW)
- `has_attachments` (BOOLEAN, DEFAULT false)
- `priority` (VARCHAR 255, DEFAULT 'normal')
- `reference_code_id` (BIGINT, NULLABLE) - FK a reference_codes
- `rut_empleador`, `dv_empleador` (VARCHAR, NULLABLE)
- `assigned_to`, `assigned_by` (BIGINT, NULLABLE) - FK a users
- `case_status` (VARCHAR 255, DEFAULT 'pending')

### Tabla: `gmail_groups`
**Propósito**: Grupos de Gmail monitoreados por el sistema
**Campos Clave**:
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `name` (VARCHAR 255, NOT NULL)
- `email` (VARCHAR 255, NOT NULL)
- `is_active` (BOOLEAN, DEFAULT true)
- `is_generic` (BOOLEAN, DEFAULT false)
- `assigned_user_id` (BIGINT, NULLABLE) - FK a users
- `gmail_label` (VARCHAR 255, NULLABLE)

### Tabla: `gmail_metadata`
**Propósito**: Metadatos específicos de Gmail para comunicaciones
**Campos Clave**:
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `communication_id` (BIGINT, NOT NULL) - FK a communications
- `gmail_message_id` (VARCHAR 255, NOT NULL)
- `gmail_thread_id` (VARCHAR 255, NOT NULL)
- `gmail_history_id` (VARCHAR 255, NULLABLE)
- `gmail_labels` (JSON, NULLABLE)
- `gmail_snippet` (TEXT, NULLABLE)
- `size_estimate` (INTEGER, NULLABLE)
- `raw_headers` (JSON, NULLABLE)
- `sync_status` (VARCHAR 255, DEFAULT 'pending')
- `is_backed_up` (BOOLEAN, DEFAULT false)

### Tabla: `reference_codes`
**Propósito**: Sistema de códigos de referencia para seguimiento
**Campos Clave**:
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `rut_empleador` (VARCHAR 8, NOT NULL)
- `dv_empleador` (VARCHAR 1, NOT NULL)
- `producto` (VARCHAR 50, NOT NULL)
- `code_hash` (VARCHAR 255, NOT NULL)
- `assigned_user_id` (BIGINT, NOT NULL) - FK a users
- `case_id` (BIGINT, NULLABLE) - FK a cases
- `channel_type`, `channel_metadata` (NULLABLE)
- `usage_count` (INTEGER, DEFAULT 0)

## Relaciones Principales Identificadas

### Flujo Email → Caso
1. `imported_emails` → `gmail_groups` (FK: gmail_group_id)
2. `imported_emails` → `reference_codes` (FK: reference_code_id)
3. `imported_emails` → `users` (FK: assigned_to, assigned_by)

### Flujo Comunicación → Caso
1. `communications` → `cases` (FK: case_id)
2. `communications` → `gmail_metadata` (1:1 via communication_id)
3. `cases` → `users` (FK: assigned_to, assigned_by)

### Sistema de Adjuntos
1. `email_attachments` → `imported_emails` (FK: imported_email_id)
2. `outbox_attachments` → `outbox_emails` (FK: outbox_email_id)

### Gestión de Contactos
1. `contact_list_members` → `contacts` (FK: contact_id)
2. `contact_list_members` → `contact_lists` (FK: contact_list_id)

## Estados y Flujos de Trabajo

### Estados de Emails Importados (`imported_emails.case_status`)
- `pending` - Email recién importado, sin procesar
- `in_progress` - Email en proceso de atención
- `resolved` - Email resuelto
- `spam` - Marcado como spam

### Estados de Casos (`cases.status`)
- `pending` - Caso pendiente de asignación
- `assigned` - Caso asignado a un usuario
- `in_progress` - Caso en proceso de atención  
- `resolved` - Caso resuelto
- `closed` - Caso cerrado

### Estados de Comunicaciones (`communications.status`)
- `pending` - Comunicación pendiente
- `processed` - Comunicación procesada
- `delivered` - Comunicación entregada
- `failed` - Comunicación fallida

## Observaciones Técnicas

### Duplicación de Datos
- Existe cierta duplicación entre `imported_emails` y `communications`
- `imported_emails` parece ser específico para Gmail
- `communications` es más genérico y puede manejar múltiples canales

### Campos JSON
Múltiples tablas usan campos JSON para flexibilidad:
- `contacts.attributes`
- `cases.tags`
- `communications.cc_contacts`, `communications.attachments`
- `gmail_metadata.gmail_labels`, `gmail_metadata.raw_headers`

### Timestamps
Todas las tablas principales incluyen `created_at` y `updated_at` (patrón Laravel)

### Identificadores Externos
- `gmail_message_id`, `gmail_thread_id` para integración con Gmail
- `brevo_contact_id`, `brevo_campaign_id` para integración con Brevo
- `external_id` en communications para IDs de sistemas externos

## Recomendaciones para Desarrollo

1. **Unificación**: Considerar unificar `imported_emails` y `communications` en el futuro
2. **Índices**: Verificar índices en campos de búsqueda frecuente como `gmail_message_id`, `case_number`
3. **Constraints**: Definir foreign keys explícitas para mantener integridad referencial
4. **Particionamiento**: Para tablas grandes como `communications`, considerar particionamiento por fecha

## Modelos Laravel Correspondientes

Basado en la estructura, los modelos Laravel esperados serían:
- `User` → `users`
- `Contact` → `contacts`
- `ContactList` → `contact_lists`
- `ContactListMember` → `contact_list_members`
- `CustomerCase` → `cases` (nota: posible renombre)
- `Communication` → `communications`
- `ImportedEmail` → `imported_emails`
- `OutboxEmail` → `outbox_emails`
- `EmailAttachment` → `email_attachments`
- `GmailGroup` → `gmail_groups`
- `GmailMetadata` → `gmail_metadata`
- `ReferenceCode` → `reference_codes`
- `SystemConfig` → `system_config`