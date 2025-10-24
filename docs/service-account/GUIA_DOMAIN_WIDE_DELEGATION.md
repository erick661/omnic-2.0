# PASO A PASO: HABILITAR SERVICE ACCOUNT CON DOMAIN-WIDE DELEGATION

## 🚀 FASE 1: GOOGLE CLOUD CONSOLE SETUP

### 1.1 Acceder a Google Cloud Console
1. **Ir a**: https://console.cloud.google.com
2. **Iniciar sesión** con cuenta de administrador: `admin@orproverificaciones.cl`
3. **Verificar proyecto** o crear uno nuevo si es necesario

### 1.2 Seleccionar/Crear Proyecto
```
Opción A - Si ya tienes proyecto:
- Selector de proyecto (parte superior) → Seleccionar proyecto existente

Opción B - Crear nuevo proyecto:
- NEW PROJECT
- Nombre: "Omnic Email System"  
- Organization: orproverificaciones.cl
- Location: Sin organización (si no aparece tu dominio)
```

### 1.3 Habilitar APIs Requeridas
```
Navigation Menu → APIs & Services → Library

BUSCAR Y HABILITAR las siguientes APIs:
✅ Gmail API
✅ Admin SDK API  
✅ Cloud Resource Manager API
✅ IAM Service Account Credentials API

Para cada API:
1. Click en la API
2. Click "ENABLE"  
3. Esperar confirmación
```

## 🔧 FASE 2: CREAR SERVICE ACCOUNT

### 2.1 Navegar a Service Accounts
```
Navigation Menu → IAM & Admin → Service Accounts
```

### 2.2 Crear Service Account
```
1. Click "CREATE SERVICE ACCOUNT"

2. Service account details:
   - Service account name: omnic-email-manager
   - Service account ID: omnic-email-manager (auto-generado)  
   - Description: Service Account para gestión automatizada de emails en Omnic

3. Click "CREATE AND CONTINUE"

4. Grant access (OPCIONAL - Skip por ahora):
   - Click "CONTINUE" sin seleccionar roles

5. Grant users access (OPCIONAL - Skip):
   - Click "DONE"
```

### 2.3 Generar Clave JSON
```
1. En la lista de Service Accounts, click en "omnic-email-manager"

2. Tab "KEYS"

3. Click "ADD KEY" → "Create new key"

4. Seleccionar "JSON"

5. Click "CREATE" 

6. Se descarga automáticamente: 
   Archivo: omnic-email-manager-[project-id]-[hash].json
   
7. IMPORTANTE: Guardar este archivo de forma segura
   Renombrar a: google-service-account.json
```

## 🌐 FASE 3: HABILITAR DOMAIN-WIDE DELEGATION

### 3.1 Configurar en Service Account
```
1. En Google Cloud Console → IAM & Admin → Service Accounts

2. Click en "omnic-email-manager" 

3. Tab "DETAILS"

4. Sección "Advanced settings" → Click "SHOW DOMAIN-WIDE DELEGATION"

5. ✅ Marcar "Enable Google Workspace Domain-wide Delegation"

6. Opcional - Product name: "Omnic Email System"

7. Click "SAVE"

8. 📋 COPIAR EL CLIENT ID que aparece (formato: números-hash.apps.googleusercontent.com)
   ESTE CLIENT ID ES CRUCIAL PARA EL SIGUIENTE PASO
```

### 3.2 Autorizar en Google Admin Console  
```
1. Ir a: https://admin.google.com

2. Iniciar sesión como admin@orproverificaciones.cl

3. Navegar: Security → Access and data control → API controls

4. Click en "DOMAIN-WIDE DELEGATION"

5. Click "ADD NEW" o "Manage Domain Wide Delegation"

6. Completar formulario:
   Client ID: [PEGAR EL CLIENT ID copiado del paso anterior]
   
   OAuth scopes (COPIAR EXACTO - separado por comas):
   https://www.googleapis.com/auth/gmail.readonly,https://www.googleapis.com/auth/gmail.send,https://www.googleapis.com/auth/gmail.modify,https://www.googleapis.com/auth/admin.directory.group,https://www.googleapis.com/auth/admin.directory.user.readonly

7. Click "AUTHORIZE"
```

## 📁 FASE 4: CONFIGURAR EN SERVIDOR

### 4.1 Subir archivo de credenciales
```bash
# Conectar al servidor y crear directorio seguro
mkdir -p /var/www/omnic/storage/app/google-credentials
chmod 700 /var/www/omnic/storage/app/google-credentials

# Subir el archivo JSON descargado
# Renombrar a: google-service-account.json
# Ubicar en: /var/www/omnic/storage/app/google-credentials/google-service-account.json
# Permisos: chmod 600 google-service-account.json
```

### 4.2 Configurar variables de entorno
```bash
# Añadir a .env
GMAIL_AUTH_MODE=service_account
GOOGLE_SERVICE_ACCOUNT_PATH=/var/www/omnic/storage/app/google-credentials/google-service-account.json
GOOGLE_WORKSPACE_ADMIN_EMAIL=admin@orproverificaciones.cl
GOOGLE_WORKSPACE_DOMAIN=orproverificaciones.cl
```

## 🧪 FASE 5: CREAR COMANDO DE PRUEBA

### 5.1 Comando de verificación
```bash
php artisan make:command TestServiceAccount
```

### 5.2 Implementación del comando de prueba
[Ver implementación en siguiente archivo]

## ⚠️ PUNTOS CRÍTICOS A VERIFICAR:

1. **Client ID correcto**: Debe coincidir exactamente entre Google Cloud y Admin Console
2. **Scopes exactos**: Un error de escritura impide el funcionamiento  
3. **Permisos de archivo**: El JSON debe ser legible solo por la aplicación
4. **Email de admin**: Debe tener permisos de super administrador en Workspace
5. **APIs habilitadas**: Todas las APIs deben estar activas antes de usar

## 🔍 SEÑALES DE ÉXITO:

✅ Service Account creado sin errores
✅ Domain-wide delegation habilitado  
✅ Client ID autorizado en Admin Console
✅ Archivo JSON descargado y ubicado correctamente
✅ Variables de entorno configuradas

## � FASE 6: CONFIGURAR POLÍTICAS DE SEGURIDAD ORGANIZACIONALES

### 6.1 Acceder a Políticas de Organización

**Via Google Admin Console:**
```
1. Ir a: https://admin.google.com
2. Iniciar sesión como admin@orproverificaciones.cl  
3. Navegar: Security → Access and data control → API controls
4. Click en "App access control"
5. Buscar sección "Third-party app access"
```

**Via Google Cloud Console (Método Directo):**
```
1. Ir a: https://console.cloud.google.com
2. Navigation Menu → IAM & Admin → Organization Policies  
3. Si no aparece, ir a: Resource Manager → Settings → Organization Policies
```

### 6.2 Políticas a Configurar

**Políticas que pueden bloquear Service Accounts:**

1. **Domain Restricted Sharing (constraints/iam.allowedPolicyMemberDomains)**
   ```
   Descripción: Restringe qué dominios pueden tener acceso
   Acción: Añadir excepción para Service Accounts internos
   ```

2. **Service Account Creation (constraints/iam.disableServiceAccountCreation)**  
   ```
   Descripción: Bloquea creación de Service Accounts
   Acción: Deshabilitar temporalmente o crear excepción
   ```

3. **Service Account Key Creation (constraints/iam.disableServiceAccountKeyCreation)**
   ```
   Descripción: Bloquea creación de claves de Service Account
   Acción: Deshabilitar para el proyecto Omnic
   ```

### 6.3 Pasos para Modificar Políticas

**Para cada política restrictiva:**

```
1. En Organization Policies, buscar la constraint mencionada

2. Click en la política 

3. Click "EDIT POLICY"

4. Opciones disponibles:
   A) Set policy: Not enforced (Deshabilita completamente)
   B) Customize policy: Crear excepciones específicas  
   C) Inherit parent policy: Usar configuración superior

5. Recomendación para Omnic:
   - Seleccionar "Customize policy"
   - Añadir excepción para tu proyecto específico
   - Scope: projects/[TU-PROJECT-ID]

6. Click "SET POLICY"
```

### 6.4 Verificación de Roles de Usuario

**Verificar que tienes los roles necesarios:**

```
1. En Google Admin Console → Admin roles
2. Verificar que admin@orproverificaciones.cl tiene:
   ✅ Super Admin (recomendado)
   O alternativamente:
   ✅ Security Admin  
   ✅ Groups Admin
   ✅ User Management Admin

3. Si no tienes Super Admin, solicitar a otro administrador que:
   - Te otorgue el rol "Administrador de políticas de la organización"
   - O configure las políticas directamente
```

## � SOLUCIÓN A PROBLEMA DE PERMISOS DE POLÍTICAS

### ERROR COMÚN: "Organization Policy Administrator" requerido

**Problema:** Aunque seas dueño del dominio, necesitas el rol específico `roles/orgpolicy.policyAdmin`

### SOLUCIÓN 1: Auto-asignarte el rol (Google Cloud Console)

```
1. Ir a: https://console.cloud.google.com/iam-admin/iam

2. Buscar tu cuenta (admin@orproverificaciones.cl)

3. Click en el ícono ✏️ (Edit principal)

4. Click "ADD ANOTHER ROLE"

5. Buscar y seleccionar: "Organization Policy Administrator"
   (roles/orgpolicy.policyAdmin)

6. Click "SAVE"
```

### SOLUCIÓN 2: Via gcloud CLI (Más rápido)

```bash
# Si tienes gcloud instalado localmente
gcloud organizations add-iam-policy-binding [ORG_ID] \
    --member="user:admin@orproverificaciones.cl" \
    --role="roles/orgpolicy.policyAdmin"

# Para encontrar ORG_ID:
gcloud organizations list
```

### SOLUCIÓN 3: Verificar permisos actuales

```bash
# Verificar qué permisos tienes actualmente
gcloud projects get-iam-policy [PROJECT_ID] \
    --flatten="bindings[].members" \
    --format="table(bindings.role)" \
    --filter="bindings.members:admin@orproverificaciones.cl"
```

### SOLUCIÓN 4: Usar Super Admin para asignarte el rol

```
1. Ir a: https://admin.google.com/ac/roles

2. Buscar "Admin roles" 

3. Click en "Super Admin" o crear rol personalizado

4. Añadir admin@orproverificaciones.cl con permisos:
   - Organization Policy Administrator
   - Security Admin (si no lo tienes)

5. Aplicar cambios
```

## 🛡️ ALTERNATIVAS SI NO PUEDES CAMBIAR POLÍTICAS

### Alternativa 1: OAuth con Application Default Credentials
```bash
# Usar credenciales de aplicación predeterminadas
gcloud auth application-default login --impersonate-service-account=omnic-email-manager@tu-proyecto.iam.gserviceaccount.com
```

### Alternativa 2: Workload Identity (Para Kubernetes/GKE)
```yaml
# Si estás usando Kubernetes
apiVersion: v1
kind: ServiceAccount
metadata:
  annotations:
    iam.gke.io/gcp-service-account: omnic-email-manager@tu-proyecto.iam.gserviceaccount.com
```

### Alternativa 3: OAuth con impersonación limitada
```php
// Mantener OAuth pero con scope reducido
$scopes = [
    'https://www.googleapis.com/auth/gmail.readonly',
    'https://www.googleapis.com/auth/gmail.send'
    // Remover admin.directory scopes si no son esenciales
];
```

## �🚨 ERRORES COMUNES:

❌ "Error 403: Forbidden" → Scopes no autorizados en Admin Console
❌ "Error 400: Invalid grant" → Client ID incorrecto o no coincide  
❌ "File not found" → Ruta incorrecta del archivo JSON
❌ "Permission denied" → Usuario no tiene permisos de super admin
❌ "Policy violation" → Políticas organizacionales bloqueando Service Account
❌ "Domain restriction" → Dominio no autorizado en políticas