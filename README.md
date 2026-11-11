# RCN Sistemas Web App

Plataforma web corporativa y sistema de gestión integral de **RCN Sistemas**, distribuidor asociado de software y soluciones CONTPAQi. La plataforma combina un portal comercial público (catálogo dinámico de productos, fichas técnicas, cotizaciones y promociones) con un completo sistema administrativo y de mesa de ayuda (tickets de soporte, órdenes de servicio en PDF, CRM de clientes, control de licencias, integración con Google Calendar y notificaciones Web Push PWA con Firebase Cloud Messaging).

---

## 🚀 Características Principales

### 🌐 Portal Comercial Público
- **Catálogo Dinámico de Productos:** Consulta, filtrado y detalle de soluciones CONTPAQi (Contabilidad, Nóminas, Bancos, Facturación, etc.).
- **Fichas Técnicas Oficiales:** Descarga y visualización de fichas técnicas oficiales en formato PDF.
- **Catálogo de Promociones:** Visualización dinámica de promociones y ofertas por tiempo limitado (`promociones.php`, `promotion_view.php`).
- **Módulo de Testimoniales:** Opiniones y valoraciones de clientes sobre el servicio recibido (`testimoniales.php`).
- **Formulario de Contacto Seguro:** Formulario interactivo con protección anti-spam basada en honeypot, validación de datos y envío de correos transaccionales vía PHPMailer (`contacto.php`).

### 🎫 Mesa de Ayuda y Tickets de Soporte
- **Gestión Integral de Tickets:** Registro, seguimiento y resolución de incidentes o servicios de soporte técnico (`admin/tickets.php`, `admin/ticket_create.php`, `admin/ticket_view.php`).
- **Ciclo de Vida y Estados:** Control de estados de ticket (Abierto, Agendado, En Proceso, Resuelto, Cerrado) con validación de transiciones e inmutabilidad en tickets cerrados.
- **Asignación y Agenda:** Asignación de tickets a técnicos/operadores, definición de fechas y horarios de citas de servicio.
- **Observaciones y Costos:** Historial cronológico de observaciones internas y cálculo de tarifas de soporte.
- **Órdenes de Servicio en PDF:** Generación y descarga automatizada de órdenes de servicio en formato PDF listas para firma e impresión mediante Dompdf (`admin/ticket_service_order_pdf.php`).

### 👥 CRM y Gestión de Clientes
- **Directorio de Clientes:** Administración de empresas y clientes con validación estricta de RFC y unicidad de teléfonos principales.
- **Contactos Asociados:** Registro de múltiples contactos secundarios por cliente (nombre, puesto, teléfono, correo) para una atención personalizada.
- **Búsqueda Predictiva en Tiempo Real:** Búsqueda dinámica "as you type" de clientes al momento de generar nuevos tickets o cotizaciones (`admin/customer_search.php`).

### 📜 Control de Licencias CONTPAQi y Cotizaciones
- **Inventario de Licencias:** Registro de licencias por número de serie, versión y tipo de licenciamiento (Tradicional o Anual).
- **Seguimiento de Renovaciones:** Alertas y monitoreo de fechas de vencimiento de licencias por cliente.
- **Cotizador de Licencias:** Generación y cálculo de cotizaciones comerciales con desglose de subtotal, IVA y retención de ISR.
- **Importador Masivo desde Excel:** Herramienta CLI para importar clientes y licencias desde hojas de cálculo (`.xlsx`) con modo de simulación (`--dry-run`).

### 📅 Integración con Google Calendar Compartido
- **Sincronización de Citas:** Sincronización automática de citas programadas en los tickets con un calendario común de Google Calendar a través de OAuth 2.0 y Google Calendar API v3.
- **Cifrado Seguro de Credenciales:** Almacenamiento seguro de tokens OAuth mediante cifrado simétrico robusto respaldado por libsodium (`paragonie/sodium_compat`).
- **Herramientas de Migración:** Script reanudable para migración y reconciliación de eventos de calendario por lotes.

### 📱 Progressive Web App (PWA) y Notificaciones Push (FCM)
- **Instalabilidad PWA:** Soporte como Progressive Web App instalable tanto en dispositivos móviles (Android/iOS) como en navegadores de escritorio (`manifest.webmanifest`, `service-worker.js`).
- **Notificaciones Web Push:** Notificaciones en tiempo real para asignaciones y actualizaciones de tickets utilizando Firebase Cloud Messaging (FCM) y Firebase Admin SDK HTTP v1 (`firebase-messaging-sw.js`, `js/push.js`).
- **Centro de Notificaciones en Vivo:** Panel de notificaciones en tiempo real vía Server-Sent Events (SSE) con alertas sonoras integradas (`admin/notifications.php`, `admin/notifications_stream.php`).

### 🛡️ Seguridad y Arquitectura
- **Patrón MVC Modular:** Estructura modular y desacoplada con Front Controller, enrutador amigable, controladores, servicios y repositorios bajo el estándar PSR-4.
- **Protección CSRF:** Tokens criptográficos CSRF en todas las solicitudes `POST`, `PUT` y `DELETE`.
- **Sesiones Persistentes Seguras:** Autenticación con "Recordarme" implementando hashing seguro y rotación de tokens contra ataques de repetición.
- **Aislamiento de Archivos Sensibles:** Reglas estrictas de Apache (`.htaccess`) que bloquean el acceso web directo a directorios privados (`storage/`, `database/`, `scripts/`, `admin/includes/`, etc.).
- **Diálogo Modal Accesible:** Modal de confirmación custom que sustituye los cuadros nativos del navegador (`confirm()`).

---

## 🛠️ Tecnologías Utilizadas

- **Lenguaje Backend:** PHP 8.0 o superior (Arquitectura MVC modular con autoloading PSR-4).
- **Base de Datos:** MySQL 5.7+ / MariaDB 10.3+ (Motor InnoDB, codificación `utf8mb4`).
- **Frontend & PWA:** HTML5 semántico, CSS3 corporativo/responsivo, JavaScript vanilla (ES6+), Service Workers, Web App Manifest.
- **Gestión de Paquetes:** Composer.
- **Librerías y SDKs Principales:**
  - `google/apiclient` & `google/auth`: Conexión con API de Google Calendar v3 mediante OAuth 2.0.
  - `dompdf/dompdf`: Generación de órdenes de servicio y documentos en PDF.
  - `phpmailer/phpmailer`: Envío de correos electrónicos transaccionales y notificaciones SMTP.
  - `paragonie/sodium_compat`: Cifrado y descifrado seguro de tokens y secretos.
  - `vlucas/phpdotenv`: Carga y gestión segura de variables de entorno.
  - `guzzlehttp/guzzle`: Cliente HTTP para llamadas a APIs externas.
  - `phpunit/phpunit`: Suite de pruebas unitarias y de integración.

---

## 📋 Prerrequisitos

Para ejecutar el proyecto en un entorno de desarrollo local se requiere:

- Servidor local con soporte Apache y PHP (ej. **MAMP**, **XAMPP**, **Laragon** o **Docker**).
- **PHP 8.0+** con las siguientes extensiones habilitadas:
  - `mysqli` y `pdo_mysql`
  - `curl`
  - `json`
  - `mbstring`
  - `openssl`
  - `sodium`
- **MySQL 5.7+** o **MariaDB 10.3+**.
- [Composer](https://getcomposer.org/) instalado globalmente.
- Módulo `mod_rewrite` activo en Apache para el soporte de URLs amigables (`.htaccess`).

---

## ⚙️ Instalación y Configuración

### 1. Clonar el repositorio
Ubícate en la raíz del servidor web local (`htdocs` o `www`) y clona el proyecto:
```bash
git clone https://github.com/AlvaroXC/rcn-sistemas-web-ap.git
cd rcn-sistemas-web-ap
```

### 2. Instalar dependencias
Descarga e instala las dependencias de Composer:
```bash
composer install
```

### 3. Configurar variables de entorno
Copia la plantilla de entorno `.env.example` como `.env`:
```bash
cp .env.example .env
```

Edita `.env` con los parámetros correspondientes a tu entorno:

```env
# Conexión a Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=rcn_sistemas
DB_USER=root
DB_PASS=root

# Configuración de Aplicación
APP_URL=http://localhost/new_web_page
APP_ENCRYPTION_KEY=clave_secreta_de_32_o_mas_caracteres_aleatorios
APP_CURRENCY=MXN
APP_TIMEZONE=America/Merida

# Datos de la Empresa e Impuestos
COMPANY_NAME="RCN Sistemas"
COMPANY_PHONE="+52 (999) 9942427"
COMPANY_EMAIL=contacto@rcnsistemas.com
TAX_IVA_RATE=0.16
TAX_ISR_WITHHOLDING_RATE=0.0125

# Notificaciones Push con Firebase (Opcional en desarrollo)
FIREBASE_ENABLED=false
FIREBASE_PROJECT_ID=
FIREBASE_API_KEY=
FIREBASE_AUTH_DOMAIN=
FIREBASE_MESSAGING_SENDER_ID=
FIREBASE_APP_ID=
FIREBASE_VAPID_PUBLIC_KEY=
FIREBASE_SERVICE_ACCOUNT_PATH=storage/credentials/firebase-service-account.json

# Google Calendar (Opcional en desarrollo)
GOOGLE_CALENDAR_CLIENT_ID=
GOOGLE_CALENDAR_CLIENT_SECRET=
GOOGLE_CALENDAR_REDIRECT_URI=http://localhost/new_web_page/admin/google-calendar/callback
GOOGLE_CALENDAR_ID=primary
GOOGLE_CALENDAR_SCOPES=https://www.googleapis.com/auth/calendar.events

# Correo Transaccional SMTP
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_USER=null
SMTP_PASS=null
SMTP_SECURE=tls
```

> [!IMPORTANT]
> `APP_ENCRYPTION_KEY` es obligatoria para el cifrado de credenciales de Google Calendar. Debe ser una cadena aleatoria y persistente; cambiarla invalidará los tokens previamente almacenados.

### 4. Configurar la Base de Datos y Migraciones
1. Crea una base de datos en MySQL (ej. `rcn_sistemas`) con juego de caracteres `utf8mb4` y cotejamiento `utf8mb4_unicode_ci`.
2. Ejecuta las migraciones versionadas desde la terminal:
   ```bash
   php scripts/migrate.php
   ```
   *El script aplicará secuencialmente los archivos en `database/migrations/` y los registrará en la tabla `app_migrations`.*
3. (Opcional) Inicializa los productos y catálogo base mediante el sembrador:
   ```bash
   php scripts/seed.php
   ```

### 5. Iniciar la aplicación
- **Con MAMP / XAMPP:** Accede directamente desde tu navegador a la ruta configurada (ej. `http://localhost/new_web_page` o `http://localhost:8888/new_web_page`).
- **Con el servidor integrado de PHP:**
  ```bash
  php -S localhost:8000
  ```
  Accede a `http://localhost:8000`.

---

## 🧰 Scripts de Mantenimiento y CLI

La carpeta `scripts/` contiene herramientas de mantenimiento por línea de comandos:

- **`php scripts/migrate.php`**: Aplica migraciones SQL pendientes en la base de datos de forma secuencial y atómica.
- **`php scripts/seed.php`**: Carga el catálogo base de productos CONTPAQi y beneficios desde `productos.json`.
- **`php scripts/import_licenses.php`**: Importa masivamente clientes y licencias desde el archivo de Excel de renovaciones CONTPAQi (`.xlsx`).
  ```bash
  # Modo simulación (no modifica la base de datos):
  php scripts/import_licenses.php "/ruta/Renovaciones.xlsx" --dry-run

  # Modo importación definitiva:
  php scripts/import_licenses.php "/ruta/Renovaciones.xlsx"
  ```
- **`php scripts/migrate_google_calendar_events.php`**: Reconcilia y traslada citas de tickets hacia el calendario compartido de Google:
  ```bash
  # Ver reporte de eventos pendientes:
  php scripts/migrate_google_calendar_events.php --report-only

  # Procesar por lotes (ej. 100 tickets por lote):
  php scripts/migrate_google_calendar_events.php --limit=100
  ```
- **`php scripts/fix_duplicate_customer_phones.php`**: Herramienta de auditoría para diagnosticar y normalizar números de teléfono duplicados.
- **`bash scripts/backup.sh`**: Genera un respaldo comprimido (`.sql` / `.sql.gz`) de la base de datos antes de realizar cambios estructurales o despliegues.

---

## 🧪 Pruebas Automatizadas y Calidad

El proyecto cuenta con una amplia suite de pruebas unitarias y de integración construida con **PHPUnit 11**:

```bash
# Ejecutar todas las pruebas unitarias e integración
composer test

# O directamente mediante el binario de PHPUnit
vendor/bin/phpunit
```

Para validar la sintaxis de todos los archivos PHP del proyecto:
```bash
composer lint
```

Para ejecutar pruebas locales aisladas de base de datos, copia la plantilla de testing:
```bash
cp .env.testing.example .env.testing
```

> [!NOTE]
> Las validaciones y pruebas automatizadas se ejecutan en cada cambio y Pull Request a través de **GitHub Actions** (`.github/workflows/tests.yml`).

---

## 📂 Estructura del Proyecto

```text
├── admin/                         # Vistas y puntos de entrada del panel administrativo
│   ├── includes/                  # Componentes y plantillas del admin (bloqueado por .htaccess)
│   ├── customers.php              # Directorio y gestión de clientes
│   ├── customer_contacts.php      # Administración de contactos secundarios
│   ├── tickets.php                # Listado y control de tickets
│   ├── ticket_create.php          # Creación y agendamiento de tickets
│   ├── ticket_view.php            # Detalle, workflow y observaciones del ticket
│   ├── ticket_service_order_pdf.php # Generador de orden de servicio PDF
│   ├── licenses.php               # Inventario de licencias CONTPAQi
│   ├── license_quotations.php     # Cotizador de licencias
│   ├── notifications.php          # Centro de notificaciones
│   ├── notifications_stream.php   # Stream SSE de notificaciones en tiempo real
│   ├── products.php               # Gestión de productos
│   ├── promotions.php             # Gestión de promociones
│   └── users.php                  # Gestión de usuarios y operadores
├── app/                           # Núcleo MVC modular (PSR-4: RcnSistemas\)
│   ├── Bootstrap/                 # Arranque de la app y definición de rutas (routes.php)
│   ├── Controllers/               # Controladores separados por área (Admin y Public)
│   ├── Core/                      # Router, Request, Response, Config, DB, CSRF, Sesiones
│   ├── Middleware/                # Middlewares de autenticación y verificación de roles
│   ├── Models/                    # Entidades de dominio
│   ├── Repositories/              # Abstracción y acceso a base de datos (MySQLi)
│   ├── Services/                  # Lógica de negocio (Tickets, Google, FCM, Licencias, etc.)
│   └── Views/                     # Plantillas PHP renderizadas por ViewRenderer
├── config/                        # Archivos de configuración centralizada
│   ├── app.php                    # Parámetros generales y zona horaria
│   ├── database.php               # Credenciales de conexión MySQL
│   ├── firebase.php               # Credenciales y opciones de FCM
│   ├── google.php                 # Opciones OAuth y API de Google Calendar
│   ├── mail.php                   # Configuración SMTP
│   ├── billing.php                # Tasas de impuestos e IVA
│   └── company.php                # Información comercial de la empresa
├── css/                           # Hojas de estilo CSS corporativas y responsivas
├── database/                      # Esquemas y control de base de datos
│   └── migrations/                # Migraciones SQL versionadas (001 a 015)
├── docs/                          # Documentación técnica extendida
│   ├── google-calendar.md         # Guía de configuración OAuth de Google Calendar
│   ├── frontend-assets.md         # Especificación de recursos visuales y auditivos
│   └── guia-completa-desarrollador-php.md # Guía para desarrolladores
├── fichas_producto/               # Fichas técnicas oficiales de CONTPAQi en PDF
├── js/                            # Controladores JavaScript, PWA y Web Push
│   ├── admin.js                   # Lógica de interacción en el panel administrativo
│   ├── pwa.js                     # Registro de Service Worker PWA
│   ├── push.js                    # Suscripción y recepción de notificaciones Web Push
│   ├── notifications.js           # Manejador del stream SSE y alertas sonoras
│   └── confirm-dialog.js          # Modal accesible para confirmaciones
├── scripts/                       # Herramientas CLI de mantenimiento y migración
├── storage/                       # Almacenamiento protegido (credenciales de Google y Firebase)
├── tests/                         # Suite de pruebas automatizadas con PHPUnit
│   ├── Unit/                      # Pruebas unitarias de servicios, core y modelos
│   └── Integration/               # Pruebas de integración de base de datos y migraciones
├── uploads/                       # Directorio de imágenes y archivos subidos
├── firebase-messaging-sw.js       # Service Worker para notificaciones push en segundo plano
├── manifest.webmanifest           # Manifiesto de instalación PWA
├── service-worker.js              # Service Worker principal de caché y offline PWA
├── phpunit.xml.dist               # Configuración estandarizada de PHPUnit
└── composer.json                  # Definición de paquetes y scripts de Composer
```

---

## 🔐 Acceso al Administrador y PWA

1. Para acceder al panel administrativo, dirígete a:
   ```text
   http://localhost/new_web_page/admin
   ```
2. Inicia sesión con tus credenciales de administrador o técnico asignado.
3. **Instalación como PWA:** Al navegar desde un navegador compatible (Google Chrome, Edge, Safari móvil), aparecerá el botón para **Instalar Aplicación** en el escritorio o pantalla de inicio.
4. **Notificaciones Push:** Tras iniciar sesión, concede el permiso de notificaciones cuando el navegador lo solicite para recibir alertas de tickets y asignaciones en tiempo real.
