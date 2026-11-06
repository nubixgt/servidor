"""Lo que acompaña a una consulta de ayuda (Beta 3.0, F3). Sin IA y con lista blanca.

Hasta aquí un reporte de un atleta llegaba sin nada: había que entrar al servidor, buscar
su traza y adivinar de qué sesión hablaba. Esto arma, en el momento de preguntar, lo que
hace falta para mirarlo:

- la sesión desde la que preguntó: lo que DECIDIÓ la IA (`week_intents.json` de su traza)
  contra lo que se CODIFICÓ (su `weekly_plan`, con los minutos que suman los pasos) y lo
  que hay en el RELOJ (el registro de subidas y la huella de la sesión);
- sus últimos trabajos, las redes que le corrigieron algo y la última semana de su expediente
  (lo prescrito y, si ya pasó, lo hecho);
- si sus zonas son las de fábrica;
- lo que manda PHP (perfil, cuenta, migraciones), filtrado otra vez aquí.

**Lista blanca en cada salto**: sólo salen las claves de abajo. Nunca la contraseña de
Garmin, ni el perfil entero, ni `spec.json`. Lo recortado se dice.
"""

from __future__ import annotations

import json
from datetime import UTC, datetime, timedelta
from pathlib import Path
from typing import Any

from services.ai.triaje import CATEGORIAS, proponer

# Lo único de PHP que entra en el diagnóstico. Una clave nueva se añade aquí a propósito.
CLAVES_DE_PHP = (
    "perfil_pct", "contrasena_garmin_guardada", "rejilla_vacia", "carreras_en_dia_de_descanso",
    "migraciones_que_faltan", "ultimas_generaciones", "semana_automatica", "tiene_macrociclo",
    "semanas_del_plan", "zona_horaria", "cambios_del_atleta",
)
_CLAVES_DEL_TRABAJO = ("job_id", "kind", "estado", "error", "proveedor", "inicio", "duracion_s")
_CLAVES_DE_LA_SEMANA = (
    "from", "to", "prescribed_sessions", "prescribed_hours", "quality_prescribed", "quality_done",
    "completed_sessions", "modified_sessions", "missed_sessions", "executed_hours",
)
MAX_ERRORES_DEL_NAVEGADOR = 10
_MAX_TEXTO = 600


def _corto(texto: Any, n: int = _MAX_TEXTO) -> str:
    t = str(texto or "")
    return t if len(t) <= n else t[:n] + f" […recortado: {len(t)} caracteres]"


def _lista_blanca(datos: Any, claves) -> dict[str, Any]:
    return {k: datos[k] for k in claves if isinstance(datos, dict) and k in datos}


def leer_clave_de_sesion(clave: str | None) -> tuple[str, str] | None:
    """"2026-10-14|unica" -> ("2026-10-14", "unica"). None si no es una clave válida."""
    partes = str(clave or "").split("|")
    fecha = partes[0]
    try:
        datetime.strptime(fecha, "%Y-%m-%d")
    except ValueError:
        return None
    franja = partes[1] if len(partes) > 1 and partes[1] in ("unica", "am", "pm") else "unica"
    return fecha, franja


def _misma_sesion(dia: dict, fecha: str, franja: str) -> bool:
    return dia.get("date") == fecha and (dia.get("session_slot") or "unica") == franja


def _decidido(storage_dir: Path, fecha: str, franja: str) -> dict[str, Any] | None:
    """Lo que decidió la IA para esa sesión, en la traza más reciente que la tenga."""
    raiz = storage_dir / "reasoning"
    if not raiz.is_dir():
        return None
    for traza in sorted((d for d in raiz.iterdir() if d.is_dir()), key=lambda d: d.name, reverse=True):
        try:
            semanas = json.loads((traza / "week_intents.json").read_text(encoding="utf-8"))
        except (OSError, ValueError):
            continue
        for semana in semanas if isinstance(semanas, list) else []:
            for dia in semana.get("days") or []:
                if _misma_sesion(dia, fecha, franja):
                    return {
                        "traza": traza.name,
                        "sport": dia.get("sport"),
                        "intensity_class": dia.get("intensity_class"),
                        "total_duration_minutes": dia.get("total_duration_minutes"),
                        "focus": _corto(dia.get("focus"), 120),
                        "session_sketch": _corto(dia.get("session_sketch")),
                    }
    return None


def _dia_del_plan(weekly_plan: Any, fecha: str, franja: str):
    from services.ai.langgraph.schemas.plan_outputs import WorkoutDay

    plan = weekly_plan if isinstance(weekly_plan, dict) else {}
    for semana in plan.get("weeks") or []:
        for dia in semana.get("days") or []:
            if _misma_sesion(dia, fecha, franja):
                try:
                    return WorkoutDay.model_validate(dia), None
                except Exception as e:  # noqa: BLE001 - un día ilegible se dice, no tumba la consulta
                    return None, f"El día está en su plan pero no se pudo leer: {str(e)[:200]}"
    return None, None


def _codificado_y_reloj(storage_dir: Path, weekly_plan: Any, fecha: str, franja: str):
    from services.ai.langgraph.nodes.weekly_planner_node import _minutos_de_los_pasos
    from services.garmin import upload_ledger

    dia, ilegible = _dia_del_plan(weekly_plan, fecha, franja)
    codificado = {"ilegible": ilegible} if ilegible else None
    if dia is not None:
        codificado = {
            "sport": dia.sport,
            "intensity_class": dia.intensity_class,
            "total_duration_minutes": dia.total_duration_minutes,
            "minutos_de_los_pasos": _minutos_de_los_pasos(dia.steps),
            "pasos": len(dia.steps or []),
            "focus": _corto(dia.focus, 120),
            "is_rest": dia.is_rest,
        }
    entrada = upload_ledger.por_sesion(upload_ledger.cargar(storage_dir)).get(upload_ledger.clave(fecha, franja))
    if entrada is None:
        reloj = {"estado": "sin_subir"}
    elif dia is not None and entrada.get("fingerprint") and entrada["fingerprint"] != upload_ledger.huella(dia):
        reloj = {"estado": "desactualizado", "subido": entrada.get("uploaded_at")}
    else:
        reloj = {"estado": "al_dia", "subido": entrada.get("uploaded_at")}
    return codificado, reloj


def _redes_que_le_corrigieron(user_id: int, dias: int = 14, ahora: datetime | None = None) -> list[dict]:
    from core import redes_ledger

    corte = (ahora or datetime.now(UTC)) - timedelta(days=dias)
    salida = []
    for linea in redes_ledger.leer():
        if linea.get("user_id") != f"u{user_id}" or str(linea.get("red", "")).startswith("_"):
            continue
        try:
            ts = datetime.fromisoformat(str(linea.get("ts")))
        except ValueError:
            continue
        if (ts if ts.tzinfo else ts.replace(tzinfo=UTC)) >= corte:
            salida.append({"ts": linea.get("ts"), "red": linea.get("red"), "detalle": _corto(linea.get("detalle"), 200)})
    return salida[-20:]


def _ultima_semana_del_expediente(storage_dir: Path) -> dict[str, Any] | None:
    try:
        lineas = (storage_dir / "track_record.jsonl").read_text(encoding="utf-8").strip().splitlines()
        return _lista_blanca(json.loads(lineas[-1]), _CLAVES_DE_LA_SEMANA) if lineas else None
    except (OSError, ValueError, IndexError):
        return None


def armar(storage_dir: Path, *, user_id: int, categoria: str, session_key: str | None = None,
          php: dict | None = None, errores_del_navegador: list | None = None,
          weekly_plan: Any = None, summary: Any = None, trabajos: list | None = None,
          sanear=None) -> dict[str, Any]:
    """El diagnóstico entero, con su triaje. `sanear` tapa correos y tokens del texto libre."""
    limpiar = sanear or (lambda t: t)
    diag: dict[str, Any] = {
        "version": 1,
        "categoria": categoria if categoria in CATEGORIAS else "otra",
        "armado": datetime.now(UTC).isoformat(timespec="seconds"),
        "php": _lista_blanca(php or {}, CLAVES_DE_PHP),
        "trabajos": [_lista_blanca(t, _CLAVES_DEL_TRABAJO) for t in (trabajos or [])[:3]],
        "redes_que_le_corrigieron": _redes_que_le_corrigieron(user_id),
        "ultima_semana_del_expediente": _ultima_semana_del_expediente(storage_dir),
        "zonas_de_fabrica": ((summary or {}).get("prescription") or {}).get("zonas_de_fabrica")
        if isinstance(summary, dict) else None,
    }
    errores = [e for e in (errores_del_navegador or []) if isinstance(e, (dict, str))]
    if len(errores) > MAX_ERRORES_DEL_NAVEGADOR:
        diag["errores_del_navegador_recortados"] = f"últimos {MAX_ERRORES_DEL_NAVEGADOR} de {len(errores)}"
    diag["errores_del_navegador"] = [
        {"cuando": _corto(e.get("cuando"), 40), "donde": _corto(e.get("donde"), 120),
         "mensaje": limpiar(_corto(e.get("mensaje"), 300))} if isinstance(e, dict)
        else {"mensaje": limpiar(_corto(e, 300))}
        for e in errores[-MAX_ERRORES_DEL_NAVEGADOR:]
    ]
    sesion = leer_clave_de_sesion(session_key)
    if sesion:
        fecha, franja = sesion
        codificado, reloj = _codificado_y_reloj(storage_dir, weekly_plan, fecha, franja)
        diag["sesion"] = {
            "fecha": fecha, "franja": franja,
            "decidido": _decidido(storage_dir, fecha, franja),
            "codificado": codificado,
            "reloj": reloj,
        }
    diag["triaje"] = proponer(diag)
    return diag
