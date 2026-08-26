# 📁 Directorio de Uploads

Este directorio almacena todos los archivos subidos por los usuarios de la aplicación móvil.

## 📂 Estructura

```
uploads/
├── registros/              # Fotografías de registros climáticos
│   ├── usuario_1/         # Fotos del usuario con ID 1
│   ├── usuario_2/         # Fotos del usuario con ID 2
│   └── usuario_X/         # Fotos del usuario con ID X
└── README.md              # Este archivo
```

## 🔐 Permisos

**Importante:** Este directorio debe tener permisos de escritura para el usuario del servidor web.

### Configuración de permisos:

```bash
# Dar permisos de escritura
chmod -R 777 uploads/

# O cambiar propietario al usuario del servidor web
sudo chown -R www-data:www-data uploads/  # Apache
sudo chown -R nginx:nginx uploads/         # Nginx
```

## 📸 Fotografías de Registros

Las fotografías de los registros climáticos se almacenan en:

```
uploads/registros/usuario_{id_usuario}/
```

### Nomenclatura de archivos:

```
registro_{id_registro}_foto_{orden}_{timestamp}.{extension}
```

**Ejemplo:**

```
registro_15_foto_1_1768258611.jpg
registro_15_foto_2_1768258611.jpg
```

### Características:

- **Formato:** JPG, JPEG, PNG
- **Tamaño máximo:** 10MB por foto (configurable en `.htaccess`)
- **Cantidad:** Hasta 5 fotos por registro
- **Organización:** Por usuario para facilitar gestión

## 🗄️ Base de Datos

Los metadatos de las fotografías se almacenan en la tabla `registros_fotografias`:

```sql
CREATE TABLE registros_fotografias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_registro INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    orden TINYINT NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_registro) REFERENCES registros_climaticos(id)
);
```

## 🔧 Configuración PHP

Los límites de subida están configurados en `backend_movil/.htaccess`:

```apache
php_value upload_max_filesize 10M
php_value post_max_size 20M
php_value max_execution_time 300
php_value max_input_time 300
```

## 🚨 Solución de Problemas

### Error: "No se puede crear el directorio"

```bash
# Verificar permisos
ls -la uploads/

# Aplicar permisos correctos
chmod -R 777 uploads/
```

### Error: "Archivo muy grande"

- Verificar límites en `.htaccess`
- Verificar `php.ini` del servidor
- Reiniciar servidor web después de cambios

### Error: "Tipo de archivo no permitido"

- Solo se permiten: JPG, JPEG, PNG
- El backend valida tanto MIME type como extensión

## 📊 Mantenimiento

### Limpieza de archivos huérfanos:

```bash
# Buscar fotos sin registro en BD (ejecutar desde backend_movil/)
php scripts/limpiar_fotos_huerfanas.php
```

### Backup:

```bash
# Respaldar todas las fotos
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

## 🔒 Seguridad

- ✅ Validación de tipo MIME
- ✅ Validación de extensión
- ✅ Límite de tamaño
- ✅ Nombres de archivo únicos (timestamp)
- ✅ Organización por usuario
- ✅ Solo técnicos autenticados pueden subir

## 📝 Notas

- Los archivos se organizan por usuario para facilitar la gestión
- Las rutas relativas se almacenan en la BD para portabilidad
- El sistema crea automáticamente los directorios necesarios
- Se recomienda implementar rotación de logs y limpieza periódica
