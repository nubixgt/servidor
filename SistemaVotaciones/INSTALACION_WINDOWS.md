# Guía de Instalación para Windows

## Sistema de Votaciones del Congreso - Windows

### 🖥️ Requisitos para Windows

- **XAMPP** o **WAMP** (incluye PHP, MySQL y Apache)
- Windows 10 o superior
- Navegador web moderno (Chrome, Firefox, Edge)

### 📥 Instalación Paso a Paso

#### 1. Instalar XAMPP

1. **Descargar XAMPP:**
   - Ir a: https://www.apachefriends.org/download.html
   - Descargar versión para Windows (PHP 7.4 o superior)
   - Tamaño aproximado: 150 MB

2. **Instalar XAMPP:**
   ```
   - Ejecutar el instalador descargado
   - Seleccionar componentes:
     ✓ Apache
     ✓ MySQL
     ✓ PHP
     ✓ phpMyAdmin
   - Instalar en: C:\xampp (recomendado)
   ```

3. **Iniciar Servicios:**
   - Abrir XAMPP Control Panel
   - Click en "Start" para Apache
   - Click en "Start" para MySQL

#### 2. Instalar el Sistema

1. **Extraer archivos:**
   ```
   - Descomprimir sistema-congreso-votaciones.zip
   - Copiar todos los archivos a:
     C:\xampp\htdocs\congreso\
   ```

2. **Crear carpeta uploads:**
   ```
   - Ir a: C:\xampp\htdocs\congreso\
   - Crear carpeta llamada: uploads
   ```

#### 3. Configurar la Base de Datos

1. **Abrir phpMyAdmin:**
   ```
   - Abrir navegador
   - Ir a: http://localhost/phpmyadmin
   - Usuario: root
   - Contraseña: (dejar en blanco)
   ```

2. **Crear base de datos:**
   ```
   - Click en "Nueva" en el menú izquierdo
   - Nombre: congreso_votaciones
   - Cotejamiento: utf8mb4_unicode_ci
   - Click en "Crear"
   ```

3. **Importar estructura:**
   ```
   - Seleccionar base de datos: congreso_votaciones
   - Click en pestaña "Importar"
   - Click en "Seleccionar archivo"
   - Buscar: C:\xampp\htdocs\congreso\database.sql
   - Click en "Continuar"
   ```

#### 4. Configurar Conexión

1. **Editar config.php:**
   ```
   - Abrir: C:\xampp\htdocs\congreso\config.php
   - Con Notepad++ o cualquier editor de texto
   ```

2. **Actualizar credenciales:**
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'congreso_votaciones');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Dejar vacío para XAMPP por defecto
   ```

3. **Guardar archivo**

#### 5. Probar la Instalación

1. **Abrir el navegador:**
   ```
   http://localhost/congreso/
   ```

2. **Deberías ver:**
   - Dashboard del sistema
   - Menú lateral con opciones
   - Estadísticas (vacías por ahora)

### 🎯 Cargar tu Primer PDF

1. **Click en "Cargar PDF"** en el menú

2. **Seleccionar archivo:**
   - Click en "Seleccionar Archivo"
   - O arrastrar el PDF a la zona indicada

3. **Procesar:**
   - Click en "Procesar Documento"
   - Esperar 10-30 segundos

4. **¡Listo!**
   - Verás un mensaje de éxito
   - Los datos ya están en el sistema

### ⚠️ Solución de Problemas en Windows

#### Error: "No se puede conectar a la base de datos"

**Solución:**
```
1. Verificar que MySQL está corriendo en XAMPP Control Panel
2. Verificar credenciales en config.php
3. Verificar que la base de datos existe en phpMyAdmin
```

#### Error: "Error al extraer texto del PDF"

**Solución:**
```
El sistema ahora usa un procesador especial para Windows que NO requiere Python.
Si aún falla:
1. Verificar que el PDF no está protegido o encriptado
2. Verificar que el PDF tiene texto (no es una imagen escaneada)
3. Revisar el archivo error.log en C:\xampp\htdocs\congreso\
```

#### Error: "No se puede crear directorio uploads"

**Solución:**
```
1. Crear manualmente la carpeta:
   C:\xampp\htdocs\congreso\uploads\
2. Click derecho > Propiedades > Seguridad
3. Dar permisos de escritura
```

#### Apache no inicia

**Solución:**
```
1. Otro programa está usando el puerto 80 (Skype, IIS, etc.)
2. En XAMPP Control Panel:
   - Click en "Config" junto a Apache
   - Click en "httpd.conf"
   - Cambiar "Listen 80" por "Listen 8080"
   - Guardar y reiniciar Apache
   - Ahora acceder en: http://localhost:8080/congreso/
```

#### MySQL no inicia

**Solución:**
```
1. Verificar que otro MySQL no esté corriendo
2. Revisar el log en XAMPP Control Panel
3. Click en "Config" > "my.ini"
4. Verificar puerto (3306 por defecto)
```

### 📁 Estructura de Archivos en Windows

```
C:\xampp\htdocs\congreso\
├── config.php                  # Configuración
├── database.sql               # Base de datos
├── index.php                  # Dashboard
├── cargar.php                 # Cargar PDFs
├── eventos.php                # Ver eventos
├── congresistas.php           # Ver congresistas
├── bloques.php                # Ver bloques
├── estadisticas.php           # Estadísticas
├── procesar_pdf.php           # Procesador principal
├── procesar_pdf_simple.php    # Procesador para Windows (sin Python)
├── uploads\                   # Carpeta para PDFs
└── README.md                  # Documentación
```

### 🚀 Accesos Rápidos

Una vez instalado, puedes acceder a:

```
Dashboard Principal:
http://localhost/congreso/

Cargar PDF:
http://localhost/congreso/cargar.php

phpMyAdmin (administrar BD):
http://localhost/phpmyadmin

XAMPP Control Panel:
C:\xampp\xampp-control.exe
```

### 💡 Tips para Windows

1. **Puertos Alternos:**
   Si el puerto 80 está ocupado, usa 8080:
   ```
   http://localhost:8080/congreso/
   ```

2. **Firewall:**
   Permitir Apache y MySQL en el Firewall de Windows

3. **Antivirus:**
   Agregar carpeta C:\xampp a excepciones

4. **Rendimiento:**
   - Cerrar programas innecesarios
   - Aumentar límites PHP en php.ini si es necesario

5. **Backups:**
   Hacer backup regular de:
   - Base de datos (phpMyAdmin > Exportar)
   - Archivos en C:\xampp\htdocs\congreso\

### 📊 Verificar que Todo Funciona

1. **Abrir:** http://localhost/congreso/

2. **Verificar:**
   - ✅ Se ve el dashboard
   - ✅ No hay errores en pantalla
   - ✅ El menú funciona
   - ✅ Puedes navegar entre páginas

3. **Cargar PDF de prueba:**
   - Ir a "Cargar PDF"
   - Subir el PDF incluido
   - Verificar que se procesa correctamente

4. **Ver resultados:**
   - Dashboard muestra estadísticas
   - "Eventos" lista la votación
   - "Congresistas" muestra los diputados

### 🔒 Seguridad en Windows

**Para uso local/desarrollo:**
- La configuración por defecto de XAMPP es suficiente

**Para servidor público (NO RECOMENDADO CON XAMPP):**
- Cambiar contraseña de MySQL root
- Configurar firewall
- Usar HTTPS
- Implementar autenticación

### 📖 Comandos Útiles en Windows

**Reiniciar servicios:**
```
- Abrir XAMPP Control Panel
- Stop Apache y MySQL
- Start Apache y MySQL
```

**Ver logs:**
```
Apache: C:\xampp\apache\logs\error.log
MySQL: C:\xampp\mysql\data\
PHP: C:\xampp\apache\logs\
Sistema: C:\xampp\htdocs\congreso\error.log
```

**Backup base de datos:**
```
1. Abrir phpMyAdmin
2. Seleccionar congreso_votaciones
3. Click en "Exportar"
4. Guardar archivo SQL
```

**Restaurar base de datos:**
```
1. Abrir phpMyAdmin
2. Seleccionar congreso_votaciones
3. Click en "Importar"
4. Seleccionar archivo SQL guardado
```

### 🎓 Próximos Pasos

1. ✅ Instalar XAMPP
2. ✅ Copiar archivos
3. ✅ Crear base de datos
4. ✅ Configurar config.php
5. ✅ Probar en navegador
6. ✅ Cargar primer PDF
7. ✅ Explorar estadísticas

### 📞 Ayuda Adicional

**Recursos:**
- Documentación completa: README.md
- Guía rápida: GUIA_RAPIDA.md
- FAQ XAMPP: https://www.apachefriends.org/faq_windows.html

**Archivos importantes:**
- LEEME_PRIMERO.txt - Información general
- README.md - Documentación técnica completa
- GUIA_RAPIDA.md - Uso diario del sistema

---

## 🎉 ¡Instalación Completada!

Si todo funciona correctamente:
- ✅ XAMPP corriendo
- ✅ Base de datos creada
- ✅ Sistema accesible en http://localhost/congreso/
- ✅ Primer PDF procesado

**¡Ya puedes empezar a analizar votaciones del Congreso!** 🏛️📊

---

**Nota especial para Windows:**
Este sistema incluye un procesador especial para Windows (`procesar_pdf_simple.php`) que NO requiere instalar Python ni herramientas adicionales. Todo funciona con PHP nativo.
