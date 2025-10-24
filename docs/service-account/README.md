# 🔐 Service Account con Domain-wide Delegation - Guía Completa

## 📋 **Resumen Ejecutivo**

Esta guía documenta la implementación completa de autenticación mediante Service Account con Domain-wide Delegation para el sistema Omnic 2.0.

## 🎯 **Configuración Final**

### **Service Account Details:**
- **Email:** `omnic-email-manager@omnic-email-system.iam.gserviceaccount.com`
- **Project ID:** `omnic-email-system`
- **Client ID:** `106868506511117693639`
- **Domain:** `orproverificaciones.cl`
- **Admin Email:** `admin@orproverificaciones.cl`

### **Scopes Configurados:**
```
https://www.googleapis.com/auth/gmail.readonly,https://www.googleapis.com/auth/gmail.send,https://www.googleapis.com/auth/gmail.modify,https://www.googleapis.com/auth/gmail.compose,https://www.googleapis.com/auth/admin.directory.group,https://www.googleapis.com/auth/admin.directory.group.member,https://www.googleapis.com/auth/admin.directory.user.readonly,https://www.googleapis.com/auth/drive,https://www.googleapis.com/auth/drive.file,https://www.googleapis.com/auth/chat.messages.create,https://www.googleapis.com/auth/chat.messages.readonly,https://www.googleapis.com/auth/chat.spaces,https://www.googleapis.com/auth/chat.spaces.readonly,https://www.googleapis.com/auth/calendar,https://www.googleapis.com/auth/calendar.events
```

## 🔧 **Configuración de Archivos**

### **Variables de Entorno (.env):**
```env
GMAIL_AUTH_MODE=service_account
GOOGLE_SERVICE_ACCOUNT_PATH="/var/www/omnic/storage/app/google-credentials/google-service-account.json"
GOOGLE_WORKSPACE_ADMIN_EMAIL="admin@orproverificaciones.cl"
GOOGLE_WORKSPACE_DOMAIN="orproverificaciones.cl"
GOOGLE_CLIENT_ID="106868506511117693639"
```

### **Archivo de Credenciales:**
- **Ubicación:** `storage/app/google-credentials/google-service-account.json`
- **Permisos:** `600` (solo lectura para propietario)

## 🚀 **Funcionalidades Implementadas**

| Servicio | Scopes | Funcionalidad |
|----------|--------|---------------|
| **Gmail** | `readonly`, `send`, `modify`, `compose` | Importación, envío, respuestas, reenvío de correos |
| **Admin Directory** | `group`, `group.member`, `user.readonly` | Gestión completa de grupos y usuarios |
| **Drive** | `drive`, `drive.file` | Gestión de adjuntos y compartición de archivos |
| **Chat** | `messages.create`, `messages.readonly`, `spaces`, `spaces.readonly` | Integración con Google Chat (futuro) |
| **Calendar** | `calendar`, `calendar.events` | Gestión de calendarios y eventos (futuro) |

## 🧪 **Comando de Prueba**

Para verificar la configuración:
```bash
php artisan service-account:test
```

## 📚 **Documentación Relacionada**

- [Guía de Adjuntos Drive](GUIA_DRIVE_ATTACHMENTS.md)
- [Solución Domain-wide Delegation](SOLUCION_DOMAIN_WIDE_DELEGATION.md)
- [Corrección de Scopes](CORRECCION_SCOPES.md)

## 🔄 **Historial de Cambios**

- **v1.0.0** - Implementación inicial con OAuth
- **v1.1.0** - Migración a Service Account con Domain-wide Delegation
- **v1.2.0** - Corrección y validación de scopes con documentación oficial

## ⚠️ **Notas Importantes**

1. **Propagación de Cambios:** Los cambios en Google Admin Console pueden tardar 2-10 minutos
2. **Permisos Requeridos:** Super Admin en Google Workspace
3. **Seguridad:** El archivo JSON contiene credenciales sensibles - mantener permisos 600
4. **Scopes Validados:** Todos los scopes han sido verificados con la documentación oficial de Google