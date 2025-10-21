# Requerimientos Funcionales - Sistema OMNIC Omnicanal

## Contexto

- **Proyecto**: Sistema omnicanal de comunicaciones para contactos con empleadores (deudores previsionales)
- **Objetivo**: Reemplazar sistema legacy PHP con plataforma omnicanal moderna
- **Usuarios**: Supervisores, Ejecutivos, Masivos y Administradores
- **Tecnología**: Laravel 12 + Livewire Volt + TailwindCSS + Mary UI
- **BaseDatos**: PostgreSQL 18

## Visión Omnicanal

OMNIC es un sistema de gestión omnicanal que centraliza todas las comunicaciones con empleadores en un solo lugar:

### Canales Soportados (Roadmap):
- ✅ **Email** (Gmail API) - Fase 1 (Actual)
- 🔄 **WhatsApp Business** - Fase 2
- 🔄 **SMS** - Fase 3  
- 🔄 **Llamadas Telefónicas** - Fase 4
- 🔄 **Chat Web** - Fase 5
- 🔄 **Redes Sociales** - Fase 6

### Concepto Central: **CASOS**
- Un **Caso** es la unidad central de trabajo que puede incluir múltiples comunicaciones a través de diferentes canales
- Cada nuevo contacto inicial genera un **Número de Caso único**
- Un caso permanece abierto hasta su resolución definitiva
- Todas las comunicaciones relacionadas (email, WhatsApp, SMS, llamadas) se vinculan al mismo caso
- **Ejemplo**: Un empleador inicia por email, continúa por WhatsApp y termina con una llamada telefónica, todo bajo un solo caso

## Actores

- **Supervisor**: Asigna correos, supervisa ejecutivos, ve estadísticas
- **Ejecutivo**: Recibe asignaciones, responde correos, gestiona su bandeja, ingresa actividades asocidas al correo
- **Masivo**: Carga lista de envio de correos masivos, Visualiza el estado del envio. Editas plantilals de correos Masivos
- **Administrador**: Puede modificar las configuraciones del sistema y dar de baja usuarios
- **Sistema**: Importa correos automáticamente, envía masivos via Brevo, envia correos de respuesta
  
## Flujos de Trabajo Omnicanal

### Flujo 1: Creación de Casos

1. **Nuevo contacto inicial** (por cualquier canal) crea automáticamente un **Caso único**
2. Sistema genera **Número de Caso** siguiendo formato: `CASO-YYYY-NNNNNN`
3. Caso se asigna a ejecutivo según reglas de negocio
4. Estado inicial: `pending` o `assigned`
5. Caso permanece activo hasta resolución definitiva

### Flujo 2: Comunicaciones Relacionadas

1. **Comunicaciones posteriores** del mismo contacto se vinculan al caso existente
2. Sistema identifica casos relacionados por:
   - RUT empleador (principal)
   - Código de referencia en comunicaciones
   - Número de teléfono/email del contacto
3. **Todas las comunicaciones** se muestran en timeline cronológico del caso
4. **Cambio de canal** no genera nuevo caso, sino nueva interacción en caso existente

### Flujo 3: Importación de Correos (Canal Email)

1. Sistema conecta a cuenta Gmail cada 1 minuto (Via API de Gmail)
2. Cuenta principal orpro@orpro es parte de varios grupos de Google Workspace
3. Lee correos no leídos dirigidos a los grupos configurados
4. **Si es correo inicial**: Crea nuevo Caso + primera comunicación
5. **Si hay código de referencia**: Vincula a Caso existente como nueva comunicación
6. Almacena comunicación con estado según contexto del caso

### Flujo 4: Asignación por Supervisor

1. Supervisor ve lista de **Casos** pendientes (no comunicaciones individuales)
2. Visualiza **historial completo** del caso (todas las comunicaciones por todos los canales)
3. Selecciona caso(s) y ejecutivo destino
4. Cambia estado del caso a "assigned"
5. **Todas las comunicaciones** del caso se asignan al mismo ejecutivo
6. Ejecutivo recibe notificación del nuevo caso

### Flujo 5: Gestión Omnicanal por Ejecutivo

1. Ejecutivo ve sus **Casos** asignados (no comunicaciones individuales)
2. Al abrir un caso, visualiza **timeline unificado** con todas las comunicaciones por todos los canales
3. Puede cambiar estado del caso: "in_progress", "pending_closure", "resolved"
4. **Responde por el canal apropiado**:
   - Email: Editor integrado
   - WhatsApp: Interfaz de mensajería (Fase 2)
   - SMS: Editor de texto corto (Fase 3)
   - Llamada: Registro de llamada + notas (Fase 4)
5. **Todas las respuestas** se registran como nuevas comunicaciones en el caso
6. Puede derivar **todo el caso** al supervisor para revisión o reasignación
7. Sistema mantiene **contexto completo** independiente del canal usado

### Flujo 6: Clasificación de SPAM

1. Supervisor lista todos los **casos** activos
2. Si determina que un caso completo es SPAM lo marca (afecta todas sus comunicaciones)
3. **Casos SPAM** permanecen en sistema 30 días para auditoría
4. **Comunicaciones individuales** también pueden marcarse como SPAM sin afectar todo el caso

### Flujo 7: Mantenimiento de Casos Históricos

1. Una vez al día el sistema revisa **casos** y **comunicaciones** vigentes
2. Busca casos marcados como SPAM con más de 30 días
3. Busca casos con estado "resolved" con más de 60 días
4. Mueve **casos completos** (con todas sus comunicaciones de todos los canales) a tablas históricas
5. Mantiene integridad referencial entre casos y comunicaciones históricas

### Flujo 8: Campañas Masivas Omnicanal

1. Usuario Masivo carga lista desde CSV/Excel con contactos
2. **Selecciona canal(es)** para la campaña:
   - Email (Brevo API) - Fase 1
   - WhatsApp Business (WhatsApp Business API) - Fase 2
   - SMS (Proveedor SMS) - Fase 3
3. Asocia plantilla específica para cada canal
4. Programa envío respetando horarios legales chilenos
5. **Respuestas generan casos automáticamente** vinculados a la campaña

### Flujo 9: Ejecución de Campañas Omnicanal

1. Sistema ejecuta envío multicanal dentro del horario legal chileno
2. **Monitorea estado por canal**:
   - Email: API Brevo para estadísticas de entrega/apertura
   - WhatsApp: API WhatsApp Business para estado de entrega/lectura
   - SMS: API proveedor SMS para confirmación de entrega
3. Genera **métricas unificadas** consolidando todos los canales
4. **Respuestas por cualquier canal** se convierten en casos automáticamente
5. Informa progreso y completion a Supervisor y Masivo

### Flujo 10: Bandeja de Salida Omnicanal

1. Sistema lee "bandejas de salida" de todos los canales cada 1 minuto
2. **Procesa por canal**:
   - Email: SMTP/API Gmail
   - WhatsApp: WhatsApp Business API
   - SMS: API proveedor SMS
3. Marca comunicaciones como enviadas y actualiza timeline del caso
4. **Registra respuesta** como nueva comunicación vinculada al caso original

## Modelo de Casos Omnicanal

### Estructura del Caso
```
CASO-2025-000001
├── Información Base
│   ├── Número de caso único
│   ├── RUT empleador (identificador principal)
│   ├── Estado del caso (pending, assigned, in_progress, resolved, spam)
│   ├── Ejecutivo asignado
│   ├── Fechas (creación, asignación, resolución)
│   └── Prioridad (low, normal, high, urgent)
├── Timeline de Comunicaciones
│   ├── 📧 Email inicial (2025-10-21 10:30)
│   ├── 📱 WhatsApp respuesta (2025-10-21 14:15)
│   ├── 📞 Llamada telefónica (2025-10-22 09:00)
│   └── 📧 Email resolución (2025-10-22 16:45)
└── Metadata
    ├── Canal de origen (email, whatsapp, sms, phone, webchat)
    ├── Campaña asociada (si aplica)
    ├── Categorización automática
    └── Notas internas del ejecutivo
```

### Reglas de Vinculación de Comunicaciones
1. **RUT Empleador**: Identificador principal para vincular comunicaciones
2. **Código de Referencia**: Generado automáticamente en respuestas
3. **Número de Teléfono**: Para WhatsApp, SMS y llamadas
4. **Email del Contacto**: Para comunicaciones email
5. **Ventana temporal**: Comunicaciones dentro de 72hrs se vinculan automáticamente

## Casos de Uso

### CU-001: Ver Dashboard de Casos (Supervisor)

**Actor**: Supervisor
**Precondición**: Usuario autenticado como supervisor
**Flujo**:

1. Sistema muestra dashboard omnicanal con:
   - **Casos activos** por estado y canal de origen
   - **Métricas consolidadas** (SLA, tiempo respuesta, resolución)
   - **Estadísticas por canal** (email, WhatsApp, SMS, llamadas)
   - **Carga de trabajo** por ejecutivo
2. Supervisor puede filtrar por:
   - Fecha, ejecutivo, estado del caso
   - **Canal de origen** (email, whatsapp, sms, phone)
   - **Tipo de interacción** (nueva, seguimiento, reclamo)
3. **Traspaso masivo de cartera**: Reasignar casos completos entre ejecutivos
4. **Gestión de SPAM**: Marcar casos completos como spam

### CU-002: Asignar Caso Completo

**Actor**: Supervisor
**Flujo**:

1. Supervisor selecciona **caso** de lista (no comunicación individual)
2. Ve **timeline completo** del caso:
   - Todas las comunicaciones por todos los canales
   - Contexto cronológico unificado
   - Adjuntos y multimedia de todas las interacciones
3. Analiza **patrón de comunicación** del empleador
4. Selecciona ejecutivo considerando **experiencia por canal**
5. Agrega nota interna con **contexto omnicanal**
6. Confirma asignación del **caso completo**
**Resultado**: Caso completo (todas las comunicaciones) asignado a ejecutivo

### CU-003: Ver Casos Asignados (Ejecutivo)

**Actor**: Ejecutivo
**Precondición**: Usuario autenticado como Ejecutivo
**Flujo**:

1. Sistema muestra **casos asignados** con vista omnicanal:
   - **Resumen por caso** con último canal de comunicación
   - **Indicadores visuales** por tipo de canal pendiente
   - **Prioridad y SLA** por caso
   - **Alertas** por casos sin actividad reciente
2. Ejecutivo puede filtrar por:
   - Estado del caso, prioridad, fecha
   - **Canal de última comunicación**
   - **Tipo de respuesta requerida**
3. **Vista de caso individual** muestra timeline unificado omnicanal

### CU-004: Gestionar Caso Omnicanal

**Actor**: Ejecutivo
**Flujo**:

1. Ejecutivo selecciona **caso** de lista
2. Ve **timeline completo omnicanal**:
   - Cronología unificada de todas las comunicaciones
   - Contexto por canal (email, WhatsApp, SMS, llamadas)
   - Adjuntos y multimedia integrados
   - Notas internas previas
3. **Selecciona canal de respuesta** más apropiado:
   - 📧 Email: Para respuestas formales/documentadas
   - 📱 WhatsApp: Para seguimiento rápido/informal
   - 📞 SMS: Para notificaciones breves
   - ☎️ Llamada: Para casos complejos (registro + notas)
4. **Compone respuesta** usando editor específico del canal
5. **Gestiona estado del caso**:
   - Mantiene "in_progress" para seguimiento
   - Marca "pending_closure" si espera confirmación
   - Marca "resolved" si caso está completo
6. **Asigna/valida RUT** empleador (validación MOD 11)
7. **Adjunta documentos** (compatibles con canal seleccionado)
8. **Envía respuesta** - Sistema:
   - Registra como nueva comunicación en timeline
   - Genera código de referencia automático
   - Actualiza estado del caso
   - Coloca en bandeja de salida correspondiente
**Resultado**: Nueva comunicación vinculada al caso, estado actualizado

## Reglas de Negocio Omnicanal

### RN-001: Creación de Casos

- **Un caso por empleador/situación** - Múltiples comunicaciones se vinculan al mismo caso
- **Numeración secuencial**: CASO-YYYY-NNNNNN (único por año)
- **Identificación por RUT empleador** como clave principal de vinculación
- **Ventana de vinculación**: 72 horas para asociar comunicaciones automáticamente

### RN-002: Importación por Canal

**Email (Gmail API)**:
- Solo grupos Gmail configurados en lista blanca
- Lectura cada 1 minuto
- Auto-vinculación por código de referencia en asunto

**WhatsApp Business** (Fase 2):
- Webhook en tiempo real para mensajes entrantes
- Vinculación por número de teléfono + RUT cuando disponible

**SMS** (Fase 3):
- Polling cada 5 minutos o webhook según proveedor
- Vinculación por número de teléfono

**Llamadas** (Fase 4):
- Registro manual post-llamada por ejecutivo
- Vinculación manual a caso existente o creación de nuevo caso

### RN-003: Asignación de Casos

- **Un caso = Un ejecutivo** (todas las comunicaciones del caso)
- **Supervisor puede reasignar casos completos** (no comunicaciones individuales)
- **Auto-asignación inteligente**:
  - Por código de referencia en comunicaciones de seguimiento
  - Por RUT empleador si hay casos previos del mismo ejecutivo
  - Por especialización del ejecutivo en el canal de comunicación
- **Balanceador de carga**: Distribuir casos nuevos equitativamente

### RN-004: Gestión Omnicanal

- **Ejecutivo gestiona casos completos**, no comunicaciones aisladas
- **Respuesta por canal apropiado** según contexto y preferencia del empleador
- **Timeline unificado** mantiene contexto completo independiente del canal
- **Código de referencia automático** en todas las respuestas para vinculación futura
- **SLA por canal**:
  - Email: 24 horas
  - WhatsApp: 4 horas
  - SMS: 2 horas
  - Llamadas: Registro inmediato post-llamada

### RN-005: Resolución y Cierre

- **Auto-resolución**: Casos sin actividad por 48 horas (configurable)
- **Cierre manual**: Supervisor puede cerrar casos completados
- **Casos multi-canal**: Resolución requiere confirmación en canal preferido del empleador
- **Preservación de contexto**: Timeline completo se mantiene en históricos

## ❓ Preguntas Pendientes

### Importación de Correos

1. ¿Con qué frecuencia importar? (cada 5min, 15min, 1hora)
   1.cada 1 minuto
2. ¿Importar solo no leídos o también leídos recientes?
   1. Solo los no leidos
3. ¿Qué hacer con correos que no son para aliases válidos?
   1. Esos correos no los administramos
4. ¿Hay alguna lógica de filtrado automático?
   1. Existen algunas reglas en la cuenta que deja los correos de esos alias en con una etiqueta especial.

### Asignación

5. ¿Debe ser manual siempre o puede haber auto-asignación?
   1. Puede haber auto asignación. en el asuto del correo cuando se responde se agrga un código de referencia. 
6. ¿Un ejecutivo puede tener límite de correos asignados?
   1. No.
7. ¿Los ejecutivos pueden ver correos no asignados?
   1. No.

### Respuestas

8. ¿Las respuestas se envían desde la cuenta original o individual?
   1. Desde la cuenta orpro@orpro pero con el nombre del grupo (el nomnre es un correo) como remitente
9. ¿Necesitas plantillas de respuesta predefinidas?
    1. Para futuras mejoras
10. ¿Los ejecutivos pueden transferir correos entre ellos?
    1. No, lo pueden tranferir al superviusor y agregar una nota. El supervisor reasigna.

### Envío Masivo

11. ¿Quién puede crear campañas? ¿Solo supervisores?
    1. El usuairuo con el rol de masivo, Este puede ser tambien supervisor  
12. ¿Necesitas segmentación avanzada de contactos?
    1. Explicame esto para entender el contexto. Pero inicialmente tenemos una carterización poir rut del empleador y el producto del cliente 
13. ¿Importar contactos desde archivos CSV/Excel?
    1. Si, ademas desde otras BD. el sistema posria necesitar integrarce en más de una forma con otros sistemas
