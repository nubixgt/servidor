"""El progreso del atleta: racha, nivel, medallas y récords (Beta 3.0, F8). Sin IA.

Decisión del responsable (2026-10-10): una racha SEMANAL y honesta.

- **Una semana suma** si hizo al menos el 70 % de las sesiones evaluadas (hechas, cambiadas o
  hechas sin la intensidad: las tres son entrenar) y no pasó del 120 % de las horas que se
  le mandaron.
- **Por encima del 120 % ni suma ni rompe**: entrenar de más no es lo que se quiere premiar,
  pero tampoco es dejarlo.
- **Con una molestia o enfermedad declarada se CONGELA**: ni suma ni rompe. Una racha que
  castiga parar por dolor empuja a entrenar con dolor.
- **Una semana sin plan rompe la cadena** (dos ventanas del expediente separadas más de una
  semana): la racha es de semanas seguidas.
- Niveles por semanas constantes acumuladas (1, 3, 6, 10, 16, 24, 36), no por la racha
  actual: perder la racha no quita lo hecho.

Las medallas se DERIVAN cada vez de los datos (no hay tabla): lo que el atleta ya vio se
recuerda en su navegador. Sólo las de los deportes que hace: un nadador no recibe medallas
de correr.
"""

from __future__ import annotations

from datetime import date, datetime, timedelta
from typing import Any

UMBRAL_DE_SESIONES = 0.70
TOPE_DE_HORAS = 1.20
NIVELES = (1, 3, 6, 10, 16, 24, 36)
_KM_POR_DEPORTE = {
    "Carrera": (50, 100, 250, 500, 1000),
    "Bici": (250, 500, 1000, 2500, 5000),
    "Natación": (10, 25, 50, 100),
}
_DEPORTE_DE_LA_DISCIPLINA = {"carrera": "Carrera", "trail": "Carrera", "bici": "Bici", "natacion": "Natación",
                             "natación": "Natación"}


def _fecha(v: Any) -> date | None:
    try:
        return datetime.strptime(str(v)[:10], "%Y-%m-%d").date()
    except (TypeError, ValueError):
        return None


def _cerrada(w: dict) -> bool:
    from services.ai.charts import _is_closed

    return _is_closed(w)


def ventanas(semanas: list[dict]) -> list[dict]:
    """Una semana por ventana: si dos se pisan (la semana se rehízo), manda la última escrita."""
    salida: list[dict] = []
    for w in sorted((w for w in semanas if _fecha(w.get("from")) and _fecha(w.get("to"))),
                    key=lambda w: (str(w.get("from")), str(w.get("ts") or ""))):
        if salida and _fecha(w["from"]) <= _fecha(salida[-1]["to"]):
            salida[-1] = w
        else:
            salida.append(w)
    return salida


def _molestia_en(w: dict, checkins: list[dict], lesiones: list[dict]) -> bool:
    """Si declaró una molestia o una lesión que cubre esa semana."""
    desde, hasta = _fecha(w.get("from")), _fecha(w.get("to"))
    # El check-in se contesta ANTES de su semana (al generarla): uno del último día de una
    # semana ya habla de la siguiente, no de la que acaba.
    for c in checkins or []:
        f = _fecha(c.get("date") or c.get("fecha"))
        if f and desde - timedelta(days=3) <= f < hasta and (str(c.get("niggle_location") or "").strip()
                                                              or (c.get("niggle_pain") or 0) > 0):
            return True
    # Una lesión congela la semana en que EMPEZÓ, no todas las que siga activa: si no, una
    # lesión vieja sin cerrar dejaría la racha congelada para siempre.
    for lesion in lesiones or []:
        inicio = _fecha(lesion.get("start_date") or lesion.get("date"))
        if inicio and desde - timedelta(days=3) <= inicio <= hasta:
            return True
    return False


def como_cuenta(w: dict, checkins: list[dict] | None = None, lesiones: list[dict] | None = None) -> str:
    """`suma`, `neutra` (pasó del 120 % de horas), `congelada` (molestia), `rompe` o `abierta`."""
    if not _cerrada(w):
        return "abierta"
    if _molestia_en(w, checkins or [], lesiones or []):
        return "congelada"
    hechas = sum(int(w.get(k) or 0) for k in ("completed_sessions", "modified_sessions", "under_intensity_sessions"))
    evaluadas = hechas + int(w.get("missed_sessions") or 0)
    prescritas, ejecutadas = float(w.get("prescribed_hours") or 0), float(w.get("executed_hours") or 0)
    if prescritas and ejecutadas > prescritas * TOPE_DE_HORAS:
        return "neutra"
    if evaluadas and hechas / evaluadas >= UMBRAL_DE_SESIONES:
        return "suma"
    return "rompe"


def racha(semanas: list[dict], checkins: list[dict] | None = None, lesiones: list[dict] | None = None) -> dict[str, Any]:
    vs = ventanas(semanas)
    actual = mejor = constantes = 0
    anterior_hasta = None
    ultima: dict[str, Any] | None = None
    for w in vs:
        cuenta = como_cuenta(w, checkins, lesiones)
        if cuenta == "abierta":
            continue
        desde = _fecha(w["from"])
        hueco = anterior_hasta is not None and (desde - anterior_hasta).days > 8
        if hueco:
            actual = 0  # una semana sin plan rompe la cadena
        anterior_hasta = _fecha(w["to"])
        # Cómo contó la última semana cerrada (2026-10-21): la pantalla enseñaba «0 semanas seguidas» con «2
        # cumplidas» al lado y sin decir por qué. Con esto puede decirlo.
        hechas = sum(int(w.get(k) or 0) for k in ("completed_sessions", "modified_sessions", "under_intensity_sessions"))
        evaluadas = hechas + int(w.get("missed_sessions") or 0)
        ultima = {"desde": str(w["from"])[:10], "hasta": str(w["to"])[:10], "cuenta": cuenta, "hechas": hechas,
                  "evaluadas": evaluadas, "tras_un_hueco": hueco}
        if cuenta == "suma":
            actual += 1
            constantes += 1
            mejor = max(mejor, actual)
        elif cuenta == "rompe":
            actual = 0
    nivel = sum(1 for n in NIVELES if constantes >= n)
    siguiente = next((n for n in NIVELES if constantes < n), None)
    return {"actual": actual, "mejor": mejor, "semanas_constantes": constantes, "nivel": nivel,
            "siguiente_nivel_en": siguiente - constantes if siguiente else None, "ultima_semana": ultima}


def _km_por_deporte(semanas: list[dict]) -> dict[str, float]:
    km: dict[str, float] = {}
    for w in ventanas(semanas):
        for disciplina, v in (w.get("executed_by_discipline") or {}).items():
            deporte = _DEPORTE_DE_LA_DISCIPLINA.get(str(disciplina).lower())
            if deporte:
                km[deporte] = km.get(deporte, 0.0) + float((v or {}).get("km") or 0)
    return {k: round(v, 1) for k, v in km.items() if v > 0}


def _records(semanas: list[dict]) -> dict[str, Any]:
    horas, km = 0.0, {}
    for w in ventanas(semanas):
        if not _cerrada(w):
            continue
        horas = max(horas, float(w.get("executed_hours") or 0))
        for disciplina, v in (w.get("executed_by_discipline") or {}).items():
            deporte = _DEPORTE_DE_LA_DISCIPLINA.get(str(disciplina).lower())
            if deporte:
                km[deporte] = max(km.get(deporte, 0.0), float((v or {}).get("km") or 0))
    return {"horas_en_una_semana": round(horas, 1), "km_en_una_semana": {k: round(v, 1) for k, v in km.items() if v > 0}}


def _subio_carga(logs: list[dict]) -> bool:
    """Como `historialPorEjercicio` del frontend: el mismo ejercicio, más kilos que la vez anterior."""
    ultimo: dict[str, float] = {}
    for log in sorted(logs or [], key=lambda x: str(x.get("fecha") or "")):
        for e in log.get("exercises") or []:
            clave = e.get("exercise_id") or e.get("name")
            kg = float(e.get("weight_kg") or 0)
            if not clave or not kg:
                continue
            if clave in ultimo and kg > ultimo[clave]:
                return True
            ultimo[clave] = kg
    return False


def _checkin_seguidos(checkins: list[dict]) -> int:
    semanas = sorted({_fecha(c.get("date") or c.get("fecha")).isocalendar()[:2]
                      for c in checkins or [] if _fecha(c.get("date") or c.get("fecha"))})
    mejor = actual = 0
    previa = None
    for anio, semana in semanas:
        lunes = date.fromisocalendar(anio, semana, 1)
        actual = actual + 1 if previa and (lunes - previa).days == 7 else 1
        mejor = max(mejor, actual)
        previa = lunes
    return mejor


def _medalla(id_: str, nombre: str, descripcion: str, conseguida: bool, progreso: str = "") -> dict[str, Any]:
    return {"id": id_, "nombre": nombre, "descripcion": descripcion, "conseguida": bool(conseguida), "progreso": progreso}


def medallas(semanas: list[dict], *, checkins: list[dict] | None = None, lesiones: list[dict] | None = None,
             registros_de_fuerza: list[dict] | None = None, perfil: dict | None = None,
             vo2max: list[dict] | None = None, hoy: date | None = None) -> list[dict[str, Any]]:
    hoy = hoy or date.today()
    r = racha(semanas, checkins, lesiones)
    vs = ventanas(semanas)
    salida = [
        _medalla("primera_semana", "Primera semana", "Cumpliste tu primera semana.", r["semanas_constantes"] >= 1),
        *[_medalla(f"racha_{n}", f"{n} semanas seguidas", f"{n} semanas seguidas cumpliendo tu plan.", r["mejor"] >= n,
                   f"{min(r['actual'], n)} de {n}") for n in (4, 8, 12)],
        _medalla("todas_las_clave", "Semana con todos los clave", "Una semana con todos sus entrenos clave hechos.",
                 any(_cerrada(w) and (w.get("quality_prescribed") or 0) > 0
                     and (w.get("quality_done") or 0) >= w["quality_prescribed"] for w in vs)),
    ]
    for deporte, km in _km_por_deporte(semanas).items():
        for umbral in _KM_POR_DEPORTE.get(deporte, ()):
            salida.append(_medalla(f"km_{deporte.lower()}_{umbral}", f"{umbral} km de {deporte.lower()}",
                                   f"{umbral} km de {deporte.lower()} entrenados con tu plan.", km >= umbral,
                                   f"{min(km, umbral):.0f} de {umbral} km"))
            if km < umbral:
                break  # sólo la siguiente que queda por conseguir
    logs = registros_de_fuerza or []
    if logs:
        salida += [
            _medalla("fuerza_1", "Primera sesión de fuerza", "Registraste tu primera sesión de fuerza.", len(logs) >= 1),
            _medalla("fuerza_10", "10 sesiones de fuerza", "Diez sesiones de fuerza registradas.", len(logs) >= 10,
                     f"{min(len(logs), 10)} de 10"),
            _medalla("fuerza_carga", "Subiste la carga", "Levantaste más que la vez anterior en un ejercicio.",
                     _subio_carga(logs)),
        ]
    seguidos = _checkin_seguidos(checkins or [])
    salida.append(_medalla("checkin_4", "Cuatro check-in seguidos", "Contaste cómo llegabas cuatro semanas seguidas.",
                           seguidos >= 4, f"{min(seguidos, 4)} de 4"))
    if (perfil or {}).get("threshold_tests"):
        salida.append(_medalla("test_umbral", "Test de umbral", "Hiciste tu test de umbral.", True))
    carreras = [c for c in (perfil or {}).get("competitions") or [] if _fecha(c.get("date")) and _fecha(c.get("date")) < hoy]
    if carreras:
        hecha = any(_cerrada(w) and float(w.get("executed_hours") or 0) > 0
                    and _fecha(w["from"]) <= _fecha(c["date"]) <= _fecha(w["to"]) for c in carreras for w in vs)
        salida.append(_medalla("carrera", "Carrera completada", "Corriste una de tus carreras con tu plan.", hecha))
    puntos = [p for p in (vo2max or []) if p.get("value") is not None and _fecha(p.get("date") or p.get("label"))]
    if len(puntos) >= 2:
        ultimo = puntos[-1]
        hace = _fecha(ultimo.get("date") or ultimo.get("label")) - timedelta(weeks=8)
        base = next((p for p in reversed(puntos) if _fecha(p.get("date") or p.get("label")) <= hace), None)
        if base is not None:
            salida.append(_medalla("vo2max", "Tu forma aeróbica subió", "Tu VO₂max subió un punto o más en 8 semanas.",
                                   float(ultimo["value"]) - float(base["value"]) >= 1))
    return salida


def del_mes(semanas: list[dict], hoy: date | None = None) -> dict[str, Any]:
    """Horas y km POR DEPORTE de las semanas cerradas que empezaron este mes."""
    hoy = hoy or date.today()
    horas, km = 0.0, {}
    for w in ventanas(semanas):
        f = _fecha(w["from"])
        if not _cerrada(w) or (f.year, f.month) != (hoy.year, hoy.month):
            continue
        horas += float(w.get("executed_hours") or 0)
        for disciplina, v in (w.get("executed_by_discipline") or {}).items():
            deporte = _DEPORTE_DE_LA_DISCIPLINA.get(str(disciplina).lower())
            if deporte:
                km[deporte] = km.get(deporte, 0.0) + float((v or {}).get("km") or 0)
    return {"horas": round(horas, 1), "km_por_deporte": {k: round(v, 1) for k, v in km.items() if v > 0}}


def calcular(semanas: list[dict], *, checkins: list[dict] | None = None, lesiones: list[dict] | None = None,
             registros_de_fuerza: list[dict] | None = None, perfil: dict | None = None,
             vo2max: list[dict] | None = None, hoy: date | None = None) -> dict[str, Any]:
    """Todo el progreso, o {} si no hay ninguna semana cerrada todavía."""
    if not any(_cerrada(w) for w in semanas or []):
        return {}
    return {
        "racha": racha(semanas, checkins, lesiones),
        "medallas": medallas(semanas, checkins=checkins, lesiones=lesiones, registros_de_fuerza=registros_de_fuerza,
                             perfil=perfil, vo2max=vo2max, hoy=hoy),
        "records": _records(semanas),
        "mes": del_mes(semanas, hoy),
    }
