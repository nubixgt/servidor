# Sistema de Registro de DPI - VISAN 10910

Sistema web para gestionar y registrar DPI físicos importados desde archivos CSV.

## 📋 Descripción

Este sistema permite buscar registros de DPI en una base de datos con más de 10,000 registros y actualizar su estado cuando se tiene el DPI físico en mano. Utiliza DataTables para una búsqueda rápida y eficiente.

## 🗂️ Estructura del Proyecto

```
DPI/
├── config/
│   └── database.php          # Configuración de conexión a BD
├── api/
│   ├── obtener_registros.php # Endpoint para listar registros
│   └── actualizar_estado.php # Endpoint para cambiar estado
├── css/
│   └── styles.css            # Estilos personalizados
├── js/
│   └── main.js               # Lógica de DataTables y AJAX
├── index.php                 # Página principal
├── modificar_tabla.sql       # Script SQL para modificar la tabla
└── README.md                 # Este archivo
```

## 🗄️ Base de Datos

### Tabla: `planillas_visan_10910`

| Columna   | Tipo         | Descripción                       |
| --------- | ------------ | --------------------------------- |
| fila      | INT          | Número de fila del registro       |
| nombre    | VARCHAR(255) | Nombre completo de la persona     |
| dpi       | VARCHAR(20)  | Número de DPI (clave de búsqueda) |
| comunidad | VARCHAR(255) | Comunidad a la que pertenece      |
| estado    | ENUM         | 'Sin Registrar' o 'DPI Físico'    |

### Estados Disponibles

- **Sin Registrar** (por defecto): El DPI aún no ha sido verificado físicamente
- **DPI Físico**: El DPI físico ha sido verificado y registrado

## ⚙️ Instalación

### 1. Ejecutar el Script SQL

Primero, modifica la tabla existente ejecutando el script `modificar_tabla.sql`:

```sql
-- Conectarse a la base de datos DPI
USE DPI;

-- Ejecutar el script completo
SOURCE modificar_tabla.sql;
```

O ejecutarlo manualmente en phpMyAdmin/MySQL Workbench.

### 2. Configurar la Conexión a la Base de Datos

Edita el archivo `config/database.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'DPI');
define('DB_USER', 'tu_usuario');    // Cambiar
define('DB_PASS', 'tu_contraseña'); // Cambiar
define('DB_PORT', '3306');
```

### 3. Configurar el Servidor Web

Asegúrate de que el proyecto esté en la carpeta de tu servidor web:

- **XAMPP**: `C:/xampp/htdocs/DPI/`
- **WAMP**: `C:/wamp64/www/DPI/`
- **MAMP**: `/Applications/MAMP/htdocs/DPI/`

### 4. Acceder al Sistema

Abre tu navegador y visita:

```
http://localhost/DPI/
```

## 🚀 Funcionalidades

### 1. Visualización de Registros

- Muestra todos los registros en una tabla interactiva
- Paginación automática (25 registros por página por defecto)
- Búsqueda en tiempo real por cualquier campo

### 2. Búsqueda de DPI

- Campo de búsqueda integrado en DataTables
- Filtra instantáneamente por DPI, nombre, comunidad, etc.
- Resalta los resultados encontrados

### 3. Registro de DPI Físico

- Botón "Guardar Registro" en cada fila
- Confirmación antes de actualizar el estado
- Actualización automática de la tabla después de guardar
- Notificaciones visuales con SweetAlert2

### 4. Estadísticas en Tiempo Real

- **Total Registros**: Cantidad total de registros en la base de datos
- **DPI Físico Registrado**: Cantidad de DPIs ya verificados
- **Sin Registrar**: Cantidad de DPIs pendientes de verificar

### 5. Diseño Moderno

- Glassmorphism y gradientes vibrantes
- Responsive (adaptable a móviles y tablets)
- Animaciones suaves
- Badges de estado con colores distintivos

## 🎨 Tecnologías Utilizadas

### Backend

- **PHP 7.4+**: Lógica del servidor
- **MySQL**: Base de datos
- **PDO**: Conexión segura a la base de datos

### Frontend

- **HTML5**: Estructura
- **CSS3**: Estilos con glassmorphism
- **JavaScript (jQuery)**: Interactividad
- **DataTables**: Tabla interactiva con búsqueda y paginación
- **SweetAlert2**: Notificaciones elegantes
- **Font Awesome**: Iconos
- **Google Fonts (Poppins)**: Tipografía moderna

## 📱 Uso del Sistema

### Flujo de Trabajo

1. **Buscar DPI**: Usa el campo de búsqueda para encontrar un DPI específico
2. **Verificar Información**: Revisa que el nombre y comunidad coincidan
3. **Registrar**: Haz clic en "Guardar Registro"
4. **Confirmar**: Confirma la acción en el diálogo
5. **Verificar**: El estado cambia automáticamente a "DPI Físico"

### Ejemplo de Búsqueda

```
Buscar: 2610358860207
Resultado: ABEL MERLOS CARRERA - AGUA SALOBREGA
Acción: Click en "Guardar Registro"
Estado: Sin Registrar → DPI Físico ✓
```

## 🔧 API Endpoints

### GET `/api/obtener_registros.php`

Obtiene todos los registros de la tabla.

**Respuesta:**

```json
{
  "success": true,
  "data": [
    {
      "fila": 1,
      "nombre": "ABEL MERLOS CARRERA",
      "dpi": "2610358860207",
      "comunidad": "AGUA SALOBREGA",
      "estado": "Sin Registrar"
    }
  ],
  "recordsTotal": 10000,
  "recordsFiltered": 10000
}
```

### POST `/api/actualizar_estado.php`

Actualiza el estado de un registro a "DPI Físico".

**Request:**

```json
{
  "dpi": "2610358860207"
}
```

**Respuesta:**

```json
{
  "success": true,
  "message": "Estado actualizado correctamente a DPI Físico",
  "data": {
    "fila": 1,
    "nombre": "ABEL MERLOS CARRERA",
    "estado": "DPI Físico"
  }
}
```

## 🛡️ Seguridad

- Uso de **PDO con prepared statements** para prevenir SQL Injection
- Validación de datos en el backend
- Manejo de errores sin exponer información sensible
- CORS configurado para el dominio local

## 📝 Notas Importantes

1. **Credenciales**: Recuerda cambiar las credenciales en `config/database.php`
2. **Backup**: Haz un respaldo de la base de datos antes de ejecutar el script SQL
3. **Permisos**: Asegúrate de que el usuario de MySQL tenga permisos de ALTER TABLE
4. **Navegadores**: Compatible con Chrome, Firefox, Edge, Safari (últimas versiones)

## 🐛 Solución de Problemas

### Error de conexión a la base de datos

- Verifica las credenciales en `config/database.php`
- Asegúrate de que MySQL esté corriendo
- Verifica que la base de datos `DPI` exista

### DataTables no carga datos

- Abre la consola del navegador (F12)
- Verifica que no haya errores en la pestaña "Network"
- Prueba acceder directamente a `http://localhost/DPI/api/obtener_registros.php`

### No se actualiza el estado

- Verifica que el usuario de MySQL tenga permisos de UPDATE
- Revisa la consola del navegador para errores JavaScript
- Verifica que el DPI exista en la base de datos

## 📄 Licencia

Este proyecto es de uso interno para VISAN 10910.

## 👨‍💻 Autor

Desarrollado para el sistema de gestión de DPI - VISAN 10910

---

**Versión:** 1.0.0  
**Fecha:** Enero 2026
