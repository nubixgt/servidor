"""Una semana puede ser alta porque el atleta la pidió; no puede ser peligrosa (2026-09-23).

El responsable, al ver la semana del atleta 2 con su natación pedida (11,9 h contra 6,1 h
normales): las peticiones del atleta mandan, pero cuando alguien pide "entrenar muchísimo
todos los días" el atleta tiene que saber que su carga es alta por lo que pidió, y la IA
tiene que saber limitar lo extremo. Sin prohibir de más: que la carga baje sólo cuando lo
pida la evidencia.

Lo que ya se aprendió aquí y manda sobre el diseño: dos techos de carga se retiraron
(`weekly_hours_exceeded`, `weekly_load_exceeded`) porque recortaban por un número con
ruido -- la carga por hora de una natación "suave" del atleta 2 va de 24 a 102 -- y el
ACWR no predice lesión (Impellizzeri 2020). Así que esto NO es un techo fino: son tres
señales robustas, cada una medida contra el historial del PROPIO atleta y con su evidencia,
y sólo la versión extrema de cada una devuelve la semana a la decisión.

- **Carga de la semana contra su crónica.** Garmin llama "alta" a 1,5-1,9 veces la
  crónica y "muy alta" a 2,0 o más. Es un cálculo de Garmin, no un límite probado, por eso
  "alta" sólo se anota y "muy alta" es lo único que se trata como extremo.
- **Una carrera mucho más larga que la más larga de sus últimos 30 días.** Frandsen 2025
  (BJSM, 5.205 corredores con Garmin): una sesión más de un 10 % más larga que la más larga
  de 30 días sube la tasa de lesión (HRR 1,64 entre 10-30 %, 1,52 entre 30-100 %) y más del
  doble, 2,28. Correr es lo que lesiona por volumen; nadar y pedalear no tienen esa
  evidencia, y aquí no se inventa.
- **Horas de resistencia contra las suyas.** Duplicar en una semana lo que hace en cuatro
  no lo respalda ningún historial; Nielsen 2014 lo midió en carrera (más de un 30 % en dos
  semanas, más lesiones por distancia). La fuerza va aparte: no es resistencia.

Y una cuarta que nunca es extrema: siete días seguidos sin descanso (Foster 1998, monotonía).

Lo "alto" se anota en el `worker.log`; el atleta sólo ve un aviso cuando la semana es EXTREMA
(el responsable, 2026-09-25: el aviso de semana alta confundía más de lo que ayudaba).

Se abstiene señal a señal: sin crónica, sin carrera en 30 días o sin horas medidas, esa
señal no dice nada.
"""

from __future__ import annotations

from datetime import date, datetime, timedelta
from typing import Any

from services.ai.weekly_load import estimar_carga

NORMAL, ALTA, EXTREMA = "normal", "alta", "extrema"

# Bandas de Garmin para la relación carga aguda / crónica.
CARGA_ALTA, CARGA_EXTREMA = 1.5, 2.0
# Frandsen 2025: pico moderado (30-100 %) y grande (>100 %) de una sesión de carrera.
CARRERA_ALTA, CARRERA_EXTREMA = 1.3, 2.0
# Horas de resistencia contra su semana normal: +50 % se avisa; el doble es extremo.
HORAS_ALTAS, HORAS_EXTREMAS = 1.5, 2.0

_DEPORTES_DE_RESISTENCIA = ("run", "bike", "swim")
_NOMBRE = {"run": "carrera", "bike": "bici", "swim": "natación"}


def historial_de_carga(actividades: Any, hoy: date | None = None) -> dict[str, Any]:
    """Lo que su historial respalda y la semana no mira sola: su carrera más larga en 30 días."""
    from services.ai.load_state import _canonical_sport

    hoy = hoy or date.today()
    desde = hoy - timedelta(days=30)
    mas_larga = 0.0
    for act in actividades or []:
        crudo = str(getattr(act, "start_time", "") or "").replace("T", " ")[:10]
        try:
            fecha = datetime.strptime(crudo, "%Y-%m-%d").date()
        except ValueError:
            continue
        if not (desde <= fecha <= hoy) or _canonical_sport(getattr(act, "activity_type", None)) != "run":
            continue
        try:
            minutos = float(getattr(getattr(act, "summary", None), "duration", 0) or 0) / 60.0
        except (TypeError, ValueError):
            continue
        mas_larga = max(mas_larga, minutos)
    return {"carrera_mas_larga_30d_min": round(mas_larga) if mas_larga else None}


def _n(valor: float, decimales: int = 1) -> str:
    """Un número como se lee en español: 8,2 y no 8.2."""
    return f"{valor:.{decimales}f}".replace(".", ",")


def _campo(dia: Any, *nombres: str):
    for n in nombres:
        valor = dia.get(n) if isinstance(dia, dict) else getattr(dia, n, None)
        if valor is not None:
            return valor
    return None


def revisar_la_carga(dias: list, *, tasas: dict | None, cronica: float | None,
                     horas_de_resistencia: float | None, carrera_mas_larga_min: float | None,
                     dias_del_bloque: int = 7, deportes_pedidos: set[str] | None = None) -> dict[str, Any]:
    """Nivel de la semana (normal / alta / extrema) y por qué, con los números.

    `dias` son los de la decisión (`duration_minutes`) o los del plan
    (`total_duration_minutes`). Un bloque de menos de 7 días se compara contra la parte
    proporcional de su crónica y de sus horas: la primera semana puede ir de miércoles a
    domingo.
    """
    proporcion = max(1, min(7, dias_del_bloque)) / 7.0
    senales: list[dict[str, Any]] = []
    pico: dict[str, Any] | None = None  # la carrera más larga respecto a su historial: una señal, no una por día
    carga, sin_tasa, minutos_por_deporte, dias_con_entreno = 0.0, 0, {}, set()
    for d in dias or []:
        if _campo(d, "is_rest"):
            continue
        deporte = _campo(d, "sport")
        minutos = float(_campo(d, "duration_minutes", "total_duration_minutes") or 0)
        if deporte in (None, "rest") or minutos <= 0:
            continue
        dias_con_entreno.add(_campo(d, "date"))
        # La carrera no es carga que prescribimos: la eligió el atleta, y estas señales existen
        # para limitar lo que NOSOTROS le ponemos. Contada, un trail de 3 h contra su tirada más
        # larga del mes de 75' salía "extrema" y la decisión recibía "bájala" sobre la carrera
        # misma (la misma familia que el día de carrera quitado por su tabla, Hugo 2026-10-09).
        # Sí cuenta para la racha sin descanso: competir no es descansar.
        if _campo(d, "intensity_class") == "race_day":
            continue
        if deporte in _DEPORTES_DE_RESISTENCIA:
            minutos_por_deporte[deporte] = minutos_por_deporte.get(deporte, 0.0) + minutos
        valor = estimar_carga(deporte, _campo(d, "intensity_class"), minutos, tasas or {}) if tasas else None
        if valor is None:
            sin_tasa += 1
        else:
            carga += valor
        if deporte == "run" and carrera_mas_larga_min and minutos > CARRERA_ALTA * carrera_mas_larga_min:
            if pico is None or minutos > pico["minutos"]:
                pico = {"minutos": minutos, "fecha": _campo(d, "date")}

    if pico:
        extremo = pico["minutos"] > CARRERA_EXTREMA * carrera_mas_larga_min
        senales.append({
            "nivel": EXTREMA if extremo else ALTA, "tipo": "carrera_larga",
            "texto": (f"una carrera de {pico['minutos']:.0f}' el {pico['fecha']} contra la más larga de sus últimos "
                      f"30 días ({carrera_mas_larga_min:.0f}'): "
                      + ("más del doble, el pico con más riesgo de lesión medido (Frandsen 2025)" if extremo
                         else "un pico de sesión que sube el riesgo de lesión (Frandsen 2025)")),
            "para_ti": (f"una carrera de {pico['minutos']:.0f}' cuando tu más larga del último mes fue de "
                        f"{carrera_mas_larga_min:.0f}'"),
        })

    relacion = None
    if cronica and tasas and carga:
        referencia = float(cronica) * proporcion
        relacion = carga / referencia
        if relacion >= CARGA_ALTA:
            senales.append({
                "nivel": EXTREMA if relacion >= CARGA_EXTREMA else ALTA, "tipo": "carga",
                "texto": (f"una carga estimada de ~{carga:.0f} contra ~{referencia:.0f} de su crónica en estos "
                          f"días ({_n(relacion)} veces; Garmin llama 'alta' a 1,5 y 'muy alta' a 2,0)"),
                "para_ti": f"una carga de unas {_n(relacion)} veces la que vienes absorbiendo",
            })

    resistencia = sum(minutos_por_deporte.values()) / 60.0
    if horas_de_resistencia:
        referencia_h = float(horas_de_resistencia) * proporcion
        relacion_h = resistencia / referencia_h if referencia_h else 0
        if relacion_h >= HORAS_ALTAS:
            senales.append({
                "nivel": EXTREMA if relacion_h >= HORAS_EXTREMAS else ALTA, "tipo": "horas",
                "texto": (f"{_n(resistencia)} h de natación, bici y carrera contra sus ~{_n(referencia_h)} h "
                          f"normales en estos días (+{(relacion_h - 1) * 100:.0f} %)"),
                "para_ti": (f"{_n(resistencia)} h de natación, bici y carrera frente a tus ~{_n(referencia_h)} h "
                            f"habituales (+{(relacion_h - 1) * 100:.0f} %)"),
            })

    # Días SEGUIDOS, no días con entreno: el bloque del atleta 2 del 2026-09-26 (nueve días, con
    # descanso los dos sábados) tenía siete días con entreno y ninguna racha de siete, y el log
    # decía "siete días seguidos sin descanso".
    if dias_del_bloque >= 7 and racha_sin_descanso(dias_con_entreno) >= 7:
        senales.append({"nivel": ALTA, "tipo": "sin_descanso",
                        "texto": "siete días seguidos sin descanso (la monotonía con carga alta es lo que se asocia "
                                 "al sobreentrenamiento, Foster 1998)",
                        "para_ti": "ningún día de descanso"})

    nivel = EXTREMA if any(s["nivel"] == EXTREMA for s in senales) else ALTA if senales else NORMAL
    pedidos = set(deportes_pedidos or ()) & set(minutos_por_deporte)
    return {"nivel": nivel, "senales": senales, "carga": round(carga) if carga else None,
            "relacion_carga": round(relacion, 2) if relacion else None,
            "horas_de_resistencia": round(resistencia, 1), "por_peticion": sorted(pedidos),
            "minutos_por_deporte": {k: round(v) for k, v in minutos_por_deporte.items()}}


def racha_sin_descanso(fechas) -> int:
    """La racha más larga de días seguidos con entreno. Una fecha que no se lee no suma."""
    dias = set()
    for f in fechas or ():
        try:
            dias.add(date.fromisoformat(str(f)[:10]))
        except ValueError:
            continue
    mejor = 0
    for d in dias:
        if d - timedelta(days=1) in dias:
            continue
        largo = 1
        while d + timedelta(days=largo) in dias:
            largo += 1
        mejor = max(mejor, largo)
    return mejor


def deportes_de_las_peticiones(texto: str) -> set[str]:
    """Qué deportes nombra lo que pidió el atleta. Sólo palabras inequívocas."""
    t = (texto or "").lower()
    salida = set()
    if any(p in t for p in ("natación", "natacion", "nadar", "nado", "piscina", "swim")):
        salida.add("swim")
    if any(p in t for p in ("correr", "carrera", "rodaje", "tirada", "run")):
        salida.add("run")
    if any(p in t for p in ("bici", "ciclismo", "rodillo", "pedalear", "bike")):
        salida.add("bike")
    return salida


def mensaje_para_la_decision(revision: dict[str, Any]) -> str:
    """Lo que se le devuelve a la decisión cuando la semana es EXTREMA. Decide ella cómo bajarla."""
    extremas = [s["texto"] for s in revision["senales"] if s["nivel"] == EXTREMA]
    pedida = revision.get("por_peticion")
    return (
        "Esta semana pasa de lo que su historial respalda:\n"
        + "\n".join(f"- {t}." for t in extremas)
        + "\nBájala a algo que su cuerpo haya demostrado absorber. "
        + ("Si es por lo que pidió, sigue la dirección de su petición pero en progresión (esta semana una parte, "
           "las siguientes el resto), sin pagarla con los otros deportes, " if pedida else "")
        + "y di en `week_rationale` qué se limitó y con qué evidencia: es por seguridad, no una opinión."
    )


def aviso_para_el_atleta(revision: dict[str, Any], peticiones: str = "") -> str:
    """El aviso que el atleta ve en su semana: SÓLO si la semana es extrema. "" en otro caso.

    El responsable, 2026-09-25, con su semana delante: el aviso de semana "alta" era raro y
    confuso ("volumen alto… la carga queda en 1,2 veces…"), y lo quiere sólo cuando el volumen
    sea de verdad muy alto, dicho claro. Lo "alto" sigue en el `worker.log`; al atleta sólo le
    llega lo que su historial no respalda (el doble de su carga o de sus horas, o una carrera de
    más del doble de su más larga del mes), con esas razones y ninguna más.
    """
    if revision["nivel"] != EXTREMA:
        return ""
    razones = "; ".join(s["para_ti"] for s in revision["senales"] if s["nivel"] == EXTREMA)
    pedida = revision.get("por_peticion") if peticiones else None
    # "Es por lo que pediste" sólo si lo pidió: la semana del atleta 2 del 2026-09-23, sin ninguna
    # petición vigente, le decía al atleta que la carga alta era cosa suya.
    causa = f" Es por lo que pediste ({', '.join(_NOMBRE.get(d, d) for d in pedida)})." if pedida else ""
    # «Rehacer semana» no existe desde la v1.1 y el chat es de sólo lectura (auditoría del
    # 2026-09-24): lo que cambia esta semana es eliminarla y volver a generarla.
    return (f"Esta semana es mucho más de lo que sueles hacer: {razones}.{causa} Si puedes, llega a esto poco "
            "a poco. Si notas cansancio que no se va, duermes peor o te aparece una molestia, baja el ritmo. "
            "Para cambiarla, elimínala («Eliminar plan» → esta semana) y vuelve a generarla.")
