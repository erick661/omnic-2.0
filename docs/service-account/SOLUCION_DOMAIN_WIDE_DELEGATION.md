# 🔧 CONFIGURACIÓN DOMAIN-WIDE DELEGATION

## 🚨 ERROR ACTUAL
```
Error: unauthorized_client
Descripción: Client is unauthorized to retrieve access tokens using this method, or client not authorized for any of the scopes requested.
```

## 🎯 CAUSA
El Service Account **no está autorizado** para Domain-wide Delegation en Google Admin Console.

## ✅ SOLUCIÓN

### 1. Acceso a Google Admin Console
- 🌐 **URL:** https://admin.google.com
- 👤 **Usuario:** admin@orproverificaciones.cl (debe ser Super Admin)

### 2. Navegación
```
Security > API Controls > Domain-wide delegation
```

### 3. Configuración Exacta
- **Client ID:** `106868506511117693639`
- **OAuth Scopes:**
```
https://www.googleapis.com/auth/gmail.readonly,https://www.googleapis.com/auth/gmail.send,https://www.googleapis.com/auth/gmail.modify,https://www.googleapis.com/auth/drive,https://www.googleapis.com/auth/drive.file,https://www.googleapis.com/auth/admin.directory.group.readonly,https://www.googleapis.com/auth/admin.directory.user.readonly
```

### 4. Verificación
```bash
# Después de la configuración (esperar 2-10 minutos)
php artisan service-account:diagnose
php artisan service-account:test
```

## 🔍 INFORMACIÓN DEL SERVICE ACCOUNT

### Detalles Verificados ✅
- **Email:** omnic-email-manager@omnic-email-system.iam.gserviceaccount.com
- **Project ID:** omnic-email-system
- **Client ID:** 106868506511117693639
- **Admin Email:** admin@orproverificaciones.cl
- **Domain:** orproverificaciones.cl
- **Archivo JSON:** ✅ Presente y válido
- **Permisos:** ✅ 600 (seguro)

### Scopes Detallados
| Scope | Propósito |
|-------|-----------|
| `gmail.readonly` | Leer emails |
| `gmail.send` | Enviar emails |
| `gmail.modify` | Modificar emails |
| `drive` | Acceso completo a Drive |
| `drive.file` | Archivos específicos en Drive |
| `admin.directory.group.readonly` | Leer grupos de Workspace |
| `admin.directory.user.readonly` | Leer usuarios de Workspace |

## 🚨 PROBLEMAS COMUNES

### ❌ Client ID Incorrecto
- Verificar que sea exactamente: `106868506511117693639`
- No debe tener espacios ni caracteres extra

### ❌ Scopes Incorrectos
- Deben estar separados por **comas** (`,`)
- **Sin espacios** entre scopes
- Usar URLs completas (no abreviaciones)

### ❌ Permisos Insuficientes
- El usuario debe ser **Super Admin** en Google Workspace
- Si no tienes permisos, solicitar a otro administrador

### ❌ Propagación Pendiente
- Esperar 2-10 minutos después de la configuración
- Intentar desde navegador incógnito si persiste

## 📞 SOPORTE ADICIONAL

Si el problema persiste después de la configuración:

1. **Verificar Logs de Admin Console**
   - Security > Audit and investigation > Admin console audit log

2. **Verificar Service Account**
   - Google Cloud Console > IAM & Admin > Service Accounts
   - Confirmar que el Service Account existe y está habilitado

3. **Contactar Soporte Google Workspace**
   - Si todos los pasos están correctos y sigue fallando

## 🔄 COMANDOS DE DIAGNÓSTICO

```bash
# Diagnóstico completo
php artisan service-account:diagnose

# Test de funcionalidad
php artisan service-account:test

# Verificar configuración
cat .env | grep GOOGLE
```