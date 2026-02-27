# Sistema de Lotificación

Sistema web de gestión de registros con login, navbar lateral, formulario de captura de datos, dashboard de estadísticas y sistema de seguimiento (bitácora) con notificaciones modernas y diseño minimalista.

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web Apache con mod_rewrite habilitado
- Conexión a internet (para CDN de librerías)

## 🚀 Instalación

### 1. Configurar la Base de Datos

```bash
# Crear la base de datos
mysql -u root -p
CREATE DATABASE Lotificacion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Lotificacion;

# Importar el archivo SQL
SOURCE /var/www/html/Lotificacion/database.sql;
```

O importar manualmente desde phpMyAdmin el archivo `database.sql`

### 2. Configurar Zona Horaria de MySQL

**Importante:** Para que las fechas y horas se guarden correctamente en hora de Guatemala:

```sql
# Opción 1: Desde MySQL
SET GLOBAL time_zone = '-06:00';
SET time_zone = '-06:00';
```

**O editar el archivo de configuración de MySQL:**

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Agregar en la sección `[mysqld]`:

```ini
[mysqld]
default-time-zone = '-06:00'
```

Luego reiniciar MySQL:

```bash
sudo systemctl restart mysql
```

### 3. Configurar la Conexión

Editar el archivo `config/database.php` y ajustar las credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'Lotificacion');
define('DB_USER', 'root');        // Tu usuario de MySQL
define('DB_PASS', '');            // Tu contraseña de MySQL
```

### 4. Establecer Permisos

```bash
cd /var/www/html/Lotificacion
chmod -R 755 .
chown -R www-data:www-data .
```

### 5. Acceder al Sistema

Abre tu navegador y ve a:

```
http://localhost/Lotificacion/login.php
```

## 🔐 Credenciales por Defecto

- **Usuario:** admin
- **Contraseña:** admin123

**IMPORTANTE:** Cambia la contraseña después del primer inicio de sesión.

## 📁 Estructura del Proyecto

```
Lotificacion/
│
├── api/                          # API REST para operaciones CRUD
│   ├── obtener_registros.php        # Obtener todos los registros del usuario
│   ├── obtener_registro.php         # Obtener un registro específico
│   ├── actualizar_registro.php      # Actualizar un registro
│   ├── exportar_excel.php           # Exportar registros a CSV
│   ├── obtener_seguimientos.php     # Obtener seguimientos de un registro
│   ├── agregar_seguimiento.php      # Agregar nuevo seguimiento
│   └── contar_seguimientos.php      # Contar seguimientos por registro
│
├── config/
│   └── database.php              # Configuración de BD y zona horaria
│
├── css/
│   ├── login.css                 # Estilos del login
│   ├── navbar.css                # Estilos del navbar lateral (NUEVO v1.5)
│   ├── formulario.css            # Estilos del formulario y dashboard
│   └── ver_registros.css         # Estilos de la tabla y modal de bitácora
│
├── includes/                     # Componentes reutilizables (NUEVO v1.5)
│   └── navbar.php                # Navbar lateral con menú de navegación
│
├── js/
│   ├── login.js                  # JavaScript del login
│   ├── formulario.js             # JavaScript del formulario con validaciones
│   └── ver_registros.js          # JavaScript de DataTables, edición y bitácora
│
├── login.php                     # Página de inicio de sesión
├── formulario.php                # Formulario de captura de datos con dashboard
├── ver_registros.php             # Visualización de registros con DataTables
├── logout.php                    # Cierre de sesión
└── index.php                     # Redirigir automáticamente al login
└── README.md                     # Este archivo
```

## 🎯 Funcionalidades

### Navbar Lateral 🆕 (v1.5)

- **Menú de navegación fijo** siempre visible
- Opciones de menú:
  - 📊 Dashboard / Nuevo Registro
  - 📋 Ver Registros
  - 🚪 Cerrar Sesión (con confirmación)
- Información del usuario logueado
- Logo del sistema
- Responsive con menú hamburguesa para móviles
- Animaciones suaves al hacer hover
- Indicador de página activa
- Diseño minimalista con glassmorphism

### Login

- Autenticación de usuarios
- Sesiones seguras
- Validación de campos
- Registro de último acceso
- **Notificaciones SweetAlert2** para errores y mensajes de éxito

### Dashboard de Estadísticas 📊

- **Tarjetas informativas** con datos en tiempo real
- Total de registros del usuario
- Contador de registros por fuente:
  - Vallas publicitarias
  - Redes sociales
  - Por amigos
- Diseño moderno con iconos y colores distintivos
- Actualización automática al crear nuevos registros

### Formulario de Registro

- Campo de **Nombre**: Solo letras y espacios (obligatorio)
- Campo de **Apellido**: Solo letras y espacios (obligatorio)
- Campo de **Teléfono Guatemala**: Formato automático nnnn-nnnn (opcional)
- Campo de **Teléfono USA**: Formato automático +1 000-000-0000 (opcional)
- Campo de **¿Cómo se enteró?**: Select con 3 opciones (obligatorio)
  - Vallas publicitarias
  - Redes sociales
  - Por amigos
- Campo de **Correo Electrónico**: Validación de formato (opcional)
- Campo de **Comentario**: Opcional, máximo 500 caracteres
- Validación en tiempo real
- Contador de caracteres
- **Notificaciones SweetAlert2** para mensajes de éxito/error

### Ver Registros (DataTables)

- Visualización de todos los registros del usuario en tabla interactiva
- Búsqueda en tiempo real en todas las columnas
- Ordenamiento por cualquier columna
- Paginación personalizable (10, 25, 50, Todos)
- Diseño responsive (adaptable a móviles)
- Idioma español
- Solo muestra registros del usuario logueado
- Columna de acciones siempre visible
- Muestra ambos teléfonos (Guatemala y USA)

### Editar Registros

- Modal moderno para editar datos
- **Confirmación con SweetAlert2** antes de guardar cambios
- Validaciones en tiempo real
- Solo puede editar sus propios registros
- Actualización automática de la tabla
- Formato automático de teléfonos (Guatemala y USA)
- Validación de correo electrónico
- Validación de nombre y apellido (solo letras)
- **Notificaciones de éxito/error** con SweetAlert2

### Sistema de Seguimiento (Bitácora) 📋

- **Gestión completa de llamadas y seguimientos**
- Modal dedicado para cada registro
- Visualización del historial completo de seguimientos
- Agregar nuevos comentarios de seguimiento
- **Notificaciones SweetAlert2** al guardar seguimientos
- Muestra quién realizó cada seguimiento
- Fecha y hora de cada seguimiento
- Muestra ambos teléfonos del cliente
- Ideal para:
  - Registrar llamadas no contestadas
  - Programar seguimientos
  - Llevar control de contactos
  - Historial de comunicación con clientes

### Exportar Datos

- Exportación a formato CSV
- Compatible con Excel, LibreOffice, Google Sheets
- Codificación UTF-8 (acentos correctos)
- Incluye todos los campos: ID, Nombre, Apellido, Teléfono GT, Teléfono USA, Cómo se enteró, Correo, Comentario, Fecha
- Nombre de archivo con fecha y hora
- Formato con punto y coma (compatible con Excel en español)

### Sistema de Notificaciones (SweetAlert2) 🔔

- **Notificaciones modernas y atractivas** en lugar de alerts nativos
- Confirmaciones antes de acciones importantes
- Mensajes de éxito con temporizadores
- Alertas de error con detalles
- Confirmación de cerrar sesión
- Validaciones visuales mejoradas

## 🔧 Validaciones de Teléfonos

### Teléfono Guatemala:

- Solo acepta números
- Formato automático: coloca el guion después del 4to dígito
- Máximo 8 números (formato: 0000-0000)
- Validación en tiempo real
- Prevención de pegado de texto no válido
- Limpieza automática al eliminar
- **Campo opcional**

### Teléfono USA:

- Formato automático: +1 000-000-0000
- Agrega automáticamente el prefijo +1
- Limita a 10 dígitos después del código de país
- Validación en tiempo real
- Limpieza automática
- **Campo opcional**

## 💻 Tecnologías Utilizadas

### Backend

- PHP 7.4+ con PDO
- MySQL 5.7+ con UTF-8
- Apache con mod_rewrite
- Zona horaria configurada (America/Guatemala)

### Frontend

- HTML5 semántico
- CSS3 (flexbox, grid, gradientes, animaciones, glassmorphism)
- JavaScript vanilla (ES6+)
- jQuery 3.7.0

### Librerías Externas (CDN)

- **DataTables 1.13.7**: Tablas interactivas con búsqueda y paginación
- **DataTables Responsive**: Adaptación automática a móviles
- **DataTables Español**: Idioma español para la interfaz
- **SweetAlert2**: Notificaciones y alertas modernas

### Características de Diseño (v1.5)

- **Navbar lateral fijo** con menú de navegación
- Diseño responsive (móviles, tablets, desktop)
- **Fondo animado** con ondas y partículas flotantes
- **Glassmorphism** (backdrop-filter blur) en tarjetas y navbar
- Gradientes suaves en tonos azules
- Animaciones CSS suaves y profesionales
- Modales con overlay (edición y bitácora)
- Dashboard con tarjetas estadísticas
- Efectos hover sutiles
- Menú hamburguesa para móviles
- Notificaciones visuales atractivas
- Botones de acción con colores distintivos

## 📊 Base de Datos

### Tabla: usuarios

- `id` - Identificador único
- `usuario` - Nombre de usuario (único)
- `password` - Contraseña hasheada
- `nombre_completo` - Nombre completo del usuario
- `fecha_creacion` - Fecha de creación
- `ultimo_acceso` - Último inicio de sesión
- `activo` - Estado del usuario

### Tabla: registros

- `id` - Identificador único
- `nombre` - Nombre de la persona (obligatorio)
- `apellido` - Apellido de la persona (obligatorio)
- `telefono` - Teléfono Guatemala formato nnnn-nnnn (opcional)
- `telefono_americano` - Teléfono USA formato +1 000-000-0000 (opcional)
- `como_se_entero` - Cómo se enteró de nosotros (obligatorio)
- `correo` - Correo electrónico (opcional)
- `comentario` - Comentario opcional
- `usuario_id` - Usuario que creó el registro
- `fecha_registro` - Fecha de creación

### Tabla: seguimiento (Bitácora)

- `id` - Identificador único
- `registro_id` - Referencia al registro (FK)
- `comentario` - Comentario del seguimiento
- `usuario_id` - Usuario que realizó el seguimiento
- `fecha_creacion` - Fecha y hora del seguimiento

## 🔄 Flujo de Trabajo del Usuario

1. **Iniciar Sesión**

   - Acceder a `login.php`
   - Ingresar credenciales
   - El sistema verifica y crea sesión
   - **Notificación de bienvenida** con SweetAlert2

2. **Navegar por el Sistema** 🆕

   - Usar el **navbar lateral** para moverse entre secciones
   - Dashboard / Nuevo Registro
   - Ver Registros
   - Cerrar Sesión (con confirmación)

3. **Ver Dashboard**

   - Visualizar estadísticas en tiempo real
   - Total de registros
   - Desglose por fuente de información

4. **Crear Registro**

   - Formulario con validaciones en tiempo real
   - Campos obligatorios: Nombre, Apellido, Cómo se enteró
   - Campos opcionales: Teléfonos, Correo, Comentario
   - Guardar → **Notificación de éxito** con SweetAlert2

5. **Ver Registros**

   - Clic en "Ver Registros" en el navbar
   - Tabla interactiva con DataTables
   - Búsqueda instantánea en todas las columnas
   - Ordenamiento por columnas
   - Paginación personalizable

6. **Gestionar Bitácora (Seguimiento)**

   - Clic en botón "📋 Bitácora"
   - Ver historial completo de seguimientos
   - Agregar nuevo comentario de seguimiento
   - **Notificación de éxito** al guardar
   - Ver quién y cuándo se realizó cada seguimiento
   - Visualizar ambos teléfonos del cliente

7. **Editar Registro**

   - Clic en botón "Editar"
   - Modal con datos pre-llenados
   - **Confirmación con SweetAlert2** antes de guardar
   - Modificar y guardar
   - Tabla se actualiza automáticamente

8. **Exportar Datos**

   - Clic en "Exportar a CSV"
   - Descarga automática
   - Archivo compatible con Excel

9. **Cerrar Sesión**
   - Clic en "Cerrar Sesión" en el navbar
   - **Confirmación con SweetAlert2**
   - Sesión destruida
   - **Mensaje de despedida**
   - Redirección a login

## 🔒 Seguridad

- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Protección contra inyección SQL con PDO y prepared statements
- Validación de sesiones en todas las páginas
- Sanitización de todas las entradas del usuario
- Protección XSS con `htmlspecialchars()`
- Protección de archivos sensibles con .htaccess
- Cabeceras de seguridad configuradas
- Aislamiento de datos por usuario (cada usuario solo ve sus registros)
- Verificación de permisos en operaciones CRUD
- Los seguimientos solo pueden ser vistos y creados por el dueño del registro
- Confirmaciones de acciones críticas con SweetAlert2

## 📝 Crear Nuevos Usuarios

Para crear un nuevo usuario, ejecuta en MySQL:

```sql
INSERT INTO usuarios (usuario, password, nombre_completo)
VALUES ('nuevo_usuario', '$2y$10$hashaqui', 'Nombre Completo');
```

Para generar el hash de la contraseña, usa este script PHP:

```php
<?php
echo password_hash("tu_contraseña", PASSWORD_DEFAULT);
?>
```

## 🐛 Solución de Problemas

### Error de conexión a la base de datos

- Verifica las credenciales en `config/database.php`
- Asegúrate de que MySQL está ejecutándose
- Verifica que la base de datos existe

### Página en blanco

- Revisa los logs de PHP: `/var/log/apache2/error.log`
- Verifica los permisos de archivos
- Asegúrate de que PHP esté habilitado

### El navbar no aparece 🆕

- Verifica que la carpeta `includes/` existe
- Confirma que `navbar.php` está en `includes/`
- Revisa que `css/navbar.css` existe y está cargando
- Verifica permisos de archivos

### Los teléfonos no se formatean

- Verifica que `js/formulario.js` esté cargando
- Revisa la consola del navegador para errores
- Asegúrate de tener JavaScript habilitado

### DataTables no carga (error 404)

- Verifica que la carpeta `api/` existe
- Confirma que los archivos PHP están en `api/`
- Verifica los permisos: `chmod 644 api/*.php`
- Asegúrate de tener conexión a internet (CDN de DataTables)

### SweetAlert2 no funciona

- Verifica conexión a internet (CDN)
- Revisa que el script esté cargado en el `<head>`
- Abre la consola del navegador para ver errores
- Verifica que jQuery esté cargado antes de los scripts

### El menú móvil no funciona 🆕

- Verifica que el JavaScript del navbar esté cargando
- Revisa la consola del navegador para errores
- Confirma que el botón hamburguesa existe en el HTML
- Verifica que jQuery esté cargado

## 📈 Historial de Versiones

### v1.5 - Navbar Lateral y Diseño Minimalista 🆕

- ✅ Navbar lateral fijo con menú de navegación
- ✅ Componente reutilizable `includes/navbar.php`
- ✅ Estilos dedicados en `css/navbar.css`
- ✅ Menú hamburguesa responsive para móviles
- ✅ Indicador de página activa
- ✅ Fondo animado con ondas y partículas
- ✅ Glassmorphism en tarjetas y navbar
- ✅ Diseño minimalista y moderno
- ✅ Animaciones suaves y profesionales
- ✅ Botón de cerrar sesión en el navbar

### v1.4 - Dashboard de Estadísticas y SweetAlert2

- ✅ Dashboard con tarjetas estadísticas
- ✅ Contadores en tiempo real por fuente de información
- ✅ SweetAlert2 integrado en todo el sistema
- ✅ Notificaciones modernas para todas las acciones
- ✅ Confirmaciones antes de acciones críticas
- ✅ Teléfonos ahora opcionales (Guatemala y USA)
- ✅ Campo teléfono USA agregado
- ✅ Mejoras en UX con notificaciones visuales

### v1.3 - Sistema de Seguimiento (Bitácora)

- ✅ Tabla de seguimiento en base de datos
- ✅ Modal de bitácora con historial completo
- ✅ Agregar nuevos seguimientos
- ✅ Mostrar usuario y fecha de cada seguimiento
- ✅ API completa para gestión de seguimientos

### v1.2 - Campos Adicionales

- ✅ Campo Apellido (obligatorio)
- ✅ Campo "¿Cómo se enteró?" (select obligatorio)
- ✅ Separación de Nombre y Apellido
- ✅ Validaciones actualizadas
- ✅ Exportación actualizada con nuevos campos

### v1.1 - Campo de Correo

- ✅ Campo de Correo Electrónico opcional
- ✅ Validación de formato de email
- ✅ Actualización de base de datos
- ✅ Exportación a CSV mejorada

### v1.0 - Versión Inicial

- ✅ Sistema de login
- ✅ Formulario de registro
- ✅ DataTables con edición
- ✅ Exportación a Excel/CSV

## 👨‍💻 Desarrollador

Sistema desarrollado para gestión de lotificación en Guatemala.

---

**Versión:** 1.5  
**Fecha:** Noviembre 2025  
**Última actualización:** Implementación de navbar lateral y diseño minimalista
