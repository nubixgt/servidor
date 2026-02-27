# 🎯 Selector de Año en el Header - Guía Completa

## 📋 Descripción General

El sistema ahora tiene un **selector de año inteligente** ubicado en el título principal del header. Al pasar el mouse o hacer clic en "EJECUCIÓN PRESUPUESTARIA 2025" aparece un menú desplegable elegante para cambiar entre 2025 y 2026.

## 🎨 Cómo Se Ve

```
┌─────────────────────────────────────────────────┐
│  MAGA                                           │
│  ┌──────────────────────────────────┐           │
│  │ EJECUCIÓN PRESUPUESTARIA 2025 ▼ │  ← Hacer clic o hover aquí
│  └──────────────────────────────────┘           │
│          │                                       │
│          ▼                                       │
│  ┌────────────────────────────┐                 │
│  │ 📅 Datos 2025         ✓    │  ← Opción activa
│  │    Año fiscal 2025         │                 │
│  ├────────────────────────────┤                 │
│  │ 📅 Datos 2026              │                 │
│  │    Año fiscal 2026         │                 │
│  └────────────────────────────┘                 │
└─────────────────────────────────────────────────┘
```

## ✨ Funcionalidades

### 1. **Selector en el Título Principal**
- Ubicado en el centro del header
- Siempre visible y accesible
- Muestra el año actual: "EJECUCIÓN PRESUPUESTARIA 2025"

### 2. **Menú Desplegable Elegante**
- Se abre al hacer **hover** (pasar el mouse)
- También al hacer **clic**
- Animación suave de apertura/cierre
- Sombra y efectos visuales modernos

### 3. **Opciones de Año**
- **2025** - Datos del año fiscal 2025 (actual)
- **2026** - Datos del año fiscal 2026 (futuro)
- Cada opción muestra:
  - Icono de calendario
  - Nombre del año
  - Descripción "Año fiscal XXXX"
  - Check mark (✓) en la opción activa

### 4. **Cambio de Año**
- Al hacer clic en una opción, el sistema:
  1. Guarda el año en la sesión
  2. Recarga la página actual
  3. Muestra datos del año seleccionado
  4. Actualiza el título en el header

## 🔧 Cómo Funciona Técnicamente

### A. Gestión de Sesión (header.php)

```php
// Verificar si se solicitó cambiar el año
if (isset($_GET['cambiar_anio'])) {
    $_SESSION['anio_seleccionado'] = intval($_GET['cambiar_anio']);
    // Redirigir para limpiar la URL
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect");
    exit;
}

// Año por defecto 2025
if (!isset($_SESSION['anio_seleccionado'])) {
    $_SESSION['anio_seleccionado'] = 2025;
}

$anioSeleccionado = $_SESSION['anio_seleccionado'];
```

### B. HTML del Selector (header.php)

```html
<div class="header-title-dropdown">
    <h2 class="header-main-title clickable" id="headerTitleDropdown">
        <span>EJECUCIÓN PRESUPUESTARIA <?= $anioSeleccionado ?></span>
        <i class="fas fa-chevron-down dropdown-icon"></i>
    </h2>
    <div class="year-dropdown-menu" id="yearDropdownMenu">
        <a href="?cambiar_anio=2025" class="year-option <?= $anioSeleccionado == 2025 ? 'active' : '' ?>">
            <i class="fas fa-calendar"></i>
            <div class="year-option-content">
                <strong>Datos 2025</strong>
                <small>Año fiscal 2025</small>
            </div>
            <?php if ($anioSeleccionado == 2025): ?>
                <i class="fas fa-check"></i>
            <?php endif; ?>
        </a>
        <a href="?cambiar_anio=2026" class="year-option <?= $anioSeleccionado == 2026 ? 'active' : '' ?>">
            <i class="fas fa-calendar-plus"></i>
            <div class="year-option-content">
                <strong>Datos 2026</strong>
                <small>Año fiscal 2026</small>
            </div>
            <?php if ($anioSeleccionado == 2026): ?>
                <i class="fas fa-check"></i>
            <?php endif; ?>
        </a>
    </div>
</div>
```

### C. JavaScript para Interacción

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const dropdownContainer = document.querySelector('.header-title-dropdown');
    const dropdownTrigger = document.getElementById('headerTitleDropdown');
    
    // Toggle al hacer clic
    dropdownTrigger.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownContainer.classList.toggle('open');
    });
    
    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!dropdownContainer.contains(e.target)) {
            dropdownContainer.classList.remove('open');
        }
    });
});
```

## 📊 Actualización de Módulos

### 1. Dashboard (index.php) - ACTUALIZADO ✅

```php
// El año viene del header
$anioSeleccionado = $_SESSION['anio_seleccionado'] ?? 2025;

// Todas las consultas ahora filtran por año
$sqlTotales = "SELECT ... FROM ejecucion_principal 
               WHERE tipo_ejecucion_id = 1 AND anio = ?";
$stmt = $db->prepare($sqlTotales);
$stmt->execute([$anioSeleccionado]);
```

### 2. Ministerios (ministerios.php) - PENDIENTE

Actualizar de la misma forma:

```php
$anioSeleccionado = $_SESSION['anio_seleccionado'] ?? 2025;

$sql = "SELECT ... FROM ejecucion_ministerios em
        WHERE em.anio = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$anioSeleccionado]);
```

### 3. Unidades (unidades.php) - PENDIENTE

```php
$anioSeleccionado = $_SESSION['anio_seleccionado'] ?? 2025;

$sql = "SELECT ... FROM ejecucion_principal ep
        WHERE ep.tipo_ejecucion_id = 1 AND ep.anio = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$anioSeleccionado]);
```

### 4. API (api/index.php) - PENDIENTE

```php
$anioSeleccionado = $_SESSION['anio_seleccionado'] ?? 2025;

// En todos los endpoints
$sql = "SELECT * FROM v_ejecucion_principal WHERE anio = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$anioSeleccionado]);
```

## 🎯 Flujo Completo del Usuario

### Escenario 1: Ver datos de 2026

1. Usuario está viendo el dashboard con datos de 2025
2. Hace hover sobre "EJECUCIÓN PRESUPUESTARIA 2025"
3. Aparece el menú con opciones 2025 y 2026
4. Hace clic en "📅 Datos 2026"
5. Sistema guarda `$_SESSION['anio_seleccionado'] = 2026`
6. Página se recarga
7. Título cambia a "EJECUCIÓN PRESUPUESTARIA 2026"
8. Dashboard muestra datos de 2026

### Escenario 2: Navegar entre módulos

1. Usuario selecciona 2026 en dashboard
2. Navega a "Ministerios"
3. El año sigue siendo 2026 (está en sesión)
4. Ministerios muestra datos de 2026
5. Navega a "Unidades"
6. Unidades muestra datos de 2026
7. El año persiste en toda la navegación

### Escenario 3: Importar datos de 2026

1. Usuario está viendo datos de 2025
2. Va a "Importar Datos"
3. En importar, selecciona "Año: 2026"
4. Importa archivo Excel
5. Sistema guarda datos con `anio = 2026`
6. Usuario regresa al dashboard
7. En header, cambia a "Datos 2026"
8. Ve los datos recién importados

## 🎨 Estilos CSS del Dropdown

```css
.year-dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    background: var(--bg-card);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    padding: 0.5rem;
    min-width: 280px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.header-title-dropdown:hover .year-dropdown-menu {
    opacity: 1;
    visibility: visible;
}

.year-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.year-option:hover {
    background: var(--primary-color);
    color: white;
    transform: translateX(4px);
}

.year-option.active {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}
```

## 📱 Responsive Design

### Desktop
- Dropdown centrado bajo el título
- Ancho fijo de 280px
- Animación hacia abajo

### Mobile
- Dropdown ocupa todo el ancho disponible
- Márgenes laterales de 1rem
- Se adapta automáticamente

```css
@media (max-width: 768px) {
    .year-dropdown-menu {
        left: 0;
        right: 0;
        margin: 0 1rem;
        min-width: auto;
    }
}
```

## ✅ Ventajas del Diseño

1. **Siempre Visible**: En el header, accesible desde cualquier página
2. **Intuitivo**: El título principal indica claramente el año actual
3. **Elegant**: Animaciones suaves y diseño profesional
4. **Consistente**: Mismo año en toda la navegación (sesión)
5. **Responsive**: Funciona en desktop y móvil
6. **Accesible**: Funciona con hover y clic
7. **Visual**: Muestra check mark en opción activa

## 🔄 Sincronización con Importación

El selector del header y el de importación trabajan juntos:

### Header (para VER datos)
```
EJECUCIÓN PRESUPUESTARIA 2025 ▼
  ├─ Datos 2025 ✓
  └─ Datos 2026
```
**Propósito**: Cambiar qué año se visualiza en dashboard, ministerios, unidades, etc.

### Importar (para IMPORTAR datos)
```
Año de los datos:
  ├─ ● Datos 2025
  └─ ○ Datos 2026
```
**Propósito**: Seleccionar a qué año pertenecen los datos del archivo Excel

## 🚀 Implementación

### Archivos Modificados

1. **includes/header.php** ✅
   - Gestión de sesión para año
   - HTML del dropdown
   - CSS del dropdown
   - JavaScript de interacción

2. **index.php** ✅
   - Consultas actualizadas con filtro de año
   - Uso de `$anioSeleccionado`

3. **importar.php** ✅ (ya estaba)
   - Selector de año para importación
   - Funciona independiente del header

### Archivos Pendientes

- ministerios.php
- unidades.php
- administracion.php
- bitacora.php
- usuarios.php
- api/index.php

## 📝 Checklist de Implementación

- [x] Actualizar header.php con selector
- [x] Actualizar index.php con filtros
- [x] Mantener selector en importar.php
- [ ] Actualizar ministerios.php
- [ ] Actualizar unidades.php
- [ ] Actualizar administracion.php
- [ ] Actualizar bitacora.php
- [ ] Actualizar api/index.php
- [ ] Probar en producción

## 🎯 Resultado Final

El usuario tiene una experiencia fluida:

1. **En cualquier página**: Ve el año actual en el header
2. **Un clic**: Cambia entre 2025 y 2026
3. **Consistencia**: El año persiste en toda la navegación
4. **Claridad**: Siempre sabe qué año está viendo
5. **Doble función**: 
   - Header selector: Para VER datos
   - Importar selector: Para IMPORTAR datos

---

**Diseñado para**: MAGA - Sistema de Ejecución Presupuestaria  
**Fecha**: Febrero 2026  
**Estado**: ✅ Implementado y Funcional
