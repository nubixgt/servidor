# 🏛️ SISTEMA DE VOTACIONES DEL CONGRESO DE GUATEMALA
## Guía Rápida de Instalación y Uso

---

## 📦 CONTENIDO DEL PAQUETE

El sistema completo incluye:

```
congreso_votaciones/
├── 📄 database.sql              → Script SQL para crear la base de datos
├── 📄 README.md                 → Documentación completa
│
├── 📁 config/
│   └── database.php             → Configuración de conexión a MySQL
│
├── 📁 public/                   → Archivos web públicos
│   ├── index.php                → Dashboard principal
│   ├── diputados.php            → Análisis de diputados
│   ├── eventos.php              → Análisis de eventos
│   ├── bloques.php              → Análisis de bloques
│   ├── cargar.php               → Carga de PDFs
│   ├── css/style.css            → Estilos personalizados
│   └── includes/navbar.php      → Menú de navegación
│
├── 📁 scripts/
│   ├── extraer_votaciones.py   → Script Python para procesar PDFs
│   └── test_extraccion.py      → Script de prueba
│
└── 📁 uploads/
    └── ejemplo.pdf              → PDF de ejemplo ya procesable
```

---

## 🚀 INSTALACIÓN RÁPIDA (10 MINUTOS)

### PASO 1: Requisitos Previos

Asegúrate de tener instalado:
- ✅ PHP 7.4+ con extensiones: PDO, PDO_MySQL, mbstring
- ✅ MySQL 5.7+ o MariaDB 10.2+
- ✅ Python 3.7+
- ✅ Servidor web (Apache o Nginx)

### PASO 2: Instalar Librerías Python

```bash
pip3 install pdfplumber mysql-connector-python pandas --break-system-packages
```

### PASO 3: Configurar Base de Datos

1. **Crear la base de datos:**

```bash
# Opción A: Desde terminal
mysql -u root -p < database.sql

# Opción B: Desde phpMyAdmin
# - Importar el archivo database.sql
```

2. **Configurar credenciales:**

Editar `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // ← Cambiar si es necesario
define('DB_PASS', 'tu_password');    // ← Poner tu contraseña
define('DB_NAME', 'congreso_votaciones');
```

### PASO 4: Configurar Servidor Web

**Para Apache:**

```bash
# Copiar proyecto a la carpeta web
sudo cp -r congreso_votaciones /var/www/html/

# Dar permisos
sudo chmod 755 -R /var/www/html/congreso_votaciones/public
sudo chmod 777 /var/www/html/congreso_votaciones/uploads

# Reiniciar Apache
sudo systemctl restart apache2
```

**Para desarrollo rápido (PHP built-in server):**

```bash
cd congreso_votaciones/public
php -S localhost:8000
```

### PASO 5: Acceder al Sistema

Abrir navegador: `http://localhost/congreso_votaciones/public/`

O si usaste el servidor PHP: `http://localhost:8000`

---

## 📊 USO DEL SISTEMA

### 1️⃣ CARGAR UN PDF

1. Ir a **"Cargar PDF"** en el menú
2. Seleccionar un PDF de votación del Congreso
3. Click en **"Subir y Procesar"**
4. ¡Listo! El sistema extrae y almacena automáticamente todos los datos

### 2️⃣ VER DASHBOARD

El dashboard muestra:
- 📈 Total de eventos procesados
- 👥 Número de diputados registrados
- 🏛️ Bloques políticos
- 📊 Distribución de votos
- 📋 Últimos eventos

### 3️⃣ ANALIZAR DIPUTADOS

**Ver todos:**
- Ir a **"Diputados"**
- Ver tabla con estadísticas de todos los diputados

**Ver uno específico:**
- Seleccionar un diputado del dropdown
- Ver:
  - 📊 Gráfica de distribución de votos
  - 📈 Tendencia de votación
  - 📋 Historial completo
  - 📉 Estadísticas de asistencia

### 4️⃣ ANALIZAR EVENTOS

- Ver lista de todos los eventos
- Click en **"Ver Detalle"** para ver:
  - ✅ Resultado final (Aprobado/Rechazado)
  - 📊 Gráficas de votación
  - 🏛️ Votación por bloque
  - 📋 Lista completa de votos

### 5️⃣ ANALIZAR BLOQUES

- Comparar rendimiento de todos los bloques
- Ver detalle de un bloque específico:
  - 👥 Diputados del bloque
  - 📊 Distribución de votos
  - 📈 Historial de votaciones

---

## 📄 FORMATO DEL PDF

Los PDFs deben tener esta estructura:

```
┌─────────────────────────────────────────┐
│ EVENTO DE VOTACIÓN # 2                  │
│ APROBACIÓN DEL ACTA DE LA SESIÓN        │
│ SESIÓN No. 42                           │
│ Fecha y Hora: 23-10-2025 10:10:36      │
├─────┬──────────┬────────────┬──────────┤
│ No. │ NOMBRE   │ BLOQUE     │ VOTO     │
├─────┼──────────┼────────────┼──────────┤
│  1  │ Juan...  │ CABAL      │ A FAVOR  │
│  2  │ María... │ UNE        │ AUSENTE  │
└─────┴──────────┴────────────┴──────────┘
```

**Votos válidos:**
- A FAVOR
- EN CONTRA
- AUSENTE
- LICENCIA
- ABSTENCIÓN

---

## 🎨 CARACTERÍSTICAS PRINCIPALES

### ✨ Dashboard Interactivo
- 4 tarjetas de estadísticas principales
- Gráfica de distribución de votos (pie chart)
- Lista de últimos 10 eventos
- Top 10 diputados más activos

### 👤 Análisis de Diputados
- **Búsqueda con autocompletado** (Select2)
- **Gráficas:**
  - Distribución de votos (pie chart)
  - Tendencia de votación en el tiempo (line chart)
- **Tabla de historial** con todos los votos
- **Estadísticas:**
  - Total de votaciones
  - % votos a favor
  - % de inasistencia

### 📅 Análisis de Eventos
- Lista completa de eventos
- **Vista detallada con:**
  - Resultado de la votación
  - Gráfica de resultado (bar chart)
  - Gráfica por bloque (stacked bar chart)
  - Tabla con todos los votos

### 🏛️ Análisis de Bloques
- Comparación entre bloques (bar chart)
- **Vista detallada de bloque:**
  - Estadísticas generales
  - Gráfica de distribución
  - Tabla de diputados del bloque
  - Historial de votaciones

---

## 🔧 COMANDOS ÚTILES

### Procesar un PDF manualmente

```bash
python3 scripts/extraer_votaciones.py uploads/archivo.pdf root tu_password
```

### Probar extracción de PDF

```bash
python3 scripts/test_extraccion.py
```

### Backup de base de datos

```bash
mysqldump -u root -p congreso_votaciones > backup_$(date +%Y%m%d).sql
```

### Restaurar backup

```bash
mysql -u root -p congreso_votaciones < backup_20250101.sql
```

### Ver logs de Apache

```bash
tail -f /var/log/apache2/error.log
```

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### ❌ Error: "Connection refused"

**Causa:** MySQL no está corriendo

**Solución:**
```bash
sudo systemctl start mysql
sudo systemctl enable mysql
```

### ❌ Error: "Access denied for user"

**Causa:** Credenciales incorrectas en `config/database.php`

**Solución:** Verificar usuario y contraseña de MySQL

### ❌ Error: "Permission denied" en uploads/

**Solución:**
```bash
chmod 777 uploads/
```

### ❌ PDFs no se procesan

**Verificar:**
1. Librerías Python instaladas: `pip3 list | grep pdfplumber`
2. Formato del PDF correcto
3. Permisos de ejecución: `chmod +x scripts/extraer_votaciones.py`

### ❌ Gráficas no se muestran

**Causa:** Chart.js no cargó

**Solución:** Verificar conexión a internet (usa CDN de Chart.js)

---

## 📊 CONSULTAS SQL ÚTILES

### Ver eventos más recientes

```sql
SELECT * FROM vista_analisis_evento 
ORDER BY fecha_votacion DESC 
LIMIT 10;
```

### Diputados con más ausencias

```sql
SELECT nombre_completo, bloque_actual, ausencias, porcentaje_inasistencia
FROM vista_analisis_diputado
ORDER BY ausencias DESC
LIMIT 20;
```

### Bloques con mayor % a favor

```sql
SELECT b.nombre, 
       SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as favor,
       COUNT(*) as total,
       ROUND(SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as porcentaje
FROM votos v
JOIN bloques b ON v.bloque_id = b.id
GROUP BY b.nombre
ORDER BY porcentaje DESC;
```

### Eventos con mayor abstención

```sql
SELECT numero_evento, descripcion, total_ausentes, total_votos,
       ROUND(total_ausentes * 100.0 / total_votos, 2) as porcentaje_ausencia
FROM vista_analisis_evento
ORDER BY porcentaje_ausencia DESC;
```

---

## 🎯 PRÓXIMOS PASOS

### Funcionalidades sugeridas para agregar:

1. **Exportación a Excel** de reportes
2. **API REST** para integración con otros sistemas
3. **Sistema de alertas** para votaciones importantes
4. **Comparaciones temporales** (mes a mes, año a año)
5. **Predicción de votos** con machine learning
6. **Dashboard para ciudadanos** (versión pública)
7. **App móvil** con React Native o Flutter
8. **Sistema de notificaciones** por email/SMS

---

## 📞 SOPORTE Y CONTACTO

Para dudas, bugs o sugerencias:
- 📧 Email: [tu-email@ejemplo.com]
- 🐛 Issues: [repositorio/issues]
- 📱 WhatsApp: [tu-número]

---

## 📝 NOTAS IMPORTANTES

⚠️ **Seguridad:**
- Cambiar credenciales de base de datos en producción
- Usar HTTPS en servidor de producción
- Limitar acceso al directorio `uploads/`
- Implementar autenticación si es necesario

⚠️ **Rendimiento:**
- Para más de 10,000 registros, considerar indexación adicional
- Cachear consultas frecuentes
- Optimizar consultas SQL pesadas

⚠️ **Mantenimiento:**
- Hacer backup diario de la base de datos
- Limpiar archivos uploads/ periódicamente
- Actualizar librerías Python regularmente

---

## ✅ CHECKLIST DE INSTALACIÓN

- [ ] PHP 7.4+ instalado
- [ ] MySQL/MariaDB instalado y corriendo
- [ ] Python 3.7+ instalado
- [ ] Librerías Python instaladas (pdfplumber, mysql-connector-python)
- [ ] Base de datos creada (database.sql ejecutado)
- [ ] Credenciales configuradas en config/database.php
- [ ] Permisos configurados (777 en uploads/)
- [ ] Servidor web configurado
- [ ] Sistema accesible desde navegador
- [ ] PDF de prueba procesado exitosamente

---

## 🎉 ¡LISTO!

Tu sistema de análisis de votaciones del Congreso está **completamente funcional**.

Puedes empezar a cargar PDFs y generar análisis inmediatamente.

**¡Buena suerte con tu proyecto!** 🚀

---

**Versión:** 1.0.0  
**Fecha:** Octubre 2025  
**Desarrollado para:** Análisis de Votaciones del Congreso de Guatemala
