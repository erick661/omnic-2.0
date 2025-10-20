# OMNIC 2.0 - Sistema de Gestión de Emails

Sistema moderno de gestión de correos electrónicos desarrollado con Laravel 11, Livewire Volt y PostgreSQL. Reemplaza el sistema legacy PHP con una arquitectura robusta y escalable.

## 🚀 Características

- **Laravel 11** con Livewire Volt para componentes reactivos
- **PostgreSQL 18** como base de datos principal
- **Gmail API** para importación automática de correos
- **Sistema de usuarios** con roles supervisor/ejecutivo
- **Auto-asignación** inteligente basada en códigos de referencia
- **Mary UI** para interfaz moderna con TailwindCSS
- **Brevo API** para envío de emails masivos

## 📋 Requisitos del Sistema

### Fedora 42 / RHEL-based

```bash
# Actualizar sistema
sudo dnf update -y

# Instalar dependencias básicas
sudo dnf install -y git curl wget unzip vim

# Instalar PHP 8.3 y extensiones
sudo dnf install -y php php-cli php-fpm php-json php-common php-mysql \
    php-zip php-gd php-mbstring php-curl php-xml php-pear php-bcmath \
    php-json php-pgsql php-intl php-soap php-xmlrpc php-opcache \
    php-redis php-memcached

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Instalar Node.js y npm
sudo dnf install -y nodejs npm

# Instalar PostgreSQL 18
sudo dnf install -y postgresql postgresql-server postgresql-contrib
sudo postgresql-setup --initdb
sudo systemctl enable postgresql
sudo systemctl start postgresql

# Configurar usuario PostgreSQL
sudo -u postgres psql -c "CREATE USER omnic WITH PASSWORD 'omnic_password';"
sudo -u postgres psql -c "CREATE DATABASE omnic OWNER omnic;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE omnic TO omnic;"

# Instalar Redis (opcional, para cache)
sudo dnf install -y redis
sudo systemctl enable redis
sudo systemctl start redis

# Instalar Google Cloud CLI (para Gmail API)
curl https://sdk.cloud.google.com | bash
exec -l $SHELL
gcloud init
```

### Ubuntu/Debian (alternativo)

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias básicas
sudo apt install -y git curl wget unzip vim software-properties-common

# Agregar repositorio PHP
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Instalar PHP 8.3 y extensiones
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-common \
    php8.3-mysql php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl \
    php8.3-xml php8.3-bcmath php8.3-pgsql php8.3-intl php8.3-soap \
    php8.3-xmlrpc php8.3-opcache php8.3-redis

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs

# Instalar PostgreSQL
sudo apt install -y postgresql postgresql-contrib
sudo systemctl enable postgresql
sudo systemctl start postgresql

# Configurar usuario PostgreSQL
sudo -u postgres psql -c "CREATE USER omnic WITH PASSWORD 'omnic_password';"
sudo -u postgres psql -c "CREATE DATABASE omnic OWNER omnic;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE omnic TO omnic;"
```

## 🛠️ Instalación del Proyecto

### 1. Clonar el Repositorio

```bash
git clone https://github.com/TU_USUARIO/omnic-2.0.git
cd omnic-2.0
```

### 2. Instalar Dependencias

```bash
# Dependencias PHP
composer install

# Dependencias Node.js
npm install
```

### 3. Configurar Entorno

```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 4. Configurar Base de Datos

Editar el archivo `.env`:

```env
# Configuración de la aplicación
APP_NAME="OMNIC 2.0"
APP_ENV=local
APP_KEY=base64:TU_CLAVE_GENERADA
APP_DEBUG=true
APP_TIMEZONE=America/Santiago
APP_URL=http://localhost:8000

# Base de datos PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=omnic
DB_USERNAME=omnic
DB_PASSWORD=omnic_password

# Gmail API (para desarrollo usar mock)
GMAIL_USE_MOCK=true
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
GOOGLE_APPLICATION_CREDENTIALS=

# Brevo API (opcional)
BREVO_API_KEY=
BREVO_SENDER_EMAIL=noreply@tudominio.cl
BREVO_SENDER_NAME="OMNIC System"

# Cache y sesiones
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Mail (usar para notificaciones del sistema)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Ejecutar Migraciones y Seeders

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders para datos de prueba
php artisan db:seed

# Verificar instalación
php artisan tinker --execute="echo 'Usuarios: ' . App\Models\User::count() . PHP_EOL; echo 'Emails: ' . App\Models\ImportedEmail::count() . PHP_EOL;"
```

## 🧪 Configuración para Desarrollo

### Configurar Mock de Gmail

El sistema incluye un servicio mock para desarrollo que no requiere autenticación real con Gmail:

```bash
# Configurar mock de Gmail
php artisan gmail:setup-test-auth

# Probar autenticación mock
php artisan gmail:test-auth --mock

# Importar emails de prueba
php artisan emails:import --mock
```

### Compilar Assets

```bash
# Desarrollo (watch mode)
npm run dev

# Producción
npm run build
```

### Levantar Servidor

```bash
# Servidor de desarrollo Laravel
php artisan serve

# Acceder a: http://localhost:8000
```

## 📁 Estructura del Proyecto

```
omnic-2.0/
├── app/
│   ├── Console/Commands/          # Comandos Artisan
│   │   ├── ImportEmails.php       # Importar emails desde Gmail
│   │   ├── SetupGmailTestAuth.php # Configurar mock Gmail
│   │   └── TestGmailAuth.php      # Probar autenticación
│   ├── Models/                    # Modelos Eloquent
│   │   ├── User.php               # Usuarios del sistema
│   │   ├── GmailGroup.php         # Grupos de Gmail
│   │   ├── ImportedEmail.php      # Emails importados
│   │   ├── ReferenceCode.php      # Códigos de referencia
│   │   └── SystemConfig.php       # Configuración del sistema
│   └── Services/                  # Servicios de negocio
│       ├── GmailServiceManager.php # Gestor de servicios Gmail
│       ├── GmailService.php       # Servicio Gmail real
│       └── MockGmailService.php   # Servicio Gmail mock
├── database/
│   ├── migrations/                # Migraciones de BD
│   └── seeders/                   # Datos de prueba
├── resources/
│   └── views/
│       └── livewire/              # Componentes Livewire Volt
└── routes/
    └── web.php                    # Rutas web
```

## 🔧 Comandos Útiles

### Base de Datos

```bash
# Refrescar migraciones y seeders
php artisan migrate:fresh --seed

# Ver estado de migraciones
php artisan migrate:status

# Rollback de migraciones
php artisan migrate:rollback
```

### Gmail API

```bash
# Configurar mock para desarrollo
php artisan gmail:setup-test-auth

# Probar autenticación (usar --mock en desarrollo)
php artisan gmail:test-auth --mock

# Importar emails (usar --mock en desarrollo)
php artisan emails:import --mock

# Para producción (sin --mock)
php artisan gmail:test-auth
php artisan emails:import
```

### Desarrollo

```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generar clave nueva
php artisan key:generate

# Verificar configuración
php artisan about
```

## 🔐 Gmail API - Configuración Producción

### Opción 1: OAuth 2.0 (Recomendado para desarrollo)

1. Ir a [Google Cloud Console](https://console.cloud.google.com/)
2. Crear/seleccionar proyecto
3. Habilitar Gmail API
4. Crear credenciales OAuth 2.0
5. Configurar dominio autorizado
6. Actualizar `.env`:

```env
GMAIL_USE_MOCK=false
GOOGLE_CLIENT_ID=tu_client_id
GOOGLE_CLIENT_SECRET=tu_client_secret
GOOGLE_REDIRECT_URI=http://tudominio.cl/auth/google/callback
```

### Opción 2: Service Account (Producción)

1. Crear Service Account en Google Cloud
2. Habilitar Domain-wide Delegation
3. Descargar archivo JSON de credenciales
4. Configurar en el dominio de Google Workspace
5. Actualizar `.env`:

```env
GMAIL_USE_MOCK=false
GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json
```

## 🚀 Despliegue

### Producción

```bash
# Optimizar para producción
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build

# Configurar permisos
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Variables de Entorno Producción

```env
APP_ENV=production
APP_DEBUG=false
GMAIL_USE_MOCK=false
# ... resto de configuración
```

## 🔍 Solución de Problemas

### Error de Permisos

```bash
sudo chown -R $USER:www-data storage
sudo chown -R $USER:www-data bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Error PostgreSQL

```bash
# Verificar servicio
sudo systemctl status postgresql

# Reiniciar servicio
sudo systemctl restart postgresql

# Verificar conexión
psql -h localhost -U omnic -d omnic
```

### Error Gmail API

```bash
# Verificar configuración mock
php artisan gmail:test-auth --mock

# Ver logs
tail -f storage/logs/laravel.log
```

## 📚 Documentación Adicional

- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Livewire Volt Documentation](https://livewire.laravel.com/docs/volt)
- [Gmail API Documentation](https://developers.google.com/gmail/api)
- [Mary UI Components](https://mary-ui.com/)

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o preguntas:
- Crear issue en GitHub
- Email: desarrollo@tudominio.cl

---

**¡Sistema listo para desarrollo y producción! 🎉**
