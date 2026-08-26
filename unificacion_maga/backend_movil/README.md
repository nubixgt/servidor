# 🌤️ Backend API - Sistema de Registros Climatológicos

API REST para el sistema de registros climatológicos de MAGA (Ministerio de Agricultura, Ganadería y Alimentación).

## 📋 Tabla de Contenidos

- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Estructura](#estructura)
- [Endpoints](#endpoints)
- [Autenticación](#autenticación)
- [Configuración](#configuración)
- [Seguridad](#seguridad)

## ✨ Características

- ✅ Autenticación con tokens HMAC
- ✅ Gestión de registros climáticos
- ✅ Subida de fotografías (hasta 5 por registro)
- ✅ Validación de roles y estados de usuario
- ✅ Endpoints de alertas
- ✅ Respuestas JSON estandarizadas
- ✅ Manejo de errores robusto
- ✅ Logs de errores
- ✅ CORS configurado

## 📦 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache/Nginx con mod_rewrite
- Extensiones PHP:
  - PDO
  - pdo_mysql
  - mbstring
  - json

## 🚀 Instalación

### 1. Configurar Base de Datos

```bash
# Crear base de datos
mysql -u root -p < scripts/crear_base_datos.sql

# Crear tablas de usuarios
mysql -u root -p RegistroClimatologico < scripts/crear_tablas.sql

# Crear tablas de registros
mysql -u root -p RegistroClimatologico < scripts/crear_tablas_registros.sql

# Actualizar enums (si es necesario)
mysql -u root -p RegistroClimatologico < scripts/actualizar_enum_desastres.sql
```

### 2. Configurar Permisos

```bash
# Permisos para uploads
chmod -R 777 uploads/

# Permisos para logs
chmod -R 755 logs/
```

### 3. Migrar Contraseñas (Solo primera vez)

```bash
# Hashear contraseñas existentes
php scripts/hash_passwords.php
```

### 4. Configurar .htaccess

El archivo `.htaccess` ya está configurado con:

- Límites de subida: 10MB por archivo
- Timeout: 300 segundos
- CORS habilitado

## 📁 Estructura

```
backend_movil/
├── api/
│   ├── auth/
│   │   ├── login.php          # Inicio de sesión
│   │   ├── logout.php         # Cierre de sesión
│   │   └── verify.php         # Verificación de token
│   └── registros/
│       ├── crear.php          # Crear registro con fotos
│       ├── listar.php         # Listar registros del técnico
│       └── detalle.php        # Detalle de un registro
├── config/
│   ├── config.php             # Configuración general
│   └── Database.php           # Conexión a BD
├── utils/
│   ├── Auth.php               # Utilidades de autenticación
│   └── Response.php           # Respuestas estandarizadas
├── scripts/
│   ├── crear_tablas_registros.sql
│   ├── actualizar_enum_desastres.sql
│   └── hash_passwords.php
├── uploads/
│   └── registros/             # Fotografías de registros
├── logs/
│   └── api_errors.log         # Logs de errores
├── .htaccess                  # Configuración Apache
└── README.md                  # Este archivo
```

## 🔌 Endpoints

### Autenticación

#### POST `/api/auth/login.php`

Iniciar sesión y obtener token.

**Request:**

```json
{
  "nombreUsuario": "tecnico1",
  "contrasena": "password123"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "usuario": {
      "id": 4,
      "nombreCompleto": "Juan Pérez",
      "nombreUsuario": "tecnico1",
      "rol": "Tecnico",
      "estado": "Activo"
    }
  }
}
```

#### POST `/api/auth/verify.php`

Verificar validez del token.

**Headers:**

```
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "usuario": { ... }
  }
}
```

#### POST `/api/auth/logout.php`

Cerrar sesión.

**Headers:**

```
Authorization: Bearer {token}
```

### Registros Climáticos

#### POST `/api/registros/crear.php`

Crear un nuevo registro climático con fotografías.

**Headers:**

```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Form Data:**

```
latitud: -14.634915
longitud: -90.506882
direccion: "Guatemala, Guatemala"
temperatura: 25.5
humedad: 65.0
precipitacion: 10.5
viento: 15.0
categoria: "desastre"
desastre_natural: "incendio_forestal"
observaciones: "Incendio forestal en zona boscosa"
foto_1: [archivo]
foto_2: [archivo]
...
foto_5: [archivo]
```

**Response:**

```json
{
  "success": true,
  "message": "Registro creado exitosamente",
  "data": {
    "id": 15,
    "fotos_guardadas": 2
  }
}
```

#### GET `/api/registros/listar.php`

Listar registros del técnico autenticado.

**Headers:**

```
Authorization: Bearer {token}
```

**Query Parameters:**

```
limite: 50 (opcional, default: 50)
pagina: 1 (opcional, default: 1)
fecha_desde: 2026-01-01 (opcional)
fecha_hasta: 2026-01-31 (opcional)
```

**Response:**

```json
{
  "success": true,
  "data": {
    "registros": [
      {
        "id": 15,
        "fecha_registro": "2026-01-12 16:56:00",
        "latitud": "-14.634915",
        "longitud": "-90.506882",
        "direccion": "Guatemala, Guatemala",
        "temperatura": "25.5",
        "humedad": "65.0",
        "precipitacion": "10.5",
        "viento": "15.0",
        "categoria": "desastre",
        "condicion_climatica": null,
        "desastre_natural": "incendio_forestal",
        "observaciones": "Incendio forestal en zona boscosa",
        "id_usuario": 4,
        "fotografias": [
          {
            "id": 1,
            "nombre_archivo": "scaled_37da955c...jpg",
            "ruta_archivo": "uploads/registros/usuario_4/registro_15_foto_1_1768258611.jpg",
            "orden": 1
          },
          {
            "id": 2,
            "nombre_archivo": "scaled_3d5d7d73...jpg",
            "ruta_archivo": "uploads/registros/usuario_4/registro_15_foto_2_1768258611.jpg",
            "orden": 2
          }
        ]
      }
    ],
    "total": 1,
    "pagina": 1,
    "limite": 50
  }
}
```

#### GET `/api/registros/detalle.php?id=15`

Obtener detalle de un registro específico.

**Headers:**

```
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "registro": { ... },
    "fotografias": [ ... ]
  }
}
```

### Alertas Climatológicas

#### GET `/api/alertas/listar.php`

Listar alertas activas y vigentes.

**Query Parameters:**

```
region: Guatemala (opcional)
```

**Response:**

```json
{
  "success": true,
  "data": {
    "alertas": [
      {
        "id": 2,
        "titulo": "Alerta de Tormenta",
        "descripcion_corta": "Esto es una prueba de una descripcion corta",
        "descripcion_detallada": "Descripción completa...",
        "tipo_alerta": "Tormenta",
        "nivel_severidad": "ALTA",
        "region": "Costa Sur",
        "icono": "⛈️",
        "fecha_emision": "2026-01-12 23:59:00",
        "fecha_vigencia": "2026-01-15 23:59:00",
        "estado": "Activa"
      }
    ],
    "total": 1
  }
}
```

## 🔐 Autenticación

### Sistema de Tokens HMAC

1. **Login:** El usuario envía credenciales
2. **Token:** El servidor genera un token HMAC con:
   - ID de usuario
   - Timestamp de expiración (24 horas)
   - Firma HMAC-SHA256
3. **Validación:** Cada request valida:
   - Firma del token
   - Expiración
   - Rol del usuario
   - Estado del usuario

### Formato del Token

```
{user_id}|{expiration_timestamp}|{hmac_signature}
```

### Headers Requeridos

```
Authorization: Bearer {token}
```

## ⚙️ Configuración

### config/config.php

```php
// Zona horaria
date_default_timezone_set('America/Guatemala');

// Configuración de tokens
define('SECRET_KEY', 'tu_clave_secreta_aqui');
define('TOKEN_EXPIRATION', 86400); // 24 horas

// Roles permitidos
define('ALLOWED_ROLES', ['Tecnico']);
define('ALLOWED_STATES', ['Activo']);
```

### .htaccess

```apache
# Límites de subida
php_value upload_max_filesize 10M
php_value post_max_size 20M
php_value max_execution_time 300
php_value max_input_time 300
```

## 🔒 Seguridad

### Implementado

- ✅ Contraseñas hasheadas con bcrypt
- ✅ Tokens HMAC con expiración
- ✅ Validación de roles y estados
- ✅ Sanitización de inputs
- ✅ Prepared statements (SQL injection)
- ✅ Validación de tipos de archivo
- ✅ Límites de tamaño de archivo
- ✅ CORS configurado
- ✅ Logs de errores

### Recomendaciones

- 🔐 Cambiar `SECRET_KEY` en producción
- 🔐 Usar HTTPS en producción
- 🔐 Implementar rate limiting
- 🔐 Monitorear logs regularmente
- 🔐 Backup regular de base de datos
- 🔐 Actualizar PHP y MySQL

## 📝 Logs

Los errores se registran en:

```
logs/api_errors.log
```

Formato:

```
[2026-01-12 16:56:00] ERROR: Mensaje de error
```

## 🐛 Solución de Problemas

### Error: "Token inválido"

- Verificar que el token no haya expirado
- Verificar headers de autorización
- Verificar SECRET_KEY en config.php

### Error: "No se pudo crear el directorio"

```bash
chmod -R 777 uploads/
```

### Error: "Archivo muy grande"

- Verificar `.htaccess`
- Verificar `php.ini`
- Reiniciar servidor web

### Error: "Usuario no autorizado"

- Verificar rol del usuario (debe ser "Tecnico")
- Verificar estado del usuario (debe ser "Activo")

## 📞 Soporte

Para reportar problemas o solicitar funcionalidades, contactar al equipo de desarrollo.

---

**Versión:** 2.0  
**Última actualización:** Enero 2026  
**Desarrollado para:** MAGA - Ministerio de Agricultura, Ganadería y Alimentación
