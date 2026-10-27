# Importación de la matriz (Excel / CSV)

## Qué archivo acepta

- **Formatos:** `.xlsx` / `.xlsm` (Excel) o `.csv` / `.txt` (separado por `;`, `,`, tabulador o `|`).
- **Codificación del CSV:** se detecta sola (UTF-8, Mac Roman —la que usa `BD PAM.csv`— o Windows-1252).
- **Encabezados:** se reconocen por nombre aunque cambien mayúsculas, tildes o espacios, y pueden estar en
  cualquiera de las primeras 15 filas (se ignoran títulos encima). En Excel se usa la primera hoja que los tenga.
- **Columnas obligatorias:** `MUNICIPIO`, `NOMBRE`, `DPI`, `ESTADO ACTUAL`. Las demás 30 columnas de la matriz son
  opcionales; si faltan se cargan vacías. Columnas extra se ignoran y se informan.

| Columna de la matriz | Destino en el sistema |
|---|---|
| Departamento, Municipio | `ref.municipio` (por el código INE de 4 dígitos al inicio, p. ej. `1601 - Cobán`) |
| Nombre, DPI, Fecha Nacimiento, Teléfono, Dirección | `core.persona` (una por CUI; sin CUI, por nombre + fecha de nacimiento) |
| No. Expediente, Fecha Solicitud | `core.expediente` |
| Visita Positiva / Visita Negativa | `core.visita` (una fila por fecha) |
| Fecha Observación, Última Observación General | `core.observacion` |
| ID / No. ID / Apoderado | `core.apoderado` |
| En Nómina Desde / Hasta | `core.movimiento_nomina` (ALTA / BAJA) |
| Fecha Última Resolución, Tipo Resolución, Resolución | `core.resolucion` + catálogo de tipos |
| Fallecio, Suspendido | fechas del expediente y eventos de la línea de tiempo |
| Fecha Estado Actual, Estado Actual | `core.estado_expediente` (historial) |
| AÑO DE …, MES DE … | **no se guardan**: se calculan desde las fechas |

Valores de relleno de Excel (`1900`, `#VALOR!`, `0` en tipo de resolución, `...` en observación) se convierten en vacío.

## Reglas de validación

**Errores (la fila no se importa):** municipio desconocido, estado no reconocido, nombre vacío.

**Advertencias (la fila se importa y queda marcada para revisión en Calidad de datos):**
documento sin CUI (cédula antigua), CUI con dígito verificador incorrecto, sin fecha de solicitud o de nacimiento,
fechas inconsistentes (evento anterior a la solicitud o al nacimiento), fallecido con nómina activa, código de
resolución no catalogado (se agrega a la familia "Otras" para que el administrador lo clasifique).

Los registros nunca se eliminan por problemas de calidad.

## Re-importar la matriz (actualizaciones periódicas)

Puede volver a importar la matriz completa cada vez que se actualice. Cada fila tiene una llave estable
(expediente + DPI + fecha de solicitud + nombre + municipio):

- **Expedientes existentes** se actualizan; **nuevos** se insertan. No se duplica nada.
- Si cambió el **estado actual**, se cierra el estado anterior y se abre uno nuevo: el historial se conserva.
- Visitas, resoluciones, observaciones y movimientos que **vienen de la matriz** se reemplazan por los del archivo;
  los registrados **en el sistema** (por usuarios) nunca se tocan.
- Al terminar se recalculan los agregados del tablero.

## Conciliación

Cada lote guarda un reporte que compara, por **departamento × estado**, las filas del archivo contra los
expedientes cargados. Debe indicar `sin diferencias`. El detalle de errores y advertencias se descarga como CSV
desde **Control → Importaciones → Errores**, con el número de línea del archivo original.

## Modos

| Modo | Web | Consola | Efecto |
|---|---|---|---|
| Solo validar | "Solo validar" | `--validar` | Lee y valida; genera el reporte. No modifica la base. |
| Importar | "Importar" | (por defecto) | Inserta, actualiza, guarda historial, refresca el tablero. |

## Rendimiento de referencia

| Archivo | Filas | Tiempo |
|---|---|---|
| BD PAM.csv (296 MB, Mac Roman) | 802,556 | ≈ 4,5 min (carga inicial, 2 vCPU) |
| Extracto Excel | 1,501 | ≈ 30 s (incluye refresco del tablero nacional) |
