# contexto/ — todo lo que un agente necesita saber de este repo

Una sola carpeta para el contexto de la app, compartida por Antigravity, Claude Code y cualquier otro agente.
La puerta de entrada es `AGENTS.md` en la raíz; `CLAUDE.md` y `PaginaLocal/CLAUDE.md` son punteros que
importan estos mismos archivos, para que no haya dos copias que se contradigan.

## Orden de lectura

| # | Archivo | Qué es |
|---|---|---|
| 1 | `01_COMO_TRABAJAMOS.md` | El método: sesgos, causa raíz contra parche, verificación, cómo informar. |
| 2 | `04_ESTADO_Y_PENDIENTES.md` | Dónde está el proyecto, qué se hizo hace poco, qué falta y qué espera decisión. |
| 3 | `02_ARQUITECTURA_Y_REGLAS.md` | La app entera: las cuatro piezas, cada regla de producto con el fallo real que la motivó. |
| 4 | `03_CEREBRO.md` | Dentro de `PaginaLocal/`: pipeline, esquema de entrenos, unidades, validador, modelos, pruebas. |

## Carpetas

- `planes/` — **`PLAN_BETA_3_0.md` es el plan que manda ahora** (Beta 3.0: control, ayuda, mover y editar, progreso). `ESTUDIO_LEAN_BETA_3_0.md` revisa cada idea de la Beta 3.0 con evidencia de mercado y qué número la confirma. `PLAN_DE_ACCION_BETA_2_0.md` es la historia de la Beta 2.x con sus casillas (fases A, B y C, y la fuerza S1-S8 de la Beta 2.1). Los
  demás (lanzamiento, pendiente antes del merge, plan 1.2 y 1.2.2, plan del coach excelente) son historia y
  detalle; si contradicen al plan de acción, gana el plan de acción.
- `operacion/DEPLOYMENT.md` — runbook de producción (cPanel, uvicorn detrás de un proxy, secretos, recuperación).
- `fichas/` — fichas cortas de arquitectura, cada afirmación con la orden que la comprueba (flujos del grafo,
  entradas y salidas de cada cerebro, herramientas).
- `historico/` — planes maestros v5/v6, la auditoría v4 y el README original del contrato de AI Studio. Sirven
  para entender por qué algo es como es; **no describen el estado actual** (el README original dice, por
  ejemplo, que `PaginaLocal/` nunca se modifica, y hoy es el corazón de la app).

## Dónde NO está el contexto

- Los porqués finos viven como **comentarios junto al código** y como **docstrings de las pruebas** con los
  números reales. Estos documentos apuntan a ellos; no los copian.
- El historial de cambios es `git log`.
- Los datos de un atleta real: `AiService/_runtime/storage/u<id>/` (espejo de MySQL, salvo
  `track_record.jsonl` y `reasoning/`, que son historia de verdad) y `AiService/_runtime/jobs/<job>/`.
