# Linter, código muerto y observabilidad

Hasta el 2026-09-02 no había ninguna de las tres. El `CLAUDE.md` lo decía:
*"no build step, linter, or type-checker wired up in this repo"*.

```bash
python -m pip install ruff vulture

python -m ruff check .          # 20 avisos, ninguno un fallo vivo
python -m ruff check . --fix    # los que se arreglan solos
python -m ruff format .         # formateo
python -m vulture               # lee la config de pyproject.toml
```

Configuración y el porqué de cada regla: `pyproject.toml`, comentado.

**No están en el CI.** `.github/workflows/deploy.yml` publica a producción, y
un linter que tumba un despliegue por una línea larga es peor que no tenerlo.

## Lo que encontró la primera pasada

227 avisos. Casi todo estilo, pero tres cosas reales que ninguna prueba veía:

- **`treadmill_access` se leía y se tiraba** (F841). El atleta declara si tiene
  cinta, el dato llega al Doctor, y al planificador **no llegaba**: la línea
  existía sin un `if` debajo. Arreglado.
- **`__all__` exportaba `usage_ledger`**, que no existe (se importa como
  `_usage_ledger`). Un `import *` habría reventado. F822.
- **`available_days` muerto** en el contexto de planificación: la
  disponibilidad entra por `render_availability_table`. Resto de antes.

## Código muerto: cuidado con las rutas

Vulture sin configurar da 266 avisos y casi todos son falsos — este repo es de
frameworks (rutas de FastAPI, campos de Pydantic, callbacks de LangChain).
Configurado baja a ~13 revisables.

**Hay que pasarle TODAS las entradas del programa.** Escaneando sin `cli/`
salía que `run_complete_analysis_and_planning` estaba muerta, y no lo está: es
el flujo de la CLI. Un escaneo incompleto no da falsos negativos, da falsos
POSITIVOS.

## Observabilidad: LangSmith

Ya estaba cableado — `setup_langsmith()` corre al construir cada grafo — y sólo
le faltaba la clave:

```
LANGSMITH_API_KEY=lsv2_...
```

Con eso, cada generación queda navegable en langsmith.com: nodo a nodo, con su
prompt, su respuesta y su coste.

**Corregido el 2026-09-02:** los grafos llamaban a `setup_langsmith()` sin
nombre de proyecto, así que las corridas de **todos** los atletas caían en el
mismo. `configure_langsmith_for_user()` existía desde el principio y no la
llamaba nadie. Ahora hay un proyecto por atleta y por tipo
(`garmin_ai_coach_season_u2`, `garmin_ai_coach_week_u2`).
