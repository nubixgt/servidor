"""La salud de la app, para el administrador (Beta 3.0, F1). Sin SSH.

Hasta aquí, saber por qué falló una generación exigía entrar al servidor y leer
`_runtime/jobs/<id>/worker.log` a mano (02, "Dónde está el log de una generación").
Estas funciones lo leen para el panel, con tres reglas:

- **Nunca `spec.json`**: lleva el perfil entero del atleta (y la contraseña cifrada de
  Garmin durante un instante). Del trabajo sólo sale lo que dice su `status.json`.
- **Saneado**: correos, contraseñas, tokens y claves se tapan antes de salir del servicio.
- **Lo recortado se dice**: "últimas 200 de 3.412 líneas", nunca un extracto mudo.

Son funciones puras sobre carpetas, para poder probarlas con un directorio temporal.
Las rutas viven en main.py.
"""

from __future__ import annotations

import os
import re
from collections.abc import Iterable
from datetime import UTC, datetime, timedelta
from pathlib import Path
from typing import Any

from bridge import jobs as _jobs

# --- Saneado ----------------------------------------------------------------

_CORREO = re.compile(r"[\w.+-]+@[\w-]+\.[\w.-]+")
_BEARER = re.compile(r"(?i)bearer\s+[A-Za-z0-9._\-]+")
_CLAVE_CON_NOMBRE = re.compile(
    r"(?i)((?:garmin_)?password|contrase[nñ]a|api[_-]?key|token|secret)(\"?\s*[:=]\s*\"?)([^\s\",}]+)")
_CLAVES_DE_PROVEEDOR = re.compile(r"\b(sk-[A-Za-z0-9_\-]{8,}|AIza[0-9A-Za-z_\-]{20,}|gho_[A-Za-z0-9]{10,})")
# Un churro largo de base64 o hexadecimal casi siempre es un token o un blob cifrado.
_CHURRO = re.compile(r"\b[A-Za-z0-9+/=_\-]{40,}\b")


def sanear(texto: str) -> str:
    """Tapa lo que nunca debe salir del servicio. Mejor tapar de más que de menos."""
    t = _BEARER.sub("Bearer ***", texto or "")
    t = _CLAVE_CON_NOMBRE.sub(lambda m: f"{m.group(1)}{m.group(2)}***", t)
    t = _CLAVES_DE_PROVEEDOR.sub("***", t)
    t = _CORREO.sub("***@***", t)
    return _CHURRO.sub("***", t)


# --- Errores de proveedor -----------------------------------------------------

def _codigo(*codigos: str) -> str:
    """Un código HTTP sólo cuenta con su contexto ("Error code: 429", "status 401",
    "429 Too Many Requests"), nunca suelto: los milisegundos de la hora del log
    ("17:36:48,401") marcaron como "clave rechazada" un macrociclo que salió bien."""
    cods = "|".join(codigos)
    return rf"(?:error code|status(?:_code)?|http)\W{{0,3}}(?:{cods})\b|\b(?:{cods}) [A-Z][a-z]"


_PROVEEDOR = (
    ("sin_creditos", re.compile(r"(?i)no credits remaining|insufficient_quota|credit balance is too low")),
    ("limite_429", re.compile(r"(?i)" + _codigo("429") + r"|rate.?limit|too many requests|RESOURCE_EXHAUSTED")),
    ("credencial", re.compile(r"(?i)" + _codigo("401", "403")
                              + r"|invalid.?api.?key|authentication_error|PERMISSION_DENIED")),
    ("sobrecargado", re.compile(r"(?i)" + _codigo("503", "529") + r"|overloaded|\bUNAVAILABLE\b")),
    ("tiempo_agotado", re.compile(r"(?i)timed? ?out|timeout|deadline exceeded")),
)
_HORA_DEL_LOG = re.compile(r"^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:[,.]\d+)?")
_LINEA_DE_ERROR = re.compile(r"(?i)error|exception|traceback|failed|fall[oó]|timeout|timed out")


def error_de_proveedor(texto: str) -> str | None:
    """Qué le pasó al proveedor, si se reconoce en una línea de error del texto. None si no."""
    for linea in (texto or "").splitlines():
        linea = _HORA_DEL_LOG.sub("", linea)
        if not _LINEA_DE_ERROR.search(linea):
            continue
        for clave, patron in _PROVEEDOR:
            if patron.search(linea):
                return clave
    return None


# --- Trabajos -----------------------------------------------------------------

def _inicio(job_id: str) -> datetime | None:
    """El id del trabajo es su marca de tiempo (%Y%m%d%H%M%S%f), en hora local del servidor."""
    try:
        return datetime.strptime(job_id[:20], "%Y%m%d%H%M%S%f")
    except ValueError:
        return None


def _cola(path: Path, lineas: int) -> tuple[list[str], int]:
    try:
        todas = path.read_text(encoding="utf-8", errors="replace").splitlines()
    except OSError:
        return [], 0
    return todas[-lineas:], len(todas)


def _estado(status: dict, job_dir: Path, ahora: float | None) -> str:
    if status.get("queued"):
        return "en_cola"
    if status.get("done"):
        return "error" if status.get("error") else "terminado"
    if status.get("awaiting_answers"):
        return "esperando_respuestas"
    return "colgado" if _jobs.is_stale(job_dir, ahora) else "en_curso"


def _costo(user_id: str | None, kind: str, desde: datetime | None, hasta: datetime | None,
           libro: list[dict]) -> float | None:
    """El gasto del libro de costos de ese atleta y tipo dentro de la ventana del trabajo."""
    if not user_id or desde is None or hasta is None or kind not in ("week", "season"):
        return None
    total, hubo = 0.0, False
    for linea in libro:
        if linea.get("user_id") != f"u{user_id}" or linea.get("kind") != kind:
            continue
        try:
            ts = datetime.fromisoformat(str(linea.get("ts"))).astimezone().replace(tzinfo=None)
        except ValueError:
            continue
        if desde - timedelta(seconds=30) <= ts <= hasta + timedelta(seconds=120):
            total += float(linea.get("cost_usd") or 0)
            hubo = True
    return round(total, 4) if hubo else None


def trabajos(raices: dict[str, Path], limite: int = 60, libro: Iterable[dict] | None = None,
             ahora: float | None = None) -> list[dict[str, Any]]:
    """Los trabajos más recientes de todas las colas, del más nuevo al más viejo."""
    libro = list(libro or [])
    dirs = []
    for tipo_por_defecto, raiz in raices.items():
        # Sin status.json no hay trabajo que enseñar: es una carpeta que la limpieza vació
        # y no pudo quitar (en local, 56 de 64; OneDrive retiene la carpeta). Leída como
        # trabajo, salía "colgado" y el panel decía "45 trabajos parecen colgados".
        dirs.extend((d, tipo_por_defecto) for d in _jobs.job_dirs(raiz) if (d / "status.json").exists())
    dirs.sort(key=lambda x: x[0].name, reverse=True)
    salida = []
    for job_dir, tipo_por_defecto in dirs[:limite]:
        status = _jobs.read_status(job_dir) or {}
        inicio = _inicio(job_dir.name)
        try:
            fin = datetime.fromtimestamp((job_dir / "status.json").stat().st_mtime)
        except OSError:
            fin = None
        user_id = _jobs.job_user(job_dir, status)
        kind = str(status.get("kind") or tipo_por_defecto)
        error = sanear(str(status.get("error") or ""))[:300]
        estado = _estado(status, job_dir, ahora)
        # Sólo de lo que falló: un 429 que se reintentó y salió bien no es una alerta.
        proveedor = None
        if estado in ("error", "colgado"):
            cola_log, _total = _cola(job_dir / "worker.log", 300)
            proveedor = error_de_proveedor(error + "\n" + "\n".join(cola_log))
        salida.append({
            "job_id": job_dir.name,
            "kind": kind,
            "user_id": user_id,
            "estado": estado,
            "progreso": status.get("progress"),
            "texto": sanear(str(status.get("status_text") or ""))[:160],
            "error": error,
            "proveedor": proveedor,
            "inicio": inicio.isoformat(timespec="seconds") if inicio else None,
            "duracion_s": round((fin - inicio).total_seconds()) if inicio and fin and fin >= inicio else None,
            "costo_usd": _costo(user_id, kind, inicio, fin, libro),
            "tiene_log": (job_dir / "worker.log").exists(),
        })
    return salida


_ID_DE_TRABAJO = re.compile(r"^\d{14,22}$")


def log_del_trabajo(raices: Iterable[Path], job_id: str, lineas: int = 200) -> dict[str, Any] | None:
    """El final del `worker.log` de un trabajo, saneado y diciendo cuánto se recortó."""
    if not _ID_DE_TRABAJO.match(job_id or ""):
        return None
    lineas = max(20, min(2000, int(lineas or 200)))
    for raiz in raices:
        path = raiz / job_id / "worker.log"
        if path.exists():
            cola, total = _cola(path, lineas)
            return {
                "job_id": job_id,
                "lineas": [sanear(x) for x in cola],
                "total": total,
                "recortado": total > len(cola),
                "aviso": f"Mostrando las últimas {len(cola)} de {total} líneas." if total > len(cola) else "",
            }
    return None


# --- Servicio -------------------------------------------------------------------

def _tamano(path: Path) -> int:
    if path.is_file():
        try:
            return path.stat().st_size
        except OSError:
            return 0
    total = 0
    for raiz, _dirs, archivos in os.walk(path):
        for nombre in archivos:
            try:
                total += os.path.getsize(os.path.join(raiz, nombre))
            except OSError:
                continue
    return total


def salud(jobs_dir: Path, upload_jobs: Path, runtime_dir: Path, libros: dict[str, Path],
          desplegado: Path | None = None) -> dict[str, Any]:
    """Si el servicio vive y cómo va la cola. Lo que ocupa el disco va en MB."""
    en_curso = _jobs.running_jobs(jobs_dir)
    en_cola = _jobs.queued_jobs(jobs_dir)
    desplegado_en = None
    if desplegado is not None and desplegado.exists():
        desplegado_en = datetime.fromtimestamp(desplegado.stat().st_mtime).isoformat(timespec="seconds")
    return {
        "vivo": True,
        "ahora": datetime.now(UTC).isoformat(timespec="seconds"),
        "generaciones_en_curso": len(en_curso),
        "generaciones_en_cola": len(en_cola),
        "subidas_en_curso": len(_jobs.running_jobs(upload_jobs)),
        "max_simultaneas": _jobs.max_concurrent(),
        "runtime_mb": round(_tamano(runtime_dir) / 1_048_576, 1),
        "libros_kb": {nombre: round(_tamano(p) / 1024, 1) for nombre, p in libros.items()},
        "desplegado_en": desplegado_en,
    }


def generaciones_en_ventana(libro: Iterable[dict], dias: int, ahora: datetime | None = None,
                            desde: datetime | None = None) -> int:
    """Cuántas semanas y macrociclos se pagaron en la ventana (del libro de costos).

    `desde` es cuándo empezó a medirse lo que se compara (el libro de redes): una
    generación de antes no pudo anotar nada, y contarla hace que una red parezca inútil.
    """
    corte = (ahora or datetime.now(UTC)) - timedelta(days=dias)
    if desde is not None:
        corte = max(corte, desde if desde.tzinfo else desde.replace(tzinfo=UTC))
    n = 0
    for linea in libro:
        if linea.get("kind") not in ("week", "season"):
            continue
        try:
            ts = datetime.fromisoformat(str(linea.get("ts")))
        except ValueError:
            continue
        if ts.tzinfo is None:
            ts = ts.replace(tzinfo=UTC)
        if ts >= corte:
            n += 1
    return n
