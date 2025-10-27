# Mapeo Modelos Laravel - Tablas Base de Datos

## Correspondencia Directa Confirmada

| Modelo Laravel | Tabla PostgreSQL | Estado | Observaciones |
|---|---|---|---|
| `User` | `users` | ✅ Existe | Modelo completo con relaciones |
| `Contact` | `contacts` | ✅ Existe | Modelo básico |
| `ContactList` | `contact_lists` | ✅ Existe | Modelo básico |
| `ContactListMember` | `contact_list_members` | ✅ Existe | Modelo básico |
| `CustomerCase` | `cases` | ✅ Existe | Modelo completo con relaciones |
| `Communication` | `communications` | ✅ Existe | Modelo completo con relaciones |
| `ImportedEmail` | `imported_emails` | ✅ Existe | Modelo completo |
| `OutboxEmail` | `outbox_emails` | ✅ Existe | Modelo completo |
| `EmailAttachment` | `email_attachments` | ✅ Existe | Modelo con relaciones |
| `GmailGroup` | `gmail_groups` | ✅ Existe | Modelo con relaciones |
| `GmailMetadata` | `gmail_metadata` | ✅ Existe | Modelo completo con relaciones |
| `ReferenceCode` | `reference_codes` | ✅ Existe | Modelo con relaciones |
| `SystemConfig` | `system_config` | ✅ Existe | Modelo para configuraciones |
| `OAuthToken` | `oauth_tokens` | ✅ Existe | Modelo para tokens OAuth |
| `UserRole` | `user_roles` | ✅ Existe | Modelo básico para roles |
| `CampaignSend` | `campaign_sends` | ✅ Existe | Modelo básico |
| `EmailCampaign` | `email_campaigns` | ✅ Existe | Modelo básico |

## Tablas Sin Modelo Laravel Explícito

| Tabla PostgreSQL | Modelo Esperado | Tipo | Observaciones |
|---|---|---|---|
| `campaigns` | `Campaign` | ❌ Faltante | Campañas generales |
| `case_metrics` | `CaseMetric` | ❌ Faltante | Métricas de casos |
| `phone_communications` | `PhoneCommunication` | ❌ Faltante | Comunicaciones telefónicas |
| `outbox_attachments` | `OutboxAttachment` | ❌ Faltante | Adjuntos de emails salientes |
| `cache` | - | Sistema Laravel | Manejo automático |
| `cache_locks` | - | Sistema Laravel | Manejo automático |
| `failed_jobs` | - | Sistema Laravel | Manejo automático |
| `job_batches` | - | Sistema Laravel | Manejo automático |
| `jobs` | - | Sistema Laravel | Manejo automático |
| `migrations` | - | Sistema Laravel | Manejo automático |
| `password_reset_tokens` | - | Sistema Laravel | Manejo automático |
| `sessions` | - | Sistema Laravel | Manejo automático |

## Análisis Detallado de Modelos Existentes

### Modelos Core con Relaciones Completas

#### 1. `CustomerCase` (cases)
- **Relaciones definidas**: assignedTo, assignedBy, communications, metrics
- **Campos calculados**: communication_count
- **Estados**: pending, assigned, in_progress, resolved, closed

#### 2. `Communication` (communications)
- **Relaciones definidas**: case, gmailMetadata, phoneCommunciation, replyTo
- **Polimorfismo**: Maneja diferentes tipos de canal
- **Estados**: pending, processed, delivered, failed

#### 3. `ImportedEmail` (imported_emails)
- **Relaciones definidas**: gmailGroup, referenceCode, assignedTo, assignedBy, attachments
- **Sistema de estados**: case_status (pending, in_progress, resolved, spam)
- **Integración Gmail**: gmail_message_id, gmail_thread_id

#### 4. `GmailMetadata` (gmail_metadata)
- **Relación 1:1 con Communication**
- **Campos específicos Gmail**: message_id, thread_id, labels, headers
- **Sistema de sincronización**: sync_status, backup_status

### Modelos Básicos (Requieren Expansión)

#### 1. `Contact` (contacts)
- **Estado actual**: Modelo muy básico
- **Campos faltantes**: Muchas relaciones no definidas
- **Integración Brevo**: brevo_contact_id

#### 2. `ContactList` y `ContactListMember`
- **Estado actual**: Modelos básicos sin relaciones
- **Funcionalidad esperada**: Gestión de listas de marketing

#### 3. `CampaignSend` y `EmailCampaign`
- **Estado actual**: Modelos básicos
- **Funcionalidad esperada**: Sistema completo de campañas

## Modelos Faltantes Críticos

### 1. `Campaign` (campaigns)
```php
// Modelo faltante para campaigns
class Campaign extends Model
{
    protected $fillable = ['name', 'description', 'status'];
    
    public function sends()
    {
        return $this->hasMany(CampaignSend::class);
    }
    
    public function emailCampaign()
    {
        return $this->hasOne(EmailCampaign::class);
    }
}
```

### 2. `CaseMetric` (case_metrics)
```php
// Modelo faltante para métricas de casos
class CaseMetric extends Model
{
    protected $fillable = [
        'case_id', 'email_count', 'whatsapp_count', 
        'sms_count', 'phone_count', 'webchat_count',
        'avg_email_response_hours', 'preferred_channel'
    ];
    
    public function case()
    {
        return $this->belongsTo(CustomerCase::class);
    }
}
```

### 3. `PhoneCommunication` (phone_communications)
```php
// Modelo faltante para comunicaciones telefónicas
class PhoneCommunication extends Model
{
    protected $fillable = [
        'communication_id', 'phone_number', 'call_duration_seconds',
        'call_type', 'call_status', 'recording_url'
    ];
    
    public function communication()
    {
        return $this->belongsTo(Communication::class);
    }
}
```

### 4. `OutboxAttachment` (outbox_attachments)
```php
// Modelo faltante para adjuntos de emails salientes
class OutboxAttachment extends Model
{
    protected $fillable = [
        'outbox_email_id', 'original_filename', 'stored_filename',
        'file_path', 'mime_type', 'file_size'
    ];
    
    public function outboxEmail()
    {
        return $this->belongsTo(OutboxEmail::class);
    }
}
```

## Relaciones Cruzadas Importantes

### Email Flow
```
ImportedEmail → GmailGroup (belongsTo)
ImportedEmail → ReferenceCode (belongsTo) 
ImportedEmail → User (assignedTo, assignedBy)
ImportedEmail → EmailAttachment (hasMany)
```

### Case Flow  
```
CustomerCase → User (assignedTo, assignedBy)
CustomerCase → Communication (hasMany)
CustomerCase → CaseMetric (hasOne) [FALTANTE]
Communication → GmailMetadata (hasOne)
Communication → PhoneCommunication (hasOne) [FALTANTE]
```

### Contact & Campaign Flow
```
Contact → ContactListMember (hasMany)
ContactList → ContactListMember (hasMany)
Campaign → CampaignSend (hasMany) [FALTANTE]
Campaign → EmailCampaign (hasOne) [FALTANTE]
```

## Recomendaciones Inmediatas

### 1. Crear Modelos Faltantes
- `Campaign`
- `CaseMetric` 
- `PhoneCommunication`
- `OutboxAttachment`

### 2. Expandir Modelos Básicos
- `Contact`: Agregar relaciones completas
- `ContactList`: Agregar relaciones y scopes
- `CampaignSend`: Agregar relaciones con Campaign
- `EmailCampaign`: Agregar relaciones con Campaign

### 3. Validar Foreign Keys
- Verificar que todas las relaciones tengan sus constraints en BD
- Implementar cascadas donde sea apropiado

### 4. Implementar Scopes y Accessors
- Scopes para estados comunes (activos, pendientes, etc.)
- Accessors para campos calculados
- Mutators para normalización de datos

## Estados del Sistema vs Modelos

### Estados de Email (`imported_emails.case_status`)
- ✅ Bien definidos: pending, in_progress, resolved, spam
- ✅ Corresponden con flujo de trabajo real

### Estados de Caso (`cases.status`)  
- ✅ Bien definidos: pending, assigned, in_progress, resolved, closed
- ⚠️  Posible duplicación con estados de email

### Estados de Comunicación (`communications.status`)
- ✅ Definidos: pending, processed, delivered, failed
- ✅ Genéricos para múltiples canales

## Conclusión

El sistema tiene una **base sólida** con 17 modelos Laravel que cubren las funcionalidades principales. Los **4 modelos faltantes** son principalmente para funcionalidades avanzadas (métricas, campañas, teléfono). 

La **integridad referencial** parece estar bien diseñada, aunque faltan algunos constraints explícitos en base de datos.

**Prioridad Alta**: Crear modelos faltantes para completar funcionalidades core.
**Prioridad Media**: Expandir modelos básicos con relaciones completas.
**Prioridad Baja**: Optimizar queries y agregar índices específicos.