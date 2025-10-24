# 🔍 COMPARACIÓN DE SCOPES - CORRECCIÓN REQUERIDA

## ❌ SCOPES ACTUALES (INCORRECTOS)
```
https://www.googleapis.com/auth/gmail.readonly
https://www.googleapis.com/auth/gmail.send
https://www.googleapis.com/auth/gmail.modify
https://www.googleapis.com/auth/admin.directory.group          ❌ INCORRECTO
https://www.googleapis.com/auth/admin.directory.group.member   ❌ NO NECESARIO
https://www.googleapis.com/auth/admin.directory.user.readonly
https://www.googleapis.com/auth/drive
https://www.googleapis.com/auth/drive.file
https://www.googleapis.com/auth/drive.apps.readonly            ❌ NO NECESARIO
```

## ✅ SCOPES CORRECTOS (REQUERIDOS)
```
https://www.googleapis.com/auth/gmail.readonly
https://www.googleapis.com/auth/gmail.send
https://www.googleapis.com/auth/gmail.modify
https://www.googleapis.com/auth/admin.directory.group.readonly ✅ AGREGAR .readonly
https://www.googleapis.com/auth/admin.directory.user.readonly
https://www.googleapis.com/auth/drive
https://www.googleapis.com/auth/drive.file
```

## 🎯 CAMBIOS ESPECÍFICOS REQUERIDOS

### 🔧 ELIMINAR estos scopes:
- `https://www.googleapis.com/auth/admin.directory.group.member`
- `https://www.googleapis.com/auth/drive.apps.readonly`

### 🔧 CAMBIAR este scope:
- `admin.directory.group` → `admin.directory.group.readonly`

## 📋 STRING FINAL CORRECTO
**Copia y pega EXACTAMENTE esto en Google Admin Console:**

```
https://www.googleapis.com/auth/gmail.readonly,https://www.googleapis.com/auth/gmail.send,https://www.googleapis.com/auth/gmail.modify,https://www.googleapis.com/auth/drive,https://www.googleapis.com/auth/drive.file,https://www.googleapis.com/auth/admin.directory.group.readonly,https://www.googleapis.com/auth/admin.directory.user.readonly
```

## ⚠️ IMPORTANTE
- Los scopes deben coincidir **EXACTAMENTE** con los configurados en el código
- Scopes adicionales o incorrectos causan el error `unauthorized_client`
- **NO usar** permisos más amplios de los necesarios por seguridad