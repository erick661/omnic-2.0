# Plan de Migración de Base de Datos - OMNIC 2.0

## 📊 Resumen de Cambios

### 🎯 Objetivo Principal
Unificar y optimizar el sistema de gestión de emails eliminando redundancias y mejorando la trazabilidad.

### 🔄 Cambios Estructurales Principales

#### 1. **Tabla `emails` Unificada** ⭐
**Reemplaza 3 tablas:**
- `imported_emails` (emails entrantes)
- `gmail_metadata` (metadatos Gmail)  
- `outbox_emails` (emails salientes)

**Beneficios:**
- ✅ Eliminación de duplicación de datos
- ✅ Consultas más simples
- ✅ Mejor trazabilidad de hilos de conversación
- ✅ Estados unificados

#### 2. **Tabla `email_queue` Específica** 🆕
**Nueva tabla para:**
- Cola de emails pendientes de envío
- Estados específicos de despacho
- Control de reintentos y errores

#### 3. **Tabla `email_dispatch_log` Específica** 🆕
**Nueva tabla para:**
- Log detallado de cada intento de envío
- Métricas de performance del Gmail API
- Detección y trazabilidad de rebotes

#### 4. **Tabla `email_attachments` Unificada**
**Reemplaza 2 tablas:**
- `email_attachments` (adjuntos entrantes)
- `outbox_attachments` (adjuntos salientes)

### 📋 Estado Actual vs Optimizado

| Concepto | Estado Actual | Estado Optimizado |
|----------|---------------|-------------------|
| **Emails Entrantes** | `imported_emails` | `emails` (direction='inbound') |
| **Metadatos Gmail** | `gmail_metadata` | Campos integrados en `emails` |
| **Emails Salientes** | `outbox_emails` | `emails` (direction='outbound') |
| **Cola de Envío** | Mezclada en `outbox_emails` | `email_queue` específica |
| **Log de Envíos** | No existe | `email_dispatch_log` |
| **Adjuntos Entrantes** | `email_attachments` | `email_attachments` unificada |
| **Adjuntos Salientes** | `outbox_attachments` | `email_attachments` unificada |

## 🗂️ Campos Unificados en `emails`

### Campos Base (Comunes)
```sql
id, gmail_message_id, gmail_thread_id, direction
subject, from_email, from_name, to_email, to_name
cc_emails, bcc_emails, reply_to, body_text, body_html
created_at, updated_at, status, priority, category, tags
has_attachments, case_id, assigned_to, assigned_by
processed_at, processed_by
```

### Campos Específicos de Inbound (desde `imported_emails`)
```sql
gmail_group_id, received_at, rut_empleador, dv_empleador
reference_code_id, marked_as_spam, spam_marked_by, spam_marked_at
marked_resolved_at, auto_resolved_at, derived_to_supervisor
derivation_notes, derived_at, assignment_notes
```

### Campos Específicos de Outbound (nuevos)
```sql
sent_at, bounced_at, bounce_reason, bounce_type
```

### Campos de Metadatos Gmail (desde `gmail_metadata`)
```sql
gmail_headers, gmail_labels, gmail_size_estimate, gmail_snippet
gmail_internal_date, gmail_history_id, raw_headers
message_references, in_reply_to, sync_status, last_sync_at
sync_error_message, is_backed_up, backup_at
eml_download_url, eml_backup_path, attachments_metadata
```

## 🔄 Flujo de Datos Optimizado

### Emails Entrantes (Inbound)
```
Gmail API → emails (direction='inbound')
         → Procesar metadatos
         → Extraer adjuntos → email_attachments
         → Crear caso si necesario → cases
```

### Emails Salientes (Outbound)
```
Usuario/Sistema → email_queue (status='queued')
               → Job procesa → Gmail API
               → Respuesta API → emails (direction='outbound')
               → Log completo → email_dispatch_log
```

### Detección de Rebotes
```
Email rebote (inbound) → Detectar original (por thread_id)
                      → Actualizar email original (bounced_at, bounce_reason)
                      → Actualizar dispatch_log (bounce_detected_at)
                      → Notificar al caso asociado
```

## 📊 Plan de Migración de Datos

### Fase 1: Crear Nuevas Tablas
1. ✅ `emails` (estructura unificada)
2. ✅ `email_queue` (nueva)
3. ✅ `email_dispatch_log` (nueva)
4. ✅ `email_attachments` (modificada para unificación)

### Fase 2: Migrar Datos Existentes

#### A. Migrar `imported_emails` → `emails`
```sql
INSERT INTO emails (
  gmail_message_id, gmail_thread_id, direction,
  subject, from_email, from_name, to_email, cc_emails, bcc_emails,
  body_text, body_html, received_at, status, priority, category,
  has_attachments, gmail_group_id, case_id, assigned_to, assigned_by,
  -- ... todos los campos específicos de inbound
) 
SELECT 
  gmail_message_id, gmail_thread_id, 'inbound',
  subject, from_email, from_name, to_email, cc_emails, bcc_emails,
  body_text, body_html, received_at, case_status, priority, NULL,
  has_attachments, gmail_group_id, NULL, assigned_to, assigned_by,
  -- mapear todos los campos
FROM imported_emails;
```

#### B. Migrar `gmail_metadata` → `emails`
```sql
UPDATE emails e 
SET 
  gmail_headers = gm.headers,
  gmail_labels = gm.labels,
  gmail_size_estimate = gm.size_estimate,
  gmail_snippet = gm.snippet,
  -- ... todos los metadatos
FROM gmail_metadata gm 
WHERE e.gmail_message_id = gm.gmail_message_id;
```

#### C. Migrar `outbox_emails` → `emails`
```sql
INSERT INTO emails (
  direction, subject, from_email, from_name, to_email, to_name,
  cc_emails, bcc_emails, body_html, body_text,
  sent_at, status, created_by, case_id
)
SELECT 
  'outbound', subject, from_email, from_name, to_email, to_name,
  cc_emails, bcc_emails, body_html, body_text,
  sent_at, send_status, created_by, NULL
FROM outbox_emails;
```

#### D. Migrar Adjuntos → `email_attachments` Unificada
```sql
-- Adjuntos de emails entrantes (ya existen)
-- Solo necesitan actualizar la referencia email_id

-- Adjuntos de emails salientes
INSERT INTO email_attachments (
  email_id, original_filename, stored_filename, 
  file_path, mime_type, file_size, created_at, updated_at
)
SELECT 
  e.id, oa.original_filename, oa.stored_filename,
  oa.file_path, oa.mime_type, oa.file_size,
  oa.created_at, oa.updated_at
FROM outbox_attachments oa
JOIN emails e ON e.legacy_outbox_id = oa.outbox_email_id
WHERE e.direction = 'outbound';
```

### Fase 3: Actualizar Modelos Laravel
1. Crear nuevo modelo `Email` unificado
2. Crear modelos `EmailQueue` y `EmailDispatchLog`
3. Actualizar relaciones en modelos existentes
4. Actualizar servicios y comandos

### Fase 4: Eliminar Tablas Obsoletas
```sql
DROP TABLE imported_emails;
DROP TABLE gmail_metadata;
DROP TABLE outbox_emails;
DROP TABLE outbox_attachments;
```

## 🚀 Beneficios Esperados

### 📈 Performance
- Menos JOINs en consultas complejas
- Índices optimizados en una sola tabla
- Consultas más rápidas para hilos de conversación

### 🔍 Funcionalidad
- Trazabilidad completa de conversaciones
- Detección automática de rebotes
- Métricas detalladas de envío
- Log completo de operaciones

### 🛠️ Mantenimiento
- Menos complejidad en el código
- Modelos más simples
- Migraciones más directas
- Backup más eficiente

## ⚠️ Consideraciones

### Compatibilidad hacia atrás
- Los comandos existentes necesitarán actualización
- Las vistas y reportes requerirán modificación
- API endpoints cambiarán estructura

### Volumen de datos
- Con **22 emails importados** actuales, la migración será rápida
- El sistema está en fase temprana, momento ideal para cambios estructurales

### Testing
- Todos los tests unitarios necesitarán actualización
- Validar integridad de datos post-migración
- Probar flujos completos inbound/outbound

## 📅 Timeline Sugerido

1. **Día 1**: Crear estructura nueva + migración de datos
2. **Día 2**: Actualizar modelos Laravel + relaciones  
3. **Día 3**: Actualizar servicios y comandos SOLID
4. **Día 4**: Actualizar tests + validación
5. **Día 5**: Limpiar tablas obsoletas + documentación

---

**Estado**: Preparado para implementación
**Riesgo**: Bajo (volumen de datos pequeño)
**Impacto**: Alto (mejora significativa de arquitectura)