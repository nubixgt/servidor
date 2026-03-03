# Guía Rápida de Uso

## Inicio Rápido en 5 Minutos

### 1. Instalación Automática (Linux/Mac)

```bash
chmod +x install.sh
./install.sh
```

Sigue las instrucciones en pantalla.

### 2. Instalación Manual

**a) Crear la base de datos:**
```bash
mysql -u root -p
CREATE DATABASE congreso_votaciones;
USE congreso_votaciones;
SOURCE database.sql;
EXIT;
```

**b) Configurar credenciales:**
Edita `config.php` y actualiza:
- DB_HOST
- DB_NAME
- DB_USER
- DB_PASS

**c) Dar permisos:**
```bash
chmod 755 uploads/
```

### 3. Probar el Sistema

```bash
php test_pdf.php
```

Si todo funciona correctamente, verás un resumen del PDF procesado.

## Uso Diario

### Cargar un PDF de Votación

1. **Accede al sistema:** `http://localhost/congreso/`
2. **Menú lateral:** Click en "Cargar PDF"
3. **Arrastra el PDF** o haz click en "Seleccionar Archivo"
4. **Click en "Procesar Documento"**
5. **Espera** mientras el sistema procesa (puede tomar 10-30 segundos)
6. **Listo** - Los datos están en el sistema

### Ver Estadísticas de un Congresista

1. **Dashboard → Congresistas**
2. **Buscar** por nombre
3. **Click en "Ver"** para ver gráficas y detalles

### Comparar Bloques Políticos

1. **Dashboard → Bloques**
2. Ver estadísticas de cada partido
3. Comparar votos a favor, en contra y ausencias

### Análisis por Eventos

1. **Dashboard → Eventos**
2. Ver historial completo de votaciones
3. Identificar eventos polémicos (más votos en contra)

## Estructura del PDF Requerido

Los PDFs deben tener este formato:

```
EVENTO DE VOTACIÓN # X
TÍTULO DE LA VOTACIÓN SESIÓN No. XX
Fecha y Hora: DD-MM-YYYY HH:MM:SS

No.  NOMBRE                    BLOQUE              VOTO EMITIDO
1    Nombre Congresista       PARTIDO POLÍTICO    A FAVOR
2    Otro Congresista         INDEPENDIENTES      EN CONTRA
```

### Tipos de Voto Reconocidos:
- `A FAVOR`
- `EN CONTRA`
- `AUSENTE`
- `LICENCIA`

## Funcionalidades Principales

### Dashboard
- **Tarjetas de estadísticas**: Total de eventos, congresistas, bloques y votos
- **Últimos eventos**: Los 10 eventos más recientes
- **Mayor ausentismo**: Top 10 congresistas con más ausencias

### Eventos
- **Listado completo**: Todas las votaciones registradas
- **Filtros**: Por fecha, resultado, número de evento
- **Detalles**: Votos favor, contra, ausentes, licencias

### Congresistas
- **Lista completa**: Todos los diputados
- **Búsqueda**: Por nombre
- **Estadísticas individuales**: Historial de cada congresista
- **Paginación**: 20 por página

### Bloques
- **Vista por partido**: Estadísticas de cada bloque
- **Gráficas**: Distribución de votos
- **Comparación**: Ver diferencias entre bloques

### Estadísticas
- **Selector de congresista**: Ver análisis detallado
- **Gráficas interactivas**: Charts.js
- **Eventos polémicos**: Los más controvertidos
- **Tendencias**: Patrones de votación

## Comandos Útiles

### Procesar PDF desde terminal
```bash
php procesar_pdf.php /ruta/al/archivo.pdf
```

### Ver logs de errores
```bash
tail -f error.log
```

### Backup de base de datos
```bash
mysqldump -u root -p congreso_votaciones > backup.sql
```

### Restaurar backup
```bash
mysql -u root -p congreso_votaciones < backup.sql
```

## Interpretación de Datos

### Porcentaje de Ausencias
- **Verde (0-15%)**: Asistencia excelente
- **Amarillo (15-30%)**: Asistencia regular
- **Rojo (>30%)**: Ausentismo alto

### Resultado de Eventos
- **APROBADO**: Más votos a favor que en contra
- **RECHAZADO**: Más votos en contra que a favor
- **PENDIENTE**: Sin suficientes datos

### Eventos Polémicos
Son aquellos con:
- Alto número de votos en contra (>10)
- Poca diferencia entre favor y contra
- Alta participación

## Solución Rápida de Problemas

### PDF no se procesa
**Problema**: "Error al extraer texto del PDF"
**Solución**: 
1. Instalar poppler-utils: `sudo apt-get install poppler-utils`
2. O instalar pdfplumber: `pip3 install pdfplumber`

### No aparecen datos
**Problema**: Tablas vacías
**Solución**:
1. Verificar que el PDF se procesó correctamente
2. Revisar `error.log`
3. Ejecutar `test_pdf.php` para diagnosticar

### Error de conexión BD
**Problema**: "Error de conexión"
**Solución**:
1. Verificar credenciales en `config.php`
2. Verificar que MySQL está corriendo: `sudo service mysql status`
3. Probar conexión: `mysql -u usuario -p`

### Gráficas no aparecen
**Problema**: Espacio en blanco donde debería haber gráficas
**Solución**:
1. Abrir consola del navegador (F12)
2. Ver si hay errores de JavaScript
3. Verificar conexión a internet (Chart.js se carga desde CDN)

## Tips de Performance

### Para grandes volúmenes de datos:
1. **Índices**: Ya están creados en las tablas principales
2. **Caché**: Considerar usar Redis o Memcached
3. **Paginación**: Ya implementada (20 registros por página)
4. **Optimización de consultas**: Usar vistas creadas

### Procesamiento por lotes:
```bash
for file in pdfs/*.pdf; do
    php procesar_pdf.php "$file"
done
```

## Mantenimiento

### Limpiar datos de prueba
```sql
USE congreso_votaciones;
DELETE FROM votos;
DELETE FROM eventos_votacion;
DELETE FROM congresistas;
DELETE FROM bloques;
```

### Optimizar base de datos
```sql
OPTIMIZE TABLE votos;
OPTIMIZE TABLE eventos_votacion;
OPTIMIZE TABLE congresistas;
OPTIMIZE TABLE bloques;
```

### Actualizar estadísticas
Las estadísticas se calculan automáticamente al procesar PDFs,
pero puedes forzar recálculo:

```bash
php -r "
require 'config.php';
require 'procesar_pdf.php';
\$p = new ProcesadorVotacionPDF();
// Código para recalcular
"
```

## Siguientes Pasos

1. **Cargar más PDFs**: Procesa el historial completo de votaciones
2. **Analizar tendencias**: Usa la sección Estadísticas
3. **Generar reportes**: Exporta datos para presentaciones
4. **Automatizar**: Configura cron jobs para procesar PDFs automáticamente

## Recursos

- **Documentación completa**: README.md
- **Código fuente**: Archivos PHP en el directorio
- **Base de datos**: database.sql
- **Logs**: error.log

## Contacto y Soporte

Si encuentras bugs o tienes sugerencias:
1. Revisa README.md
2. Consulta esta guía
3. Revisa error.log
4. Verifica la documentación del código

---

**¡Listo para analizar votaciones del Congreso!** 🏛️📊

Versión 1.0.0 | Sistema desarrollado para el Congreso de Guatemala
