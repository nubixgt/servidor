"""El macrociclo anterior, para que el siguiente no empiece de cero.

## Qué faltaba

El planificador de temporada recibía la ADHERENCIA -- qué se le prescribió al
atleta y qué cumplió, semana a semana -- pero no el plan de temporada que
generó esas prescripciones. Auditado sobre un prompt real de 34.948
caracteres: ni el nombre de una fase anterior, ni sus horas objetivo, ni una
línea de su narrativa.

Eso deja al modelo escribiendo el macrociclo número dos sin saber:

  - **Qué fase estaba corriendo el atleta** cuando se rehízo el plan. Volver a
    poner "Base Aeróbica, semanas 1-4" a alguien que iba por la semana 14 de
    Construcción es reiniciar una progresión que ya estaba hecha.
  - **Qué recomendó el plan anterior y por qué**, para poder continuarlo o
    apartarse de ello A PROPÓSITO. Un macrociclo nuevo que contradice al
    anterior sin decirlo deja al atleta sin saber cuál de los dos creer.
  - **Cuánto de la temporada anterior llegó a vivirse**. Un plan que se
    rehace en la semana 2 y otro que se rehace en la 20 son situaciones
    distintas y merecen decisiones distintas.

## Lo que NO se manda, y por qué

La narrativa entera del plan anterior son ~20.000 caracteres de prosa que ya
resumen lo que sus fases dicen en cuatro filas. Se manda **la estructura** --
fases, semanas, horas, énfasis, descargas -- más un recorte corto de la
narrativa y las restricciones que declaró. Lo que hace falta para continuar o
para cambiar con criterio, no para releerlo entero.

Tampoco se manda como una orden. El plan anterior es CONTEXTO: el atleta puede
haber cambiado de objetivo, haberse lesionado o haber pedido expresamente otra
cosa. El bloque lo dice explícitamente para que el modelo no lo trate como una
restricción que no es.
"""

from __future__ import annotations

import logging
from datetime import date, datetime

logger = logging.getLogger(__name__)

# Cuánto de la narrativa anterior entra. Su valor decae rápido -- las fases y
# las restricciones ya llevan lo estructural -- y el prompt de temporada tiene
# otros diez bloques compitiendo por el sitio.
NARRATIVA_MAX = 1500


def semana_alcanzada(season_start: str | None, hoy: date | None = None) -> int | None:
    """En qué semana del plan anterior está el atleta hoy.

    Misma fórmula que `compute_mesocycle_position` en weekly_planner_node: el
    día del arranque es la semana 1. Devuelve None si no hay fecha de arranque
    o es ilegible -- no saberlo es una respuesta, inventar una semana no.
    """
    if not season_start:
        return None
    try:
        inicio = datetime.strptime(str(season_start)[:10], "%Y-%m-%d").date()
    except (ValueError, TypeError):
        return None
    dias = ((hoy or date.today()) - inicio).days
    return max(1, dias // 7 + 1)


def render_previous_season(
    season_plan_md: str | None,
    season_phases: dict | None,
    hoy: date | None = None,
) -> str:
    """El bloque de texto, o "" si no hay macrociclo anterior que contar.

    Devolver "" en la primera temporada del atleta es deliberado: un bloque que
    dijera "no hay plan anterior" gasta tokens para decir nada y le da al
    modelo una ausencia sobre la que razonar.
    """
    from services.ai.preguntas_del_coach import sin_respuestas_al_coach

    fases = (season_phases or {}).get("phases") or []
    # Las respuestas al coach eran de ESE macrociclo; no pasan al siguiente.
    narrativa = sin_respuestas_al_coach(season_plan_md or "").strip()
    if not fases and not narrativa:
        return ""

    inicio = (season_phases or {}).get("season_start_date")
    semana = semana_alcanzada(inicio, hoy)
    ultima = max((int(f.get("end_week") or 0) for f in fases), default=0)

    lineas: list[str] = ["## Macrociclo ANTERIOR (el que este plan reemplaza)", ""]

    if inicio and semana:
        de_cuantas = f" de {ultima}" if ultima else ""
        estado = "**terminado**" if ultima and semana > ultima else "**en curso**"
        lineas.append(
            f"Arrancó el {inicio} y el atleta llegó a la **semana {semana}{de_cuantas}** — {estado}."
        )
        if ultima and semana > ultima:
            lineas.append(
                f"Se le acabó hace {semana - ultima} semana(s), así que las últimas se planificaron "
                "sin fase ni objetivo de horas."
            )
        lineas.append("")

    if fases:
        lineas.append("| Fase | Semanas | h/sem | Énfasis | Descargas |")
        lineas.append("|---|---|---|---|---|")
        for f in fases:
            marca = ""
            if semana and (int(f.get("start_week") or 0) <= semana <= int(f.get("end_week") or 0)):
                marca = "  ← aquí estaba"
            descargas = ", ".join(str(w) for w in (f.get("deload_weeks") or [])) or "—"
            lineas.append(
                f"| {f.get('name', '?')}{marca} | {f.get('start_week')}–{f.get('end_week')} "
                f"| {f.get('weekly_hours_target') or '—'} | {f.get('intensity_emphasis') or '—'} "
                f"| {descargas} |"
            )
        lineas.append("")

    # El campo es `statement`, no `text` ni `description`. Se comprobó contra
    # el plan real: `SeasonConstraint` lo llama así (ver plan_outputs.py), y
    # los otros dos nombres dejaban la cabecera puesta con once restricciones
    # invisibles debajo -- que es peor que no ponerla.
    restricciones = [
        c for c in ((season_phases or {}).get("constraints") or [])
        if isinstance(c, dict) and str(c.get("statement") or "").strip()
    ]
    if restricciones:
        lineas.append("**Restricciones que dejó declaradas:**")
        for c in restricciones[:10]:
            lineas.append(f"- ({c.get('kind', 'otra')}) {str(c['statement']).strip()[:220]}")
        if len(restricciones) > 10:
            lineas.append(f"- ...y {len(restricciones) - 10} más.")
        lineas.append("")

    if narrativa:
        recorte = narrativa[:NARRATIVA_MAX]
        if len(narrativa) > NARRATIVA_MAX:
            recorte += "\n\n[...resto de la narrativa anterior omitido]"
        lineas.extend(["**Cómo lo explicó (extracto):**", "```markdown", recorte, "```", ""])

    # La instrucción, y es la mitad del valor del bloque. Sin ella el modelo
    # puede leer esto como una plantilla a copiar o ignorarlo por completo.
    lineas.extend([
        "**Qué hacer con esto.** Es CONTEXTO, no una restricción: el atleta puede haber cambiado "
        "de objetivo, haberse lesionado o haber pedido expresamente otra cosa, y todo eso pesa más "
        "que la continuidad. Lo que sí debes hacer es **no reiniciar una progresión que el atleta "
        "ya vivió**: si venía de semanas de construcción, el plan nuevo continúa desde ahí en vez "
        "de devolverle a base. Y si te apartas de lo que decía el anterior — otra estructura de "
        "fases, otro reparto entre deportes, otro volumen — **dilo en la narrativa y explica por "
        "qué**, contrastándolo con lo que de verdad cumplió (ver el expediente de adherencia). "
        "Un macrociclo nuevo que contradice al anterior sin decirlo deja al atleta sin saber cuál "
        "de los dos creer.",
        "",
    ])
    return "\n".join(lineas).strip()
