# Sistema de Administración Oirsa

Sistema web de administración para la gestión de contratos y recursos humanos de Oirsa.

## 🚀 Características

### Autenticación y Seguridad

- ✅ Sistema de login con validación de credenciales
- ✅ Gestión de sesiones PHP
- ✅ Protección de rutas administrativas
- ✅ Confirmación de cierre de sesión con SweetAlert2
- ✅ Conexión a base de datos con PDO (segura contra SQL injection)

### Diseño UI/UX - Glassmorphism ✨ **NUEVO**

- ✅ **Rediseño completo del sistema** con estética glassmorphism moderna y profesional
- ✅ **Paleta de colores actualizada**:
  - Azul principal: #1A73E8
  - Verde acento: #43A047
  - Naranja: #ff9800
  - Rosa: #e91e63
  - Eliminado color morado de toda la interfaz
- ✅ **Tipografía**: Fuente Poppins de Google Fonts en todo el sistema
- ✅ **Navegación lateral izquierda** (sidebar):
  - Logo de OIRSA en la parte superior
  - Menú vertical con iconos
  - Perfil de usuario en la parte inferior
  - Indicador de página activa
  - Responsive con colapso en móviles
- ✅ **Efectos visuales**:
  - Fondos semi-transparentes con blur (backdrop-filter)
  - Bordes sutiles y sombras suaves
  - Animaciones de entrada (fadeInUp, slideInLeft)
  - Hover effects en todos los elementos interactivos
  - Transiciones suaves (cubic-bezier)
- ✅ **Componentes rediseñados**:
  - **Login**: Fondo con imagen, burbujas flotantes, glassmorphism blanco
  - **Dashboard**: Header card con info de usuario, tarjetas estadísticas con gradientes, DataTables estilizado
  - **Contratos**: Tabla moderna, botones circulares de acción, modal profesional con tarjetas
  - **Formularios**: Inputs con bordes azules, file upload con grid, botones con gradiente
  - **Badges**: Colores distintivos (azul, verde, naranja, rosa) sin morado

### Panel Administrativo (Dashboard)

- ✅ **Estadísticas en tiempo real** con datos de la base de datos
- ✅ **4 tarjetas principales**: Monto Total, Total Contratos, Contratos Activos, Contratos del Mes
- ✅ **Tipo de Servicios**: Técnicos vs Profesionales (con montos)
- ✅ **Tipo de Fondos**: APN, Opción 1, Opción 2 (con montos y total de personas)
- ✅ **Tipo de Armonización**: Normativas, Vicedespacho, Despacho Superior, Otro (con montos)
- ✅ **IVA**: Incluir vs Sumarse (con montos)
- ✅ **Tabla de contratos con DataTables**: Búsqueda, filtros, paginación y ordenamiento
- ✅ **Contratos activos calculados automáticamente** basado en fechas
- ✅ Diseño moderno con gradientes y animaciones
- ✅ Barra de navegación responsive
- ✅ Indicador de página activa en menú

### Sistema de Contratos

- ✅ Formulario completo de registro de contratos con:
  - **Número de Contrato**: Auto-generado (001-2026-O-M) pero editable
  - **Servicios**: Técnicos o Profesionales
  - **IVA**: Incluir o Sumarse
  - **Fondos**: APN, Opción 1, Opción 2
  - **Cargo de Presupuesto**: 8 opciones (Armonización de Normativas, Despacho Superior, Dirección Sanidad Vegetal, Dirección Sanidad Animal, Inocuidad de Alimentos, Cuarentena Vegetal, Trazabilidad, Otro)
  - **Término de Contratación**: Campo de texto personalizable que aparece en el PDF
  - **Fecha de Contrato**: Campo de fecha
  - **Datos del Contratista**: Nombre, edad, estado civil, profesión, domicilio, DPI
  - **Términos de Referencia**: 10 campos opcionales para detalles del servicio
  - **Fechas**: Inicio y finalización del contrato con validación
  - **Datos Financieros**: Monto total, número de pagos, monto por pago mensual
  - **Archivos Adjuntos**: CV, Título, Colegiado Activo, Cuenta de Banco, DPI, Otro (opcional)

### Gestión de Contratos

- ✅ **Página de Contratos** (`contratos.php`):

  - DataTables con búsqueda, filtrado y paginación
  - Columnas: ID, Nombre, Número de Contrato, Servicio, Fechas, Monto Total
  - Botones de acción con iconos de Font Awesome:
    - 👁️ **Visualizar**: Modal con todos los detalles del contrato
    - ✏️ **Editar**: Página completa de edición
    - 📋 **Bitácora**: Historial de cambios (próximamente)
    - 📄 **Descargar PDF**: Genera y descarga PDF del contrato ✨ **NUEVO**

- ✅ **Modal de Visualización**:

  - Muestra todos los datos del contrato
  - Sección de archivos adjuntos con iconos según tipo
  - Botones "Ver" para abrir archivos en nueva pestaña
  - Diseño elegante con gradientes y animaciones

- ✅ **Página de Edición** (`editar_contrato.php`):
  - Pre-carga todos los datos del contrato
  - Permite editar todos los campos (datos personales, términos, montos)
  - **Gestión de archivos adjuntos**:
    - Visualización de archivos actuales con botón "Ver"
    - Reemplazo automático al subir archivo nuevo del mismo tipo
    - Elimina archivo antiguo automáticamente
  - Botón "Cancelar" con confirmación de SweetAlert2
  - Validaciones en tiempo real
  - Formateo automático de montos y DPI

### Generación de PDFs ✨ **NUEVO**

- ✅ **Generación automática de contratos en PDF**:

  - PDF generado con formato oficial del MAGA
  - Encabezado con logo en todas las páginas
  - Pie de página con numeración
  - Fuente Arial 12pt (formato estándar)
  - **Descarga automática** al registrar un nuevo contrato
  - **Descarga desde página de contratos** con botón PDF

- ✅ **Contenido del PDF**:

  - Título con número de contrato
  - Introducción con datos de ambas partes (MAGA y Contratista)
  - **13 Cláusulas completas**:
    - PRIMERA: Objeto de la Contratación
    - SEGUNDA: Principales Actividades a Realizar (términos de referencia)
    - TERCERA: Plazo e Informes para Pago
    - CUARTA: Valor del Contrato y Forma de Pago
    - QUINTA: Erogaciones
    - SEXTA: Autoridad Administrativa
    - SEPTIMA: Cesión
    - OCTAVA: Exclusión de Responsabilidad Laboral
    - NOVENA: Terminación del Contrato
    - DECIMA: Causas de Fuerza Mayor
    - DÉCIMA PRIMERA: Confidencialidad y Derechos de Autor
    - DÉCIMA SEGUNDA: Solución de Diferencias
    - DÉCIMA TERCERA: Aceptación
  - Sección de firmas

- ✅ **Conversiones automáticas en el PDF**:
  - **Números a letras**: "96000" → "NOVENTA Y SEIS MIL"
  - **Fechas a texto**: "2025-06-02" → "dos de junio del dos mil veinticinco"
  - **DPI formateado**: "2130619610101" → "2130 61961 0101"
  - **DPI en letras con ceros iniciales**: "9846 02302 6489" → "nueve mil ochocientos cuarenta y seis espacio cero dos mil trescientos dos espacio..."
  - **Montos con formato**: "Q96,000.00" y en letras
  - **Cálculo automático**: N-1 pagos (si son 6 pagos, muestra "CINCO pagos")
  - **Término de contratación dinámico**: Texto personalizado en cláusula PRIMERA

### Funcionalidades Avanzadas

- ✅ **Auto-incremento de número de contrato** por año
- ✅ **Formateo automático de DPI**: `0000 00000 0000`
- ✅ **Formateo de montos**: `Q96,000.00` con conversión a letras
- ✅ **Manejo correcto de decimales** en edición de montos
- ✅ **Vista previa de archivos**: Imágenes y PDFs antes de enviar
- ✅ **Validaciones en tiempo real** con SweetAlert2
- ✅ **Almacenamiento de archivos** en servidor con estructura organizada por ID
- ✅ **Reemplazo automático de archivos** al editar contratos
- ✅ **Transacciones de base de datos** para integridad de datos
- ✅ **DataTables** para búsqueda y filtrado de contratos
- ✅ **Cache busting** para JavaScript (evita problemas de caché)
- ✅ **Rutas de archivos normalizadas** (maneja `../` correctamente)
- ✅ **Generación de PDFs con TCPDF** ✨ **NUEVO**
- ✅ **Conversión de números y fechas a texto en español** ✨ **NUEVO**

## 🛠️ Tecnologías Utilizadas

### Backend

- PHP 7.4+
- MySQL 5.7+
- PDO para conexión a base de datos
- **Composer** (gestor de dependencias) ✨ **NUEVO**
- **TCPDF 6.10+** (generación de PDFs) ✨ **NUEVO**

### Frontend

- HTML5
- CSS3 (Vanilla CSS con diseño moderno)
- JavaScript (ES6+)
- Font Awesome 6.0 (iconos)
- SweetAlert2 (notificaciones)
- DataTables (tablas interactivas)
- jQuery (requerido por DataTables)

## 📁 Estructura del Proyecto

```
Oirsa/
├── api/
│   ├── obtener_ultimo_contrato.php    # Auto-incremento de número de contrato
│   ├── procesar_formulario.php        # Procesamiento de nuevo contrato
│   ├── obtener_contratos.php          # Endpoint para DataTables
│   ├── ver_contrato.php               # Obtener detalles de un contrato
│   ├── actualizar_contrato.php        # Actualizar contrato existente
│   └── generar_pdf.php                # Generar PDF de contrato ✨ NUEVO
├── assets/
│   └── images/
│       ├── maga_logo.png              # Logo para encabezado del PDF
│       └── background.png             # ✨ NUEVO - Fondo para UI glassmorphism
├── config/
│   └── database.php                   # Configuración de conexión PDO
├── css/
│   ├── global.css                     # ✨ NUEVO - Variables globales y utilidades glassmorphism
│   ├── login.css                      # ✨ ACTUALIZADO - Diseño glassmorphism con burbujas
│   ├── dashboard.css                  # ✨ ACTUALIZADO - Estilos glassmorphism del dashboard
│   ├── formulario.css                 # ✨ ACTUALIZADO - Estilos glassmorphism del formulario
│   ├── contratos.css                  # ✨ ACTUALIZADO - Estilos glassmorphism de contratos
│   └── navbar.css                     # ✨ ACTUALIZADO - Sidebar lateral glassmorphism
├── js/
│   ├── login.js                       # Lógica del login
│   ├── formulario.js                  # Validaciones y formateo del formulario
│   ├── validar_formulario.js          # Envío de formulario y descarga PDF ✨ NUEVO
│   ├── contratos.js                   # Lógica de la página de contratos
│   └── editar_contrato.js             # Lógica de edición de contratos
├── lib/                                # ✨ NUEVO
│   ├── pdf_helpers.php                # Funciones auxiliares para PDFs
│   └── ContratoPDF.php                # Clase para generar PDFs
├── modules/
│   └── admin/
│       ├── dashboard.php              # ✨ ACTUALIZADO - Panel con glassmorphism
│       ├── formulario.php             # ✨ ACTUALIZADO - Formulario glassmorphism
│       ├── contratos.php              # ✨ ACTUALIZADO - Gestión con glassmorphism
│       └── editar_contrato.php        # ✨ ACTUALIZADO - Edición glassmorphism
├── includes/
│   └── navbar.php                     # ✨ ACTUALIZADO - Sidebar lateral
├── uploads/
│   └── contratos/                     # Archivos subidos organizados por contrato ID
│       ├── 1/                         # Contrato ID 1
│       ├── 2/                         # Contrato ID 2
│       └── ...
├── vendor/                             # ✨ NUEVO (Composer dependencies)
│   └── tecnickcom/tcpdf/              # Librería TCPDF
├── composer.json                       # ✨ NUEVO (Dependencias de Composer)
├── composer.lock                       # ✨ NUEVO (Versiones bloqueadas)
├── cleanup_test_data.sql               # ✨ NUEVO - Script para limpiar datos de prueba
├── migration_2026_01_07.sql            # ✨ NUEVO - Migración de BD (termino_contratacion, otro)
├── index.php                          # Redirección al login
├── login.php                          # ✨ ACTUALIZADO - Página login glassmorphism
├── logout.php                         # Cierre de sesión
└── README.md                          # Este archivo
```

## 🗄️ Base de Datos

### Tablas Principales

#### `usuarios`

```sql
- id (INT, PK, AUTO_INCREMENT)
- usuario (VARCHAR)
- password (VARCHAR, encriptada)
- rol (ENUM: 'admin')
- activo (TINYINT)
- fecha_creacion (TIMESTAMP)
- fecha_actualizacion (TIMESTAMP)
```

#### `contratos`

```sql
- id (INT, PK, AUTO_INCREMENT)
- numero_contrato (VARCHAR, auto-generado)
- servicios (ENUM: 'Tecnicos', 'Profesionales')
- iva (ENUM: 'Incluir', 'Sumarse')
- fondos (VARCHAR)
- armonizacion (VARCHAR) -- Ahora llamado "Cargo de Presupuesto" en el formulario
- armonizacion_otro (TEXT, para opción personalizada)
- fecha_contrato (DATE)
- nombre_completo (VARCHAR)
- edad (INT)
- estado_civil (ENUM: 'Soltero', 'Casado')
- profesion (VARCHAR)
- domicilio (TEXT)
- dpi (VARCHAR, 13 dígitos)
- termino1 a termino10 (TEXT, opcionales)
- fecha_inicio (DATE)
- fecha_fin (DATE)
- monto_total (DECIMAL)
- numero_pagos (INT)
- monto_pago (DECIMAL)
- termino_contratacion (TEXT) -- ✨ NUEVO: Texto personalizado para cláusula PRIMERA del PDF
- usuario_id (INT, FK)
- fecha_registro (TIMESTAMP)
- fecha_actualizacion (TIMESTAMP)
```

#### `contrato_archivos`

```sql
- id (INT, PK, AUTO_INCREMENT)
- contrato_id (INT, FK)
- tipo_archivo (ENUM: 'cv', 'titulo', 'colegiadoActivo', 'cuentaBanco', 'dpiArchivo', 'otro') -- ✨ Agregado 'otro'
- nombre_archivo (VARCHAR)
- ruta_archivo (VARCHAR)
- fecha_subida (TIMESTAMP)
```

## 📦 Instalación

### Requisitos Previos

- XAMPP/WAMP/LAMP (Apache + MySQL + PHP)
- Navegador web moderno
- Editor de código (opcional)

### Pasos de Instalación

1. **Clonar/Descargar el proyecto**

   ```bash
   # Colocar en la carpeta htdocs de XAMPP
   C:\xampp\htdocs\Oirsa
   ```

2. **Crear la base de datos**
   - Abrir phpMyAdmin: `http://localhost/phpmyadmin`
   - Crear base de datos `Oirsa`
   - Ejecutar el siguiente script SQL:

```sql
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS Oirsa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Oirsa;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin') NOT NULL DEFAULT 'admin',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario admin (contraseña: admin123)
INSERT INTO usuarios (usuario, password, rol)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Tabla de contratos
CREATE TABLE IF NOT EXISTS contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_contrato VARCHAR(50) NULL,
    servicios ENUM('Tecnicos', 'Profesionales') NULL,
    iva ENUM('Incluir', 'Sumarse') NULL,
    fondos VARCHAR(100) NULL,
    armonizacion VARCHAR(100) NULL,
    armonizacion_otro TEXT NULL,
    fecha_contrato DATE NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    edad INT NOT NULL,
    estado_civil ENUM('Soltero', 'Casado') NOT NULL,
    profesion VARCHAR(255) NOT NULL,
    domicilio TEXT NOT NULL,
    dpi VARCHAR(13) NOT NULL,
    termino1 TEXT NULL,
    termino2 TEXT NULL,
    termino3 TEXT NULL,
    termino4 TEXT NULL,
    termino5 TEXT NULL,
    termino6 TEXT NULL,
    termino7 TEXT NULL,
    termino8 TEXT NULL,
    termino9 TEXT NULL,
    termino10 TEXT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    monto_total DECIMAL(12, 2) NOT NULL,
    numero_pagos INT NOT NULL,
    monto_pago DECIMAL(12, 2) NOT NULL,
    usuario_id INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero_contrato (numero_contrato),
    INDEX idx_dpi (dpi),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha_registro (fecha_registro),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de archivos adjuntos
CREATE TABLE IF NOT EXISTS contrato_archivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT NOT NULL,
    tipo_archivo ENUM('cv', 'titulo', 'colegiadoActivo', 'cuentaBanco', 'dpiArchivo') NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contrato (contrato_id),
    INDEX idx_tipo (tipo_archivo),
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

3. **Configurar la conexión a la base de datos**

   - Editar `config/database.php`
   - Ajustar credenciales si es necesario:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'Oirsa');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Tu contraseña de MySQL
   ```

4. **Configurar permisos de carpeta uploads**

   **En Windows (XAMPP):**

   ```powershell
   icacls uploads /grant "Everyone:(OI)(CI)F" /T
   ```

   **En Linux/Mac:**

   ```bash
   chmod -R 777 uploads/
   chown -R www-data:www-data uploads/
   ```

5. **Instalar dependencias con Composer** ✨ **NUEVO**

   **Si tienes Composer instalado:**

   ```bash
   cd /var/www/html/Oirsa  # O la ruta de tu proyecto
   composer install
   ```

   **Si no tienes Composer:**

   ```bash
   # Instalar Composer
   curl -sS https://getcomposer.org/installer | php
   php composer.phar install
   ```

   Esto instalará TCPDF y todas las dependencias necesarias.

6. **Acceder al sistema**
   - Abrir navegador: `http://localhost/Oirsa`
   - Credenciales de prueba:
     - **Usuario:** `admin`
     - **Contraseña:** `admin123`

## 🎨 Características de Diseño

- **Gradientes modernos** en tonos morados
- **Animaciones suaves** en hover y transiciones
- **Diseño responsive** adaptable a diferentes pantallas
- **Iconos de Font Awesome** para mejor UX
- **Alertas elegantes** con SweetAlert2
- **Campos con validación visual** en tiempo real
- **Tarjetas con estadísticas** coloridas y animadas
- **Badges de estado** para contratos activos/finalizados
- **Modal elegante** para visualización de contratos
- **Grid de archivos** con iconos según tipo de archivo
- **PDFs profesionales** con formato oficial ✨ **NUEVO**

## 📝 Uso del Sistema

### Iniciar Sesión

1. Acceder a `http://localhost/Oirsa`
2. Ingresar usuario y contraseña
3. Click en "Iniciar Sesión"

### Dashboard

El dashboard muestra:

- **Monto Total**: Suma de todos los contratos
- **Total Contratos**: Cantidad total registrada
- **Contratos Activos**: Contratos cuya fecha de fin no ha pasado
- **Este Mes**: Contratos registrados en el mes actual
- **Estadísticas por categoría**: Con cantidad de personas y montos totales
- **Tabla completa**: Todos los contratos con búsqueda y filtros

### Registrar un Contrato

1. Ir a "Formulario" en el menú
2. El número de contrato se genera automáticamente (editable)
3. Llenar todos los campos obligatorios (\*)
4. Los campos opcionales pueden dejarse vacíos
5. Subir los archivos requeridos
6. Click en "Enviar Formulario"
7. **Aparecerá un SweetAlert con opción "Descargar PDF"** ✨ **NUEVO**
8. Click en "Descargar PDF" para obtener el contrato en formato PDF ✨ **NUEVO**

### Gestionar Contratos

1. Ir a "Contratos" en el menú
2. Ver lista completa con DataTables
3. Usar búsqueda para filtrar contratos
4. Acciones disponibles:
   - **Visualizar**: Ver todos los detalles en un modal
   - **Editar**: Modificar cualquier campo del contrato
   - **Bitácora**: Ver historial (próximamente)
   - **PDF**: Descargar contrato en formato PDF ✨ **NUEVO**

### Descargar PDF de un Contrato ✨ **NUEVO**

**Opción 1: Al crear el contrato**

1. Después de enviar el formulario exitosamente
2. Aparece SweetAlert con botón "Descargar PDF"
3. Click en el botón para descargar

**Opción 2: Desde la página de contratos**

1. Ir a "Contratos" en el menú
2. Localizar el contrato deseado
3. Click en el botón PDF (icono de documento)
4. El PDF se descarga automáticamente

**Contenido del PDF:**

- Encabezado con logo del MAGA en todas las páginas
- Título con número de contrato
- Introducción con datos completos
- 13 cláusulas del contrato
- Sección de firmas
- Numeración de páginas en pie de página

### Editar un Contrato

1. Click en el botón "Editar" (icono de lápiz)
2. Modificar los campos necesarios
3. **Para reemplazar archivos**:
   - Ver archivos actuales en la sección "Archivos Adjuntos Actuales"
   - Subir nuevo archivo en "Reemplazar o Agregar Archivos"
   - El archivo antiguo se elimina automáticamente
4. Click en "Guardar Cambios"
5. O click en "Cancelar" para descartar cambios

### Formateo Automático

- **Número de Contrato**: Se genera automáticamente `001-2026-O-M`, `002-2026-O-M`, etc.
- **DPI**: Escribe 13 dígitos → Se formatea automáticamente `0000 00000 0000`
- **Montos**: Escribe el número → Al salir del campo (Tab o click fuera) se formatea `Q96,000.00` y muestra el texto en letras

### Vista Previa de Archivos

- **Imágenes**: Se muestra la vista previa
- **PDFs**: Ícono + botón "Ver PDF" para abrir en nueva pestaña

### Búsqueda de Contratos

- Usa la tabla en el dashboard o en la página de contratos
- Escribe en el campo de búsqueda para filtrar
- Ordena por cualquier columna haciendo click en el encabezado
- Cambia el número de registros por página (10, 25, 50, 100)

## 🔒 Seguridad

- ✅ Contraseñas encriptadas con `password_hash()`
- ✅ Consultas preparadas con PDO (prevención de SQL injection)
- ✅ Validación de sesiones en todas las páginas protegidas
- ✅ Validación de archivos por tipo y tamaño
- ✅ Transacciones de base de datos para integridad
- ✅ Eliminación segura de archivos antiguos al reemplazar

## 🐛 Solución de Problemas

### Error al subir archivos

- Verificar permisos de carpeta `uploads/`
- Verificar configuración en `php.ini`:
  ```ini
  file_uploads = On
  upload_max_filesize = 20M
  post_max_size = 25M
  ```

### Error de conexión a base de datos

- Verificar que MySQL esté corriendo
- Verificar credenciales en `config/database.php`
- Verificar que la base de datos `Oirsa` exista

### Formateo de montos no funciona

- Hacer click fuera del campo (o presionar Tab) después de escribir
- Refrescar la página con Ctrl+F5 para limpiar caché
- Verificar que JavaScript esté habilitado

### Los archivos no se abren correctamente

- Verificar que la ruta del proyecto sea `/Oirsa/` en el servidor
- Verificar permisos de la carpeta `uploads/`
- Limpiar caché del navegador con Ctrl+Shift+R

### DataTables no funciona

- Verificar conexión a internet (usa CDN)
- Refrescar la página con Ctrl+F5
- Verificar consola del navegador para errores

### Error al generar PDF ✨ **NUEVO**

**"Class 'TCPDF' not found":**

- Verificar que Composer esté instalado: `composer --version`
- Instalar dependencias: `composer install`
- Verificar que existe la carpeta `vendor/`

**PDF se genera pero está en blanco:**

- Verificar que el contrato existe en la base de datos
- Revisar logs de PHP para errores
- Verificar que la imagen `assets/images/maga_logo.png` existe

**Conversiones de números/fechas incorrectas:**

- Verificar que los datos en la BD estén en el formato correcto
- Fechas deben estar en formato `Y-m-d` (2025-06-02)
- Montos deben ser numéricos (sin formato)

**Error "Failed to load PDF document":**

- Limpiar caché del navegador
- Verificar permisos de la carpeta `lib/`
- Revisar logs de errores de PHP

### Composer no está instalado ✨ **NUEVO**

**En Linux/Mac:**

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**En Windows:**

- Descargar desde: https://getcomposer.org/download/
- Ejecutar el instalador
- Reiniciar terminal/VSCode

## 📋 Registro de Cambios

### Versión 1.4.0 (Enero 2026) ✨ **NUEVO**

- ✅ **Actualización del formulario de contratos**:
  - Campo "Armonización" renombrado a "Cargo de Presupuesto" en la interfaz
  - 8 nuevas opciones de Cargo de Presupuesto (antes solo 4)
  - Nuevo campo "Término de Contratación" para personalizar texto del PDF
  - Archivo adjunto opcional "Otro" agregado
- ✅ **Mejoras en generación de PDF**:
  - Cláusula PRIMERA ahora usa texto dinámico del campo "Término de Contratación"
  - Corrección de bug: DPI con ceros iniciales ahora se convierte correctamente
  - Ejemplo: "9846 02302 6489" → "nueve mil... espacio **cero** dos mil..."
  - Texto continuo sin saltos de párrafo entre cláusulas
  - Palabras clave en negrita según especificaciones
- ✅ **Base de datos actualizada**:
  - Nueva columna `termino_contratacion` en tabla `contratos`
  - ENUM `tipo_archivo` actualizado para incluir 'otro'

### Versión 1.3.0 (Enero 2026)

- ✅ **Sistema completo de generación de PDFs**:
  - Instalación de TCPDF via Composer
  - Clase `ContratoPDF` con todas las 13 cláusulas
  - Funciones auxiliares para conversiones (números a letras, fechas a texto)
  - Endpoint `generar_pdf.php` para generación bajo demanda
- ✅ **Descarga automática de PDF** al registrar contrato
- ✅ **Botón PDF funcional** en página de contratos
- ✅ **Conversiones automáticas**:
  - Números a letras en español
  - Fechas a formato de texto
  - DPI formateado y en letras
  - Montos con formato de moneda
- ✅ **PDF con formato oficial**:
  - Encabezado con logo en todas las páginas
  - Pie de página con numeración
  - Fuente Helvetica 12pt
  - Texto justificado
  - 13 cláusulas completas del contrato
- ✅ **Estructura de carpetas** actualizada (lib/, vendor/)
- ✅ **Documentación completa** en README

### Versión 1.2.0 (Enero 2026)

- ✅ **Página de Contratos** con DataTables y acciones
- ✅ **Modal de visualización** de contratos con archivos adjuntos
- ✅ **Página de edición completa** de contratos
- ✅ **Gestión de archivos adjuntos**:
  - Visualización de archivos actuales
  - Reemplazo automático al subir nuevo archivo
  - Eliminación automática de archivos antiguos
- ✅ **Botón de cancelar** con confirmación en edición
- ✅ **Iconos de Font Awesome** en botones de acción
- ✅ **Corrección de formateo de montos** con decimales
- ✅ **Cache busting** para evitar problemas de caché
- ✅ **Normalización de rutas** de archivos
- ✅ **Reorganización de estructura** (carpeta `api/`)

### Versión 1.1.0 (Enero 2026)

- ✅ **Dashboard mejorado** con estadísticas en tiempo real
- ✅ **6 nuevos campos** en formulario de contratos
- ✅ **Auto-incremento** de número de contrato por año
- ✅ **DataTables** para búsqueda y filtrado de contratos
- ✅ **Montos por categoría** en todas las estadísticas
- ✅ **Contratos activos** calculados automáticamente por fechas
- ✅ **Total de personas** en cada categoría
- ✅ **Diseño mejorado** con gradientes y animaciones

### Versión 1.0.0 (Enero 2026)

- ✅ Sistema de autenticación completo
- ✅ Dashboard administrativo básico
- ✅ Migración de MySQLi a PDO
- ✅ Formulario de contratos con validaciones
- ✅ Sistema de subida de archivos
- ✅ Vista previa de archivos (imágenes y PDFs)
- ✅ Formateo automático de campos
- ✅ Conversión de números a letras
- ✅ Integración con SweetAlert2
- ✅ Diseño moderno y responsive

## 🚀 Próximas Funcionalidades

- [ ] Bitácora de cambios en contratos
- [ ] Eliminación de contratos con confirmación
- [ ] Firma digital en PDFs
- [ ] Envío de contratos por correo electrónico
- [ ] Módulo de gestión de usuarios
- [ ] Sistema de permisos por rol
- [ ] Gráficas estadísticas (Chart.js)
- [ ] Exportación de datos a Excel
- [ ] Notificaciones de contratos por vencer
- [ ] Búsqueda avanzada con filtros múltiples
- [ ] Carga masiva de contratos
- [ ] Plantillas personalizables de contratos
- [ ] Integración con sistema de firmas electrónicas

## 👨‍💻 Desarrollador

Sistema desarrollado para Oirsa - 2026

## 📄 Licencia

Uso interno de Oirsa. Todos los derechos reservados.
