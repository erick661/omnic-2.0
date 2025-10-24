# 🚨 ARCHIVOS DE PRUEBA Y DESARROLLO - INVENTARIO

> **NOTA IMPORTANTE:** Este documento identifica todos los archivos creados para pruebas, desarrollo o experimentación que **NO son funcionales al proyecto**.

## 📋 **COMANDOS DE PRUEBA (PARA ELIMINAR)**

### **Ubicación:** `app/Console/Commands/`

| Archivo | Propósito | Estado | Acción Requerida |
|---------|-----------|---------|------------------|
| `TestServiceAccount.php` | ✅ **MANTENER** - Comando funcional para verificar Service Account | **FUNCIONAL** | Conservar - Es útil para diagnósticos |
| `DiagnoseServiceAccount.php` | ❌ Diagnóstico temporal durante desarrollo | **ELIMINAR** | Borrar - Ya no es necesario |
| `CheckScopes.php` | ❌ Verificación temporal de scopes | **ELIMINAR** | Borrar - Ya no es necesario |
| `GenerateCompleteScopes.php` | ❌ Generación inicial de scopes | **ELIMINAR** | Borrar - Ya no es necesario |
| `GenerateFinalScopes.php` | ❌ Validación final de scopes | **ELIMINAR** | Borrar - Ya no es necesario |

## 📁 **DOCUMENTACIÓN MOVIDA**

### **De raíz → docs/service-account/**

| Archivo Original | Nueva Ubicación | Descripción |
|------------------|-----------------|-------------|
| `RESUMEN_SERVICE_ACCOUNT.md` | `docs/service-account/RESUMEN_SERVICE_ACCOUNT.md` | Resumen de configuración |
| `GUIA_DRIVE_ATTACHMENTS.md` | `docs/service-account/GUIA_DRIVE_ATTACHMENTS.md` | Guía de adjuntos Drive |
| `SOLUCION_DOMAIN_WIDE_DELEGATION.md` | `docs/service-account/SOLUCION_DOMAIN_WIDE_DELEGATION.md` | Solución de problemas |
| `CORRECCION_SCOPES.md` | `docs/service-account/CORRECCION_SCOPES.md` | Corrección de scopes |

## 🗑️ **LIMPIEZA RECOMENDADA**

### **Comandos para ejecutar:**

```bash
# Eliminar comandos de prueba innecesarios
rm app/Console/Commands/DiagnoseServiceAccount.php
rm app/Console/Commands/CheckScopes.php
rm app/Console/Commands/GenerateCompleteScopes.php
rm app/Console/Commands/GenerateFinalScopes.php
```

### **Mantener solo:**
- ✅ `TestServiceAccount.php` - Es funcional para el proyecto

## 💡 **MEJORES PRÁCTICAS APRENDIDAS**

### **❌ MALAS PRÁCTICAS COMETIDAS:**

1. **Archivos "final", "final_v2", "definitivo"** - Indica falta de control de versiones
2. **Múltiples comandos de prueba** - Crear archivos nuevos en lugar de usar Git
3. **Documentación dispersa** - No tener una estructura organizada
4. **Archivos temporales sin limpiar** - Ensuciar el repositorio

### **✅ BUENAS PRÁCTICAS A SEGUIR:**

1. **Usar Git para versiones** - `git commit`, `git branch`, `git tag`
2. **Estructura de carpetas clara** - `docs/`, `tests/`, separar por funcionalidad
3. **Limpiar archivos temporales** - No dejar archivos de desarrollo
4. **Un archivo por funcionalidad** - Editar en lugar de duplicar

### **🔧 MEJORES ALTERNATIVAS:**

| En lugar de... | Usar... |
|----------------|---------|
| `archivo_final.php` | `git tag v1.0.0` |
| `comando_v2.php` | `git commit -m "update command"` |
| `test_final_definitivo.php` | `git branch feature/testing` |
| Múltiples archivos MD | Carpeta `docs/` organizada |

## 🎯 **PLAN DE LIMPIEZA**

1. ✅ **Crear estructura docs/** - Completado
2. ✅ **Mover documentación** - Completado  
3. ⏳ **Eliminar comandos de prueba** - Pendiente
4. ⏳ **Actualizar .gitignore** - Pendiente
5. ⏳ **Documentar buenas prácticas** - En progreso

---

> **Mensaje para los creadores:** La tendencia a crear archivos "final", "v2", "definitivo" es una **anti-patrón** muy común entre desarrolladores que no utilizan adecuadamente Git. Es importante educar sobre el uso correcto de control de versiones, branches y tags para evitar repositorios desordenados.