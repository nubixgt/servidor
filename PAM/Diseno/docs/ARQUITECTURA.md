# Arquitectura técnica

```
Navegador ──HTTPS──► Nginx ──► /            frontend estático (HTML/CSS/JS, fuentes y logos locales)
                           └─► /api/v1/*    FastAPI (uvicorn, N procesos)
                                              │
                                              ├── PostgreSQL 16 ── core / ref / seg / audit / stg / mart
                                              └── /data (volumen compartido) ◄── worker: importaciones y refrescos
```

## Modelo de datos

- **Persona** (`core.persona`): identidad única por CUI; sin CUI, por nombre normalizado + fecha de nacimiento.
- **Expediente** (`core.expediente`): un proceso administrativo; guarda un *resumen* (estado vigente, última visita,
  última resolución, fechas de nómina, fallecimiento y suspensión) para consultas rápidas.
- **Eventos**: `core.visita`, `core.observacion`, `core.resolucion`, `core.movimiento_nomina`, `core.estado_expediente`
  (historial con un solo estado vigente). La línea de tiempo es la vista `core.v_evento` (no duplica datos).
- **Catálogos** (`ref.*`): departamentos y municipios INE, estados, familias y tipos de resolución, parámetros.
- **Seguridad** (`seg.*`): usuarios con ámbito territorial, roles, permisos, sesiones (refresh tokens en hash).
- **Auditoría** (`audit.bitacora`): solo inserción (trigger que bloquea UPDATE/DELETE/TRUNCATE); triggers en todas las
  tablas de negocio registran antes/después de cada cambio con usuario, IP y request id.
- **Carga** (`stg.*`): copia fiel de la matriz por lote, errores por línea y reporte de conciliación.
- **Agregados** (`mart.*`): `mv_cubo` (≈150 mil combinaciones de municipio × estado × edad × año × resolución × visita ×
  nómina × antigüedad, con medidas de calidad), `mv_serie` (eventos por mes) y `mv_tiempos` (media, mediana, P90).

Funciones clave: `ref.f_cui_valido` (módulo 11), `ref.f_fecha` (fechas seguras), `ref.f_norm` (sin tildes),
`stg.procesar_lote` (transformación masiva idempotente), `core.recalcular_resumen`, `mart.refrescar`.

## API (resumen)

| Método y ruta | Permiso |
|---|---|
| `POST /auth/login` · `/auth/refresh` · `/auth/logout` · `/auth/cambiar-clave` · `GET /auth/yo` | — |
| `GET /tablero/datos` (cubo + series + tiempos, con ETag y gzip) | dashboard.ver |
| `GET /expedientes?q&dep&mun&anio&e&r&n&v&s&f&a&am&t&regla&orden&cursor` | beneficiarios.ver |
| `GET /expedientes/{id}` (360°) · `GET /expedientes/{id}/dpi` | beneficiarios.ver · ver_dpi |
| `POST /expedientes` · `/{id}/observaciones` · `/visitas` · `/resoluciones` · `/estado` · `/nomina` · `/casos` · `PATCH /{id}/contacto` | según acción |
| `GET /reportes` · `POST /reportes/exportar` | reportes.ver / exportar |
| `POST /importaciones` · `GET /importaciones[/{id}]` · `/{id}/errores.csv` | importacion.ejecutar |
| `/admin/usuarios` · `/admin/roles` · `/admin/bitacora` · `/admin/parametros` · `/admin/tipos-resolucion` | usuarios / roles / auditoria / catalogos |
| `GET /catalogos` · `GET /catalogos/calidad` · `GET /api/salud` | — |

En ambiente `desarrollo` la documentación interactiva está en `/api/docs`.

## Rendimiento medido (802,556 expedientes, 2 vCPU)

| Operación | Tiempo |
|---|---|
| Importación completa | ≈ 4,5 min |
| Datos del tablero (primera vez / en caché) | 1,6 s / 20 ms |
| Búsqueda por DPI exacto | 16 ms |
| Búsqueda por nombre ("jose lopez") | ≈ 100 ms |
| Listado filtrado con conteo | 200–450 ms |
| Expediente 360° | 12 ms |

Técnicas: índices trigram (`pg_trgm`) sobre nombres normalizados, índices `text_pattern_ops` para prefijos de
DPI/expediente/teléfono, paginación por cursor (keyset), agregados precalculados, conteos acotados a 10,000.

## Escalamiento

- Hasta ~3 millones de expedientes en un solo servidor de 8 vCPU / 16 GB sin cambios.
- Más allá: réplica de lectura para tablero y reportes, particionar `audit.bitacora` por mes y `core.observacion` por año.
- Redis puede añadirse como caché compartida si se usan varios servidores de API.
