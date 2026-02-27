# Sistema Web - AppUBA

Sistema web de gestión y administración de denuncias de maltrato animal para MAGA (Ministerio de Agricultura, Ganadería y Alimentación de Guatemala).

## 🚀 Tecnologías

- PHP 7.4+ / PHP 8.x
- MySQL/MariaDB
- HTML5 / CSS3
- JavaScript (Vanilla JS + jQuery)
- Bootstrap Icons / FontAwesome
- SweetAlert2
- DataTables
- Chart.js
- Google Maps API
- Lightbox2

## 📁 Estructura del Proyecto

```
web/
├── config/
│   ├── database.php              # Configuración de conexión a BD
│   └── workflow.php              # Configuración del workflow de denuncias
├── css/
│   ├── login.css                 # Estilos del login
│   ├── dashboard_admin.css       # Estilos del dashboard administrador
│   ├── dashboard_tecnicos.css    # Estilos UNIFICADOS para dashboards técnicos 1-5 ✅
│   ├── ver_denuncia_admin.css    # Estilos para ver denuncias
│   ├── editar_denuncia_admin.css # Estilos para editar denuncias
│   ├── servicios_admin.css       # Estilos para gestión de servicios
│   ├── noticias_admin.css        # Estilos para gestión de noticias
│   └── areas_tecnicas.css        # Estilos para áreas técnicas (workflow) ✅
├── js/
│   ├── login.js                  # Lógica del login
│   ├── dashboard_admin.js        # Lógica del dashboard administrador
│   ├── editar_denuncia_admin.js  # Lógica para editar denuncias
│   ├── servicios_admin.js        # Lógica y validaciones de servicios
│   ├── noticias_admin.js         # Lógica y validaciones de noticias
│   ├── seguimiento_denuncias.js  # Lógica para workflow de denuncias ✅
│   ├── dashboard_tecnico1.js     # Lógica del dashboard técnico 1 ✅
│   ├── dashboard_tecnico2.js     # Lógica del dashboard técnico 2 ✅
│   ├── dashboard_tecnico3.js     # Lógica del dashboard técnico 3 ✅
│   ├── dashboard_tecnico4.js     # Lógica del dashboard técnico 4 ✅
│   └── dashboard_tecnico5.js     # Lógica del dashboard técnico 5 ✅
├── includes/
│   ├── detectar_rol_navbar.php    # Helper para detectar rol y navbar ✅
│   ├── navbar_admin.php          # Navbar del administrador ✅
│   ├── navbar_tecnico1.php       # Navbar del técnico 1 ✅
│   ├── navbar_tecnico2.php       # Navbar del técnico 2 ✅
│   ├── navbar_tecnico3.php       # Navbar del técnico 3 ✅
│   ├── navbar_tecnico4.php       # Navbar del técnico 4 ✅
│   └── navbar_tecnico5.php       # Navbar del técnico 5 ✅
├── modules/
│   ├── admin/
│   │   ├── dashboard.php             # Dashboard principal ✅
│   │   ├── ver_denuncia.php          # Ver detalle completo de denuncia ✅
│   │   ├── editar_denuncia.php       # Editar denuncia ✅
│   │   ├── actualizar_denuncia.php   # Procesar actualización ✅
│   │   ├── noticias/
│   │   │   ├── index.php             # Listado de noticias con DataTables ✅
│   │   │   ├── crear.php             # Formulario crear noticia ✅
│   │   │   ├── guardar.php           # Backend guardar noticia ✅
│   │   │   ├── ver.php               # Ver detalle de la noticia ✅
│   │   │   ├── editar.php            # Formulario editar noticia ✅
│   │   │   ├── actualizar.php        # Backend actualizar noticia ✅
│   │   │   └── eliminar.php          # Eliminar noticia ✅
│   │   ├── servicios/
│   │   │   ├── index.php             # Listado de servicios con DataTables ✅
│   │   │   ├── crear.php             # Formulario crear servicio ✅
│   │   │   ├── guardar.php           # Backend guardar servicio ✅
│   │   │   ├── ver.php               # Ver detalle del servicio ✅
│   │   │   ├── editar.php            # Formulario editar servicio ✅
│   │   │   ├── actualizar.php        # Backend actualizar servicio ✅
│   │   │   └── eliminar.php          # Eliminar servicio ✅
│   │   ├── area_legal/
│   │   │   ├── index.php             # Listado de denuncias + tarjetas estadísticas ✅
│   │   │   ├── detalle_denuncia.php  # Ver detalle de denuncia ✅
│   │   │   ├── procesar.php          # Formulario de procesamiento ✅
│   │   │   └── guardar_seguimiento.php # Backend guardar seguimiento ✅
│   │   ├── area_tecnica/
│   │   │   ├── index.php             # Listado de denuncias + tarjetas estadísticas ✅
│   │   │   ├── detalle_denuncia.php  # Ver detalle de denuncia ✅
│   │   │   ├── procesar.php          # Formulario de procesamiento ✅
│   │   │   └── guardar_seguimiento.php # Backend guardar seguimiento ✅
│   │   ├── emitir_dictamen/
│   │   │   ├── index.php             # Listado de denuncias + tarjetas estadísticas ✅
│   │   │   ├── detalle_denuncia.php  # Ver detalle de denuncia ✅
│   │   │   ├── procesar.php          # Formulario de procesamiento ✅
│   │   │   └── guardar_seguimiento.php # Backend guardar seguimiento ✅
│   │   ├── opinion_legal/
│   │   │   ├── index.php             # Listado de denuncias + tarjetas estadísticas ✅
│   │   │   ├── detalle_denuncia.php  # Ver detalle de denuncia ✅
│   │   │   ├── procesar.php          # Formulario de procesamiento ✅
│   │   │   └── guardar_seguimiento.php # Backend guardar seguimiento ✅
│   │   └── resolucion_final/
│   │       ├── index.php             # Listado de denuncias + tarjetas estadísticas ✅
│   │       ├── detalle_denuncia.php  # Ver detalle de denuncia ✅
│   │       ├── procesar.php          # Formulario de procesamiento (Resolver/Rechazar) ✅
│   │       └── guardar_seguimiento.php # Backend guardar seguimiento ✅
│   ├── tecnico_1/
│   │   └── dashboard.php         # Dashboard del técnico 1 (Área Legal) ✅
│   ├── tecnico_2/
│   │   └── dashboard.php         # Dashboard del técnico 2 (Área Técnica) ✅
│   ├── tecnico_3/
│   │   └── dashboard.php         # Dashboard del técnico 3 (Emitir Dictamen) ✅
│   ├── tecnico_4/
│   │   └── dashboard.php         # Dashboard del técnico 4 (Opinión Legal) ✅
│   └── tecnico_5/
│       └── dashboard.php         # Dashboard del técnico 5 (Resolución Final) ✅
├── index.php                     # Página de inicio (redirige según rol)
├── login.php                     # Página de inicio de sesión
├── logout.php                    # Cierre de sesión
└── README.md                     # Este archivo
```

## 🔧 Instalación

### 1. Requisitos Previos

- Servidor web (Apache/Nginx)
- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3 o superior
- Acceso al backend de la aplicación móvil (comparte la misma BD)
- Google Maps API Key

### 2. Configurar la Base de Datos

```bash
# Conectar a MySQL
mysql -u root -p
```

Ejecutar el siguiente script SQL:

```sql
USE AppUBA;

-- Crear tabla de usuarios del sistema web con 6 roles
CREATE TABLE usuarios_web (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nombre_completo VARCHAR(150) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'tecnico_1', 'tecnico_2', 'tecnico_3', 'tecnico_4', 'tecnico_5') NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    INDEX idx_usuario (usuario),
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Crear tabla de servicios autorizados
CREATE TABLE servicios_autorizados (
    id_servicio INT PRIMARY KEY AUTO_INCREMENT,
    nombre_servicio VARCHAR(200) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    latitud DECIMAL(10, 8),
    longitud DECIMAL(11, 8),
    telefono VARCHAR(20) NOT NULL,
    servicios_ofrecidos TEXT NOT NULL COMMENT 'Ej: Consulta, Cirugía, Emergencias 24/7',
    calificacion DECIMAL(2, 1) DEFAULT 0.0 COMMENT 'Calificación de 0.0 a 5.0',
    total_calificaciones INT DEFAULT 0 COMMENT 'Cantidad de personas que han calificado',
    imagen_url VARCHAR(255) COMMENT 'Foto de la clínica/veterinaria',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_estado (estado),
    INDEX idx_calificacion (calificacion),
    INDEX idx_latitud_longitud (latitud, longitud),
    FOREIGN KEY (creado_por) REFERENCES usuarios_web(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Crear tabla de noticias
CREATE TABLE noticias (
    id_noticia INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    categoria ENUM('Campaña', 'Rescate', 'Legislación', 'Alerta', 'Evento', 'Otro') NOT NULL,
    descripcion_corta TEXT NOT NULL COMMENT 'Para preview en la app',
    contenido_completo TEXT NOT NULL COMMENT 'Contenido completo de la noticia',
    imagen_url VARCHAR(255) COMMENT 'Foto de la noticia',
    fecha_publicacion DATE NOT NULL,
    estado ENUM('publicada', 'borrador', 'archivada') DEFAULT 'publicada',
    prioridad ENUM('normal', 'importante', 'urgente') DEFAULT 'normal',
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_categoria (categoria),
    INDEX idx_fecha_publicacion (fecha_publicacion),
    INDEX idx_prioridad (prioridad),
    FOREIGN KEY (creado_por) REFERENCES usuarios_web(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Crear tabla de seguimiento de denuncias (WORKFLOW)
CREATE TABLE seguimiento_denuncias (
    id_seguimiento INT PRIMARY KEY AUTO_INCREMENT,
    id_denuncia INT NOT NULL,
    etapa ENUM(
        'area_legal',
        'area_tecnica',
        'emitir_dictamen',
        'opinion_legal',
        'resolucion_final'
    ) NOT NULL COMMENT 'Etapa donde se procesó',
    accion ENUM('siguiente_paso', 'rechazado', 'resuelto') NOT NULL COMMENT 'Acción tomada',
    comentario TEXT NOT NULL COMMENT 'Comentario del técnico',
    etapa_actual ENUM(
        'pendiente_revision',
        'en_area_legal',
        'en_area_tecnica',
        'en_dictamen',
        'en_opinion_legal',
        'en_resolucion_final',
        'finalizada'
    ) NOT NULL COMMENT 'Próxima etapa después de esta acción',
    procesado_por INT NOT NULL COMMENT 'ID del usuario que procesó',
    fecha_procesamiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_denuncia (id_denuncia),
    INDEX idx_etapa (etapa),
    INDEX idx_etapa_actual (etapa_actual),
    INDEX idx_accion (accion),
    INDEX idx_fecha (fecha_procesamiento),
    FOREIGN KEY (id_denuncia) REFERENCES denuncias(id_denuncia) ON DELETE CASCADE,
    FOREIGN KEY (procesado_por) REFERENCES usuarios_web(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Crear tabla de archivos de seguimiento
CREATE TABLE archivos_seguimiento (
    id_archivo INT PRIMARY KEY AUTO_INCREMENT,
    id_seguimiento INT NOT NULL,
    tipo_archivo ENUM('imagen', 'documento', 'audio', 'video') NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    tamano_bytes INT NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_seguimiento (id_seguimiento),
    INDEX idx_tipo (tipo_archivo),
    FOREIGN KEY (id_seguimiento) REFERENCES seguimiento_denuncias(id_seguimiento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar usuario administrador por defecto
INSERT INTO usuarios_web (nombre_completo, usuario, email, password, rol)
VALUES (
    'Administrador',
    'admin',
    'admin@maga.gob.gt',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);
-- Password por defecto: password123

-- Insertar usuarios técnicos de ejemplo (opcional)
INSERT INTO usuarios_web (nombre_completo, usuario, email, password, rol) VALUES
('Técnico Legal', 'tecnico1', 'tecnico1@maga.gob.gt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico_1'),
('Técnico Área Técnica', 'tecnico2', 'tecnico2@maga.gob.gt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico_2'),
('Técnico Dictamen', 'tecnico3', 'tecnico3@maga.gob.gt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico_3'),
('Técnico Opinión Legal', 'tecnico4', 'tecnico4@maga.gob.gt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico_4'),
('Técnico Resolución', 'tecnico5', 'tecnico5@maga.gob.gt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico_5');
-- Password de todos: password123

-- Insertar servicios de ejemplo (opcional)
INSERT INTO servicios_autorizados
(nombre_servicio, direccion, latitud, longitud, telefono, servicios_ofrecidos, calificacion, total_calificaciones, creado_por)
VALUES
('Clínica Veterinaria Mascota Feliz', '5ta Avenida 12-53 Zona 10, Guatemala', 14.593780, -90.513840, '2334-5678', 'Consulta, Cirugía, Emergencias 24/7', 4.8, 127, 1),
('Hospital Veterinario Pet Care', 'Boulevard Los Próceres 24-69, Zona 10', 14.589123, -90.516234, '2267-8900', 'Consulta, Laboratorio, Hospitalización', 4.9, 203, 1),
('Veterinaria San Francisco', 'Calzada Roosevelt 34-56, Zona 11', 14.613456, -90.553789, '2440-1234', 'Consulta, Vacunación, Peluquería', 4.6, 89, 1);

-- Insertar noticias de ejemplo (opcional)
INSERT INTO noticias
(titulo, categoria, descripcion_corta, contenido_completo, fecha_publicacion, estado, prioridad, creado_por)
VALUES
('Campaña de Esterilización Gratuita', 'Campaña',
 'Jornada de esterilización en zona 18 los días 5 y 6 de octubre.',
 'El Ministerio de Agricultura, Ganadería y Alimentación anuncia una jornada de esterilización gratuita para perros y gatos en la zona 18. La campaña se llevará a cabo los días 5 y 6 de octubre de 8:00 AM a 4:00 PM. Se recomienda llevar a las mascotas en ayunas.',
 '2025-09-28', 'publicada', 'importante', 1),

('Rescate Exitoso en Villa Nueva', 'Rescate',
 '15 perros fueron rescatados de condiciones deplorables.',
 'Gracias a una denuncia ciudadana, el equipo de MAGA logró rescatar 15 perros que se encontraban en condiciones deplorables en Villa Nueva. Los animales recibieron atención veterinaria inmediata y están en proceso de recuperación. Se busca hogar responsable para ellos.',
 '2025-09-25', 'publicada', 'normal', 1),

('Nueva Ley de Protección Animal', 'Legislación',
 'Se aprueba reforma que aumenta penas por maltrato animal.',
 'El Congreso de la República aprobó una reforma al Código Penal que aumenta las penas por maltrato animal. Las nuevas sanciones incluyen multas de hasta Q50,000 y penas de cárcel de hasta 5 años para casos graves. La ley entrará en vigencia el próximo mes.',
 '2025-09-20', 'publicada', 'urgente', 1);
```

### 3. Crear carpetas de uploads

```bash
# Crear carpetas para imágenes
mkdir -p /var/www/html/AppUBA/backend/uploads/servicios
mkdir -p /var/www/html/AppUBA/backend/uploads/noticias
mkdir -p /var/www/html/AppUBA/backend/uploads/seguimiento

# Dar permisos
chmod -R 755 /var/www/html/AppUBA/backend/uploads/servicios
chmod -R 755 /var/www/html/AppUBA/backend/uploads/noticias
chmod -R 755 /var/www/html/AppUBA/backend/uploads/seguimiento
chown -R www-data:www-data /var/www/html/AppUBA/backend/uploads/servicios
chown -R www-data:www-data /var/www/html/AppUBA/backend/uploads/noticias
chown -R www-data:www-data /var/www/html/AppUBA/backend/uploads/seguimiento
```

### 4. Configurar Conexión a Base de Datos

```bash
# Editar con tus credenciales
nano config/database.php
```

Actualizar estas líneas:

```php
private $host = "localhost";
private $db_name = "AppUBA";
private $username = "root";
private $password = "TU_CONTRASEÑA_AQUI";
```

### 5. Configurar Google Maps API Key

Editar los archivos que usan Google Maps y reemplazar la API Key:

- `modules/admin/ver_denuncia.php`
- `modules/admin/editar_denuncia.php`
- `modules/admin/servicios/crear.php`
- `modules/admin/servicios/ver.php`
- `modules/admin/servicios/editar.php`
- `modules/admin/area_legal/detalle_denuncia.php`
- `modules/admin/area_tecnica/detalle_denuncia.php`
- `modules/admin/emitir_dictamen/detalle_denuncia.php`
- `modules/admin/opinion_legal/detalle_denuncia.php`
- `modules/admin/resolucion_final/detalle_denuncia.php`

## 🔐 Sistema de Roles

El sistema cuenta con **6 roles diferentes**, cada uno con permisos y funciones específicas:

### 1. **Administrador** (`admin`) ✅ 100% COMPLETO

- ✅ Acceso completo al sistema
- ✅ Gestión de denuncias (ver, editar, cambiar estado)
- ✅ Gestión de servicios autorizados (CRUD completo)
- ✅ Gestión de noticias para la app móvil (CRUD completo)
- ✅ Acceso a todas las 5 áreas técnicas del workflow
- ✅ Dashboard con estadísticas completas y gráficos
- ✅ Tarjetas de estadísticas en cada área técnica
- ✅ Historial completo de seguimiento en cada área
- ✅ Procesar denuncias en cualquier etapa
- ✅ Ver detalle completo de denuncias
- ✅ Exportar reportes (Excel, PDF, Imprimir)

### 2. **Técnico Área Legal** (`tecnico_1`) ✅ 100% COMPLETO

- ✅ Dashboard personalizado con denuncias de su área
- ✅ Revisión legal de denuncias pendientes
- ✅ Primera etapa del workflow
- ✅ Aprobar o rechazar denuncias
- ✅ Agregar comentarios y archivos adjuntos
- ✅ Ver historial de seguimiento
- ✅ 4 tarjetas de estadísticas personalizadas
- ✅ DataTables con exportación (Excel, PDF, Copiar, Imprimir)
- ✅ Navbar personalizado con tema azul (#3b82f6)
- ✅ Acceso a `detalle_denuncia.php` y `procesar.php` del área legal
- ✅ Redirección automática a su dashboard al iniciar sesión
- ✅ Navegación dinámica (vuelve a su dashboard, no al del admin)

### 3. **Técnico Área Técnica** (`tecnico_2`) ✅ 100% COMPLETO

- ✅ Dashboard personalizado con casos de su área
- ✅ Evaluación técnica de casos aprobados por Área Legal
- ✅ Segunda etapa del workflow
- ✅ Inspecciones de campo
- ✅ Informes técnicos con archivos
- ✅ Ver historial de seguimiento
- ✅ 4 tarjetas de estadísticas personalizadas
- ✅ DataTables con exportación (Excel, PDF, Copiar, Imprimir)
- ✅ Navbar personalizado con tema verde (#10b981)
- ✅ Acceso a `detalle_denuncia.php` y `procesar.php` del área técnica
- ✅ Redirección automática a su dashboard al iniciar sesión
- ✅ Navegación dinámica (vuelve a su dashboard, no al del admin)

### 4. **Técnico Emitir Dictamen** (`tecnico_3`) ✅ 100% COMPLETO

- ✅ Dashboard personalizado con casos de su área
- ✅ Emisión de dictámenes técnicos
- ✅ Tercera etapa del workflow
- ✅ Análisis de casos con documentación
- ✅ Resoluciones preliminares
- ✅ Ver historial de seguimiento
- ✅ 4 tarjetas de estadísticas personalizadas
- ✅ DataTables con exportación (Excel, PDF, Copiar, Imprimir)
- ✅ Navbar personalizado con tema naranja (#f59e0b)
- ✅ Acceso a `detalle_denuncia.php` y `procesar.php` de emitir dictamen
- ✅ Redirección automática a su dashboard al iniciar sesión
- ✅ Navegación dinámica (vuelve a su dashboard, no al del admin)

### 5. **Técnico Opinión Legal** (`tecnico_4`) ✅ 100% COMPLETO

- ✅ Dashboard personalizado con casos de su área
- ✅ Opiniones legales especializadas
- ✅ Cuarta etapa del workflow
- ✅ Revisión de procedimientos legales
- ✅ Asesoría legal avanzada
- ✅ Ver historial de seguimiento
- ✅ 4 tarjetas de estadísticas personalizadas
- ✅ DataTables con exportación (Excel, PDF, Copiar, Imprimir)
- ✅ Navbar personalizado con tema morado (#8b5cf6)
- ✅ Acceso a `detalle_denuncia.php` y `procesar.php` de opinión legal
- ✅ Redirección automática a su dashboard al iniciar sesión
- ✅ Navegación dinámica (vuelve a su dashboard, no al del admin)

### 6. **Técnico Resolución Final** (`tecnico_5`) ✅ 100% COMPLETO

- ✅ Dashboard personalizado con casos de su área
- ✅ Resoluciones finales de casos
- ✅ Quinta y última etapa del workflow
- ✅ Cierre de expedientes
- ✅ Emisión de documentos oficiales
- ✅ Opción de "Resolver" o "Rechazar"
- ✅ Ver historial de seguimiento
- ✅ 4 tarjetas de estadísticas personalizadas
- ✅ DataTables con exportación (Excel, PDF, Copiar, Imprimir)
- ✅ Navbar personalizado con tema rojo (#ef4444)
- ✅ Acceso a `detalle_denuncia.php` y `procesar.php` de resolución final
- ✅ Redirección automática a su dashboard al iniciar sesión
- ✅ Navegación dinámica (vuelve a su dashboard, no al del admin)

### 🎨 Modernización de Dashboards Técnicos (Glassmorphism Design) ✅ COMPLETO

**Fecha de Implementación:** Enero 2026

Todos los dashboards técnicos (técnico_1 a técnico_5) fueron modernizados con un diseño glassmorphism idéntico al dashboard del administrador, garantizando una experiencia visual consistente y profesional en todo el sistema.

#### Características del Diseño Modernizado:

**1. Sidebar Lateral (280px)**

- ✅ Logo azul con gradiente (#3b82f6 → #2563eb)
- ✅ Navegación con efectos hover
- ✅ Avatar de usuario circular
- ✅ Botón de logout rojo con gradiente
- ✅ Diseño idéntico al sidebar del admin

**2. Topbar Superior (70px)**

- ✅ Breadcrumb con icono
- ✅ Fecha y hora en tiempo real
- ✅ Fondo glassmorphism
- ✅ Mismo diseño que el admin

**3. Tarjetas de Estadísticas**

- ✅ **Fondo de color completo** (no solo el icono):
  - 🔵 Azul (#3b82f6): Pendientes en mi área
  - 🔴 Rojo (#ef4444): Urgentes (+5 días)
  - 🟢 Verde (#10b981): Procesadas hoy
  - 🟡 Amarillo (#fbbf24): Sin revisar
- ✅ **Texto blanco** en todas las tarjetas
- ✅ Iconos con fondo semi-transparente blanco
- ✅ Números grandes (3.5rem)
- ✅ Animaciones de entrada escalonadas

**4. DataTables Moderno**

- ✅ **Botones de exportación** con gradiente azul:
  - Excel, PDF, Copiar, Imprimir
  - Clase `btn-dt` con efectos hover
- ✅ **Campo de búsqueda** con borde redondeado
- ✅ **Selector de cantidad** personalizado
- ✅ **Paginación** con botones modernos
- ✅ **Scroll horizontal** para ver todas las columnas
- ✅ Iconos de ordenamiento con Font Awesome

#### Archivos Consolidados:

**CSS Unificado:**

- ✅ `dashboard_tecnicos.css` - Archivo CSS único para todos los dashboards técnicos
- ❌ Eliminados: `dashboard_tecnico1.css` a `dashboard_tecnico5.css` (obsoletos)

**Navbars Modernizados:**

- ✅ `navbar_tecnico1.php` a `navbar_tecnico5.php` - Sidebar lateral idéntico al admin

**Dashboards Actualizados:**

- ✅ `tecnico_1/dashboard.php` - Área Legal
- ✅ `tecnico_2/dashboard.php` - Área Técnica
- ✅ `tecnico_3/dashboard.php` - Emitir Dictamen
- ✅ `tecnico_4/dashboard.php` - Opinión Legal
- ✅ `tecnico_5/dashboard.php` - Resolución Final

**JavaScript Optimizado:**

- ✅ `dashboard_tecnico1.js` a `dashboard_tecnico5.js`
- ✅ Configuración `scrollX: true` para scroll horizontal
- ✅ Clase `btn-dt` para botones de exportación

#### Problemas Solucionados:

1. **CSS Responsive Conflictivo** - Eliminado `responsive.dataTables.min.css` que causaba colapso de columnas
2. **Clases de Tarjetas Incorrectas** - Cambiadas de `stat-primary/danger/success/warning` a `total/rejected/resolved/pending`
3. **Contenedor Incorrecto** - Cambiado de `areas-container` a `dashboard-container` en técnicos 3, 4 y 5
4. **Logout Success Message** - Limpieza de parámetro URL después de mostrar mensaje de sesión cerrada

#### Resultado Final:

✅ **Diseño 100% Idéntico al Admin**

- Sidebar lateral (280px) con logo azul
- Topbar superior (70px) con fecha/hora
- Tarjetas con fondo de color completo
- Texto blanco en todas las tarjetas
- Botones de exportación con gradiente azul
- Scroll horizontal en DataTables
- Mismo layout que el admin
- Mismos colores exactos
- Mismas animaciones y efectos hover

### 🎨 Características Comunes de Dashboards Técnicos

Todos los dashboards técnicos incluyen:

- **Filtrado Automático**: Cada técnico ve únicamente las denuncias de su etapa del workflow
- **4 Tarjetas de Estadísticas**:
  1. Pendientes en mi área
  2. Urgentes (+5 días sin procesar)
  3. Procesadas hoy por el usuario
  4. Sin revisar (sin seguimiento)
- **DataTables Avanzado**:
  - Búsqueda en tiempo real
  - Ordenamiento por columnas
  - Paginación
  - Exportación a Excel, PDF, Copiar, Imprimir
  - Responsive design
- **Botones de Acción**:
  - **Ver detalle**: Redirige a `detalle_denuncia.php` del área correspondiente
  - **Procesar**: Redirige a `procesar.php` del área correspondiente
- **Navbars Personalizados**:
  - Logo de AppUBA - MAGA
  - Un solo enlace: "Mi Dashboard"
  - Información del usuario con nombre y rol específico
  - Botón de cerrar sesión con confirmación SweetAlert
  - Tema de color único por rol
- **Sistema de Navegación Dinámico**:
  - Archivo helper `detectar_rol_navbar.php` detecta automáticamente el rol del usuario
  - Muestra el navbar correcto según el rol (no el del admin)
  - Los botones "Volver" y "Cancelar" redirigen al dashboard del técnico (no al del admin)
  - Reutiliza archivos existentes del admin (`detalle_denuncia.php`, `procesar.php`, `guardar_seguimiento.php`)

**Nota:** Todos los roles (ADMINISTRADOR y TÉCNICOS 1-5) están 100% COMPLETOS.

## 🔄 Sistema de Workflow de Denuncias ✅ COMPLETO

### Flujo de Estados:

```
1. Pendiente (cuando se crea desde app móvil)
   ↓
2. En Área Legal → Aprobar o Rechazar
   ↓ (si aprueba)
3. En Área Técnica → Aprobar o Rechazar
   ↓ (si aprueba)
4. En Dictamen → Aprobar o Rechazar
   ↓ (si aprueba)
5. En Opinión Legal → Aprobar o Rechazar
   ↓ (si aprueba)
6. En Resolución Final → Resolver o Rechazar
   ↓
7. Resuelta o Rechazada (estados finales)
```

### Características del Workflow ✅ TODAS IMPLEMENTADAS:

- ✅ **5 áreas técnicas completas**: Área Legal, Área Técnica, Emitir Dictamen, Opinión Legal, Resolución Final
- ✅ **Tarjetas de estadísticas**: Cada área muestra 4 tarjetas con contadores específicos
- ✅ **Seguimiento completo**: Cada acción queda registrada con comentario, usuario y fecha
- ✅ **Archivos adjuntos**: En cada etapa se pueden subir imágenes, PDFs, documentos, audio o video
- ✅ **Historial visible**: Timeline completo en formulario de procesamiento
- ✅ **Estados automáticos**: El estado de la denuncia se actualiza automáticamente según la acción
- ✅ **Transacciones SQL**: Garantiza integridad de datos
- ✅ **Validaciones**: Comentario obligatorio (mínimo 20 caracteres)
- ✅ **Archivos validados**: Máximo 10MB, tipos permitidos verificados
- ✅ **Ver detalle completo**: Botón "Ver detalle" funcional en todas las áreas
- ✅ **Procesar denuncias**: Formulario completo con drag & drop de archivos
- ✅ **DataTables avanzado**: Búsqueda, ordenamiento, paginación, exportación

### Tarjetas de Estadísticas por Área:

**Área Legal:**

1. Total en esta Etapa
2. Procesadas Hoy
3. Pendientes de Revisión
4. Rechazadas en esta Etapa

**Área Técnica:**

1. Total en esta Etapa
2. Procesadas Hoy
3. Pendientes de Revisión
4. Rechazadas en esta Etapa

**Emitir Dictamen:**

1. Total en esta Etapa
2. Dictámenes Emitidos Hoy
3. Pendientes de Dictamen
4. Rechazadas en esta Etapa

**Opinión Legal:**

1. Total en esta Etapa
2. Opiniones Emitidas Hoy
3. Pendientes de Opinión
4. Rechazadas en esta Etapa

**Resolución Final:**

1. Total en esta Etapa
2. Resueltas Hoy
3. Pendientes de Resolver
4. Rechazadas en esta Etapa

### Tabla: `seguimiento_denuncias`

```sql
Campos:
- id_seguimiento (PK, AUTO_INCREMENT)
- id_denuncia (FK a denuncias)
- etapa (ENUM) - Etapa donde se procesó
- accion (ENUM) - siguiente_paso, rechazado, resuelto
- comentario (TEXT) - Obligatorio, mínimo 20 caracteres
- etapa_actual (ENUM) - Próxima etapa después de esta acción
- procesado_por (FK a usuarios_web)
- fecha_procesamiento (TIMESTAMP)
```

### Tabla: `archivos_seguimiento`

```sql
Campos:
- id_archivo (PK, AUTO_INCREMENT)
- id_seguimiento (FK a seguimiento_denuncias)
- tipo_archivo (ENUM) - imagen, documento, audio, video
- nombre_archivo (VARCHAR 255)
- ruta_archivo (VARCHAR 255)
- tamano_bytes (INT)
- fecha_subida (TIMESTAMP)
```

## 🎨 Características del Sistema

### Login ✅

- ✅ Autenticación con usuario y contraseña (no email)
- ✅ Validación en tiempo real
- ✅ Mostrar/ocultar contraseña
- ✅ Mensajes de error con SweetAlert2
- ✅ Redirección automática según rol
- ✅ Diseño responsive con gradientes
- ✅ Conversión automática de usuario a minúsculas
- ✅ Prevención de espacios en usuario

### Dashboard Administrador ✅

- ✅ 5 tarjetas de estadísticas con íconos
- ✅ Gráfico de dona interactivo con Chart.js
- ✅ DataTables con funcionalidades avanzadas
- ✅ Botones de acción: Ver detalle y Editar
- ✅ Diseño moderno con animaciones

### Gestión de Denuncias ✅

**Ver Denuncia:**

- ✅ Información completa del denunciante
- ✅ Galería de fotos con Lightbox2
- ✅ Mapa interactivo de Google Maps
- ✅ Lista de infracciones con badges
- ✅ Archivos adjuntos con descarga
- ✅ Botones: Volver, Editar, Imprimir

**Editar Denuncia:**

- ✅ Formulario completo editable
- ✅ Cambiar estado de denuncia
- ✅ Mapa con marcador draggable
- ✅ Actualización de coordenadas automática
- ✅ Validaciones en tiempo real
- ✅ Confirmaciones con SweetAlert
- ✅ Transacciones SQL

### Áreas Técnicas (Workflow) ✅ TODAS COMPLETAS

**Listado de Denuncias por Área:**

- ✅ 4 tarjetas de estadísticas específicas por área
- ✅ DataTables con búsqueda, ordenamiento, paginación
- ✅ Exportar a Excel, PDF, Copiar, Imprimir
- ✅ Filtrado automático por etapa del workflow
- ✅ Badges de estado y etapa actual
- ✅ **Columna "Días Pendientes"** con indicadores visuales:
  - 🟢 Badge verde: Denuncias de 1-5 días (timeframe normal)
  - 🔴 Badge rojo: Denuncias de más de 5 días (urgentes)
  - Cálculo automático desde la fecha de denuncia
  - Integrado con ordenamiento y exportación de DataTables
- ✅ Botones: Ver detalle y Procesar
- ✅ Diseño moderno con tema claro

**Ver Detalle de Denuncia:**

- ✅ Información completa del denunciante
- ✅ Galería de fotos con Lightbox2
- ✅ Mapa interactivo de Google Maps
- ✅ Lista de infracciones con badges
- ✅ Archivos adjuntos con descarga
- ✅ Historial completo de seguimiento (timeline)
- ✅ Botones: Volver y Procesar
- ✅ Diseño limpio y profesional

**Procesar Denuncia:**

- ✅ Información básica de la denuncia
- ✅ Historial completo de seguimiento (timeline)
- ✅ Formulario de comentario (obligatorio, min 20 caracteres)
- ✅ Contador de caracteres en tiempo real
- ✅ Subida múltiple de archivos (opcional)
- ✅ Drag & Drop de archivos
- ✅ Preview y validación de archivos
- ✅ Botones según etapa:
  - Área Legal a Opinión Legal: "Siguiente Paso" y "Rechazar"
  - Resolución Final: "Resolver" y "Rechazar"
- ✅ Confirmación con SweetAlert antes de procesar
- ✅ Mensajes de éxito/error

**Historial de Seguimiento:**

- ✅ Timeline visual con todas las acciones
- ✅ Badges de color según acción (siguiente_paso=verde, rechazado=rojo, resuelto=azul)
- ✅ Información completa: etapa, acción, usuario, fecha, comentario
- ✅ Archivos adjuntos por etapa
- ✅ Diseño limpio y fácil de seguir

### Gestión de Servicios Autorizados ✅ COMPLETO

**Listado de Servicios:**

- ✅ DataTables con búsqueda, ordenamiento, paginación
- ✅ Exportar a Excel, PDF, Copiar, Imprimir
- ✅ Badges de estado (Activo/Inactivo)
- ✅ Calificación con estrellas y total de calificaciones
- ✅ Botones de acción: Ver, Editar, Eliminar
- ✅ SweetAlert en todos los botones
- ✅ Diseño responsive y moderno

**Crear Servicio:**

- ✅ Formulario completo con validaciones
- ✅ Campo nombre de clínica/veterinaria
- ✅ Campo teléfono con formato automático: `1234-5678`
- ✅ Campo dirección (textarea)
- ✅ Campo servicios ofrecidos (textarea libre)
- ✅ Google Maps con buscador de direcciones integrado
- ✅ Marcador draggable para ajustar ubicación
- ✅ Captura automática de coordenadas GPS
- ✅ Subida de imagen opcional (JPG, PNG, max 2MB)
- ✅ Preview de imagen antes de subir
- ✅ Selector de estado (Activo/Inactivo)
- ✅ Validaciones cliente y servidor
- ✅ Confirmaciones con SweetAlert2

**Ver Servicio:**

- ✅ Vista detallada completa con toda la información
- ✅ Mapa de Google Maps con marcador
- ✅ InfoWindow con datos del servicio al hacer clic
- ✅ Calificación con estrellas
- ✅ Imagen del servicio (si tiene)
- ✅ Fecha de creación y usuario creador
- ✅ Botones: Volver, Editar, Imprimir
- ✅ Diseño limpio y profesional

**Editar Servicio:**

- ✅ Formulario pre-cargado con datos actuales
- ✅ Mapa con ubicación actual
- ✅ Marcador draggable para ajustar ubicación
- ✅ Buscador de direcciones en el mapa
- ✅ Cambiar imagen (opcional, mantiene la anterior si no se cambia)
- ✅ Preview de imagen nueva
- ✅ Muestra imagen actual
- ✅ Formato de teléfono automático
- ✅ Validaciones en tiempo real
- ✅ Confirmación antes de guardar
- ✅ Transacciones SQL

**Eliminar Servicio:**

- ✅ Confirmación con SweetAlert antes de eliminar
- ✅ Elimina registro de la base de datos
- ✅ Elimina imagen del servidor automáticamente
- ✅ Transacciones SQL (rollback en caso de error)
- ✅ Mensajes de éxito/error

### Gestión de Noticias ✅ COMPLETO

**Listado de Noticias:**

- ✅ DataTables con búsqueda, ordenamiento, paginación
- ✅ Exportar a Excel, PDF, Copiar, Imprimir
- ✅ Badges de categoría (Campaña, Rescate, Legislación, Alerta, Evento, Otro)
- ✅ Badges de estado (Publicada, Borrador, Archivada)
- ✅ Badges de prioridad (Normal, Importante, Urgente)
- ✅ Botones de acción: Ver, Editar, Eliminar
- ✅ SweetAlert en todos los botones
- ✅ Diseño responsive con tema claro

**Crear Noticia:**

- ✅ Formulario completo con validaciones
- ✅ Campo título (mínimo 10 caracteres)
- ✅ Selector de categoría (6 opciones)
- ✅ Campo descripción corta para preview (mínimo 20 caracteres, máx 500)
- ✅ Campo contenido completo (mínimo 50 caracteres)
- ✅ Selector de fecha de publicación
- ✅ Selector de estado (Publicada/Borrador/Archivada)
- ✅ Selector de prioridad (Normal/Importante/Urgente)
- ✅ Subida de imagen opcional (JPG, PNG, WEBP, max 2MB)
- ✅ Preview de imagen antes de subir
- ✅ Contador de caracteres en tiempo real
- ✅ Validaciones cliente y servidor
- ✅ Confirmación con SweetAlert antes de crear

**Ver Noticia:**

- ✅ Vista detallada completa con toda la información
- ✅ Imagen de la noticia (si tiene)
- ✅ Badges de categoría, estado y prioridad
- ✅ Título, descripción corta y contenido completo
- ✅ Fecha de publicación y creación
- ✅ Usuario creador
- ✅ Fecha de última modificación
- ✅ Botones: Volver, Editar, Imprimir
- ✅ Diseño limpio y profesional

**Editar Noticia:**

- ✅ Formulario pre-cargado con datos actuales
- ✅ Todos los campos editables
- ✅ Cambiar imagen (opcional, mantiene la anterior si no se cambia)
- ✅ Preview de nueva imagen
- ✅ Muestra imagen actual
- ✅ Contador de caracteres en tiempo real
- ✅ Validaciones en tiempo real
- ✅ Confirmación antes de actualizar
- ✅ Confirmación al cancelar edición
- ✅ Transacciones SQL

**Eliminar Noticia:**

- ✅ Confirmación con SweetAlert antes de eliminar
- ✅ Elimina registro de la base de datos
- ✅ Elimina imagen del servidor automáticamente
- ✅ Transacciones SQL (rollback en caso de error)
- ✅ Mensajes de éxito/error

### Navbar Administrador ✅

- ✅ Menú: Dashboard, Noticias, Servicios
- ✅ Menú dropdown "Áreas Técnicas" con 5 opciones
- ✅ Información del usuario
- ✅ Botón cerrar sesión con confirmación SweetAlert
- ✅ Rutas absolutas (funciona desde cualquier carpeta)
- ✅ Animaciones y efectos hover
- ✅ Responsive

### Seguridad ✅

- ✅ Passwords hasheados con bcrypt
- ✅ Sesiones PHP seguras
- ✅ Verificación de rol en cada página
- ✅ Protección SQL Injection (PDO prepared statements)
- ✅ Validación de formularios cliente/servidor
- ✅ Transacciones SQL para operaciones críticas
- ✅ Validación de archivos subidos
- ✅ Sanitización de inputs
- ✅ Control de tamaño de archivos (max 10MB)
- ✅ Validación de tipos MIME

## 📊 Gestión de Servicios Autorizados

### Tabla: `servicios_autorizados`

```sql
Campos:
- id_servicio (PK, AUTO_INCREMENT)
- nombre_servicio (VARCHAR 200) - Nombre de la clínica/veterinaria
- direccion (VARCHAR 255) - Dirección completa
- latitud (DECIMAL 10,8) - Coordenada GPS
- longitud (DECIMAL 11,8) - Coordenada GPS
- telefono (VARCHAR 20) - Formato: 1234-5678
- servicios_ofrecidos (TEXT) - Descripción de servicios
- calificacion (DECIMAL 2,1) - Promedio 0.0 a 5.0
- total_calificaciones (INT) - Cantidad de calificaciones
- imagen_url (VARCHAR 255) - Ruta de la foto
- estado (ENUM: activo, inactivo)
- fecha_creacion (TIMESTAMP)
- fecha_modificacion (TIMESTAMP)
- creado_por (INT, FK a usuarios_web)
```

### Funcionalidades para App Móvil

Los servicios creados en la web se mostrarán en la app móvil con:

- ✅ Nombre del servicio
- ✅ Calificación con estrellas
- ✅ Dirección
- ✅ Teléfono
- ✅ Servicios ofrecidos
- ✅ Botón "Llamar" (abre dialer)
- ✅ Botón "Ubicación" (abre Google Maps)
- ✅ Buscador de servicios
- ⏳ Sistema de calificación (pendiente implementar en app)

## 📰 Gestión de Noticias

### Tabla: `noticias`

```sql
Campos:
- id_noticia (PK, AUTO_INCREMENT)
- titulo (VARCHAR 200) - Título de la noticia
- categoria (ENUM) - Campaña, Rescate, Legislación, Alerta, Evento, Otro
- descripcion_corta (TEXT) - Preview para app móvil (máx 500 caracteres)
- contenido_completo (TEXT) - Contenido detallado
- imagen_url (VARCHAR 255) - Ruta de la foto
- fecha_publicacion (DATE) - Fecha de publicación
- estado (ENUM) - publicada, borrador, archivada
- prioridad (ENUM) - normal, importante, urgente
- creado_por (INT, FK a usuarios_web)
- fecha_creacion (TIMESTAMP)
- fecha_modificacion (TIMESTAMP)
```

### Funcionalidades para App Móvil

Las noticias creadas en la web se mostrarán en la app móvil con:

- ✅ Título de la noticia
- ✅ Categoría con badge
- ✅ Descripción corta (preview)
- ✅ Fecha de publicación
- ✅ Imagen (si tiene)
- ✅ Prioridad (para destacar noticias importantes/urgentes)
- ✅ Contenido completo al abrir la noticia
- ✅ Solo se muestran noticias con estado "publicada"

## 🌐 URLs del Sistema

### Producción

```
http://159.65.168.91/AppUBA/web/
```

### Módulo de Servicios

```
/web/modules/admin/servicios/index.php         # Listado
/web/modules/admin/servicios/crear.php         # Crear nuevo
/web/modules/admin/servicios/ver.php?id=X      # Ver detalle
/web/modules/admin/servicios/editar.php?id=X   # Editar
/web/modules/admin/servicios/eliminar.php?id=X # Eliminar
```

### Módulo de Noticias

```
/web/modules/admin/noticias/index.php         # Listado
/web/modules/admin/noticias/crear.php         # Crear nueva
/web/modules/admin/noticias/ver.php?id=X      # Ver detalle
/web/modules/admin/noticias/editar.php?id=X   # Editar
/web/modules/admin/noticias/eliminar.php?id=X # Eliminar
```

### Módulos de Áreas Técnicas

```
/web/modules/admin/area_legal/index.php                  # Área Legal
/web/modules/admin/area_legal/detalle_denuncia.php?id=X  # Ver detalle
/web/modules/admin/area_legal/procesar.php?id=X          # Procesar

/web/modules/admin/area_tecnica/index.php                # Área Técnica
/web/modules/admin/area_tecnica/detalle_denuncia.php?id=X
/web/modules/admin/area_tecnica/procesar.php?id=X

/web/modules/admin/emitir_dictamen/index.php            # Emitir Dictamen
/web/modules/admin/emitir_dictamen/detalle_denuncia.php?id=X
/web/modules/admin/emitir_dictamen/procesar.php?id=X

/web/modules/admin/opinion_legal/index.php              # Opinión Legal
/web/modules/admin/opinion_legal/detalle_denuncia.php?id=X
/web/modules/admin/opinion_legal/procesar.php?id=X

/web/modules/admin/resolucion_final/index.php           # Resolución Final
/web/modules/admin/resolucion_final/detalle_denuncia.php?id=X
/web/modules/admin/resolucion_final/procesar.php?id=X
```

## 🔑 Credenciales por Defecto

**Usuario Administrador:**

- **Usuario:** `admin`
- **Contraseña:** `password123`

**Usuarios Técnicos (todos con la misma contraseña):**

- **Usuario:** `tecnico1` / `tecnico2` / `tecnico3` / `tecnico4` / `tecnico5`
- **Contraseña:** `password123`

⚠️ **IMPORTANTE:** Cambiar estas credenciales en producción.

## 📦 Librerías Externas Utilizadas

### CSS

- [FontAwesome 6.x](https://fontawesome.com/) - Iconos
- [DataTables 1.13.6](https://datatables.net/) - Tablas interactivas
- [SweetAlert2](https://sweetalert2.github.io/) - Alertas modales
- [Lightbox2 2.11.4](https://lokeshdhakar.com/projects/lightbox2/) - Galería de imágenes

### JavaScript

- [jQuery 3.7.0](https://jquery.com/) - Requerido por DataTables
- [Chart.js 4.x](https://www.chartjs.org/) - Gráficos
- [DataTables 1.13.6](https://datatables.net/) - Tablas
- [SweetAlert2](https://sweetalert2.github.io/) - Alertas
- [Google Maps API](https://developers.google.com/maps) - Mapas interactivos
- [Google Places API](https://developers.google.com/maps/documentation/places) - Búsqueda de direcciones

### Plugins de DataTables

- DataTables Responsive
- DataTables Buttons
- JSZip (Excel export)
- pdfMake (PDF export)

## 📱 Integración con App Móvil

El sistema web **comparte la misma base de datos** con la aplicación móvil AppUBA Flutter:

- ✅ Denuncias creadas en app aparecen en web
- ✅ Cambios de estado en web se reflejan en app
- ✅ Workflow de denuncias procesa casos de la app
- ✅ Servicios autorizados creados en web se muestran en app
- ✅ Noticias creadas en web se muestran en app
- ⏳ Sistema de calificaciones de servicios desde app (pendiente)

**Tablas compartidas:**

- `denuncias` - Denuncias de maltrato animal
- `infracciones_denuncia` - Tipos de infracción
- `evidencias_denuncia` - Fotos y archivos
- `servicios_autorizados` - Clínicas/veterinarias
- `noticias` - Noticias para usuarios de la app

**Tablas exclusivas del sistema web:**

- `usuarios_web` - Usuarios administrativos (6 roles)
- `seguimiento_denuncias` - Historial de workflow
- `archivos_seguimiento` - Archivos del workflow

**Backend compartido:**

- `/AppUBA/backend/uploads/dpi/` - Fotos de DPI
- `/AppUBA/backend/uploads/fachadas/` - Fotos de fachadas
- `/AppUBA/backend/uploads/evidencias/` - Evidencias
- `/AppUBA/backend/uploads/servicios/` - Imágenes de servicios
- `/AppUBA/backend/uploads/noticias/` - Imágenes de noticias
- `/AppUBA/backend/uploads/seguimiento/` - Archivos del workflow

## 🚀 Funcionalidades Implementadas

### ✅ Sistema Completo de Denuncias

- [x] Dashboard con estadísticas y gráficos
- [x] DataTables con exportación
- [x] Ver denuncia completa
- [x] Editar denuncia con validaciones
- [x] Actualizar estado
- [x] Mapa interactivo
- [x] Descargar archivos adjuntos
- [x] Imprimir denuncias

### ✅ Sistema de Workflow de Denuncias - 100% COMPLETO

- [x] 5 áreas técnicas completas (Área Legal, Área Técnica, Emitir Dictamen, Opinión Legal, Resolución Final)
- [x] Tarjetas de estadísticas en cada área (4 tarjetas por área)
- [x] Flujo secuencial de aprobación
- [x] Historial completo de seguimiento
- [x] Comentarios obligatorios en cada etapa
- [x] Subida de archivos por etapa
- [x] Estados automáticos según acción
- [x] Timeline visual del historial
- [x] Filtrado automático por etapa
- [x] Ver detalle completo de denuncias
- [x] Procesar denuncias con formulario completo
- [x] Transacciones SQL
- [x] Validaciones completas
- [x] DataTables con exportación en todas las áreas
- [x] SweetAlert en todas las confirmaciones

### ✅ Sistema Completo de Servicios Autorizados

- [x] Listado con DataTables (búsqueda, ordenar, exportar)
- [x] Crear servicio con mapa y búsqueda de direcciones
- [x] Ver detalle completo del servicio
- [x] Editar servicio con mapa interactivo
- [x] Eliminar servicio (con confirmación)
- [x] Subida y gestión de imágenes
- [x] Formato automático de teléfono (1234-5678)
- [x] Captura automática de coordenadas GPS
- [x] Sistema de calificaciones (preparado para app)
- [x] Estados activo/inactivo
- [x] Validaciones completas
- [x] SweetAlert en todos los botones
- [x] Responsive design

### ✅ Sistema Completo de Noticias

- [x] Listado con DataTables (búsqueda, ordenar, exportar)
- [x] Crear noticia con todas las validaciones
- [x] Ver detalle completo de la noticia
- [x] Editar noticia con confirmaciones
- [x] Eliminar noticia (con confirmación)
- [x] Subida y gestión de imágenes
- [x] Categorías: Campaña, Rescate, Legislación, Alerta, Evento, Otro
- [x] Estados: Publicada, Borrador, Archivada
- [x] Prioridades: Normal, Importante, Urgente
- [x] Contador de caracteres en tiempo real
- [x] Preview de imagen
- [x] Validaciones completas
- [x] SweetAlert en todos los botones
- [x] Responsive design con tema claro

### ✅ Sistema de Usuarios y Roles

- [x] 6 roles diferenciados (admin + 5 técnicos)
- [x] Login con usuario y contraseña
- [x] Verificación de permisos por rol
- [x] Logout con confirmación SweetAlert
- [x] Navbar con rutas absolutas
- [x] ROL ADMINISTRADOR 100% COMPLETO
- [x] ROL TÉCNICO_1 100% COMPLETO (Área Legal)
- [x] ROL TÉCNICO_2 100% COMPLETO (Área Técnica)
- [x] ROL TÉCNICO_3 100% COMPLETO (Emitir Dictamen)
- [x] ROL TÉCNICO_4 100% COMPLETO (Opinión Legal)
- [x] ROL TÉCNICO_5 100% COMPLETO (Resolución Final)

### ⏳ En Desarrollo

- [ ] Dashboards personalizados para cada técnico (tecnico_1 hasta tecnico_5)
- [ ] Navbar personalizado para cada técnico
- [ ] CSS y JS específicos por técnico
- [ ] Sistema de notificaciones push
- [ ] Reportes avanzados por etapa
- [ ] Sistema de calificación de servicios desde app móvil
- [ ] Asignación automática de denuncias

## 🔒 Seguridad en Producción

### Checklist de Seguridad:

- [ ] Cambiar contraseñas por defecto
- [ ] Usar HTTPS (SSL/TLS)
- [ ] Deshabilitar `display_errors` en PHP
- [ ] Configurar backups automáticos de BD
- [ ] Proteger Google Maps API Key (restricciones por dominio)
- [ ] Validar todos los inputs del usuario
- [ ] Revisar permisos de carpetas (755 para web, 755 para uploads)
- [ ] Configurar rate limiting para subida de archivos
- [ ] Implementar logs de auditoría
- [ ] Configurar límites de tamaño de archivos en servidor

## 👨‍💻 Desarrollo

### Formato de Teléfono Implementado

El sistema usa un formato automático para teléfonos guatemaltecos:

```javascript
// Formato: 1234-5678
// Solo números, máximo 8 dígitos
// Guion automático después del 4º dígito
// Funciona con tipeo y copiar/pegar
```

### Función Helper para Rutas

```php
function obtenerRutaArchivo($rutaBD) {
    $rutaLimpia = str_replace(['../', './'], '', $rutaBD);

    if (strpos($rutaLimpia, 'uploads/') === 0) {
        return "/AppUBA/backend/" . $rutaLimpia;
    }

    if (strpos($rutaLimpia, 'backend/') === 0) {
        return "/AppUBA/" . $rutaLimpia;
    }

    return "/AppUBA/backend/" . $rutaLimpia;
}
```

### Clase WorkflowDenuncias (config/workflow.php)

La clase `WorkflowDenuncias` centraliza toda la lógica del workflow:

**Propiedades estáticas:**

- `$siguienteEtapa` - Mapea etapa actual a siguiente etapa
- `$etapasPorRol` - Define qué etapas ve cada rol
- `$nombresEtapas` - Nombres amigables para mostrar

**Métodos principales:**

- `obtenerEtapaActual($id_denuncia, $db)` - Obtiene la etapa actual de una denuncia
- `actualizarEstadoDenuncia($id_denuncia, $accion, $db)` - Actualiza el estado según la acción
- `obtenerDenunciasPorEtapa($etapas, $db)` - Obtiene denuncias para etapas específicas
- `obtenerHistorial($id_denuncia, $db)` - Obtiene el historial completo de seguimiento
- `obtenerArchivos($id_seguimiento, $db)` - Obtiene archivos de un seguimiento
- `obtenerEstadisticasPorEtapa($etapa, $db)` - Obtiene estadísticas para las tarjetas

## 👥 Autores

- **Miguel** - Desarrollador principal - MAGA (Ministerio de Agricultura, Ganadería y Alimentación)

## 📄 Licencia

Proyecto gubernamental - Todos los derechos reservados © 2024-2025 MAGA

---

**Última actualización:** Diciembre 2025  
**Versión:** 3.0.0  
**Estado:** TODOS LOS ROLES 100% COMPLETOS ✅

## 📝 Notas de la Última Versión (3.0.0)

### ✅ COMPLETADO EN ESTA VERSIÓN:

**🎉 TODOS LOS ROLES 100% FUNCIONALES**

Todas las funcionalidades del administrador están completas y probadas:

1. **Dashboard Principal** ✅

   - 5 tarjetas de estadísticas
   - Gráfico de dona con Chart.js
   - Tabla de denuncias con DataTables
   - Botones Ver detalle y Editar

2. **Gestión de Denuncias** ✅

   - Ver detalle completo
   - Editar denuncia con mapa
   - Actualizar estado
   - Validaciones completas

3. **Gestión de Servicios Autorizados** ✅

   - CRUD completo (Crear, Ver, Editar, Eliminar)
   - Google Maps integrado
   - Subida de imágenes
   - DataTables con exportación

4. **Gestión de Noticias** ✅

   - CRUD completo (Crear, Ver, Editar, Eliminar)
   - Categorías y prioridades
   - Subida de imágenes
   - DataTables con exportación

5. **Sistema de Workflow - 5 Áreas Técnicas** ✅
   - **Área Legal** - Completa con tarjetas de estadísticas
   - **Área Técnica** - Completa con tarjetas de estadísticas
   - **Emitir Dictamen** - Completa con tarjetas de estadísticas
   - **Opinión Legal** - Completa con tarjetas de estadísticas
   - **Resolución Final** - Completa con tarjetas de estadísticas

**Cada área técnica incluye:**

- ✅ 4 tarjetas de estadísticas específicas
- ✅ DataTables con exportación (Excel, PDF, Copiar, Imprimir)
- ✅ Botón "Ver detalle" funcional
- ✅ Botón "Procesar" funcional
- ✅ Historial completo de seguimiento (timeline)
- ✅ Formulario de procesamiento con:
  - Comentario obligatorio (min 20 caracteres)
  - Contador de caracteres en tiempo real
  - Subida múltiple de archivos
  - Drag & Drop de archivos
  - Preview de archivos
  - Validaciones completas
- ✅ Guardado con transacciones SQL
- ✅ SweetAlert en todas las confirmaciones

### ⏳ PENDIENTE PARA SIGUIENTE VERSIÓN:

**Dashboards de Técnicos (tecnico_1 hasta tecnico_5)**

Cada técnico necesitará:

- [ ] Dashboard personalizado (similar al de admin pero limitado a su área)
- [ ] Navbar específico con solo su área técnica
- [ ] CSS personalizado
- [ ] JavaScript personalizado
- [ ] Redirección automática desde login
- [ ] Ver solo denuncias de su área asignada
- [ ] Procesar denuncias de su área
- [ ] Ver historial completo

**Estructura a crear:**

```
/modules/tecnico_1/dashboard.php
/includes/navbar_tecnico1.php
/css/dashboard_tecnico1.css
/js/dashboard_tecnico1.js

(Y lo mismo para tecnico_2, tecnico_3, tecnico_4, tecnico_5)
```

### Resumen de Implementación:

**✅ COMPLETO:**

- Login system
- ROL ADMINISTRADOR (100%)
- ROL TÉCNICO_1 (100%)
- ROL TÉCNICO_2 (100%)
- ROL TÉCNICO_3 (100%)
- ROL TÉCNICO_4 (100%)
- ROL TÉCNICO_5 (100%)
  - Dashboard principal
  - Gestión de denuncias
  - Gestión de servicios
  - Gestión de noticias
  - 5 áreas técnicas del workflow
  - Tarjetas de estadísticas en cada área
  - Historial de seguimiento completo
  - Procesamiento de denuncias

**⏳ PENDIENTE:**

- Dashboards de los 5 roles de técnicos
- Navbars personalizados por técnico
- Notificaciones push
- Reportes avanzados
- Sistema de calificación desde app móvil

### Instrucciones para Próxima Fase:

Para implementar los dashboards de técnicos:

1. Crear archivo `/modules/tecnico_1/dashboard.php`
2. Copiar estructura de `/modules/admin/area_legal/index.php`
3. Simplificar navbar (solo mostrar su área)
4. Crear CSS personalizado
5. Crear JS personalizado
6. Actualizar `index.php` para redirigir correctamente
7. Repetir para técnico_2, técnico_3, técnico_4, técnico_5

### Notas Técnicas Importantes:

- **Workflow completo**: Todas las 5 áreas funcionan correctamente
- **Estadísticas dinámicas**: Las tarjetas se actualizan en tiempo real
- **Validaciones robustas**: Cliente y servidor
- **Transacciones SQL**: Garantizan integridad de datos
- **Historial completo**: Timeline visual en cada procesamiento
- **Archivos seguros**: Validación de tipos y tamaño
- **DataTables avanzado**: Exportación a Excel, PDF, etc.

### Estado del Proyecto:

```
PROGRESO TOTAL: 75%

✅ Login: 100%
✅ ROL ADMIN: 100%
⏳ ROL TÉCNICO_1: 0%
⏳ ROL TÉCNICO_2: 0%
⏳ ROL TÉCNICO_3: 0%
⏳ ROL TÉCNICO_4: 0%
⏳ ROL TÉCNICO_5: 0%
⏳ Notificaciones: 0%
⏳ Reportes avanzados: 0%
```

---

## 🎉 HITO IMPORTANTE

**TODOS LOS ROLES (ADMIN + TÉCNICOS 1-5) 100% COMPLETOS Y FUNCIONALES** ✅

El sistema está listo para que el administrador gestione completamente:

- Denuncias
- Servicios autorizados
- Noticias
- Workflow completo de las 5 áreas técnicas
- Procesamiento de denuncias
- Historial de seguimiento

**Próximo objetivo:** Implementar dashboards para los 5 roles de técnicos 🚀

---

**FIN DEL README - VERSIÓN 3.0.0**
