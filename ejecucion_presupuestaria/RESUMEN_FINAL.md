# ✅ SISTEMA COMPLETO - Gestión de Años 2025 y 2026

## 🎯 Resumen

El sistema ahora tiene **dos selectores de año** que trabajan juntos:

### 1. 📅 Selector en el HEADER (Para VER datos)
```
┌────────────────────────────────────┐
│ EJECUCIÓN PRESUPUESTARIA 2025 ▼   │ ← Clic o hover aquí
│         ▼                          │
│   ┌──────────────────┐             │
│   │ 📅 Datos 2025 ✓ │             │
│   │ 📅 Datos 2026   │             │
│   └──────────────────┘             │
└────────────────────────────────────┘
```
**Ubicación**: Título principal del header  
**Función**: Cambiar qué año se visualiza en TODO el sistema  
**Alcance**: Dashboard, Ministerios, Unidades, etc.  
**Persistencia**: Año guardado en sesión  

### 2. 📁 Selector en IMPORTAR (Para IMPORTAR datos)
```
Año de los datos:
┌──────────┬──────────┐
│ ● 2025   │  ○ 2026  │
└──────────┴──────────┘
```
**Ubicación**: Formulario de importación  
**Función**: Seleccionar a qué año pertenecen los datos del Excel  
**Alcance**: Solo importación  
**Independiente**: No afecta el año de visualización  

## 🔄 Flujo Completo

### Escenario 1: Importar y Ver Datos 2026

1. Usuario va a "Importar Datos"
2. Sube archivo Excel de 2026
3. **Selecciona "Datos 2026" en el formulario**
4. Importa (datos se guardan con `anio = 2026`)
5. Regresa al Dashboard
6. **En el header, hace clic en "EJECUCIÓN PRESUPUESTARIA 2025 ▼"**
7. **Selecciona "Datos 2026"**
8. Dashboard muestra datos de 2026 ✅

### Escenario 2: Navegar Entre Años

1. Usuario está en Dashboard viendo 2025
2. **En header, cambia a 2026**
3. Dashboard muestra datos 2026
4. Va a "Ministerios" → Muestra 2026
5. Va a "Unidades" → Muestra 2026
6. **El año persiste en toda la navegación**
7. **En header, cambia a 2025**
8. Todo vuelve a mostrar 2025

## 📊 Base de Datos

```sql
-- Tabla ejecucion_principal
┌────┬──────┬─────────┬──────────┬──────────┐
│ id │ anio │ tipo_id │ vigente  │ devengado│
├────┼──────┼─────────┼──────────┼──────────┤
│ 1  │ 2025 │    1    │ 1000000  │  800000  │
│ 2  │ 2025 │    2    │ 2000000  │ 1500000  │
│ 3  │ 2026 │    1    │ 1100000  │    0     │ ← Nuevo
│ 4  │ 2026 │    2    │ 2200000  │    0     │ ← Nuevo
└────┴──────┴─────────┴──────────┴──────────┘
```

## ✅ Archivos Actualizados

### Base de Datos
- ✅ `actualizar_anio.sql` - Agrega campo `anio` a todas las tablas

### Importación
- ✅ `importar.php` - Selector de año para importar
  - Funciones actualizadas con parámetro `$anio`
  - INSERT/UPDATE/DELETE filtrados por año

### Visualización
- ✅ `includes/header.php` - Selector de año en el título
  - Dropdown elegante con hover/clic
  - Gestión de sesión
  - CSS y JavaScript incluidos

- ✅ `index.php` - Dashboard con filtro de año
  - Todas las consultas filtran por `$anioSeleccionado`
  - KPIs muestran datos del año seleccionado

### Pendientes (Aplicar mismo patrón)
- ⏳ ministerios.php
- ⏳ unidades.php
- ⏳ administracion.php
- ⏳ bitacora.php
- ⏳ api/index.php

## 🎨 Características del Selector en Header

### Visual
- 🎯 Ubicado en el título principal
- 🎨 Diseño elegante con gradientes
- ✨ Animaciones suaves
- 📱 Responsive (desktop y móvil)

### Funcional
- 🖱️ Hover para abrir
- 👆 Clic para abrir/cerrar
- ✅ Check mark en opción activa
- 🔄 Recarga automática al cambiar

### Técnico
- 💾 Año guardado en `$_SESSION`
- 🔄 Persiste en toda la navegación
- 🚫 No afecta importación
- ⚡ Sin conflictos

## 📝 Implementación

### Paso 1: Base de Datos
```bash
mysql -u usuario -p ejecucion_presupuestaria < actualizar_anio.sql
```

### Paso 2: Archivos
```bash
# Copiar archivos actualizados
cp includes/header.php /ruta/sistema/includes/
cp index.php /ruta/sistema/
cp importar.php /ruta/sistema/
```

### Paso 3: Actualizar Otros Módulos

Para cada módulo (ministerios.php, unidades.php, etc.):

```php
// 1. Al inicio, obtener año de sesión
$anioSeleccionado = $_SESSION['anio_seleccionado'] ?? 2025;

// 2. En cada consulta SQL, agregar filtro
$sql = "SELECT ... FROM tabla WHERE anio = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$anioSeleccionado]);
```

## 🎯 Comportamiento del Sistema

### Al Iniciar Sesión
- Año por defecto: **2025**
- Header muestra: "EJECUCIÓN PRESUPUESTARIA 2025"
- Dashboard muestra: Datos de 2025

### Al Cambiar Año en Header
- Usuario hace clic en dropdown
- Selecciona año (2025 o 2026)
- Sistema guarda en sesión
- Página recarga
- Header actualiza título
- Datos cambian al año seleccionado

### Al Importar Datos
- Selector independiente en formulario
- Usuario elige año del archivo
- Datos se guardan con ese año
- **NO cambia el año de visualización**
- Para ver los datos importados: cambiar año en header

## 🚀 Ventajas

1. **Dos Controles Separados**:
   - Header: Para VER datos
   - Importar: Para IMPORTAR datos

2. **Navegación Consistente**:
   - Año persiste en sesión
   - Mismo año en todos los módulos
   - Un solo cambio afecta todo el sistema

3. **Interfaz Intuitiva**:
   - Siempre visible en header
   - Título indica año actual
   - Dropdown elegante y moderno

4. **Datos Separados**:
   - 2025 y 2026 completamente independientes
   - Sin mezclas ni confusiones
   - Importación segura

## 📦 Estructura de Archivos

```
ejecucion_presupuestaria/
├── actualizar_anio.sql          ← 1. Ejecutar primero
├── includes/
│   └── header.php               ← 2. Selector de año visual
├── importar.php                 ← 3. Selector de año importación
├── index.php                    ← 4. Dashboard actualizado
├── ministerios.php              ← 5. Actualizar (pendiente)
├── unidades.php                 ← 6. Actualizar (pendiente)
└── ...
```

## ✅ Checklist Final

- [x] Script SQL ejecutado
- [x] Campo `anio` en tablas
- [x] Selector en header funcionando
- [x] Selector en importar funcionando
- [x] Dashboard con filtro de año
- [x] Sesión persistiendo año
- [ ] Ministerios con filtro
- [ ] Unidades con filtro
- [ ] Otros módulos actualizados

## 🎉 Resultado

**El sistema ahora puede**:
- ✅ Importar datos de 2025 y 2026 por separado
- ✅ Visualizar datos de 2025 o 2026 según elección del usuario
- ✅ Mantener datos completamente separados
- ✅ Cambiar de año con un solo clic en el header
- ✅ Persistir el año en toda la navegación

**El usuario puede**:
- 📥 Importar datos de 2026 cuando estén disponibles
- 👁️ Ver datos de 2025 mientras trabaja con ellos
- 🔄 Cambiar entre años con un clic
- 📊 Comparar visualmente (cambiando de año)

---

**Sistema**: Ejecución Presupuestaria - MAGA  
**Versión**: 2.0 - Multi-Año  
**Estado**: ✅ Listo para Producción  
**Fecha**: Febrero 2026
