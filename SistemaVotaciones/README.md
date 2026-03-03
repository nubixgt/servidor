# Sistema de Votaciones del Congreso de Guatemala

Sistema completo en PHP y MySQL para gestionar, visualizar y analizar votaciones del Congreso de la República de Guatemala.

## 📋 Características

- ✅ **Carga automática de PDFs** - Procesa archivos PDF de votaciones
- 📊 **Dashboard interactivo** - Visualización de estadísticas en tiempo real
- 👥 **Gestión de congresistas** - Seguimiento individual de cada diputado
- 🏛️ **Análisis por bloques** - Estadísticas por partido político
- 📈 **Gráficas dinámicas** - Visualización con Chart.js
- 🔍 **Búsqueda y filtros** - Encuentra información rápidamente
- 📱 **Diseño responsive** - Funciona en todos los dispositivos

## 🚀 Instalación

### Requisitos Previos

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3+
- Apache o Nginx
- Extensiones PHP requeridas:
  - PDO MySQL
  - mbstring
  - fileinfo
- Python 3.6+ (para procesamiento de PDFs)
- pdftotext (poppler-utils) o pdfplumber

### Paso 1: Clonar o descargar los archivos

Coloca todos los archivos en tu directorio web (ej: `/var/www/html/congreso/` o `C:\xampp\htdocs\congreso\`)

### Paso 2: Instalar dependencias

#### En Ubuntu/Debian:

```bash
# Instalar poppler-utils para pdftotext
sudo apt-get update
sudo apt-get install poppler-utils

# Instalar Python y pdfplumber (opcional, como fallback)
sudo apt-get install python3 python3-pip
pip3 install pdfplumber --break-system-packages
```

#### En macOS:

```bash
# Instalar con Homebrew
brew install poppler

# Instalar Python y pdfplumber
brew install python3
pip3 install pdfplumber
```

#### En Windows:

1. Descargar poppler desde: https://github.com/oschwartz10612/poppler-windows/releases
2. Extraer y agregar la carpeta `bin` al PATH
3. Instalar Python desde: https://www.python.org/downloads/
4. Instalar pdfplumber: `pip install pdfplumber`

### Paso 3: Crear la base de datos

```bash
# Conectar a MySQL
mysql -u root -p

# Ejecutar el script SQL
mysql -u root -p < database.sql
```

O importar manualmente:
1. Abrir phpMyAdmin
2. Crear nueva base de datos llamada `congreso_votaciones`
3. Importar el archivo `database.sql`

### Paso 4: Configurar la conexión

Editar el archivo `config.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'congreso_votaciones');
define('DB_USER', 'tu_usuario');      // Cambiar
define('DB_PASS', 'tu_contraseña');   // Cambiar
```

### Paso 5: Configurar permisos

```bash
# Dar permisos de escritura al directorio uploads
chmod 755 uploads/
chmod 644 *.php
```

### Paso 6: Acceder al sistema

Abrir en el navegador:
```
http://localhost/congreso/
```

## 📤 Cómo Usar

### Cargar un PDF

1. Ir a **Cargar PDF** en el menú lateral
2. Seleccionar o arrastrar un archivo PDF de votación
3. Hacer clic en "Procesar Documento"
4. El sistema automáticamente:
   - Extrae los datos del PDF
   - Identifica congresistas y bloques
   - Registra todos los votos
   - Calcula estadísticas

### Formato del PDF

El sistema espera PDFs con el siguiente formato (como el ejemplo proporcionado):

```
EVENTO DE VOTACIÓN # [NÚMERO]
[TÍTULO DE LA VOTACIÓN] SESIÓN No. [NÚMERO]
Fecha y Hora: DD-MM-YYYY HH:MM:SS

No.  NOMBRE                           BLOQUE                  VOTO EMITIDO
1    Nombre del Congresista          PARTIDO POLÍTICO        A FAVOR
2    Otro Congresista                INDEPENDIENTES          EN CONTRA
...
```

### Ver Estadísticas

1. **Dashboard**: Vista general del sistema
2. **Eventos**: Historial de todas las votaciones
3. **Congresistas**: Lista completa con estadísticas individuales
4. **Bloques**: Análisis por partido político
5. **Estadísticas**: Gráficas y análisis detallados

### Buscar un Congresista

1. Ir a **Congresistas**
2. Usar la barra de búsqueda
3. Hacer clic en "Ver" para estadísticas detalladas

## 🗂️ Estructura de Archivos

```
congreso/
├── config.php              # Configuración de la base de datos
├── database.sql            # Script de creación de base de datos
├── procesar_pdf.php        # Procesador de PDFs
├── index.php               # Dashboard principal
├── cargar.php              # Página de carga de PDFs
├── eventos.php             # Lista de eventos
├── congresistas.php        # Lista de congresistas
├── bloques.php             # Análisis por bloques
├── estadisticas.php        # Gráficas y estadísticas
├── uploads/                # Directorio para PDFs cargados
└── README.md               # Este archivo
```

## 🎨 Características del Sistema

### Dashboard
- Tarjetas de estadísticas generales
- Últimos eventos procesados
- Congresistas con mayor ausentismo
- Acceso rápido a todas las secciones

### Procesamiento de PDFs
- Extracción automática de datos
- Detección de congresistas y bloques
- Manejo de duplicados
- Log de errores

### Análisis de Datos
- Gráficas de distribución de votos
- Historial individual por congresista
- Eventos más polémicos
- Porcentajes de ausencias
- Comparación entre bloques

### Base de Datos
- **Tablas principales**:
  - `eventos_votacion`: Información de cada evento
  - `congresistas`: Registro de diputados
  - `bloques`: Partidos políticos
  - `votos`: Registro de cada voto
  - `resumen_eventos`: Conteos por evento

- **Vistas**:
  - `vista_estadisticas_congresista`: Stats por diputado
  - `vista_estadisticas_bloque`: Stats por partido
  - `vista_detalle_eventos`: Resumen de eventos

## 🔧 Procesamiento desde Línea de Comandos

También puedes procesar PDFs directamente desde la terminal:

```bash
php procesar_pdf.php /ruta/al/archivo.pdf
```

Esto es útil para:
- Automatización con cron jobs
- Procesamiento por lotes
- Integración con otros sistemas

## 📊 Ejemplo de Datos

El sistema puede procesar múltiples eventos del mismo congresista, generando:

- **Historial de votaciones**: Ver en qué eventos votó a favor, en contra o estuvo ausente
- **Tendencias**: Identificar patrones de votación
- **Comparaciones**: Ver diferencias entre bloques políticos
- **Análisis temporal**: Seguimiento a lo largo del tiempo

## 🐛 Solución de Problemas

### Error al cargar PDF
- Verificar que poppler-utils está instalado
- Verificar permisos del directorio uploads/
- Revisar el tamaño máximo de archivos en php.ini

### No se extraen datos correctamente
- Verificar que el PDF tiene el formato esperado
- Ver el log de errores en `error.log`
- Probar con pdfplumber como alternativa

### Error de conexión a la base de datos
- Verificar credenciales en config.php
- Asegurar que MySQL está corriendo
- Verificar que la base de datos existe

### Gráficas no aparecen
- Verificar que Chart.js se carga correctamente
- Ver la consola del navegador para errores
- Verificar que hay datos en la base de datos

## 🔐 Seguridad

El sistema incluye:
- Sanitización de entradas
- Prepared statements (previene SQL injection)
- Validación de archivos subidos
- Manejo seguro de sesiones

**Recomendaciones adicionales para producción:**
- Cambiar credenciales por defecto
- Usar HTTPS
- Implementar autenticación de usuarios
- Restringir acceso al directorio uploads/
- Habilitar logging detallado

## 📝 Personalización

### Cambiar colores
Editar las variables CSS en cualquier archivo .php:

```css
:root {
    --primary-color: #2563eb;
    --success-color: #10b981;
    --danger-color: #ef4444;
}
```

### Agregar campos
1. Modificar `database.sql` para agregar columnas
2. Actualizar `procesar_pdf.php` para extraer nuevos datos
3. Modificar las páginas PHP para mostrar los datos

## 🤝 Contribuciones

Este sistema fue diseñado específicamente para las votaciones del Congreso de Guatemala, pero puede adaptarse para otros parlamentos o sistemas de votación.

## 📄 Licencia

Este proyecto es de código abierto y está disponible para uso libre.

## 📧 Soporte

Si encuentras problemas:
1. Revisar la sección de solución de problemas
2. Ver el archivo `error.log`
3. Verificar la configuración de la base de datos

## 🎯 Próximas Funcionalidades

- [ ] Exportar reportes a Excel/PDF
- [ ] Comparar múltiples congresistas
- [ ] Análisis de tendencias temporales
- [ ] API REST para integración
- [ ] Sistema de alertas
- [ ] Generación automática de informes

---

**Desarrollado para el análisis de votaciones del Congreso de la República de Guatemala**

Versión 1.0.0 | Octubre 2025
