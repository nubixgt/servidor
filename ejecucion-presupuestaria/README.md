# Sistema de Ejecución Presupuestaria - MAGA

Sistema web para la gestión y visualización de la ejecución presupuestaria del Ministerio de Agricultura, Ganadería y Alimentación de Guatemala.

## 📋 Características

- **Dashboard Principal**: Visualización de KPIs con animaciones de conteo
- **Filtros Dinámicos**: Por tipo de ejecución, unidad ejecutora, etc.
- **Gráficas Interactivas**: Barras, donas y comparativas usando Chart.js
- **Sistema de Semáforo**: Verde (>80%), Amarillo (60-80%), Rojo (<60%)
- **Comparativa de Ministerios**: Ranking y posición del MAGA
- **Módulo de Administración**: Edición de datos con validación
- **Bitácora de Cambios**: Historial completo de modificaciones
- **Importación de Excel**: Carga de datos desde archivos .xlsx/.csv
- **API REST**: Endpoints para consultas externas
- **Modo Oscuro**: Toggle para cambiar tema
- **Exportación**: Excel y PDF
- **Diseño Responsivo**: Adaptable a móviles y tablets

## 🛠️ Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3+
- Apache con mod_rewrite habilitado
- Extensiones PHP: PDO, pdo_mysql, json, mbstring

## 📦 Instalación

### 1. Clonar o copiar archivos

```bash
# Copiar la carpeta al directorio web
cp -r ejecucion_presupuestaria /var/www/html/
```

### 2. Crear la base de datos

```bash
# Acceder a MySQL
mysql -u root -p

# Ejecutar el script SQL
source /ruta/a/ejecucion_presupuestaria/database.sql
```

O importar desde phpMyAdmin el archivo `database.sql`

### 3. Configurar conexión a base de datos

Editar el archivo `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ejecucion_presupuestaria');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 4. Configurar permisos

```bash
# Dar permisos de escritura si es necesario
chmod 755 -R ejecucion_presupuestaria/
chmod 777 -R ejecucion_presupuestaria/assets/img/
```

### 5. Agregar el escudo de Guatemala

Colocar la imagen del escudo en:
- `assets/img/escudo-guatemala.png`

### 6. Acceder al sistema

Abrir en el navegador:
```
http://localhost/ejecucion_presupuestaria/
```

## 📁 Estructura de Archivos

```
ejecucion_presupuestaria/
├── api/
│   ├── index.php          # API REST endpoints
│   └── .htaccess          # Rewrite rules
├── assets/
│   ├── css/
│   │   └── styles.css     # Estilos principales
│   ├── js/
│   │   └── app.js         # JavaScript principal
│   └── img/
│       └── escudo-guatemala.png
├── config/
│   └── database.php       # Configuración BD
├── includes/
│   ├── header.php         # Header común
│   └── footer.php         # Footer común
├── modules/               # Módulos adicionales
├── index.php              # Dashboard principal
├── unidades.php           # Unidades ejecutoras
├── ministerios.php        # Comparativa ministerios
├── administracion.php     # Módulo admin
├── bitacora.php           # Historial cambios
├── importar.php           # Importar Excel
├── database.sql           # Script base de datos
└── README.md              # Este archivo
```

## 🔌 API REST

### Endpoints disponibles

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/?endpoint=dashboard` | GET | Totales del dashboard |
| `/api/?endpoint=ejecucion` | GET | Listado ejecución principal |
| `/api/?endpoint=ejecucion&id=X` | PUT | Actualizar registro |
| `/api/?endpoint=unidades` | GET | Listado unidades ejecutoras |
| `/api/?endpoint=detalle` | GET | Detalle por unidad |
| `/api/?endpoint=ministerios` | GET | Listado ministerios |
| `/api/?endpoint=estadisticas` | GET | Estadísticas para gráficas |
| `/api/?endpoint=bitacora` | GET | Historial de cambios |

### Ejemplo de uso

```javascript
// Obtener datos del dashboard
fetch('/ejecucion_presupuestaria/api/?endpoint=dashboard')
    .then(response => response.json())
    .then(data => console.log(data));
```

## 👤 Usuario por defecto

- **Email**: admin@maga.gob.gt
- **Contraseña**: admin123
- **Rol**: Administrador

⚠️ **Importante**: Cambiar la contraseña después de la instalación.

## 🎨 Personalización

### Colores del tema

Editar las variables CSS en `assets/css/styles.css`:

```css
:root {
    --primary-color: #1a365d;
    --secondary-color: #3182ce;
    --success-color: #38a169;
    --warning-color: #d69e2e;
    --danger-color: #e53e3e;
}
```

### Umbrales del semáforo

Modificar en la tabla `configuracion`:

```sql
UPDATE configuracion SET valor = '80' WHERE clave = 'umbral_verde';
UPDATE configuracion SET valor = '60' WHERE clave = 'umbral_amarillo';
```

## 📊 Importar datos desde Excel

1. Ir a **Importar Datos** en el menú
2. Seleccionar el archivo Excel (.xlsx) o CSV
3. Elegir el tipo de datos (Principal, Detalle, Ministerios)
4. Hacer clic en **Importar**

### Formato esperado

El archivo debe tener las mismas columnas que el Excel original:
- Hoja "UNI EJE" → Ejecución Principal
- Hoja "UniEjeYGru_Gas" → Detalle por Unidad
- Hoja "MINISTERIOS" → Ministerios

## 🔒 Seguridad

- Contraseñas hasheadas con bcrypt
- Protección contra SQL Injection (PDO prepared statements)
- Validación de entrada de datos
- Bitácora de todos los cambios

## 📱 Compatibilidad

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+
- Opera 67+

## 🐛 Solución de problemas

### Error de conexión a base de datos
- Verificar credenciales en `config/database.php`
- Confirmar que MySQL está corriendo

### Gráficas no se muestran
- Verificar que Chart.js carga correctamente
- Revisar consola del navegador para errores

### Estilos no cargan
- Limpiar caché del navegador
- Verificar ruta de archivos CSS

## 📞 Soporte

Para soporte técnico o reportar errores, contactar al equipo de desarrollo.

---

**Desarrollado para**: Ministerio de Agricultura, Ganadería y Alimentación (MAGA)  
**Versión**: 1.0.0  
**Última actualización**: Enero 2025
