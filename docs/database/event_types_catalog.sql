// OMNIC 2.0 - CATÁLOGO DE EVENTOS DEL SISTEMA
// Todos los tipos de eventos posibles en el sistema

// =============================================
// TABLA DE EVENTOS MAESTROS
// =============================================

Table event_types {
  id bigserial [pk, increment]
  event_type varchar(100) [unique, not null]
  aggregate_type varchar(50) [not null]
  description text [not null]
  severity varchar(20) [default: 'info']
  schema_version int [default: 1]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
  
  Note: '''
    Catálogo maestro de todos los eventos posibles en el sistema
    Define la estructura y validaciones para cada tipo de evento
  '''
}

// =============================================
// EVENTOS DE EMAIL
// =============================================

// EMAIL LIFECYCLE
INSERT INTO event_types (event_type, aggregate_type, description, severity) VALUES
('email.received', 'email', 'Email recibido desde Gmail API', 'info'),
('email.sent', 'email', 'Email enviado exitosamente', 'info'),
('email.created', 'email', 'Email creado en el sistema', 'info'),
('email.updated', 'email', 'Email actualizado', 'debug'),

// EMAIL STATUS CHANGES
('email.status_changed', 'email', 'Estado del email cambió (pending→assigned→resolved)', 'info'),
('email.assigned', 'email', 'Email asignado a un agente', 'info'),
('email.unassigned', 'email', 'Email desasignado de un agente', 'info'),
('email.processed', 'email', 'Email procesado completamente', 'info'),

// EMAIL SPAM/MODERATION
('email.marked_as_spam', 'email', 'Email marcado como spam', 'warning'),
('email.unmarked_as_spam', 'email', 'Email desmarcado como spam', 'info'),

// EMAIL BOUNCES (OUTBOUND)
('email.bounced', 'email', 'Email rebotado (hard/soft bounce)', 'error'),
('email.bounce_detected', 'email', 'Rebote detectado automáticamente', 'warning'),

// EMAIL SYNC EVENTS
('email.sync_started', 'email', 'Sincronización de email iniciada', 'debug'),
('email.sync_completed', 'email', 'Email sincronizado exitosamente', 'debug'),
('email.sync_failed', 'email', 'Fallo en sincronización de email', 'error'),

// EMAIL ATTACHMENTS
('email.attachment_uploaded', 'email', 'Adjunto subido exitosamente', 'info'),
('email.attachment_failed', 'email', 'Fallo en subida de adjunto', 'error'),
('email.attachments_processed', 'email', 'Todos los adjuntos procesados', 'info'),

// =============================================
// EVENTOS DE CASOS
// =============================================

// CASE LIFECYCLE  
('case.created', 'case', 'Caso creado desde email/comunicación', 'info'),
('case.updated', 'case', 'Datos del caso actualizados', 'debug'),
('case.deleted', 'case', 'Caso eliminado', 'warning'),

// CASE STATUS CHANGES
('case.status_changed', 'case', 'Estado del caso cambió', 'info'),
('case.assigned', 'case', 'Caso asignado a agente', 'info'),
('case.unassigned', 'case', 'Caso desasignado', 'info'),
('case.escalated', 'case', 'Caso escalado a supervisor', 'warning'),
('case.resolved', 'case', 'Caso marcado como resuelto', 'info'),
('case.reopened', 'case', 'Caso reabierto', 'warning'),
('case.closed', 'case', 'Caso cerrado definitivamente', 'info'),

// CASE PRIORITY
('case.priority_changed', 'case', 'Prioridad del caso cambió', 'info'),
('case.marked_urgent', 'case', 'Caso marcado como urgente', 'warning'),

// CASE TIMING/SLA
('case.first_response_sent', 'case', 'Primera respuesta enviada', 'info'),
('case.sla_breach_warning', 'case', 'Advertencia de posible incumplimiento SLA', 'warning'),
('case.sla_breached', 'case', 'SLA incumplido', 'error'),

// CASE DERIVATION  
('case.derived_to_supervisor', 'case', 'Caso derivado a supervisor', 'info'),
('case.derivation_accepted', 'case', 'Derivación aceptada por supervisor', 'info'),
('case.derivation_rejected', 'case', 'Derivación rechazada, devuelto a agente', 'warning'),

// =============================================
// EVENTOS DE IMPORTACIÓN 
// =============================================

// IMPORT PROCESS
('import.started', 'system', 'Proceso de importación iniciado', 'info'),
('import.progress', 'system', 'Progreso de importación (cada N emails)', 'debug'),
('import.completed', 'system', 'Importación completada exitosamente', 'info'),
('import.failed', 'system', 'Importación falló completamente', 'error'),
('import.partial_failure', 'system', 'Importación completada con errores parciales', 'warning'),

// IMPORT GROUP LEVEL
('import.group_started', 'gmail_group', 'Importación de grupo Gmail iniciada', 'debug'),
('import.group_completed', 'gmail_group', 'Grupo importado exitosamente', 'debug'),
('import.group_failed', 'gmail_group', 'Fallo en importación de grupo', 'error'),

// IMPORT EMAIL LEVEL
('import.email_processed', 'email', 'Email individual procesado en importación', 'debug'),
('import.email_skipped', 'email', 'Email saltado (ya existe, filtros)', 'debug'),
('import.email_failed', 'email', 'Fallo al procesar email individual', 'error'),

// =============================================
// EVENTOS DE GMAIL API
// =============================================

// AUTHENTICATION
('gmail.auth_started', 'system', 'Inicio de autenticación Gmail API', 'debug'),
('gmail.auth_success', 'system', 'Autenticación Gmail exitosa', 'info'),
('gmail.auth_failed', 'system', 'Fallo de autenticación Gmail', 'error'),
('gmail.token_refreshed', 'system', 'Token OAuth renovado', 'debug'),
('gmail.token_expired', 'system', 'Token OAuth expirado', 'warning'),

// API CALLS
('gmail.api_call_started', 'system', 'Llamada a Gmail API iniciada', 'debug'),
('gmail.api_call_success', 'system', 'Llamada a Gmail API exitosa', 'debug'),
('gmail.api_call_failed', 'system', 'Llamada a Gmail API falló', 'error'),

// QUOTAS & LIMITS
('gmail.quota_warning', 'system', 'Advertencia de cuota Gmail (80%)', 'warning'),
('gmail.quota_exceeded', 'system', 'Cuota Gmail excedida', 'error'),
('gmail.rate_limited', 'system', 'Rate limit alcanzado, esperando', 'warning'),

// =============================================
// EVENTOS DE ENVÍO DE EMAILS
// =============================================

// EMAIL QUEUE
('email_queue.created', 'email_queue', 'Email agregado a cola de envío', 'info'),
('email_queue.processing', 'email_queue', 'Email en cola siendo procesado', 'debug'),
('email_queue.sent', 'email_queue', 'Email enviado exitosamente desde cola', 'info'),
('email_queue.failed', 'email_queue', 'Fallo en envío desde cola', 'error'),
('email_queue.retry', 'email_queue', 'Reintento de envío programado', 'warning'),
('email_queue.cancelled', 'email_queue', 'Envío cancelado', 'info'),
('email_queue.expired', 'email_queue', 'Email en cola expiró (max intentos)', 'error'),

// =============================================
// EVENTOS DE USUARIOS
// =============================================

// USER AUTHENTICATION
('user.login', 'user', 'Usuario inició sesión', 'info'),
('user.logout', 'user', 'Usuario cerró sesión', 'info'),
('user.login_failed', 'user', 'Intento de login fallido', 'warning'),

// USER MANAGEMENT
('user.created', 'user', 'Usuario creado en el sistema', 'info'),
('user.updated', 'user', 'Datos de usuario actualizados', 'debug'),
('user.deactivated', 'user', 'Usuario desactivado', 'warning'),
('user.reactivated', 'user', 'Usuario reactivado', 'info'),
('user.role_changed', 'user', 'Rol de usuario cambió', 'info'),

// USER WORKLOAD
('user.assignment_changed', 'user', 'Carga de trabajo cambió (caso asignado/removido)', 'debug'),
('user.workload_warning', 'user', 'Advertencia de sobrecarga de trabajo', 'warning'),

// =============================================
// EVENTOS DE SISTEMA/BASE DE DATOS
// =============================================

// DATABASE
('database.connection_failed', 'system', 'Fallo de conexión a base de datos', 'critical'),
('database.query_failed', 'system', 'Query de base de datos falló', 'error'),
('database.migration_applied', 'system', 'Migración de BD aplicada', 'info'),
('database.backup_started', 'system', 'Backup de BD iniciado', 'info'),
('database.backup_completed', 'system', 'Backup completado exitosamente', 'info'),
('database.backup_failed', 'system', 'Backup falló', 'error'),

// SYSTEM CONFIGURATION
('system.config_changed', 'system', 'Configuración del sistema cambió', 'info'),
('system.maintenance_started', 'system', 'Modo mantenimiento activado', 'warning'),
('system.maintenance_ended', 'system', 'Modo mantenimiento desactivado', 'info'),

// JOB PROCESSING
('job.started', 'system', 'Job de Laravel iniciado', 'debug'),
('job.completed', 'system', 'Job completado exitosamente', 'debug'),
('job.failed', 'system', 'Job falló', 'error'),
('job.retrying', 'system', 'Job reintentándose', 'warning'),

// =============================================
// EVENTOS DE COMUNICACIONES
// =============================================

// MULTI-CHANNEL COMMUNICATIONS
('communication.received', 'communication', 'Comunicación recibida (cualquier canal)', 'info'),
('communication.sent', 'communication', 'Comunicación enviada', 'info'),
('communication.failed', 'communication', 'Fallo en envío de comunicación', 'error'),

// PHONE COMMUNICATIONS
('phone.call_received', 'phone_communication', 'Llamada telefónica recibida', 'info'),
('phone.call_completed', 'phone_communication', 'Llamada completada', 'info'),
('phone.call_missed', 'phone_communication', 'Llamada perdida', 'warning'),
('phone.recording_saved', 'phone_communication', 'Grabación de llamada guardada', 'debug'),

// =============================================
// EVENTOS DE MÉTRICAS Y REPORTES
// =============================================

// METRICS CALCULATION
('metrics.calculated', 'system', 'Métricas del sistema calculadas', 'debug'),
('metrics.sla_report_generated', 'system', 'Reporte SLA generado', 'info'),
('metrics.performance_alert', 'system', 'Alerta de rendimiento del sistema', 'warning'),

// DASHBOARD EVENTS
('dashboard.data_refreshed', 'system', 'Datos del dashboard actualizados', 'debug'),
('dashboard.alert_triggered', 'system', 'Alerta del dashboard disparada', 'warning'),

// =============================================
// EVENTOS DE INTEGRACIÓN EXTERNA
// =============================================

// BREVO/SENDINBLUE
('brevo.contact_synced', 'contact', 'Contacto sincronizado con Brevo', 'debug'),
('brevo.campaign_sent', 'campaign', 'Campaña enviada via Brevo', 'info'),
('brevo.webhook_received', 'system', 'Webhook de Brevo recibido', 'debug'),

// DRIVE INTEGRATION
('drive.file_uploaded', 'email_attachment', 'Archivo subido a Google Drive', 'debug'),
('drive.file_failed', 'email_attachment', 'Fallo en subida a Drive', 'error'),
('drive.quota_warning', 'system', 'Advertencia de cuota Google Drive', 'warning');