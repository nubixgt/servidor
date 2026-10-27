# Manual de operación

## Roles y permisos iniciales

Los permisos se cambian desde **Administración → Roles y permisos** sin programar. Estos son los valores iniciales:

| Permiso | Administrador | Supervisor | Operador | Visitador | Nómina | Consulta | Auditor |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| Ver dashboard, territorio y estados | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ver alertas | ✓ | ✓ | ✓ |  | ✓ | ✓ | ✓ |
| Gestionar casos de alerta |  | ✓ |  |  |  |  |  |
| Buscar y ver expedientes | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ver DPI completo (auditado) | ✓ | ✓ |  |  | ✓ |  | ✓ |
| Editar contacto · registrar solicitudes |  | ✓ | ✓ |  |  |  |  |
| Registrar observaciones |  | ✓ | ✓ | ✓ | ✓ |  |  |
| Registrar visitas |  | ✓ |  | ✓ |  |  |  |
| Emitir resoluciones · cambiar estado |  | ✓ |  |  |  |  |  |
| Altas y bajas de nómina |  | ✓ |  |  | ✓ |  |  |
| Ver reportes | ✓ | ✓ | ✓ |  | ✓ | ✓ | ✓ |
| Exportar datos personales | ✓ | ✓ |  |  | ✓ |  | ✓ |
| Calidad de datos (ver / gestionar) | ✓ / ✓ | ✓ / ✓ |  |  |  | ✓ / – | ✓ / – |
| Importar archivos | ✓ |  |  |  |  |  |  |
| Usuarios, roles, catálogos | ✓ |  |  |  |  |  |  |
| Bitácora de auditoría | ✓ |  |  |  |  |  | ✓ |

El Administrador no registra gestiones de expedientes por diseño (separación de funciones); si lo necesita, asigne los permisos.

## Ámbito territorial

Cada usuario puede limitarse a un **departamento** o un **municipio**. Con ámbito asignado solo ve tablero, listados,
expedientes y reportes de su territorio. Sin ámbito ve todo el país.

## Ciclo de un expediente en el sistema

1. **Solicitud** (Solicitudes o Beneficiarios → Nueva solicitud): valida el CUI y evita una segunda solicitud activa.
2. **Visita**: positiva, negativa o no localizado.
3. **Resolución**: según el catálogo de 46 tipos agrupados en 9 familias.
4. **Nómina**: alta (pasa a EN NÓMINA ACTIVA) o baja (con estado resultante: suspendido, fallecido, etc.).
5. **Cambio de estado** con motivo obligatorio.

Todo queda en la **línea de tiempo** del Expediente 360° y en la **bitácora** con usuario, fecha, IP y valores antes/después.

## Alertas y calidad de datos

Las 12 reglas (fallecidos activos, trámite > 365 días, sin visita, resolución positiva sin nómina, DPI y expedientes
duplicados, fechas incoherentes, etc.) se calculan sobre toda la base. "Ver casos" abre el listado filtrado; en cada
expediente el botón **Revisar** registra el resultado (confirmado, corregido o descartado) con comentario.

## Reportes

9 reportes (resúmenes departamental y municipal, estados, edad, antigüedad, resoluciones, movimientos mensuales,
calidad y listado detallado). Respetan los filtros activos del tablero y el ámbito del usuario. La exportación pide
motivo, lleva marca de "uso oficial" con usuario y fecha, y se registra en la bitácora. El listado con datos personales
tiene un máximo de filas por rol (parámetro `export_max_filas_detalle`).

## Consulta en lenguaje natural

El botón **Preguntar** (Ctrl K) entiende frases como "beneficiarios en nómina mayores de 80 años en Alta Verapaz" y
las convierte en los mismos filtros del tablero, mostrando la interpretación antes de aplicarlas. Funciona con reglas
locales (no envía datos a servicios externos) y nunca toma decisiones sobre elegibilidad.
