"""Reconstruye el prompt del codificador, con el texto de HOY, para cada semana archivada.

Cada traza guarda la decisión (`week_intents.json`) y la ficha que recibió el codificador
(`prescription.json` → `card`); el prompt es esas dos cosas más las instrucciones fijas. Así el banco
del codificador (`scripts/banco_codificador.py`) no se queda en las pocas semanas cuyo prompt se
archivó, y prueba las instrucciones actuales. Sin modelos.

Lo que no reconstruye: los días de fuerza que en su día cubrió la rutina confirmada del atleta (S5)
salen del prompt sólo si se le pasa `sin_fuerza=True`, que es lo que hace el banco.
"""

from __future__ import annotations

import json
from pathlib import Path

from services.ai.langgraph.nodes.prompt_components import get_workflow_context
from services.ai.langgraph.nodes.weekly_planner_node import (
    WORKOUT_FORMATTER_SYSTEM_PROMPT,
    WORKOUT_FORMATTER_USER_PROMPT,
    _hay_fuerza,
    render_catalogo_para_prompt,
    render_exercise_catalogue,
)
from services.ai.langgraph.schemas.plan_outputs import WeekIntentContent


def semanas(entrada: Path, sin_fuerza: bool = False) -> list[tuple[WeekIntentContent, list[dict]]]:
    """Cada semana de la decisión archivada, con los mensajes que recibiría hoy el codificador."""
    decision = json.loads((entrada / "week_intents.json").read_text(encoding="utf-8"))
    ficha = (json.loads((entrada / "prescription.json").read_text(encoding="utf-8")) or {}).get("card") or ""
    salida = []
    for i, semana in enumerate(decision if isinstance(decision, list) else [decision], start=1):
        intent = WeekIntentContent.model_validate(semana)
        if sin_fuerza:
            intent = intent.model_copy(update={"days": [d for d in intent.days if d.sport != "strength"]})
        if not [d for d in intent.days if not d.is_rest]:
            continue
        usuario = WORKOUT_FORMATTER_USER_PROMPT.format(
            week_number=getattr(intent, "week_number", None) or i,
            prescription_card=ficha,
            exercise_catalogue=render_exercise_catalogue() if _hay_fuerza(intent) else "",
            exercise_images=render_catalogo_para_prompt() if _hay_fuerza(intent) else "",
            week_intent=intent.model_dump_json(indent=2),
        )
        sistema = get_workflow_context("weekly_planner") + WORKOUT_FORMATTER_SYSTEM_PROMPT
        salida.append((intent, [{"role": "system", "content": sistema}, {"role": "user", "content": usuario}]))
    return salida
