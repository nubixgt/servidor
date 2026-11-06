"""A quien pertenece cada trabajo en cola, y cual es el ultimo de un usuario.

Vive aparte de `main.py` a proposito: importar `main` arrastra
`bridge.paginalocal`, que carga PaginaLocal entera y necesita un `.env` con
claves de proveedor. Estas cuatro funciones no necesitan nada de eso, y son
justo las que no pueden fallar en silencio -- asi se pueden probar.

**La regla es una: un trabajo que no se puede atribuir es de NADIE.** El estado
que devuelven estas funciones lleva dentro `result`, que es el plan generado, y
el lado PHP guarda ese resultado en la fila del usuario que esta preguntando
(`PlanService::pollStatus` -> `saveWeek($userId, ...)`). Antes de v7.1 se
devolvia el trabajo mas reciente de cualquiera: dos atletas generando a la vez
y el plan de uno se archivaba como el del otro. Ante la duda se responde
"idle", nunca el trabajo de otro.
"""
from __future__ import annotations

import json
import logging
import os
import subprocess
import time
from pathlib import Path

logger = logging.getLogger("aiservice.jobs")


def read_status(job_dir: Path) -> dict | None:
    try:
        return json.loads((job_dir / "status.json").read_text("utf-8"))
    except (OSError, json.JSONDecodeError):
        return None


def job_dirs(root: Path) -> list[Path]:
    """Los trabajos, del mas reciente al mas antiguo. El nombre del directorio
    es una marca de tiempo, asi que ordenar por nombre ordena por fecha."""
    if not root.exists():
        return []
    return sorted((d for d in root.iterdir() if d.is_dir()), key=lambda d: d.name, reverse=True)


def job_user(job_dir: Path, status: dict | None) -> str | None:
    """El dueño del trabajo, o None si no se puede saber.

    `status.json` lo lleva desde v7.1. Los trabajos escritos antes solo lo
    tienen en `spec.json` -- y el worker de Garmin borra ese fichero en cuanto
    cifra la contraseña, asi que puede no haber ninguno de los dos.
    """
    if status and status.get("user_id") is not None:
        return str(status["user_id"])
    try:
        return str(json.loads((job_dir / "spec.json").read_text("utf-8"))["user_id"])
    except (OSError, json.JSONDecodeError, KeyError):
        return None


def newest_status(root: Path, user_id: int | str) -> dict | None:
    """El trabajo mas reciente DE ESTE USUARIO, o None si no tiene ninguno."""
    want = str(user_id)
    for d in job_dirs(root):
        s = read_status(d)
        if s and job_user(d, s) == want:
            return s
    return None


# --- La cola -------------------------------------------------------------
#
# Cada generacion es un PROCESO aparte que importa PaginaLocal y corre su
# propio grafo, pero las claves de API son UNAS SOLAS para todos. Medido sobre
# 18 generaciones reales, una semana cuesta ~8 llamadas y ~112k tokens de
# entrada repartidos entre Google, Anthropic y OpenAI -- y no llegan
# espaciadas: los tres summarizers salen a la vez, y despues los tres expertos.
# Diez atletas pulsando "generar" a la misma hora son treinta llamadas
# simultaneas al mismo proveedor.
#
# Por eso el limite es una COLA y no un candado. Una generacion tarda minutos y
# nadie la mira en directo: esperar cinco minutos mas no le cuesta nada al
# atleta, y una tanda de 429 le cuesta una generacion pagada. El candado global
# que habia antes hacia lo contrario -- no encolaba, rechazaba.

JOB_STALE_S = 25 * 60          # sin tocar status.json = worker muerto
DEFAULT_MAX_CONCURRENT = 3


def max_concurrent() -> int:
    try:
        return max(1, int(os.getenv("MAX_CONCURRENT_GENERATIONS", DEFAULT_MAX_CONCURRENT)))
    except ValueError:
        return DEFAULT_MAX_CONCURRENT


def is_stale(job_dir: Path, now: float | None = None) -> bool:
    try:
        edad = (now or time.time()) - (job_dir / "status.json").stat().st_mtime
    except OSError:
        return True
    return edad > JOB_STALE_S


def running_jobs(root: Path) -> list[Path]:
    """Los que estan corriendo de verdad: lanzados, sin terminar y con señales
    de vida. Un worker que murio sin escribir `done` no ocupa sitio para
    siempre -- si no, un fallo dejaria la cola bloqueada 25 minutos."""
    out = []
    for d in job_dirs(root):
        s = read_status(d)
        if not s or s.get("done") or s.get("queued"):
            continue
        # Esperando a que el atleta conteste las preguntas del coach SÍ ocupa
        # sitio. Hasta el 2026-09-15 no lo ocupaba, porque no llama a ningún
        # modelo mientras espera; pero el proceso sigue vivo con su memoria
        # (~200 MB medidos) y al reanudar no vuelve a mirar el tope. En el
        # servidor de 2 GB lo que se agota es la memoria, no las claves: con
        # MAX_CONCURRENT_GENERATIONS=2 y tres atletas con preguntas corrían
        # cinco grafos a la vez. La espera está acotada (ESPERA_RESPUESTAS_S,
        # diez minutos), así que el peor caso es una cola más lenta, no rota.
        if not is_stale(d):
            out.append(d)
    return out


def queued_jobs(root: Path) -> list[Path]:
    """Los que esperan, EL MAS ANTIGUO PRIMERO: la cola es FIFO, que es lo
    unico que un atleta puede entender y predecir."""
    espera = [d for d in job_dirs(root) if (read_status(d) or {}).get("queued")]
    return sorted(espera, key=lambda d: d.name)


def queue_position(root: Path, job_id: str) -> int:
    """1 = el siguiente en entrar. 0 = no esta en cola."""
    for i, d in enumerate(queued_jobs(root), 1):
        if d.name == job_id:
            return i
    return 0


def launch(job_dir: Path, python_bin: str, worker_py: Path) -> None:
    """Marca el trabajo como en marcha y arranca su proceso.

    El orden importa: primero se quita `queued` del estado y despues se lanza.
    Al reves, dos drenados concurrentes verian el mismo trabajo en cola y
    arrancarian dos procesos para la misma generacion -- y se pagaria dos veces.
    """
    s = read_status(job_dir) or {}
    s.update({"queued": False, "running": True, "progress": 5, "status_text": "Iniciando..."})
    tmp = job_dir / "status.json.tmp"
    tmp.write_text(json.dumps(s, ensure_ascii=False), encoding="utf-8")
    tmp.replace(job_dir / "status.json")

    # El hijo se queda con su propia copia del descriptor al arrancar, asi que
    # el padre cierra el suyo al salir del `with`. Sin cerrarlo, uvicorn iba
    # acumulando un descriptor abierto por cada trabajo lanzado.
    with open(job_dir / "worker.log", "ab", buffering=0) as log:
        subprocess.Popen(
            [python_bin, str(worker_py), str(job_dir)],
            cwd=str(worker_py.parent),
            stdin=subprocess.DEVNULL, stdout=log, stderr=log,
            start_new_session=True, close_fds=True, env=os.environ.copy(),
        )


def drain(root: Path, python_bin: str, worker_py: Path, limite: int | None = None) -> int:
    """Arranca todos los que quepan. Devuelve cuantos arranco."""
    limite = max_concurrent() if limite is None else limite
    hueco = limite - len(running_jobs(root))
    arrancados = 0
    for d in queued_jobs(root):
        if hueco <= 0:
            break
        launch(d, python_bin, worker_py)
        hueco -= 1
        arrancados += 1
    return arrancados


def descartar_resultados(root: Path, user_id: int | str) -> int:
    """Marca los trabajos TERMINADOS de este atleta como ya cobrados.

    ## El fallo que arregla: la semana fantasma

    `generate-status` devuelve el trabajo mas reciente del atleta, y PHP guarda
    su `result` en MySQL cuando lo ve terminado. Para no guardarlo dos veces
    compara con `athlete_plans.last_generation_job`... que vive en la fila del
    plan. Al borrar el plan entero esa fila DESAPARECE, asi que la comparacion
    deja de valer y el siguiente sondeo -- cada arranque de la app hace uno --
    vuelve a guardar el resultado del mismo trabajo.

    El atleta borraba su semana, reiniciaba, y la semana estaba otra vez ahi.
    Reproducido: cuatro trabajos terminados en disco, los cuatro con su
    `result` completo, todos del mismo atleta.

    ## Por que se vacia el resultado y no se borra el trabajo

    `worker.log` es lo unico que queda para entender una generacion que fallo,
    y borrar el directorio se lo lleva. Vaciar `result` basta: PHP solo guarda
    cuando hay uno, asi que un trabajo cobrado deja de resucitar nada y su
    rastro sigue en disco.

    Los trabajos EN CURSO no se tocan: el atleta pudo borrar el plan viejo
    mientras se genera el nuevo, y ese si tiene que llegar.
    """
    tocados = 0
    want = str(user_id)
    for d in job_dirs(root):
        s = read_status(d)
        if not s or job_user(d, s) != want:
            continue
        if not s.get("done") or s.get("result") is None:
            continue
        s["result"] = None
        s["collected"] = True
        try:
            tmp = d / "status.json.tmp"
            tmp.write_text(json.dumps(s, ensure_ascii=False), "utf-8")
            tmp.replace(d / "status.json")
            tocados += 1
        except OSError:
            logger.warning("No se pudo marcar como cobrado el trabajo %s", d.name, exc_info=True)
    return tocados
