"""Las series que la app dibuja. Sin una sola llamada a un modelo.

## Por qué esto no cuesta nada

Todo lo que hay aquí sale de archivos que la app ya escribe: el expediente
(`track_record.jsonl`), el plan de la semana, la adherencia y el estado de
carga que ya venía de Garmin. No se pide un dato nuevo a Garmin, no se llama a
ningún modelo, y el cálculo es aritmética.

Es deliberado. Una gráfica es presentación, y pedirle a un modelo que resuma
números que ya tenemos exactos sería pagar por perder precisión -- el mismo
razonamiento que sacó al formateador de informes de manos de un LLM en v5
Track 9, y que quitó `zones_table` de la salida del planificador en v6.

## Qué se dibuja, y por qué

No las que quedan bonitas: las que responden a una pregunta que el atleta se
hace de verdad. Y salen de DOS sitios distintos, que es lo que decide cuáles
aparecen desde el primer día y cuáles necesitan semanas:

**Del expediente** (`track_record.jsonl`, una línea por semana):

1. **Lo que te mandé contra lo que hiciste.** La gráfica que ningún plan
   estático puede enseñar, porque un plan estático no sabe qué hiciste.
2. **Las sesiones de calidad que sobrevivieron.** El rodaje suave se hace casi
   siempre; las series no. Separarlas convierte "cumplí el 70%" en una frase
   accionable.
3. **Qué día de la semana se te cae.** Una adherencia del 70% sostenida puede
   ser setenta por ciento repartido o el mismo día fallando siempre, y son dos
   problemas distintos. Sólo se puede ver sabiendo qué se mandó Y qué se hizo.
4. **Cuánto has entrenado de cada deporte.** Horas y kilómetros reales
   acumulados, para el triatleta que quiere ver el volumen por disciplina.

**Del archivo de razonamiento** (`reasoning/<fecha>/`, una carpeta por
generación) — estas son largas desde la PRIMERA vez, porque cada generación ya
traía meses de datos de Garmin que se resumían en prosa y se tiraban:

5. **Tu carga (ACWR) contra su banda.** El número que decide si la semana que
   viene sube o baja.
6. **Cómo se reparte tu carga** entre aeróbico bajo, alto y anaeróbico, cada
   uno contra el rango que Garmin calculó para este atleta.
7. **Tu VO2max en el tiempo.** ~26 puntos de un año llegan de golpe en la
   primera generación (`long_term_vo2_max_trend`).
8. **Tu recuperación**: HRV nocturna contra el rango base PROPIO del atleta, y
   el pulso en reposo. Un punto por día.
9. **Tu predicción de carrera contra tu meta.** La que responde "¿voy a
   llegar?", que es la razón por la que alguien entrena. La predicción la hace
   Garmin; la meta la puso el atleta.

**Y el reparto de la semana en curso**, que sale del plan mismo.

## Lo que este módulo NO hace

No interpreta. Devuelve series y sus umbrales; decir si un ACWR de 1.35 es un
problema para ESTE atleta es una lectura que necesita el cuaderno, el check-in
y la fase de temporada -- y esa la hace el coach, que ya los tiene.
"""

from __future__ import annotations

import json
import logging
from collections import Counter
from datetime import date, datetime
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)

# Las bandas de ACWR que se dibujan de fondo. NO son un umbral de esta app:
# son el rango que la literatura de Gabbett cita como "sweet spot" (0.8-1.3) y
# el >1.5 que se asocia a mayor riesgo. Se dibujan porque un número sin su
# referencia no dice nada, y se etiquetan como lo que son -- una referencia
# publicada, no un veredicto nuestro. `services/ai/load_state.py` documenta por
# qué esta app describe la carga y nunca la usa para decidir sola.
ACWR_SWEET_SPOT = (0.8, 1.3)
ACWR_HIGH = 1.5

# El intervalo que GARMIN dibuja en su propia gráfica "Proporción de carga".
# Se usa cuando la línea es la suya, que es lo correcto: pintar la banda de
# Gabbett bajo una línea calculada con el método exponencial de Garmin
# mezclaría dos métodos, que es justo lo que load_state.py lleva desde v4
# impidiendo. No es una cifra inventada: es la que aparece rotulada como
# "Intervalo óptimo" en Garmin Connect.
ACWR_GARMIN_OPTIMO = (0.8, 1.5)

# Cuántas semanas entran en las gráficas de tendencia. Doce son un trimestre:
# suficiente para ver un patrón, poco para que la gráfica se vuelva ilegible en
# un móvil, que es donde se mira.
WEEKS_SHOWN = 12

_DISCIPLINE_LABELS = {
    "carrera": "Carrera", "run": "Carrera",
    "bici": "Bici", "bike": "Bici",
    "natacion": "Natación", "swim": "Natación",
    "fuerza": "Fuerza", "strength": "Fuerza",
    "rehab": "Rehab", "prehab": "Prehab",
    "movilidad": "Movilidad", "pliometria": "Pliometría",
    "brick": "Brick", "descanso": "Descanso",
}


def _label(key: str) -> str:
    return _DISCIPLINE_LABELS.get((key or "").lower(), (key or "?").capitalize())


def _short_date(iso: str) -> str:
    """'2026-08-27' -> '27 ago'. El eje de una gráfica no cabe en ISO."""
    meses = ["ene", "feb", "mar", "abr", "may", "jun",
             "jul", "ago", "sep", "oct", "nov", "dic"]
    try:
        d = datetime.strptime(iso, "%Y-%m-%d").date()
    except (TypeError, ValueError):
        return iso or ""
    return f"{d.day} {meses[d.month - 1]}"


def _rango_de_la_semana(w: dict[str, Any]) -> str:
    """'15-21 sep' / '26 sep-4 oct': la etiqueta de una barra semanal.

    Sólo el día de inicio («15 SEP, 21 SEP, 26 SEP, 9 OCT») no decía que cada barra era una semana, ni que
    algunas eran de tres días (revisión del 2026-10-21, semanas reales de José). Sin `to`, el inicio.
    """
    desde, hasta = w.get("from", ""), w.get("to", "")
    try:
        a = datetime.strptime(desde, "%Y-%m-%d").date()
        b = datetime.strptime(hasta, "%Y-%m-%d").date()
    except (TypeError, ValueError):
        return _short_date(desde)
    if a == b:
        return _short_date(desde)
    return f"{a.day}-{_short_date(hasta)}" if a.month == b.month else f"{_short_date(desde)}-{_short_date(hasta)}"


def _dias_de_la_semana(w: dict[str, Any]) -> int | None:
    """Cuántos días cubre una «semana» del expediente: la primera de un plan o una rehecha a mitad pueden ser de
    2 o 3 días, y pintadas como semanas enteras se leían como semanas fallidas (revisión del 2026-10-22)."""
    try:
        return (datetime.strptime(w.get("to", ""), "%Y-%m-%d") - datetime.strptime(w.get("from", ""), "%Y-%m-%d")).days + 1
    except (TypeError, ValueError):
        return None


def _axis_labels(fechas: list[str]) -> list[str]:
    """Las etiquetas del eje de una serie, con año cuando hace falta.

    `_short_date` da "9 ago", que es lo correcto para doce semanas de
    expediente y una mentira para una serie de un año: el VO2max real del
    atleta va de 2025-08-09 a 2026-08-28 y el eje decía "9 ago … 28 ago", que
    se lee como tres semanas. Cuando la serie cruza más de diez meses, las
    etiquetas llevan el año.
    """
    validos = []
    for f in fechas:
        try:
            validos.append(datetime.strptime(f, "%Y-%m-%d").date())
        except (TypeError, ValueError):
            validos.append(None)
    reales = [d for d in validos if d]
    largo = bool(reales) and (max(reales) - min(reales)).days > 300
    salida = []
    for f, d in zip(fechas, validos):
        if d and largo:
            # El año entero: «sep 25» se leía como 25 de septiembre (revisión del 2026-10-21).
            salida.append(f"{_short_date(f).split()[1]} {d.year}")
        else:
            salida.append(_short_date(f))
    return salida


def _is_closed(week: dict[str, Any]) -> bool:
    """Si esta semana ya se midió contra lo que el atleta hizo.

    Una semana ABIERTA -- la que está entrenando ahora -- aparecería con cero
    horas hechas y cero sesiones de calidad, y se leería como un
    incumplimiento que todavía no ha ocurrido. La gráfica de calidad hacía
    exactamente eso: la primera semana del atleta salía como "0 de 2 · 0%" el
    día que se generó.

    Y la prueba NO puede ser `executed_hours is not None`, que es lo que usaba
    la de horas: una semana en la que no se entrenó nada tiene cero minutos
    emparejados y por tanto `executed_hours` vacío, así que desaparecería del
    gráfico en vez de dibujarse como lo que fue. Lo que distingue una semana
    cerrada es que su adherencia se EVALUÓ, la haya cumplido o no.
    """
    return any(
        week.get(campo) is not None
        for campo in ("completed_sessions", "modified_sessions", "missed_sessions")
    )


def plan_vs_reality(weeks: list[dict[str, Any]]) -> dict[str, Any]:
    """Horas prescritas contra horas ejecutadas, semana a semana.

    Sólo se incluyen las semanas que YA tienen ejecución medida: una semana
    abierta (la que el atleta está entrenando ahora) aparecería con cero horas
    hechas y se leería como un incumplimiento que no ha ocurrido.
    """
    cerradas = [w for w in weeks if _is_closed(w)]
    if not cerradas:
        return {}
    puntos = [
        {
            "label": _rango_de_la_semana(w),
            "dias": _dias_de_la_semana(w),
            "prescribed": round(float(w.get("prescribed_hours") or 0), 2),
            "executed": round(float(w.get("executed_hours") or 0), 2),
        }
        for w in cerradas[-WEEKS_SHOWN:]
    ]
    prescritas = sum(p["prescribed"] for p in puntos)
    hechas = sum(p["executed"] for p in puntos)
    return {
        "points": puntos,
        "total_prescribed": round(prescritas, 1),
        "total_executed": round(hechas, 1),
        "pct": round(hechas * 100 / prescritas) if prescritas else None,
    }


def quality_compliance(weeks: list[dict[str, Any]]) -> dict[str, Any]:
    """Sesiones de calidad prescritas contra realizadas.

    Aparte del total de horas a propósito: un atleta puede cumplir el 90% de
    sus horas y haberse saltado todas las series, y esas dos semanas no son la
    misma semana ni de lejos.
    """
    cerradas = [w for w in weeks if w.get("quality_prescribed") and _is_closed(w)]
    if not cerradas:
        return {}
    puntos = [
        {
            "label": _rango_de_la_semana(w),
            "dias": _dias_de_la_semana(w),
            "prescribed": int(w.get("quality_prescribed") or 0),
            "done": int(w.get("quality_done") or 0),
        }
        for w in cerradas[-WEEKS_SHOWN:]
    ]
    total_p = sum(p["prescribed"] for p in puntos)
    total_d = sum(p["done"] for p in puntos)
    return {
        "points": puntos,
        "total_prescribed": total_p,
        "total_done": total_d,
        "pct": round(total_d * 100 / total_p) if total_p else None,
    }


def load_trend(weeks: list[dict[str, Any]], load_state: dict[str, Any] | None,
               serie_garmin: list[dict[str, Any]] | None = None) -> dict[str, Any]:
    """La carga aguda/crónica a lo largo del tiempo, con su banda de referencia.

    ## Dos líneas, y cuál manda

    La principal es **la de Garmin**: es el número que el atleta ve en su reloj,
    así que es el único contra el que puede contrastar lo que le decimos. Hasta
    aquí no había serie de esa: `build_light_snapshot` la leía de un atributo
    que no existe en ningún modelo del extractor, así que ninguna foto diaria
    la guardó nunca y la gráfica sólo podía dibujar la nuestra -- una foto por
    generación, o sea dos puntos iguales y una línea plana.

    La nuestra (media móvil 7d ÷ 28d) se sigue dibujando **detrás**, más tenue.
    No se quita: es la que alimenta el análisis, tiene más historia porque se
    calcula de sus actividades, y verla al lado es lo que hace evidente que
    **son métodos distintos**. Lo que no hace es gobernar la gráfica.

    ## La banda

    Es la referencia de ESTA app (0.8–1.3, del trabajo de Gabbett), y se
    etiqueta como tal. Garmin no publica los límites de su propia zona: lo que
    publica es un veredicto por palabra (`acwr_status`: OPTIMAL, LOW, HIGH),
    que viaja aparte. Pintar nuestra banda como si fuera la suya sería
    exactamente la confusión de métodos que `services/ai/load_state.py` lleva
    desde v4 impidiendo.
    """
    nuestros = [
        {"label": _short_date(w.get("from", "")), "acwr": round(float(w["acwr"]), 2)}
        for w in weeks[-WEEKS_SHOWN:]
        if w.get("acwr") is not None
    ]
    actual = (load_state or {}).get("own_acwr")
    if actual is not None and (not nuestros or nuestros[-1]["acwr"] != round(float(actual), 2)):
        nuestros.append({"label": "hoy", "acwr": round(float(actual), 2)})

    crudos = _thin(list(serie_garmin or []), SERIES_POINTS_MAX)
    de_garmin = [
        {"label": etiqueta, "acwr": round(float(p["acwr"]), 2), "status": p.get("status")}
        for etiqueta, p in zip(_axis_labels([p["date"] for p in crudos]), crudos)
    ]
    # El de hoy, que puede ser más fresco que la última foto guardada.
    hoy_garmin = (load_state or {}).get("garmin_acwr")
    if hoy_garmin is not None and (not de_garmin or de_garmin[-1]["acwr"] != round(float(hoy_garmin), 2)):
        de_garmin.append({
            "label": "hoy",
            "acwr": round(float(hoy_garmin), 2),
            "status": (load_state or {}).get("garmin_acwr_status"),
        })

    if not de_garmin and not nuestros:
        return {}

    # Manda la de Garmin cuando hay; si el reloj no la da, la nuestra ocupa su
    # sitio en vez de dejar la tarjeta vacía.
    principal = de_garmin or nuestros
    secundaria = nuestros if de_garmin else []

    return {
        "points": principal,
        "own_points": secundaria,
        "source": "garmin" if de_garmin else "app",
        # La banda que corresponde al método de la línea principal.
        "sweet_spot": list(ACWR_GARMIN_OPTIMO if de_garmin else ACWR_SWEET_SPOT),
        "band_label": "intervalo óptimo de Garmin" if de_garmin else "referencia de esta app",
        "high": ACWR_HIGH,
        "current": principal[-1]["acwr"],
        "garmin_acwr": (load_state or {}).get("garmin_acwr"),
        "garmin_status": (load_state or {}).get("garmin_acwr_status"),
        "own_current": nuestros[-1]["acwr"] if nuestros else None,
        # Las dos cifras usan métodos distintos y las dos son correctas bajo
        # su propia definición. Decirlo aquí evita que la gráfica parezca
        # contradecir al reloj. Ver services/ai/load_state.py.
        "note": (
            "La línea gruesa es el ACWR de tu reloj (media exponencial), el mismo que ves en "
            "Garmin Connect, y la franja es su intervalo óptimo (0,8–1,5). La línea tenue es el "
            "que calcula esta app (media móvil 7d ÷ 28d) para su análisis: es otro método y no "
            "tiene por qué coincidir. Los dos son correctos bajo su propia definición."
        ),
    }


_LOAD_BALANCE_LABELS = {
    "aerobic_low": "Aeróbico bajo",
    "aerobic_high": "Aeróbico alto",
    "anaerobic": "Anaeróbico",
}


def load_balance(load_state: dict[str, Any] | None) -> dict[str, Any]:
    """Cómo se reparte la carga entre los tres sistemas, contra su rango.

    Es la gráfica que explica una queja frecuente y difícil de ver en números:
    *"entreno mucho y no mejoro"*. Casi siempre es todo aeróbico bajo y nada
    de lo otro, o al revés.

    Los rangos NO los pone esta app: son los que Garmin publica para este
    atleta a partir de su propia carga crónica, y llegan ya calculados dentro
    de `get_training_status`. Dibujarlos es enseñar lo que su reloj ya decidió,
    no una opinión nuestra -- la misma regla que `services/ai/load_state.py`
    fija: describir la carga, nunca inventarle un umbral.
    """
    balance = (load_state or {}).get("load_balance") or {}
    puntos = []
    for clave, etiqueta in _LOAD_BALANCE_LABELS.items():
        dato = balance.get(clave) or {}
        carga = dato.get("load")
        if carga is None:
            continue
        puntos.append({
            "label": etiqueta,
            "load": round(float(carga)),
            "target_min": round(float(dato.get("target_min") or 0)),
            "target_max": round(float(dato.get("target_max") or 0)),
            "status": dato.get("status"),
        })
    if not puntos:
        return {}
    return {
        "points": puntos,
        "feedback": (load_state or {}).get("load_balance_feedback"),
        "note": (
            "Los rangos los calcula Garmin a partir de tu propia carga crónica, "
            "no esta app. Estar por debajo de uno no es un error: depende de la "
            "fase de tu temporada."
        ),
    }


def week_by_discipline(plan: dict[str, Any] | None) -> dict[str, Any]:
    """Cómo se reparte la semana que el atleta tiene ahora mismo."""
    semanas = (plan or {}).get("weeks") or []
    if not semanas:
        return {}
    reparto: Counter = Counter()
    for dia in semanas[-1].get("days") or []:
        if dia.get("is_rest"):
            continue
        minutos = float(dia.get("total_duration_minutes") or 0)
        if minutos:
            reparto[_label(dia.get("discipline") or dia.get("sport"))] += minutos
    if not reparto:
        return {}
    total = sum(reparto.values())
    return {
        "points": [
            {"label": k, "minutes": round(v), "pct": round(v * 100 / total)}
            for k, v in reparto.most_common()
        ],
        "total_hours": round(total / 60, 1),
    }


# --------------------------------------------------------------------------
# Series largas: lo que cada generación archivó, unido entre generaciones.
# --------------------------------------------------------------------------

def _reasoning_runs(storage_dir: Path) -> list[tuple[str, Path]]:
    """Las carpetas de razonamiento, de la más antigua a la más nueva.

    El nombre de la carpeta es el sello de tiempo ISO de la generación, así
    que ordenarlas alfabéticamente las ordena cronológicamente y sus primeros
    diez caracteres son la fecha.
    """
    raiz = Path(storage_dir) / "reasoning"
    if not raiz.is_dir():
        return []
    return [(e.name[:10], e) for e in sorted(raiz.iterdir()) if e.is_dir()]


def _read_json(path: Path) -> dict[str, Any]:
    try:
        return json.loads(path.read_text(encoding="utf-8")) or {}
    except (OSError, json.JSONDecodeError):
        return {}


def _series_sources(storage_dir: Path) -> list[tuple[str, Path]]:
    """Todos los archivos de serie, de la más antigua a la más nueva.

    Dos orígenes y en este orden, que importa: primero las generaciones
    (`reasoning/<sello>/fitness_series.json`) y después los refrescos diarios
    (`fitness/<fecha>.json`). Como la unión de más adelante deduplica por fecha
    y gana el último, un refresco de hoy pisa al de la generación de hoy -- que
    es lo correcto: es la lectura más reciente del mismo reloj.

    Los refrescos existen porque las series se archivaban una vez por
    generación y entre semana la pantalla enseñaba números de hace días. Ver
    `services/ai/dashboard_refresh.py`.
    """
    fuentes = [(f, carpeta / "fitness_series.json") for f, carpeta in _reasoning_runs(storage_dir)]
    diarios = Path(storage_dir) / "fitness"
    if diarios.is_dir():
        fuentes += [(p.stem, p) for p in sorted(diarios.glob("*.json"))]
    return sorted(fuentes, key=lambda par: par[0])


def merge_archived_series(storage_dir: Path) -> dict[str, Any]:
    """Une las series de todas las generaciones, deduplicando por fecha.

    Cada generación archiva una foto larga (`fitness_series.json`): ~26 puntos
    de VO2max de un año y hasta 120 días de recuperación. Las corridas se
    solapan mucho -- dos generaciones de la misma semana traen casi los mismos
    días -- así que la unión es por fecha y **gana la más reciente**: es la que
    Garmin recalculó al final, después de que las actividades de ese día se
    hubieran sincronizado.

    Las predicciones de carrera son distintas: no son una serie que Garmin
    guarde, sino un valor de HOY que recalcula contra la forma actual. La
    serie se construye AQUÍ, una entrada por generación, y se lee también del
    `prescription.json` que el archivo ya guardaba antes de que
    `fitness_series.json` existiera -- por eso esa gráfica funciona hacia
    atrás y no espera a la próxima generación.
    """
    vo2max: dict[str, dict[str, Any]] = {}
    recovery: dict[str, dict[str, Any]] = {}
    # El peso. Misma union por fecha que las demas: cada generacion trae su
    # tramo y los dias solapados los gana la lectura mas reciente.
    weight: dict[str, dict[str, Any]] = {}
    # La disponibilidad. Solo la escriben las fotos diarias, porque Garmin no
    # publica su historico: crece un punto por dia hacia delante.
    readiness: dict[str, dict[str, Any]] = {}
    predicciones: dict[str, dict[str, Any]] = {}
    # El ACWR de Garmin, un punto por día. Es el número que el atleta ve en su
    # reloj, así que es el que hace la gráfica interpretable; el nuestro sigue
    # calculándose y se usa para el análisis, pero comparte pantalla sin
    # gobernarla. Ver `load_trend` y services/ai/load_state.py.
    acwr_garmin: dict[str, dict[str, Any]] = {}

    for fecha, archivo in _series_sources(storage_dir):
        serie = _read_json(archivo) if archivo.is_file() else {}
        carpeta = archivo.parent
        # Las fotos diarias lo traen en la raíz; las generaciones, en su
        # `load_state.json`. Los dos son la misma lectura del mismo reloj.
        acwr = serie.get("garmin_acwr")
        estado = serie.get("garmin_acwr_status")
        if acwr is None:
            estado_carga = _read_json(carpeta / "load_state.json")
            acwr = estado_carga.get("garmin_acwr")
            estado = estado_carga.get("garmin_acwr_status")
        if acwr is not None:
            fecha_dato = serie.get("as_of") or fecha[:10]
            acwr_garmin[fecha_dato] = {"date": fecha_dato, "acwr": float(acwr), "status": estado}
        for punto in serie.get("vo2max") or []:
            if punto.get("date"):
                vo2max[punto["date"]] = punto
        for punto in serie.get("recovery") or []:
            if punto.get("date"):
                recovery[punto["date"]] = punto
        for punto in serie.get("weight") or []:
            if punto.get("date"):
                weight[punto["date"]] = punto
        for punto in serie.get("readiness") or []:
            if punto.get("date") and punto.get("value") is not None:
                readiness[punto["date"]] = punto

        # El HISTÓRICO que publica Garmin, cuando lo hay. Es la fuente buena:
        # una sola petición trae meses de pronósticos diarios, mientras que
        # apilar la foto de hoy tarda meses en dibujar una línea. Va primero
        # para que las fotos sueltas de abajo sólo rellenen lo que le falte.
        for entrada in serie.get("race_prediction_history") or []:
            if entrada.get("date") and entrada.get("times"):
                predicciones[entrada["date"]] = dict(entrada["times"])

        # El ACWR de Garmin, día a día.
        for entrada in serie.get("load_ratio_history") or []:
            if entrada.get("date") and entrada.get("acwr") is not None:
                acwr_garmin[entrada["date"]] = {
                    "date": entrada["date"],
                    "acwr": float(entrada["acwr"]),
                    "status": entrada.get("status"),
                }

        # La predicción de esta generación. Del archivo nuevo si está, y si no
        # del `prescription.json` que ya se guardaba desde v6.
        crudas = serie.get("race_predictions")
        if not crudas:
            crudas = _read_json(carpeta / "prescription.json").get("race_predictions") or []
        por_distancia = {
            p.get("distance_key"): p.get("time_seconds")
            for p in crudas if p.get("distance_key") and p.get("time_seconds")
        }
        if por_distancia:
            predicciones[fecha] = por_distancia

    return {
        "vo2max": sorted(vo2max.values(), key=lambda p: p["date"]),
        "recovery": sorted(recovery.values(), key=lambda p: p["date"]),
        "weight": sorted(weight.values(), key=lambda p: p["date"]),
        "readiness": sorted(readiness.values(), key=lambda p: p["date"]),
        "race_predictions": [
            {"date": f, "times": t} for f, t in sorted(predicciones.items())
        ],
        "garmin_acwr": sorted(acwr_garmin.values(), key=lambda p: p["date"]),
    }


def _thin(puntos: list[dict[str, Any]], maximo: int) -> list[dict[str, Any]]:
    """Reduce una serie larga a `maximo` puntos, conservando SIEMPRE el último.

    Un año de HRV diaria son 365 puntos en 300 píxeles de móvil: se convierte
    en una mancha. Se muestrea de forma uniforme y se ancla el final, porque el
    valor de hoy es el que el atleta viene a buscar.
    """
    if len(puntos) <= maximo:
        return puntos
    paso = len(puntos) / maximo
    muestra = [puntos[int(i * paso)] for i in range(maximo)]
    muestra[-1] = puntos[-1]
    return muestra


# Cuántos puntos entran como máximo en una serie larga.
SERIES_POINTS_MAX = 40


def fitness_trend(vo2max: list[dict[str, Any]]) -> dict[str, Any]:
    """VO2max en el tiempo. La métrica que todo atleta mira.

    Se dibuja con su rango real y no desde cero: la variación útil de un
    VO2max son dos o tres puntos, y un eje que empieza en cero los aplasta
    hasta hacerlos invisibles. El eje lleva sus dos extremos escritos, para que
    quede claro que está recortado.
    """
    puntos = [p for p in vo2max if p.get("value")]
    if len(puntos) < 2:
        return {}
    muestra = _thin(puntos, SERIES_POINTS_MAX)
    valores = [p["value"] for p in muestra]
    etiquetas = _axis_labels([p["date"] for p in muestra])
    return {
        "points": [{"label": e, "value": p["value"]} for e, p in zip(etiquetas, muestra)],
        "current": puntos[-1]["value"],
        "first": puntos[0]["value"],
        "change": round(puntos[-1]["value"] - puntos[0]["value"], 1),
        "first_date": str(puntos[0].get("date") or "")[:10],  # «+7 desde …»: sin el periodo, el +7 no decía nada
        "min": min(valores),
        "max": max(valores),
        "unit": "ml/kg/min",
        "note": (
            "Lo estima tu reloj a partir de tus carreras: no es una prueba de laboratorio, y se "
            "mueve también con el calor, la altitud y la calidad de la señal de pulso."
        ),
    }


def recovery_trend(recovery: list[dict[str, Any]],
                   baseline: dict[str, Any] | None = None) -> dict[str, Any]:
    """HRV nocturna y pulso en reposo, un punto por noche.

    Dos series con escalas MUY distintas (ms contra ppm), así que se dibujan
    una debajo de la otra y cada una con su propio eje. Superponerlas en un
    solo eje dejaría una de las dos plana para siempre.

    La banda de la HRV es el rango base PROPIO de este atleta, el que Garmin
    calcula contra sus últimas semanas. Nunca un rango general: fuera de este
    atleta esos números no significan nada, que es exactamente lo que
    `services/ai/recovery_state.py` lleva desde v4 impidiendo que el
    planificador olvide.
    """
    hrv = [p for p in recovery if p.get("hrv")]
    rhr = [p for p in recovery if p.get("rhr")]
    if len(hrv) < 2 and len(rhr) < 2:
        return {}

    base = baseline or {}
    salida: dict[str, Any] = {
        "note": (
            "La banda verde es TU rango normal, el que Garmin calcula contra tus propias semanas: "
            "no es una zona óptima general y no significa nada para otra persona. Una noche fuera "
            "de rango tampoco dice gran cosa por sí sola; lo que informa es la tendencia."
        ),
    }
    if len(hrv) >= 2:
        muestra = _thin(hrv, SERIES_POINTS_MAX)
        salida["hrv"] = {
            "points": [{"label": e, "value": p["hrv"]}
                       for e, p in zip(_axis_labels([q["date"] for q in muestra]), muestra)],
            "current": hrv[-1]["hrv"],
            "band": (
                [base["hrv_baseline_low"], base["hrv_baseline_high"]]
                if base.get("hrv_baseline_low") and base.get("hrv_baseline_high") else None
            ),
            "status": base.get("hrv_status"),
            "unit": "ms",
        }
    if len(rhr) >= 2:
        muestra = _thin(rhr, SERIES_POINTS_MAX)
        salida["rhr"] = {
            "points": [{"label": e, "value": p["rhr"]}
                       for e, p in zip(_axis_labels([q["date"] for q in muestra]), muestra)],
            "current": rhr[-1]["rhr"],
            "unit": "ppm",
        }
    return salida


def _serie_simple(puntos: list[dict[str, Any]], clave: str, unidad: str,
                  decimales: int = 1) -> dict[str, Any]:
    """Una serie de un solo valor por día, lista para dibujar.

    Se exigen DOS puntos como mínimo, igual que el resto: un eje con un punto
    no es una tendencia, y ocupa el sitio de algo que sí informa.

    Devuelve también el promedio de los últimos siete registros. Es lo que hace
    legible un dato ruidoso: una noche de 5 h no dice nada, siete seguidas sí.
    """
    validos = [p for p in puntos if p.get(clave) is not None]
    if len(validos) < 2:
        return {}
    muestra = _thin(validos, SERIES_POINTS_MAX)
    ultimos = [float(p[clave]) for p in validos[-7:]]
    return {
        "points": [{"label": e, "value": p[clave]}
                   for e, p in zip(_axis_labels([q["date"] for q in muestra]), muestra)],
        "current": round(float(validos[-1][clave]), decimales),
        "avg7": round(sum(ultimos) / len(ultimos), decimales),
        "unit": unidad,
        "days": len(validos),
    }


def sleep_trend(recovery: list[dict[str, Any]]) -> dict[str, Any]:
    """Horas dormidas por noche, y la puntuación de sueño de Garmin.

    Los dos datos llegaban en el mismo objeto que la HRV desde el principio --
    `sleep.duration.total` y `sleep.quality.overall_score` -- y la puntuación
    incluso se archivaba ya en `fitness_series.json`. Ninguno se dibujaba.

    Es la gráfica que explica a las otras: un pulso en reposo alto y una HRV
    baja casi siempre tienen aquí su causa, y sin esto el atleta ve el efecto
    sin la causa.

    No se dice cuántas horas "debería" dormir. El rango recomendado depende de
    la persona y esta app no lo mide -- misma regla que con el ACWR: describir
    lo que hay, no inventar un umbral.
    """
    horas = _serie_simple(recovery, "sleep_hours", "h", decimales=1)
    puntuacion = _serie_simple(recovery, "sleep_score", "/100", decimales=0)
    if not horas and not puntuacion:
        return {}
    salida: dict[str, Any] = {
        "note": (
            "Tus propias noches, tal como las registró el reloj. No se marca un mínimo "
            "recomendado: cuánto necesitas dormir depende de ti y esto no lo mide. Lo que "
            "informa es la media de los últimos días y hacia dónde va."
        ),
    }
    if horas:
        salida["hours"] = horas
    if puntuacion:
        salida["score"] = puntuacion
    return salida


def stress_trend(recovery: list[dict[str, Any]]) -> dict[str, Any]:
    """El estrés medio diario que mide Garmin (0-100).

    Puesto al lado de la carga contesta una pregunta que el entrenamiento solo
    no puede: si una mala racha viene de entrenar de más o de la vida de
    alrededor. Dos atletas con el mismo ACWR y estrés de 25 y de 70 no están
    en la misma situación, y el plan no debería tratarlos igual.

    Las bandas son las que publica Garmin en su propia app (reposo / bajo /
    medio / alto), no un criterio de esta app.
    """
    serie = _serie_simple(recovery, "stress", "", decimales=0)
    if not serie:
        return {}
    serie["bands"] = [
        {"label": "reposo", "max": 25},
        {"label": "bajo", "max": 50},
        {"label": "medio", "max": 75},
        {"label": "alto", "max": 100},
    ]
    serie["note"] = (
        "Los tramos (reposo / bajo / medio / alto) son los que usa Garmin en su propia app, "
        "no un criterio de aquí. Mirado junto a tu carga dice si una mala racha viene del "
        "entrenamiento o de lo demás."
    )
    return serie


def weight_trend(weight: list[dict[str, Any]]) -> dict[str, Any]:
    """El peso, tal como lo registró la báscula.

    Se dibuja la serie completa y el promedio de los últimos siete registros,
    porque el peso diario oscila un kilo por hidratación y comida: leer la
    tendencia en el dato suelto lleva a conclusiones que no están.

    No hay objetivo ni rango "ideal". Esta app no sabe cuánto debe pesar nadie,
    y ponerle una línea de meta a un atleta sería inventarle un criterio
    clínico -- justo lo que el resto de este módulo lleva evitando.
    """
    serie = _serie_simple(weight, "value", "kg", decimales=2)
    if not serie:
        return {}
    valores = [float(p["value"]) for p in weight if p.get("value") is not None]
    if valores:
        serie["min"] = round(min(valores), 2)
        serie["max"] = round(max(valores), 2)
        serie["change"] = round(valores[-1] - valores[0], 2)
    serie["note"] = (
        "El peso diario oscila casi un kilo por hidratación y comida, así que lo que informa "
        "es la media de los últimos días, no la última pesada. No se marca ningún peso "
        "objetivo: eso no lo decide una app."
    )
    return serie


def readiness_trend(readiness: list[dict[str, Any]]) -> dict[str, Any]:
    """La puntuación diaria de disponibilidad de Garmin (0-100).

    A diferencia de las demás, esta serie NO se puede reconstruir hacia atrás:
    Garmin no publica un histórico de disponibilidad como sí hace con el VO2max
    o la ratio de carga. Se acumula hacia delante, un punto por refresco
    diario, así que arranca vacía y la gráfica aparece sola cuando haya días
    suficientes. Es honesto que tarde: la alternativa sería inventar el pasado.
    """
    serie = _serie_simple(readiness, "value", "/100", decimales=0)
    if not serie:
        return {}
    serie["note"] = (
        "La disponibilidad que tu reloj calcula cada mañana. Esta serie sólo crece hacia "
        "delante — Garmin no publica su histórico — así que se va llenando día a día."
    )
    return serie


def race_goal_gap(series: list[dict[str, Any]], competitions: list[dict[str, Any]] | None,
                  today: date | None = None) -> dict[str, Any]:
    """La predicción de Garmin contra la meta que el atleta declaró.

    Es la pregunta por la que alguien entrena -- *voy a llegar o no* -- y la
    única gráfica aquí que junta un dato de Garmin con una decisión del atleta.

    Ni el uno ni la otra se tocan: se dibuja la predicción tal cual la publica
    el reloj y la meta tal cual la escribió la persona. Que la brecha se cierre
    o no es un hecho; si la meta es alcanzable lo decide el planificador de
    temporada, que tiene el contexto entero. Ver `services/ai/goal_realism.py`
    para por qué esto NO emite un veredicto.
    """
    from services.ai.goal_realism import (
        format_seconds,
        infer_distance,
        parse_time_to_seconds,
    )

    today = today or date.today()
    if not series:
        return {}

    # La competición más próxima que no haya pasado, con meta y con una
    # distancia para la que Garmin publique predicción.
    candidatas = []
    for competicion in (competitions or []):
        clave, _origen = infer_distance(competicion)
        objetivo = parse_time_to_seconds(competicion.get("target_time"), clave)
        fecha = str(competicion.get("date") or "")[:10]
        if objetivo and clave and fecha >= today.isoformat():
            candidatas.append((fecha, competicion, objetivo, clave))
    if not candidatas:
        return {}
    fecha_carrera, competicion, objetivo, clave = min(candidatas, key=lambda c: c[0])

    con_dato = [e for e in series if (e.get("times") or {}).get(clave)]
    # Seis meses de pronóstico diario son ~180 puntos en 300 píxeles. Se
    # muestrea igual que el resto de las series largas, anclando SIEMPRE el
    # último: la lectura de hoy es la que el atleta viene a buscar. Ver _thin.
    con_dato = _thin(con_dato, SERIES_POINTS_MAX)
    puntos = [
        {"label": etiqueta, "value": e["times"][clave]}
        for etiqueta, e in zip(_axis_labels([e["date"] for e in con_dato]), con_dato)
    ]
    if not puntos:
        return {}

    actual = puntos[-1]["value"]
    # Cuánto ha recortado desde la primera lectura de esta serie. Es LA
    # pregunta de esta gráfica -- no "¿cuánto me falta?" sino "¿me estoy
    # acercando?" -- y con dos puntos iguales no hay respuesta todavía, así
    # que se dice que no la hay en vez de enseñar un cero que parece
    # estancamiento.
    primero = puntos[0]["value"]
    avance = round(primero - actual) if len(puntos) > 1 and primero != actual else None
    return {
        "points": puntos,
        "first_seconds": primero,
        "progress_seconds": avance,
        "progress_clock": format_seconds(abs(avance)) if avance else None,
        "goal_seconds": objetivo,
        "goal_clock": format_seconds(objetivo),
        "current_seconds": actual,
        "current_clock": format_seconds(actual),
        "gap_seconds": round(actual - objetivo),
        "gap_clock": format_seconds(abs(actual - objetivo)),
        "reached": actual <= objetivo,
        "distance_key": clave,
        "race_name": competicion.get("name") or "tu carrera",
        "race_date": fecha_carrera,
        "note": (
            "La línea es lo que tu reloj predice HOY para esa distancia con tu forma actual; la "
            "raya es tu meta. Una predicción no es un pronóstico del día de carrera: no sabe del "
            "recorrido, del calor ni de cómo llegues de descansado."
        ),
    }


_WEEKDAY_ORDER = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]


def _plural_del_dia(dia: str) -> str:
    return dia + "s" if dia.endswith("o") else dia


MUESTRA_MINIMA_DEL_DIA = 3


def weekday_adherence(weeks: list[dict[str, Any]]) -> dict[str, Any]:
    """Qué día de la semana se cae el entreno.

    Un porcentaje semanal no distingue un 70% repartido de un 70% que es
    siempre el mismo día fallando, y son dos problemas distintos: el primero
    dice que el plan pide más de lo que cabe, el segundo que el jueves de esta persona
    no está libre y el plan tiene que dejar de fingir que lo está.
    """
    acumulado: dict[str, list[int]] = {}
    for semana in weeks:
        for dia, cuenta in (semana.get("by_weekday") or {}).items():
            if not isinstance(cuenta, (list, tuple)) or len(cuenta) < 2:
                continue
            casilla = acumulado.setdefault(dia, [0, 0])
            casilla[0] += int(cuenta[0] or 0)
            casilla[1] += int(cuenta[1] or 0)

    # Con menos de cuatro sesiones evaluadas esto es la adherencia de una
    # semana contada de otra forma: no hay patrón que enseñar todavía.
    if sum(p for p, _ in acumulado.values()) < 4:
        return {}

    puntos = [
        {
            "label": dia.capitalize()[:3],
            "full": dia,
            "prescribed": acumulado[dia][0],
            "done": acumulado[dia][1],
            "pct": round(acumulado[dia][1] * 100 / acumulado[dia][0]) if acumulado[dia][0] else None,
        }
        for dia in _WEEKDAY_ORDER if dia in acumulado
    ]
    # Con 3 sesiones o más (revisión del 2026-10-22): «los sábados cumples menos» salía de 1 de 2.
    flojos = [p for p in puntos if p["prescribed"] >= MUESTRA_MINIMA_DEL_DIA and (p["pct"] or 0) < 60]
    return {
        "points": puntos,
        "weakest": min(flojos, key=lambda p: p["pct"])["full"] if flojos else None,
        # El chat es de sólo lectura: "díselo al coach y moverá la sesión" prometía algo que no
        # hace (auditoría del 2026-09-24). Lo que lo arregla es su tabla de días.
        # Sólo con un día flojo, y sin suponer la causa (revisión del 2026-10-21): salía siempre, decía que
        # «suele ser que ese día no está libre» y mandaba a quitarlo, también a quien falla por otra cosa.
        "note": (
            # «Los sábados», no «Los sábado»: sábado y domingo llevan plural; los demás ya acaban en s.
            # Con su muestra: «1 de 3» dice cuánto pesa el consejo, que sin ella sonaba a certeza.
            f"Los {_plural_del_dia(min(flojos, key=lambda p: p['pct'])['full'])} cumples menos "
            f"({min(flojos, key=lambda p: p['pct'])['done']} de {min(flojos, key=lambda p: p['pct'])['prescribed']} sesiones). "
            "Si ese día no te va bien, cámbialo en tus días de entrenamiento."
        ) if flojos else None,
    }


# Disciplinas sin kilómetros. La fuerza puede llegar con distancia desde Garmin (una sesión de gimnasio
# emparejada con una actividad «otra» que la tenía): el Inicio de José enseñaba «Fuerza 10 km» el
# 2026-10-21. Un km de fuerza no significa nada y, sumado al total, lo infla.
_SIN_KM = {"fuerza", "strength", "movilidad", "pliometria", "rehab", "prehab"}


def _km(disciplina: str, valores: dict | None) -> float:
    return 0.0 if str(disciplina).lower() in _SIN_KM else float((valores or {}).get("km") or 0)


def last_week(weeks: list[dict[str, Any]]) -> dict[str, Any]:
    """La última semana CERRADA: horas y kilómetros hechos, contra lo prescrito.

    Es lo que el Inicio enseña como «Tu progreso» (Beta 3.0, F4): un número que el atleta
    entiende sin saber qué es un ACWR. Una semana abierta no cuenta (ver `_is_closed`):
    saldría con cero horas hechas el día que se generó.

    Los kilómetros van POR DEPORTE y sin sumar (ver `volume_by_sport`): la semana real del
    atleta 2 del 26-sep al 4-oct daba "43,6 km" juntando 30,7 de bici, 6,5 nadando y 6,4
    corriendo, que no son el mismo entrenamiento. Donde no hay km (fuerza), no se dice.
    """
    cerradas = [w for w in weeks if _is_closed(w)]
    if not cerradas:
        return {}
    w = cerradas[-1]
    reparto = w.get("executed_by_discipline") or {}
    km = {_label(d): round(_km(d, v), 1) for d, v in reparto.items()}
    return {
        "from": w.get("from"),
        "to": w.get("to"),
        "hours": round(float(w.get("executed_hours") or 0), 1),
        "prescribed_hours": round(float(w.get("prescribed_hours") or 0), 1),
        "km_by_sport": dict(sorted(((k, v) for k, v in km.items() if v > 0), key=lambda kv: -kv[1])),
    }


def volume_by_sport(weeks: list[dict[str, Any]]) -> dict[str, Any]:
    """Horas y kilómetros REALMENTE entrenados, acumulados por disciplina.

    Los kilómetros se enseñan sólo donde existen: no hay km en una sesión de
    fuerza, y una barra de cero kilómetros al lado de las de carrera se leería
    como que esa semana no se hizo nada de fuerza. Las horas están siempre, y
    son la única unidad que compara de verdad un deporte con otro -- 40 km de
    bici y 40 km de carrera no son el mismo entrenamiento ni de lejos.
    """
    acumulado: dict[str, dict[str, float]] = {}
    semanas_con_datos = 0
    for semana in weeks[-WEEKS_SHOWN:]:
        reparto = semana.get("executed_by_discipline") or {}
        if not reparto:
            continue
        semanas_con_datos += 1
        for disciplina, valores in reparto.items():
            casilla = acumulado.setdefault(_label(disciplina), {"hours": 0.0, "km": 0.0})
            casilla["hours"] += float((valores or {}).get("hours") or 0)
            casilla["km"] += _km(disciplina, valores)
    if not acumulado:
        return {}

    total_horas = sum(v["hours"] for v in acumulado.values())
    puntos = sorted(
        (
            {"label": k, "hours": round(v["hours"], 1), "km": round(v["km"], 1),
             "pct": round(v["hours"] * 100 / total_horas) if total_horas else 0}
            for k, v in acumulado.items()
        ),
        key=lambda p: p["hours"], reverse=True,
    )
    return {
        "points": puntos,
        "total_hours": round(total_horas, 1),
        "total_km": round(sum(v["km"] for v in acumulado.values()), 1),
        "weeks": semanas_con_datos,
    }

def _latest_load_state(storage_dir: Path) -> dict[str, Any]:
    """El estado de carga de la última generación.

    Sale del ARCHIVO DE RAZONAMIENTO y no de `summary.json`, que es donde la
    primera versión lo buscó: `summary.json` nunca ha tenido una clave
    `load_state`, así que la gráfica de carga leía `{}` y no podía aparecer
    nunca. El dato sí existe, y está en `reasoning/<fecha>/load_state.json`
    desde v6.
    """
    raiz = Path(storage_dir) / "reasoning"
    if not raiz.is_dir():
        return {}
    for entrada in sorted(raiz.iterdir(), reverse=True):
        archivo = entrada / "load_state.json"
        if archivo.is_file():
            try:
                return json.loads(archivo.read_text(encoding="utf-8")) or {}
            except (OSError, json.JSONDecodeError):
                continue
    return {}


def _latest_recovery(storage_dir: Path) -> dict[str, Any]:
    """El rango base de HRV de la última generación, para la banda personal."""
    for _fecha, carpeta in reversed(_reasoning_runs(storage_dir)):
        datos = _read_json(carpeta / "recovery.json")
        if datos:
            return datos
    return {}


def _progreso(weeks, checkins, strength_logs, profile, vo2max, today) -> dict[str, Any]:
    from services.ai import progreso

    lesiones = (profile or {}).get("current_injuries") or []
    return progreso.calcular(weeks, checkins=checkins, lesiones=lesiones if isinstance(lesiones, list) else [],
                             registros_de_fuerza=strength_logs, perfil=profile, vo2max=vo2max, hoy=today)


def build_charts(storage_dir: Path, profile: dict[str, Any] | None = None,
                 today: date | None = None, checkins: list[dict] | None = None,
                 strength_logs: list[dict] | None = None) -> dict[str, Any]:
    """Todas las series de un atleta, listas para dibujar.

    Una gráfica sin datos suficientes NO se devuelve, en vez de devolverse
    vacía: un eje con un solo punto o una barra sola no informan de nada y
    ocupan el sitio de algo que sí. La única excepción deliberada es la carga,
    que con un solo valor sigue siendo legible porque se dibuja contra una
    banda de referencia publicada -- el front la pinta como un medidor y no
    como una línea de un punto.

    `profile` sólo se usa para la meta de carrera: es lo único de estas
    gráficas que no sale de un archivo del atleta sino de una decisión suya.
    """
    from services.ai.track_record import read_all

    storage_dir = Path(storage_dir)
    weeks = sorted(read_all(storage_dir), key=lambda w: w.get("from") or "")

    try:
        plan = json.loads((storage_dir / "weekly_plan.json").read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        plan = {}

    load_state = _latest_load_state(storage_dir)
    series = merge_archived_series(storage_dir)
    competitions = (profile or {}).get("competitions") or []

    charts = {
        "plan_vs_reality": plan_vs_reality(weeks),
        "quality": quality_compliance(weeks),
        "weekday": weekday_adherence(weeks),
        "load": load_trend(weeks, load_state, series.get("garmin_acwr")),
        "balance": load_balance(load_state),
        "race_goal": race_goal_gap(series["race_predictions"], competitions, today=today),
        "fitness": fitness_trend(series["vo2max"]),
        "recovery": recovery_trend(series["recovery"], _latest_recovery(storage_dir)),
        # Las cuatro que faltaban. Los datos ya se descargaban en cada
        # generacion y ya llegaban a los expertos; lo que no habia era forma
        # de que el atleta los viera.
        "sleep": sleep_trend(series["recovery"]),
        "stress": stress_trend(series["recovery"]),
        "weight": weight_trend(series["weight"]),
        "readiness": readiness_trend(series["readiness"]),
        "volume": volume_by_sport(weeks),
        "last_week": last_week(weeks),
        "discipline": week_by_discipline(plan),
        # Racha, nivel, medallas y récords (Beta 3.0, F8). Los check-in y el registro de fuerza
        # vienen de MySQL (los manda PHP): una molestia declarada congela la racha.
        "progreso": _progreso(weeks, checkins, strength_logs, profile, series.get("vo2max"), today),
    }
    return {
        "charts": {k: v for k, v in charts.items() if v},
        "weeks_recorded": len(weeks),
    }
