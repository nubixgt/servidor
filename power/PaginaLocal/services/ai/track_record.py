"""El expediente: qué se le recetó a este atleta y cuánto de eso cumplió.

## El hueco que llena

El planificador de temporada sabía **qué hizo** el atleta -- Garmin le da 180
días de actividades reales -- y sabía **qué le mandó la última vez**, porque
carga el plan de temporada anterior. Lo que no sabía era la relación entre las
dos cosas a lo largo del tiempo: *le prescribí tres sesiones de calidad por
semana durante ocho semanas y cumplió el 40% de ellas*.

Esa frase es la diferencia entre un plan y un entrenador. Un plan repite lo
que le parece correcto; un entrenador se acuerda de que lo que mandó no se
cumplió y ajusta lo que manda, no al atleta.

La adherencia ya se calculaba (`services/ai/adherence.py`), pero sólo contra
`weekly_plan.json` -- que desde la generación semana a semana contiene UNA
semana. El macrociclo veía la adherencia de siete días para decidir dieciséis
semanas.

## Por qué NO es "mandarle el historial"

`data/storage/<perfil>/history/` guarda cada generación entera y pesa ~14 MB
al año por atleta. Mandarle eso a un modelo es exactamente la explosión de
tokens que este proyecto lleva seis versiones evitando, y encima la mayor
parte son pasos de entrenamiento que ya no le importan a nadie.

Lo que un entrenador recuerda de hace cuatro meses no es el entreno del
martes: es **el patrón**. Así que esto guarda una línea por semana con lo
prescrito, lo ejecutado y lo que se cayó -- unos 200 bytes. Diez meses caben
en 9 KB, menos que un solo informe de experto.

## Append-only, y sobrevive al borrado del plan

Vive en `<perfil>/track_record.jsonl`, no dentro del plan. Borrar la semana o
regenerar el macrociclo no puede borrar el hecho de que hace tres meses se
prescribieron cuatro series y se hicieron dos: eso ya pasó, y es justo lo que
el planificador necesita saber para no volver a prescribirlas.

Es el mismo principio que `core/usage_ledger.py`, aplicado a otra cosa: un
registro que se borra cuando el usuario limpia no es un registro.

## Lo que este módulo NO hace

No juzga. No dice "mala adherencia" ni pone notas: reporta lo prescrito, lo
ejecutado y la diferencia, con fechas. Decidir si un 60% de cumplimiento
significa que el atleta se desmotivó, que el plan era demasiado, o que tuvo un
mes de viajes es exactamente el trabajo del planificador -- que además tiene
el cuaderno y el check-in para saberlo.
"""

from __future__ import annotations

import json
import logging
import os
import threading
from collections import Counter
from datetime import date, datetime
from pathlib import Path
from typing import Any

from services.ai.langgraph.schemas.plan_outputs import PACE_TARGETED_CLASSES

logger = logging.getLogger(__name__)

TRACK_RECORD_FILE = "track_record.jsonl"

# Cuántas semanas del expediente se le muestran al planificador de temporada.
# 26 son medio año: cubre un macrociclo entero y el anterior, que es la
# ventana en la que un patrón significa algo. Más atrás, el atleta ya es otro
# -- y las 180 días de actividades de Garmin siguen estando ahí para el arco
# largo.
WEEKS_IN_PROMPT = 26

# Clases de intensidad que cuentan como "sesión de calidad". Son las que un
# entrenador mira cuando se pregunta si su plan se está cumpliendo: el rodaje
# suave se hace casi siempre, las series no.
#
# SALE DEL ESQUEMA, no de un vocabulario escrito aquí. La primera versión
# listaba a mano palabras de entrenador -- "threshold", "tempo", "intervals",
# "fartlek", "cuestas" -- y ninguna de ellas es un valor de `IntensityClass`.
# La comparación es de pertenencia exacta a un conjunto, así que el efecto era
# doble y los dos lados estaban mal:
#
#   - `tempo_threshold`, que es LA sesión de calidad más común de todas, no
#     coincidía con nada y no se contaba nunca;
#   - `neuromuscular` sí coincidía, y no es una sesión de calidad: son
#     progresivos, tramos cortos y técnica, que el validador excluye
#     explícitamente de `PACE_TARGETED_CLASSES` porque mantenerlos en una
#     semana de descarga es buen entrenamiento, no una sesión dura.
#
# Medido contra el plan real del atleta: su "Aeróbico + técnica" del 28/08
# figuraba como calidad no realizada y su semana decía 2 sesiones de calidad
# donde había 1. `race_day` sí se suma: la carrera es la sesión de calidad de
# la que cuelga todo lo demás.
# `hill_repeats` entra aunque NO esté en PACE_TARGETED_CLASSES, y el motivo de
# estar fuera de aquella es justo el contrario del de estar dentro de ésta: en
# una cuesta el ritmo no mide nada (el mismo esfuerzo da 4:30 o 7:30 según el
# desnivel), así que no se le puede exigir una banda de ritmo -- pero 6x3' en
# cuesta fuerte ES la sesión dura de la semana de un corredor de montaña. Sin
# ella, un trailrunner veía "0 sesiones de calidad" en su expediente y en sus
# gráficas la semana en que más se había vaciado.
#
# `downhill_technique` y `hike_run` se quedan fuera: la primera es técnica y
# carga excéntrica, la segunda es volumen en pendiente. Cansan, pero no son la
# sesión de intensidad de la semana, y contarlas inflaría el recuento igual
# que hacía `neuromuscular`.
QUALITY_CLASSES = frozenset(PACE_TARGETED_CLASSES | {"race_day", "hill_repeats"})

# Para planes anteriores a que `intensity_class` existiera, que lo traen a
# None. Sin esto una semana antigua cuenta cero sesiones de calidad, que se
# lee como "no le prescribí ninguna" en vez de "no lo sé".
_QUALITY_WORDS = (
    "vo2", "umbral", "threshold", "tempo", "serie", "interval",
    "repetici", "cuesta", "fartlek", "ritmo de carrera", "competic",
)

_write_lock = threading.Lock()


def _path(storage_dir: Path) -> Path:
    return Path(storage_dir) / TRACK_RECORD_FILE


def _append(storage_dir: Path, record: dict[str, Any]) -> None:
    try:
        path = _path(storage_dir)
        path.parent.mkdir(parents=True, exist_ok=True)
        with _write_lock:
            with open(path, "a", encoding="utf-8") as handle:
                handle.write(json.dumps(record, ensure_ascii=False, default=str) + "\n")
                handle.flush()
                os.fsync(handle.fileno())
    except OSError:
        # Igual que el libro de costos: perder la anotación es malo, perder la
        # generación que el atleta ya pagó por no poder anotarla es peor.
        logger.exception("No se pudo anotar el expediente -- linea perdida: %s", record)


def lineas_crudas(storage_dir: Path) -> dict[str, list[str]]:
    """Las líneas del expediente y de sus descartadas TAL CUAL, para su copia en MySQL (Database/034).

    Sin parsear: la copia tiene que poder reponer el fichero idéntico, y una línea ilegible también
    es parte de él (se dice al leer, no se tira al copiar).
    """
    salida: dict[str, list[str]] = {}
    for clave, nombre in (("expediente", TRACK_RECORD_FILE), ("descartadas", DESCARTADAS_FILE)):
        path = Path(storage_dir) / nombre
        texto = path.read_text(encoding="utf-8") if path.exists() else ""
        salida[clave] = [linea for linea in texto.splitlines() if linea.strip()]
    return salida


def read_all(storage_dir: Path) -> list[dict[str, Any]]:
    path = _path(storage_dir)
    if not path.exists():
        return []
    salida = []
    for numero, linea in enumerate(path.read_text(encoding="utf-8").splitlines(), start=1):
        linea = linea.strip()
        if not linea:
            continue
        try:
            salida.append(json.loads(linea))
        except json.JSONDecodeError:
            logger.warning("Linea %d del expediente ilegible, se omite", numero)
    return salida


def _is_quality(day: dict[str, Any]) -> bool:
    """Si esta sesión es de las que un entrenador cuenta como calidad.

    Vale para CUALQUIER deporte: `intensity_class` describe qué es la sesión,
    no en qué deporte se hace, así que un 8x100 en la piscina y unas series de
    bici cuentan igual que unas de pista. Lo que decide es la intención, y esa
    la declara el propio coach en la capa de decisión.
    """
    declarada = (day.get("intensity_class") or "").lower()
    if declarada:
        return declarada in QUALITY_CLASSES
    texto = f"{day.get('focus') or ''} {day.get('purpose') or ''}".lower()
    return any(palabra in texto for palabra in _QUALITY_WORDS)


_WEEKDAYS_ES = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]


def _weekday(iso: str | None) -> str | None:
    try:
        return _WEEKDAYS_ES[datetime.strptime(iso or "", "%Y-%m-%d").weekday()]
    except (TypeError, ValueError):
        return None


def _week_span(week: dict[str, Any]) -> tuple[str, str]:
    fechas = sorted(d.get("date") for d in (week.get("days") or []) if d.get("date"))
    return (fechas[0], fechas[-1]) if fechas else ("", "")


def summarise_week(week: dict[str, Any], adherence: dict[str, Any] | None,
                   load_state: dict[str, Any] | None = None,
                   marcadores: dict[str, Any] | None = None) -> dict[str, Any]:
    """Una semana entera, reducida a lo que un entrenador recordaría de ella.

    `adherence` es el estado que ya produce `services/ai/adherence.py`; se
    acepta vacío porque la primera semana de un atleta no tiene nada contra
    qué medirse todavía.
    """
    dias = [d for d in (week.get("days") or []) if not d.get("is_rest")]
    desde, hasta = _week_span(week)

    por_disciplina: Counter = Counter()
    minutos_prescritos = 0.0
    calidad_prescrita = []
    for dia in dias:
        minutos = float(dia.get("total_duration_minutes") or 0)
        minutos_prescritos += minutos
        por_disciplina[(dia.get("discipline") or dia.get("sport") or "?")] += minutos
        if _is_quality(dia):
            calidad_prescrita.append({
                "date": dia.get("date"),
                "focus": dia.get("focus"),
                "sport": dia.get("sport"),
            })

    # Los campos van en la RAÍZ del estado de adherencia, no dentro de una
    # clave "summary". Esa clave no existe y nunca existió: la primera versión
    # de este módulo la invento, asi que `completed`, `modified`, `missed` y
    # `executed_hours` salian None en TODAS las semanas y el expediente no
    # registraba una sola hora entrenada. La causa fue probar contra un
    # diccionario inventado en vez de contra el que produce
    # AdherenceReport.to_state_dict() -- que es lo que ahora hacen los tests.
    resumen = adherence or {}
    sesiones = resumen.get("sessions") or []
    # Una sesión del MISMO día todavía no es un incumplimiento: el atleta
    # puede entrenar esta tarde. Es la misma regla que adherence.py aplica.
    # ENTRENADA y CALIDAD HECHA dejaron de ser lo mismo.
    #
    # Una sesión `under_intensity` es de calidad, se entrenó y su pico de
    # pulsaciones no llegó ni una vez a Z4. Cuenta para el volumen y para el
    # día de la semana -- el atleta salió, y decir que ese día falló sería
    # falso. Pero NO cuenta como estímulo de calidad, que es el número con el
    # que el macrociclo decide si progresar la intensidad. Meterla en las dos
    # cosas, o en ninguna, pierde la mitad de la información en cada caso.
    calidad_ok = {s.get("date") for s in sesiones if s.get("status") in ("completed", "modified")}
    evaluadas = {s.get("date") for s in sesiones if s.get("status") != "pending"}

    calidad_hecha = [q for q in calidad_prescrita if q["date"] in calidad_ok]
    # Sólo se cae lo que ya se EVALUÓ. Antes bastaba con no estar pendiente, y
    # una semana recién prescrita -- sin adherencia, o con días que la
    # adherencia todavía no mira -- anotaba TODA su calidad como caída: la del
    # atleta 2 del 2026-09-13 guardó "2026-09-16 VO2max" y "2026-09-20 5K
    # Monterrico" en `quality_missed` el mismo domingo en que se escribieron.
    calidad_perdida = [
        q for q in calidad_prescrita
        if q["date"] in evaluadas and q["date"] not in calidad_ok
    ]

    # Por día de la semana. "Siempre se te cae el jueves" es un hallazgo que
    # sólo puede hacer un sistema que sepa qué mandó Y qué se hizo, y no cabe
    # en un porcentaje semanal: una adherencia del 70% sostenida puede ser
    # setenta por ciento repartido o el mismo día fallando siempre, y son dos
    # problemas distintos con dos soluciones distintas.
    #
    # Sólo cuentan los días ya EVALUADOS: un día pendiente entraría como
    # prescrito y no hecho, y le colgaría un incumplimiento a un entreno que
    # todavía puede hacerse esta tarde.
    #
    # Sesión a sesión, no fecha a fecha (2026-10-22): un día con dos sesiones y una hecha contaba «2 de 2»,
    # porque `hechas` son FECHAS. El lunes 12-oct del atleta 2 (soltura + fuerza, una sola hecha al cerrar la
    # semana) salió «lunes 2 de 2» y su viernes también. Cada sesión de la adherencia es una prescrita.
    fechas_del_plan = {d.get("date") for d in dias}
    por_dia_semana: dict[str, list[int]] = {}
    for sesion in sesiones:
        fecha = sesion.get("date")
        if not fecha or fecha not in fechas_del_plan or sesion.get("status") == "pending":
            continue
        nombre = _weekday(fecha)
        if not nombre:
            continue
        casilla = por_dia_semana.setdefault(nombre, [0, 0])
        casilla[0] += 1
        if sesion.get("status") in ("completed", "modified", "under_intensity"):
            casilla[1] += 1

    # Volumen realmente entrenado, por disciplina. La disciplina la pone el
    # PLAN (una sesión de rehab y una de fuerza son las dos `strength` para
    # Garmin y cosas distintas para un entrenador); los minutos y los km los
    # pone la actividad real.
    disciplina_de = {
        d.get("date"): (d.get("discipline") or d.get("sport"))
        for d in dias if d.get("date")
    }
    ejecutado: dict[str, dict[str, float]] = {}
    for sesion in sesiones:
        minutos, km = sesion.get("actual_minutes"), sesion.get("actual_distance_km")
        if not minutos and not km:
            continue
        clave = disciplina_de.get(sesion.get("date")) or sesion.get("sport") or "?"
        casilla = ejecutado.setdefault(clave, {"hours": 0.0, "km": 0.0})
        casilla["hours"] += float(minutos or 0) / 60
        casilla["km"] += float(km or 0)

    return {
        "ts": datetime.now().isoformat(timespec="seconds"),
        "from": desde,
        "to": hasta,
        "week_number": week.get("week_number"),
        "prescribed_sessions": len(dias),
        "prescribed_hours": round(minutos_prescritos / 60, 2),
        "prescribed_by_discipline": {k: round(v / 60, 2) for k, v in por_disciplina.most_common()},
        "quality_prescribed": len(calidad_prescrita),
        "quality_done": len(calidad_hecha),
        # Las que se cayeron, con su foco: "cuáles" dice más que "cuántas"
        # cuando lo que se busca es un patrón (¿siempre las series? ¿siempre
        # los domingos?).
        "quality_missed": [f"{q['date']} {q['focus']}" for q in calidad_perdida],
        "completed_sessions": resumen.get("completed"),
        "modified_sessions": resumen.get("modified"),
        "missed_sessions": resumen.get("missed"),
        # Entrenadas pero sin el estimulo: el numero que explica una semana
        # con buena adherencia y calidad estancada.
        "under_intensity_sessions": resumen.get("under_intensity"),
        "executed_hours": (
            round(float(resumen["executed_hours"]), 2)
            if resumen.get("executed_hours") is not None else None
        ),
        # La foto de carga de la semana. Se guarda aqui y no en otro sitio
        # porque el expediente ya tiene una linea por semana: anotandolo de
        # paso, la TENDENCIA de ACWR se construye sola, sin una sola llamada
        # extra a Garmin ni a ningun modelo. Es lo que hace posible la grafica.
        "acwr": (load_state or {}).get("own_acwr"),
        "acwr_garmin": (load_state or {}).get("garmin_acwr"),
        # {"jueves": [prescritas, hechas], ...} -- sólo días ya evaluados.
        "by_weekday": por_dia_semana or None,
        # {"carrera": {"hours": 2.1, "km": 18.4}, ...} de lo REALMENTE hecho.
        "executed_by_discipline": {
            k: {"hours": round(v["hours"], 2), "km": round(v["km"], 2)}
            for k, v in ejecutado.items()
        } or None,
        # v1.2.2: la foto del atleta al CERRAR la semana -- predicciones,
        # umbrales, HRV, reparto hecho, deriva y la fase con lo planificado.
        # Es lo que permite medir cómo respondió a cada fase. Ver
        # services/ai/respuesta_a_las_fases.py.
        "marcadores": marcadores or None,
    }


def lo_vivido(week: dict[str, Any], corte: str | None) -> dict[str, Any] | None:
    """La semana que se cierra, sólo con los días anteriores a `corte` (el primer día del plan nuevo).

    Una semana se cierra al generar la siguiente, y hasta ahora se cerraba ENTERA. Si el plan
    nuevo empieza a mitad de ella (el atleta la borró y generó otra, o se rehizo), los días que
    faltaban nunca se vivieron bajo ese plan: el atleta 2 cerró su semana del 21 al 27 de
    septiembre el miércoles 23 con las 7,9 h de la semana entera contra 0,45 h hechas en dos días,
    y con marcadores fechados el 27. Analytics decía "2 h de 11,1 h · 18 %" y el macrociclo leía un
    incumplimiento que no existió (auditoría del 2026-09-24).

    Devuelve la semana recortada, la misma si no hay nada que recortar, o None si no le queda
    ningún día (el plan nuevo empieza el mismo día que ella).
    """
    if not corte:
        return week
    dias = [d for d in (week.get("days") or []) if str(d.get("date") or "")[:10] < corte]
    if len(dias) == len(week.get("days") or []):
        return week
    return {**week, "days": dias} if dias else None


def record_week(storage_dir: Path, week: dict[str, Any], adherence: dict[str, Any] | None,
                load_state: dict[str, Any] | None = None,
                marcadores: dict[str, Any] | None = None) -> dict[str, Any]:
    """Anota una semana en el expediente. Idempotente por ventana de fechas:
    regenerar la misma semana ACTUALIZA su línea en vez de duplicarla."""
    entrada = summarise_week(week, adherence, load_state, marcadores)
    if not entrada["from"]:
        return entrada

    previas = read_all(storage_dir)
    anterior = next((p for p in previas if p.get("from") == entrada["from"]), None)

    if anterior and entrada.get("executed_hours") is None and not entrada.get("quality_done"):
        # Lo EJECUTADO no se desanota. Si esta semana ya tenía datos de
        # ejecución y ahora se vuelve a prescribir sin ellos -- porque el
        # atleta borró la semana y la regeneró, o porque se está reabriendo --
        # se conserva lo que ya había ocurrido: volver a prescribir algo no
        # deshace lo que la persona entrenó.
        #
        # Sin esto, borrar y regenerar una semana ya cumplida la dejaría
        # contando como no ejecutada, y el macrociclo leería un patrón de
        # incumplimiento que nunca existió.
        for campo in ("executed_hours", "completed_sessions", "modified_sessions",
                      "missed_sessions", "under_intensity_sessions",
                      "quality_done", "quality_missed",
                      "acwr", "acwr_garmin", "by_weekday", "executed_by_discipline", "marcadores"):
            # Las caídas sólo existen en una semana que se evaluó. Heredarlas de
            # una que no arrastraba el error viejo: el 2026-09-13 la semana
            # regenerada del atleta 2 copió "2026-09-16 VO2max" y "2026-09-20
            # 5K Monterrico" como caídas de la línea que reemplazaba.
            if campo == "quality_missed" and not _evaluada(anterior):
                continue
            if anterior.get(campo) not in (None, [], 0):
                entrada[campo] = anterior[campo]

    if anterior and not entrada.get("marcadores") and anterior.get("marcadores"):
        # La foto del atleta se toma al CERRAR la semana. Volver a medirla entera días después
        # (server.semana_a_completar) actualiza lo hecho, no la foto.
        entrada["marcadores"] = anterior["marcadores"]

    if anterior:
        # Reescribir el archivo entero es aceptable a esta escala (una línea
        # por semana) y es lo único que evita que regenerar una semana tres
        # veces deje tres versiones contradictorias del mismo periodo.
        actualizadas = [p for p in previas if p.get("from") != entrada["from"]] + [entrada]
        actualizadas.sort(key=lambda p: p.get("from") or "")
        with _write_lock:
            _path(storage_dir).write_text(
                "\n".join(json.dumps(p, ensure_ascii=False, default=str) for p in actualizadas) + "\n",
                encoding="utf-8",
            )
        return entrada

    _append(storage_dir, entrada)
    return entrada


DESCARTADAS_FILE = "track_record.descartadas.jsonl"


def descartar_las_no_vividas(storage_dir: Path, hoy: str) -> int:
    """Al borrar una semana, sus líneas prescritas y nunca medidas salen del expediente.

    El expediente abre la línea de cada semana al generarla. Si el atleta la borra, la línea se
    quedaba para siempre como "prescrita, sin evaluar": el atleta 2 tenía tres (23-sep, 28-sep y
    5-oct) de planes borrados, que el macrociclo leía como semanas pendientes y el panel contaba
    como "en curso" (auditoría del 2026-09-24).

    Sólo las que NO se evaluaron y cuya semana no ha terminado (`to` >= hoy): lo medido no se
    toca, y lo pasado queda como estaba. Nada se borra en silencio: van a
    `track_record.descartadas.jsonl` con la fecha. Devuelve cuántas.
    """
    previas = read_all(storage_dir)
    fuera = [s for s in previas if not _evaluada(s) and str(s.get("to") or "") >= hoy]
    if not fuera:
        return 0
    quedan = [s for s in previas if s not in fuera]
    with _write_lock:
        with open(Path(storage_dir) / DESCARTADAS_FILE, "a", encoding="utf-8") as h:
            for s in fuera:
                h.write(json.dumps({**s, "descartada": hoy, "motivo": "semana borrada sin llegar a medirse"},
                                   ensure_ascii=False, default=str) + "\n")
        _path(storage_dir).write_text(
            "".join(json.dumps(s, ensure_ascii=False, default=str) + "\n" for s in quedan), encoding="utf-8")
    logger.info("Expediente: %d semana(s) borrada(s) sin medir, a %s", len(fuera), DESCARTADAS_FILE)
    return len(fuera)


def _evaluada(semana: dict[str, Any]) -> bool:
    """Si la adherencia ya miró esta semana. Una recién prescrita trae None en
    los tres; una cerrada sin nada hecho trae ceros, que sí son un dato."""
    return any(semana.get(c) is not None for c in ("completed_sessions", "missed_sessions", "executed_hours"))


def _pct(hechas: int | None, de: int | None) -> str:
    if not de:
        return "—"
    return f"{round((hechas or 0) * 100 / de)}%"


def render_track_record(storage_dir: Path, today: date | None = None) -> str:
    """El expediente, listo para el prompt del planificador de temporada.

    Vacío si no hay al menos dos semanas: con una sola no hay patrón que leer,
    y la adherencia de esa semana ya viaja por su propio canal.
    """
    semanas = read_all(storage_dir)
    if len(semanas) < 2:
        return ""

    semanas.sort(key=lambda s: s.get("from") or "")
    mostradas = semanas[-WEEKS_IN_PROMPT:]
    omitidas = len(semanas) - len(mostradas)

    # Las horas, igual que la calidad: sólo las semanas ya evaluadas. El macrociclo del
    # atleta 2 del 2026-09-23 leyó "33.5h prescritas · 2.0h ejecutadas (3%)" sumando dos
    # semanas que todavía no habían empezado, y escribió "escasa ejecución frente a lo
    # prescrito" (auditoría del 2026-09-24).
    horas_prescritas = sum(s.get("prescribed_hours") or 0 for s in mostradas if _evaluada(s))
    horas_hechas = sum(s.get("executed_hours") or 0 for s in mostradas if s.get("executed_hours"))
    sin_evaluar = sum(1 for s in mostradas if not _evaluada(s))
    # La calidad de una semana que todavía no se ha evaluado no es ni hecha ni
    # caída: sumarla al denominador bajaba el cumplimiento de un atleta por
    # sesiones que aún no tocaba hacer.
    calidad_prescrita = sum(s.get("quality_prescribed") or 0 for s in mostradas if _evaluada(s))
    calidad_hecha = sum(s.get("quality_done") or 0 for s in mostradas)

    lineas = [
        "### Expediente: lo que se le prescribió y lo que cumplió",
        "",
        f"{len(mostradas)} semana(s) registrada(s), de {mostradas[0]['from']} a {mostradas[-1]['to']}.",
        "",
        "**Acumulado del periodo**",
        f"- Horas prescritas: **{horas_prescritas:.1f}h** · ejecutadas: "
        + (f"**{horas_hechas:.1f}h** ({_pct(int(horas_hechas), int(horas_prescritas))})"
           if horas_hechas else "sin datos de ejecución"),
        f"- Sesiones de calidad prescritas: **{calidad_prescrita}** · realizadas: "
        f"**{calidad_hecha}** ({_pct(calidad_hecha, calidad_prescrita)})",
    ] + ([f"- {sin_evaluar} semana(s) aún sin evaluar (en curso o por venir): no cuentan en lo de arriba."]
         if sin_evaluar else []) + [
        "",
        "**Semana a semana**",
        "",
        "| Semana | Prescrito | Ejecutado | Calidad | Se cayeron |",
        "|---|---|---|---|---|",
    ]
    for s in mostradas:
        ejecutado = f"{s['executed_hours']:.1f}h" if s.get("executed_hours") is not None else "—"
        perdidas = ", ".join(s.get("quality_missed") or []) or "—"
        calidad = (f"{s.get('quality_done', 0)}/{s.get('quality_prescribed', 0)}"
                   if _evaluada(s) else "sin evaluar")
        lineas.append(
            f"| {s['from']} | {s['prescribed_hours']:.1f}h / {s['prescribed_sessions']} ses. "
            f"| {ejecutado} | {calidad} | {perdidas} |"
        )

    lineas += [
        "",
        "**Cómo leer esto:** son hechos, no un juicio. Un cumplimiento bajo puede significar que el plan "
        "pedía más de lo que la vida de este atleta permite, que atravesó un mes malo, o que las sesiones "
        "que se caen son siempre del mismo tipo o del mismo día. Cruza esto con el cuaderno y el check-in "
        "antes de concluir, y ajusta lo que PRESCRIBES antes que lo que le exiges.",
    ]
    if omitidas:
        lineas.append(f"\n_({omitidas} semana(s) anterior(es) no se muestran; hay {len(semanas)} en total.)_")
    return "\n".join(lineas)


def adherence_global(root: Path) -> dict[str, Any]:
    """Cuántas semanas se PRESCRIBIERON y cuántas se ENTRENARON, en toda la
    instalación y por cuenta.

    Es la métrica de valor de este producto, y es la única que no se puede
    sacar de MySQL: el expediente vive en el disco del AiService, una línea por
    semana cerrada. Todo lo demás que mide el panel -- altas, entradas, planes
    generados -- dice si la gente PRUEBA la app. Esto dice si le SIRVE.

    Un plan generado es curiosidad. Un plan entrenado, y una segunda semana
    después, es el producto funcionando. Si el embudo va bien y esto va mal, el
    problema no está en cómo se entra: está en lo que se entrega.

    Se cuentan sólo semanas CERRADAS -- las que ya tienen ejecución anotada --
    porque la semana en curso todavía no ha ocurrido y meterla hundiría el
    porcentaje por el mero hecho de estar viva.
    """
    root = Path(root)
    por_cuenta: list[dict[str, Any]] = []
    if not root.is_dir():
        return {"accounts": [], "weeks_recorded": 0, "weeks_trained": 0, "prescribed_hours": 0.0,
                "executed_hours": 0.0, "quality_prescribed": 0, "quality_done": 0}

    for carpeta in sorted(root.iterdir()):
        if not carpeta.is_dir() or not _path(carpeta).exists():
            continue
        semanas = read_all(carpeta)
        # Cerrada = ya sabemos qué hizo el atleta con ella.
        cerradas = [s for s in semanas if s.get("executed_hours") is not None]
        entrenadas = [s for s in cerradas if (s.get("executed_hours") or 0) > 0]
        por_cuenta.append({
            "account": carpeta.name,
            "weeks_recorded": len(semanas),
            "weeks_closed": len(cerradas),
            "weeks_trained": len(entrenadas),
            "prescribed_hours": round(sum(float(s.get("prescribed_hours") or 0) for s in cerradas), 1),
            "executed_hours": round(sum(float(s.get("executed_hours") or 0) for s in cerradas), 1),
            "quality_prescribed": sum(int(s.get("quality_prescribed") or 0) for s in cerradas),
            "quality_done": sum(int(s.get("quality_done") or 0) for s in cerradas),
            # Entrenadas sin el estimulo. Una cuenta con buena adherencia y
            # esto alto esta cumpliendo el calendario y no el entrenamiento.
            "under_intensity": sum(int(s.get("under_intensity_sessions") or 0) for s in cerradas),
            "last_week": (semanas[-1].get("from") if semanas else None),
        })

    def suma(campo: str) -> Any:
        return sum(c[campo] for c in por_cuenta)

    return {
        "accounts": por_cuenta,
        "weeks_recorded": suma("weeks_recorded"),
        "weeks_closed": suma("weeks_closed"),
        "weeks_trained": suma("weeks_trained"),
        "prescribed_hours": round(suma("prescribed_hours"), 1),
        "executed_hours": round(suma("executed_hours"), 1),
        "quality_prescribed": suma("quality_prescribed"),
        "quality_done": suma("quality_done"),
        "under_intensity": suma("under_intensity"),
    }


# ---------------------------------------------------------------------------
# El embudo del bucle
# ---------------------------------------------------------------------------

def _dia(iso: Any) -> date | None:
    try:
        return date.fromisoformat(str(iso))
    except (TypeError, ValueError):
        return None


def _ventanas(semanas: list[dict[str, Any]]) -> list[tuple[date, date, dict[str, Any]]]:
    """Las semanas con su ventana ya interpretada, en orden y sin las ilegibles."""
    salida = []
    for s in semanas:
        desde, hasta = _dia(s.get("from")), _dia(s.get("to"))
        if desde and hasta and hasta >= desde:
            salida.append((desde, hasta, s))
    return sorted(salida, key=lambda v: v[0])


def embudo_del_bucle(root: Path, hoy: date | None = None) -> dict[str, Any]:
    """En cuál de los cinco pasos del bucle se cae cada atleta.

    ## Por qué existe

    La beta dejó **37 generaciones pagadas y 0 semanas cerradas**, y el panel no
    sabía decir por qué. Medía el primer paso (generó) y el último (volvió a
    entrar), y nada de los tres del medio -- que es exactamente donde estaría la
    explicación. Sin eso, la única forma de decidir qué construir era adivinar.

    ## La señal que lo hace legible: CONTIGÜIDAD

    Dos entradas del expediente cuyas ventanas se tocan (una empieza el día
    después de que la otra acabe) son una semana **continuada**: el atleta
    avanzó. Dos que se SOLAPAN son una semana **rehecha**: borró y volvió a
    empezar los mismos días.

    Esa distinción es el bucle entero. `week_number` no sirve para verla, porque
    se reinicia al borrar el plan -- de ahí que las cinco semanas de la cuenta
    del fundador estuvieran numeradas "1" las cinco.

    ## Qué NO mide, y por qué se dice

    `subio_al_reloj` sale del registro de subidas, que sólo se escribe cuando la
    subida SALE BIEN. Una cuenta que nunca subió y una que lo intentó y falló se
    ven igual desde aquí. Y `entreno` sólo se conoce en semanas cerradas: la que
    está en curso todavía no ha ocurrido, así que cuenta aparte y no hunde el
    porcentaje por estar viva.
    """
    from services.garmin import upload_ledger

    root = Path(root)
    hoy = hoy or date.today()
    cuentas: list[dict[str, Any]] = []
    if not root.is_dir():
        return {"accounts": [], "generadas": 0, "rehechas": 0, "subidas": 0,
                "entrenadas": 0, "continuadas": 0, "racha_maxima": 0, "en_curso": 0}

    for carpeta in sorted(root.iterdir()):
        if not carpeta.is_dir() or not _path(carpeta).exists():
            continue
        ventanas = _ventanas(read_all(carpeta))
        if not ventanas:
            continue

        try:
            subidas_registradas = upload_ledger.cargar(carpeta)
        except Exception:   # noqa: BLE001 - el embudo nunca puede tumbar el panel
            logger.warning("No se pudo leer el registro de subidas de %s", carpeta.name)
            subidas_registradas = []
        fechas_subidas = {_dia(e.get("date")) for e in subidas_registradas}
        fechas_subidas.discard(None)

        generadas = rehechas = subidas = entrenadas = continuadas = en_curso = 0
        racha = mejor_racha = 0

        for i, (desde, hasta, s) in enumerate(ventanas):
            generadas += 1

            # REHECHA: hay una posterior que pisa estos mismos dias.
            if any(otro_desde <= hasta and otro_hasta >= desde
                   for otro_desde, otro_hasta, _ in ventanas[i + 1:]):
                rehechas += 1

            if any(f and desde <= f <= hasta for f in fechas_subidas):
                subidas += 1

            ejecutadas = s.get("executed_hours")
            if ejecutadas is None:
                # Sin cerrar. Si la ventana ya pasó es que nunca se cerró; si no,
                # sigue viva y no se juzga.
                if hasta >= hoy:
                    en_curso += 1
            elif float(ejecutadas or 0) > 0:
                entrenadas += 1

            # CONTINUADA: la siguiente empieza justo donde ésta acabó.
            siguiente = ventanas[i + 1][0] if i + 1 < len(ventanas) else None
            if siguiente and (siguiente - hasta).days == 1:
                continuadas += 1
                racha += 1
                mejor_racha = max(mejor_racha, racha + 1)
            else:
                racha = 0

        cuentas.append({
            "account": carpeta.name,
            "generadas": generadas,
            "rehechas": rehechas,
            "subidas": subidas,
            "entrenadas": entrenadas,
            "continuadas": continuadas,
            # La OMTM: semanas seguidas sin romper la cadena. Uno significa que
            # generó una y nunca encadenó otra.
            "racha_maxima": mejor_racha or (1 if generadas else 0),
            "en_curso": en_curso,
        })

    def suma(campo: str) -> int:
        return sum(c[campo] for c in cuentas)

    return {
        "accounts": cuentas,
        "generadas": suma("generadas"),
        "rehechas": suma("rehechas"),
        "subidas": suma("subidas"),
        "entrenadas": suma("entrenadas"),
        "continuadas": suma("continuadas"),
        "en_curso": suma("en_curso"),
        "racha_maxima": max([c["racha_maxima"] for c in cuentas], default=0),
        # Cuantas cuentas encadenaron al menos dos semanas: el umbral del plan
        # Lean ("3 de 5 atletas cierran al menos 2 seguidas").
        "cuentas_con_racha": sum(1 for c in cuentas if c["racha_maxima"] >= 2),
        "cuentas": len(cuentas),
    }
