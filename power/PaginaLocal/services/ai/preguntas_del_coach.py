"""El coach mira los datos del atleta, le pregunta lo que los datos no dicen, y después planifica.

## Por qué existe

Los datos de Garmin no cuentan cómo se sintió el atleta tras sus series, si la
molestia del jueves sigue, o si esa semana floja fue un viaje. Un entrenador lo
pregunta antes de decidir. El LangGraph original de Leon ya lo hacía, desde la
consola: cada experto podía devolver preguntas en vez de su análisis y el
orquestador las recogía con `input()`. Aquí estaba apagado, porque la app web
no tenía consola.

## Por qué un paso aparte y no el mecanismo de Leon

Su diseño relanza, con la respuesta, a los expertos que preguntaron. Pero en el
grafo los tres expertos convergen en un punto que espera a los TRES (`add_edge`
con una lista es una barrera): si sólo se relanza uno, la barrera no vuelve a
dispararse y la generación se queda parada sin avisar. Además cada experto
preguntaba por su cuenta, en paralelo, y el atleta habría recibido tres tandas.

Este paso corre UNA vez, entre los resumidores y los expertos: lee los tres
resúmenes -- lo que un entrenador mira primero --, decide si hay algo que
preguntar (hasta tres cosas, y ninguna es una respuesta válida), espera la
respuesta, y la deja en la conversación de cada experto y de los dos
planificadores. Ningún nodo tiene que saber que existe: los cinco ya leían
esa conversación (`*_messages`) desde el diseño de Leon.

## Cuándo pregunta y cuándo no

Sólo cuando hay un proveedor registrado (`usar_proveedor`), que en la app es el
worker de cada generación. Sin proveedor -- pruebas, CLI, un script -- no gasta
la llamada y devuelve `{}`. Nunca rompe una generación: cualquier fallo se
registra y la generación sigue sin preguntas.

## Dónde se guardan las respuestas: en el macrociclo, no en el cuaderno

La primera versión las anotaba en el cuaderno para no repetir preguntas. Pero
el cuaderno es para hechos DURADEROS y cada generación pregunta: en unas
semanas el cuaderno sería una pila de "cómo te sentiste tras las series del
9-sep", y un macrociclo nuevo, dentro de un año, seguiría leyendo "vas a tope
al 5K Monterrico". Lo dijo el atleta: la conclusión ya la saca el macrociclo.

Así que las respuestas de la temporada van al FINAL del informe del macro
(`seccion_de_respuestas`): la semana las lee con el macro y no las vuelve a
preguntar, el atleta las ve en su informe, y cuando el macro se borra o se
rehace se van con él -- `sin_respuestas_al_coach` las quita antes de dárselo
al macrociclo siguiente como plan anterior. Las de una semana sólo sirven a
esa semana y no se guardan en ningún sitio más que en su traza.
"""

from __future__ import annotations

import asyncio
import logging
from datetime import datetime
from typing import Any, Protocol

from langchain_core.callbacks.usage import get_usage_metadata_callback
from langchain_core.messages import AIMessage, HumanMessage
from pydantic import BaseModel, Field

logger = logging.getLogger(__name__)

MAX_PREGUNTAS = 3


class PreguntaAlAtleta(BaseModel):
    pregunta: str = Field(
        ...,
        description="La pregunta, en español, corta y concreta, dirigida al atleta de tú. Una sola cosa por "
        "pregunta. Ejemplo de forma: '¿Cómo te encontraste el día después de las series del martes?'",
    )
    por_que: str = Field(
        ...,
        description="En una frase, qué dato viste que te hace preguntarlo (con su fecha). Se le enseña al "
        "atleta para que entienda por qué se lo preguntas.",
    )


class PreguntasDelCoach(BaseModel):
    preguntas: list[PreguntaAlAtleta] = Field(
        default_factory=list,
        max_length=MAX_PREGUNTAS,
        description="Entre 0 y 3 preguntas. Una lista vacía es la respuesta correcta cuando los datos ya "
        "bastan para planificar bien.",
    )


class ProveedorDePreguntas(Protocol):
    def preguntar(self, preguntas: list[dict[str, str]]) -> list[str]:
        """Devuelve una respuesta por pregunta, en el mismo orden ("" si no contestó)."""
        ...

    # Opcional: `sin_preguntas()`, para avisar de que el coach no va a
    # preguntar. Ver `_avisar_sin_preguntas`.


def _avisar_sin_preguntas(quien: Any) -> None:
    """Le dice al proveedor que ya no habrá preguntas, si sabe escucharlo.

    El worker lo usa para que la pantalla deje de pedir que no se cierre (ver
    `ProveedorWeb.sin_preguntas` en AiService/worker.py). Opcional y sin
    consecuencias: un proveedor que no lo implementa sigue funcionando igual.
    """
    aviso = getattr(quien, "sin_preguntas", None)
    if callable(aviso):
        try:
            aviso()
        except Exception:  # noqa: BLE001 - un aviso a la pantalla no puede costar la generación
            logger.warning("No se pudo avisar de que no hay preguntas", exc_info=True)


_proveedor: ProveedorDePreguntas | None = None


def usar_proveedor(proveedor: ProveedorDePreguntas | None) -> None:
    """Registra quién le hace llegar las preguntas al atleta. Uno por proceso."""
    global _proveedor
    _proveedor = proveedor


def proveedor() -> ProveedorDePreguntas | None:
    return _proveedor


SISTEMA = """Eres el entrenador de este atleta. Antes de analizar sus datos a fondo y planificar, los miras
por encima y decides si hay algo que SOLO el atleta te puede decir y que cambiaría lo que vas a planificar.

Pregunta cuando los datos no bastan o se contradicen, sobre cosas que ningún reloj mide:
- cómo se sintió después de sus sesiones más exigentes, o si le costó recuperarse;
- si una molestia o un dolor que aparece en sus datos o en su check-in sigue ahí;
- qué pasó en una semana rara (menos entrenamiento, pulso alterado, sueño peor): viaje, trabajo, enfermedad;
- qué quiere cuando sus datos admiten dos caminos razonables.

No preguntes lo que ya está en su perfil, en su cuaderno, en lo que ya te contestó -- ni para su macrociclo
ni al generar su semana anterior -- o en los datos, ni nada genérico ("¿cómo estás?"). Volver a preguntarle
algo que contestó hace siete días es la forma más rápida de que deje de contestar.
Hasta tres preguntas, cortas, de tú, en español. Si con los datos basta, no preguntes nada: es la respuesta
correcta más a menudo de lo que parece, y cada pregunta le cuesta tiempo al atleta."""

USUARIO = """{calendario}

## Lo que declara el atleta
{contexto}{checkin}

## Su cuaderno
{cuaderno}

## Lo que ya te contestó para su macrociclo vigente
{respondido}

{semana_anterior}

## Resumen de sus actividades
{actividades}

## Resumen de sus métricas de carga
{metricas}

## Resumen de su fisiología y recuperación
{fisiologia}

{respuesta}

Decide si hay algo que preguntarle antes de planificar."""


_DIAS_ES = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]


def calendario(state: dict) -> str:
    """Hoy y sus carreras, con el DÍA DE LA SEMANA escrito.

    Este nodo no recibía la fecha de hoy ni ningún día de la semana: sólo las
    fechas de las carreras dentro del perfil. En el macrociclo del atleta 2
    (2026-09-13) preguntó "El 5K de Monterrico es el LUNES 20/09... coincide con
    tu lunes de fuerza fijo", y el 20 era domingo. Una pregunta con un dato
    falso le enseña al atleta que el coach no mira su calendario. El día de la
    semana es un cálculo, no algo que un modelo deba adivinar.
    """
    from datetime import date

    hoy_raw = str((state.get("current_date") or {}).get("date") or "")[:10]
    try:
        hoy = date.fromisoformat(hoy_raw)
    except ValueError:
        return ""
    lineas = [f"## Hoy\n{_DIAS_ES[hoy.weekday()]} {hoy.isoformat()}."]
    # Y los días de la semana que se va a planificar, con su nombre. José,
    # 2026-09-15: sin ellos preguntó por "el martes (16/09)" y "el miércoles
    # (17/09)", que eran miércoles y jueves.
    semana = []
    for d in state.get("week_dates") or []:
        try:
            dia = date.fromisoformat(str((d or {}).get("date") or "")[:10])
        except (ValueError, AttributeError):
            continue
        semana.append(f"{_DIAS_ES[dia.weekday()]} {dia.isoformat()}")
    if semana:
        lineas.append("## La semana que se va a planificar\n" + ", ".join(semana) + ".")
    carreras = []
    for c in state.get("competitions") or []:
        if not isinstance(c, dict):
            continue
        try:
            dia = date.fromisoformat(str(c.get("date") or "")[:10])
        except ValueError:
            continue
        if dia < hoy:
            continue
        carreras.append(
            f"- {c.get('name') or 'carrera'}: {_DIAS_ES[dia.weekday()]} {dia.isoformat()} "
            f"(prioridad {c.get('priority') or '?'}, dentro de {(dia - hoy).days} días)"
        )
    if carreras:
        lineas.append("## Sus próximas carreras\n" + "\n".join(carreras))
    return "\n\n".join(lineas)


def _texto(state: dict, clave: str, limite: int = 12000) -> str:
    valor = str(state.get(clave) or "").strip()
    if len(valor) > limite:
        return valor[:limite] + f"\n[... recortado: {len(valor) - limite} caracteres más en el original]"
    return valor or "(sin datos)"


ENCABEZADO_RESPUESTAS = "## Lo que respondiste a tu coach"


def seccion_de_respuestas(respondidas: list[dict[str, str]], fecha: str) -> str:
    """La sección que el macrociclo añade al final de su informe. "" si no hay nada contestado."""
    lineas = []
    for qa in respondidas or []:
        respuesta = str(qa.get("respuesta") or "").strip()
        if not respuesta:
            continue
        lineas.append(f"- **{str(qa.get('pregunta') or '').strip()}** {respuesta}")
    if not lineas:
        return ""
    return (
        f"\n\n{ENCABEZADO_RESPUESTAS}\n"
        f"Antes de escribir este macrociclo ({fecha}) te pregunté lo que tus datos no decían. "
        "Estas respuestas valen para este plan: si lo borras o lo rehaces, se van con él.\n\n"
        + "\n".join(lineas)
    )


def _partir(markdown: str) -> tuple[str, str]:
    """(el informe sin la sección, la sección). La sección va hasta el siguiente "## " o el final."""
    texto = markdown or ""
    i = texto.find(ENCABEZADO_RESPUESTAS)
    if i < 0:
        return texto, ""
    fin = texto.find("\n## ", i + len(ENCABEZADO_RESPUESTAS))
    fin = len(texto) if fin < 0 else fin
    return (texto[:i].rstrip() + texto[fin:]), texto[i:fin].strip()


def sin_respuestas_al_coach(markdown: str) -> str:
    """El informe del macro sin las respuestas: lo que ve el macrociclo SIGUIENTE como plan anterior."""
    return _partir(markdown)[0]


def respuestas_del_macrociclo(markdown: str) -> str:
    """Sólo la sección de respuestas del macro vigente, o ""."""
    return _partir(markdown)[1]


def _mensajes(preguntas: list[dict[str, str]], respuestas: list[str]) -> list:
    """La conversación, tal cual la leerán los expertos y los planificadores."""
    lineas_p = [f"{i}. {p['pregunta']}" for i, p in enumerate(preguntas, 1)]
    lineas_r = []
    for i, r in enumerate(respuestas, 1):
        r = (r or "").strip()
        lineas_r.append(f"{i}. {r}" if r else f"{i}. (no contestó; decide con los datos)")
    return [
        AIMessage(content="Antes de planificar le pregunté al atleta:\n" + "\n".join(lineas_p)),
        HumanMessage(content="Respuestas del atleta:\n" + "\n".join(lineas_r)
                     + "\n\nLo que dice el atleta sobre cómo se siente pesa tanto como sus datos: úsalo."),
    ]


async def preguntas_del_coach_node(state: dict) -> dict[str, Any]:
    quien = proveedor()
    if quien is None:
        return {}
    inicio = datetime.now()
    try:
        from services.ai.ai_settings import AgentRole
        from services.ai.langgraph.nodes.node_base import create_cost_entry
        from services.ai.model_config import ModelSelector

        constraints = state.get("availability_constraints") or {}
        respuesta = ((constraints.get("training_response") or {}).get("block") or "")
        cuaderno = ((constraints.get("notebook") or {}).get("block") or "").strip()
        # Lo que ya contesto para el macro vigente. Sin esto el coach
        # preguntaba lo mismo en el macrociclo y en la semana: se vio en la
        # primera prueba real, dos de tres preguntas repetidas.
        from services.ai.checkin import bloque_del_checkin
        from services.ai.langgraph.nodes.weekly_planner_node import extract_season_plan_text
        respondido = respuestas_del_macrociclo(extract_season_plan_text(state.get("season_plan")))
        # Y lo que contesto al generar la semana ANTERIOR: sin esto el coach
        # repetia sus mismas tres preguntas cada semana (medido el 2026-09-11).
        semana_anterior = ((constraints.get("respuestas_previas") or {}).get("block") or "").strip()
        mensajes = [
            {"role": "system", "content": SISTEMA},
            {"role": "user", "content": USUARIO.format(
                calendario=calendario(state),
                # El perfil ENTERO, no los primeros 6.000 caracteres.
                #
                # Con el recorte, el equipamiento y el entorno -- que van al
                # final del bloque -- no llegaban, y el coach preguntaba cosas
                # que el atleta ya habia declarado: "¿tienes rodillo?" a quien
                # tiene puesto "las dos" en su perfil (visto el 2026-09-11).
                # Preguntar lo que ya contesto es la forma mas rapida de que
                # deje de contestar, y el perfil real mide ~9.000 caracteres.
                contexto=_texto(state, "planning_context", 20000),
                # Su check-in de hoy. Sin él preguntaba lo que acababa de contestar
                # ("¿cómo quedaste de piernas?", "¿tienes algún viaje?"): atleta 2,
                # Beta 2.0 publicada, 2026-09-21. "" sin check-in.
                checkin=bloque_del_checkin(state),
                cuaderno=cuaderno or "(vacío)",
                respondido=respondido or "(nada)",
                semana_anterior=semana_anterior or "",
                actividades=_texto(state, "activity_summary"),
                metricas=_texto(state, "metrics_summary", 6000),
                fisiologia=_texto(state, "physiology_summary", 6000),
                respuesta=respuesta,
            )},
        ]
        # El modelo del experto de fisiología: aquí hay criterio -- decidir qué
        # merece la pena preguntar --, no compresión.
        llm = ModelSelector.get_llm(AgentRole.PHYSIOLOGY_EXPERT).with_structured_output(PreguntasDelCoach)
        with get_usage_metadata_callback() as uso:
            salida: PreguntasDelCoach = await llm.ainvoke(mensajes)
        coste = create_cost_entry("preguntas_del_coach", (datetime.now() - inicio).total_seconds(),
                                  uso.usage_metadata)
        preguntas = [p.model_dump() for p in (salida.preguntas or [])][:MAX_PREGUNTAS]
        if not preguntas:
            logger.info("El coach no tiene preguntas: los datos bastan")
            _avisar_sin_preguntas(quien)
            return {"costs": [coste]}

        logger.info("El coach pregunta %d cosa(s) al atleta", len(preguntas))
        # En un hilo: el proveedor espera la respuesta del atleta (minutos), y
        # hacerlo en el bucle de eventos lo congelaria entero mientras tanto.
        respuestas = list(await asyncio.to_thread(quien.preguntar, preguntas))
        respuestas += [""] * (len(preguntas) - len(respuestas))
        conversacion = _mensajes(preguntas, respuestas[:len(preguntas)])
        return {
            "costs": [coste],
            "respuestas_al_coach": [{**p, "respuesta": (r or "").strip()}
                                    for p, r in zip(preguntas, respuestas, strict=False)],
            "metrics_expert_messages": list(conversacion),
            "physiology_expert_messages": list(conversacion),
            "activity_expert_messages": list(conversacion),
            "season_planner_messages": list(conversacion),
            "weekly_planner_messages": list(conversacion),
        }
    except Exception:  # noqa: BLE001 - una pregunta nunca puede costar la generación
        logger.warning("El paso de preguntas del coach falló; se sigue sin preguntas", exc_info=True)
        _avisar_sin_preguntas(quien)
        return {}
