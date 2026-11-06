"""Cómo reparte el atleta su intensidad, medido POR SESIÓN con sus propios umbrales.

## Por qué existe

Hasta la v1.2.2 la palabra «polarizado» no aparecía en ningún prompt de este
repositorio. Había techo de CARGA y clases de intensidad por sesión, pero nadie
contaba cuánto del mes pasado fue suave, cuánto entre umbrales y cuánto por
encima. Sin esa cuenta nada impedía que un plan se instalara en un reparto que
nadie eligió, o que metiera calidad porque «la semana debería tener una» -- que
es lo que el atleta 2 vio el 2026-09-11: un VO2max en una mini-semana.

## El modelo de 3 zonas, y dónde se anclan

Es el de la literatura (y el del artículo de Aixsurge que el atleta 2 trajo el
2026-09-13): zona 1 por debajo del primer umbral de lactato, zona 2 entre los dos,
zona 3 por encima del segundo. Con las zonas del reloj se anclan así:

- **Primer umbral** = el techo de la Z2 de Garmin de ese deporte (152 ppm en la
  carrera del atleta 2, un 86 % de su umbral de lactato).
- **Segundo umbral** = el umbral de lactato del atleta en CARRERA (176 ppm), que
  es lo que Garmin mide. En bici y natación, donde no hay uno propio, el suelo de
  la Z5 de ese deporte.

**La primera versión de este módulo lo hacía mal y lo decía peor.** Llamaba
"por encima del segundo umbral" a tocar Z4 -- y la Z4 del atleta 2 empieza en
167, nueve pulsaciones por DEBAJO de su umbral. Todo el trabajo de umbral contaba
como alta. En seis meses reales: 21 carreras "altas" que con las 3 zonas bien
ancladas son 12, y 21 nados que son 2.

## Por qué el ritmo también vota, en carrera

Porque el pulso llega tarde en las series cortas, y anclarlo bien no lo arregla:
con sólo el pulso, los intervalos del atleta 2 del 3, 10, 22 y 29 de julio y su
VO2max del 2 de septiembre salían SUAVES -- series a 4:37-5:07/km, más rápido que
su ritmo de 5K, con el pulso medio de las repeticiones en 135-158. Con el ritmo
en llano contra su umbral (ver services/ai/ritmo_de_tramos.py) vuelven a alta, y
lo que el pulso sí veía bien -- el trail de Panajachel, las carreras -- se queda.

La sesión se queda con la señal más dura de las dos, y el bloque dice cuántas
sesiones altas lo fueron sólo por el ritmo: ese es el rasgo de un atleta cuyo
pulso va por detrás de su esfuerzo, y cambia cómo hay que leerle.

En bici el ritmo no vota nunca (lo mueven el viento y la pendiente). La potencia
contra el FTP votaría igual que el ritmo, pero no está escrita: no hay todavía una
cuenta real con potenciómetro contra la que verificarla.

## Lo que ya no cuenta como duro, con nombre

- **Progresivos de 30"** (seis sesiones del atleta 2 en abril, hasta 4:18/km):
  tramos demasiado cortos para decir nada de zona 3. Son trabajo neuromuscular,
  que el esquema del plan ya cuenta como suave.
- **Tiradas largas con un pico suelto** (141' el 31-may y 92' el 14-jun, a
  8:00-9:44/km con el pulso medio en 134-143 y picos de 165-172).

## Por sesión, y también por tiempo

La literatura cuenta de las dos formas: por TIEMPO en zona (Stöggl & Sperlich
2014; el caso de Ainhoa Murúa, 7.ª en Londres 2012) y por el objetivo de cada
sesión. Aquí manda la sesión porque el plan se escribe en sesiones y porque el
pulso medio de un tramo subestima la zona 3 en series cortas; el reparto por
tiempo se da también, dicho como lo que es. No se comparan entre sí.

Los cortes -- 15' entre umbrales para "umbral", 2' en llano más rápido que el
umbral para "alta" -- salen de las sesiones reales: las suaves acumulan pocos
minutos entre umbrales, y las de umbral o más, veinte o más.

## Lo que NO hace, a propósito

- **No prescribe.** Describe lo que el atleta ha hecho. No hay un reparto
  correcto para todos y ningún número de aquí es una cuota.
- **No inventa zonas.** Una sesión de un deporte sin zonas del reloj se abstiene;
  nunca se le aplican las de otro deporte (`GarminHrZones.for_sport` sí cae a
  otro deporte, y por eso aquí no se usa).
- **Sin muestras no hay número.** Con menos de `MIN_SESIONES` clasificadas
  devuelve None, y un atleta sin zonas recibe cero tokens sobre esto.
"""

from __future__ import annotations

import logging
from datetime import date, timedelta
from typing import Any

from services.ai.load_state import _canonical_sport
from services.ai.prescription import _PACE_CLASS_MULTIPLIERS
from services.ai.ritmo_de_tramos import en_cinta, segundos_entre
from services.ai.weekly_load import _fecha, _minutos
from services.garmin.extraction_windows import CHRONIC_LOAD_WINDOW_DAYS

logger = logging.getLogger(__name__)

DEPORTES_DE_RESISTENCIA = ("run", "bike", "swim")

# Minutos entre umbrales para que una sesión cuente como de umbral.
MIN_MINUTOS_UMBRAL = 15
# Un tramo con el pulso medio en zona 3 cuenta desde un minuto: menos es un pico.
MIN_SEGUNDOS_TRAMO_EN_ZONA_3 = 60
# En carrera: segundos en llano más rápido que el umbral para "alta", y entre
# umbrales para "umbral". El 0,98 deja fuera el trote que roza el umbral.
SEGUNDOS_ALTA_POR_RITMO = 120
SEGUNDOS_UMBRAL_POR_RITMO = MIN_MINUTOS_UMBRAL * 60
_MARGEN_BAJO_UMBRAL = 0.98
# Sin un primer umbral de ritmo, el borde lento de la banda de maratón de la
# prescripción: el ritmo de maratón de un amateur cae junto a su primer umbral.
_LT1_SOBRE_UMBRAL = _PACE_CLASS_MULTIPLIERS["maraton"][1]

# Con menos sesiones clasificadas no hay reparto que describir.
MIN_SESIONES = 4
# Desde cuántas sesiones altas "sólo por ritmo" se describe el rasgo.
MIN_ALTAS_POR_RITMO = 2

_NOMBRE = {"run": "carrera", "bike": "bici", "swim": "natación"}
_ORDEN = {"suave": 0, "umbral": 1, "alta": 2}

# Las clases del esquema, en los tres cubos, SÓLO para el lint de la semana
# planificada (la medición del historial clasifica por pulso y ritmo, no por
# clase). `tempo_threshold` vive entre umbrales; lo que busca pasar el segundo es
# alta. `neuromuscular` (progresivos, técnica) es suave. `strength_freeform`,
# `rest` y `other` no son sesiones de resistencia y no cuentan.
#
# `race_day` tampoco cuenta, y estuvo contando. Primera semana real con el
# lint (atleta 2, 2026-09-13): afinamiento de manual hacia el 5K Monterrico --
# un 6x400 el martes, progresivos el viernes, descanso el sábado, carrera el
# domingo -- y el lint avisó "2 sesiones duras de 5, el techo permite 1".
# La segunda era la CARRERA. El techo de una fase sirve para decidir
# entrenamientos; la carrera no es una decisión contra ese techo.
CLASES_ALTAS = frozenset({"vo2max", "repeticiones", "hill_repeats"})
CLASES_UMBRAL = frozenset({"tempo_threshold"})
_CLASES_FUERA = frozenset({"rest", "strength_freeform", "other", "race_day"})


def _num(valor: Any) -> float | None:
    try:
        return float(valor) if valor is not None else None
    except (TypeError, ValueError):
        return None


def _zonas_del_deporte(zonas: Any, deporte: str) -> dict[int, tuple]:
    """Las zonas de ESE deporte y de ningún otro. Acepta `GarminHrZones` o el
    dict serializado de `prescription.json` (claves de zona como texto)."""
    if zonas is None:
        return {}
    por_deporte = zonas if isinstance(zonas, dict) else getattr(zonas, "by_sport", None)
    crudo = (por_deporte or {}).get(deporte) or {}
    salida: dict[int, tuple] = {}
    for zona, limites in crudo.items():
        try:
            salida[int(zona)] = tuple(limites)
        except (TypeError, ValueError):
            continue
    return salida


def referencias_de_prescripcion(prescripcion: Any) -> dict[str, float | None]:
    """El umbral de lactato, el ritmo umbral y el primer umbral de ritmo del atleta.

    Acepta `AthletePrescription` o su forma serializada. Todo None cuando no se
    sabe: entonces el ritmo no vota y el umbral de carrera cae al suelo de Z5.
    """
    if prescripcion is None:
        return {"lthr": None, "umbral_ritmo_s_km": None, "lt1_ritmo_s_km": None}
    es_dict = isinstance(prescripcion, dict)
    leer = (lambda k: prescripcion.get(k)) if es_dict else (lambda k: getattr(prescripcion, k, None))
    bandas = leer("pace_bands") or {}
    maraton = bandas.get("maraton") if isinstance(bandas, dict) else None
    if isinstance(maraton, (list, tuple)) and len(maraton) == 2:
        lt1 = _num(maraton[1])
    else:
        lt1 = _num(getattr(maraton, "slow_sec_per_km", None))
    # La FC umbral en bici sólo existe si el atleta hizo el test en bici
    # (services/ai/test_de_umbral.py): la de carrera no vale para rodar.
    por_deporte = leer("lthr_por_deporte")
    return {
        "lthr_bici": _num(por_deporte.get("bike")) if isinstance(por_deporte, dict) else None,
        "lthr": _num(leer("lactate_threshold_hr")),
        "umbral_ritmo_s_km": _num(leer("threshold_pace_sec_per_km")),
        "lt1_ritmo_s_km": lt1,
    }


def clasificar_sesion(actividad: Any, zonas: Any, referencias: dict | None = None) -> dict[str, Any] | None:
    """Una actividad como sesión suave, de umbral o alta. None si se abstiene.

    Se abstiene cuando no es de resistencia, cuando su deporte no tiene zonas del
    reloj, o cuando ningún tramo trae pulso y duración.
    """
    deporte = _canonical_sport(getattr(actividad, "activity_type", None))
    if deporte not in DEPORTES_DE_RESISTENCIA:
        return None
    zd = _zonas_del_deporte(zonas, deporte)
    techo_z2 = _num((zd.get(2) or (None, None))[1])
    suelo_z5 = _num((zd.get(5) or (None,))[0])
    if not techo_z2 or not suelo_z5:
        return None
    ref = referencias or {}
    umbral_lactato = (_num(ref.get("lthr")) if deporte == "run"
                      else _num(ref.get("lthr_bici")) if deporte == "bike" else None)
    segundo_umbral = umbral_lactato or suelo_z5

    segundos = {"zona1": 0.0, "zona2": 0.0, "zona3": 0.0}
    alta_por_pulso = False
    for tramo in getattr(actividad, "laps", None) or []:
        if not isinstance(tramo, dict):
            continue
        duracion = _num(tramo.get("duration_s"))
        media = _num(tramo.get("averageHR_bpm"))
        pico = _num(tramo.get("maxHR_bpm"))
        if pico is not None and pico >= suelo_z5:
            alta_por_pulso = True
        if not duracion or duracion <= 0 or not media:
            continue
        if media >= segundo_umbral:
            segundos["zona3"] += duracion
            if duracion >= MIN_SEGUNDOS_TRAMO_EN_ZONA_3:
                alta_por_pulso = True
        elif media > techo_z2:
            segundos["zona2"] += duracion
        else:
            segundos["zona1"] += duracion

    if sum(segundos.values()) <= 0:
        return None
    if alta_por_pulso:
        por_pulso = "alta"
    elif segundos["zona2"] / 60.0 >= MIN_MINUTOS_UMBRAL:
        por_pulso = "umbral"
    else:
        por_pulso = "suave"

    por_ritmo = None
    umbral_ritmo = _num(ref.get("umbral_ritmo_s_km"))
    if deporte == "run" and umbral_ritmo and not en_cinta(actividad):
        primer_umbral = _num(ref.get("lt1_ritmo_s_km")) or umbral_ritmo * _LT1_SOBRE_UMBRAL
        rapido = segundos_entre(actividad, 0.0, umbral_ritmo * _MARGEN_BAJO_UMBRAL)
        moderado = segundos_entre(actividad, umbral_ritmo * _MARGEN_BAJO_UMBRAL, primer_umbral)
        if rapido >= SEGUNDOS_ALTA_POR_RITMO:
            por_ritmo = "alta"
        elif moderado >= SEGUNDOS_UMBRAL_POR_RITMO:
            por_ritmo = "umbral"
        else:
            por_ritmo = "suave"

    clase = por_pulso
    senal = "pulso"
    if por_ritmo and _ORDEN[por_ritmo] > _ORDEN[por_pulso]:
        clase, senal = por_ritmo, "ritmo"
    fecha = _fecha(actividad)
    return {
        "fecha": fecha.isoformat() if fecha else None,
        "deporte": deporte,
        "clase": clase,
        "senal": senal,
        "minutos": round(_minutos(actividad)),
        "minutos_zona2": round(segundos["zona2"] / 60.0),
        "segundos": segundos,
    }


def es_sesion_dura(actividad: Any, zonas: Any, referencias: dict | None = None) -> bool | None:
    """La ÚNICA respuesta de la app a "¿esta sesión fue de calidad?".

    True si fue de umbral o alta, False si fue suave, None si no se puede
    clasificar (sin zonas del deporte, sin pulso por tramo, o no es de
    resistencia). Con None, quien llama cae a su criterio de siempre -- los
    minutos vigorosos de Garmin --, que es lo que hace un atleta sin zonas.

    Existe porque "calidad" se decidía en cuatro sitios con dos criterios: la
    distribución por pulso anclado y ritmo, y las tasas de carga, los hábitos y
    las sesiones exigentes por minutos vigorosos de Garmin -- que salen del pulso
    y, en un atleta cuyo pulso llega tarde, convierten su VO2max en un rodaje.
    """
    s = clasificar_sesion(actividad, zonas, referencias)
    return None if s is None else s["clase"] in ("umbral", "alta")


def forma_del_reparto(suave: int, umbral: int, alta: int) -> str:
    """El nombre del reparto, contado en sesiones.

    piramidal: suave > umbral > alta · polarizado: suave > alta > umbral ·
    umbral: lo de entre umbrales iguala o supera a lo suave · alta intensidad: lo
    de por encima del segundo umbral iguala o supera a lo suave.
    """
    if umbral == 0 and alta == 0:
        return "todo suave"
    if alta >= suave:
        return "alta intensidad"
    if umbral >= suave:
        return "umbral"
    return "polarizado" if alta > umbral else "piramidal"


def medir_distribucion(
    actividades: Any,
    zonas: Any,
    hoy: date | None = None,
    dias: int = CHRONIC_LOAD_WINDOW_DAYS,
    referencias: dict | None = None,
) -> dict[str, Any] | None:
    """El reparto de las sesiones de los últimos `dias`, o None sin muestras."""
    hoy = hoy or date.today()
    desde = hoy - timedelta(days=dias)
    sesiones = {"suave": 0, "umbral": 0, "alta": 0}
    por_deporte: dict[str, dict[str, int]] = {}
    segundos = {"zona1": 0.0, "zona2": 0.0, "zona3": 0.0}
    de_umbral: list[dict[str, Any]] = []
    sin_pulso = no_resistencia = altas_por_ritmo = 0

    for a in actividades or []:
        f = _fecha(a)
        if not f or not (desde < f <= hoy):
            continue
        s = clasificar_sesion(a, zonas, referencias)
        if s is None:
            if _canonical_sport(getattr(a, "activity_type", None)) in DEPORTES_DE_RESISTENCIA:
                sin_pulso += 1
            else:
                no_resistencia += 1
            continue
        sesiones[s["clase"]] += 1
        d = por_deporte.setdefault(s["deporte"], {"suave": 0, "umbral": 0, "alta": 0})
        d[s["clase"]] += 1
        for k, v in s["segundos"].items():
            segundos[k] += v
        if s["clase"] == "alta" and s["senal"] == "ritmo":
            altas_por_ritmo += 1
        if s["clase"] == "umbral":
            de_umbral.append({k: s[k] for k in ("fecha", "deporte", "minutos", "minutos_zona2")})

    total = sum(sesiones.values())
    if total < MIN_SESIONES:
        return None
    total_seg = sum(segundos.values()) or 1.0
    return {
        "dias": dias,
        "hasta": hoy.isoformat(),
        "sesiones": {**sesiones, "total": total},
        "forma": forma_del_reparto(sesiones["suave"], sesiones["umbral"], sesiones["alta"]),
        "por_deporte": por_deporte,
        "tiempo_pct": {k: round(100.0 * v / total_seg) for k, v in segundos.items()},
        "sesiones_de_umbral": sorted(de_umbral, key=lambda x: x["minutos_zona2"], reverse=True)[:3],
        "altas_por_ritmo": altas_por_ritmo,
        "con_ritmo": bool(_num((referencias or {}).get("umbral_ritmo_s_km"))),
        "sin_pulso": sin_pulso,
        "no_resistencia": no_resistencia,
    }


def _pct(n: int, total: int) -> str:
    return f"{round(100.0 * n / total)} %" if total else "—"


def render_distribucion(d: dict[str, Any] | None) -> str:
    """El bloque para los dos planificadores. "" sin medición: cero tokens."""
    # Tolerante a propósito: el reparto se guarda en summary.json -- en MySQL --
    # y el chat lo relee en cada mensaje. Un reparto guardado con un formato
    # anterior (claves renombradas el 2026-09-13) no puede tumbar el chat de un
    # atleta hasta su próxima generación.
    if not d or not isinstance(d.get("sesiones"), dict):
        return ""
    s = {k: int(d["sesiones"].get(k) or 0) for k in ("suave", "umbral", "alta")}
    total = int(d["sesiones"].get("total") or sum(s.values()))
    if not total:
        return ""
    metodo = (
        "Modelo de 3 zonas: zona 1 por debajo del primer umbral (el techo de su Z2 del reloj), zona 2 entre "
        "los dos umbrales, zona 3 por encima del segundo (su umbral de lactato en carrera; en bici y natación, "
        "el suelo de su Z5). Cada sesión cuenta por lo más duro que hizo: alta si pasó un tramo en zona 3 o "
        f"tocó Z5, umbral si acumuló {MIN_MINUTOS_UMBRAL} minutos o más en zona 2, suave en otro caso."
    )
    if d.get("con_ritmo"):
        metodo += (
            " En carrera vota también el ritmo en llano contra su umbral (2 minutos o más más rápido que el "
            "umbral es alta), porque en series cortas el pulso llega tarde; manda la más dura de las dos señales."
        )
    forma = d.get("forma") or forma_del_reparto(s["suave"], s["umbral"], s["alta"])
    lineas = [
        f"### Cómo reparte su intensidad (MEDIDO en sus últimos {d.get('dias') or CHRONIC_LOAD_WINDOW_DAYS} días; "
        "describe, no es un objetivo)",
        metodo,
        f"- {total} sesiones: {s['suave']} suaves ({_pct(s['suave'], total)}), {s['umbral']} de umbral "
        f"({_pct(s['umbral'], total)}), {s['alta']} altas ({_pct(s['alta'], total)}). Forma: {forma}.",
    ]
    partes = [
        f"{_NOMBRE.get(dep, dep)} {int(c.get('suave') or 0)} suaves · {int(c.get('umbral') or 0)} umbral · "
        f"{int(c.get('alta') or 0)} altas"
        for dep, c in sorted((d.get("por_deporte") or {}).items()) if isinstance(c, dict)
    ]
    if partes:
        lineas.append("- Por deporte: " + "; ".join(partes) + ".")
    for u in d.get("sesiones_de_umbral") or []:
        minutos_z2 = u.get("minutos_zona2", u.get("minutos_z3"))
        if not u.get("fecha") or minutos_z2 is None:
            continue
        lineas.append(
            f"- Sesión de umbral el {u['fecha']}: {_NOMBRE.get(u.get('deporte'), u.get('deporte') or '')} de "
            f"{u.get('minutos', '?')}', {minutos_z2}' en zona 2."
        )
    if (d.get("altas_por_ritmo") or 0) >= MIN_ALTAS_POR_RITMO:
        lineas.append(
            f"- {d['altas_por_ritmo']} de sus sesiones altas lo son sólo por el ritmo: su pulso no llegó a zona 3. "
            "En series cortas su pulso va por detrás de su esfuerzo, así que su zona de pulso subestima lo que "
            "hace: léelas por ritmo. Por la misma razón, el Load Focus de Garmin, que reparte la carga según el "
            "pulso, puede marcarle déficit de aeróbico alto o de anaeróbico aunque haga esas sesiones."
        )
    # Sólo con el formato de 3 zonas: uno anterior guardaba las zonas del reloj
    # (Z1-2 / Z3 / Z4-5), y reinterpretarlas pondría un número mal etiquetado.
    t = d.get("tiempo_pct")
    if isinstance(t, dict):
        lineas.append(
            f"- Por tiempo (pulso medio de cada tramo): zona 1 {t.get('zona1', 0)} % · zona 2 {t.get('zona2', 0)} % "
            f"· zona 3 {t.get('zona3', 0)} %. Es la otra forma de contar de la literatura; con series cortas "
            "subestima la zona 3, así que no se compara con el recuento por sesiones."
        )
    nado = (d.get("por_deporte") or {}).get("swim") or {}
    if nado.get("alta") or nado.get("umbral"):
        lineas.append(
            "- Natación: en triatletas es normal que lleve más intensidad que la bici o la carrera (Ainhoa "
            "Murúa, 7.ª en Londres 2012, nadaba un 10 % en zona 3 y rodaba un 2 %), y el pulso de muñeca en "
            "el agua es además el menos fiable."
        )
    fuera = []
    if d.get("no_resistencia"):
        fuera.append(f"{d['no_resistencia']} de fuerza u otras actividades")
    if d.get("sin_pulso"):
        fuera.append(f"{d['sin_pulso']} de resistencia sin pulso por tramo o sin zonas de ese deporte")
    if fuera:
        lineas.append("- No cuentan: " + " y ".join(fuera) + ".")
    lineas.append(
        "No hay un reparto correcto para todos: polarizado, piramidal, umbral y alta intensidad han funcionado "
        "según el atleta, su objetivo y sus horas. Esto dice lo que ha hecho; qué le conviene ahora lo decides "
        "tú con su historia y cómo respondió."
    )
    return "\n".join(lineas) + "\n"


def revisar_reparto_de_la_semana(sesiones: list[dict[str, Any]], techo: float | None) -> str | None:
    """LINT: si la semana planificada pasa el techo de sesiones duras de su fase.

    Nunca bloquea ni reescribe nada: devuelve un aviso para el log, igual que
    `number_audit.py`. Sube a recorte sólo cuando se le haya visto acertar.

    El techo es una fracción (0,2 = como mucho ~2 de cada 10). Se permite
    siempre al menos una sesión dura: en una semana de tres sesiones, 0,2 × 3
    redondea a 1, y una sola calidad no es el sesgo que esto vigila.
    """
    if techo is None:
        return None
    try:
        techo = min(1.0, max(0.0, float(techo)))
    except (TypeError, ValueError):
        return None
    duras = total = 0
    for s in sesiones or []:
        if s.get("is_rest") or s.get("sport") not in DEPORTES_DE_RESISTENCIA:
            continue
        clase = str(s.get("intensity_class") or "")
        if clase in _CLASES_FUERA:
            continue
        total += 1
        if clase in CLASES_ALTAS or clase in CLASES_UMBRAL:
            duras += 1
    if total == 0:
        return None
    permitidas = max(1, int(techo * total + 0.5))
    if duras <= permitidas:
        return None
    return (
        f"Reparto: la semana planifica {duras} sesiones duras de {total} de resistencia, y el techo de la "
        f"fase ({round(techo * 100)} %) permite {permitidas}."
    )
