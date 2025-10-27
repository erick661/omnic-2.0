# COMANDOS ÚTILES - OMNIC 2.0

## 📧 Comandos de Email (✅ SOLID - Sin Duplicación)

### Importación de Correos
```bash
# Importar correos de todos los grupos activos
php artisan email:import

# Importar solo de grupos específicos
php artisan email:import --group=ejecutivo.lucas.munoz@orproverificaciones.cl --group=soporte@orproverificaciones.cl

# Importar últimos 3 días con límite
php artisan email:import --days=3 --limit=50

# Dry run para ver qué se importaría
php artisan email:import --dry-run
```

### Asignación de Correos
```bash
# Asignar correo específico a agente
php artisan email:assign-to-agent 123 456 --supervisor=1

# Asignar con notas específicas
php artisan email:assign-to-agent 123 456 --notes="Caso urgente, requiere atención inmediata"
```

### Procesamiento y Envío
```bash
# Procesar bandeja de salida
php artisan email:process-outbox

# Procesar con reintentos de fallidos
php artisan email:process-outbox --retry

# Enviar email directo
php artisan email:send destinatario@ejemplo.com "Asunto del correo" --body="Mensaje de prueba"

# Enviar con plantilla
php artisan email:send test@ejemplo.com "Test" --template=bienvenida --vars="name=Juan,company=ABC"

# Programar email
php artisan email:send test@ejemplo.com "Programado" --body="Mensaje" --schedule="2024-12-01 10:00:00"
```

### Estadísticas y Monitoreo
```bash
# Estadísticas de hoy
php artisan email:stats

# Estadísticas de la semana
php artisan email:stats --period=week

# Estadísticas de un agente específico
php artisan email:stats --agent=16069813

# Estadísticas de un grupo específico
php artisan email:stats --group=ejecutivo.lucas.munoz@orproverificaciones.cl
```

## 👥 Comandos de Grupos (Nuevos)

### Gestión de Grupos
```bash
# Listar todos los grupos
php artisan groups:list

# Listar solo grupos activos con estadísticas
php artisan groups:list --active-only --with-stats

# Crear nuevo grupo
php artisan groups:create "Soporte Técnico" soporte@orproverificaciones.cl --description="Grupo de soporte"

# Crear grupo con auto-asignación
php artisan groups:create "Ventas" ventas@orproverificaciones.cl --auto-assign
```

### Gestión de Miembros
```bash
# Listar miembros de un grupo
php artisan groups:members list ejecutivo.lucas.munoz@orproverificaciones.cl

# Agregar miembro
php artisan groups:members add ejecutivo.lucas.munoz@orproverificaciones.cl nuevo@orproverificaciones.cl

# Agregar miembro como manager
php artisan groups:members add ejecutivo.lucas.munoz@orproverificaciones.cl admin@orproverificaciones.cl --role=manager

# Remover miembro
php artisan groups:members remove ejecutivo.lucas.munoz@orproverificaciones.cl usuario@orproverificaciones.cl
```

## 💾 Comandos de Drive (Base creada)

```bash
# Listar carpetas principales
php artisan drive:folders

# Listar carpetas de una carpeta específica
php artisan drive:folders --parent=FOLDER_ID

# Listar con límite
php artisan drive:folders --limit=20
```

## 💬 Comandos de Chat (Base creada)

```bash
# Enviar mensaje a espacio
php artisan chat:send SPACE_ID "Mensaje de prueba"

# Responder en hilo
php artisan chat:send SPACE_ID "Respuesta" --thread=THREAD_ID
```

## �🔍 Comandos de Verificación Rápida

### Estado General del Sistema
```bash
# Usuario autenticado actual
php artisan tinker --execute="dd(auth()->user());"

# Correos totales en sistema
php artisan tinker --execute="echo 'Total correos: ' . App\Models\ImportedEmail::count();"

# Correos de Lucas Muñoz (usuario actual)
php artisan tinker --execute="
\$emails = App\Models\ImportedEmail::where('assigned_to', 16069813)->get();
echo 'Correos asignados a Lucas: ' . \$emails->count() . PHP_EOL;
foreach(\$emails->take(5) as \$e) {
    echo '- ID: ' . \$e->id . ' | ' . \$e->subject . PHP_EOL;
}
"

# Estadísticas por estado
php artisan tinker --execute="
foreach(App\Models\ImportedEmail::selectRaw('case_status, count(*) as total')->groupBy('case_status')->get() as \$s) {
    echo \$s->case_status . ': ' . \$s->total . PHP_EOL;
}
"
```

### Verificación de Funcionalidad
```bash
# Probar autenticación Gmail
php artisan gmail:test-auth 2>/dev/null || echo "Comando no existe, usar importación"

# Importar correos nuevos
php artisan emails:import

# Enviar email de prueba
php artisan gmail:send-test ejecutivo.lucas.munoz@orproverificaciones.cl --subject="Test $(date)"

# Verificar que el correo específico esté visible
curl -s https://dev-estadisticas.orpro.cl/debug/agente | grep -q "verificación de empresa ABC" && echo "✅ Correo visible" || echo "❌ Correo no visible"
```

## 📊 Consultas de Base de Datos

### Consultas SQL Directas (psql)
```sql
-- Conectar a BD
psql -h 127.0.0.1 -U laravel_dev -d laravel_db

-- Correos del usuario actual
SELECT id, subject, case_status, assigned_to, received_at 
FROM imported_emails 
WHERE assigned_to = 16069813 
ORDER BY received_at DESC LIMIT 10;

-- Estados de todos los correos
SELECT case_status, COUNT(*) as total 
FROM imported_emails 
GROUP BY case_status;

-- Grupos con más correos
SELECT g.name, COUNT(e.id) as total_emails
FROM gmail_groups g
LEFT JOIN imported_emails e ON g.id = e.gmail_group_id
GROUP BY g.id, g.name
ORDER BY total_emails DESC;
```

### Consultas con Eloquent
```bash
# Correo específico de Lucas
php artisan tinker --execute="
\$email = App\Models\ImportedEmail::find(25);
if (\$email) {
    echo 'ID: ' . \$email->id . PHP_EOL;
    echo 'Asunto: ' . \$email->subject . PHP_EOL;
    echo 'Estado: ' . \$email->case_status . PHP_EOL;
    echo 'Asignado a: ' . \$email->assigned_to . PHP_EOL;
    echo 'Grupo: ' . \$email->gmailGroup->name . PHP_EOL;
} else {
    echo 'Email no encontrado' . PHP_EOL;
}
"

# Listar grupos Gmail
php artisan tinker --execute="
foreach(App\Models\GmailGroup::take(10)->get() as \$g) {
    echo \$g->id . ': ' . \$g->name . ' (' . \$g->email . ')' . PHP_EOL;
}
"
```

## 🛠️ Comandos de Desarrollo

### Limpieza de Caches
```bash
# Limpiar todos los caches
php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Regenerar autoload
composer dump-autoload

# Limpiar logs
> storage/logs/laravel.log
```

### Debugging de Componentes
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Verificar sintaxis de componente
php -l resources/views/livewire/inbox/agente.blade.php

# Probar URL de componente
curl -I https://dev-estadisticas.orpro.cl/debug/agente

# Ver contenido HTML del componente
curl -s https://dev-estadisticas.orpro.cl/debug/agente | grep -A 10 -B 10 "Total Casos"
```

### Asignación Manual de Correos
```bash
# Asignar correo específico a Lucas
php artisan tinker --execute="
\$email = App\Models\ImportedEmail::find(25);
\$email->assigned_to = 16069813;
\$email->case_status = 'assigned';
\$email->assigned_at = now();
\$email->save();
echo 'Correo asignado: ' . \$email->subject;
"

# Asignar múltiples correos pendientes
php artisan tinker --execute="
\$emails = App\Models\ImportedEmail::where('case_status', 'pending')->take(3)->get();
foreach(\$emails as \$email) {
    \$email->assigned_to = 16069813;
    \$email->case_status = 'assigned';
    \$email->assigned_at = now();
    \$email->save();
    echo 'Asignado: ' . \$email->subject . PHP_EOL;
}
"
```

## 🎯 Verificación de Estado Específico

### Verificar Correo de Lucas Visible
```bash
# Una línea para verificar todo
php artisan tinker --execute="
\$email = App\Models\ImportedEmail::find(25);
\$visible = \$email && \$email->assigned_to == 16069813 && \$email->case_status == 'assigned';
echo \$visible ? '✅ Correo de Lucas VISIBLE en panel' : '❌ Correo de Lucas NO visible';
"

# Verificar en la UI
curl -s https://dev-estadisticas.orpro.cl/debug/agente | grep -q "Consulta sobre verificación de empresa ABC" && echo "✅ UI OK" || echo "❌ UI Error"
```

### Panel del Agente Estado
```bash
# Verificar que el componente carga
curl -s https://dev-estadisticas.orpro.cl/debug/agente | grep -c "Total Casos"

# Verificar estadísticas
curl -s https://dev-estadisticas.orpro.cl/debug/agente | grep -o "value=\"[0-9]*\"" | head -4
```

## 🔧 Comandos de Gmail API

### Gestión de Grupos
```bash
# Listar grupos (si existe el comando)
php artisan gmail:list-groups 2>/dev/null || echo "Usar consulta BD"

# Verificar miembros de grupo
php artisan gmail:group-members list ejecutivo.lucas.munoz@orproverificaciones.cl 2>/dev/null || echo "Comando puede no existir"

# Buscar en grupo específico
php artisan gmail:search-group ejecutivo.lucas.munoz@orproverificaciones.cl 2>/dev/null || echo "Comando puede no existir"
```

## 📝 Scripts de Contexto

### Generar contexto automático
```bash
# Ejecutar script de contexto
./scripts/generate_context.sh

# Ver contexto generado
cat docs/AUTO_GENERATED_CONTEXT.md

# Contexto manual completo
cat CONTEXT.md

# Contexto técnico
cat TECHNICAL_CONTEXT.md
```

## 🚨 Comandos de Emergencia

### Si el panel no carga
```bash
# 1. Verificar usuario autenticado
php artisan tinker --execute="var_dump(auth()->user());"

# 2. Verificar correos asignados
php artisan tinker --execute="echo App\Models\ImportedEmail::where('assigned_to', 16069813)->count();"

# 3. Limpiar caches
php artisan view:clear && php artisan cache:clear

# 4. Verificar URL
curl -I https://dev-estadisticas.orpro.cl/debug/agente

# 5. Ver logs de error
tail -20 storage/logs/laravel.log
```

### Si Gmail API falla
```bash
# Verificar credenciales
ls -la /var/www/omnic/storage/app/google-credentials/

# Verificar variables de entorno
grep GOOGLE .env

# Test de conexión (si existe)
php artisan emails:import | head -10
```

## 📋 Checklist de Estado Saludable

```bash
# ✅ Usuario autenticado: ID 16069813
php artisan tinker --execute="echo auth()->user()->id ?? 'No auth';"

# ✅ Correos asignados: >= 6
php artisan tinker --execute="echo App\Models\ImportedEmail::where('assigned_to', 16069813)->count();"

# ✅ Correo específico visible: ID 25
php artisan tinker --execute="echo App\Models\ImportedEmail::find(25)->assigned_to == 16069813 ? 'OK' : 'ERROR';"

# ✅ Panel carga: HTTP 200
curl -I https://dev-estadisticas.orpro.cl/debug/agente | head -1

# ✅ Contenido visible: Contiene correo de Lucas
curl -s https://dev-estadisticas.orpro.cl/debug/agente | grep -q "verificación de empresa ABC" && echo "OK" || echo "ERROR"
```

---

**Uso recomendado:** Copia y pega estos comandos según necesites verificar el estado del sistema en cualquier momento.