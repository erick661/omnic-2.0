# Requerimientos Funcionales - Sistema de Correos Omnic

## Contexto

- **Proyecto**: Sistema de gestión de correos para contactos con empleadosres ( deudores precicionales)
- **Objetivo**: Reemplazar sistema legacy PHP
- **Usuarios**: Supervisores y Ejecutivos
- **Tecnología**: Laravel 12 + Livewire Volt + TailwindCSS + Mary UI
- **BaseDatos** PostgreSQL 18

## Actores

- **Supervisor**: Asigna correos, supervisa ejecutivos, ve estadísticas
- **Ejecutivo**: Recibe asignaciones, responde correos, gestiona su bandeja, ingresa actividades asocidas al correo
- **Masivo**: Carga lista de envio de correos masivos, Visualiza el estado del envio. Editas plantilals de correos Masivos
- **Administrador**: Puede modificar las configuraciones del sistema y dar de baja usuarios
- **Sistema**: Importa correos automáticamente, envía masivos via Brevo, envia correos de respuesta
  
## Flujos de Trabajo

### Flujo 1: Importación de Correos

1. Sistema conecta a cuenta IMAP principal cada X minutos (Via API de Gmail).
2. Cuenta principal orpro@orpro esparte de varios grupos de google workspace.
3. Lee correos no leídos dirijidos a los grupos en los que es parte.
4. Almacena en BD con estado "pending" O
5. Si hay codigo de refencia en el asunto de un envio masivo o respuesta anterior lo asigna según esta.
6. Los supervisores pueden ver todos los correos en la bandeja de supervisores.

### Flujo 2: Asignación por Supervisor

1. Supervisor ve lista de correos pendientes.
2. Visualiza el correo y analiza.
3. Selecciona correo(s) y ejecutivo destino.
4. Cambia estado a "assigned".
5. Ejecutivo recibe notificación.

### Flujo 3: Gestión por Ejecutivo

1. Ejecutivo ve sus correos asignados
2. Puede marcar como "in_progress", "resolved"
3. Puede responder directamente desde el sistema
4. Todos las respuesta deben quedar en una "bandeja de salida" para que el sistema las procese
5. Puede derivar al supervisor para revición o reasignación.
6. Supervisor puede ver el progreso.

### Flujo 4: Clasificación correos SPAM

1. El supervisor lista todos los correos
2. Si determian que el correo es spam lo marca
3. Los correos SPAM permanecen en el sistema vigente por 30 días

### Flujo 5: Mantencion correos vigentes

1. Una ves al día el sistema revisa las tablas con correos vigente
2. Buscara en esta tablas los correso marcados como SPAM con mas de 30 días
3. Buscara los correos con estado finalizado con mas de 60.
4. Movera los datos de la BD correspondientes a estos coorreos a tablas de historicos que son replizas de las vigentes.

### Flujo 6: Carga de lista de correos Masivos

1. El Masivo carga la lista a la plataformde desde un archivo CSV o xlsx.
2. Asocia la carga a una platilla.
3. Programa el envio

### Flujo 7: Envio de Masivos

1. El sistema ejecuta el envio dentro del horario legal para comunicaciones de Chile.
2. Informa a el Supoervisor y al Masivo del comienzo del envio.
3. El sistema en forma periodica coinsulta la API de Brevo para revisar el estado real del envio y las estadisticas de efectividad.
4. El Masivo puede consultar las estadisticas.
5. Informa al Supevisor y al Masico del termino cuando se complete la campaña de envio.

### Flujo 8: Envio de bandeja de salida

1. El sistema lee la "bandeja de salida cada 1 minuto"
2. Envia los correos de la bandeja
3. Marca los correos como enviado

## Casos de Uso

### CU-001: Ver Bandeja de Supervisor

**Actor**: Supervisor
**Precondición**: Usuario autenticado como supervisor
**Flujo**:

1. Sistema muestra dashboard con:
   - Correos vigentes  
   - Estadísticas del día/semana
2. Supervisor puede filtrar por fecha, ejecutivo, estado.
3. Supervisor puede asignar masivamente de un ejecutivo a otro (Traspaso de cartera).
4. Supervisor puede marcar como spam un correo.

### CU-002: Asignar Correo Individual

**Actor**: Supervisor
**Flujo**:

1. Supervisor selecciona correo de lista
2. Ve detalle completo (remitente, asunto, cuerpo, adjuntos)
3. Selecciona ejecutivo de dropdown
4. Agrega nota interna opcional
5. Confirma asignación
**Resultado**: Correo cambia a estado "assigned"

### CU-003: Ver Bandeja de Ejecutivo

**Actor**: Ejecutivo
**Precondición**: Usuario autenticado como Ejecutivo
**Flujo**:

1. Sistema muestra dashboard con:
   - Correos vigentes  
2. Ejecutivo puede filtrar por fecha, ejecutivo, estado.
3. Ejecutivo puede visualizar el correo

### CU-004: Responder Correo

**Actor**: Ejecutivo
**Flujo**:

1. Ejecutivo selecciona correo de lista
2. Ve detalle completo (remitente, asunto, cuerpo, adjuntos).
3. Escribe respuesta.
4. Puede marcar marcar el estado como terminado, Sistema lo marca como pendiente de cierre.
5. Puede asignar un rut. El sistema valida que sea un rut valido (MOD 11).
6. Puede adjuntar documentos a la respuesta.
7. Envia el correo.
8. Sistema si se asicio a un rut y el correo aún no lo tiene. Agrega un codigo de referencia. El rut del empleados codificado.
9. Sistema deja el correo en la bandeja de salida.
**Resultado**: Correo cambia a estado "respondido o pendiente de cierre"

## Reglas de Negocio

### RN-001: Importación

- Solo importar correos donde la cuenta <orpro@orpro.cl> es parte del grupo y esta en una lista de grupos especifico
- Lectura cada 1 minuto

### RN-002: Asignación

- Un correo solo puede estar asignado a un ejecutivo
- Supervisor puede reasignar correos no respondidos
- Auto-asignación basada en base a asunto del correo que contenga un codigo de referencia
- La asignación es por rut y producto del cliente (Nustros clientes son las AFP y estos tienen productos)

### RN-003: Respuestas

- Ejecutivo debe responder desde el sistema para tracking
- Marcar como resuelto cuando pasen mas de dos días sin respuesta del empleador o contacto
- El supervisor puede marcarlos como resueltos

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
