from functools import lru_cache
from typing import Literal

from pydantic import BaseModel, Field, create_model

from .agent_outputs import Question


class ReceiverPayload(BaseModel):
    """One expert's read of its own domain, addressed to one downstream consumer.

    The four list fields are original. The four scalars below were added in
    Plan Maestro v5 Track 4, because the structure was carrying WHAT each
    expert thought without ever carrying HOW MUCH IT MATTERED. Three domains
    look at the same athlete from three angles and routinely disagree --
    "ejecutó todo lo prescrito" next to "la carga viene subiendo" next to "la
    recuperación viene cayendo" -- and the planner had no way to tell which of
    two contradictory claims outranked the other. It picked one silently, and
    picked differently on each run.

    Note what is deliberately absent: any ranking of the EXPERTS themselves.
    Deciding once and for all that physiology outranks activity would be a bias
    baked into the design -- it would hold whether the physiology reading came
    from six measured nights or one. Weight travels with the claim, not with
    the domain that made it (see prompt_components.get_conflict_policy).
    """

    # Los tres van con descripción, y no es cosmética. Sin ella eran tres
    # `list[str]` requeridas y sin explicar, y el modelo se dejó `evidence` en
    # UNO de los tres destinatarios: el nodo murió y la semana salió vacía
    # después de cobrar. Un campo requerido que nadie explica es el que se cae.
    signals: list[str] = Field(
        ...,
        description="Qué ves en tu dominio. Frases cortas, una por hallazgo.",
    )
    evidence: list[str] = Field(
        ...,
        description=(
            "Los NÚMEROS que sostienen cada señal, con su fecha. 'HRV 65 ms el 2026-08-28 "
            "(rango propio 56-82)', no 'HRV normal'. Obligatorio: una señal sin el dato que "
            "la sostiene no se puede contrastar con la de otro experto que diga lo contrario."
        ),
    )
    implications: list[str] = Field(
        ...,
        description="Qué debería hacer con esto quien lo recibe. No repitas la señal: di qué cambia.",
    )
    uncertainty: list[str] | None = None

    confidence: Literal["alta", "media", "baja"] = Field(
        "media",
        description=(
            "How well the DATA supports this read: 'alta' = directly measured, good coverage, recent; "
            "'baja' = inferred, sparse or stale. This is about the evidence, not about how sure you feel."
        ),
    )
    priority: int = Field(
        2,
        ge=1,
        le=3,
        description=(
            "1 = this must shape the receiver's decision; 2 = worth weighing; 3 = context only. "
            "Reserve 1 for the few things that genuinely change what the athlete should do."
        ),
    )
    horizon_days: int | None = Field(
        None,
        description=(
            "Roughly how long this read can be expected to hold before it needs re-checking. A single "
            "night's HRV is a 1-2 day read; a 6-week volume trend is a 30+ day one. Leave empty if unclear."
        ),
    )
    conflicts_with: str = Field(
        "",
        description=(
            "If another domain is likely to claim the opposite, say so in one sentence and name what "
            "would settle it. Empty when there is no plausible contradiction. Surfacing the disagreement "
            "is the point -- do not soften your own read to avoid one."
        ),
    )


class ReceiverOutputs(BaseModel):
    for_synthesis: ReceiverPayload = Field(
        ...,
        description="Output for Synthesis Agent creating comprehensive athlete report"
    )
    for_season_planner: ReceiverPayload = Field(
        ...,
        description="Output for the Season Planner, which designs the macrocycle up to the goal race"
    )
    for_weekly_planner: ReceiverPayload = Field(
        ...,
        description="Output for the Weekly Planner, which decides the next 7 days of training"
    )


RECEPTORES = ("for_synthesis", "for_season_planner", "for_weekly_planner")


@lru_cache(maxsize=8)
def _salida_de(pedidos: tuple[str, ...]) -> type[BaseModel]:
    campos = {r: (ReceiverPayload, ReceiverOutputs.model_fields[r]) for r in pedidos}
    return create_model("ReceiverOutputs", __doc__=ReceiverOutputs.__doc__, **campos)


def salida_para(receptores) -> type[BaseModel]:
    """El esquema del experto con SÓLO las secciones que alguien va a leer en esta generación.

    Semana del atleta 2 del 2026-09-23: cada experto escribía sus tres secciones
    (~3.100, ~2.600 y ~2.500 caracteres) y en una semana sólo se lee
    `for_weekly_planner` -- no corren ni la síntesis ni el macrociclo. Dos tercios de
    su salida, ~0,04 USD de 0,23, se pagaban y no los leía nadie. Sin receptores
    (cualquier camino que no lo diga) se piden las tres, como siempre.
    """
    pedidos = tuple(r for r in RECEPTORES if r in (receptores or ()))
    if not pedidos or pedidos == RECEPTORES:
        return ReceiverOutputs
    return _salida_de(pedidos)


def nota_de_receptores(receptores) -> str:
    """Lo que el prompt tiene que decir cuando se piden menos secciones. "" si se piden todas."""
    pedidos = [r for r in RECEPTORES if r in (receptores or ())]
    if not pedidos or len(pedidos) == len(RECEPTORES):
        return ""
    return ("\n\nEn esta generación sólo se lee " + " y ".join(f"`{r}`" for r in pedidos)
            + ": escribe sólo eso. Las demás secciones de arriba no se piden esta vez.")


class ExpertOutputBase(BaseModel):
    """The HITL-capable shape: EITHER questions OR the analysis.

    Only bound when HITL is actually enabled. The web app runs with
    hitl_enabled=False, and there the experts bind `ReceiverOutputs` directly
    -- a union in a structured-output schema measurably degrades the result,
    which is the same finding that had the workout formatter returning empty
    step lists for roughly half its days before v4 narrowed its schema.
    """

    output: list[Question] | ReceiverOutputs = Field(
        ...,
        description="EITHER questions for HITL OR full output for downstream consumers"
    )


class MetricsExpertOutputs(ExpertOutputBase):
    pass


class ActivityExpertOutputs(ExpertOutputBase):
    pass


class PhysiologyExpertOutputs(ExpertOutputBase):
    pass
