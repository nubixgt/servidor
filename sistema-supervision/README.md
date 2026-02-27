# 📋 SISTEMA DE SUPERVISIÓN v6.0.10 - GLASSMORPHISM EDITION + MEJORAS INVENTARIO

## 📖 Índice

1. [Descripción del Proyecto](#descripción-del-proyecto)
2. [Estructura de Carpetas](#estructura-de-carpetas)
3. [Requisitos del Sistema](#requisitos-del-sistema)
4. [Instalación](#instalación)
5. [Base de Datos](#base-de-datos)
6. [Configuración](#configuración)
7. [Arquitectura del Sistema](#arquitectura-del-sistema)
8. [Módulos y Funcionalidades](#módulos-y-funcionalidades)
9. [Sistema de Roles y Niveles](#sistema-de-roles-y-niveles)
10. [Gestión de Sesiones](#gestión-de-sesiones)
11. [Guía de Desarrollo](#guía-de-desarrollo)
12. [Seguridad](#seguridad)
13. [Solución de Problemas](#solución-de-problemas)
14. [Historial de Versiones](#historial-de-versiones)
15. [Créditos y Licencia](#créditos-y-licencia)

---

## 📝 Descripción del Proyecto

**Sistema de Supervisión v6.0.10** es una aplicación web moderna diseñada para gestionar supervisiones, empleados, contratistas, **proveedores**, proyectos, **inventario** y **manejo de inventario (salidas/ingresos de bodega)** con un diseño **glassmorphism** completamente renovado. El sistema cuenta con **tres niveles de acceso** con permisos diferenciados y **aislamiento total de datos por usuario**:

- **Administrador**: Acceso completo a TODOS los registros del sistema
- **Técnico Básico**: Solo VE y GESTIONA sus propias supervisiones
- **Técnico Completo**: Solo VE y GESTIONA sus propias supervisiones e inventario

### 🎨 Diseño Glassmorphism

- ✨ **Glassmorphism completo** en todas las páginas
- 🔔 **SweetAlert2** para modales elegantes
- 📊 **Animaciones suaves** y transiciones
- 🎨 **Colores únicos** por módulo
- 📱 **100% responsive** en todos los dispositivos
- 💎 **Efectos de vidrio esmerilado** (backdrop-filter)
- 🌈 **Gradientes modernos** en headers
- ⚡ **Rendimiento optimizado**

### 🆕 Novedades v6.0.10 (15 Ene 2026) - 📦 MEJORAS EN INVENTARIO + CORRECCIONES

**CAMBIOS IMPLEMENTADOS:**

**1. Módulo de Inventario - Campo Cantidad Agregado:**

**Campo AGREGADO:**

- ✨ **cantidad** (INT) - Cantidad de equipos del mismo tipo
- ✨ Valor por defecto: 1
- ✨ Validación: Entero mayor o igual a 1
- ✨ Ubicación: Después de `tipo_equipo`

**Formulario Actualizado:**

- ✨ Nuevo campo numérico "Cantidad" con validación HTML5
- ✨ Input type="number" con min="1" y step="1"
- ✨ Texto de ayuda: "Cantidad de equipos de este tipo"
- ✨ Validación en frontend y backend

**Tabla Actualizada:**

- ✨ Nueva columna "Cantidad" con badge morado/púrpura
- ✨ Badge con gradiente: `linear-gradient(135deg, #ede9fe 0%, #c4b5fd 100%)`
- ✨ Color de texto: `#5b21b6`
- ✨ Estilo glassmorphism consistente

**Modal de Detalles:**

- ✨ Nueva card "Cantidad" con icono 🔢
- ✨ Formato: "X unidad(es)"
- ✨ Colores: Fondo morado claro, texto morado oscuro

**API Actualizada:**

- ✅ Captura y validación de cantidad en POST (crear)
- ✅ Captura y validación de cantidad en PUT (editar)
- ✅ Validación: `filter_var($cantidad, FILTER_VALIDATE_INT) && $cantidad >= 1`
- ✅ Incluida en consultas SELECT con JOIN

**2. Tipo de Equipo - Entrada Libre:**

**Cambio de Campo:**

- ✨ Cambiado de `<select>` a `<input type="text">`
- ✨ Permite entrada manual libre de cualquier tipo de equipo
- ✨ Validación: Texto requerido, no vacío
- ✨ Placeholder: "Ej: Excavadora hidráulica, Bulldozer, etc."

**Beneficios:**

- ✅ Mayor flexibilidad para tipos de equipo personalizados
- ✅ No limitado a opciones predefinidas
- ✅ Mejor adaptación a necesidades específicas

**3. Corrección de Alertas en Login:**

**Problema Solucionado:**

- ❌ **Antes**: Al cerrar sesión y luego intentar login incorrecto, mostraba "Sesión Cerrada" en lugar de "Error de Autenticación"
- ✅ **Ahora**: Limpia parámetros de URL después de mostrar alertas de logout/sesión expirada

**Implementación:**

- ✨ Agregado `window.history.replaceState()` en callbacks de SweetAlert2
- ✨ Limpia `?logout=success` después de mostrar alerta de logout
- ✨ Limpia `?sesion=expirada` después de mostrar alerta de sesión expirada
- ✨ Evita conflictos entre mensajes de alerta

**Flujo Corregido:**

1. Usuario cierra sesión → Muestra "¡Sesión Cerrada!" → Limpia URL
2. Usuario intenta login incorrecto → Muestra "¡Error de Autenticación!"
3. Sesión expira → Muestra "Sesión Expirada" → Limpia URL
4. Mensajes de error ahora se muestran correctamente

**4. Corrección de Fondos de Página:**

**Cambio Estético:**

- ✨ **Manejo de Inventario**: Fondo azul, elementos mantienen color naranja 🟠
- ✨ **Proveedores**: Fondo azul, elementos mantienen color morado 🟣
- ✨ Solo se cambió `--bg-gradient` a azul
- ✨ Todos los demás colores (headers, tarjetas, badges) mantienen colores originales

**5. Corrección de Fotos en Manejo de Inventario:**

**Problemas Solucionados:**

- ✅ **Fotos se reemplazaban**: Ahora se agregan correctamente sin reemplazarse
- ✅ **Botón X no funcionaba**: Ahora elimina fotos correctamente
- ✅ **Validación de máximo**: Ahora considera fotos ya seleccionadas + existentes + nuevas
- ✅ **Índices incorrectos**: Recalcula índices después de cada eliminación

**Implementación:**

- ✨ Función `mostrarPreview()` actualizada para no eliminar previews anteriores
- ✨ Función `removeImage()` reescrita para reconstruir previews con índices correctos
- ✨ Validación mejorada: `fotosExistentes + fotosYaSeleccionadas + files.length`
- ✨ Mensajes de error más precisos sobre espacio disponible

**SQL ejecutado v6.0.10:**

```sql
-- Agregar columna cantidad a inventario
ALTER TABLE inventario
ADD COLUMN cantidad INT NULL DEFAULT 1 AFTER tipo_equipo;
```

**Archivos modificados v6.0.10:**

- `modules/admin/inventario.php` (agregada columna cantidad en consulta SQL)
- `assets/js/pages/inventario.js` (campo cantidad en formulario y modal)
- `api/inventario.php` (validación y guardado de cantidad)
- `assets/css/pages/inventario.css` (badge de cantidad morado)
- `login.php` (limpieza de parámetros URL en alertas)
- `assets/css/pages/manejo_inventario.css` (fondo azul, elementos naranja)
- `assets/css/pages/proveedores.css` (fondo azul, elementos morado)
- `assets/js/pages/manejo_inventario.js` (corrección de fotos)
- `README.md` (esta actualización)

### 🆕 Novedades v6.0.9 (28 Nov 2025) - 🏗️ MÓDULO DE PROYECTOS ACTUALIZADO

**CAMBIOS IMPLEMENTADOS:**

**1. Módulo de Proyectos Completamente Rediseñado:**

**Campos AGREGADOS:**

- ✨ **consejo** (DECIMAL 15,2) - Aporte del Consejo de Desarrollo
- ✨ **muni** (DECIMAL 15,2) - Aporte Municipal
- ✨ **odc** (DECIMAL 15,2) - ODC (Orden de Compra)

**Cambios en Presupuesto:**

- ✅ **presupuesto** ahora se calcula AUTOMÁTICAMENTE: `presupuesto = consejo + muni`
- ✅ Formateo automático de moneda en todos los campos monetarios
- ✅ Validación para que consejo y muni sean mayores o iguales a 0

**Formulario Actualizado:**

- ✨ 4 filas reorganizadas para mejor UX
- ✨ Campos monetarios con formato Q0,000.00
- ✨ Cálculo en tiempo real del presupuesto total
- ✨ Validaciones HTML5 en tiempo real
- ✨ Campo ODC independiente (no suma al presupuesto)
- ✨ Textos de ayuda debajo de campos críticos

**Fila 1:** Nombre del Proyecto (ancho completo)
**Fila 2:** Tipo de Proyecto + Ubicación
**Fila 3:** Consejo + Municipal (cálculo automático → Presupuesto Total)
**Fila 4:** ODC + Cliente

**Tabla Actualizada:**

- ✨ Nueva columna "Consejo" con formato Q0,000.00
- ✨ Nueva columna "Municipal" con formato Q0,000.00
- ✨ Nueva columna "ODC" con formato Q0,000.00
- ✨ Columna "Presupuesto" muestra suma de Consejo + Muni
- ✨ Mantiene diseño glassmorphism naranja

**Modal de Detalles (SweetAlert2):**

- ✨ 15 cards coloridas (agregadas 3 nuevas):
  1. 🔵 ID
  2. 🟢 Nombre
  3. 🟣 Tipo
  4. 🟡 Ubicación
  5. 📝 Descripción
  6. 🟠 Estado
  7. 📅 Fecha Inicio
  8. 📅 Fecha Fin Estimada
  9. 📅 Fecha Fin Real
  10. 💰 **Consejo** (formato Q0,000.00) ✨ NUEVO
  11. 💵 **Municipal** (formato Q0,000.00) ✨ NUEVO
  12. 💎 Presupuesto Total (Consejo + Muni)
  13. 📋 **ODC** (formato Q0,000.00) ✨ NUEVO
  14. 👤 Cliente
  15. 🗓️ Fecha de Registro

**API Actualizada:**

- ✅ Cálculo automático de presupuesto (consejo + muni)
- ✅ Validación de campos monetarios (≥ 0)
- ✅ Limpieza automática de formato de moneda
- ✅ Formateo en respuestas JSON
- ✅ Actualizado INSERT y UPDATE con nuevos campos

**SQL ejecutado v6.0.9:**

```sql
-- Agregar nuevas columnas monetarias
ALTER TABLE proyectos
ADD COLUMN consejo DECIMAL(15,2) NULL DEFAULT 0.00 AFTER presupuesto,
ADD COLUMN muni DECIMAL(15,2) NULL DEFAULT 0.00 AFTER consejo,
ADD COLUMN odc DECIMAL(15,2) NULL DEFAULT 0.00 AFTER muni;

-- Opcional: Actualizar presupuestos existentes si es necesario
-- UPDATE proyectos SET consejo = presupuesto, muni = 0.00 WHERE consejo IS NULL;
```

**Archivos modificados v6.0.9:**

- `modules/admin/proyectos.php`
- `assets/css/pages/proyectos.css`
- `assets/js/pages/proyectos.js`
- `api/proyectos.php`
- `README.md`

### 🆕 Novedades v6.0.8 (27 Nov 2025) - 👷 MÓDULO DE EMPLEADOS ACTUALIZADO

**CAMBIOS IMPLEMENTADOS:**

**1. Módulo de Empleados Completamente Rediseñado:**

**Campos ELIMINADOS:**

- ❌ **email** (VARCHAR 100) - Campo completamente removido de la tabla

**Campos AGREGADOS:**

- ✨ **fecha_nacimiento** (DATE) - Fecha de nacimiento del trabajador
- ✨ **fecha_contratacion** (DATE) - Fecha de contratación
- ✨ **salario** (DECIMAL 10,2) - Salario en quetzales con formato Q0,000.00
- ✨ **horas_extra** (INT) - Número de horas extras (solo enteros positivos)
- ✨ **modalidad** (ENUM) - Modalidad de contratación: "Plan 24", "Mes", "Destajo"

**Validaciones Estrictas:**

- ✅ **DPI:** Exactamente 13 dígitos (ejemplo: 2156789012345)
- ✅ **Teléfono:** Exactamente 8 dígitos (ejemplo: 45289012)
- ✅ **Salario:** Formateo automático al salir del campo → Q3,500.00
- ✅ **Horas Extra:** Solo números enteros positivos
- ✅ **Modalidad:** Solo 3 opciones válidas

**Tabla Actualizada:**

- ✨ Nueva columna "Modalidad" con badges coloridos:
  - **Plan 24**: Badge azul (#dbeafe → #93c5fd)
  - **Mes**: Badge morado (#ede9fe → #c4b5fd)
  - **Destajo**: Badge naranja (#ffedd5 → #fdba74)
- ✨ Columna "Salario" con formato Q0,000.00
- ✨ Mantiene diseño glassmorphism verde

**Formulario Modernizado:**

- ✨ 6 filas organizadas lógicamente
- ✨ Validaciones HTML5 en tiempo real
- ✨ Formateo automático de campos:
  - DPI: Solo números, máximo 13
  - Teléfono: Solo números, máximo 8
  - Salario: Formateo con blur → Q0,000.00
  - Horas Extra: Solo enteros positivos
- ✨ Textos de ayuda debajo de campos críticos
- ✨ Select con 3 modalidades predefinidas

**Modal de Detalles (SweetAlert2):**

- ✨ 13 cards coloridas (agregadas 5 nuevas):
  1. 🔵 ID
  2. 🟢 Nombre
  3. 🟣 Contratista
  4. 🟡 Puesto
  5. 🔵 DPI
  6. 🩷 Teléfono
  7. ⚪ Fecha de Nacimiento
  8. 🟦 Fecha de Contratación
  9. 🟢 Salario (formato Q0,000.00)
  10. 🔵 Horas Extra ("X horas extras")
  11. 🟡 Modalidad
  12. 🟠 Estado
  13. 🟦 Fecha de Registro

**API Actualizada:**

- ✅ Validación de DPI (13 dígitos exactos)
- ✅ Validación de teléfono (8 dígitos exactos)
- ✅ Limpieza automática de salario (quita formato)
- ✅ Validación de horas extra (solo enteros ≥ 0)
- ✅ Validación de modalidad (solo 3 opciones)
- ✅ Actualizado INSERT y UPDATE con nuevos campos

**SQL ejecutado v6.0.8:**

```sql
-- 1. Eliminar columna email
ALTER TABLE trabajadores DROP COLUMN email;

-- 2. Agregar nuevas columnas
ALTER TABLE trabajadores
ADD COLUMN fecha_nacimiento DATE NULL AFTER telefono,
ADD COLUMN fecha_contratacion DATE NULL AFTER fecha_nacimiento,
ADD COLUMN salario DECIMAL(10,2) NULL AFTER fecha_contratacion,
ADD COLUMN horas_extra INT NULL DEFAULT 0 AFTER salario,
ADD COLUMN modalidad ENUM('Plan 24', 'Mes', 'Destajo') NULL AFTER horas_extra;
```

**Archivos modificados v6.0.8:**

- `modules/admin/empleados.php`
- `assets/css/pages/empleados.css`
- `assets/js/pages/empleados.js`
- `api/trabajadores.php`
- `README.md`

### 🆕 Novedades v6.0.7 (26 Nov 2025) - 📦 MÓDULO DE MANEJO DE INVENTARIO

**CAMBIOS IMPLEMENTADOS:**

**1. Nuevo Módulo de Manejo de Inventario:**

- ✨ Creadas 2 tablas: `manejo_inventario` y `manejo_inventario_fotografias`
- ✨ Módulo completo con glassmorphism **naranja/ámbar**
- ✨ Gestión de **Salidas e Ingresos de Bodega**
- ✨ Sistema de fotografías (mínimo 1, máximo 2 fotos)
- ✨ Validación estricta de archivos (JPG, PNG, WEBP)
- ✨ Tamaño máximo: 5MB por foto
- ✨ Badge "FOTO EXISTENTE" en modo edición
- ✨ Eliminar fotos individuales con botón X
- ✨ Agregar fotos nuevas al editar (mantiene existentes)
- ✨ Estadísticas animadas (Total, Salidas, Ingresos)

**Campos del Manejo de Inventario:**

- 📦 Producto (select con 6 opciones)
- 🔄 Tipo de Gestión (Salida/Ingreso de Bodega)
- 🏗️ Proyecto (FK a proyectos)
- 👷 Trabajador (FK a trabajadores)
- 📅 Fecha de Entrega (DATE)
- 📝 Observaciones (opcional)
- 📸 Fotografías (1-2 fotos obligatorias)

**Productos disponibles:**

1. Excavadora hidráulica
2. Retroexcavadora
3. Patrol
4. Motoniveladora
5. Minicargador
6. Cargador frontal

**2. Actualización del Navbar Admin:**

- ✨ Agregado "Manejo de Inventario" en el navbar
- ✨ Ubicado después de "Inventario"
- ✨ Icono de paquete/caja (SVG)
- ✨ Animaciones actualizadas (10 items)
- ✨ CSS actualizado para 10 elementos
- ✨ Comparación exacta en selección (evita conflictos)

**3. Correcciones Críticas en Módulo Inventario:**

**Problemas solucionados v6.0.7:**

- ✅ **Campo costo:** Ahora acepta múltiples dígitos (antes solo primer dígito)
- ✅ **Formateo blur:** Solo formatea al salir del campo (no mientras escribe)
- ✅ **Fotos en edición:** Badge "FOTO EXISTENTE" visible
- ✅ **Preservación fotos:** Al cargar nuevas, mantiene las existentes
- ✅ **Botón X funcional:** Elimina fotos individuales correctamente
- ✅ **Modal Ver:** Muestra proveedor (eliminada ubicación GPS)
- ✅ **API JOIN:** Incluye nombre del proveedor en consultas
- ✅ **Referencias GPS:** Eliminadas todas las funciones de ubicación

### 🆕 Novedades v6.0.6 (25 Nov 2025) - 🏪 MÓDULO DE PROVEEDORES + MEJORAS INVENTARIO

**CAMBIOS IMPLEMENTADOS:**

**1. Nuevo Módulo de Proveedores:**

- ✨ Creada tabla `proveedores` con 8 columnas
- ✨ Módulo completo: `modules/admin/proveedores.php`
- ✨ API REST: `api/proveedores.php` (CRUD completo)
- ✨ JavaScript: `assets/js/pages/proveedores.js`
- ✨ CSS glassmorphism morado/púrpura: `assets/css/pages/proveedores.css`
- ✨ Validación estricta de teléfono (8 dígitos exactos)
- ✨ Integración con inventario mediante Foreign Key

**Campos del Proveedor:**

- 🏪 Nombre (obligatorio)
- 📄 NIT (opcional)
- 📞 Teléfono (8 dígitos, validación estricta)
- 📝 Observaciones (opcional)
- ⚡ Estado (activo/inactivo)

**2. Mejoras en Inventario:**

- ✨ **Campo Costo del Equipo** (DECIMAL 10,2, formato Q0.00)
- ✨ **Campo Proveedor** (Foreign Key a tabla proveedores)
- ✨ **Campo Fecha de Compra** (DATE)
- ❌ **Eliminado campo Ubicación del Equipo** (texto + GPS)
- ✅ Formateo automático de costo con evento blur
- ✅ Select de proveedores con lista real de proveedores activos
- ✅ Campo costo con estilo NORMAL (sin color especial)

### Tecnologías Utilizadas

- **Backend:** PHP 7.4+ con PDO
- **Frontend:** HTML5, CSS3 (Glassmorphism), JavaScript (Vanilla)
- **Base de Datos:** MySQL 5.7+ / MariaDB 10+
- **Librerías:**
  - jQuery 3.7.0
  - DataTables 1.13.7
  - Select2 4.1.0
  - SweetAlert2 11
  - SheetJS (xlsx) 0.20.1
- **Servidor Web:** Apache 2.4+ / Nginx

---

## 📂 Estructura de Carpetas

```
SistemaSupervision/
│
├── 📁 config/                          # Configuraciones del sistema
│   ├── config.php                     # Configuración general + permisos
│   └── database.php                   # Conexión a BD (PDO Singleton)
│
├── 📁 assets/                          # Recursos estáticos
│   ├── 📁 css/
│   │   ├── style.css                  # Estilos base + session manager
│   │   ├── navbar_admin.css           # Navbar administrador ✨ v6.0.7
│   │   ├── navbar_tecnico.css         # Navbar técnico
│   │   └── 📁 pages/                  # ✨ Estilos glassmorphism por página
│   │       ├── login.css              # Login (azul claro)
│   │       ├── dashboard-admin.css    # Dashboard admin (azul)
│   │       ├── dashboard-tecnico.css  # Dashboard técnico
│   │       ├── empleados.css          # Empleados (verde) ✨ ACTUALIZADO v6.0.8
│   │       ├── contratistas.css       # Contratistas (azul)
│   │       ├── proveedores.css        # Proveedores (morado/púrpura)
│   │       ├── proyectos.css          # Proyectos (naranja) ✨ ACTUALIZADO v6.0.9
│   │       ├── inventario.css         # Inventario (rojo) ✨ CORREGIDO v6.0.7
│   │       ├── manejo_inventario.css  # Manejo inventario (naranja) ✨ NUEVO v6.0.7
│   │       ├── nueva-supervision.css  # Nueva supervisión (AZUL)
│   │       └── supervisiones.css      # Listado supervisiones
│   │
│   ├── 📁 js/
│   │   ├── main.js                    # JavaScript base compartido
│   │   ├── session-manager.js         # Gestión de sesión/inactividad
│   │   ├── navbar_admin.js            # JS navbar admin ✨ v6.0.7
│   │   ├── navbar_tecnico.js          # JS navbar técnico
│   │   └── 📁 pages/                  # ✨ JS con SweetAlert2
│   │       ├── login.js
│   │       ├── dashboard-admin.js
│   │       ├── dashboard-tecnico.js
│   │       ├── empleados.js               # ✨ ACTUALIZADO v6.0.8
│   │       ├── contratistas.js
│   │       ├── proveedores.js
│   │       ├── proyectos.js           # ✨ ACTUALIZADO v6.0.9
│   │       ├── inventario.js              # Admin inventario ✨ CORREGIDO v6.0.7
│   │       ├── inventario-tecnico.js      # Técnico inventario
│   │       ├── manejo_inventario.js       # Manejo inventario ✨ NUEVO v6.0.7
│   │       ├── nueva-supervision.js
│   │       ├── supervisiones.js           # Admin supervisiones
│   │       └── supervisiones-tecnico.js   # Técnico supervisiones
│   │
│   └── 📁 images/                     # Imágenes (logos, iconos)
│
├── 📁 includes/                        # Archivos PHP incluibles
│   ├── header.php                     # Header HTML común
│   ├── footer.php                     # Footer HTML común
│   ├── navbar_admin.php               # Navbar del administrador ✨ v6.0.7
│   └── navbar_tecnico.php             # Navbar del técnico
│
├── 📁 modules/                         # Módulos por rol
│   ├── 📁 admin/                      # ✨ Módulos con glassmorphism
│   │   ├── dashboard.php              # Dashboard admin
│   │   ├── empleados.php              # Gestión de empleados ✨ ACTUALIZADO v6.0.8
│   │   ├── contratistas.php           # Gestión de contratistas
│   │   ├── proveedores.php            # Gestión de proveedores
│   │   ├── proyectos.php              # Gestión de proyectos ✨ ACTUALIZADO v6.0.9
│   │   ├── inventario.php             # Gestión de inventario ✨ CORREGIDO v6.0.7
│   │   ├── manejo_inventario.php      # Manejo inventario ✨ NUEVO v6.0.7
│   │   ├── nueva-supervision.php      # Crear supervisión
│   │   └── supervisiones.php          # Listado supervisiones (TODAS)
│   │
│   └── 📁 tecnico/                    # Módulo del técnico
│       ├── dashboard.php              # Dashboard técnico con niveles
│       ├── nueva-supervision.php      # Nueva supervisión (propias)
│       ├── supervisiones-tecnico.php  # Supervisiones (propias)
│       ├── inventario.php             # Inventario (propios)
│       └── reportes.php               # Reportes ⏳ PENDIENTE
│
├── 📁 api/                             # APIs REST para CRUD
│   ├── trabajadores.php               # API de empleados ✨ ACTUALIZADO v6.0.8
│   ├── contratistas.php               # API de contratistas
│   ├── proveedores.php                # API de proveedores
│   ├── proyectos.php                  # API de proyectos ✨ ACTUALIZADO v6.0.9
│   ├── supervisiones.php              # API de supervisiones (con usuario_id)
│   ├── inventario.php                 # API de inventario ✨ CORREGIDO v6.0.7
│   └── manejo_inventario.php          # API manejo inventario ✨ NUEVO v6.0.7
│
├── 📁 public/                          # Archivos públicos
│   └── 📁 uploads/                    # Archivos subidos
│       ├── 📁 inventario/             # Fotografías de equipos
│       └── 📁 manejo_inventario/      # Fotografías manejo inventario ✨ NUEVO v6.0.7
│
├── index.php                           # Página principal
├── login.php                           # Inicio de sesión
├── logout.php                          # Cierre de sesión
└── README.md                           # Este archivo ✨ v6.0.9
```

---

## 💻 Requisitos del Sistema

### Servidor

- **Sistema Operativo:** Linux (Ubuntu 20.04+ / CentOS 7+) o Windows Server
- **Servidor Web:** Apache 2.4+ o Nginx 1.18+
- **PHP:** 7.4 o superior (recomendado: PHP 8.0+)
- **Base de Datos:** MySQL 5.7+ o MariaDB 10.3+

### Extensiones PHP Requeridas

```bash
- php-pdo
- php-mysql
- php-mbstring
- php-json
- php-session
- php-gd              # Para procesamiento de imágenes
- php-fileinfo        # Para validación de tipos de archivo
```

### Navegadores Compatibles (con soporte para backdrop-filter)

- Chrome 90+ ✅
- Firefox 103+ ✅
- Safari 14+ ✅
- Edge 90+ ✅
- Opera 76+ ✅

**Nota:** El efecto glassmorphism requiere navegadores modernos con soporte para `backdrop-filter`.

---

## 🚀 Instalación

### Paso 1: Descargar el Proyecto

```bash
cd /var/www/html/
tar -xzf SistemaSupervision.tar.gz
```

### Paso 2: Configurar Permisos

```bash
sudo chown -R www-data:www-data SistemaSupervision/
sudo chmod -R 755 SistemaSupervision/

# Crear directorios de uploads con permisos de escritura
sudo mkdir -p SistemaSupervision/public/uploads/inventario
sudo mkdir -p SistemaSupervision/public/uploads/manejo_inventario
sudo chmod 775 SistemaSupervision/public/uploads/inventario
sudo chmod 775 SistemaSupervision/public/uploads/manejo_inventario
sudo chown www-data:www-data SistemaSupervision/public/uploads/inventario
sudo chown www-data:www-data SistemaSupervision/public/uploads/manejo_inventario
```

### Paso 3: Crear la Base de Datos

```sql
CREATE DATABASE SistemaSupervision CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Paso 4: Importar las Tablas

Ejecuta los scripts SQL en este orden:

```bash
# 1. Crear tablas principales
mysql -u root -p SistemaSupervision < 01_crear_tablas.sql

# 2. Agregar columna nivel_acceso a usuarios
mysql -u root -p SistemaSupervision -e "ALTER TABLE usuarios ADD COLUMN nivel_acceso ENUM('basico', 'completo') DEFAULT 'basico' AFTER rol;"

# 3. Crear tabla de trabajadores
mysql -u root -p SistemaSupervision < 01_crear_tabla_trabajadores.sql

# 4. Crear tabla de proyectos
mysql -u root -p SistemaSupervision < crear_tabla_proyectos.sql

# 5. Crear tabla de supervisiones
mysql -u root -p SistemaSupervision < crear_tabla_supervisiones.sql

# 6. Agregar columna teléfono (v6.0.3)
mysql -u root -p SistemaSupervision -e "ALTER TABLE supervisiones ADD COLUMN telefono VARCHAR(20) NULL AFTER trabajador_id;"

# 7. Agregar columna usuario_id a supervisiones (v6.0.5)
mysql -u root -p SistemaSupervision -e "
ALTER TABLE supervisiones
ADD COLUMN usuario_id INT NULL AFTER id,
ADD CONSTRAINT fk_supervisiones_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
ON DELETE SET NULL;
"

# 8. Crear tabla de inventario
mysql -u root -p SistemaSupervision < crear_tabla_inventario.sql

# 9. Crear tabla de proveedores (v6.0.6)
mysql -u root -p SistemaSupervision -e "
CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nit VARCHAR(20) NULL,
    telefono VARCHAR(8) NULL,
    observaciones TEXT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaModificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"

# 10. Agregar campos a inventario (v6.0.6)
mysql -u root -p SistemaSupervision -e "
ALTER TABLE inventario
ADD COLUMN costo_equipo DECIMAL(10,2) NULL AFTER tipo_equipo,
ADD COLUMN proveedor_id INT NULL AFTER costo_equipo,
ADD COLUMN fecha_compra DATE NULL AFTER proveedor_id;
"

# 11. Eliminar columnas de ubicación de inventario (v6.0.6)
mysql -u root -p SistemaSupervision -e "
ALTER TABLE inventario
DROP COLUMN ubicacion_texto,
DROP COLUMN ubicacion_latitud,
DROP COLUMN ubicacion_longitud;
"

# 12. Crear Foreign Key de inventario a proveedores (v6.0.6)
mysql -u root -p SistemaSupervision -e "
ALTER TABLE inventario
ADD CONSTRAINT fk_inventario_proveedor
FOREIGN KEY (proveedor_id)
REFERENCES proveedores(id)
ON DELETE SET NULL
ON UPDATE CASCADE;
"

# 13. Agregar columna usuario_id a inventario (v6.0.5)
mysql -u root -p SistemaSupervision -e "
ALTER TABLE inventario
ADD COLUMN usuario_id INT NULL AFTER id,
ADD CONSTRAINT fk_inventario_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
ON DELETE SET NULL;
"

# 14. ✨ NUEVO v6.0.7: Crear tabla manejo_inventario
mysql -u root -p SistemaSupervision -e "
CREATE TABLE IF NOT EXISTS manejo_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto VARCHAR(100) NOT NULL,
    tipo_gestion ENUM('Salida de Bodega', 'Ingreso de Bodega') NOT NULL,
    proyecto_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    fecha_entrega DATE NOT NULL,
    observaciones TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE RESTRICT,
    FOREIGN KEY (trabajador_id) REFERENCES trabajadores(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"

# 15. ✨ NUEVO v6.0.7: Crear tabla manejo_inventario_fotografias
mysql -u root -p SistemaSupervision -e "
CREATE TABLE IF NOT EXISTS manejo_inventario_fotografias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manejo_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_archivo VARCHAR(100) NOT NULL,
    tamanio_bytes INT NOT NULL,
    orden INT NOT NULL,
    fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manejo_id) REFERENCES manejo_inventario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"

# 16. ✨ NUEVO v6.0.8: Actualizar tabla trabajadores (eliminar email, agregar nuevos campos)
mysql -u root -p SistemaSupervision -e "
ALTER TABLE trabajadores DROP COLUMN email;
"

mysql -u root -p SistemaSupervision -e "
ALTER TABLE trabajadores
ADD COLUMN fecha_nacimiento DATE NULL AFTER telefono,
ADD COLUMN fecha_contratacion DATE NULL AFTER fecha_nacimiento,
ADD COLUMN salario DECIMAL(10,2) NULL AFTER fecha_contratacion,
ADD COLUMN horas_extra INT NULL DEFAULT 0 AFTER salario,
ADD COLUMN modalidad ENUM('Plan 24', 'Mes', 'Destajo') NULL AFTER horas_extra;
"

# 17. ✨ NUEVO v6.0.9: Agregar campos monetarios a proyectos
mysql -u root -p SistemaSupervision -e "
ALTER TABLE proyectos
ADD COLUMN consejo DECIMAL(15,2) NULL DEFAULT 0.00 AFTER presupuesto,
ADD COLUMN muni DECIMAL(15,2) NULL DEFAULT 0.00 AFTER consejo,
ADD COLUMN odc DECIMAL(15,2) NULL DEFAULT 0.00 AFTER muni;
"

# 18. Insertar usuario administrador
mysql -u root -p SistemaSupervision < insertar_usuario_ejemplo.sql

# 19. Crear usuarios técnicos de prueba
mysql -u root -p SistemaSupervision -e "
INSERT INTO usuarios (usuario, contrasena, rol, nivel_acceso, email, telefono, estado, fechaCreacion) VALUES
('tecnico_basico', 'tecnico123', 'tecnico', 'basico', 'tecnico.basico@supervision.com', '5555-1234', 'activo', NOW()),
('tecnico_completo', 'tecnico123', 'tecnico', 'completo', 'tecnico.completo@supervision.com', '5555-5678', 'activo', NOW());
"

# 20. Insertar contratista CONCRETO DE ORIENTE
mysql -u root -p SistemaSupervision < 02_insertar_concreto_oriente.sql

# 21. Insertar trabajadores del contratista
mysql -u root -p SistemaSupervision < 03_insertar_trabajadores_concreto_oriente.sql
```

### Paso 5: Configurar el Sistema

#### A) Editar configuración de base de datos

```bash
nano /var/www/html/SistemaSupervision/config/database.php
```

Modificar:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'SistemaSupervision');
define('DB_USER', 'tu_usuario');        // ← CAMBIAR
define('DB_PASS', 'tu_contraseña');     // ← CAMBIAR
```

#### B) Editar configuración general

```bash
nano /var/www/html/SistemaSupervision/config/config.php
```

Modificar:

```php
define('SITE_URL', 'http://tu-dominio.com/SistemaSupervision');  // ← CAMBIAR
define('BASE_PATH', '/var/www/html/SistemaSupervision/');        // ← CAMBIAR

// Timeout de sesión
define('SESSION_TIMEOUT', 1800); // 30 minutos (1800 segundos)
```

### Paso 6: Verificar Instalación

Accede a:

```
http://tu-servidor/SistemaSupervision/
```

Deberías ver el **nuevo login con diseño glassmorphism** ✨

---

## 🗄️ Base de Datos

### Tablas del Sistema ✨ ACTUALIZADO v6.0.9

#### 1. **usuarios**

| Campo         | Tipo         | Descripción                                     |
| ------------- | ------------ | ----------------------------------------------- |
| id            | INT          | ID único (PK, AUTO_INCREMENT)                   |
| usuario       | VARCHAR(50)  | Nombre de usuario (UNIQUE)                      |
| contrasena    | VARCHAR(255) | Contraseña (texto plano por ahora)              |
| rol           | ENUM         | 'administrador' o 'tecnico'                     |
| nivel_acceso  | ENUM         | 'basico', 'completo'                            |
| email         | VARCHAR(100) | Correo electrónico (UNIQUE)                     |
| telefono      | VARCHAR(20)  | Teléfono de contacto                            |
| estado        | ENUM         | 'activo', 'pendiente', 'suspendido', 'inactivo' |
| fechaCreacion | DATETIME     | Fecha de creación                               |
| ultimoAcceso  | DATETIME     | Último acceso al sistema                        |

#### 2. **contratistas**

| Campo             | Tipo         | Descripción                        |
| ----------------- | ------------ | ---------------------------------- |
| id                | INT          | ID único (PK, AUTO_INCREMENT)      |
| nombre            | VARCHAR(150) | Nombre de la empresa               |
| nit               | VARCHAR(20)  | NIT (UNIQUE)                       |
| direccion         | VARCHAR(255) | Dirección física                   |
| telefono          | VARCHAR(20)  | Teléfono                           |
| email             | VARCHAR(100) | Correo electrónico                 |
| contactoPrincipal | VARCHAR(100) | Nombre del contacto                |
| estado            | ENUM         | 'activo', 'inactivo', 'suspendido' |
| fechaCreacion     | DATETIME     | Fecha de registro                  |
| fechaModificacion | DATETIME     | Última modificación                |

#### 3. **proveedores**

| Campo             | Tipo         | Descripción                   |
| ----------------- | ------------ | ----------------------------- |
| id                | INT          | ID único (PK, AUTO_INCREMENT) |
| nombre            | VARCHAR(100) | Nombre del proveedor          |
| nit               | VARCHAR(20)  | NIT (opcional)                |
| telefono          | VARCHAR(8)   | Teléfono (8 dígitos)          |
| observaciones     | TEXT         | Observaciones                 |
| estado            | ENUM         | 'activo', 'inactivo'          |
| fechaCreacion     | DATETIME     | Fecha de creación             |
| fechaModificacion | DATETIME     | Última modificación           |

#### 4. **trabajadores** ✨ ACTUALIZADO v6.0.8

| Campo              | Tipo                                    | Descripción                   |
| ------------------ | --------------------------------------- | ----------------------------- |
| id                 | INT                                     | ID único (PK, AUTO_INCREMENT) |
| contratista_id     | INT                                     | ID del contratista (FK)       |
| nombre             | VARCHAR(100)                            | Nombre del trabajador         |
| telefono           | VARCHAR(20)                             | Teléfono (8 dígitos)          |
| fecha_nacimiento   | DATE                                    | Fecha de nacimiento           |
| fecha_contratacion | DATE                                    | Fecha de contratación         |
| salario            | DECIMAL(10,2)                           | Salario en quetzales          |
| horas_extra        | INT                                     | Número de horas extras        |
| modalidad          | ENUM('Plan 24', 'Mes', 'Destajo')       | Modalidad de contratación     |
| puesto             | VARCHAR(100)                            | Cargo/puesto                  |
| dpi                | VARCHAR(20)                             | DPI guatemalteco (13 dígitos) |
| estado             | ENUM('activo', 'inactivo','suspendido') | Estado del trabajador         |
| fechaCreacion      | DATETIME                                | Fecha de registro             |
| fechaModificacion  | DATETIME                                | Última modificación           |

**⚠️ NOTA:** La columna `email` fue ELIMINADA en v6.0.8

#### 5. **proyectos** ✨ ACTUALIZADO v6.0.9

| Campo              | Tipo          | Descripción                                    |
| ------------------ | ------------- | ---------------------------------------------- |
| id                 | INT           | ID único (PK, AUTO_INCREMENT)                  |
| nombre             | VARCHAR(200)  | Nombre del proyecto                            |
| tipo               | VARCHAR(100)  | Tipo de proyecto (Edificio, Carretera, etc.)   |
| ubicacion          | VARCHAR(255)  | Ubicación del proyecto                         |
| descripcion        | TEXT          | Descripción detallada                          |
| estado             | VARCHAR(50)   | 'activo', 'completado', 'pausado', 'cancelado' |
| fecha_inicio       | DATE          | Fecha de inicio                                |
| fecha_fin_estimada | DATE          | Fecha estimada de finalización                 |
| fecha_fin_real     | DATE          | Fecha real de finalización                     |
| presupuesto        | DECIMAL(15,2) | Presupuesto total (Consejo + Muni)             |
| **consejo**        | DECIMAL(15,2) | **Aporte del Consejo** ✨ NUEVO v6.0.9         |
| **muni**           | DECIMAL(15,2) | **Aporte Municipal** ✨ NUEVO v6.0.9           |
| **odc**            | DECIMAL(15,2) | **ODC** ✨ NUEVO v6.0.9                        |
| cliente            | VARCHAR(150)  | Cliente del proyecto                           |
| fecha_creacion     | DATETIME      | Fecha de registro                              |
| fecha_modificacion | DATETIME      | Última modificación                            |

**⚠️ NOTA:** Las columnas `consejo`, `muni` y `odc` fueron AGREGADAS en v6.0.9

#### 6. **supervisiones**

| Campo              | Tipo        | Descripción                     |
| ------------------ | ----------- | ------------------------------- |
| id                 | INT         | ID único (PK, AUTO_INCREMENT)   |
| usuario_id         | INT         | ID del usuario que creó         |
| proyecto_id        | INT         | ID del proyecto (FK)            |
| contratista_id     | INT         | ID del contratista (FK)         |
| trabajador_id      | INT         | ID del trabajador (FK)          |
| telefono           | VARCHAR(20) | Teléfono de contacto            |
| fecha_supervision  | DATETIME    | Fecha y hora de la supervisión  |
| estado             | VARCHAR(50) | 'pendiente', 'completada', etc. |
| observaciones      | TEXT        | Observaciones de la supervisión |
| fecha_creacion     | DATETIME    | Fecha de registro               |
| fecha_modificacion | DATETIME    | Última modificación             |

#### 7. **inventario** ✨ ACTUALIZADO v6.0.10

| Campo              | Tipo          | Descripción                                                 |
| ------------------ | ------------- | ----------------------------------------------------------- |
| id                 | INT           | ID único (PK, AUTO_INCREMENT)                               |
| usuario_id         | INT           | ID del usuario que creó                                     |
| tipo_equipo        | VARCHAR(200)  | Tipo de equipo (entrada libre)                              |
| **cantidad**       | INT           | **Cantidad de equipos** ✨ NUEVO v6.0.10                    |
| costo_equipo       | DECIMAL(10,2) | Costo del equipo en quetzales                               |
| proveedor_id       | INT           | ID del proveedor (FK)                                       |
| fecha_compra       | DATE          | Fecha de compra                                             |
| proyecto_id        | INT           | ID del proyecto asignado (FK, opcional)                     |
| contratista_id     | INT           | ID del contratista asignado (FK, opcional)                  |
| observaciones      | TEXT          | Observaciones sobre el equipo                               |
| estado             | ENUM          | 'activo', 'en_mantenimiento', 'fuera_servicio', 'dado_baja' |
| fecha_creacion     | DATETIME      | Fecha de registro                                           |
| fecha_modificacion | DATETIME      | Última modificación                                         |

**⚠️ NOTA:** La columna `cantidad` fue AGREGADA en v6.0.10

#### 8. **inventario_fotografias**

| Campo          | Tipo         | Descripción                             |
| -------------- | ------------ | --------------------------------------- |
| id             | INT          | ID único (PK, AUTO_INCREMENT)           |
| inventario_id  | INT          | ID del equipo (FK)                      |
| nombre_archivo | VARCHAR(255) | Nombre original del archivo             |
| ruta_archivo   | VARCHAR(500) | Ruta completa del archivo               |
| tipo_archivo   | VARCHAR(100) | Tipo MIME (image/jpeg, application/pdf) |
| tamanio_bytes  | INT          | Tamaño del archivo en bytes             |
| orden          | INT          | Orden de la fotografía (1, 2, 3)        |
| fecha_subida   | DATETIME     | Fecha de subida                         |

#### 9. **manejo_inventario** ✨ NUEVO v6.0.7

| Campo              | Tipo         | Descripción                             |
| ------------------ | ------------ | --------------------------------------- |
| id                 | INT          | ID único (PK, AUTO_INCREMENT)           |
| usuario_id         | INT          | ID del usuario que creó (FK)            |
| producto           | VARCHAR(100) | Tipo de producto (6 opciones)           |
| tipo_gestion       | ENUM         | 'Salida de Bodega', 'Ingreso de Bodega' |
| proyecto_id        | INT          | ID del proyecto (FK)                    |
| trabajador_id      | INT          | ID del trabajador (FK)                  |
| fecha_entrega      | DATE         | Fecha de entrega                        |
| observaciones      | TEXT         | Observaciones (opcional)                |
| fecha_creacion     | DATETIME     | Fecha de creación                       |
| fecha_modificacion | DATETIME     | Última modificación                     |

**Foreign Keys:**

- `usuario_id` → `usuarios.id` (ON DELETE CASCADE)
- `proyecto_id` → `proyectos.id` (ON DELETE RESTRICT)
- `trabajador_id` → `trabajadores.id` (ON DELETE RESTRICT)

#### 10. **manejo_inventario_fotografias** ✨ NUEVO v6.0.7

| Campo          | Tipo         | Descripción                             |
| -------------- | ------------ | --------------------------------------- |
| id             | INT          | ID único (PK, AUTO_INCREMENT)           |
| manejo_id      | INT          | ID del movimiento (FK)                  |
| nombre_archivo | VARCHAR(255) | Nombre original del archivo             |
| ruta_archivo   | VARCHAR(500) | Ruta completa del archivo               |
| tipo_archivo   | VARCHAR(100) | Tipo MIME (image/jpeg, image/png, etc.) |
| tamanio_bytes  | INT          | Tamaño del archivo en bytes             |
| orden          | INT          | Orden de la fotografía (1, 2)           |
| fecha_subida   | DATETIME     | Fecha de subida                         |

**Foreign Key:**

- `manejo_id` → `manejo_inventario.id` (ON DELETE CASCADE)

### Diagrama de Relaciones ✨ ACTUALIZADO v6.0.9

```
┌─────────────────┐
│    USUARIOS     │
│ + nivel_acceso  │
└────────┬────────┘
         │ 1
         │
         ├──────────► N ┌─────────────────┐
         │              │  SUPERVISIONES  │
         │              │  + usuario_id   │
         │              └─────────────────┘
         │
         ├──────────► N ┌─────────────────┐
         │              │   INVENTARIO    │
         │              │  + usuario_id   │
         │              │  + costo_equipo │
         │              │  + proveedor_id │
         │              │  + fecha_compra │
         │              └────────┬────────┘
         │                       │ N
         │                       │ 1
         │              ┌────────▼────────┐
         │              │   PROVEEDORES   │
         │              │  (id, nombre)   │
         │              └─────────────────┘
         │
         └──────────► N ┌──────────────────────┐
                        │ MANEJO_INVENTARIO    │ ✨ v6.0.7
                        │  + usuario_id        │
                        │  + producto          │
                        │  + tipo_gestion      │
                        └──────────┬───────────┘
                                   │ 1
                                   │ N
                        ┌──────────▼──────────────────────┐
                        │ MANEJO_INVENTARIO_FOTOGRAFIAS   │ ✨ v6.0.7
                        │    (fotos movimientos 1-2)      │
                        └─────────────────────────────────┘

┌─────────────────┐
│  CONTRATISTAS   │
└────────┬────────┘
         │ 1
         │ N
┌────────▼────────────┐
│   TRABAJADORES      │ ✨ ACTUALIZADO v6.0.8
│ - email (ELIMINADO) │
│ + fecha_nacimiento  │
│ + fecha_contratacion│
│ + salario           │
│ + horas_extra       │
│ + modalidad         │
└─────────────────────┘

┌─────────────────┐
│    PROYECTOS    │ ✨ ACTUALIZADO v6.0.9
│ + consejo       │
│ + muni          │
│ + odc           │
│ + presupuesto   │
│   (calc auto)   │
└─────────────────┘
```

---

## 🎯 Módulos y Funcionalidades ✨ ACTUALIZADO v6.0.9

### ✅ Implementado con Glassmorphism

#### 🏗️ Módulo de Proyectos ✨ ACTUALIZADO v6.0.9

**Ubicación:** `modules/admin/proyectos.php`

**Acceso:** Solo Administrador

**Diseño glassmorphism:**

- ✨ Header naranja con gradiente (#f97316, #ea580c)
- ✨ 3 tarjetas de estadísticas animadas (Activos, Completados, Total)
- ✨ Tabla con glassmorphism y hover effects
- ✨ **SweetAlert2** para modales de detalles/confirmación
- ✨ Badges coloridos para estados
- ✨ Icono de edificio (SVG)

**Campos del Formulario:**

1. **Nombre del Proyecto** \* (obligatorio, ancho completo)
2. **Tipo de Proyecto** \* (obligatorio)
3. **Ubicación** \* (obligatorio)
4. **Consejo** \* (DECIMAL 15,2, formato Q0,000.00, obligatorio) ✨ NUEVO
5. **Municipal** \* (DECIMAL 15,2, formato Q0,000.00, obligatorio) ✨ NUEVO
6. **Presupuesto Total** (calculado automáticamente: consejo + muni, solo lectura)
7. **ODC** (DECIMAL 15,2, formato Q0,000.00, opcional) ✨ NUEVO
8. **Cliente** \* (obligatorio)
9. **Descripción** (opcional, textarea)
10. **Fecha de Inicio** (opcional, DATE)
11. **Fecha Fin Estimada** (opcional, DATE)
12. **Estado** (select: activo, completado, pausado, cancelado)

**Validaciones Implementadas:**

- ✅ **Consejo:** Número decimal ≥ 0, formato automático Q0,000.00
- ✅ **Municipal:** Número decimal ≥ 0, formato automático Q0,000.00
- ✅ **ODC:** Número decimal ≥ 0, formato automático Q0,000.00
- ✅ **Presupuesto:** Cálculo automático en tiempo real (consejo + muni)
- ✅ **Formateo blur:** Solo formatea al salir del campo

**Tabla Actualizada:**

Columnas:

- ID
- Nombre (con icono de edificio)
- Tipo
- **Consejo** (formato Q0,000.00) ✨ NUEVO
- **Municipal** (formato Q0,000.00) ✨ NUEVO
- **Presupuesto Total** (formato Q0,000.00)
- **ODC** (formato Q0,000.00) ✨ NUEVO
- Estado (badge colorido)
- Acciones

**Modal de Detalles (SweetAlert2):**

15 cards coloridas (agregadas 3 nuevas):

1. 🔵 ID
2. 🟢 Nombre
3. 🟣 Tipo
4. 🟡 Ubicación
5. 📝 Descripción
6. 🟠 Estado
7. 📅 Fecha Inicio
8. 📅 Fecha Fin Estimada
9. 📅 Fecha Fin Real
10. 💰 **Consejo** (formato Q0,000.00) ✨ NUEVO
11. 💵 **Municipal** (formato Q0,000.00) ✨ NUEVO
12. 💎 Presupuesto Total (formato Q0,000.00)
13. 📋 **ODC** (formato Q0,000.00) ✨ NUEVO
14. 👤 Cliente
15. 🗓️ Fecha de Registro

**API REST:** `/api/proyectos.php`

- GET: Obtener todos o uno específico
- POST: Crear proyecto con cálculo automático de presupuesto
- PUT: Actualizar proyecto
- DELETE: Eliminar proyecto (verifica referencias)

**Funcionalidades:**

- ✅ Crear proyecto con campos monetarios
- ✅ Cálculo automático de presupuesto (consejo + muni)
- ✅ Formateo automático de moneda en blur
- ✅ Editar proyecto existente
- ✅ Ver detalles completos (15 cards)
- ✅ Eliminar con confirmación SweetAlert2
- ✅ Validación de referencias (no elimina si tiene supervisiones)
- ✅ Estadísticas animadas
- ✅ DataTables con búsqueda y paginación
- ✅ Exportación (funcionalidad de DataTables)

#### 👷 Módulo de Empleados ✨ ACTUALIZADO v6.0.8

**Ubicación:** `modules/admin/empleados.php`

**Acceso:** Solo Administrador

**Diseño glassmorphism:**

- ✨ Header verde con gradiente (#10b981, #059669)
- ✨ 3 tarjetas de estadísticas animadas (Activos, Inactivos, Contratistas)
- ✨ Tabla con glassmorphism y hover effects
- ✨ **SweetAlert2** para modales de detalles/confirmación
- ✨ Badges coloridos para estados y modalidades
- ✨ Icono de usuarios (SVG)

**Campos del Formulario:**

1. **Nombre Completo** \* (obligatorio)
2. **Contratista** \* (select, obligatorio)
3. **Puesto/Cargo** \* (obligatorio)
4. **DPI** \* (13 dígitos exactos, obligatorio)
5. **Teléfono** \* (8 dígitos exactos, obligatorio)
6. **Fecha de Nacimiento** (opcional, DATE)
7. **Fecha de Contratación** (opcional, DATE)
8. **Salario** (opcional, DECIMAL, formato Q0,000.00)
9. **Horas Extra** (opcional, INT, solo enteros positivos)
10. **Modalidad** (opcional, ENUM: "Plan 24", "Mes", "Destajo")
11. **Estado** (select: activo, inactivo, suspendido)

**Validaciones Implementadas:**

- ✅ **DPI:** Exactamente 13 dígitos numéricos
- ✅ **Teléfono:** Exactamente 8 dígitos numéricos
- ✅ **Salario:** Formateo automático al salir del campo (Q0,000.00)
- ✅ **Horas Extra:** Solo números enteros positivos
- ✅ **Modalidad:** Solo 3 opciones válidas

**Tabla Actualizada:**

Columnas:

- ID
- Nombre (con avatar circular)
- Contratista
- Puesto
- **Modalidad** (badge colorido) ✨ NUEVO
- **Salario** (formato Q0,000.00) ✨ NUEVO
- Estado (badge colorido)
- Acciones

**Badges de Modalidad:**

- **Plan 24**: Azul (#dbeafe → #93c5fd)
- **Mes**: Morado (#ede9fe → #c4b5fd)
- **Destajo**: Naranja (#ffedd5 → #fdba74)

**Modal de Detalles (SweetAlert2):**

13 cards coloridas:

1. 🔵 ID
2. 🟢 Nombre
3. 🟣 Contratista
4. 🟡 Puesto
5. 🔵 DPI
6. 🩷 Teléfono
7. ⚪ Fecha de Nacimiento (formato DD/MM/YYYY)
8. 🟦 Fecha de Contratación (formato DD/MM/YYYY)
9. 🟢 Salario (formato Q0,000.00)
10. 🔵 Horas Extra ("X horas extras")
11. 🟡 Modalidad
12. 🟠 Estado
13. 🟦 Fecha de Registro

**API REST:** `/api/trabajadores.php`

- GET: Obtener todos o uno específico
- POST: Crear trabajador con validaciones
- PUT: Actualizar trabajador
- DELETE: Eliminar trabajador

**Funcionalidades:**

- ✅ Crear empleado con todos los campos
- ✅ Editar empleado existente
- ✅ Ver detalles completos (13 cards)
- ✅ Eliminar con confirmación SweetAlert2
- ✅ Formateo automático de salario
- ✅ Validación en tiempo real de DPI y teléfono
- ✅ Estadísticas animadas
- ✅ DataTables con búsqueda y paginación
- ✅ Exportación (funcionalidad de DataTables)

#### 📦 Módulo de Manejo de Inventario ✨ v6.0.7

**Ubicación:** `modules/admin/manejo_inventario.php`

**Acceso:** Solo Administrador

**Diseño glassmorphism:**

- ✨ Header naranja/ámbar con gradiente (#f97316, #ea580c)
- ✨ 3 tarjetas de estadísticas animadas
- ✨ Tabla con glassmorphism y hover effects
- ✨ **SweetAlert2** para modales de detalles/confirmación
- ✨ Badges coloridos para tipo de gestión
- ✨ Icono de paquete/caja (SVG)

**Funcionalidades:**

- ✅ Crear Salida/Ingreso de Bodega
- ✅ Editar movimiento existente
- ✅ Ver detalles con galería de fotos
- ✅ Eliminar movimiento con confirmación
- ✅ Eliminar fotos individuales (botón X)
- ✅ Agregar fotos nuevas al editar (máximo 2 total)
- ✅ Mantiene fotos existentes al cargar nuevas
- ✅ Estadísticas (Total, Salidas, Ingresos)
- ✅ Select2 para búsqueda de proyectos/trabajadores
- ✅ Badge "FOTO EXISTENTE" en modo edición

**Validaciones:**

- ✅ **Fotografías:** Mínimo 1, Máximo 2
- ✅ **Formatos:** JPG, PNG, WEBP (5MB máximo por foto)
- ✅ **Campos obligatorios:** Producto, Tipo, Proyecto, Trabajador, Fecha, Fotos

**Productos disponibles:**

1. Excavadora hidráulica
2. Retroexcavadora
3. Patrol
4. Motoniveladora
5. Minicargador
6. Cargador frontal

#### 🏪 Módulo de Proveedores

**Ubicación:** `modules/admin/proveedores.php`

**Acceso:** Solo Administrador

**Diseño glassmorphism:**

- ✨ Header morado/púrpura con gradiente (#8b5cf6, #7c3aed)
- ✨ 3 tarjetas de estadísticas con efecto vidrio
- ✨ Tabla con glassmorphism y hover effects
- ✨ **SweetAlert2** para modal de detalles con 7 cards coloridas
- ✨ Avatar circular con iniciales del proveedor
- ✨ Badges coloridos para estados
- ✨ Icono de bolsa de compras

**Funcionalidades:**

- ✅ Listar proveedores con DataTables
- ✅ Crear nuevo proveedor
- ✅ Editar proveedor existente
- ✅ Ver detalles en SweetAlert2
- ✅ Eliminar con confirmación SweetAlert2
- ✅ Verificación antes de eliminar (si está en uso en inventario)
- ✅ Estadísticas (activos, inactivos, total)
- ✅ Validación estricta de teléfono (exactamente 8 dígitos)

#### 📦 Módulo de Inventario ✨ CORREGIDO v6.0.7

**Ubicación:**

- Admin: `modules/admin/inventario.php`
- Técnico: `modules/tecnico/inventario.php`

**Acceso:**

- Administrador: VE TODOS los equipos
- Técnico Completo: VE SOLO sus equipos

**Correcciones v6.0.7:**

- ✅ Campo costo acepta múltiples dígitos
- ✅ Formateo solo al salir del campo (blur)
- ✅ Fotos existentes NO desaparecen al agregar nuevas
- ✅ Badge "FOTO EXISTENTE" en edición
- ✅ Botón X funcional para eliminar fotos individuales
- ✅ Modal "Ver" muestra proveedor
- ✅ Eliminadas referencias a ubicación GPS
- ✅ API con JOIN a proveedores

**Funcionalidades principales:**

- ✅ **Gestión completa de equipos**
- ✅ **Campo costo con formateo automático**
- ✅ **Selección de proveedor**
- ✅ **Fecha de compra**
- ✅ **Gestión de fotografías** (1-3 fotos por equipo)
- ✅ Formatos: JPG, PNG, WEBP, PDF
- ✅ Tamaño máximo: 5MB por archivo
- ✅ Estados: Activo, En Mantenimiento, Fuera de Servicio, Dado de Baja

---

## 🔒 Sistema de Roles y Niveles

### Matriz de Permisos y Datos ✨ ACTUALIZADO v6.0.9

| Módulo               | Administrador         | Técnico Básico        | Técnico Completo       |
| -------------------- | --------------------- | --------------------- | ---------------------- |
| Dashboard            | ✅ VE TODO            | ✅ VE SOLO SUS DATOS  | ✅ VE SOLO SUS DATOS   |
| **Empleados**        | **✅ VE TODO**        | **❌ SIN ACCESO**     | **❌ SIN ACCESO**      |
| Contratistas         | ✅ VE TODO            | ❌ SIN ACCESO         | ❌ SIN ACCESO          |
| Proveedores          | ✅ VE TODO            | ❌ SIN ACCESO         | ❌ SIN ACCESO          |
| **Proyectos**        | **✅ VE TODO**        | **❌ SIN ACCESO**     | **❌ SIN ACCESO**      |
| Nueva Supervisión    | ✅ CREA (sin user_id) | ✅ CREA (con user_id) | ✅ CREA (con user_id)  |
| Supervisiones        | ✅ VE TODAS           | ✅ VE SOLO LAS SUYAS  | ✅ VE SOLO LAS SUYAS   |
| Inventario           | ✅ VE TODO            | ❌ SIN ACCESO         | ✅ VE SOLO SUS EQUIPOS |
| Manejo de Inventario | ✅ VE TODO            | ❌ SIN ACCESO         | ❌ SIN ACCESO          |
| Reportes             | ✅ TODOS LOS DATOS    | ⏳ SOLO SUS DATOS     | ⏳ SOLO SUS DATOS      |

---

## 🗓️ Historial de Versiones

### v6.0.9 (28 Nov 2025) - 🏗️ MÓDULO DE PROYECTOS ACTUALIZADO

**CAMBIOS IMPLEMENTADOS:**

**1. Tabla proyectos modificada:**

**Campos AGREGADOS:**

- ✨ `consejo` (DECIMAL 15,2, DEFAULT 0.00) - Aporte del Consejo
- ✨ `muni` (DECIMAL 15,2, DEFAULT 0.00) - Aporte Municipal
- ✨ `odc` (DECIMAL 15,2, DEFAULT 0.00) - ODC

**Cambio en presupuesto:**

- ✨ `presupuesto` ahora se calcula automáticamente: `consejo + muni`

**2. Módulo proyectos.php actualizado:**

**Vista (proyectos.php):**

- ✨ Formulario reorganizado en 4 filas
- ✨ 12 campos totales (3 nuevos monetarios)
- ✨ Validaciones HTML5 (min="0" para campos monetarios)
- ✨ Cálculo en tiempo real del presupuesto
- ✨ Tabla con columnas "Consejo", "Municipal", "ODC"
- ✨ Mantiene glassmorphism naranja

**JavaScript (proyectos.js):**

- ✨ Nueva función `calcularPresupuesto()`
- ✨ Event listeners para cálculo automático
- ✨ Formateo de consejo, muni y odc con blur
- ✨ Validaciones actualizadas
- ✨ Modal con 15 cards (agregadas 3 nuevas)
- ✨ Limpieza de formato antes de enviar

**CSS (proyectos.css):**

- ✨ Sin cambios (mantiene diseño existente)

**API (proyectos.php):**

- ✨ Cálculo automático de presupuesto en POST y PUT
- ✨ Validación de campos monetarios (≥ 0)
- ✨ Limpieza automática de formato
- ✨ Actualizado INSERT y UPDATE
- ✨ Formateo en respuestas JSON

**SQL ejecutado:**

```sql
ALTER TABLE proyectos
ADD COLUMN consejo DECIMAL(15,2) NULL DEFAULT 0.00 AFTER presupuesto,
ADD COLUMN muni DECIMAL(15,2) NULL DEFAULT 0.00 AFTER consejo,
ADD COLUMN odc DECIMAL(15,2) NULL DEFAULT 0.00 AFTER muni;
```

**Archivos modificados v6.0.9:**

- `modules/admin/proyectos.php`
- `assets/css/pages/proyectos.css` (sin cambios)
- `assets/js/pages/proyectos.js`
- `api/proyectos.php`
- `README.md`

---

### v6.0.8 (27 Nov 2025) - 👷 MÓDULO DE EMPLEADOS ACTUALIZADO

**CAMBIOS IMPLEMENTADOS:**

**1. Tabla trabajadores modificada:**

**Campo ELIMINADO:**

- ❌ `email` (VARCHAR 100)

**Campos AGREGADOS:**

- ✨ `fecha_nacimiento` (DATE) - Fecha de nacimiento
- ✨ `fecha_contratacion` (DATE) - Fecha de contratación
- ✨ `salario` (DECIMAL 10,2) - Salario en quetzales
- ✨ `horas_extra` (INT, DEFAULT 0) - Horas extras trabajadas
- ✨ `modalidad` (ENUM: 'Plan 24', 'Mes', 'Destajo') - Modalidad de contratación

**2. Módulo empleados.php actualizado:**

**Vista (empleados.php):**

- ✨ Formulario con 6 filas organizadas
- ✨ 11 campos totales (5 nuevos)
- ✨ Validaciones HTML5 (pattern, maxlength)
- ✨ Textos de ayuda en campos críticos
- ✨ Tabla con columnas "Modalidad" y "Salario"
- ✨ Badges coloridos para modalidades

**JavaScript (empleados.js):**

- ✨ Nueva función `initFieldFormatting()`
- ✨ Formateo de DPI (solo números, máx 13)
- ✨ Formateo de teléfono (solo números, máx 8)
- ✨ Formateo de salario con blur (Q0,000.00)
- ✨ Formateo de horas extra (solo enteros)
- ✨ Modal con 13 cards (agregadas 5 nuevas)
- ✨ Validaciones actualizadas en frontend

**CSS (empleados.css):**

- ✨ Agregados estilos para badges de modalidad:
  - `.modalidad-plan-24` (azul)
  - `.modalidad-mes` (morado)
  - `.modalidad-destajo` (naranja)

**API (trabajadores.php):**

- ✨ Validación DPI (13 dígitos exactos)
- ✨ Validación teléfono (8 dígitos exactos)
- ✨ Limpieza automática de salario
- ✨ Validación de horas extra (≥ 0)
- ✨ Validación de modalidad (3 opciones)
- ✨ Actualizado INSERT y UPDATE

**SQL ejecutado:**

```sql
ALTER TABLE trabajadores DROP COLUMN email;
ALTER TABLE trabajadores
ADD COLUMN fecha_nacimiento DATE NULL AFTER telefono,
ADD COLUMN fecha_contratacion DATE NULL AFTER fecha_nacimiento,
ADD COLUMN salario DECIMAL(10,2) NULL AFTER fecha_contratacion,
ADD COLUMN horas_extra INT NULL DEFAULT 0 AFTER salario,
ADD COLUMN modalidad ENUM('Plan 24', 'Mes', 'Destajo') NULL AFTER horas_extra;
```

**Archivos modificados v6.0.8:**

- `modules/admin/empleados.php`
- `assets/css/pages/empleados.css`
- `assets/js/pages/empleados.js`
- `api/trabajadores.php`
- `README.md`

---

### v6.0.7 (26 Nov 2025) - 📦 MÓDULO DE MANEJO DE INVENTARIO

**CAMBIOS IMPLEMENTADOS:**

**1. Nuevo Módulo de Manejo de Inventario:**

**Tablas creadas:**

- ✨ `manejo_inventario` (10 columnas)
- ✨ `manejo_inventario_fotografias` (7 columnas)

**Módulo completo:**

- ✨ `modules/admin/manejo_inventario.php` (518 líneas)
- ✨ `assets/css/pages/manejo_inventario.css` (glassmorphism naranja)
- ✨ `assets/js/pages/manejo_inventario.js` (con animaciones)
- ✨ `api/manejo_inventario.php` (API REST completa, 523 líneas)

**Características:**

- ✅ Gestión de Salidas e Ingresos de Bodega
- ✅ 6 productos predefinidos (select)
- ✅ Fotografías: mínimo 1, máximo 2 (validación estricta)
- ✅ Formatos: JPG, PNG, WEBP (5MB máximo)
- ✅ Badge "FOTO EXISTENTE" en edición
- ✅ Eliminar fotos individuales (botón X)
- ✅ Agregar fotos al editar (mantiene existentes)
- ✅ Estadísticas animadas (Total, Salidas, Ingresos)
- ✅ DataTables con búsqueda y paginación
- ✅ Select2 para proyectos y trabajadores
- ✅ SweetAlert2 para modales

**2. Navbar Admin Actualizado:**

- ✨ Agregado "Manejo de Inventario" (10 items)
- ✨ Icono de paquete/caja (SVG)
- ✨ Comparación exacta en selección (evita conflictos)
- ✨ Animaciones actualizadas (10 elementos)

**3. Correcciones en Inventario:**

**Problemas solucionados:**

- ✅ Campo costo acepta múltiples dígitos
- ✅ Formateo automático solo al salir (blur)
- ✅ Fotos existentes NO se eliminan al cargar nuevas
- ✅ Badge "FOTO EXISTENTE" visible en edición
- ✅ Botón X funcional para eliminar fotos
- ✅ Modal "Ver" muestra proveedor (quitada ubicación)
- ✅ Eliminadas funciones y referencias a GPS
- ✅ API con JOIN a tabla proveedores

**Archivos nuevos v6.0.7:**

- `modules/admin/manejo_inventario.php`
- `assets/css/pages/manejo_inventario.css`
- `assets/js/pages/manejo_inventario.js`
- `api/manejo_inventario.php`

**Archivos modificados v6.0.7:**

- `includes/navbar_admin.php`
- `assets/css/navbar_admin.css`
- `assets/js/navbar_admin.js`
- `modules/admin/inventario.php`
- `assets/css/pages/inventario.css`
- `assets/js/pages/inventario.js`
- `api/inventario.php`
- `README.md`

---

### v6.0.6 (25 Nov 2025) - 🏪 MÓDULO DE PROVEEDORES + MEJORAS INVENTARIO

**CAMBIOS IMPLEMENTADOS:**

**1. Nuevo Módulo de Proveedores:**

- ✨ Creada tabla `proveedores` (8 columnas)
- ✨ Módulo PHP completo con glassmorphism morado/púrpura
- ✨ API REST completa (GET, POST, PUT, DELETE)
- ✨ JavaScript con validación estricta de teléfono
- ✨ CSS glassmorphism personalizado
- ✨ Integración con inventario (Foreign Key)
- ✨ Verificación antes de eliminar (si está en uso)

**2. Mejoras en Inventario:**

**Campos agregados:**

- ✨ `costo_equipo` (DECIMAL 10,2, formato Q0.00)
- ✨ `proveedor_id` (INT, Foreign Key a proveedores)
- ✨ `fecha_compra` (DATE)

**Campos eliminados:**

- ❌ `ubicacion_texto` (TEXT)
- ❌ `ubicacion_latitud` (DECIMAL)
- ❌ `ubicacion_longitud` (DECIMAL)

---

## 📊 Estadísticas del Proyecto v6.0.9

- **Líneas de código PHP:** ~28,000+
- **Líneas de código CSS:** ~16,000+
- **Líneas de código JavaScript:** ~15,500+
- **Archivos totales:** 125+
- **Tablas de base de datos:** 10
- **APIs REST:** 7
- **Módulos completos:** 13/13 (100%) ✨
- **Diseño:** Glassmorphism v6.0.9 ✨
- **Última actualización:** 28 Noviembre 2025

### Progreso del Proyecto

```
Implementados: ███████████████████████████████ 100%
Pendientes:    ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ 0%
```

**Completados:**

- ✅ Sistema de Autenticación (glassmorphism)
- ✅ Sistema de Roles y Niveles
- ✅ Aislamiento de Datos por Usuario
- ✅ Gestión de Sesiones con Timeout
- ✅ Dashboard Admin (6 estadísticas + glassmorphism)
- ✅ Dashboard Técnico (dinámico según nivel + filtrado)
- ✅ **Módulo Empleados (actualizado v6.0.8)** ✨
- ✅ Módulo Contratistas (glassmorphism + SweetAlert2)
- ✅ Módulo Proveedores (glassmorphism + validación)
- ✅ **Módulo Proyectos (actualizado v6.0.9)** ✨
- ✅ Módulo Supervisiones Admin (filtro + Excel)
- ✅ Módulo Supervisiones Técnico (filtrado usuario)
- ✅ Nueva Supervisión Admin (teléfono + observaciones + azul)
- ✅ Nueva Supervisión Técnico (con usuario_id)
- ✅ Módulo Inventario Admin (corregido v6.0.7)
- ✅ Módulo Inventario Técnico (corregido v6.0.7)
- ✅ Módulo Manejo de Inventario (salidas/ingresos v6.0.7)

**Pendientes:**

- ⏳ Reportes para Técnicos
- ⏳ Editar Supervisiones

---

## 🚀 Roadmap Futuro

### Corto Plazo (1-2 semanas)

- [ ] Hash de contraseñas con bcrypt ⚠️ URGENTE
- [ ] **Reportes para técnicos con filtrado de datos**
- [ ] **Editar supervisiones existentes**
- [ ] Módulo de mantenimiento de equipos
- [ ] Historial de movimientos por equipo
- [ ] Tokens CSRF

### Mediano Plazo (1 mes)

- [ ] Dashboard con gráficos glassmorphism (Chart.js)
- [ ] Exportar movimientos a PDF
- [ ] Notificaciones en tiempo real
- [ ] Historial de costos por equipo
- [ ] QR codes para equipos
- [ ] Reportes de movimientos por trabajador
- [ ] Sistema de nómina basado en salarios y horas extra
- [ ] Reportes de proyectos por montos (Consejo, Muni, ODC)

### Largo Plazo (2-3 meses)

- [ ] App móvil con diseño similar
- [ ] Sistema de backup automático
- [ ] Módulo de compras y órdenes
- [ ] PWA (Progressive Web App)
- [ ] Firmas digitales
- [ ] Multi-idioma

---

## ⚠️ NOTAS IMPORTANTES

### 🔴 ANTES DE PASAR A PRODUCCIÓN:

1. **✅ OBLIGATORIO:** Hash de contraseñas con bcrypt ⚠️
2. **✅ OBLIGATORIO:** Configurar HTTPS obligatorio
3. **✅ OBLIGATORIO:** Cambiar contraseñas por defecto
4. **✅ OBLIGATORIO:** Implementar tokens CSRF
5. **✅ OBLIGATORIO:** Configurar backups automáticos
6. **✅ OBLIGATORIO:** Deshabilitar mostrar errores
7. **✅ OBLIGATORIO:** Validar contenido real de archivos
8. **✅ OBLIGATORIO:** Rate limiting en APIs
9. **✅ OBLIGATORIO:** Revisar permisos de archivos
10. **✅ OBLIGATORIO:** Configurar logs de auditoría
11. **✅ OBLIGATORIO:** Ajustar timeout de sesión según necesidad
12. **✅ OBLIGATORIO:** Verificar foreign keys y cascadas

### ℹ️ INFORMACIÓN v6.0.9:

- 🏗️ **Módulo Proyectos:** Completamente rediseñado con campos monetarios ✨
- 👷 **Módulo Empleados:** Completamente rediseñado ✨
- 📦 **Manejo de Inventario:** Salidas/Ingresos implementado
- 🏪 **Módulo Proveedores:** Gestión completa implementada
- 📦 **Inventario Mejorado:** Costo, proveedor y fecha de compra
- 🔒 **Aislamiento de Datos:** Técnicos solo ven SUS registros
- 🔐 **Roles Técnicos:** Sistema de niveles de acceso implementado
- ⏱️ **Gestión de Sesión:** Timeout de 30 min con advertencia
- 🚨 **Modal Inactividad:** "¿Sigues ahí?" a los 25 min
- 📊 **Dashboard Técnico:** Estadísticas filtradas por usuario
- 🔒 **Protección:** Módulos protegidos según permisos
- 👥 **Usuarios:** 3 niveles (Admin, Técnico Básico, Técnico Completo)
- 🗄️ **Base de Datos:** 10 tablas con Foreign Keys
- 💰 **Costos:** Formato guatemalteco (Q0,000.00)
- 📞 **Validación:** Teléfono y DPI con validación estricta
- 📸 **Fotografías:** Validación estricta en todos los módulos
- 📊 **Exportación:** Excel con filtro de fechas
- 📱 **Responsive:** 100% en todas las resoluciones
- 🌐 **Navegadores:** Chrome 90+, Firefox 103+, Safari 14+, Edge 90+
- 🛠️ **Fix iOS:** Taps bloqueados solucionados

---

## 📄 Licencia

Este proyecto es software privado desarrollado para uso interno.

**Todos los derechos reservados © 2025**

---

## 🎨 Créditos

**Diseño:** Glassmorphism v6.0.9  
**Modales:** SweetAlert2  
**Tablas:** DataTables 1.13.7  
**Búsqueda:** Select2 4.1.0  
**Exportación:** SheetJS (xlsx) 0.20.1  
**Framework:** PHP + JavaScript Vanilla  
**Base de Datos:** MySQL/MariaDB  
**Gestión de Sesión:** Session Manager personalizado  
**Sistema de Roles:** Permisos granulares  
**Aislamiento de Datos:** Usuario ID  
**Gestión de Proveedores:** Módulo completo  
**Manejo de Inventario:** Salidas e Ingresos  
**Gestión de Empleados:** Sistema completo con salarios y modalidades ✨ v6.0.8  
**Gestión de Proyectos:** Sistema con campos monetarios y cálculo automático ✨ v6.0.9

---

**Última actualización:** 28 de Noviembre, 2025  
**Versión:** 6.0.9 - Glassmorphism Edition + Proyectos Actualizados ✨  
**Módulos completados:** 13/13 (100%) ✅  
**APIs REST:** 7/7 (100%) ✅  
**Tablas BD:** 10  
**Diseño:** Glassmorphism v6.0.9  
**Modales:** SweetAlert2  
**Animaciones:** CSS3  
**Exportación:** SheetJS (Excel)  
**Responsive:** 100%  
**Roles:** 3 niveles con permisos ✨  
**Sesión:** Gestión con timeout ✨  
**Datos:** Aislamiento por usuario ✨  
**Proveedores:** Gestión completa ✨  
**Inventario:** Con costos y proveedores ✨  
**Manejo Inventario:** Salidas/Ingresos ✨  
**Empleados:** Sistema completo con salarios ✨ v6.0.8  
**Proyectos:** Campos monetarios y cálculo automático ✨ v6.0.9  
**Fix iOS:** ✅ Implementado

---

**Sistema de Supervisión v6.0.9 - Glassmorphism Edition** 💎✨🔐⏱️🔒🏪📦👷🏗️

¡El sistema más moderno, seguro y completo para gestión de supervisiones con control de acceso por niveles, aislamiento total de datos, gestión de proveedores, control de movimientos de inventario, gestión completa de empleados con salarios y modalidades, y gestión de proyectos con campos monetarios y cálculo automático de presupuestos!

**¡Cada usuario ve solo lo que le corresponde!** 🎉🔐🏪📦👷🏗️
