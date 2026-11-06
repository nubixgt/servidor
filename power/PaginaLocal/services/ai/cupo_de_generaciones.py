"""Cuántas versiones de una misma semana puede generar un atleta (D2, Beta 3.0 F2).

Decisión del responsable (2026-10-10): como mucho 3 versiones de una misma semana y 1
macrociclo cada 7 días; los fallos no gastan cupo; la semana automática no cuenta. Antes
nada lo impedía: en la Beta 1 un atleta generó 16 planes en un día, a ~0,30 USD cada uno.

La SEMANA OBJETIVO la sabe sólo el cerebro (`server.semana_objetivo`: la siguiente a la
última del plan, la que se rehace, o hoy/mañana en la primera). PHP guarda qué semanas
se generaron (`athlete_generations`) y se las manda al puente; aquí se decide si queda
cupo, para que no haya dos copias de la regla que decide qué semana se escribe.

**Una versión cuenta para una semana si su inicio cae a ±1 día.** La primera semana del
plan arranca hoy o mañana, a elección del atleta: borrarla y pedirla "desde mañana" no
puede ser una semana nueva. Dos semanas de verdad nunca están a menos de 3 días
(`MIN_DIAS_DEL_BLOQUE`), así que la holgura no las junta.
"""

from __future__ import annotations

from collections.abc import Iterable
from datetime import date, datetime
from typing import Any

# Las dos constantes viven también en PHP (`GenerationRepository`): una prueba fija que
# valen lo mismo.
TOPE_POR_SEMANA = 3
DIAS_ENTRE_MACROCICLOS = 7

_HOLGURA_DIAS = 1


def _fecha(valor: Any) -> date | None:
    if isinstance(valor, date):
        return valor
    try:
        return datetime.strptime(str(valor)[:10], "%Y-%m-%d").date()
    except ValueError:
        return None


def versiones_de(objetivo: date, previas: Iterable[Any]) -> int:
    """Cuántas de las semanas ya generadas son esta misma semana."""
    n = 0
    for p in previas or []:
        f = _fecha(p)
        if f is not None and abs((f - objetivo).days) <= _HOLGURA_DIAS:
            n += 1
    return n


def _fecha_para_el_atleta(d: date) -> str:
    dias = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]
    meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto",
             "septiembre", "octubre", "noviembre", "diciembre"]
    return f"{dias[d.weekday()]} {d.day} de {meses[d.month - 1]}"


def cupo_de_la_semana(objetivo: date, previas: Iterable[Any], tope: int = TOPE_POR_SEMANA) -> dict[str, Any]:
    """Lo que queda de esta semana. `previas` son los inicios de las generaciones que
    cuentan (no automáticas, sin error): lo filtra quien las guarda."""
    usadas = versiones_de(objetivo, previas)
    quedan = max(tope - usadas, 0)
    salida = {
        "cupo": True,
        "target_week": objetivo.isoformat(),
        "usadas": usadas,
        "quedan": quedan,
        "tope": tope,
        "allowed": quedan > 0,
        "reason": "",
    }
    if quedan == 0:
        salida["reason"] = (
            f"Ya generaste las {tope} versiones de tu semana del {_fecha_para_el_atleta(objetivo)}. "
            "Si algo no encaja, cuéntanoslo desde «Ayuda» y lo revisamos contigo."
        )
    return salida
