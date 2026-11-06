# Los dos flujos que corren de verdad

El `CLAUDE.md` dice que la app web usa
`create_integrated_analysis_and_planning_workflow`. **No es cierto desde hace
tiempo**: ese flujo existe y lo usa la **CLI**, no la web.

| Botón de la app | Función | Grafo | Nodos |
|---|---|---|---|
| Generar macrociclo | `run_analysis_and_season_planning` | `create_analysis_and_season_workflow` | 13 |
| Generar semana | `run_week_generation` | `create_week_generation_workflow` | 8 |
| *(sólo CLI)* | `run_complete_analysis_and_planning` | `create_integrated_...` | 16 |

## Redibujarlos

```bash
cd PaginaLocal
python -c "
import sys; sys.path.insert(0,'.')
from services.ai.langgraph.workflows.planning_workflow import create_week_generation_workflow
print(create_week_generation_workflow().get_graph().draw_mermaid())
"
```

Pegar en https://mermaid.live.

## El dibujo del de temporada sale INCOMPLETO, y no es un fallo

`draw_mermaid()` muestra `master_orchestrator → __end__` y cinco cajas sin
aristas. El orquestador **no tiene salidas fijas**: enruta en ejecución con
`Command(goto=…)`, y eso no existe en el grafo estático.

Sus cuatro salidas, leídas de `orchestrator_node.py`:

| Situación | A dónde va |
|---|---|
| Sin preguntas de los expertos | `synthesis` **y** `data_integration` a la vez ← **la de la app** |
| Sin preguntas y `skip_synthesis` | sólo `data_integration` |
| Con preguntas y HITL apagado | `synthesis` |
| Con preguntas y HITL encendido | **vuelve a los expertos** ← el bucle |

**El bucle nunca se dispara en la web.** El servidor llama con
`hitl_enabled=False`, y eso cambia el esquema de salida de los expertos a
`ReceiverOutputs`, que no tiene campo de preguntas. Es el camino de la CLI.

## Fuera de los grafos

El **entrenador del chat** y el **Doctor** no están en ningún grafo: son
llamadas sueltas, sin LangGraph.
