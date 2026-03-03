# 📸 GUÍA VISUAL DEL SISTEMA
## Sistema de Análisis de Votaciones del Congreso de Guatemala

---

## 🏠 PÁGINA PRINCIPAL (Dashboard)

### Vista General

```
┌─────────────────────────────────────────────────────────────────────┐
│  🏛️ Sistema de Votaciones - Congreso de Guatemala                   │
├─────────────────────────────────────────────────────────────────────┤
│  Dashboard | Eventos | Diputados | Bloques | Cargar PDF            │
└─────────────────────────────────────────────────────────────────────┘

┌───────────────┐  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐
│  📅 EVENTOS   │  │  👥 DIPUTADOS │  │  🏛️ BLOQUES   │  │  ✅ VOTOS     │
│      42       │  │      160      │  │      17       │  │    6,720      │
└───────────────┘  └───────────────┘  └───────────────┘  └───────────────┘

┌─────────────────────────────────┐  ┌─────────────────────────────────┐
│  📊 Distribución de Votos       │  │  📋 Últimos Eventos             │
│                                 │  │                                 │
│     [Gráfica circular]          │  │  • Evento #2: Aprobación        │
│                                 │  │    ✅ A favor: 51               │
│  • A Favor: 51%                 │  │    ❌ En contra: 14             │
│  • En Contra: 14%               │  │    ⚠️  Ausentes: 75             │
│  • Ausentes: 30%                │  │                                 │
│  • Licencia: 5%                 │  │  • Evento #3: Reforma...        │
│                                 │  │    ✅ A favor: 89               │
└─────────────────────────────────┘  └─────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  🏆 Diputados Más Activos                                           │
├────┬────────────────────┬─────────┬──────┬──────┬────────┬─────────┤
│ #  │ Nombre             │ Bloque  │ Vot. │ Favor│ Contra │ Ausenc. │
├────┼────────────────────┼─────────┼──────┼──────┼────────┼─────────┤
│ 1  │ Felipe Alejos...   │ TODOS   │  42  │  38  │   4    │   0     │
│ 2  │ Cristian Alvarez...│ CREO    │  42  │  35  │   5    │   2     │
│ 3  │ César Amézquita... │ VIVA    │  41  │  32  │   7    │   2     │
└────┴────────────────────┴─────────┴──────┴──────┴────────┴─────────┘
```

**Características:**
- ✅ 4 tarjetas estadísticas con colores distintivos
- ✅ Gráfica circular interactiva (hover para ver detalles)
- ✅ Lista scrolleable de últimos eventos
- ✅ Tabla ordenable de diputados más activos

---

## 👥 PÁGINA DE DIPUTADOS

### Vista de Lista

```
┌─────────────────────────────────────────────────────────────────────┐
│  👥 Análisis de Diputados                                           │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  Seleccionar Diputado                                               │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │ 🔍 Buscar diputado...                                         ▼│ │
│  └───────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  📊 Todos los Diputados                                             │
├────────────────────┬─────────┬──────┬──────┬────────┬─────┬────────┤
│ Nombre             │ Bloque  │ Vot. │ Favor│ Contra │ Aus.│ % Fav. │
├────────────────────┼─────────┼──────┼──────┼────────┼─────┼────────┤
│ Felipe Alejos...   │ TODOS   │  42  │  38  │   4    │  0  │ 90.5%  │
│ Cristian Alvarez...│ CREO    │  42  │  35  │   5    │  2  │ 87.5%  │
│ ...                │ ...     │  ... │  ... │  ...   │ ... │ ...    │
└────────────────────┴─────────┴──────┴──────┴────────┴─────┴────────┘
```

### Vista Detallada de Diputado

```
┌─────────────────────────────────────────────────────────────────────┐
│  ◀️ Volver                                                          │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  Felipe Alejos Lorenzana                                            │
├─────────────────────────────────────────────────────────────────────┤
│  Bloque: [TODOS]  Votaciones: 42  A Favor: 38  Contra: 4  Aus: 0   │
│  % A Favor: 90.5%  % Inasistencia: 0%                              │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────┐  ┌─────────────────────────────────┐
│  📊 Distribución de Votos       │  │  📈 Tendencia de Votación       │
│                                 │  │                                 │
│     [Gráfica circular]          │  │     [Gráfica de línea]          │
│                                 │  │                                 │
│  • A Favor: 90.5%               │  │   1.0 ┤─────╮                  │
│  • En Contra: 9.5%              │  │   0.5 ┤     │  ╭─╮              │
│  • Ausente: 0%                  │  │   0.0 ┤     ╰──╯                │
│                                 │  │  -1.0 ┤                         │
└─────────────────────────────────┘  └─────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  📋 Historial de Votaciones                                         │
├──────────┬───────┬────────────────────────┬────────┬────────┬───────┤
│ Fecha    │ Ev. # │ Descripción            │ Sesión │ Voto   │ Bloq. │
├──────────┼───────┼────────────────────────┼────────┼────────┼───────┤
│23/10/2025│   2   │ Aprobación del acta... │   42   │ A FAVOR│ TODOS │
│20/10/2025│   3   │ Reforma fiscal...      │   41   │ A FAVOR│ TODOS │
│18/10/2025│   4   │ Presupuesto 2026...    │   40   │ CONTRA │ TODOS │
└──────────┴───────┴────────────────────────┴────────┴────────┴───────┘
```

**Características:**
- ✅ Búsqueda con autocompletado (Select2)
- ✅ Tarjeta de información destacada
- ✅ 2 gráficas interactivas lado a lado
- ✅ Tabla completa de historial con scroll
- ✅ Badges de colores para tipo de voto

---

## 📅 PÁGINA DE EVENTOS

### Vista de Lista

```
┌─────────────────────────────────────────────────────────────────────┐
│  📅 Eventos de Votación                                             │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  📊 Todos los Eventos                                               │
├───────┬─────────────────────┬──────┬──────────┬──────┬──────┬──────┤
│ Ev. # │ Descripción         │ Ses. │ Fecha    │ Favor│ Contr│Result│
├───────┼─────────────────────┼──────┼──────────┼──────┼──────┼──────┤
│   2   │ Aprobación acta...  │  42  │23/10/2025│  51  │  14  │✅ APR│
│   3   │ Reforma fiscal...   │  41  │20/10/2025│  89  │  25  │✅ APR│
│   4   │ Presupuesto 2026... │  40  │18/10/2025│  42  │  98  │❌ REC│
└───────┴─────────────────────┴──────┴──────────┴──────┴──────┴──────┘
```

### Vista Detallada de Evento

```
┌─────────────────────────────────────────────────────────────────────┐
│  ◀️ Volver a la lista                                              │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  Evento #2                                                          │
├─────────────────────────────────────────────────────────────────────┤
│  APROBACIÓN DEL ACTA DE LA SESIÓN ANTERIOR                         │
│  Sesión: 42  |  Fecha: 23/10/2025 10:10:36  |  [✅ APROBADO]      │
│                                                                     │
│       A Favor: 51   |   En Contra: 14   |   Ausentes: 75          │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────┐  ┌─────────────────────────────────┐
│  📊 Resultado de la Votación    │  │  🏛️ Votación por Bloque        │
│                                 │  │                                 │
│     [Gráfica de barras]         │  │     [Gráfica apilada]           │
│                                 │  │                                 │
│  60 ┤█████                      │  │  CABAL    ████████░░            │
│  40 ┤███                        │  │  UNE      ██████████            │
│  20 ┤                           │  │  VAMOS    ████░░░░░░            │
│   0 └────────────────           │  │  ...                            │
│     Favor Contra Aus. Lic.      │  │                                 │
└─────────────────────────────────┘  └─────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  📋 Detalle de Votos                                                │
├────────────────────────────┬──────────────────┬────────────────────┤
│ Diputado                   │ Bloque           │ Voto               │
├────────────────────────────┼──────────────────┼────────────────────┤
│ Felipe Alejos Lorenzana    │ [TODOS]          │ [✅ A FAVOR]       │
│ Cristian Rodolfo Alvarez...│ [CREO]           │ [✅ A FAVOR]       │
│ César Augusto Amézquita... │ [VIVA]           │ [✅ A FAVOR]       │
│ Héctor Adolfo Aldana...    │ [VAMOS]          │ [❌ EN CONTRA]     │
│ ...                        │ ...              │ ...                │
└────────────────────────────┴──────────────────┴────────────────────┘
```

**Características:**
- ✅ Tarjeta de encabezado con información clave
- ✅ Números grandes y visibles para resultados
- ✅ 2 tipos de gráficas: barras simples y apiladas
- ✅ Tabla completa filtrable
- ✅ Badges con colores para bloques y votos

---

## 🏛️ PÁGINA DE BLOQUES

### Vista Comparativa

```
┌─────────────────────────────────────────────────────────────────────┐
│  🏛️ Bloques Políticos                                              │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  📊 Comparación de Bloques                                          │
│                                                                     │
│  [Gráfica de barras comparativas]                                  │
│                                                                     │
│  100 ┤                                                              │
│   80 ┤ ██  ██      ██  ██                                          │
│   60 ┤ ██  ██  ██  ██  ██  ██                                      │
│   40 ┤ ██  ██  ██  ██  ██  ██  ██                                  │
│   20 ┤ ██  ██  ██  ██  ██  ██  ██  ██                              │
│    0 └─────────────────────────────                                │
│       CABAL UNE VAMOS VIVA VALOR IND ...                           │
│       [A Favor] [En Contra]                                        │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  📊 Todos los Bloques                                               │
├──────────────┬──────┬──────┬────────┬─────────┬─────────┬──────────┤
│ Bloque       │ Dip. │ Favor│ Contra │ Ausent. │ % Favor │ Acciones │
├──────────────┼──────┼──────┼────────┼─────────┼─────────┼──────────┤
│ CABAL        │  15  │  589 │   102  │   189   │  85.2%  │ [👁️ Ver] │
│ UNE          │  28  │ 1,050│   245  │   457   │  81.0%  │ [👁️ Ver] │
│ VAMOS        │  35  │ 1,280│   398  │   582   │  76.3%  │ [👁️ Ver] │
└──────────────┴──────┴──────┴────────┴─────────┴─────────┴──────────┘
```

### Vista Detallada de Bloque

```
┌─────────────────────────────────────────────────────────────────────┐
│  ◀️ Volver a la lista                                              │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  CABAL                                                              │
├─────────────────────────────────────────────────────────────────────┤
│   Diputados: 15   |   A Favor: 589   |   Contra: 102   |   Aus: 189│
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────┐  ┌─────────────────────────────────┐
│  📊 Distribución de Votos       │  │  👥 Rendimiento de Diputados    │
│                                 │  │                                 │
│     [Gráfica circular]          │  │     [Gráfica de barras]         │
│                                 │  │                                 │
│  • A Favor: 85.2%               │  │  50┤ ███                        │
│  • En Contra: 14.8%             │  │  40┤ ███ ██                     │
│  • Ausente: 21.5%               │  │  30┤ ███ ██ ██                  │
│                                 │  │  20┤ ███ ██ ██ █                │
└─────────────────────────────────┘  └─────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  👥 Diputados del Bloque                                            │
├────────────────────────┬──────┬──────┬────────┬─────────┬──────────┤
│ Nombre                 │ Vot. │ Favor│ Contra │ Ausenc. │ % Favor  │
├────────────────────────┼──────┼──────┼────────┼─────────┼──────────┤
│ Manuel Archila Cordón  │  42  │  38  │   3    │   1     │  92.7%   │
│ Bequer Chocooj...      │  42  │  35  │   5    │   2     │  87.5%   │
│ ...                    │  ... │  ... │  ...   │  ...    │  ...     │
└────────────────────────┴──────┴──────┴────────┴─────────┴──────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  📋 Historial Reciente de Votaciones                                │
├───────┬──────────┬──────┬────────┬─────────┬─────────────────────┤
│ Ev. # │ Fecha    │ Favor│ Contra │ Ausent. │ % A Favor           │
├───────┼──────────┼──────┼────────┼─────────┼─────────────────────┤
│   2   │23/10/2025│  14  │   1    │   0     │ 93.3%               │
│   3   │20/10/2025│  12  │   2    │   1     │ 85.7%               │
└───────┴──────────┴──────┴────────┴─────────┴─────────────────────┘
```

**Características:**
- ✅ Gráfica comparativa de todos los bloques
- ✅ Vista detallada con múltiples secciones
- ✅ Tabla de diputados del bloque
- ✅ Historial de votaciones del bloque
- ✅ Estadísticas en tiempo real

---

## 📤 PÁGINA DE CARGA DE PDFs

```
┌─────────────────────────────────────────────────────────────────────┐
│  📤 Cargar Archivo PDF                                              │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  ✅ Archivo procesado exitosamente                                  │
│  Los datos han sido importados a la base de datos.           [✕]   │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────┐  ┌────────────────────────────┐
│  📤 Subir Nuevo PDF de Votación     │  │  📁 Archivos Procesados    │
│                                      │  │                            │
│  Seleccionar archivo PDF             │  │  📄 ejemplo.pdf            │
│  [Elegir archivo...]                 │  │  ↳ Procesado: 23/10/2025  │
│  Tamaño máximo: 50MB                 │  │                            │
│                                      │  │  📄 votacion_2.pdf         │
│  [📤 Subir y Procesar]              │  │  ↳ Procesado: 22/10/2025  │
│                                      │  │                            │
│  [████████░░] Procesando...         │  │  📄 votacion_3.pdf         │
│                                      │  │  ↳ Procesado: 21/10/2025  │
└──────────────────────────────────────┘  └────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  ℹ️ Información                                                     │
│                                                                     │
│  ¿Qué hace este formulario?                                        │
│  1. Sube el archivo PDF de votación                                │
│  2. Extrae automáticamente la información del evento               │
│  3. Identifica todos los diputados y sus votos                     │
│  4. Almacena los datos en la base de datos                         │
│  5. Genera estadísticas y análisis                                 │
│                                                                     │
│  Formato esperado del PDF:                                         │
│  • Encabezado con número de evento                                 │
│  • Descripción del evento                                          │
│  • Número de sesión                                                │
│  • Fecha y hora                                                    │
│  • Tabla con: No., Nombre, Bloque, Voto Emitido                   │
└─────────────────────────────────────────────────────────────────────┘
```

**Características:**
- ✅ Drag & drop para subir archivos
- ✅ Barra de progreso durante el procesamiento
- ✅ Alertas de éxito/error
- ✅ Lista de archivos procesados previamente
- ✅ Instrucciones claras sobre el formato

---

## 🎨 ELEMENTOS DE DISEÑO

### Paleta de Colores

```
✅ A FAVOR       → Verde  (#198754)
❌ EN CONTRA     → Rojo   (#dc3545)
⚠️  AUSENTE      → Amarillo (#ffc107)
ℹ️  LICENCIA     → Azul  (#0dcaf0)
📊 PRIMARIO      → Azul  (#0d6efd)
```

### Iconos Utilizados

```
📅 Eventos
👥 Diputados
🏛️ Bloques
📊 Gráficas
📋 Tablas
✅ Aprobado
❌ Rechazado
⚠️  Advertencia
ℹ️  Información
🔍 Buscar
📤 Subir
👁️ Ver
◀️ Volver
```

### Componentes Interactivos

1. **Tarjetas (Cards)**
   - Sombra sutil
   - Hover con elevación
   - Bordes redondeados

2. **Tablas**
   - Rayas alternadas
   - Hover en filas
   - Scroll horizontal en móvil

3. **Gráficas**
   - Animación al cargar
   - Tooltips informativos
   - Responsive

4. **Botones**
   - Hover con elevación
   - Iconos descriptivos
   - Feedback visual

5. **Badges**
   - Colores por tipo
   - Tamaño legible
   - Esquinas redondeadas

---

## 📱 DISEÑO RESPONSIVE

### Vista Móvil (< 768px)

```
┌────────────────────────┐
│  ≡ Menú                │
├────────────────────────┤
│  📅 EVENTOS            │
│       42               │
├────────────────────────┤
│  👥 DIPUTADOS          │
│      160               │
├────────────────────────┤
│  🏛️ BLOQUES           │
│       17               │
├────────────────────────┤
│  ✅ VOTOS              │
│    6,720               │
├────────────────────────┤
│  [Gráfica Circular]    │
├────────────────────────┤
│  📋 Eventos            │
│  • Evento #2           │
│  • Evento #3           │
└────────────────────────┘
```

**Adaptaciones Móviles:**
- ✅ Menú colapsable (hamburguesa)
- ✅ Tarjetas en columna única
- ✅ Tablas con scroll horizontal
- ✅ Gráficas adaptativas
- ✅ Botones de tamaño táctil
- ✅ Texto legible sin zoom

---

## 🌐 NAVEGACIÓN

### Barra de Navegación

```
┌─────────────────────────────────────────────────────────────────────┐
│  🏛️ Sistema de Votaciones - Congreso de Guatemala                   │
│                                                                     │
│  [Dashboard] [Eventos] [Diputados] [Bloques] [Cargar PDF]         │
└─────────────────────────────────────────────────────────────────────┘
```

**Menú Activo:**
- Estado actual resaltado
- Iconos descriptivos
- Acceso rápido a todas las secciones

---

## ✨ ANIMACIONES Y EFECTOS

1. **Carga de Página**
   - Fade in de elementos
   - Aparición secuencial

2. **Hover Estados**
   - Elevación de tarjetas
   - Cambio de color en botones
   - Resaltado de filas

3. **Transiciones**
   - Smooth scrolling
   - Cambio de página fluido
   - Colapso/expansión animado

4. **Loading States**
   - Spinners mientras carga
   - Skeleton screens
   - Barra de progreso

---

Este sistema proporciona una experiencia visual moderna, intuitiva y 
profesional para el análisis de votaciones del Congreso de Guatemala.

Todas las interfaces son responsive y accesibles desde cualquier dispositivo.
