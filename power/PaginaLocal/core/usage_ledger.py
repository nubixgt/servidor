"""Lo que cada atleta ha costado en APIs, en un libro que nadie puede borrar.

## Por qué existe, y por qué NO vive en data/storage/

Hasta v7 el costo de una generación se escribía en
`data/storage/<perfil>/summary.json`, y ahí lo leía la UI como *"Costo de la
última generación"*. Dos consecuencias, las dos reales:

1. **La corrida siguiente lo pisaba.** La temporada del 2026-08-26 costó
   $0.2077 y la semana la sobrescribió media hora después. El atleta preguntó
   por qué veía el costo de la semana y no el del plan: porque ya no estaba.
2. **Borrar el plan borraba la cuenta.** `delete_current_generation` y
   `DELETE /api/profiles/{name}` hacen `rmtree` sobre el árbol del perfil. El
   dinero ya se gastó en Anthropic, OpenAI y Google; la factura llega igual.
   Un libro contable que se borra cuando el cliente borra sus datos no es un
   libro contable.

Así que este archivo vive en `data/billing/usage.jsonl`, **fuera** del árbol
que se borra, y **sólo se le agregan líneas**. Nunca se reescribe una, nunca
se borra una. Un perfil eliminado deja un evento `profile_deleted` en el
libro; sus líneas anteriores siguen ahí y sus totales siguen sumando.

## Qué es y qué no es este archivo

Es un registro de **costo de proveedor**: lo que la app le pagó a Anthropic,
OpenAI y Google por cuenta de un atleta. No es una factura, no calcula
impuestos, no sabe de precios de suscripción. Convertir esto en lo que se le
cobra al atleta es una decisión de negocio que se toma con estos números
delante, no dentro de este módulo.

Tampoco es una fuente de verdad frente al proveedor: los tokens los reporta el
propio SDK del modelo, y el precio sale de
`ModelSelector.MODEL_PRICING_USD_PER_MILLION`, que es una tabla que mantenemos
a mano. Si un proveedor cambia su precio y la tabla no, el libro miente con
toda confianza. Por eso cada línea guarda **los tokens además del costo**: los
tokens son un hecho medido, el costo es una multiplicación nuestra, y con los
tokens guardados se puede recalcular el pasado entero cuando la tabla cambie.

## Concurrencia

Una línea por escritura, en modo append, y un lock de proceso. El modo append
de POSIX y de Windows garantiza que una escritura corta no se entrelace con
otra; el lock cubre el caso de dos hilos de FastAPI generando a la vez. Si
esto alguna vez corre en varios procesos, esto pasa a ser una base de datos —
no le pongas un parche.
"""

from __future__ import annotations

import json
import logging
import os
import threading
from collections import defaultdict
from collections.abc import Callable, Iterable, Iterator
from datetime import UTC, datetime, timedelta
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)

_PROJECT_ROOT = Path(__file__).resolve().parents[1]
LEDGER_PATH = _PROJECT_ROOT / "data" / "billing" / "usage.jsonl"

_write_lock = threading.Lock()

# Quien esta gastando, cuando el que llama no lo sabe.
#
# `record_run` se invoca desde dentro de las tareas de generacion, que reciben
# un perfil y no una sesion. Con una sola instalacion eso bastaba: el perfil
# ERA la identidad. Con cuentas de verdad ya no -- dos atletas pueden llamarse
# igual, y cobrarle a un nombre no es cobrarle a nadie.
#
# El puente (AiService/bridge/paginalocal.py) instala aqui un resolvedor que
# lee su contextvar de usuario, igual que ya reemplaza el directorio de
# almacenamiento. Sin puente vale None y todo sigue como antes.
USER_ID_RESOLVER: Callable[[], str | None] | None = None


def _resolve_user_id(explicito: str | None) -> str | None:
    if explicito:
        return explicito
    if USER_ID_RESOLVER is None:
        return None
    try:
        return USER_ID_RESOLVER() or None
    except Exception:  # noqa: BLE001 - el cobro nunca puede tumbar una generacion
        logger.exception("USER_ID_RESOLVER fallo; la linea se anota sin usuario")
        return None

# Los tipos de gasto que la app puede registrar. No es una enum cerrada por
# capricho: si aparece un tipo nuevo y nadie lo añade aquí, el gasto se
# registra igual (con su nombre tal cual) en vez de perderse. Un gasto sin
# clasificar es un problema de informe; un gasto sin registrar es dinero que
# desaparece.
KIND_SEASON = "season"
KIND_WEEK = "week"
KIND_CHAT = "chat"
KIND_HEALTH_INTAKE = "health_intake"  # el Doctor se retiró (Beta 3.0); se conserva para leer líneas antiguas del libro
# Beta 2.1, S4: ordenar la rutina de gimnasio que el atleta pega (services/ai/rutina_de_fuerza.py).
KIND_STRENGTH_ROUTINE = "strength_routine"
# Beta 3.0, F9: «Reordenar cuaderno» (services/ai/reordenar_cuaderno.py).
KIND_NOTEBOOK_REORDER = "notebook_reorder"
KIND_REPLAY = "replay"
# `detail` de una semana que generó el cron (AutoWeekService), sin nadie delante.
DETALLE_AUTOMATICA = "automatica"
KIND_PROFILE_DELETED = "profile_deleted"

# Gasto que NO es del atleta aunque se haya hecho contra sus datos. El banco
# de pruebas replica prompts archivados para decidir qué modelo usa el
# producto; eso es desarrollo, y cargárselo al atleta le duplicaba el costo
# que ve (medido: $0.6862 de banco contra $0.7063 de entrenamiento real).
# El informe del operador SÍ lo cuenta -- lo pagó igual.
_OPERATOR_ONLY_KINDS = frozenset({KIND_REPLAY})

_KIND_LABELS_ES = {
    KIND_SEASON: "Plan de temporada",
    KIND_WEEK: "Semana de entrenamiento",
    KIND_CHAT: "Chat con el coach",
    KIND_HEALTH_INTAKE: "Cuestionario de salud",
    KIND_STRENGTH_ROUTINE: "Rutina de fuerza",
    KIND_NOTEBOOK_REORDER: "Reordenar cuaderno",
    KIND_REPLAY: "Banco de pruebas",
    KIND_PROFILE_DELETED: "Perfil eliminado",
}


def label_for_kind(kind: str) -> str:
    return _KIND_LABELS_ES.get(kind, kind)


def _now() -> str:
    return datetime.now(UTC).astimezone().isoformat(timespec="seconds")


def canonical_profile(profile: str) -> str:
    """Una persona, una clave.

    El nombre visible ("Jose Ines Castillo") y el de su carpeta
    ("Jose_Ines_Castillo") son el mismo atleta, y el código llega aquí con uno
    o con otro según por dónde entre: el runtime pasa `profile_dict["name"]`,
    el sembrado histórico pasa el nombre del directorio. Sin normalizar, el
    libro le cobra a dos personas lo que gastó una.

    Es exactamente la trampa que `_profile_storage_dir` documenta haber
    pisado ya una vez en producción, con la misma función de saneado, y por
    eso se usa la misma: si alguna vez cambia el criterio, cambia para el
    disco y para la contabilidad a la vez.
    """
    from services.ai.utils.plan_storage import sanitize_storage_name

    return sanitize_storage_name(profile or "")


def _append(record: dict[str, Any]) -> None:
    """Una línea, en append, con lock. El único camino de escritura."""
    try:
        LEDGER_PATH.parent.mkdir(parents=True, exist_ok=True)
        line = json.dumps(record, ensure_ascii=False, default=str)
        with _write_lock:
            with open(LEDGER_PATH, "a", encoding="utf-8") as handle:
                handle.write(line + "\n")
                handle.flush()
                os.fsync(handle.fileno())
    except OSError:
        # Nunca tumbar una generación que el atleta ya pagó porque no se pudo
        # anotar. Se pierde la línea y queda el grito en el log.
        logger.exception("NO SE PUDO REGISTRAR EL COSTO -- linea perdida: %s", record)


def _model_names(models: Any) -> list[str]:
    """Los nombres de modelo de un nodo, venga como venga.

    El pipeline entrega `[{"name": "gpt-5.2", "cost_usd": ...}]` y `record_call`
    entregaba `["claude-haiku-4-5"]`. La diferencia tumbaba la anotación con un
    `AttributeError` -- y como anotar ocurre DESPUÉS de generar la respuesta,
    el atleta pagaba la llamada y recibía un error. Se acepta cualquiera de las
    dos formas en vez de exigir una: un desacuerdo de formato entre dos
    funciones de este archivo no puede costar una respuesta ya pagada.
    """
    nombres = []
    for modelo in models or []:
        if isinstance(modelo, str):
            nombres.append(modelo)
        elif isinstance(modelo, dict) and modelo.get("name"):
            nombres.append(modelo["name"])
    return nombres


def record_run(
    profile: str,
    kind: str,
    cost_summary: dict[str, Any] | None,
    *,
    mode: str | None = None,
    user_id: str | None = None,
    detail: str | None = None,
    ts: str | None = None,
) -> dict[str, Any]:
    """Anota lo que costó una corrida. Devuelve la línea escrita.

    `cost_summary` es la forma que ya produce el pipeline (`total_cost_usd`,
    `total_tokens`, `agents`), que es también la que guarda `summary.json`.
    Se acepta vacío: una corrida que falló antes de llamar a ningún modelo
    costó cero, y anotar ese cero es más útil que no anotar nada — deja el
    intento en el historial.
    """
    summary = cost_summary or {}
    nodes = summary.get("agents") or summary.get("cost_breakdown") or []
    record = {
        # `ts` es CUÁNDO SE GASTÓ, no cuándo se anotó. Casi siempre coinciden,
        # y el único caso en que no es el sembrado histórico
        # (scripts/backfill_usage_ledger.py), donde ponerle la hora de la
        # anotación metería meses de gasto viejo en el mes en curso.
        "ts": ts or _now(),
        # Clave canónica para agrupar, y el nombre tal cual llegó para
        # enseñarlo. Guardar sólo el canónico dejaría los informes hablando
        # de "Jose_Ines_Castillo".
        "profile": canonical_profile(profile),
        "profile_label": profile,
        # Reservado para cuando exista autenticación: entonces el perfil deja
        # de ser la identidad y esta columna pasa a serlo. Se escribe desde
        # ahora para que el libro viejo no quede sin la clave nueva.
        "user_id": _resolve_user_id(user_id),
        "kind": kind,
        "mode": mode,
        "cost_usd": round(float(summary.get("total_cost_usd") or 0.0), 6),
        "tokens": int(summary.get("total_tokens") or 0),
        "input_tokens": sum(int(n.get("input_tokens") or 0) for n in nodes),
        "output_tokens": sum(int(n.get("output_tokens") or 0) for n in nodes),
        # Cuantos tokens de entrada salieron de la cache y cuantos la
        # escribieron. Se guardan para poder comprobar DESDE FUERA que la
        # cache del chat sigue acertando: sin esto, una cache que dejara de
        # funcionar se veria solo como una factura que sube, y meses despues.
        # Cero en todo lo que no cachea, que es todo lo demas.
        "cache_read_tokens": sum(int(n.get("cache_read_tokens") or 0) for n in nodes),
        "cache_creation_tokens": sum(int(n.get("cache_creation_tokens") or 0) for n in nodes),
        "nodes": [
            {
                "name": n.get("name"),
                "cost_usd": round(float(n.get("cost_usd") or 0.0), 6),
                "tokens": int(n.get("tokens") or 0),
                "models": _model_names(n.get("models")),
            }
            for n in nodes
        ],
        "detail": detail,
    }
    _append(record)
    return record


def record_call(
    profile: str,
    kind: str,
    usage_metadata: dict[str, Any] | None,
    *,
    mode: str | None = None,
    user_id: str | None = None,
    detail: str | None = None,
) -> dict[str, Any]:
    """Igual, para una llamada suelta a un modelo (el chat, el cuestionario).

    Toma directamente el `usage_metadata` de LangChain, que es lo que esos
    caminos tienen a mano — no pasan por el resumen de costos del pipeline.
    """
    from services.ai.model_config import ModelSelector

    nodes, total_cost, total_in, total_out, total_tokens = [], 0.0, 0, 0, 0
    cache_read_total = cache_write_total = 0
    for model_name, usage in (usage_metadata or {}).items():
        input_tokens = int(usage.get("input_tokens") or 0)
        output_tokens = int(usage.get("output_tokens") or 0)
        # LangChain mete el desglose de cache aqui cuando el proveedor lo
        # reporta. Si no esta, todo sale a cero y el precio es el de siempre --
        # que es lo que pasa en todo lo que no cachea.
        #
        # OJO CON `cache_creation`. Con langchain-anthropic 1.6.1 esa clave
        # llega a CERO en una escritura de cache, y los tokens escritos
        # aparecen en `ephemeral_5m_input_tokens`. Verificado contra la API
        # real el 2026-09-03, primer mensaje de una conversacion:
        #
        #     {"input_tokens": 22673, "output_tokens": 117,
        #      "input_token_details": {"cache_read": 0, "cache_creation": 0,
        #                              "ephemeral_5m_input_tokens": 22655,
        #                              "ephemeral_1h_input_tokens": 0}}
        #
        # Fiarse solo de `cache_creation` habria cobrado esos 22.655 tokens a
        # tarifa normal en vez de a 1,25x: un 25% de menos, en silencio y en el
        # unico sitio donde el error se descubre leyendo la factura. Se leen
        # las dos, con la estandar por delante para que esto siga siendo
        # correcto si una version futura la rellena.
        detalles = usage.get("input_token_details") or {}
        cache_read = int(detalles.get("cache_read") or 0)
        efimera_5m = int(detalles.get("ephemeral_5m_input_tokens") or 0)
        efimera_1h = int(detalles.get("ephemeral_1h_input_tokens") or 0)
        cache_write = int(detalles.get("cache_creation") or 0) or efimera_5m
        cost = ModelSelector.estimate_cost_usd(
            model_name, input_tokens, output_tokens,
            cache_read_tokens=cache_read,
            cache_creation_tokens=cache_write,
            cache_creation_1h_tokens=efimera_1h,
        )
        total_cost += cost
        total_in += input_tokens
        total_out += output_tokens
        cache_read_total += cache_read
        cache_write_total += cache_write + efimera_1h
        total_tokens += int(usage.get("total_tokens") or (input_tokens + output_tokens))
        nodes.append({
            "name": kind,
            "cost_usd": round(cost, 6),
            "tokens": input_tokens + output_tokens,
            "input_tokens": input_tokens,
            "output_tokens": output_tokens,
            # Se guardan para poder comprobar DESDE FUERA que la cache esta
            # funcionando. Sin esto, una cache que dejara de acertar se veria
            # solo como una factura que sube, y meses despues.
            "cache_read_tokens": cache_read,
            "cache_creation_tokens": cache_write + efimera_1h,
            "models": [model_name],
        })

    return record_run(
        profile,
        kind,
        {"total_cost_usd": total_cost, "total_tokens": total_tokens, "agents": nodes},
        mode=mode,
        user_id=user_id,
        detail=detail,
    )


def record_profile_deleted(profile: str, *, user_id: str | None = None) -> dict[str, Any]:
    """El perfil se fue; su gasto se queda.

    Esto es lo que hace que el informe pueda decir "Ana (perfil eliminado):
    $3.40" en vez de perder tres meses de gasto real el día que alguien pulsa
    borrar.
    """
    return record_run(profile, KIND_PROFILE_DELETED, None, user_id=user_id)


def read_all() -> Iterator[dict[str, Any]]:
    """Cada línea del libro. Una línea corrupta se salta y se avisa: un byte
    malo a mitad del archivo no puede costar la contabilidad entera."""
    if not LEDGER_PATH.exists():
        return
    with open(LEDGER_PATH, encoding="utf-8") as handle:
        for number, line in enumerate(handle, start=1):
            line = line.strip()
            if not line:
                continue
            try:
                yield json.loads(line)
            except json.JSONDecodeError:
                logger.warning("Linea %d del libro de costos ilegible, se omite", number)


UMBRAL_DE_ALERTA_USD = 1.0


def gasto_por_dia(records: Iterable[dict[str, Any]] | None = None, *, dias: int = 7,
                  umbral: float = UMBRAL_DE_ALERTA_USD, hoy: str | None = None) -> list[dict[str, Any]]:
    """Los atletas que gastaron más de `umbral` en un mismo día (Beta 3.0, F2).

    Es la alerta del "agujero negro" (16 planes en un día en la Beta 1): el tope de
    versiones lo frena, pero la semana automática no cuenta para el tope y el chat no
    tiene ninguno. El día es el de la marca de tiempo tal cual se escribió (la hora del
    servidor); el banco de pruebas (`replay`) no es gasto de un atleta y no entra.

    Devuelve, del día más reciente al más viejo, `{user_id, dia, cost_usd, usos}` con
    cuántas veces se usó cada herramienta, para saber si fue el chat o fueron planes.
    """
    registros = read_all() if records is None else records
    corte = None
    if hoy:
        try:
            corte = (datetime.strptime(hoy, "%Y-%m-%d") - timedelta(days=dias - 1)).strftime("%Y-%m-%d")
        except ValueError:
            corte = None
    por_dia: dict[tuple[str, str], dict[str, Any]] = {}
    for r in registros:
        kind = r.get("kind") or "?"
        if kind in _OPERATOR_ONLY_KINDS or kind == KIND_PROFILE_DELETED:
            continue
        dia = str(r.get("ts") or "")[:10]
        usuario = r.get("user_id")
        if not usuario or len(dia) != 10 or (corte and dia < corte):
            continue
        fila = por_dia.setdefault((usuario, dia), {"user_id": usuario, "dia": dia, "cost_usd": 0.0, "usos": {}})
        fila["cost_usd"] += float(r.get("cost_usd") or 0.0)
        fila["usos"][kind] = fila["usos"].get(kind, 0) + 1
    salida = [dict(f, cost_usd=round(f["cost_usd"], 4)) for f in por_dia.values() if f["cost_usd"] > umbral]
    return sorted(salida, key=lambda f: (f["dia"], f["cost_usd"]), reverse=True)


def _month_of(record: dict[str, Any]) -> str:
    return str(record.get("ts") or "")[:7]  # YYYY-MM


def _blank_totals() -> dict[str, Any]:
    return {"cost_usd": 0.0, "tokens": 0, "runs": 0, "by_kind": defaultdict(lambda: {"cost_usd": 0.0, "runs": 0})}


def _fold(records: Iterable[dict[str, Any]]) -> dict[str, Any]:
    totals = _blank_totals()
    for record in records:
        if record.get("kind") == KIND_PROFILE_DELETED:
            continue  # es un evento, no un gasto
        totals["cost_usd"] += float(record.get("cost_usd") or 0.0)
        totals["tokens"] += int(record.get("tokens") or 0)
        totals["runs"] += 1
        kind = totals["by_kind"][record.get("kind") or "?"]
        kind["cost_usd"] += float(record.get("cost_usd") or 0.0)
        kind["runs"] += 1
    totals["cost_usd"] = round(totals["cost_usd"], 6)
    totals["by_kind"] = {
        k: {"cost_usd": round(v["cost_usd"], 6), "runs": v["runs"], "label": label_for_kind(k)}
        for k, v in sorted(totals["by_kind"].items(), key=lambda kv: -kv[1]["cost_usd"])
    }
    return totals


def usage_for_profile(profile: str, *, recent: int = 20, include_operator_only: bool = False) -> dict[str, Any]:
    """Lo que este atleta ha costado: total, por tipo, por mes, y las últimas
    corridas. Es lo que se le enseña a él durante la beta.

    Por defecto excluye el gasto de desarrollo (el banco de pruebas): se hizo
    contra sus datos pero no es su entrenamiento, y contárselo le duplicaba la
    cifra. `include_operator_only=True` da la vista completa.
    """
    key = canonical_profile(profile)
    records = [r for r in read_all() if r.get("profile") == key]
    if not include_operator_only:
        records = [r for r in records if r.get("kind") not in _OPERATOR_ONLY_KINDS]
    totals = _fold(records)
    by_month: dict[str, dict[str, Any]] = {}
    for record in records:
        if record.get("kind") == KIND_PROFILE_DELETED:
            continue
        month = by_month.setdefault(_month_of(record), {"cost_usd": 0.0, "runs": 0})
        month["cost_usd"] = round(month["cost_usd"] + float(record.get("cost_usd") or 0.0), 6)
        month["runs"] += 1
    spend = [r for r in records if r.get("kind") != KIND_PROFILE_DELETED]
    return {
        "profile": next((r.get("profile_label") for r in reversed(records) if r.get("profile_label")), profile),
        "profile_key": key,
        "deleted": any(r.get("kind") == KIND_PROFILE_DELETED for r in records),
        "total_cost_usd": totals["cost_usd"],
        "total_tokens": totals["tokens"],
        "runs": totals["runs"],
        "by_kind": totals["by_kind"],
        "by_month": dict(sorted(by_month.items(), reverse=True)),
        "recent": list(reversed(spend[-recent:])),
        "first_seen": spend[0]["ts"] if spend else None,
        "last_seen": spend[-1]["ts"] if spend else None,
    }


def usage_for_user(user_id: str, *, recent: int = 20,
                   include_operator_only: bool = False) -> dict[str, Any]:
    """Lo mismo que `usage_for_profile`, pero por CUENTA en vez de por nombre.

    Es la vista que sirve para cobrar. El nombre del perfil no es una
    identidad -- dos atletas pueden llamarse igual, y uno puede cambiarse el
    nombre -- asi que facturar por nombre factura a quien no toca.

    Las lineas anteriores a que existieran las cuentas no llevan `user_id` y no
    aparecen aqui. Eso es deliberado: preferimos cobrar de menos por un gasto
    que no sabemos de quien fue, a cobrarselo al usuario equivocado.
    """
    quien = str(user_id or "").strip()
    if not quien:
        # Un id vacio casaba con las lineas SIN usuario, es decir: preguntar
        # por nadie devolvia el gasto de todos los que no se pudieron
        # atribuir. Es la version de cobrar al equivocado que mas facil pasa
        # desapercibida, porque el numero sale plausible.
        return {"user_id": "", "profile_label": "", "total_cost_usd": 0.0,
                "total_tokens": 0, "runs": 0, "by_kind": {}, "by_month": {},
                "recent": [], "first_seen": None, "last_seen": None}
    records = [r for r in read_all() if str(r.get("user_id") or "") == quien]
    if not include_operator_only:
        records = [r for r in records if r.get("kind") not in _OPERATOR_ONLY_KINDS]
    totals = _fold(records)
    by_month: dict[str, dict[str, Any]] = {}
    for record in records:
        if record.get("kind") == KIND_PROFILE_DELETED:
            continue
        month = by_month.setdefault(_month_of(record), {"cost_usd": 0.0, "runs": 0})
        month["cost_usd"] = round(month["cost_usd"] + float(record.get("cost_usd") or 0.0), 6)
        month["runs"] += 1
    gasto = [r for r in records if r.get("kind") != KIND_PROFILE_DELETED]
    etiquetas = [r.get("profile_label") for r in gasto if r.get("profile_label")]
    return {
        "user_id": quien,
        # El nombre mas reciente que uso, solo para enseñarlo.
        "profile_label": etiquetas[-1] if etiquetas else "",
        # Los mismos nombres que `usage_for_profile`: quien consuma esto no
        # deberia tener que saber por cual de las dos vistas llego.
        "total_cost_usd": totals["cost_usd"],
        "total_tokens": totals["tokens"],
        "runs": totals["runs"],
        "by_kind": totals["by_kind"],
        "by_month": dict(sorted(by_month.items(), reverse=True)),
        "recent": list(reversed(gasto[-recent:])),
        "first_seen": gasto[0].get("ts") if gasto else None,
        "last_seen": gasto[-1].get("ts") if gasto else None,
    }


def usage_by_profile() -> dict[str, Any]:
    """El informe del operador: cada atleta, incluidos los eliminados.

    Los perfiles borrados se marcan pero siguen sumando al total, porque el
    proveedor ya cobró por ellos.
    """
    grouped: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for record in read_all():
        grouped[record.get("profile") or "?"].append(record)

    rows = []
    for key, records in grouped.items():
        totals = _fold(records)
        spend = [r for r in records if r.get("kind") != KIND_PROFILE_DELETED]
        label = next((r.get("profile_label") for r in reversed(records) if r.get("profile_label")), key)
        rows.append({
            "profile": label,
            "profile_key": key,
            "deleted": any(r.get("kind") == KIND_PROFILE_DELETED for r in records),
            "cost_usd": totals["cost_usd"],
            "tokens": totals["tokens"],
            "runs": totals["runs"],
            "by_kind": totals["by_kind"],
            "last_seen": spend[-1]["ts"] if spend else None,
        })
    rows.sort(key=lambda row: -row["cost_usd"])

    all_records = [r for records in grouped.values() for r in records]
    by_month: dict[str, dict[str, Any]] = {}
    for record in all_records:
        if record.get("kind") == KIND_PROFILE_DELETED:
            continue
        month = by_month.setdefault(_month_of(record), {"cost_usd": 0.0, "runs": 0})
        month["cost_usd"] = round(month["cost_usd"] + float(record.get("cost_usd") or 0.0), 6)
        month["runs"] += 1

    return {
        "profiles": rows,
        "total_cost_usd": round(sum(row["cost_usd"] for row in rows), 6),
        "total_runs": sum(row["runs"] for row in rows),
        "by_month": dict(sorted(by_month.items(), reverse=True)),
        "ledger_path": str(LEDGER_PATH),
    }


def usage_global(*, include_operator_only: bool = False) -> dict[str, Any]:
    """El gasto de TODA la instalación, desglosado por herramienta.

    Es la vista del panel de administración: cuánto ha costado cada
    herramienta (el chat, el doctor, la temporada, la semana), cuánto por mes,
    y cuánto por cuenta. Las dos vistas que ya existían responden "cuánto ha
    costado ESTE atleta"; ninguna respondía "en qué se va el dinero".

    Importa porque las cuatro herramientas tienen economías distintas y sólo
    una es ilimitada: una temporada y una semana se generan cuando el atleta lo
    pide, pero el chat corre tantas veces como le apetezca escribir. Ver esa
    proporción es lo que deja decidir con datos si el chat hay que cachearlo,
    acotarlo o cobrarlo aparte.

    `by_kind` trae también `cache_read_tokens` y `cache_creation_tokens`, que
    es como se comprueba DESDE FUERA que la caché del chat sigue acertando.
    Una caché que deja de funcionar no da ningún error: sólo sube la factura.

    El banco de pruebas (`replay`) queda FUERA por defecto, igual que en las
    otras dos vistas: es gasto de desarrollo, no de un atleta, y meterlo aquí
    inflaría el coste por herramienta con corridas que nadie pidió.
    """
    records = [r for r in read_all() if r.get("kind") != KIND_PROFILE_DELETED]
    if not include_operator_only:
        records = [r for r in records if r.get("kind") not in _OPERATOR_ONLY_KINDS]

    totals = _fold(records)

    # El desglose por herramienta, enriquecido con lo que `_fold` no mira.
    por_tipo: dict[str, dict[str, Any]] = {}
    for kind, resumen in totals["by_kind"].items():
        de_este_tipo = [r for r in records if (r.get("kind") or "?") == kind]
        automaticas = [r for r in de_este_tipo if r.get("detail") == DETALLE_AUTOMATICA]
        runs = resumen["runs"] or 1
        por_tipo[kind] = {
            **resumen,
            "tokens": sum(int(r.get("tokens") or 0) for r in de_este_tipo),
            "cache_read_tokens": sum(int(r.get("cache_read_tokens") or 0) for r in de_este_tipo),
            "cache_creation_tokens": sum(int(r.get("cache_creation_tokens") or 0) for r in de_este_tipo),
            # Lo que cuesta UNA vez que se usa esa herramienta. Es el número que
            # de verdad decide si algo es sostenible: un total alto repartido
            # entre muchas corridas baratas no es el mismo problema que uno
            # concentrado en pocas caras.
            "avg_cost_usd": round(resumen["cost_usd"] / runs, 6),
            # Las semanas que generó el cron: van dentro de `runs` y `cost_usd` (se pagaron
            # igual) y se dicen aparte, porque ningún contador de clics las ve.
            "automatic_runs": len(automaticas),
            "automatic_cost_usd": round(sum(float(r.get("cost_usd") or 0.0) for r in automaticas), 6),
        }

    by_month: dict[str, dict[str, Any]] = {}
    por_usuario: dict[str, dict[str, Any]] = {}
    for record in records:
        coste = float(record.get("cost_usd") or 0.0)
        mes = by_month.setdefault(_month_of(record), {"cost_usd": 0.0, "runs": 0})
        mes["cost_usd"] = round(mes["cost_usd"] + coste, 6)
        mes["runs"] += 1

        # Las líneas anteriores a que existieran las cuentas no llevan
        # `user_id`. Se agrupan aparte en vez de repartirse: atribuir un gasto
        # a quien no fue es peor que dejarlo sin atribuir.
        quien = str(record.get("user_id") or "") or "sin_cuenta"
        fila = por_usuario.setdefault(quien, {"user_id": quien, "label": "", "cost_usd": 0.0, "runs": 0})
        fila["cost_usd"] = round(fila["cost_usd"] + coste, 6)
        fila["runs"] += 1
        if record.get("profile_label"):
            fila["label"] = record["profile_label"]

    return {
        "total_cost_usd": totals["cost_usd"],
        "total_tokens": totals["tokens"],
        "total_runs": totals["runs"],
        "by_kind": por_tipo,
        "by_month": dict(sorted(by_month.items(), reverse=True)),
        "by_user": sorted(por_usuario.values(), key=lambda f: -f["cost_usd"]),
    }
