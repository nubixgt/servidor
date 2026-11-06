"""Las semanas del macrociclo son las semanas del ATLETA, no bloques de 7 días desde que se generó.

Atleta 2, Beta 2.0 publicada, 2026-09-21: su macrociclo arrancó el martes
2026-09-15 y su semana empieza el lunes (`week_start_day`). El lunes 21 empezó
su segunda semana de entrenamiento, y la pantalla seguía marcando la semana 1
de 31. No era sólo la pantalla: la semana se calcula con la misma fórmula
(`compute_mesocycle_position`), así que la decisión semanal también creyó que
seguía en la semana 1 -- con una descarga en la semana 7, la habría puesto una
semana tarde.

La causa: `season_start_date` es el día en que se generó el macrociclo, y todas
las cuentas hacen `(fecha - arranque) // 7 + 1`. Eso sólo coincide con las
semanas del atleta si generó el día en que empieza su semana. El arreglo es el
arranque, no cada cuenta: la semana 1 es la semana del atleta que contiene el
día en que se generó, así que su arranque efectivo es el último `week_start_day`
anterior o igual a ese día. Con eso todas las cuentas que ya existen salen bien.

Se aplica al LEER (el espejo de AiService, `bridge/storage_adapter.py`) y no
sólo al generar: así los macrociclos ya guardados se corrigen sin tocar la base,
y si el atleta cambia el día en que empieza su semana, las cuentas le siguen.
Sin `week_start_day` sus semanas son bloques de 7 días desde el primero, que es
lo que ya hacía la fórmula: no se toca nada.

Un caso que queda a propósito así: si genera a uno o dos días de que empiece su
semana, su PRIMER bloque de entrenamiento dura más de siete días
(`MIN_DIAS_DEL_BLOQUE` en web_app/server.py), y cubre las semanas 1 y 2 del
macrociclo. Las semanas del macrociclo son de calendario; la siguiente semana
de entrenamiento es la 3, que es la que de verdad es.

El espejo JS es `alinearArranque` en Frontend/src/utils/macrociclo.js y
`tests/test_semanas_del_macrociclo.py` fija que hacen lo mismo.
"""

from __future__ import annotations

from datetime import date, timedelta

DIAS = ("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo")


def arranque_de_sus_semanas(inicio_iso: str | None, week_start_day: str | None) -> str | None:
    """El lunes (o el día que elija) de la semana del atleta que contiene `inicio_iso`.

    Devuelve `inicio_iso` tal cual si no hay día elegido o la fecha no se entiende.
    """
    if not inicio_iso or week_start_day not in DIAS:
        return inicio_iso
    try:
        inicio = date.fromisoformat(str(inicio_iso)[:10])
    except ValueError:
        return inicio_iso
    atras = (inicio.weekday() - DIAS.index(week_start_day)) % 7
    return (inicio - timedelta(days=atras)).isoformat()


def con_arranque_de_sus_semanas(fases, week_start_day: str | None):
    """Las fases con `season_start_date` alineado a sus semanas. Copia; lo demás no cambia."""
    if not isinstance(fases, dict) or not fases.get("season_start_date"):
        return fases
    alineado = arranque_de_sus_semanas(fases["season_start_date"], week_start_day)
    if alineado == fases["season_start_date"]:
        return fases
    return {**fases, "season_start_date": alineado}
