# Documentación de Base de Datos - OMNIC 2.0

Este directorio contiene la documentación completa de la estructura y análisis de la base de datos PostgreSQL del sistema OMNIC 2.0.

## 📁 Contenido de la Documentación

### [`database_structure.md`](./database_structure.md)
Documentación completa de la estructura de la base de datos:
- **29 tablas** organizadas por categorías funcionales
- Análisis detallado de cada tabla principal
- Relaciones y constraints identificados
- Estados y flujos de trabajo del sistema
- Recomendaciones técnicas de desarrollo

### [`model_mapping.md`](./model_mapping.md)  
Mapeo entre modelos Laravel y tablas PostgreSQL:
- **17 modelos existentes** con correspondencia directa
- **4 modelos faltantes** identificados como críticos
- Análisis de relaciones y funcionalidades
- Código de ejemplo para modelos faltantes
- Recomendaciones de expansión

### [`data_analysis.md`](./data_analysis.md)
Análisis del estado actual de los datos:
- Conteos de registros por tabla principal
- Identificación de problemas en flujos de trabajo
- Análisis de módulos funcionales vs no funcionales
- Recomendaciones prioritarias de implementación

## 🏗️ Arquitectura General

### Módulos Principales
1. **Sistema de Email** (Gmail Integration)
2. **Gestión de Casos** (Customer Support)
3. **Comunicaciones** (Multi-channel)
4. **Contactos y Listas** (CRM Basic)
5. **Campañas** (Email Marketing)
6. **Usuarios y Autenticación** (System Access)

### Flujos de Datos Principales
```
Gmail → ImportedEmail → Communication → Case → Response
     → GmailMetadata
     → EmailAttachment
     → Contact (pendiente)
```

## 📊 Estado Actual del Sistema

### ✅ Funcional
- **Gmail Groups**: 51 grupos configurados
- **Users**: 50 usuarios registrados  
- **Imported Emails**: 22 emails procesados
- **Arquitectura SOLID**: 14 comandos implementados

### ⚠️ Parcialmente Funcional
- **Cases**: 3 casos (subutilizado)
- **Communications**: 5 registros (desbalanceado)
- **Gmail Metadata**: 1 registro (procesamiento incompleto)
- **Outbox Emails**: 4 registros (funcionalidad limitada)

### ❌ No Funcional
- **Contacts**: 0 registros (no implementado)
- **Email Attachments**: 0 registros (procesamiento fallido)
- **Campañas**: No activas
- **Métricas**: Sin implementar

## 🎯 Prioridades de Desarrollo

### Alta Prioridad (Crítico)
1. **Procesamiento de Adjuntos**: Los emails con adjuntos no se procesan correctamente
2. **Metadatos Gmail**: Solo 1 de 22 emails tiene metadatos completos
3. **Creación de Contactos**: Falta automatización desde emails importados
4. **Modelos Faltantes**: `Campaign`, `CaseMetric`, `PhoneCommunication`, `OutboxAttachment`

### Media Prioridad (Importante)
1. **Vinculación Casos-Comunicaciones**: Mejorar creación automática
2. **Sistema de Referencias**: Activar uso de códigos de referencia
3. **Expansión de Modelos**: Completar relaciones en modelos básicos

### Baja Prioridad (Futuro)
1. **Listas de Contactos**: Funcionalidad de marketing
2. **Métricas de Casos**: Dashboard y reportería
3. **Comunicaciones Telefónicas**: Canal adicional

## 🔧 Comandos SOLID Disponibles

Para gestión y diagnóstico de la base de datos, utilizar los comandos implementados:

```bash
# Estadísticas de emails
php artisan email:stats --period=week

# Gestión de usuarios
php artisan user:list --role=ejecutivo

# Gestión de casos  
php artisan case:list --status=pending

# Gestión de comunicaciones
php artisan communication:list --channel=email

# Limpieza de sistema
php artisan system:clean-cache
```

## 📋 Queries de Diagnóstico Útiles

### Verificar Integridad de Datos
```sql
-- Emails sin metadatos
SELECT COUNT(*) FROM imported_emails ie 
LEFT JOIN gmail_metadata gm ON ie.gmail_message_id = gm.gmail_message_id 
WHERE gm.id IS NULL;

-- Emails con adjuntos no procesados
SELECT COUNT(*) FROM imported_emails 
WHERE has_attachments = true AND id NOT IN (
    SELECT DISTINCT imported_email_id FROM email_attachments
);

-- Comunicaciones sin casos
SELECT COUNT(*) FROM communications 
WHERE case_id IS NULL;
```

### Análisis de Performance
```sql
-- Emails por grupo Gmail
SELECT gg.name, COUNT(ie.id) as email_count
FROM gmail_groups gg
LEFT JOIN imported_emails ie ON gg.id = ie.gmail_group_id
GROUP BY gg.id, gg.name
ORDER BY email_count DESC;

-- Estados de casos
SELECT status, COUNT(*) FROM cases GROUP BY status;

-- Estados de emails importados
SELECT case_status, COUNT(*) FROM imported_emails GROUP BY case_status;
```

## 🔗 Referencias Relacionadas

- [`/docs/solid-architecture/`](../solid-architecture/) - Comandos y servicios implementados
- [`/docs/technical/`](../technical/) - Especificaciones técnicas
- [`/docs/uml/`](../uml/) - Diagramas de arquitectura
- [`/app/Models/`](../../app/Models/) - Modelos Laravel actuales

## 📝 Notas de Desarrollo

### Foreign Keys Identificadas (Implícitas)
- `cases.assigned_to` → `users.id`
- `imported_emails.gmail_group_id` → `gmail_groups.id`
- `communications.case_id` → `cases.id`
- `gmail_metadata.communication_id` → `communications.id`
- `email_attachments.imported_email_id` → `imported_emails.id`

### Campos JSON Utilizados
- `contacts.attributes` - Datos flexibles de contacto
- `cases.tags` - Etiquetas de categorización
- `communications.cc_contacts` - Lista de contactos en copia
- `gmail_metadata.gmail_labels` - Etiquetas de Gmail
- `gmail_metadata.raw_headers` - Headers completos del email

### Consideraciones de Escalabilidad
- **Particionamiento**: Considerar para `communications` por fecha
- **Índices**: Verificar en `gmail_message_id`, `case_number`, `email`
- **Archivado**: Implementar para emails antiguos
- **Backup**: Estrategia para datos de Gmail y adjuntos

---

**Última Actualización**: Octubre 2024  
**Versión de Base de Datos**: PostgreSQL 13+  
**Framework**: Laravel 11  
**Estado**: Desarrollo Activo