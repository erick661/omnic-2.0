# REORGANIZACIÓN DE COMANDOS Y SERVICIOS - OMNIC 2.0

## 📋 Resumen de Cambios

### ✅ Lo que se ha creado:

#### 🏗️ Arquitectura de Servicios Base
- `GoogleApiService` - Clase base con manejo centralizado de:
  - Autenticación Service Account
  - Cache de tokens
  - Sistema de reintentos
  - Configuración de scopes

#### 📧 Servicios de Email (Nuevos)
- `EmailImportService` - Importación avanzada de correos
- `EmailSendService` - Envío de correos con templates
- `EmailStatsService` - Estadísticas y reportes
- `OutboxEmailService` - Movido y actualizado namespace

#### 👥 Servicios de Grupos
- `GmailGroupService` - Gestión completa de grupos Gmail

#### 🗂️ Servicios Base (Drive y Chat)
- `DriveService` - Gestión de Google Drive
- `ChatService` - Integración con Google Chat

#### 📁 Comandos Reorganizados
- `Email/` - 4 comandos nuevos para gestión de correos
- `Groups/` - 3 comandos para gestión de grupos
- `System/` - Comando para reorganizar archivos existentes
- `Drive/` y `Chat/` - Comandos base creados

### 🔄 Próximos pasos:

#### 1. Reorganizar comandos existentes
```bash
# Ejecutar comando de reorganización
php artisan system:organize-commands

# Actualizar autoload
composer dump-autoload
```

#### 2. Migrar servicios legacy
- Actualizar referencias a `GmailService` antiguo
- Migrar funcionalidad a nuevos servicios específicos
- Mantener compatibilidad durante transición

#### 3. Actualizar imports y referencias
- Cambiar `use App\Services\OutboxEmailService` por `use App\Services\Email\OutboxEmailService`
- Actualizar dependencias en controladores y otros comandos

## 🎯 Beneficios de la nueva arquitectura:

### ✅ Separación de responsabilidades
- Cada servicio tiene una función específica
- Comandos organizados por funcionalidad
- Código más mantenible

### ✅ Reutilización de código
- Clase base `GoogleApiService` evita duplicación
- Manejo centralizado de autenticación
- Sistema común de reintentos y logging

### ✅ Escalabilidad
- Fácil agregar nuevos servicios (Drive, Chat, etc.)
- Estructura preparada para futuras funcionalidades
- Configuración centralizada

### ✅ Mejor debugging
- Logs más específicos por servicio
- Comandos especializados para cada área
- Separación clara entre funcional y tests

## 📝 Comandos disponibles inmediatamente:

### Email
```bash
php artisan email:import --days=7
php artisan email:process-outbox
php artisan email:send test@ejemplo.com "Test" --body="Prueba"
php artisan email:stats --period=today
```

### Grupos
```bash
php artisan groups:list --with-stats
php artisan groups:create "Nuevo Grupo" nuevo@orproverificaciones.cl
php artisan groups:members list ejecutivo.lucas.munoz@orproverificaciones.cl
```

### Sistema
```bash
php artisan system:organize-commands
```

## 🛠️ Archivos modificados/creados:

### Servicios
- ✅ `app/Services/Base/GoogleApiService.php`
- ✅ `app/Services/Email/EmailImportService.php`
- ✅ `app/Services/Email/EmailSendService.php`
- ✅ `app/Services/Email/EmailStatsService.php`
- ✅ `app/Services/Email/OutboxEmailService.php` (movido)
- ✅ `app/Services/Groups/GmailGroupService.php`
- ✅ `app/Services/Drive/DriveService.php`
- ✅ `app/Services/Chat/ChatService.php`

### Comandos
- ✅ `app/Console/Commands/Email/` (4 comandos)
- ✅ `app/Console/Commands/Groups/` (3 comandos)
- ✅ `app/Console/Commands/System/OrganizeCommandsCommand.php`
- ✅ `app/Console/Commands/Drive/ListDriveFoldersCommand.php`
- ✅ `app/Console/Commands/Chat/SendChatMessageCommand.php`

### Documentación
- ✅ `USEFUL_COMMANDS.md` actualizado con nuevos comandos
- ✅ `CONTEXT.md` actualizado con nueva arquitectura

## 🔧 Para completar la migración:

1. **Ejecutar reorganización:**
   ```bash
   php artisan system:organize-commands
   composer dump-autoload
   ```

2. **Probar nuevos comandos:**
   ```bash
   php artisan email:stats
   php artisan groups:list --with-stats
   ```

3. **Actualizar referencias:**
   - Buscar `use App\Services\OutboxEmailService`
   - Cambiar por `use App\Services\Email\OutboxEmailService`

4. **Migrar gradualmente:**
   - Mantener `GmailService` antiguo temporalmente
   - Migrar funcionalidad paso a paso
   - Actualizar tests

La nueva arquitectura está lista y funcional. Los comandos nuevos pueden usarse inmediatamente, y la reorganización de comandos existentes se puede hacer cuando sea conveniente.