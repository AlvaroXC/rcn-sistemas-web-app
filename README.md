# RCN Sistemas Web App

Plataforma web corporativa de **RCN Sistemas**, distribuidor asociado de sistemas CONTPAQi. El sitio incluye un catálogo dinámico de productos, fichas técnicas en formato PDF, sistema de testimoniales, catálogo de promociones y un panel de administración para la gestión de contenidos.

## 🚀 Características

- **Catálogo de Productos:** Consulta y filtrado de soluciones CONTPAQi (Contabilidad, Nóminas, Bancos, etc.) con sus respectivas fichas técnicas en PDF.
- **Detalle de Productos y Promociones:** Páginas de visualización dinámica (`product_view.php`, `promotion_view.php`).
- **Sección de Testimoniales:** Módulo interactivo con opiniones de clientes.
- **Panel de Administración (`/admin`):**
  - Autenticación segura de administradores.
  - CRUD (Crear, Leer, Actualizar, Eliminar) de productos y promociones.
  - Carga de imágenes para promociones y productos.
- **Configuración Segura:** Conexión a base de datos parametrizada mediante variables de entorno (`.env`).

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 8.x
- **Gestor de Dependencias:** Composer
- **Base de Datos:** MySQL / MariaDB
- **Frontend:** HTML5, Vanilla CSS, JavaScript (ES6)

## 📋 Prerrequisitos

Para ejecutar este proyecto en tu entorno local necesitas:

- Servidor local compatible con PHP (ej. **MAMP**, **XAMPP** o **Laragon**).
- PHP 8.0 o superior.
- MySQL 5.7 o superior.
- [Composer](https://getcomposer.org/) instalado.

## ⚙️ Instalación y Configuración

Sigue estos pasos para configurar el entorno de desarrollo local:

### 1. Clonar el repositorio
Si no lo has hecho, clona el proyecto a tu directorio del servidor local (ej. `/htdocs` o `/www`):
```bash
git clone https://github.com/AlvaroXC/rcn-sistemas-web-ap.git
cd rcn-sistemas-web-ap
```

### 2. Instalar dependencias
Instala los paquetes necesarios definidos en `composer.json`:
```bash
composer install
```

### 3. Configurar variables de entorno
Crea una copia del archivo `.env.example` y renómbralo a `.env`:
```bash
cp .env.example .env
```
Abre el archivo `.env` y edita las credenciales de conexión a tu base de datos local:
```env
DB_HOST=localhost
DB_NAME=rcn_sistemas
DB_USER=root
DB_PASS=tu_contraseña
```

### 4. Configurar la Base de Datos
1. Crea una base de datos vacía en tu gestor de MySQL (ej. phpMyAdmin) llamada `rcn_sistemas`.
2. Importa el esquema inicial de la base de datos:
   ```bash
   mysql -u tu_usuario -p rcnsiste_axsoluciones < database/migrations/001_initial_schema.sql
   mysql -u tu_usuario -p rcnsiste_axsoluciones < database/migrations/002_support_and_admin_extensions.sql
   ```
3. (Opcional) Si deseas inicializar productos de demostración, ejecuta el archivo PHP sembrador desde tu navegador o terminal:
   ```bash
   php seed.php
   ```

### 5. Iniciar la aplicación
Si estás utilizando MAMP/XAMPP, simplemente accede a la URL local (ej. `http://localhost/new_web_page` o `http://localhost:8888/new_web_page`).

También puedes levantar el servidor embebido de PHP directamente desde la terminal:
```bash
php -S localhost:8000
```
Y abre `http://localhost:8000` en tu navegador.

### Ejecutar pruebas

```bash
vendor/bin/phpunit
```

### Ejecutar migraciones

```bash
php scripts/migrate.php
```

Las migraciones se registran en `app_migrations` y no se repiten. Antes de modificar una base existente, crea un respaldo.

## 📂 Estructura del Proyecto

```text
├── admin/                     # Panel de administración (CRUD de productos y promociones)
├── css/                       # Archivos de estilos CSS (incluye estilos corporativos)
├── fichas_producto/           # PDFs de fichas técnicas oficiales de CONTPAQi
├── includes/                  # Componentes reutilizables (cabecera, pie de página, DB)
├── js/                        # Controladores de comportamiento JavaScript
├── src/                       # Recursos estáticos (imágenes oficiales, logos SVG)
├── uploads/                   # Carpeta de subidas para imágenes del admin
├── .env.example               # Plantilla de configuración de variables de entorno
└── database.sql               # Esquema de la Base de Datos
```

## 🔐 Acceso al Administrador
Para ingresar al panel administrativo, dirígete a `http://localhost/new_web_page/admin` e inicia sesión con las credenciales correspondientes.
