# Sistema de Ejecución Presupuestaria - Actualización para Datos 2025 y 2026

## Cambios Implementados

Se ha actualizado el sistema para permitir la importación y gestión separada de datos para los años 2025 y 2026.

## Archivos Modificados

### 1. `actualizar_anio.sql`
Script SQL para actualizar la base de datos con los siguientes cambios:
- Agrega el campo `anio` (tipo INT) a las tablas:
  - `ejecucion_principal`
  - `ejecucion_detalle`
  - `ejecucion_ministerios`
- Crea índices para mejorar el rendimiento de consultas por año
- Actualiza las vistas para incluir el campo `anio`

### 2. `importar.php`
Archivo de importación actualizado con:
- Selector de año en el formulario (2025 o 2026)
- Todas las funciones de importación modificadas para incluir el año:
  - `limpiarDatosAnteriores()` - Limpia solo datos del año seleccionado
  - `importarDatos()` - Recibe el año como parámetro
  - `importarFilaPrincipal()` - Incluye año en INSERT y búsqueda
  - `importarFilaDetalle()` - Incluye año en INSERT y búsqueda
  - `importarFilaMinisterio()` - Incluye año en INSERT y búsqueda

## Instrucciones de Implementación

### Paso 1: Actualizar la Base de Datos

Ejecuta el script SQL en tu base de datos:

```bash
mysql -u usuario -p ejecucion_presupuestaria < actualizar_anio.sql
```

O desde phpMyAdmin:
1. Abre phpMyAdmin
2. Selecciona la base de datos `ejecucion_presupuestaria`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de `actualizar_anio.sql`
5. Haz clic en "Continuar"

### Paso 2: Reemplazar el Archivo importar.php

Reemplaza el archivo `importar.php` actual con el archivo actualizado:

```bash
cp importar.php /ruta/del/sistema/importar.php
```

### Paso 3: Verificar Permisos

Asegúrate de que el servidor web tenga permisos de lectura en los archivos:

```bash
chmod 644 importar.php
```

## Uso del Sistema

### Importar Datos

1. Accede a la sección "Importar Datos"
2. **NUEVO**: Selecciona el año de los datos (2025 o 2026)
3. Selecciona el tipo de datos a importar (Principal, Detalle o Ministerios)
4. Sube el archivo Excel
5. Configura las opciones de importación
6. Haz clic en "Importar Datos"

### Características Importantes

- **Separación por Año**: Los datos de 2025 y 2026 se almacenan por separado
- **Sin Duplicados**: El sistema detecta registros existentes por año
- **Actualización Selectiva**: Puedes actualizar datos de un año sin afectar el otro
- **Limpieza por Año**: La opción "Limpiar antes" solo elimina datos del año seleccionado

## Selector de Año en el Formulario

El formulario ahora incluye dos botones para seleccionar el año:

```
┌─────────────────┬─────────────────┐
│   📅 Datos 2025  │  📅 Datos 2026  │
│ Año fiscal 2025 │ Año fiscal 2026 │
└─────────────────┴─────────────────┘
```

Por defecto, se selecciona el año 2025.

## Consultas con Filtro de Año

Para consultar datos de un año específico en otros módulos del sistema, recuerda agregar el filtro `WHERE anio = 2025` o `WHERE anio = 2026` en las consultas SQL.

Ejemplo:
```sql
SELECT * FROM ejecucion_principal WHERE anio = 2025;
SELECT * FROM ejecucion_detalle WHERE anio = 2026;
SELECT * FROM ejecucion_ministerios WHERE anio = 2025;
```

## Vistas Actualizadas

Las vistas `v_ejecucion_principal`, `v_ejecucion_detalle` y `v_ejecucion_ministerios` ahora incluyen el campo `anio`, lo que facilita las consultas:

```sql
SELECT * FROM v_ejecucion_principal WHERE anio = 2025;
```

## Notas Importantes

1. **Datos Existentes**: Al ejecutar el script SQL, todos los registros existentes recibirán el valor `anio = 2025` por defecto
2. **Índices**: Se crearon índices en el campo `anio` para mejorar el rendimiento
3. **Bitácora**: Las operaciones de importación registran el año en la bitácora
4. **Compatibilidad**: Los cambios son retrocompatibles con datos existentes

## Próximos Pasos Recomendados

1. Actualizar el dashboard (`index.php`) para incluir un selector de año
2. Modificar los reportes para filtrar por año
3. Actualizar los módulos de administración para gestionar años
4. Considerar agregar una tabla de "años disponibles" si el sistema se expandirá a más años

## Soporte

Para cualquier duda o problema con la implementación, contacta al desarrollador del sistema.

---
**Fecha de Actualización**: Febrero 2026
**Versión**: 1.1.0
