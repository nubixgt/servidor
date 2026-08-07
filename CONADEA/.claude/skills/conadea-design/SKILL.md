---
name: conadea-design
description: Sistema de diseño CONADEA / MAGA AgroIA (glassmorphism verde sobre paisaje). Úsala al crear o modificar cualquier pantalla/vista de Frontend (Vue) o app_conadea (Flutter) para mantener el mismo look & feel en web y móvil.
---

# Sistema de diseño CONADEA · MAGA AgroIA

Aula Virtual AgroIA (Ministerio de Agricultura, Ganadería y Alimentación de Guatemala / CONADEA)
comparte un único lenguaje visual entre el **Frontend web** (Vue + Tailwind, en `Frontend/`) y la
**app móvil** (Flutter, en `app_conadea/`). Antes de diseñar o tocar una pantalla nueva en
cualquiera de los dos, revisa esta guía para no romper la consistencia.

## Concepto visual

"Glassmorphism verde sobre paisaje": el fondo siempre es una fotografía de paisaje verde/montaña
(`fondoC.png`) oscurecida con un degradado, y encima flotan tarjetas de vidrio esmerilado
(blur + transparencia + borde sutil). El acento de color es el verde de la marca AgroIA, nunca
azules o morados genéricos.

## Fuente de la verdad de los assets

- Logo: `Frontend/src/assets/logo_agroia.png` (isotipo "MAGA AgroIA" con escudo de Guatemala).
  Copiar/reusar ese mismo archivo — no regenerar el logo.
- Fondo: `Frontend/src/assets/fondoC.png` (paisaje de montaña/bosque verde-azulado).
- En `app_conadea/`, estos assets viven en `app_conadea/assets/images/` (copia local, mismo
  contenido binario que el Frontend — si el logo o el fondo cambian en `Frontend/src/assets/`,
  vuelve a copiarlos ahí).

## Paleta (variables CSS en `Frontend/src/style.css`, portadas a `AppColors` en Flutter)

| Token | Hex / valor | Uso |
|---|---|---|
| `--texto` | `#FFFFFF` | Texto principal sobre fondo oscuro |
| `--texto-suave` | `rgba(255,255,255,.70)` | Texto secundario / subtítulos |
| `--verde` | `#4ADE80` | Acento primario, focus, iconos activos |
| `--verde-fuerte` | `#22C55E` | Botones sólidos, hover |
| `--verde-oscuro` | `#15803D` | Bordes inferiores tipo "3D" de botones |
| `--lima` | `#A3E635` | Gradientes de progreso |
| `--oro` | `#F4C542` | Estados "completado/repasar", insignias doradas |
| `--azul` | `#38BDF8` | Acentos secundarios (rutas tipo "azul") |
| `--rojo` | `#F87171` | Errores, cerrar sesión |
| `--vidrio` | `rgba(13,38,48,.45)` | Fondo de tarjetas glass |
| `--vidrio-2` | `rgba(255,255,255,.08)` | Fondo de botones/íconos secundarios |
| `--vidrio-3` | `rgba(255,255,255,.15)` | Hover de vidrio |
| `--borde` | `rgba(255,255,255,.12)` | Bordes de tarjetas |
| `--borde-claro` | `rgba(255,255,255,.22)` | Bordes de botones/avatares |
| `--radio` | `20px` | Radio estándar de tarjetas |
| Fondo base | `#0C2630` | Color sólido detrás de la imagen de fondo |

Colores por módulo/curso (`COLORES_MOD` en `Frontend/src/data/local.js`) son gradientes
`[claro, oscuro]` únicos por id de curso — reusar la misma tabla si se listan cursos en la app.

## Tipografía

- Texto general: **Inter** (300–800).
- Títulos (`h1–h4`, nombres de tarjeta): **Outfit**, peso 700–900, `letter-spacing: -0.02em`.
- En Flutter usar `google_fonts` con `GoogleFonts.inter()` / `GoogleFonts.outfit()` para no requerir
  archivos de fuente locales.

## Componentes clave y sus reglas

- **Tarjeta de vidrio** (`.vidrio` / `GlassCard`): fondo `--vidrio`, `blur(20px)`, borde `--borde`,
  `border-radius: 20px`, padding `20px`, sombra `0 8px 32px rgba(0,0,0,.22)`.
- **Botón primario verde** (`.btn-verde` / `PrimaryButton`): degradado `--verde → --verde-fuerte`,
  texto oscuro `#06281A`, borde inferior grueso `--verde-oscuro` (efecto "3D pulsable"), sombra
  verde. Al presionar, el borde inferior se reduce (simula profundidad).
- **Barra de progreso** (`.pista` / `ProgressTrack`): riel `rgba(255,255,255,.14)` de 8px alto,
  relleno degradado `--lima → --verde-fuerte` con `border-radius: 6px`.
- **Insignias**: forma de hexágono (`clip-path` en web / `CustomClipper` en Flutter), degradado
  dorado por defecto, verde o azul si está "obtenida", gris + escala de grises si está bloqueada.
- **Bottom nav (solo app móvil)**: barra de vidrio oscuro flotante con 5 accesos —
  Inicio, Cursos, botón central elevado (acceso a AgroIA/WhatsApp, ícono hoja, gradiente verde,
  sin etiqueta, sobresale del riel), Rutas, Insignias. El ítem activo se resalta en verde
  (`--verde`), los inactivos en `--texto-suave`.

## Reglas al construir una pantalla nueva

1. Nunca uses fondo blanco sólido ni colores fríos (azul/morado) como base — siempre paisaje +
   overlay oscuro + vidrio.
2. Todo texto de cuerpo va en blanco / blanco 70%, nunca gris oscuro sobre fondo claro.
3. Reutiliza el logo y el fondo reales (ver "Fuente de la verdad de los assets"); no generes
   nuevas imágenes de marca.
4. Los estados "completado / repasar" usan oro (`--oro`); "en progreso / continuar" usa verde.
5. Mantén el mismo copy en español que ya existe en `Frontend/src/data/local.js` cuando muestres
   cursos, rutas o insignias — es contenido real del programa CONADEA, no lo inventes distinto en
   la app.
