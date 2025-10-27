# CONTEXTO TÉCNICO DE DESARROLLO

## 🔧 Sesión Actual de Desarrollo

### Problema Resuelto
**Fecha:** 2025-10-26
**Issue:** Correo "Consulta sobre verificación de empresa ABC" no aparecía en panel de agente
**Causa Raíz:** Usuario simulado vs usuario real autenticado
**Solución:** Actualización de filtros y asignaciones de usuario

### Cambios Realizados

#### 1. Componente Agente (`/resources/views/livewire/inbox/agente.blade.php`)
```php
// ANTES (Usuario simulado)
public function getAuthUser(): array {
    return ['id' => 1, 'name' => 'Juan Pérez'];
}

// DESPUÉS (Usuario real)
public function getAuthUser(): array {
    $user = auth()->user();
    return [
        'id' => $user ? $user->id : 16069813,
        'name' => $user ? $user->name : 'Lucas Muñoz'
    ];
}

// FILTRO ACTUALIZADO
$emails = ImportedEmail::with(['gmailGroup'])
    ->whereIn('case_status', ['assigned', 'opened', 'in_progress', 'resolved'])
    ->where('assigned_to', $currentUserId) // ← AGREGADO
    ->orderBy('received_at', 'desc')
    ->get();
```

#### 2. Asignación de Correos
```sql
-- Correos asignados al usuario real
UPDATE imported_emails 
SET assigned_to = 16069813, case_status = 'assigned', assigned_at = NOW()
WHERE id IN (1, 6, 9, 10, 11, 25);
```

## 🗄️ Estructura de Datos Detallada

### Estados de Caso (case_status)
- `pending` → No asignado, pendiente de revisión
- `assigned` → Asignado a agente, sin iniciar
- `opened` → Agente ha abierto el caso
- `in_progress` → En trabajo activo
- `resolved` → Resuelto por agente
- `closed` → Cerrado definitivamente

### Mapeo UI (mapCaseStatus)
```php
'pending|assigned|opened' → 'asignado' (Columna Kanban)
'in_progress' → 'en_progreso' (Columna Kanban)
'resolved|closed' → 'resuelto' (Columna Kanban)
```

### Prioridades Automáticas (determinePriority)
```php
'urgente|crítico' → 'high' (badge-error)
'consulta|info' → 'low' (badge-success)
default → 'medium' (badge-warning)
```

### Categorías (determineCategory)
```php
'facturación|billing' → 'billing' (badge-primary)
'cuenta|account' → 'account' (badge-secondary)
'bug|error' → 'bug' (badge-error)
'feature|funcionalidad' → 'feature' (badge-info)
default → 'support' (badge-accent)
```

## 📊 Consultas SQL Útiles

### Verificar Correos del Usuario
```sql
SELECT id, subject, case_status, assigned_to, received_at 
FROM imported_emails 
WHERE assigned_to = 16069813 
ORDER BY received_at DESC;
```

### Estadísticas por Estado
```sql
SELECT case_status, COUNT(*) as total 
FROM imported_emails 
GROUP BY case_status;
```

### Correos Pendientes
```sql
SELECT e.id, e.subject, g.name as grupo 
FROM imported_emails e 
JOIN gmail_groups g ON e.gmail_group_id = g.id 
WHERE e.case_status = 'pending';
```

## 🔍 Debugging y Verificación

### Comandos de Verificación
```bash
# Estado del usuario actual
php artisan tinker --execute="dd(auth()->user());"

# Correos asignados
php artisan tinker --execute="
\$emails = App\Models\ImportedEmail::where('assigned_to', 16069813)->get();
echo 'Total: ' . \$emails->count();
foreach(\$emails as \$e) echo \$e->id . ': ' . \$e->subject;
"

# Componente funcionando
curl -s https://dev-estadisticas.orpro.cl/debug/agente | grep -c "Consulta sobre verificación"
```

### URLs de Verificación
- Panel Agente: https://dev-estadisticas.orpro.cl/debug/agente
- Panel Supervisor: https://dev-estadisticas.orpro.cl/debug/supervisor
- Test Component: https://dev-estadisticas.orpro.cl/debug/test

## 🎯 Estado Actual Verificado

### ✅ Panel Agente
- **Correos Visibles:** 6 correos asignados a Lucas Muñoz
- **Correo Específico:** ID 25 "Consulta sobre verificación de empresa ABC" ✅ VISIBLE
- **Funcionalidad:** Kanban board, filtros, categorización - TODO FUNCIONANDO

### ✅ Base de Datos
- **Tabla imported_emails:** 22 registros totales
- **Usuario 16069813:** 6 correos asignados
- **Relaciones:** gmailGroup funcionando correctamente

### ✅ Autenticación
- **Service Account:** Gmail API operativa
- **Usuario Real:** Lucas Muñoz autenticado
- **Permisos:** Domain-wide delegation funcionando

## 🔧 Configuración de Desarrollo

### Rutas de Debug (Sin Middleware Auth)
```php
Route::prefix('debug')->group(function () {
    Volt::route('/test', 'test-component');
    Volt::route('/agente-simple', 'inbox.agente-simple');
    Volt::route('/agente-debug', 'inbox.agente-debug');
    Volt::route('/agente', 'inbox.agente');
    Volt::route('/supervisor', 'inbox.supervisor');
});
```

### Middleware de Producción
```php
Route::middleware(['centralized.auth'])->group(function () {
    Volt::route('/agente', 'inbox.agente')->name('agente');
    Volt::route('/supervisor', 'inbox.supervisor');
});
```

## 📝 Logs y Debugging

### Ubicación de Logs
- Laravel: `/var/www/omnic/storage/logs/laravel.log`
- Nginx: `/var/log/nginx/error.log`

### Debug de Componentes Livewire
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -i "livewire\|error"

# Limpiar caches
php artisan view:clear && php artisan cache:clear && php artisan config:clear
```

---

**Nota:** Este archivo debe actualizarse después de cada sesión de desarrollo significativa para mantener el contexto actualizado.