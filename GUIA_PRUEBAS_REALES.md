# GUÍA DE PRUEBAS REALES - SISTEMA DE EMAIL OMNICANAL
## Usuario de Prueba: Lucas Muñoz (lucas.munoz@orpro.cl - ID: 16069813)

### ✅ CONFIGURACIÓN PREVIA COMPLETADA

**Usuario creado:**
- ID: 16069813  
- Email: lucas.munoz@orpro.cl
- Nombre: Lucas Muñoz

**Grupo Gmail creado:**
- Email: ejecutivo.lucas.munoz@orproverificaciones.cl
- Asociado al usuario ID: 16069813
- Miembro único: admin@orproverificaciones.cl

---

## 📋 PASO A PASO PARA PRUEBAS REALES

### FASE 1: PREPARACIÓN DEL SISTEMA

#### 1.1 Verificar configuración OAuth
```bash
php artisan gmail:oauth-status
```
> **Verificar:** Token válido y scopes correctos (gmail.readonly, gmail.send)

#### 1.2 Verificar conexión a Gmail
```bash
php artisan test:simple-email-sending --real --to=lucas.munoz@orpro.cl
```
> **Esperado:** Email enviado exitosamente con Message ID

---

### FASE 2: PRUEBAS DE FLUJO COMPLETO

#### 2.1 Importar emails desde Gmail
```bash
php artisan emails:import --group-email=ejecutivo.lucas.munoz@orproverificaciones.cl
```

**QUÉ VERIFICAR:**
- [ ] Emails importados correctamente
- [ ] Aparecen en tabla `imported_emails`
- [ ] Status: `pending`

#### 2.2 Asignar emails importados al usuario
```bash
php artisan emails:assign-to-user --user-id=16069813
```

**QUÉ VERIFICAR:**
- [ ] Emails cambian de status `pending` → `assigned`  
- [ ] Campo `assigned_to_user_id` = 16069813
- [ ] Aparecen en interfaz web del usuario Lucas

#### 2.3 Procesar outbox (enviar respuestas)
```bash
php artisan emails:send-outbox
```

**QUÉ VERIFICAR:**
- [ ] Emails en `outbox_emails` se procesan
- [ ] Status cambia a `sent`
- [ ] Se obtiene `gmail_message_id`
- [ ] Destinatario recibe el email

---

### FASE 3: PRUEBAS EN INTERFAZ WEB

#### 3.1 Acceso al sistema web
1. Abrir navegador: `http://localhost/login` (ajustar URL según configuración)
2. Iniciar sesión con usuario Lucas:
   - Email: `lucas.munoz@orpro.cl`  
   - Password: `password123`

#### 3.2 Verificar dashboard
**VERIFICAR EN PANTALLA:**
- [ ] Dashboard muestra emails asignados
- [ ] Contador de emails pendientes correcto
- [ ] Filtros funcionan (leídos/no leídos)

#### 3.3 Visualizar email específico
1. Click en email de la lista
2. Verificar que se abre correctamente

**VERIFICAR:**
- [ ] Remitente se muestra correctamente
- [ ] Asunto completo visible
- [ ] Contenido del email se renderiza bien
- [ ] Fecha/hora correctas

#### 3.4 Responder a un email
1. Abrir email sin responder
2. Click en "Responder"
3. Escribir respuesta de prueba:
   ```
   Hola,
   
   Esta es una respuesta de prueba del sistema omnicanal.
   Confirmamos la recepción de tu mensaje.
   
   Saludos,
   Lucas Muñoz
   Ejecutivo de Verificaciones
   ```
4. Click en "Enviar"

**VERIFICAR:**
- [ ] Respuesta se guarda en `outbox_emails`
- [ ] Status inicial: `pending`
- [ ] Mensaje de confirmación en pantalla
- [ ] Email original cambia status a "respondido"

#### 3.5 Procesar envío automático
```bash
# En terminal paralelo:
php artisan emails:send-outbox
```

**VERIFICAR:**
- [ ] Status en `outbox_emails` cambia a `sent`
- [ ] Se asigna `gmail_message_id`
- [ ] En interfaz web: estado actualizado
- [ ] Destinatario recibe la respuesta

---

### FASE 4: VERIFICACIONES DE INTEGRIDAD

#### 4.1 Verificar creación de casos
```bash
php artisan tinker --execute="
\App\Models\CustomerCase::with(['communications', 'assignedUser'])
->where('assigned_user_id', 16069813)
->orderBy('created_at', 'desc')
->first()
"
```

**VERIFICAR:**
- [ ] Se crea caso automáticamente
- [ ] `case_number` generado (formato: CASO-YYYY-XXXXXX)
- [ ] `assigned_user_id` = 16069813
- [ ] Status = `open`
- [ ] Comunicaciones asociadas

#### 4.2 Verificar historial completo
```bash
php artisan tinker --execute="
\App\Models\Communication::with(['customerCase', 'importedEmail', 'outboxEmail'])
->whereHas('customerCase', function(\$q) {
    \$q->where('assigned_user_id', 16069813);
})
->orderBy('created_at', 'desc')
->get()
"
```

**VERIFICAR:**
- [ ] Comunicación de entrada (imported_email)
- [ ] Comunicación de salida (outbox_email) 
- [ ] Ambas asociadas al mismo caso
- [ ] Timestamps correctos

---

### FASE 5: PRUEBAS DE ESCENARIOS EDGE

#### 5.1 Email duplicado
```bash
# Importar nuevamente el mismo email
php artisan emails:import --group-email=ejecutivo.lucas.munoz@orproverificaciones.cl
```

**VERIFICAR:**
- [ ] No se duplican registros
- [ ] Sistema detecta emails ya procesados
- [ ] Log indica "ya existe"

#### 5.2 Respuesta múltiple al mismo email
1. En interfaz web, intentar responder nuevamente al mismo email
2. **VERIFICAR:**
   - [ ] Sistema permite o bloquea según configuración
   - [ ] Si permite: se crea nueva comunicación
   - [ ] Si bloquea: mensaje explicativo

#### 5.3 Email sin remitente válido
- Verificar comportamiento con emails malformados
- **VERIFICAR:** Sistema no se rompe, logs de error apropiados

---

## 📊 CHECKLIST FINAL DE VALIDACIÓN

### Funcionalidades Core
- [ ] ✅ Importación de emails desde Gmail
- [ ] ✅ Asignación automática por grupo
- [ ] ✅ Visualización en interfaz web
- [ ] ✅ Sistema de respuestas
- [ ] ✅ Envío real via Gmail API
- [ ] ✅ Creación automática de casos
- [ ] ✅ Historial de comunicaciones

### Performance y Estabilidad  
- [ ] ✅ Comandos ejecutan sin errores
- [ ] ✅ Interfaz web responde < 2 segundos
- [ ] ✅ No hay memory leaks en procesamiento
- [ ] ✅ Logs de error vacíos (sin errores críticos)

### Seguridad
- [ ] ✅ Autenticación OAuth funcional
- [ ] ✅ Usuario solo ve sus emails asignados
- [ ] ✅ No hay exposición de datos sensibles
- [ ] ✅ Validación de permisos en interfaz

### Integración Gmail
- [ ] ✅ Scopes correctos configurados
- [ ] ✅ Rate limits respetados
- [ ] ✅ Manejo de errores de API
- [ ] ✅ Message threading funcional

---

## 🚨 TROUBLESHOOTING COMÚN

### Error: "Token expired"
```bash
php artisan gmail:reauth
```

### Error: "Insufficient permissions"
```bash
# Verificar scopes
php artisan gmail:oauth-status
```

### Error: "Group not found"
```bash
# Re-crear grupo
php artisan gmail:create-group ejecutivo.lucas.munoz@orproverificaciones.cl --user-id=16069813
```

### Error: "Database connection"
```bash
# Verificar conexión
php artisan tinker --execute="DB::connection()->getPdo()"
```

---

## 📋 COMANDOS DE MONITOREO ÚTILES

```bash
# Ver últimos emails importados
php artisan tinker --execute="
\App\Models\ImportedEmail::orderBy('created_at', 'desc')->limit(5)->get(['subject', 'from_email', 'status', 'created_at'])
"

# Ver últimas respuestas enviadas  
php artisan tinker --execute="
\App\Models\OutboxEmail::orderBy('created_at', 'desc')->limit(5)->get(['to_email', 'subject', 'status', 'gmail_message_id'])
"

# Ver casos del usuario Lucas
php artisan tinker --execute="
\App\Models\CustomerCase::where('assigned_user_id', 16069813)->count()
"

# Estado general del sistema
php artisan emails:status
```

---

## 🎯 MÉTRICAS DE ÉXITO

**Para considerar las pruebas EXITOSAS:**

1. **Importación:** ≥95% emails importados sin error
2. **Asignación:** 100% emails asignados al usuario correcto  
3. **Visualización:** Interfaz carga completa en <2seg
4. **Respuesta:** 100% respuestas enviadas exitosamente
5. **Casos:** 100% casos creados automáticamente
6. **Integridad:** 0 registros huérfanos o duplicados

---

*Documento generado para pruebas del sistema omnicanal con usuario lucas.munoz@orpro.cl*  
*Fecha: 22 de Octubre 2025*