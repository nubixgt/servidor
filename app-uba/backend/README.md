# Backend - AppUBA

API REST desarrollada en PHP para la gestión de denuncias de maltrato animal en Guatemala.

## 🚀 Tecnologías

- PHP 7.4+
- MySQL/MariaDB
- PDO (PHP Data Objects)
- Apache/Nginx

## 📁 Estructura del Proyecto

```
backend/
├── api/
│   ├── denuncias.php           # CRUD de denuncias
│   ├── uploads.php             # Subida de archivos
│   ├── infracciones.php        # Catálogos (departamentos, municipios, etc.)
│   ├── noticias.php            # Obtener noticias publicadas ← NUEVO
│   ├── servicios.php           # Obtener servicios autorizados ← NUEVO
│   └── calificar_servicio.php  # Calificar servicios ← NUEVO
├── config/
│   ├── database.php            # Configuración de BD (NO incluido en Git)
│   └── database.example.php    # Plantilla de configuración
└── uploads/
    ├── dpi/                    # Fotos de DPI
    ├── fachadas/               # Fotos de fachadas
    ├── evidencias/             # Archivos de evidencia
    ├── noticias/               # Imágenes de noticias ← NUEVO
    └── servicios/              # Imágenes de servicios ← NUEVO
```

## 🔧 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/TU_USUARIO/AppUBA.git
cd AppUBA/backend
```

### 2. Configurar la base de datos

```bash
# Copiar el archivo de ejemplo
cp config/database.example.php config/database.php

# Editar con tus credenciales
nano config/database.php
```

Actualiza estas líneas con tus datos:

```php
private $host = "localhost";
private $db_name = "AppUBA";
private $username = "root";
private $password = "TU_CONTRASEÑA_AQUI";
```

### 3. Crear la base de datos

Ejecuta los siguientes scripts SQL en phpMyAdmin o desde consola:

```sql
CREATE DATABASE AppUBA CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE AppUBA;

-- Tabla principal de denuncias
CREATE TABLE denuncias (
    id_denuncia INT PRIMARY KEY AUTO_INCREMENT,
    tipo_persona ENUM('Individual', 'Juridica') NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    dpi VARCHAR(15) NOT NULL,
    edad INT NOT NULL,
    genero ENUM('Masculino', 'Femenino') NOT NULL,
    celular VARCHAR(10) NOT NULL,
    foto_dpi_frontal VARCHAR(255) NOT NULL,
    foto_dpi_trasera VARCHAR(255) NOT NULL,
    nombre_responsable VARCHAR(150) NULL,
    direccion_infraccion TEXT NOT NULL,
    departamento VARCHAR(50) NOT NULL,
    municipio VARCHAR(50) NOT NULL,
    color_casa VARCHAR(50) NULL,
    color_puerta VARCHAR(50) NULL,
    foto_fachada VARCHAR(255) NOT NULL,
    latitud DECIMAL(10, 8) NULL,
    longitud DECIMAL(11, 8) NULL,
    especie_animal VARCHAR(50) NOT NULL,
    especie_otro VARCHAR(100) NULL,
    cantidad INT NOT NULL,
    raza VARCHAR(50) NULL,
    descripcion_detallada TEXT NOT NULL,
    fecha_denuncia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_denuncia ENUM('pendiente', 'en_proceso', 'resuelta', 'rechazada') DEFAULT 'pendiente',
    INDEX idx_fecha (fecha_denuncia),
    INDEX idx_estado (estado_denuncia),
    INDEX idx_departamento (departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de infracciones
CREATE TABLE infracciones_denuncia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_denuncia INT NOT NULL,
    tipo_infraccion VARCHAR(100) NOT NULL,
    infraccion_otro TEXT NULL,
    FOREIGN KEY (id_denuncia) REFERENCES denuncias(id_denuncia) ON DELETE CASCADE,
    INDEX idx_denuncia (id_denuncia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de evidencias
CREATE TABLE evidencias_denuncia (
    id_evidencia INT PRIMARY KEY AUTO_INCREMENT,
    id_denuncia INT NOT NULL,
    tipo_archivo ENUM('imagen', 'pdf', 'doc', 'audio', 'video', 'otro') NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    tamanio_kb INT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_denuncia) REFERENCES denuncias(id_denuncia) ON DELETE CASCADE,
    INDEX idx_denuncia (id_denuncia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de noticias (NUEVO)
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
    INDEX idx_prioridad (prioridad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de servicios autorizados (NUEVO)
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
    INDEX idx_latitud_longitud (latitud, longitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 4. Configurar permisos de carpetas

```bash
# En Linux/Mac
chmod -R 755 uploads/
chown -R www-data:www-data uploads/

# Crear carpetas para noticias y servicios
mkdir -p uploads/noticias
mkdir -p uploads/servicios
chmod -R 755 uploads/noticias
chmod -R 755 uploads/servicios

# En Windows (desde XAMPP/WAMP no es necesario)
```

## 📡 Endpoints de la API

### **1. Subir archivos**

```
POST /api/uploads.php
Content-Type: multipart/form-data

Parámetros:
- tipo: 'dpi' | 'fachada' | 'evidencia'
- archivo: File

Respuesta exitosa (201):
{
  "success": true,
  "message": "Archivo subido exitosamente",
  "data": {
    "nombre_archivo": "abc123_1234567890.jpg",
    "ruta_archivo": "../uploads/dpi/abc123_1234567890.jpg",
    "url": "http://servidor.com/AppUBA/backend/uploads/dpi/abc123_1234567890.jpg",
    "tipo_archivo": "imagen",
    "tamanio_kb": 1500.25
  }
}
```

### **2. Crear denuncia**

```
POST /api/denuncias.php
Content-Type: application/json

Body:
{
  "tipo_persona": "Individual",
  "nombre_completo": "Juan Pérez",
  "dpi": "3000053690101",
  "edad": 35,
  "genero": "Masculino",
  "celular": "30107000",
  "foto_dpi_frontal": "uploads/dpi/frontal.jpg",
  "foto_dpi_trasera": "uploads/dpi/trasera.jpg",
  "nombre_responsable": "Pedro García",
  "direccion_infraccion": "5ta Calle 3-45 Zona 1",
  "departamento": "Guatemala",
  "municipio": "Guatemala",
  "color_casa": "Azul",
  "color_puerta": "Blanca",
  "foto_fachada": "uploads/fachadas/fachada.jpg",
  "latitud": 14.6349,
  "longitud": -90.5069,
  "especie_animal": "Caninos",
  "especie_otro": null,
  "cantidad": 2,
  "raza": "Labrador",
  "descripcion_detallada": "Descripción del caso...",
  "infracciones": [
    {"tipo": "Maltrato físico", "otro": null},
    {"tipo": "Abandono", "otro": null}
  ],
  "evidencias": [
    {
      "tipo": "imagen",
      "nombre": "evidencia1.jpg",
      "ruta": "uploads/evidencias/evidencia1.jpg",
      "tamanio": 1500
    }
  ]
}

Respuesta exitosa (201):
{
  "success": true,
  "message": "Denuncia creada exitosamente",
  "id_denuncia": 1
}
```

### **3. Listar denuncias**

```
GET /api/denuncias.php?limit=10&offset=0&estado=pendiente

Respuesta:
{
  "success": true,
  "data": [...],
  "total": 50,
  "limit": 10,
  "offset": 0
}
```

### **4. Obtener noticias** ← NUEVO

```
GET /api/noticias.php

Respuesta:
{
  "success": true,
  "data": [
    {
      "id_noticia": 1,
      "titulo": "Campaña de Esterilización Gratuita",
      "categoria": "Campaña",
      "descripcion_corta": "Jornada de esterilización en zona 18...",
      "contenido_completo": "El Ministerio de Agricultura...",
      "imagen_url": "http://servidor.com/AppUBA/backend/uploads/noticias/imagen1.jpg",
      "fecha_publicacion": "28 Sep 2025",
      "prioridad": "importante"
    }
  ],
  "total": 3
}

Características:
- Solo retorna noticias con estado 'publicada'
- Ordenadas por fecha de publicación descendente
- URLs de imágenes completas
- Fecha formateada para mostrar
```

### **5. Obtener servicios autorizados** ← NUEVO

```
GET /api/servicios.php

Respuesta:
{
  "success": true,
  "data": [
    {
      "id_servicio": 1,
      "nombre_servicio": "Clínica Veterinaria Mascota Feliz",
      "direccion": "5ta Avenida 12-53 Zona 10, Guatemala",
      "latitud": 14.593780,
      "longitud": -90.513840,
      "telefono": "2334-5678",
      "servicios_ofrecidos": "Consulta, Cirugía, Emergencias 24/7",
      "calificacion": 4.8,
      "total_calificaciones": 127,
      "imagen_url": "http://servidor.com/AppUBA/backend/uploads/servicios/imagen1.jpg"
    }
  ],
  "total": 3
}

Características:
- Solo retorna servicios con estado 'activo'
- Ordenados por calificación descendente
- URLs de imágenes completas
- Coordenadas GPS para Google Maps
```

### **6. Calificar servicio** ← NUEVO

```
POST /api/calificar_servicio.php
Content-Type: application/json

Body:
{
  "id_servicio": 1,
  "calificacion": 5.0
}

Respuesta exitosa (200):
{
  "success": true,
  "message": "¡Gracias por tu calificación!",
  "data": {
    "nueva_calificacion": 4.8,
    "total_calificaciones": 128
  }
}

Características:
- Valida que la calificación esté entre 1 y 5
- Calcula automáticamente el nuevo promedio
- Fórmula: ((calificación_actual × total) + nueva_calificación) / (total + 1)
- Actualiza ambos campos en una transacción SQL
- Redondea a 1 decimal
- Retorna valores numéricos (no strings)
```

### **7. Obtener departamentos**

```
GET /api/infracciones.php?tipo=departamentos

Respuesta:
{
  "success": true,
  "data": [
    {"id": 1, "nombre": "Guatemala"},
    {"id": 2, "nombre": "Alta Verapaz"},
    ...
  ]
}
```

### **8. Obtener municipios**

```
GET /api/infracciones.php?tipo=municipios&departamento=Guatemala

Respuesta:
{
  "success": true,
  "data": [
    {"id": 1, "nombre": "Guatemala"},
    {"id": 2, "nombre": "Mixco"},
    ...
  ]
}
```

### **9. Obtener tipos de infracción**

```
GET /api/infracciones.php?tipo=tipos_infraccion

Respuesta:
{
  "success": true,
  "data": [
    {"id": 1, "nombre": "Maltrato físico"},
    {"id": 2, "nombre": "Abandono"},
    ...
  ]
}
```

### **10. Obtener especies**

```
GET /api/infracciones.php?tipo=especies

Respuesta:
{
  "success": true,
  "data": [
    {"id": 1, "nombre": "Caninos"},
    {"id": 2, "nombre": "Felinos"},
    ...
  ]
}
```

## 🔒 Seguridad

- Las contraseñas de la BD **NO** deben incluirse en el repositorio
- Usa `.gitignore` para excluir `config/database.php`
- En producción, habilita HTTPS
- Configura CORS según tus necesidades
- Limita el tamaño de archivos subidos (actualmente 10MB)
- Usa PDO con prepared statements para prevenir SQL injection
- Validación de tipos MIME en archivos subidos
- Transacciones SQL para operaciones críticas

## � Sistema de Calificaciones

El sistema de calificaciones funciona con un promedio ponderado:

**Fórmula:**

```
suma_total = (calificacion_actual × total_calificaciones) + nueva_calificacion
nuevo_total = total_calificaciones + 1
nuevo_promedio = suma_total / nuevo_total
```

**Ejemplo:**

- Calificación actual: 4.8 con 127 calificaciones
- Nueva calificación: 5.0
- Resultado: 4.8 con 128 calificaciones

**Características:**

- Calificación entre 1.0 y 5.0
- Redondeo a 1 decimal
- Actualización atómica con transacciones
- Validación de entrada
- Retorna valores numéricos (JSON_NUMERIC_CHECK)

## �🐛 Debugging

Para ver errores detallados, edita `backend/.htaccess`:

```apache
# Mostrar errores (solo en desarrollo)
php_flag display_errors On
php_flag log_errors On
```

O en el archivo PHP:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📝 Notas

- Los archivos subidos se almacenan en `uploads/`
- Las rutas de archivos se guardan relativamente en la BD
- El sistema usa PDO con prepared statements para prevenir SQL injection
- CORS está habilitado para permitir peticiones desde la app móvil
- Las imágenes de noticias y servicios se construyen con URL completa
- El sistema de calificaciones es acumulativo (no permite editar calificaciones previas)

## 🆕 Últimas Actualizaciones (Enero 2026)

### Nuevos Endpoints

1. **`noticias.php`**

   - Obtiene noticias publicadas
   - Ordenadas por fecha descendente
   - URLs de imágenes completas
   - Fecha formateada

2. **`servicios.php`**

   - Obtiene servicios activos
   - Ordenados por calificación
   - Incluye coordenadas GPS
   - URLs de imágenes completas

3. **`calificar_servicio.php`**
   - Sistema de calificación con estrellas
   - Cálculo automático de promedio
   - Transacciones SQL
   - Validación de entrada (1-5)
   - Retorna valores numéricos

### Nuevas Tablas

- `noticias` - Gestión de noticias para la app
- `servicios_autorizados` - Clínicas y veterinarias

## 👨‍💻 Autor

Desarrollado por Miguel - MAGA (Ministerio de Agricultura, Ganadería y Alimentación)

## 📄 Licencia

Proyecto gubernamental - Todos los derechos reservados
