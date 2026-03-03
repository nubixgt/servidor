# 🌱 VIDER - Sistema de Gestión MAGA Guatemala

Sistema web para la gestión de datos del Viceministerio de Desarrollo Económico Rural (VIDER) del Ministerio de Agricultura, Ganadería y Alimentación (MAGA) de Guatemala.

## 📋 Características

- ✅ **Dashboard interactivo** con estadísticas en tiempo real
- ✅ **Mapa interactivo de Guatemala** con zoom por departamento/municipio
- ✅ **Importación de Excel** con detección automática de duplicados
- ✅ **Tabla de datos** con filtros avanzados y búsqueda
- ✅ **Exportación** a Excel y CSV
- ✅ **Reportes** personalizados
- ✅ **Historial** de importaciones con auditoría
- ✅ **Diseño moderno** con animaciones y efectos glassmorphism
- ✅ **Responsive** - Compatible con móviles y tablets

## 🗂️ Estructura del Proyecto

```
vider/
├── api/                          # Endpoints de la API
│   ├── get_dashboard_stats.php   # Estadísticas del dashboard
│   ├── get_map_data.php          # Datos para el mapa
│   ├── get_municipios.php        # Municipios por departamento
│   ├── get_filtered_data.php     # Datos con filtros
│   ├── upload.php                # Subida de archivos
│   ├── process_import.php        # Procesamiento de importación
│   └── export.php                # Exportación de datos
├── css/
│   └── style.css                 # Estilos del sistema
├── js/
│   └── app.js                    # JavaScript principal
├── includes/
│   ├── config.php                # Configuración y clase Database
│   └── ExcelReader.php           # Lector de archivos Excel
├── uploads/                      # Archivos subidos
├── logs/                         # Logs del sistema
├── assets/                       # Recursos estáticos
├── database.sql                  # Schema de la base de datos
├── index.php                     # Dashboard principal
├── mapa.php                      # Página del mapa interactivo
├── datos.php                     # Tabla de datos con filtros
├── importar.php                  # Importación de Excel
├── reportes.php                  # Centro de reportes
└── historial.php                 # Historial de importaciones
```

## 🚀 Instalación

### Requisitos
- PHP 8.0 o superior
- MySQL 5.7 o superior
- Composer
- Extensiones PHP: pdo_mysql, mbstring, zip

### Paso 1: Clonar o copiar archivos
```bash
# Copiar la carpeta vider al directorio de tu servidor web
cp -r vider /var/www/html/
```

### Paso 2: Instalar dependencias
```bash
cd /var/www/html/vider
composer require phpoffice/phpspreadsheet
```

### Paso 3: Crear la base de datos
```bash
# Conectar a MySQL
mysql -u root -p

# Crear la base de datos
CREATE DATABASE vider_maga CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Importar el schema
mysql -u root -p vider_maga < database.sql
```

### Paso 4: Configurar conexión
Editar `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vider_maga');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### Paso 5: Configurar permisos
```bash
chmod 755 uploads/ logs/
chown -R www-data:www-data uploads/ logs/
```

### Paso 6: Acceder al sistema
```
http://tu-servidor/vider/
```

## 📊 Uso del Sistema

### Dashboard
La página principal muestra:
- Estadísticas generales de beneficiarios
- Ejecución física y financiera
- Gráficos por departamento y dependencia
- Mapa interactivo de Guatemala

### Importar Datos
1. Ir a "Importar"
2. Arrastrar o seleccionar archivo Excel
3. El sistema detecta automáticamente duplicados
4. Ver resumen de importación

### Filtrar Datos
1. Ir a "Datos"
2. Usar los filtros avanzados
3. Buscar en la tabla
4. Exportar resultados filtrados

### Generar Reportes
1. Ir a "Reportes"
2. Seleccionar tipo de reporte
3. Configurar filtros
4. Descargar en Excel o CSV

## 🔒 Seguridad

- Validación de tipos de archivo
- Sanitización de entradas
- Protección contra SQL Injection (PDO prepared statements)
- Hash de registros para detectar duplicados
- Logs de actividad

## 📱 Capturas de Pantalla

El sistema incluye:
- **Dashboard** con gráficos Chart.js
- **Mapa SVG** interactivo de Guatemala
- **Tablas** con paginación y ordenamiento
- **Formularios** con validación en tiempo real
- **Notificaciones** toast para feedback

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 8.x, MySQL
- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Librerías**: 
  - Chart.js (gráficos)
  - PhpSpreadsheet (Excel)
  - Font Awesome (iconos)
  - Google Fonts (tipografía)

## 📄 Licencia

Sistema desarrollado para MAGA Guatemala.

## 📞 Soporte

Para soporte técnico contactar al equipo de TI de MAGA.

---
*Desarrollado con ❤️ para Guatemala*
