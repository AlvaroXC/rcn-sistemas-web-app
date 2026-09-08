# Plan de migración a MVC modular

## 1. Propósito

Este documento define el plan para reorganizar RCN Sistemas Web App con una arquitectura MVC modular.

El objetivo no es cambiar la funcionalidad del sistema, sino separar responsabilidades para que el código sea más fácil de entender, probar, mantener y ampliar.

La migración debe conservar el comportamiento actual del sitio público y del panel administrativo durante todo el proceso.

## 2. Alcance actual

El sistema tiene dos áreas principales:

- Sitio público: inicio, productos, promociones, testimoniales y contacto.
- Panel administrativo: autenticación, usuarios, productos, promociones, clientes, tickets, asignaciones, notificaciones y generación de órdenes de servicio en PDF.

El flujo de contacto es una parte central del sistema:

1. El visitante envía sus datos y su consulta.
2. Se validan los datos y el archivo adjunto.
3. Se crea o actualiza un cliente.
4. Se crea un ticket relacionado con el contacto, producto o promoción.
5. Se envía una notificación por correo.
6. El ticket se administra desde el panel.

Actualmente las páginas PHP combinan recepción de solicitudes, validaciones, consultas SQL, reglas de negocio y HTML. Además, `includes/db.php` contiene la conexión, cambios de esquema, consultas, validadores y reglas relacionadas con varios módulos.

## 3. Resultado esperado

Al terminar la migración:

- Las páginas públicas y administrativas tendrán controladores claros.
- Las vistas contendrán principalmente HTML y presentación.
- Los modelos representarán los datos principales del sistema.
- Los repositorios concentrarán las consultas SQL.
- Los servicios contendrán reglas de negocio y procesos que involucren varios pasos.
- La seguridad estará separada en middleware y servicios reutilizables.
- Las migraciones de base de datos no se ejecutarán en cada visita al sitio.
- Los archivos subidos se gestionarán desde un único servicio.
- El comportamiento existente podrá verificarse mediante pruebas.
- Será posible agregar nuevos módulos sin seguir aumentando `includes/db.php` o las páginas administrativas.

## 4. Principios de trabajo

1. **Migración gradual:** cada parte nueva debe convivir temporalmente con la parte antigua.
2. **Compatibilidad funcional:** no se deben cambiar reglas de negocio sin una decisión explícita.
3. **Cambios pequeños:** cada PBI debe dividirse en tareas que puedan revisarse y probarse por separado.
4. **Una responsabilidad por clase:** una clase no debe encargarse al mismo tiempo de HTML, SQL, autenticación y correo.
5. **Seguridad centralizada:** validación CSRF, sesiones, permisos y cargas de archivos deben tener un único punto de referencia.
6. **Base de datos controlada:** los cambios de tablas deben quedar documentados y ejecutarse mediante migraciones.
7. **Código legible:** se debe preferir código claro y explícito antes que abstracciones innecesarias.
8. **Sin cambios destructivos:** no se deben eliminar archivos antiguos hasta comprobar que sus reemplazos funcionan.
9. **Testabilidad desde el inicio:** todo código nuevo debe poder probarse sin depender de páginas web, datos reales ni servicios externos.

## 5. Requisito de testabilidad

La aplicación debe poder probarse de forma automatizada durante toda la migración. Las clases nuevas deben recibir sus dependencias y separar la lógica de negocio de HTTP, HTML, MySQL, correo y archivos.

Las pruebas se dividirán en:

- **Unitarias:** validan una clase o regla aislada, sin base de datos ni servicios externos.
- **Integración:** validan la comunicación con una base de datos de pruebas y componentes reales.
- **Funcionales:** validan flujos completos, como crear un ticket desde contacto o cambiar su estado.

Reglas mínimas:

- PHPUnit será la herramienta base.
- Las pruebas no utilizarán credenciales ni datos de producción.
- Cada módulo nuevo debe incluir pruebas de sus reglas importantes.
- Las dependencias externas deben poder sustituirse por dobles de prueba.
- Las pruebas deben ejecutarse con `vendor/bin/phpunit`.

## 6. Arquitectura objetivo

```text
rcnsistemas-web-app/
├── public/
│   ├── index.php
│   ├── productos.php
│   ├── product_view.php
│   ├── promociones.php
│   ├── promotion_view.php
│   ├── testimoniales.php
│   ├── contacto.php
│   ├── admin/
│   │   ├── index.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── products.php
│   │   ├── promotions.php
│   │   ├── tickets.php
│   │   ├── ticket_create.php
│   │   ├── ticket_view.php
│   │   ├── customers.php
│   │   ├── customer_create.php
│   │   ├── customer_edit.php
│   │   ├── customer_view.php
│   │   ├── users.php
│   │   ├── user_create.php
│   │   ├── user_edit.php
│   │   ├── notifications.php
│   │   ├── notifications_stream.php
│   │   └── ticket_service_order_pdf.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   │   └── audio/
│   └── uploads/
│       ├── images/
│       ├── pdfs/
│       └── tickets/
├── app/
│   ├── Bootstrap/
│   ├── Core/
│   ├── Controllers/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   ├── Middleware/
│   └── Views/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── scripts/
├── storage/
├── tests/
├── composer.json
├── .env.example
└── README.md
```

La arquitectura será un **MVC modular**:

- **Controladores:** reciben la solicitud, llaman al servicio apropiado y seleccionan la vista o respuesta.
- **Modelos:** representan entidades como producto, promoción, cliente, ticket y usuario.
- **Repositorios:** leen y escriben información en MySQL.
- **Servicios:** ejecutan procesos de negocio, por ejemplo crear un ticket, calcular un precio o enviar un correo.
- **Vistas:** muestran información y formularios sin realizar consultas SQL ni tomar decisiones de negocio.
- **Middleware:** comprueba sesión, rol y token CSRF antes de ejecutar una acción.

## 7. PBIs y tareas

## PBI-00: Habilitar pruebas automatizadas desde el inicio

### Objetivo

Crear la base de pruebas antes de migrar funcionalidades para que cada paso de la reorganización pueda comprobarse automáticamente.

### Tareas

- [x] Agregar PHPUnit como dependencia de desarrollo.
- [x] Crear `phpunit.xml.dist` con una suite de pruebas del proyecto.
- [x] Crear `tests/bootstrap.php` para cargar Composer.
- [x] Crear la primera prueba unitaria de la clase `Config`.
- [ ] Crear un archivo de configuración para el entorno de pruebas.
- [ ] Definir una base de datos exclusiva para pruebas de integración.
- [ ] Crear utilidades para limpiar y preparar datos de prueba.
- [ ] Documentar el comando para ejecutar las pruebas.
- [ ] Configurar la ejecución de pruebas en integración continua.

### Criterios de aceptación

- PHPUnit está definido como dependencia de desarrollo.
- Las pruebas se ejecutan sin abrir un navegador.
- Las pruebas unitarias iniciales no requieren conexión a MySQL.
- Las pruebas no utilizan datos de producción.
- Un cambio que rompa una prueba produce un resultado fallido identificable.

## PBI-01: Preparar la base técnica de la migración

### Objetivo

Crear los elementos mínimos para cargar la aplicación de forma consistente y permitir que el código nuevo conviva con las páginas actuales.

### Tareas

- [x] Crear `app/Bootstrap/app.php` como punto único de inicio para cargar Composer, configuración y servicios básicos.
- [x] Configurar el autoload PSR-4 de Composer para el espacio de nombres de la aplicación.
- [x] Crear `config/app.php` para nombre, entorno, URL base y modo de errores.
- [x] Crear `config/database.php` para las variables de conexión.
- [x] Crear `config/mail.php` para la configuración SMTP.
- [x] Crear `config/uploads.php` para centralizar las rutas de archivos.
- [x] Crear una clase de configuración que lea `.env.local` y `.env` sin exponer valores sensibles.
- [x] Crear una clase de conexión que devuelva una conexión MySQL reutilizable.
- [x] Definir una forma estándar de obtener la ruta raíz y las rutas públicas.
- [ ] Documentar cómo ejecutar la aplicación con MAMP y con el servidor integrado de PHP.
- [x] Mantener temporalmente `includes/db.php` funcionando para evitar romper las páginas existentes.

### Criterios de aceptación

- La aplicación puede iniciar con la configuración actual.
- Composer carga las clases nuevas automáticamente.
- Ninguna credencial queda escrita directamente en las clases nuevas.
- Las páginas existentes siguen funcionando sin cambios de comportamiento.

## PBI-02: Crear el núcleo MVC y el sistema de vistas

### Objetivo

Establecer una forma común de recibir solicitudes, devolver respuestas y renderizar vistas.

### Tareas

- [x] Crear una clase `Request` para consultar de forma uniforme parámetros, formularios, archivos y método HTTP.
- [x] Crear una clase `Response` para respuestas HTML, redirecciones y JSON.
- [x] Crear un renderizador de vistas con soporte para layouts y variables.
- [x] Crear un layout público que pueda reemplazar gradualmente `includes/header.php` y `includes/footer.php`.
- [x] Crear un layout administrativo que pueda reemplazar gradualmente `admin/includes/admin_header.php`.
- [x] Crear componentes reutilizables para alertas, botones, formularios, tablas y paginación.
- [x] Crear una clase de rutas o un router sencillo para asociar URLs con controladores.
- [x] Definir respuestas estándar para páginas no encontradas y errores internos.
- [x] Definir una convención para nombres de vistas, por ejemplo `public/products/index.php` y `admin/tickets/show.php`.
- [x] Crear un controlador base que pueda renderizar vistas mediante el renderizador común.

### Criterios de aceptación

- Un controlador puede renderizar una vista sin incluir manualmente archivos globales.
- Un controlador puede redirigir y devolver respuestas HTML de forma uniforme.
- Los layouts público y administrativo producen el mismo resultado visual actual.
- Los errores 404 y 500 tienen una respuesta controlada.

## PBI-03: Separar autenticación, sesiones y permisos

### Objetivo

Extraer la seguridad de `includes/security.php` y mantener el comportamiento actual de inicio de sesión, roles, CSRF y sesión persistente.

### Tareas

- [x] Crear `AuthService` para iniciar sesión y cerrar sesión.
- [x] Crear `SessionManager` para iniciar sesiones seguras, regenerar identificadores y controlar expiración.
- [x] Crear un repositorio de usuarios administrativos para consultar usuarios por nombre e identificador.
- [x] Crear `CsrfService` para emitir y validar tokens.
- [x] Crear `AuthMiddleware` para proteger el panel administrativo.
- [x] Crear `RoleMiddleware` para restringir la administración de usuarios a supervisores.
- [x] Crear métodos claros para obtener el usuario actual, su rol y su etiqueta visible.
- [x] Mantener las opciones actuales de cookies `secure`, `httponly` y `samesite`.
- [ ] Revisar que ningún controlador protegido pueda ejecutarse sin validación de sesión.
- [ ] Migrar `login.php`, `logout.php`, `users.php`, `user_create.php` y `user_edit.php`.

### Criterios de aceptación

- Un usuario no autenticado es redirigido al login.
- Un operador no puede acceder a la administración de usuarios.
- La sesión expira después del tiempo configurado.
- El token de “recordarme” se valida, rota y revoca correctamente.
- Todos los formularios protegidos rechazan un token CSRF inválido.

## PBI-04: Migrar catálogo de productos y beneficios

### Objetivo

Separar productos y beneficios de las páginas públicas, el panel administrativo y `includes/db.php`.

### Tareas

- [x] Crear los modelos `Product` y `Benefit`.
- [x] Crear `ProductRepository` para listar, consultar, crear, actualizar y eliminar productos.
- [x] Crear `BenefitRepository` para administrar beneficios relacionados con productos.
- [x] Crear `ProductService` para validar y guardar productos junto con sus beneficios.
- [x] Crear `ProductController` público para listado y detalle.
- [x] Crear `AdminProductController` para listado y guardado inicial.
- [x] Separar las vistas públicas de productos y las vistas administrativas.
- [ ] Centralizar la validación de nombre, descripción, video, PDF e imagen.
- [ ] Centralizar el manejo de productos inexistentes.
- [ ] Migrar `index.php`, `productos.php`, `product_view.php` y `admin/products.php`.
- [ ] Revisar el uso de `productos.json` y decidir si queda únicamente como fuente de seed o si debe retirarse.

### Criterios de aceptación

- El catálogo público conserva su contenido y orden actual.
- El panel puede crear, editar y eliminar productos.
- Los beneficios se guardan y eliminan correctamente al actualizar un producto.
- Las consultas SQL no aparecen dentro de las vistas.
- Los campos mostrados al usuario se escapan correctamente.

## PBI-05: Migrar promociones y testimoniales

### Objetivo

Separar la administración y publicación de promociones y testimoniales.

### Tareas

- [ ] Crear los modelos `Promotion` y `Testimonial`.
- [ ] Crear sus repositorios para consultas y operaciones de escritura.
- [ ] Crear `PromotionService` para validar fechas, vigencia, textos e imagen.
- [ ] Crear `TestimonialService` para validar nombre, mensaje, calificación y fecha.
- [ ] Crear controladores públicos para listados y detalles.
- [ ] Crear controladores administrativos para altas, modificaciones y eliminaciones.
- [ ] Mantener la compatibilidad temporal con promociones que todavía tengan el campo antiguo `description`.
- [ ] Mover la normalización de promociones a un servicio o transformador dedicado.
- [ ] Migrar `promociones.php`, `promotion_view.php`, `testimoniales.php` y `admin/promotions.php`.
- [ ] Revisar si los testimoniales requieren moderación antes de publicarse.

### Criterios de aceptación

- Solo se muestran promociones activas y vigentes en el sitio público.
- Las fechas inválidas o invertidas se rechazan.
- Los campos antiguos de promociones no provocan pérdida de información.
- El formulario de testimoniales conserva sus validaciones y mensajes actuales.

## PBI-06: Migrar clientes y tickets

### Objetivo

Separar el módulo de mayor complejidad funcional y convertirlo en el centro de la nueva arquitectura.

### Tareas de clientes

- [ ] Crear el modelo `Customer`.
- [ ] Crear `CustomerRepository` para búsquedas, paginación y consultas por identificadores.
- [ ] Crear `CustomerService` para normalizar nombre, correo, teléfono y tipo de cliente.
- [ ] Mantener la regla de identificar clientes por teléfono normalizado.
- [ ] Mantener la posibilidad de usar correo opcional.
- [ ] Migrar `customers.php`, `customer_create.php`, `customer_edit.php`, `customer_view.php` y `customer_search.php`.

### Tareas de tickets

- [ ] Crear el modelo `Ticket`.
- [ ] Crear `TicketObservation` y las entidades necesarias para asignaciones y notificaciones.
- [ ] Crear `TicketRepository` para listado, detalle, filtros, paginación y estadísticas.
- [ ] Crear `TicketService` para crear y actualizar tickets.
- [ ] Crear `TicketWorkflow` para validar las transiciones de estado.
- [ ] Crear `TicketPricing` para calcular base, IVA, retención y total.
- [ ] Crear `TicketScheduleService` para validar citas y horarios de servicio.
- [ ] Crear `TicketAssignmentService` para asignar responsables y validar permisos.
- [ ] Crear `TicketObservationService` para agregar y consultar observaciones.
- [ ] Mantener la regla de que un ticket cerrado no admite modificaciones.
- [ ] Mantener la diferencia de acceso entre administradores y operadores.
- [ ] Migrar `tickets.php`, `ticket_create.php` y `ticket_view.php`.
- [ ] Mover las etiquetas de origen, estados y tipos a catálogos o constantes centralizadas.
- [ ] Eliminar funciones duplicadas como etiquetas de origen definidas en más de una página.

### Criterios de aceptación

- Un contacto público puede crear un ticket sin interacción manual del administrador.
- Un cliente existente se actualiza sin crear duplicados por teléfono.
- Los filtros y la paginación del listado siguen funcionando.
- Un operador solo ve y modifica los tickets permitidos.
- Un administrador puede asignar y reasignar tickets.
- Las transiciones de estado mantienen sus reglas actuales.
- Un ticket cerrado no puede editarse.
- Los cálculos monetarios producen los mismos resultados actuales.
- Las citas y horarios inválidos se rechazan con mensajes claros.

## PBI-07: Separar contacto, correo y cargas de archivos

### Objetivo

Convertir el formulario de contacto en un proceso reutilizable y seguro.

### Tareas

- [ ] Crear `ContactController` para mostrar y procesar el formulario.
- [ ] Crear `ContactService` para coordinar cliente, contexto, ticket y notificación.
- [ ] Crear `MailService` para encapsular PHPMailer.
- [ ] Crear plantillas de correo separadas del controlador.
- [ ] Crear `UploadService` para validar extensión, tamaño, MIME, nombre y destino.
- [ ] Separar reglas de imágenes, PDFs y adjuntos de tickets.
- [ ] Generar nombres de archivo seguros sin depender del nombre original del usuario.
- [ ] Revisar permisos de lectura y escritura de `uploads`.
- [ ] Definir qué archivos pueden ser públicos y cuáles deben descargarse mediante una acción protegida.
- [ ] Mantener el contexto de producto o promoción en el ticket.
- [ ] Mantener el comportamiento actual cuando el correo no pueda enviarse: registrar el error sin perder el ticket.
- [ ] Migrar el formulario de `contacto.php`.

### Criterios de aceptación

- Un archivo no permitido nunca se guarda.
- Los archivos guardados no pueden ejecutar código del lado del servidor.
- El ticket se conserva aunque el envío de correo falle.
- El correo contiene los datos escapados del ticket.
- El adjunto correcto se incorpora al correo cuando existe.

## PBI-08: Migrar notificaciones y documentos PDF

### Objetivo

Separar los procesos de comunicación del panel y la generación de órdenes de servicio.

### Tareas

- [ ] Crear `NotificationRepository` para notificaciones administrativas.
- [ ] Crear `NotificationService` para crear, consultar, marcar como leídas y eliminar notificaciones.
- [ ] Crear una respuesta específica para el flujo de eventos en tiempo real.
- [ ] Proteger el stream de notificaciones con la misma validación de sesión del panel.
- [ ] Crear `PdfService` para encapsular Dompdf y la alternativa de PDF simple.
- [ ] Crear `ServiceOrderDocument` para construir el contenido de una orden de servicio.
- [ ] Separar la plantilla HTML del documento de las funciones de formato.
- [ ] Mantener la restricción de acceso al PDF según el ticket y el usuario actual.
- [ ] Migrar `notifications.php`, `notifications_stream.php`, `ticket_service_order_pdf.php` y el documento asociado.

### Criterios de aceptación

- Una asignación genera la notificación correspondiente.
- El contador de no leídas se actualiza correctamente.
- Un usuario no autorizado no recibe eventos del stream.
- El PDF mantiene el contenido y formato actuales.
- La generación funciona con Dompdf instalado y con el mecanismo alternativo definido.

## PBI-09: Sustituir cambios automáticos de esquema por migraciones

### Objetivo

Hacer que la estructura de la base de datos sea explícita, versionada y controlable.

### Tareas

- [ ] Documentar el esquema real actual de las tablas.
- [ ] Comparar `database.sql` con el esquema que actualmente crea y modifica `includes/db.php`.
- [ ] Crear una migración inicial para `users`, `products`, `benefits`, `promotions` y `testimonials`.
- [ ] Crear migraciones para `customers`, `tickets` y `ticket_observations`.
- [ ] Crear migraciones para notificaciones y tokens persistentes.
- [ ] Crear migraciones para columnas de precios, agenda, asignaciones y auditoría.
- [ ] Crear una migración de compatibilidad para datos antiguos de promociones.
- [ ] Crear scripts de respaldo antes de ejecutar cambios sobre datos existentes.
- [ ] Crear un comando o script para ejecutar migraciones pendientes.
- [ ] Retirar gradualmente las llamadas `ensure_*` de las solicitudes web.
- [ ] Actualizar `database.sql` o reemplazarlo por una guía de instalación basada en migraciones.
- [ ] Separar los seeders de los scripts de corrección de datos.

### Criterios de aceptación

- Una visita normal al sitio no modifica la estructura de la base de datos.
- Las migraciones pueden ejecutarse en una base nueva.
- Las migraciones pueden ejecutarse sobre una base existente sin perder datos.
- La versión de la base de datos puede identificarse.
- Los scripts de corrección no se exponen como páginas públicas.

## PBI-10: Separar recursos y mejorar la organización del frontend

### Objetivo

Organizar CSS, JavaScript, imágenes, PDFs y archivos subidos según su uso.

### Tareas

- [ ] Separar estilos públicos y administrativos.
- [ ] Separar JavaScript público, administrativo y de notificaciones.
- [ ] Mover logos e imágenes estáticas a `public/assets/images`.
- [ ] Mover el audio de notificaciones a `public/assets/audio`.
- [ ] Definir una estrategia para las fichas PDF oficiales.
- [ ] Mantener URLs compatibles durante la migración o crear redirecciones.
- [ ] Crear un helper para versionar CSS y JavaScript usando fecha de modificación o versión de aplicación.
- [ ] Eliminar el uso de `time()` como versión de assets cuando no sea necesario.
- [ ] Revisar rutas relativas para que funcionen igual desde el sitio público y el panel.
- [ ] Evitar estilos en línea cuando puedan convertirse en clases reutilizables.

### Criterios de aceptación

- El sitio público y el panel conservan su apariencia.
- Los recursos se cargan correctamente desde cualquier página.
- No se duplican reglas CSS por errores de rutas.
- Las notificaciones continúan reproduciendo su sonido.

## PBI-11: Crear pruebas y validaciones de regresión

### Objetivo

Comprobar que la reorganización no cambió las funcionalidades existentes.

### Tareas

- [ ] Elegir PHPUnit como herramienta de pruebas.
- [ ] Crear una configuración de pruebas separada de producción.
- [ ] Crear pruebas unitarias para normalización de teléfonos y correos.
- [ ] Crear pruebas unitarias para validación de clientes.
- [ ] Crear pruebas unitarias para estados y transiciones de tickets.
- [ ] Crear pruebas unitarias para cálculos de precios.
- [ ] Crear pruebas unitarias para citas y horarios.
- [ ] Crear pruebas para permisos de administrador y operador.
- [ ] Crear pruebas para CSRF y expiración de sesiones.
- [ ] Crear pruebas para validación de archivos.
- [ ] Crear pruebas de integración para crear un cliente y un ticket.
- [ ] Crear pruebas de integración para crear un ticket desde contacto.
- [ ] Crear pruebas de acceso a tickets asignados y no asignados.
- [ ] Crear una lista manual de regresión para validar las páginas públicas y administrativas.
- [ ] Ejecutar la lista manual antes de retirar cada página antigua.

### Criterios de aceptación

- Las reglas críticas tienen pruebas automatizadas.
- Las pruebas pueden ejecutarse sin utilizar datos reales.
- Existe una lista clara de funcionalidades que deben revisarse manualmente.
- Cada módulo migrado tiene evidencia de validación.

## PBI-12: Retirar código antiguo y documentar la arquitectura

### Objetivo

Finalizar la migración sin dejar dos implementaciones activas de la misma funcionalidad.

### Tareas

- [ ] Confirmar que cada página antigua tiene un reemplazo funcionando.
- [ ] Redirigir o retirar los puntos de entrada antiguos de forma controlada.
- [ ] Eliminar consultas y funciones duplicadas de `includes/db.php`.
- [ ] Convertir `includes/db.php` en una capa temporal o eliminarlo cuando ya no tenga consumidores.
- [ ] Retirar funciones duplicadas de `includes/security.php` después de migrarlas.
- [ ] Mover `debug_db.php`, `fix_admin.php` y `fix_paths.php` fuera del directorio público.
- [ ] Revisar y documentar el propósito de `scripts/fix_duplicate_customer_phones.php`.
- [ ] Eliminar archivos de seed o corrección que ya no sean necesarios, conservando sus reemplazos documentados.
- [ ] Actualizar el README con la nueva estructura, instalación, migraciones y comandos de pruebas.
- [ ] Documentar las reglas principales de tickets, permisos y archivos.
- [ ] Documentar el proceso de despliegue.
- [ ] Hacer una revisión final de secretos, permisos de carpetas y archivos accesibles desde el navegador.

### Criterios de aceptación

- Existe una única implementación activa por funcionalidad.
- El README permite configurar el proyecto desde cero.
- No hay herramientas de diagnóstico o corrección expuestas públicamente.
- El proyecto puede ser entendido por un desarrollador que no participó en la migración.

## 8. Orden recomendado de ejecución

La secuencia recomendada es:

1. PBI-00: habilitar pruebas automatizadas.
2. PBI-01: base técnica.
3. PBI-02: núcleo MVC y vistas.
4. PBI-03: autenticación y permisos.
5. PBI-04: productos y beneficios.
6. PBI-05: promociones y testimoniales.
7. PBI-06: clientes y tickets.
8. PBI-07: contacto, correo y archivos.
9. PBI-08: notificaciones y PDF.
10. PBI-09: migraciones de base de datos.
11. PBI-10: frontend y recursos.
12. PBI-11: pruebas y validaciones ampliadas.
13. PBI-12: retiro y documentación.

El PBI-09 puede comenzar en paralelo como trabajo de documentación, pero sus cambios deben aplicarse a producción después de que los repositorios nuevos estén funcionando. El PBI-11 amplía la base creada en el PBI-00 con pruebas específicas de cada regla y flujo.

## 9. Reglas de transición

Durante la migración se permitirá una convivencia temporal:

- Los controladores nuevos pueden reutilizar funciones antiguas mientras se extraen los servicios.
- Las páginas antiguas pueden llamar a servicios nuevos antes de ser reemplazadas por completo.
- No se deben crear nuevas funciones de negocio dentro de `includes/db.php`.
- Toda funcionalidad nueva debe implementarse en la estructura MVC.
- Cada archivo antiguo debe tener identificado su reemplazo.
- Una vez migrada una funcionalidad, las pruebas deben ejecutarse antes de iniciar la siguiente.

## 10. Riesgos y medidas de control

### Riesgo: cambiar accidentalmente reglas de tickets

Medida: crear primero pruebas para estados, permisos, agenda, precios y tickets cerrados.

### Riesgo: perder datos durante una migración de esquema

Medida: respaldos, migraciones reversibles cuando sea posible y ejecución primero en una copia de la base.

### Riesgo: romper rutas o recursos estáticos

Medida: mantener redirecciones y validar todas las páginas desde el navegador.

### Riesgo: duplicar clientes

Medida: conservar la normalización de teléfonos y probar números con formatos diferentes.

### Riesgo: exponer archivos subidos

Medida: validar tipo real de archivo, controlar nombres y revisar permisos del directorio.

### Riesgo: mantener dos arquitecturas indefinidamente

Medida: registrar en cada PBI qué archivo antiguo queda pendiente de retirar.

## 11. Definición de terminado

La migración se considerará terminada cuando:

- Las páginas públicas y administrativas utilicen controladores MVC.
- Los módulos tengan repositorios y servicios separados.
- Las vistas no contengan consultas SQL.
- La base de datos utilice migraciones versionadas.
- La autenticación y autorización estén centralizadas.
- Los archivos, correos, notificaciones y PDFs utilicen servicios dedicados.
- Las pruebas críticas estén automatizadas.
- Los scripts de diagnóstico no estén expuestos públicamente.
- El README esté actualizado.
- Se haya validado el flujo completo de contacto a ticket y de ticket a orden de servicio.
