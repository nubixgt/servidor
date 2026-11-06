"""AiService — the bridge between the PHP backend and PaginaLocal's AI pipeline.

PaginaLocal is imported as a library and never modified. The PHP backend sends
the athlete's data (pulled from MySQL); this service feeds it to PaginaLocal's
agents and returns the result. PHP stores the result back in MySQL.

Run:  ./run.sh            (or: .venv/bin/uvicorn main:app --port 8600)
Auth: every /bridge/* call must carry  X-Service-Token: <BRIDGE_SERVICE_TOKEN>
"""
from __future__ import annotations

import json
import logging
import os
import re
import sys
import time
from datetime import date, datetime
from datetime import time as dtime
from pathlib import Path
from typing import Any

from fastapi import FastAPI, Header, HTTPException
from fastapi.responses import JSONResponse
from pydantic import ValidationError  # noqa: E402

from bridge import jobs as _jobs
from bridge import storage_adapter as sa
from bridge.jobs import (
    job_dirs as _job_dirs,
)
from bridge.jobs import (
    job_user as _job_user,
)
from bridge.jobs import (
    newest_status as _newest_status,
)
from bridge.jobs import (
    read_status as _read_status,
)

# bridge.paginalocal must be imported before anything else touches PaginaLocal.
from bridge.paginalocal import (
    RUNTIME_STORAGE,
    STORAGE_KEY,
    ask_coach,
    current_storage_dir,
    get_config,
    pl_server,
)
from bridge.schemas import (
    ChartsRequest,
    CoachAnswersRequest,
    CoachChatRequest,
    EncryptCredentialRequest,
    GarminImportRequest,
    GarminRetireRequest,
    GarminSessionsRequest,
    GarminSyncRequest,
    GarminUploadRequest,
    GenerateSeasonRequest,
    GenerateWeekRequest,
    LedgerMoveRequest,
    NotebookReorganizeRequest,
    PlanDeletedRequest,
    PlanEditRequest,
    ReportRequest,
    SessionStatusRequest,
    StrengthProgramRequest,
    StrengthRoutineRequest,
    SupportBundleRequest,
    ThresholdTestRequest,
)

# Los tipos de gasto, importados y no escritos a mano: si alguien renombra uno,
# esto revienta al arrancar en vez de escribir en silencio lineas con un tipo
# que el contador no sabe agrupar.
from core.usage_ledger import KIND_CHAT, KIND_NOTEBOOK_REORDER, KIND_STRENGTH_ROUTINE  # noqa: E402
from services.ai import reasoning_archive as pl_reasoning  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload  # noqa: E402

# Las plantillas imprimibles de PaginaLocal. Van DESPUES de bridge.paginalocal
# como todo lo demas: importar cualquier cosa de PaginaLocal antes de que el
# puente tenga puestos sus parches deja el modulo apuntando al almacenamiento
# equivocado.
from services.ai.rendering import printable_renderer as pl_render  # noqa: E402

logging.basicConfig(level=os.getenv("LOG_LEVEL", "INFO"))
logger = logging.getLogger("aiservice")

SERVICE_TOKEN = os.getenv("BRIDGE_SERVICE_TOKEN", "")

# A plan generation / Garmin upload takes minutes — too long for a synchronous
# HTTP request, and `asyncio.create_task` doesn't survive a Passenger worker
# recycle on shared hosting. So the endpoint writes a job spec and Popen's a
# fully detached `worker.py`; the client polls generate-status, which reads the
# job's status.json off disk. Crash-survivable, warm-restart-safe.
RUNTIME_DIR = RUNTIME_STORAGE.parent          # _runtime/
JOBS_DIR = RUNTIME_DIR / "jobs"               # season / week generations
UPLOAD_JOBS = RUNTIME_DIR / "upload_jobs"     # Garmin uploads
WORKER_PY = Path(__file__).resolve().parent / "worker.py"
PYTHON_BIN = sys.executable or str(Path(sys.prefix) / "bin" / "python")

_MAX_JOB_AGE_S = 25 * 60      # an unfinished job older than this = crashed worker
# Y un tope ABSOLUTO desde que el trabajo nacio. `_MAX_JOB_AGE_S` mira cuando se
# toco `status.json` por ultima vez, y el hilo de progreso del worker lo refresca
# cada dos segundos: un worker colgado (una llamada a un modelo que no vuelve)
# seguia "vivo" para siempre y dejaba al atleta sin poder generar. Una generacion
# real tarda 5-8 minutos; 45 es de sobra. Auditoria previa al merge, 2026-09-11.
_MAX_JOB_TOTAL_S = 45 * 60
# Lo más que un trabajo puede llevar ESPERANDO en la cola y seguir contando
# como "en curso" para su atleta. Con tres huecos y generaciones de 5-8 min,
# veinte atletas a la vez salen en ~50 min; tres horas sólo cubren el caso de
# una cola que nadie drena (el AiService caído a medias).
_MAX_JOB_EN_COLA_S = 3 * 3600
_KEEP_RECENT_JOBS = 30
_JOB_TTL_S = 3 * 24 * 3600
# Un resultado que nadie recogio todavia vive mucho mas: es una generacion
# pagada. Ver _sin_recoger().
_JOB_TTL_SIN_RECOGER_S = 30 * 24 * 3600

app = FastAPI(title="Tricoach AiService (PaginaLocal bridge)")


def _is_busy(root: Path, user_id: int | str) -> bool:
    """Si ESTE atleta tiene ya algo en curso.

    El candado es por atleta, no por servidor: dos personas generando a la vez
    son dos cosas independientes, y mientras fue global el primero en pulsar
    dejaba a los demas con "ya hay una generacion en curso" sin tener ninguna.
    Un trabajo sin dueño legible no cuenta como de nadie -- si no, un trabajo
    viejo y colgado bloqueaba a todo el mundo hasta caducar.
    """
    want = str(user_id)
    for d in _job_dirs(root):
        s = _read_status(d)
        if not s or s.get("done") or _job_user(d, s) != want:
            continue
        # En cola nadie toca su status.json, así que a los 25 minutos parecía
        # abandonado y el atleta podía encolar un SEGUNDO trabajo pagado. Un
        # trabajo en cola sigue siendo suyo mientras no pase el tope de la cola.
        if s.get("queued"):
            try:
                if time.time() - (d / "spec.json").stat().st_ctime < _MAX_JOB_EN_COLA_S:
                    return True
            except OSError:
                pass
            continue
        try:
            age = time.time() - (d / "status.json").stat().st_mtime
            desde_que_nacio = time.time() - (d / "spec.json").stat().st_ctime
        except OSError:
            age = _MAX_JOB_AGE_S + 1
            desde_que_nacio = _MAX_JOB_TOTAL_S + 1
        if age < _MAX_JOB_AGE_S and desde_que_nacio < _MAX_JOB_TOTAL_S:
            return True
    return False


def _espejo_ocupado(user_id: int | str) -> bool:
    """Si una generacion de este atleta es DUEÑA de su espejo ahora mismo.

    ## El fallo: la generacion pagada que se guardaba con el plan viejo

    El worker escribe el espejo al empezar (`worker.py::_main`), corre el grafo
    cinco a ocho minutos -- que escribe la semana o el macrociclo NUEVOS en ese
    mismo directorio -- y al final lee el resultado con `read_back`. Durante esa
    ventana, tres rutas de aqui reescribian el mismo `u<id>/` con lo que tiene
    MySQL, que es el plan ANTERIOR:

      - `/bridge/charts`, que la app pide al abrir Inicio;
      - `/bridge/coach-chat`, con notebook, check-ins, macrociclo y semana;
      - la comprobacion de ventana de `/bridge/generate-week`.

    Si una caia despues de que el grafo escribiera y antes de `read_back`, el
    worker leia el plan viejo y PHP lo guardaba como la generacion recien
    pagada. Sin error en ninguna parte.

    ## Por que no escribir en vez de esperar

    Mientras hay generacion, el espejo es SUYO: estas rutas leen lo que haya
    (el plan anterior o el nuevo a medio escribir, los dos coherentes para
    contestar una pregunta o pintar una grafica) y no lo pisan. Un candado que
    las hiciera esperar dejaria el chat colgado minutos. Cuando el worker
    arranca reescribe el espejo entero desde su spec, asi que nada de lo que se
    salta aqui se pierde. Un trabajo en cola tambien cuenta: aun no escribio,
    pero va a escribir.
    """
    return _is_busy(JOBS_DIR, user_id)


def _sin_recoger(d: Path) -> bool:
    """Un trabajo que TERMINO BIEN y trae un plan dentro.

    PHP guarda el resultado en el unico momento en que lo tiene delante: cuando
    la app pregunta por el estado. Si el atleta cierra la pestaña, ese plan --ya
    pagado-- se queda aqui, y la limpieza lo borraba a los tres dias o al
    llegar a 30 trabajos. La app ya lo recoge al entrar (appStore.
    rescatarGeneracion), pero entre las dos cosas hay una ventana, y quien no
    vuelve en tres dias es justo quien mas necesita encontrar su semana.

    No se puede saber desde aqui si PHP ya lo guardo, asi que se conserva mas
    tiempo en vez de menos: un resultado son ~100 KB y son nueve atletas.
    """
    s = _read_status(d)
    return bool(s and s.get("done") and not s.get("error") and s.get("result"))


def _cleanup(root: Path) -> None:
    dirs = _job_dirs(root)
    now = time.time()
    for i, d in enumerate(dirs):
        sin_recoger = _sin_recoger(d)
        ttl = _JOB_TTL_SIN_RECOGER_S if sin_recoger else _JOB_TTL_S
        try:
            old = now - (d / "status.json").stat().st_mtime > ttl
        except OSError:
            old = True
        if (i >= _KEEP_RECENT_JOBS and not sin_recoger) or old:
            for f in d.glob("*"):
                try:
                    f.unlink()
                except OSError:
                    pass
            try:
                d.rmdir()
            except OSError:
                pass


def _spawn_job(root: Path, kind: str, spec: dict) -> str:
    _cleanup(root)
    if _is_busy(root, spec.get("user_id")):
        raise HTTPException(409, "Ya tienes una generación o subida en curso. Espera a que termine.")

    job_id = datetime.now().strftime("%Y%m%d%H%M%S%f")
    jd = root / job_id
    jd.mkdir(parents=True, exist_ok=True)
    (jd / "spec.json").write_text(json.dumps({**spec, "kind": kind, "job_id": job_id}, ensure_ascii=False), "utf-8")

    # Todo trabajo NACE en cola, y `drain` decide si hay sitio para arrancarlo
    # ya. Un solo camino para entrar significa que no hay forma de saltarse el
    # limite -- y con MAX_CONCURRENT_GENERATIONS por encima de la demanda, la
    # cola es invisible: se arranca en el mismo instante.
    (jd / "status.json").write_text(json.dumps({
        "kind": kind, "job_id": job_id, "user_id": spec.get("user_id"),
        "queued": True, "running": False, "progress": 0,
        "status_text": "En cola...", "done": False, "error": None, "result": None,
    }, ensure_ascii=False), "utf-8")

    # Las subidas a Garmin no llaman a ningun modelo: no compiten por el limite
    # de la API y no tiene sentido hacerlas esperar detras de una generacion.
    limite = None if root == JOBS_DIR else 1000
    _jobs.drain(root, PYTHON_BIN, WORKER_PY, limite)
    return job_id


_IDLE_STATUS = {
    "kind": None, "job_id": None, "running": False, "queued": False,
    "queue_position": 0, "progress": 0,
    "status_text": "idle", "done": False, "error": None, "result": None,
}


def _check_token(x_service_token: str | None) -> None:
    if not SERVICE_TOKEN:
        raise HTTPException(500, "BRIDGE_SERVICE_TOKEN no configurado en el servicio")
    if x_service_token != SERVICE_TOKEN:
        raise HTTPException(401, "X-Service-Token inválido")


def _profile_dict(profile: dict[str, Any], garmin_password: str | None = None) -> dict[str, Any]:
    d = dict(profile)
    d.setdefault("name", "Atleta")
    # The cost tier is an operator decision (AI_MODE in the repo-root .env),
    # not a per-athlete DB value — always let the env win.
    d["ai_mode"] = os.getenv("AI_MODE", "standard")
    if garmin_password is not None:
        d["garmin_password"] = garmin_password
    d.setdefault("competitions", d.get("competitions") or [])
    return d


def _con_ajuste(profile: dict[str, Any], ajuste: str, encabezado: str) -> dict[str, Any]:
    """Mete lo que el atleta pidio cambiar en el canal que el planificador ya
    lee (`planning_context`, via build_custom_planning_context).

    Sobre la COPIA que recibe la tarea. Es de un solo uso a proposito: hasta v7
    esto se guardaba en el perfil y nadie lo limpiaba, asi que "mueve el
    miercoles" seguia dirigiendo los planes meses despues.
    """
    ajuste = (ajuste or "").strip()
    if ajuste:
        profile = dict(profile)
        profile["planning_context"] = f"{encabezado}: {ajuste}"
    return profile


def _clave_para_el_spec(valor: str | None) -> str:
    """La contraseña que se escribe en `spec.json`, SIEMPRE cifrada.

    El worker la descifra al arrancar y borra el campo del fichero
    (`sin_contrasenas`), pero entre que el trabajo se encola y arranca puede
    pasar un rato -- con la cola y las esperas del coach, mas de diez minutos.
    En ese hueco estaba en claro en disco. Cifrarla aqui lo cierra: la clave
    vive solo en este servicio. Auditoria previa al merge, 2026-09-11.
    """
    valor = (valor or "").strip()
    if not valor or valor.startswith("enc:v1:"):
        return valor
    try:
        return pl_server.encrypt_secret(valor)
    except Exception:  # noqa: BLE001 - sin cifrado preferimos seguir generando
        logger.warning("No se pudo cifrar la clave para el spec; va en claro")
        return valor


def _clave_garmin(valor: str | None) -> str:
    """La contraseña de Garmin en claro, venga cifrada o no.

    ## El reparto

    La clave de cifrado (`GARMIN_CREDENTIALS_KEY`) vive SÓLO aquí. PHP guarda un
    bloque opaco que no puede leer y lo devuelve tal cual cuando hace falta
    usarlo. Así no hay una segunda implementación de la criptografía en otro
    lenguaje — que es donde se rompen estas cosas — y MySQL nunca ve la
    contraseña.

    ## Por qué esto no rompe nada al llegar

    `decrypt_secret` devuelve intacto lo que no lleve el prefijo `enc:v1:`. Una
    contraseña escrita a mano en el momento sigue funcionando igual, así que el
    cambio a guardarla es gradual y no hay migración que correr.

    Un bloque que no se puede descifrar (la clave cambió, o el `.env` se perdió)
    no puede pasar como si fuera una contraseña: eso llegaría a Garmin como un
    intento de acceso con basura. Se convierte en un 400 con un mensaje que se
    puede leer.
    """
    valor = valor or ""
    try:
        from core.credential_crypto import decrypt_secret
        return decrypt_secret(valor) or ""
    except RuntimeError as e:
        logger.error("No se pudo descifrar la contraseña de Garmin guardada: %s", e)
        raise HTTPException(
            400,
            "No se pudo leer tu contraseña de Garmin guardada. Vuelve a escribirla en tu perfil.",
        ) from e


def _ahora_del_atleta(hoy: str | None) -> datetime:
    """Qué día es HOY para el atleta, no para el servidor.

    La ventana de antelación ("todavía no toca generar") se resuelve aquí
    dentro y no en el worker, así que no la cubre el cambio de zona horaria del
    proceso: hay que decírselo. El navegador manda su fecha local; sin ella se
    usa la del servidor, que es como se comportaba antes.

    El servidor de producción no está en Guatemala. A las 8 de la tarde allí ya
    es el día siguiente en UTC, y la ventana contaba un día de más o de menos
    contra el calendario que el atleta tiene delante.

    La hora del día no importa aquí -- la ventana trabaja en días enteros --
    así que basta con plantar la fecha del atleta a mediodía.
    """
    try:
        if hoy:
            return datetime.combine(date.fromisoformat(str(hoy)[:10]), dtime(12, 0))
    except ValueError:
        logger.warning("Fecha del atleta ilegible (%r); se usa la del servidor", hoy)
    return datetime.now()


def _anotar_costo(profile: dict[str, Any], kind: str, uso: dict[str, Any] | None) -> None:
    """Apunta en el libro de costos una llamada suelta (el chat, la rutina de fuerza).

    ## Por que hace falta esto aqui

    Este servicio NO llama a los endpoints de PaginaLocal: reimplementa su
    cuerpo llamando directo a `ask_coach` (y antes al Doctor, retirado en la Beta 3.0). Al hacerlo
    se salto lo que esos endpoints hacian alrededor, y una de esas cosas era
    anotar el gasto. `usage_ledger.record_call` existe desde v7, escrito
    exactamente para estos dos caminos, y solo se invocaba en un endpoint que
    el puente no usa. Resultado: el chat y el doctor se cobraban al proveedor
    y no aparecian en el contador del atleta.

    El doctor no se habia contado NUNCA, ni siquiera en PaginaLocal.

    ## Por que no puede fallar

    Va despues de responder. Un fallo aqui le costaria al atleta una respuesta
    que ya tiene -- paso de verdad una vez, con un desacuerdo de formato entre
    `record_call` y `record_run`. Se apunta o no se apunta; nunca rompe.

    Tiene que correr DENTRO del contexto de STORAGE_KEY: el resolvedor que
    instala el puente (bridge/paginalocal.py) lee esa contextvar para saber de
    quien es el gasto. Fuera de ella, todo se le carga a "default".
    """
    if not uso:
        return
    try:
        from core import usage_ledger
        usage_ledger.record_call(
            profile.get("name") or "",
            kind,
            uso,
            mode=os.getenv("AI_MODE"),
        )
    except Exception:  # noqa: BLE001
        logger.exception("No se pudo anotar el costo de esta llamada (%s)", kind)


@app.get("/health")
def health() -> dict[str, Any]:
    cfg = get_config()
    return {
        "ok": True,
        "ai_mode": str(getattr(cfg.ai_mode, "value", cfg.ai_mode)),
        "providers": {
            "anthropic": bool(cfg.anthropic_api_key),
            "openai": bool(cfg.openai_api_key),
            "gemini": bool(cfg.gemini_api_key),
        },
        "token_configured": bool(SERVICE_TOKEN),
    }


@app.post("/bridge/encrypt-credential")
def encrypt_credential(req: EncryptCredentialRequest, x_service_token: str | None = Header(default=None)):
    """Cifra un secreto para que PHP pueda guardarlo sin poder leerlo.

    ## Qué protege esto y qué no

    Protege contra que la contraseña de Garmin de un atleta salga legible de un
    volcado de MySQL, de una copia de seguridad o de una inyección SQL. Eso es
    lo que suele filtrarse, y es la razón de que exista.

    **No protege contra alguien con acceso al servidor entero**: la clave está
    en el `.env` de la misma máquina, así que quien pueda leer ese fichero puede
    descifrar. Blindar eso de verdad pide un gestor de secretos aparte, y no es
    lo que este producto tiene hoy. Decirlo importa más que insinuar lo
    contrario.

    Sin `GARMIN_CREDENTIALS_KEY` configurada se responde 503 y NO se devuelve el
    texto en claro: `encrypt_secret` degrada a devolverlo tal cual — que es lo
    correcto para el `profiles.json` de una instalación de una persona, y
    exactamente lo que no se puede hacer aquí, porque el destino es una base de
    datos compartida por todos los atletas.
    """
    _check_token(x_service_token)
    secreto = (req.secret or "").strip()
    if not secreto:
        raise HTTPException(400, "No hay nada que cifrar")

    from core.credential_crypto import _ENC_PREFIX, encrypt_secret
    cifrado = encrypt_secret(secreto) or ""
    if not cifrado.startswith(_ENC_PREFIX):
        logger.error("GARMIN_CREDENTIALS_KEY no configurada: no se puede guardar la credencial")
        raise HTTPException(
            503,
            "El servicio no puede cifrar credenciales ahora mismo "
            "(falta GARMIN_CREDENTIALS_KEY). No se ha guardado nada.",
        )
    return {"status": "success", "data": {"value": cifrado}}


@app.post("/bridge/garmin-import")
def garmin_import(req: GarminImportRequest, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)
    try:
        from services.garmin.client import SESION_GARMIN
        # Ver SESION_GARMIN en services/garmin/client.py. Este camino se
        # atiende dentro del servicio, no en el proceso aparte de las
        # generaciones, asi que dos atletas conectando su Garmin a la vez
        # escribirian sobre la misma sesion global de garth.
        with SESION_GARMIN:
            result = pl_server.garmin_import(
                pl_server.GarminImportModel(
                    garmin_email=req.garmin_email,
                    garmin_password=_clave_garmin(req.garmin_password),
                )
            )
    except HTTPException as e:
        return JSONResponse(status_code=e.status_code, content={"status": "error", "message": e.detail})
    except Exception as e:  # noqa: BLE001
        logger.exception("garmin-import failed")
        return JSONResponse(status_code=502, content={"status": "error", "message": str(e)})
    return {"status": "success", "data": result}


@app.post("/bridge/threshold-test")
def threshold_test(req: ThresholdTestRequest, x_service_token: str | None = Header(default=None)):
    """El test de umbral: `subir` lo programa en el reloj del atleta; `analizar` lee la actividad
    que hizo con ese entreno (por su workoutId). Sin IA.

    Corre dentro del servicio, como la importación: mismo candado de sesión de
    Garmin (ver SESION_GARMIN en services/garmin/client.py). El registro de
    subidas vive en el espejo del atleta y lo escribe también la subida del plan,
    así que con una subida suya en marcha se espera.
    """
    _check_token(x_service_token)
    if req.action not in ("subir", "analizar"):
        return JSONResponse(status_code=400, content={"status": "error", "message": "Acción no válida"})
    en_marcha = _newest_status(UPLOAD_JOBS, req.user_id)
    if req.action == "subir" and en_marcha and en_marcha.get("running"):
        return JSONResponse(status_code=409, content={
            "status": "error", "message": "Hay una subida a Garmin en marcha; espera a que termine."})
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        from services.garmin.client import SESION_GARMIN
        storage_dir: Path = current_storage_dir()
        clave = _clave_garmin(req.garmin_password)
        with SESION_GARMIN:
            if req.action == "subir":
                result = pl_server.subir_test_de_umbral(req.garmin_email, clave, storage_dir, req.sport, req.fecha)
            else:
                result = pl_server.analizar_test_de_umbral(req.garmin_email, clave, storage_dir, req.sport)
    except HTTPException as e:
        return JSONResponse(status_code=e.status_code, content={"status": "error", "message": e.detail})
    except Exception as e:  # noqa: BLE001
        logger.exception("threshold-test failed")
        return JSONResponse(status_code=502, content={"status": "error", "message": str(e)})
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": result}


@app.post("/bridge/garmin-sync")
def garmin_sync(req: GarminSyncRequest, x_service_token: str | None = Header(default=None)):
    """Cuándo subió algo su reloj por última vez, para el aviso del check-in (José, 2026-10-19: «si
    Connect no estaba sincronizado con el reloj, cuenta como que no se hicieron los entrenos»).

    Corre dentro del servicio, como la importación: mismo candado de sesión de Garmin. Un fallo
    no es un error para el atleta: el check-in simplemente no avisa.
    """
    _check_token(x_service_token)
    try:
        from services.garmin.client import SESION_GARMIN
        with SESION_GARMIN:
            ultima = pl_server.ultima_sincronizacion_del_reloj(req.garmin_email, _clave_garmin(req.garmin_password))
    except Exception as e:  # noqa: BLE001
        logger.warning("garmin-sync: %s", e)
        return {"status": "success", "data": {"ultima_sincronizacion": None}}
    return {"status": "success", "data": {"ultima_sincronizacion": ultima}}


# Cuánto vale lo leído de Garmin para los ✓/✗ antes de volver a preguntar: entrar a Plan no puede costar
# 15 llamadas a Garmin cada vez. «Actualizar» lo salta, como mucho una vez por minuto.
_ESTADO_TTL_S = 15 * 60
_ESTADO_FORZAR_CADA_S = 60


@app.post("/bridge/session-status")
def session_status(req: SessionStatusRequest, x_service_token: str | None = Header(default=None)):
    """✓/✗ de cada sesión reciente, leído de Garmin y sin IA (el responsable, 2026-10-21: «que marque un
    cheque cuando la sesión ya se logró y una x cuando no»). Guarda lo leído en el espejo
    (`estado_de_las_sesiones.json`) y lo reutiliza 15 minutos si el plan y el día son los mismos.
    Un fallo de Garmin no es un error para el atleta: no hay marcas y se dice por qué.
    """
    import hashlib
    import time

    _check_token(x_service_token)
    hoy = date.fromisoformat(req.today) if re.fullmatch(r"\d{4}-\d{2}-\d{2}", req.today or "") else date.today()
    firma = hashlib.sha1(json.dumps(
        [(d.get("date"), d.get("session_slot"), d.get("sport"), d.get("is_rest"))
         for w in (req.weekly_plan or {}).get("weeks") or [] for d in w.get("days") or []],
        sort_keys=True).encode()).hexdigest()
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        d = current_storage_dir()
        cache = d / "estado_de_las_sesiones.json"
        guardado = None
        try:
            guardado = json.loads(cache.read_text(encoding="utf-8")) if cache.exists() else None
        except (OSError, ValueError):
            guardado = None
        edad = time.time() - float((guardado or {}).get("leido", 0))
        vale = guardado and guardado.get("firma") == firma and guardado.get("hoy") == hoy.isoformat()
        if vale and (edad < _ESTADO_TTL_S and not req.force or req.force and edad < _ESTADO_FORZAR_CADA_S):
            return {"status": "success", "data": {**guardado["datos"], "leido_hace_s": int(edad)}}
        try:
            from services.garmin.client import SESION_GARMIN
            with SESION_GARMIN:
                datos = pl_server.estado_de_las_sesiones_del_reloj(
                    req.garmin_email, _clave_garmin(req.garmin_password), d, req.weekly_plan, hoy,
                    req.threshold_pace_sec_per_km)
        except Exception as e:  # noqa: BLE001
            from services.garmin.client import explicar_error_de_garmin
            logger.warning("session-status: %s", e)
            return {"status": "success", "data": {"sesiones": {}, "error": explicar_error_de_garmin(e)
                                                  or "No pudimos leer tu Garmin ahora mismo."}}
        try:
            cache.write_text(json.dumps({"leido": time.time(), "firma": firma, "hoy": hoy.isoformat(),
                                         "datos": datos}, ensure_ascii=False, default=str), encoding="utf-8")
        except OSError:
            pass
        return {"status": "success", "data": {**datos, "leido_hace_s": 0}}
    finally:
        STORAGE_KEY.reset(token)


@app.post("/bridge/coach-chat")
async def coach_chat(req: CoachChatRequest, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        storage_dir: Path = current_storage_dir()
        # Con una generacion en marcha el espejo es suyo. Ver _espejo_ocupado.
        if not _espejo_ocupado(req.user_id):
            sa.write_notebook(storage_dir, req.notebook)
            sa.write_checkins(storage_dir, req.checkins)
            sa.write_season_plan(storage_dir, req.season_plan_md, req.season_phases,
                                 (req.profile or {}).get("week_start_day"))
            sa.write_weekly_plan(storage_dir, req.weekly_plan)
            sa.write_summary(storage_dir, req.summary)

        perfil = _profile_dict(req.profile)
        result = await ask_coach(
            storage_dir,
            perfil,
            req.message,
            req.chat_history,
            coach_id=req.coach_id,
            registro_de_fuerza=req.strength_logs,
        )
        # Lo que el proveedor dice que costo. Se copia en vez de sacarlo: PHP
        # se lo devuelve a la UI, y ademas hay que anotarlo en el libro.
        uso = dict(result.get("usage_metadata") or {})

        # v7 Track 7: the coach looks at what just happened and decides if
        # there's anything durable worth remembering. Cheap, separate call;
        # almost always adds nothing. Its failure must not cost the reply.
        new_notes: list = []
        try:
            from services.ai.notebook import propose_entries
            created, uso_extra = await propose_entries(storage_dir, req.message, result.get("reply", ""))
            new_notes = created or []
            # Esta segunda llamada tambien se paga. Su `usage` se estaba
            # descartando aqui (`created, _`), asi que el cuaderno gastaba en
            # cada mensaje del chat sin aparecer en ninguna cuenta.
            for modelo, u in (uso_extra or {}).items():
                actual = uso.setdefault(modelo, {"input_tokens": 0, "output_tokens": 0, "total_tokens": 0})
                for clave in ("input_tokens", "output_tokens", "total_tokens"):
                    actual[clave] = (actual.get(clave) or 0) + (u.get(clave) or 0)
        except Exception:  # noqa: BLE001
            logger.exception("notebook propose failed")
        result["notebook_new"] = new_notes

        # El chat es el unico gasto SIN TOPE DE FRECUENCIA del producto: el
        # atleta puede mandar veinte mensajes en una tarde. Anotarlo por
        # mensaje es lo que hace que el costo por usuario sea real y no una
        # estimacion de las generaciones solamente.
        _anotar_costo(perfil, KIND_CHAT, uso)
    except Exception as e:  # noqa: BLE001
        logger.exception("coach-chat failed")
        raise HTTPException(502, f"coach-chat: {e}") from e
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": result}


@app.post("/bridge/garmin-upload")
def garmin_upload(req: GarminUploadRequest, x_service_token: str | None = Header(default=None)):
    """Upload the plan's sessions to the athlete's Garmin watch (detached worker).
    Poll /bridge/garmin-upload-status."""
    _check_token(x_service_token)
    spec = {
        "user_id": req.user_id,
        "profile": dict(req.profile),
        "garmin_password": _clave_para_el_spec(req.garmin_password),
        "dates": req.dates,
        "context": {"weekly_plan": req.weekly_plan, "summary": req.summary},
    }
    job_id = _spawn_job(UPLOAD_JOBS, "garmin-upload", spec)
    return {"status": "success", "data": {"started": True, "job_id": job_id}}


@app.post("/bridge/plan-deleted")
def plan_deleted(req: PlanDeletedRequest, x_service_token: str | None = Header(default=None)):
    """Limpia lo que el plan borrado deja aqui: su traza de razonamiento.

    MySQL es la fuente y este directorio es un espejo, asi que los ficheros
    del plan se corrigen solos en la siguiente llamada que escriba el espejo.
    La traza no: `save_reasoning` la escribe una vez y nadie la volvia a
    tocar, asi que sobrevivia al plan que explicaba. El chat ya no la lee (ver
    `_ultima_traza_todavia_vigente`), pero seguia ocupando ~500 KB para
    siempre.

    Se borra SOLO la del alcance pedido. Borrar la semana no puede llevarse el
    razonamiento del macrociclo, que sigue describiendo algo que existe.

    Nunca falla el borrado: si esto revienta, el plan ya se fue de MySQL y lo
    que queda es un directorio de mas.
    """
    _check_token(x_service_token)
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        storage_dir: Path = current_storage_dir()
        alcance = req.scope if req.scope in ("week", "season") else None
        # PRIMERO el resultado del trabajo terminado, que si no resucita el plan.
        # `athlete_plans.last_generation_job` -- la unica guarda contra guardar
        # dos veces el mismo resultado -- vive en la fila del plan, asi que
        # borrar el plan entero se la lleva: el siguiente sondeo, y la app hace
        # uno en cada arranque, volvia a guardar la semana que el atleta
        # acababa de borrar. Ver `descartar_resultados`.
        #
        # Iba DESPUES de borrar las trazas, y eso lo rompia: en Windows cada
        # traza bloqueada reintenta ~1,5 s, PHP corta esta llamada a los 15 s,
        # y el sondeo que la app hace justo despues guardaba otra vez la
        # semana. Reproducido el 2026-09-13 con el atleta 2: borrado "all" a
        # las 19:28, la semana de vuelta en MySQL, y los trabajos marcados
        # como cobrados a las 19:28:54 -- tarde. Lo barato y lo que importa va
        # antes que lo lento.
        cobrados = _jobs.descartar_resultados(JOBS_DIR, req.user_id)
        # Las semanas borradas que nunca se midieron salen del expediente (a su archivo de
        # descartadas): si no, el macrociclo las leía como pendientes para siempre. Ver
        # track_record.descartar_las_no_vividas. Borrar sólo el macrociclo no toca semanas.
        if alcance != "season":
            from services.ai.track_record import descartar_las_no_vividas

            descartar_las_no_vividas(storage_dir, _ahora_del_atleta(None).date().isoformat())
        quitadas = pl_reasoning.borrar_trazas(storage_dir, kind=alcance)
    except Exception as e:  # noqa: BLE001
        logger.warning("No se pudieron borrar las trazas de u%s: %s", req.user_id, e)
        return {"status": "success", "data": {"trazas_borradas": 0, "error": str(e)}}
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": {"trazas_borradas": quitadas, "trabajos_cerrados": cobrados, "error": None}}


def _informe_de_analisis(nombre: str, resumen: dict[str, Any], generado: str | None) -> str | None:
    """El informe de analisis con la plantilla de HOY, desde la sintesis archivada.

    El pipeline guarda el HTML ya pintado en MySQL al generar. Eso congelaba el
    diseño: rediseñar la hoja (2026-09-11, "dignos de la refri") no llegaba al
    atleta hasta su siguiente macrociclo. La sintesis en markdown se archiva
    en la traza (`reasoning/<ISO>/synthesis.md`), asi que se re-renderiza.
    None si no hay traza: PHP sirve entonces el HTML guardado, como antes.
    """
    from services.ai.reasoning_archive import list_reasoning_entries, rescatar_markdown

    for entrada in list_reasoning_entries(current_storage_dir()):
        if entrada.get("kind") != "season":
            continue
        sintesis = Path(entrada["path"]) / "synthesis.md"
        if not sintesis.is_file():
            continue
        # Las trazas anteriores al 2026-09-11 guardaban el repr de un mensaje de
        # LangChain ("content=[{'type': 'text'...") en vez del markdown; pintado
        # tal cual salia un unico parrafo de 90.000 px de ancho. Se rescata.
        texto = rescatar_markdown(sintesis.read_text(encoding="utf-8")).strip()
        if not texto or texto.startswith("content="):
            return None
        # Las tarjetas salen de la MISMA función que las pinta al generar. Aquí había
        # una copia que decía siempre "de tu Garmin" y "Carga crónica": el informe del
        # atleta 2 (2026-09-23) ponía "5:43/km de tu Garmin" encima del aviso de que
        # ese umbral se había AJUSTADO desde el de Garmin (5:53).
        from services.ai.langgraph.nodes.formatter_node import _headline_stats
        stats = _headline_stats({
            "prescription": resumen.get("prescription") or {},
            "availability_constraints": {"volume_baseline": resumen.get("volume_baseline") or {}},
            "adherence": resumen.get("adherence") or {},
        })
        return pl_render.render_analysis_report(
            athlete_name=nombre,
            synthesis_markdown=texto,
            generated_at=(entrada.get("generated_at") or generado or "")[:10],
            headline_stats=stats,
            warnings=[w for w in ((resumen.get("prescription") or {}).get("warnings") or []) if w],
        )
    return None


@app.post("/bridge/report")
def report(req: ReportRequest, x_service_token: str | None = Header(default=None)):
    """Un informe imprimible, con las plantillas que ya existian.

    ## Por que existe

    `printable_plan` y `printable_season` (con su calendario de semanas
    marcando descargas) viven en
    services/ai/rendering/templates/ desde v6/v7, con tests, y la app
    multiusuario no tenia por donde pedirlas: la vista de Informes rehacia a
    mano versiones peores en Vue -- ahi salio el bloque de repeticiones
    contado dos veces, y ahi se perdio el calendario de semanas.

    El informe de ANALISIS no pasa por aqui: ese lo escribe el pipeline y se
    guarda como HTML en MySQL, asi que PHP lo sirve solo.

    ## Sincrono

    Son plantillas Jinja sobre datos que llegan en la peticion: milisegundos,
    sin LLM y sin Garmin. Lo unico que se toca del disco es `profiles.json`
    del atleta, y de solo lectura.
    """
    _check_token(x_service_token)
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        perfil = _profile_dict(req.profile)
        nombre = perfil.get("name") or "Atleta"
        resumen = req.summary or {}

        if req.kind == "analysis":
            html = _informe_de_analisis(nombre, resumen, req.generated_at)
            if html is None:
                raise HTTPException(status_code=404, detail="No hay una sintesis archivada para re-renderizar.")

        elif req.kind == "season":
            fases = req.season_phases or {}
            narrativa = req.season_plan_md or ""
            if not fases.get("phases") and not narrativa:
                raise HTTPException(status_code=404, detail="No hay plan de temporada para imprimir.")
            html = pl_render.render_printable_season(
                athlete_name=nombre,
                season_phases=fases,
                competitions=perfil.get("competitions") or [],
                narrative_html=pl_render.markdown_to_html(narrativa),
                goals=resumen.get("goal_realism") or [],
                prescription=resumen.get("prescription") or {},
            )

        else:  # "plan"
            crudo = req.weekly_plan or {}
            if not (crudo.get("weeks") or []):
                raise HTTPException(status_code=404, detail="No hay una semana generada para imprimir.")
            try:
                payload = WeeklyPlanPayload.model_validate(crudo)
            except ValidationError as exc:
                logger.warning("Plan semanal invalido al imprimir: %s", exc)
                raise HTTPException(status_code=422, detail="El plan guardado no se pudo leer. Genera uno nuevo.") from exc
            html = pl_render.render_printable_plan(
                payload,
                prescription=resumen.get("prescription"),
                generated_at=req.generated_at or resumen.get("analysis_date"),
                # Para poner la HORA REAL de la franja cuando un dia lleva
                # varias sesiones, en vez de un "AM"/"PM" que miente si las dos
                # son de manana. Ver slot_label en printable_renderer.py.
                availability=pl_server.build_availability_constraints(perfil),
            )
    except HTTPException:
        raise
    except Exception as e:  # noqa: BLE001
        logger.exception("report failed")
        raise HTTPException(status_code=500, detail=str(e)) from e
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": {"html": html}}


@app.post("/bridge/garmin-retire")
def garmin_retire(req: GarminRetireRequest, x_service_token: str | None = Header(default=None)):
    """Saca del reloj los entrenos que esta app subio, al borrar el plan.

    Sincrono a proposito: son pocas llamadas y el atleta esta esperando la
    confirmacion del borrado. **Nunca falla el borrado del plan**: si Garmin no
    contesta se devuelve cuantos quedaron pendientes y PHP borra igual -- el
    plan es del atleta y ya dijo que lo queria fuera.

    Solo toca `workout_id`s que estan en el registro de subidas de ESTE atleta
    (services/garmin/upload_ledger.py). Los entrenos que el atleta se creo por
    su cuenta no aparecen ahi y por eso no se pueden borrar por accidente.
    """
    _check_token(x_service_token)
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        storage_dir: Path = current_storage_dir()
        sa.write_weekly_plan(storage_dir, req.weekly_plan)
        from services.garmin.client import SESION_GARMIN
        with SESION_GARMIN:   # ver SESION_GARMIN: garth.client es global
            out = pl_server.retirar_semana_de_garmin_con(
                req.profile.get("garmin_email", ""), _clave_garmin(req.garmin_password), storage_dir,
                todas=req.todo,
            )
    except Exception as e:  # noqa: BLE001
        logger.exception("garmin-retire failed")
        return {"status": "success", "data": {"retirados": 0, "pendientes": 0, "error": str(e)}}
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": out}


@app.get("/bridge/garmin-upload-status")
def garmin_upload_status(user_id: int, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)
    s = _newest_status(UPLOAD_JOBS, user_id)
    if not s:
        return {"status": "success", "data": {"running": False, "progress": 0, "status": "idle", "error": None, "result": None}}
    return {"status": "success", "data": {
        "running": bool(s.get("running")),
        "progress": int(s.get("progress") or 0),
        "status": s.get("status_text") or "",
        "error": s.get("error"),
        "result": s.get("result") if s.get("done") else None,
    }}


@app.post("/bridge/charts")
def charts(req: ChartsRequest, x_service_token: str | None = Header(default=None)):
    """Dashboard/analytics series. No LLM. Reads from the per-user storage dir
    (track_record accumulates there as weeks are generated); an optional Garmin
    password refreshes the fitness/load/recovery snapshot first (3 cheap calls)."""
    _check_token(x_service_token)
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        storage_dir = current_storage_dir()
        # Inicio pide esto al abrirse: es la ruta que mas facil pisaba una
        # generacion en marcha. Ver _espejo_ocupado.
        if not _espejo_ocupado(req.user_id):
            sa.write_weekly_plan(storage_dir, req.weekly_plan)
        profile = _profile_dict(req.profile)

        refreshed = {}
        if req.garmin_password:
            try:
                from services.ai import dashboard_refresh
                from services.garmin.client import SESION_GARMIN
                from services.garmin.data_extractor import TriathlonCoachDataExtractor
                # Ver SESION_GARMIN: `garth.client` es un global del modulo, y
                # esto corre DENTRO del servicio (el pool de hilos de FastAPI),
                # no en el proceso aparte que usan las generaciones. Sin el
                # candado, dos atletas refrescando a la vez comparten sesion.
                with SESION_GARMIN:
                    extractor = TriathlonCoachDataExtractor(req.profile.get("garmin_email", ""), _clave_garmin(req.garmin_password))
                    refreshed = dashboard_refresh.refresh(extractor, storage_dir)
            except Exception as e:  # noqa: BLE001
                logger.warning("charts garmin refresh failed: %s", e)
                refreshed = {"refresh_error": str(e)}

        from services.ai import dashboard_refresh
        from services.ai.charts import build_charts
        out = build_charts(storage_dir, profile, checkins=req.checkins, strength_logs=req.strength_logs)
        out["last_refresh"] = dashboard_refresh.last_snapshot_date(storage_dir)
        out["stale"] = dashboard_refresh.is_stale(storage_dir)
        out.update({k: v for k, v in refreshed.items() if k not in out})
        return {"status": "success", "data": out}
    except Exception as e:  # noqa: BLE001
        logger.exception("charts failed")
        raise HTTPException(502, f"charts: {e}") from e
    finally:
        STORAGE_KEY.reset(token)


@app.post("/bridge/notebook-reorganize")
async def notebook_reorganize(req: NotebookReorganizeRequest, x_service_token: str | None = Header(default=None)):
    """«Revisa mi cuaderno» (Beta 3.0, F9): preguntas, o el cuaderno ordenado y lo que hay que
    cambiar en el perfil. No guarda nada: el atleta acepta cada cambio.

    Una llamada al modelo del cuaderno por vuelta (dos si pregunta); el costo va al libro con su
    propio tipo y nunca se le enseña al atleta.
    """
    _check_token(x_service_token)
    token = STORAGE_KEY.set(f"u{req.user_id}")
    perfil = _profile_dict(req.profile)
    try:
        from services.ai.reordenar_cuaderno import revisar

        propuesta, uso = await revisar(req.entries, perfil, req.answers)
        if uso:
            _anotar_costo(perfil, KIND_NOTEBOOK_REORDER, uso)
    except Exception:  # noqa: BLE001
        logger.exception("notebook-reorganize failed")
        return JSONResponse(status_code=502, content={
            "status": "error", "message": "No se pudo revisar el cuaderno ahora. Inténtalo en un momento."})
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": propuesta}


@app.post("/bridge/strength-routine")
async def strength_routine(req: StrengthRoutineRequest, x_service_token: str | None = Header(default=None)):
    """Ordena la rutina pegada en sesiones, grupos y ejercicios para que el atleta la revise.

    No guarda nada: la rutina se guarda con el perfil cuando el atleta la confirma.
    Una llamada al modelo barato (rol STRENGTH_ROUTINE_READER, ~0,005 USD con la
    rutina de Fernando); el costo va al libro y nunca se le enseña al atleta.
    """
    _check_token(x_service_token)
    token = STORAGE_KEY.set(f"u{req.user_id}")
    perfil = _profile_dict(req.profile)
    try:
        from services.ai.rutina_de_fuerza import RutinaDemasiadoLarga, ordenar_rutina

        borrador, uso = await ordenar_rutina(req.text, req.grouping, req.rest_between_rounds_s)
        _anotar_costo(perfil, KIND_STRENGTH_ROUTINE, uso)
    except RutinaDemasiadoLarga as e:
        return JSONResponse(status_code=422, content={"status": "error", "message": str(e)})
    except ValueError as e:
        return JSONResponse(status_code=422, content={"status": "error", "message": str(e)})
    except Exception:  # noqa: BLE001
        logger.exception("strength-routine failed")
        return JSONResponse(status_code=502, content={
            "status": "error", "message": "No pudimos ordenar tu rutina ahora. Inténtalo en un momento."})
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": borrador}


@app.post("/bridge/strength-program")
async def strength_program(req: StrengthProgramRequest, x_service_token: str | None = Header(default=None)):
    """El coach escribe el programa de gimnasio del atleta, UNA vez por bloque.

    No guarda nada: devuelve el borrador con la misma forma que una rutina pegada,
    para que el atleta lo revise y lo confirme en la misma pantalla. Lo que sale de
    aquí es lo que él declaró (objetivo, equipo, reparto, minutos, barras que ya hace)
    y sus días de gimnasio; el costo va al libro y nunca se le enseña al atleta.
    """
    _check_token(x_service_token)
    token = STORAGE_KEY.set(f"u{req.user_id}")
    perfil = _profile_dict(req.profile)
    try:
        from langchain_core.callbacks.usage import get_usage_metadata_callback

        from services.ai import programa_del_coach as pc
        from services.ai.ai_settings import AgentRole
        from services.ai.model_config import ModelSelector
        from services.ai.niggle_constraints import collect, render_niggle_constraints
        from services.ai.perfil_de_fuerza import leer_perfil_de_fuerza

        perfil_de_fuerza = leer_perfil_de_fuerza(perfil)
        dias = len([d for d, estado in ((perfil.get("sport_days") or {}).get("Fuerza") or {}).items() if estado])
        llm = ModelSelector.get_llm(AgentRole.WORKOUT).with_structured_output(pc.ProgramaPropuesto)
        with get_usage_metadata_callback() as uso_cb:
            borrador, _ = await pc.escribir_el_programa(
                perfil=perfil_de_fuerza,
                dias_de_gimnasio=dias,
                deporte=perfil.get("sports_preference") or "",
                molestias=render_niggle_constraints(collect(None, perfil)),
                llamar=llm.ainvoke,
            )
            uso = dict(uso_cb.usage_metadata or {})
        _anotar_costo(perfil, KIND_STRENGTH_ROUTINE, uso)
    except Exception:  # noqa: BLE001
        logger.exception("strength-program failed")
        return JSONResponse(status_code=502, content={
            "status": "error", "message": "No pudimos escribir tu programa ahora. Inténtalo en un momento."})
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": borrador}


@app.get("/bridge/usage")
def usage(user_id: int, x_service_token: str | None = Header(default=None)):
    """Lo que esta cuenta ha costado en APIs: total, por tipo y por mes.

    Por CUENTA, no por nombre de perfil: dos atletas pueden llamarse igual y
    uno puede cambiarse el nombre. Y el libro es de solo-añadir y vive fuera del
    arbol que se borra, asi que **borrar el plan no borra lo que ya costo** --
    el dinero ya se le pago a Anthropic, OpenAI y Google.

    No es una factura: es costo de proveedor. Convertirlo en lo que se le cobra
    al atleta es una decision de negocio que se toma con estos numeros delante.
    """
    _check_token(x_service_token)
    from core.usage_ledger import usage_for_user
    return {"status": "success", "data": usage_for_user(f"u{user_id}")}


@app.get("/bridge/usage-global")
def usage_global_endpoint(x_service_token: str | None = Header(default=None)):
    """El gasto de TODA la instalacion, desglosado por herramienta y por cuenta.

    Es la vista del panel de administracion, y es distinta de `/bridge/usage`:
    aquella responde "cuanto ha costado ESTE atleta" y esta responde "en que se
    va el dinero". Las cuatro herramientas tienen economias distintas y solo
    una es ilimitada -- una temporada y una semana se generan cuando el atleta
    lo pide, pero el chat corre tantas veces como le apetezca escribir.

    Solo la sirve el backend con el token de servicio, y solo se la enseña a un
    administrador: es el gasto de todos los atletas junto.
    """
    _check_token(x_service_token)
    from core.usage_ledger import usage_global
    return {"status": "success", "data": usage_global()}


# --- La salud de la app (Beta 3.0, F1): lo que antes sólo se veía por SSH. ----------
# Sólo con el token de servicio, y PHP sólo se lo enseña a un administrador. Nunca
# devuelve spec.json (lleva el perfil) y todo texto sale saneado: ver bridge/admin.py.

@app.get("/bridge/admin/jobs")
def admin_jobs(limit: int = 60, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)
    from bridge import admin
    from core.usage_ledger import read_all
    limite = max(1, min(200, int(limit)))
    return {"status": "success", "data": {
        "jobs": admin.trabajos({"generacion": JOBS_DIR, "garmin-upload": UPLOAD_JOBS}, limite, read_all()),
        # Se conservan los 30 más recientes y 3 días: lo más viejo ya no está, y se dice.
        "conservados": f"los {_KEEP_RECENT_JOBS} más recientes, como mucho {_JOB_TTL_S // 86400} días",
    }}


@app.get("/bridge/admin/job-log")
def admin_job_log(job_id: str, lines: int = 200, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)
    from bridge import admin
    log = admin.log_del_trabajo([JOBS_DIR, UPLOAD_JOBS], job_id, lines)
    if log is None:
        raise HTTPException(404, "No hay log de ese trabajo (o ya se limpió)")
    return {"status": "success", "data": log}


@app.get("/bridge/admin/health")
def admin_health(x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)
    from bridge import admin
    from core import redes_ledger
    from core.usage_ledger import LEDGER_PATH
    return {"status": "success", "data": admin.salud(
        JOBS_DIR, UPLOAD_JOBS, RUNTIME_DIR,
        {"costos": LEDGER_PATH, "redes": redes_ledger.REDES_PATH},
        desplegado=Path(__file__))}


@app.get("/bridge/admin/nets")
def admin_nets(days: int = 30, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)
    from bridge import admin
    from core import redes_ledger
    from core.usage_ledger import read_all
    dias = max(1, min(365, int(days)))
    # Sin el libro empezado no hay "0 de N": ninguna generación pudo anotar nada.
    desde = redes_ledger.midiendo_desde()
    generaciones = admin.generaciones_en_ventana(read_all(), dias, desde=desde) if desde else 0
    return {"status": "success", "data": {
        "days": dias, "generaciones": generaciones,
        "midiendo_desde": desde.isoformat(timespec="seconds") if desde else None,
        "redes": redes_ledger.resumen(dias, generaciones),
    }}


@app.post("/bridge/plan-edit")
def plan_edit(req: PlanEditRequest, x_service_token: str | None = Header(default=None)):
    """Calcula un cambio de sesión (Beta 3.0, F6). No escribe nada: PHP guarda el plan.

    422 con el motivo para el atleta si no se puede (pasado, día de carrera, otra semana).
    """
    _check_token(x_service_token)
    from services.ai import ajuste_de_sesion as aj
    from services.ai import edicion_de_sesion as ed
    from services.ai import sesion_del_atleta as sa

    hoy = req.today if re.fullmatch(r"\d{4}-\d{2}-\d{2}", req.today or "") else date.today().isoformat()
    try:
        if req.op == "mover":
            r = ed.mover(req.weekly_plan, req.session_key, req.to_date, hoy=hoy, modo=req.modo,
                         rejilla=req.sport_days)
        elif req.op == "editor":
            r = aj.editor(req.weekly_plan, req.session_key, hoy=hoy, prescripcion=req.prescripcion)
        elif req.op == "ajustar":
            r = aj.ajustar(req.weekly_plan, req.session_key, req.elementos or [], hoy=hoy,
                           prescripcion=req.prescripcion, original=req.original, grupos=req.grupos)
        elif req.op == "revertir":
            r = aj.revertir(req.weekly_plan, req.session_key, req.original, hoy=hoy)
        elif req.op == "nuevo":
            r = sa.nuevo_editor(req.deporte, req.prescripcion)
        elif req.op == "anadir":
            r = sa.anadir(req.weekly_plan, req.to_date, req.deporte, hoy=hoy, elementos=req.elementos,
                          grupos=req.grupos, titulo=req.titulo, rejilla=req.sport_days)
        elif req.op == "quitar":
            r = sa.quitar(req.weekly_plan, req.session_key, hoy=hoy)
        else:
            raise HTTPException(422, "No sé hacer ese cambio.")
    except ed.EdicionNoPermitida as e:
        raise HTTPException(422, str(e)) from e
    return {"status": "success", "data": r}


@app.post("/bridge/garmin-ledger-move")
def garmin_ledger_move(req: LedgerMoveRequest, x_service_token: str | None = Header(default=None)):
    """Lleva cada `workout_id` del registro a la clave nueva de su sesión (Beta 3.0, F6).

    No toca el reloj: la siguiente subida reemplaza el entreno (crea el nuevo y borra el
    viejo). Con una generación o subida en marcha, 409: las dos escribirían el registro.
    """
    _check_token(x_service_token)
    from services.garmin import upload_ledger

    # La subida, como en el test de umbral, por su estado y no por `_is_busy`: el worker de
    # Garmin borra su spec.json al cifrar la contraseña, y sin él `_is_busy` la da por muerta.
    subida = _newest_status(UPLOAD_JOBS, req.user_id)
    if _espejo_ocupado(req.user_id) or (subida and subida.get("running")):
        raise HTTPException(409, "Tienes una generación o subida en curso. Espera a que termine.")
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        d = current_storage_dir()
        registro = upload_ledger.cargar(d)
        try:
            nuevo = upload_ledger.mover(registro, req.cambios)
        except ValueError as e:
            raise HTTPException(409, str(e)) from e
        upload_ledger.guardar(d, nuevo)
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": {"moved": len(req.cambios), "entries": nuevo}}


@app.post("/bridge/garmin-sessions")
def garmin_sessions(req: GarminSessionsRequest, x_service_token: str | None = Header(default=None)):
    """Por sesión del plan: `en_el_reloj`, `cambios_sin_enviar` o `sin_subir` (Beta 3.0, F5).

    Antes la pantalla sólo sabía lo que decía el sondeo de ESA subida, y se perdía al
    recargar. Lee el registro de subidas y la semana que manda PHP; no escribe nada.
    """
    _check_token(x_service_token)
    from services.garmin import upload_ledger

    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        entradas = upload_ledger.cargar(current_storage_dir())
    finally:
        STORAGE_KEY.reset(token)
    registro = upload_ledger.por_sesion(entradas)
    try:
        plan = WeeklyPlanPayload.model_validate(req.weekly_plan) if req.weekly_plan else None
    except Exception:  # noqa: BLE001 - un plan ilegible no tiene sesiones que marcar
        plan = None
    sesiones = {}
    for semana in (plan.weeks if plan else []):
        for dia in semana.days:
            if dia.is_rest:
                continue
            k = upload_ledger.clave(dia.date, dia.session_slot)
            sesiones[k] = upload_ledger.estado_de_la_sesion(registro.get(k), dia)
    # Lo que está en su reloj y ya no en su plan, de hoy en adelante: un entreno que añadió y quitó después
    # de enviarlo (2026-10-23). «Enviar al reloj» manda su fecha y la subida lo retira.
    if plan:
        fechas = {d.date for w in plan.weeks for d in w.days}
        vivas = {upload_ledger.clave(d.date, d.session_slot) for w in plan.weeks for d in w.days if not d.is_rest}
        hoy = date.today().isoformat()
        for e in upload_ledger.huerfanas(entradas, fechas, vivas):
            if (e.get("date") or "") >= hoy:
                sesiones[upload_ledger.clave(e["date"], e.get("session_slot"))] = "sobra"
    return {"status": "success", "data": {"sessions": sesiones}}


@app.get("/bridge/garmin-ledger")
def garmin_ledger(user_id: int, x_service_token: str | None = Header(default=None)):
    """El registro de subidas de un atleta, para su copia en MySQL (Beta 3.0, F5)."""
    _check_token(x_service_token)
    from services.garmin import upload_ledger

    token = STORAGE_KEY.set(f"u{user_id}")
    try:
        entradas = upload_ledger.cargar(current_storage_dir())
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": {"entries": entradas}}


@app.get("/bridge/track-record")
def track_record_copia(user_id: int, x_service_token: str | None = Header(default=None)):
    """El expediente de un atleta, línea a línea, para su copia en MySQL (Database/034). Sólo lee."""
    _check_token(x_service_token)
    from services.ai import track_record

    token = STORAGE_KEY.set(f"u{user_id}")
    try:
        lineas = track_record.lineas_crudas(current_storage_dir())
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": lineas}


@app.post("/bridge/support-bundle")
def support_bundle(req: SupportBundleRequest, x_service_token: str | None = Header(default=None)):
    """El diagnóstico de una consulta de ayuda, con su triaje (Beta 3.0, F3). Sin IA.

    Sólo lee: la traza de razonamiento, el registro de subidas y el expediente del atleta,
    sus últimos trabajos y el libro de redes. No escribe el espejo ni lanza nada.
    """
    _check_token(x_service_token)
    from bridge import admin
    from services.ai.diagnostico_de_ayuda import armar

    trabajos = [t for t in admin.trabajos({"generacion": JOBS_DIR, "garmin-upload": UPLOAD_JOBS}, 60)
                if t.get("user_id") == str(req.user_id)][:3]
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        diag = armar(current_storage_dir(), user_id=req.user_id, categoria=req.categoria,
                     session_key=req.session_key, php=req.php, errores_del_navegador=req.client_errors,
                     weekly_plan=req.weekly_plan, summary=req.summary, trabajos=trabajos,
                     sanear=admin.sanear)
    finally:
        STORAGE_KEY.reset(token)
    return {"status": "success", "data": diag}


@app.get("/bridge/usage-alerts")
def usage_alerts(threshold: float = 1.0, days: int = 7, today: str = "",
                 x_service_token: str | None = Header(default=None)):
    """Los atletas que gastaron más de `threshold` USD en un día (Beta 3.0, F2). Sólo admin."""
    _check_token(x_service_token)
    from core.usage_ledger import gasto_por_dia
    dias = max(1, min(90, int(days)))
    umbral = max(0.0, float(threshold))
    hoy = today if re.fullmatch(r"\d{4}-\d{2}-\d{2}", today or "") else date.today().isoformat()
    return {"status": "success", "data": {
        "threshold_usd": umbral, "days": dias,
        "alerts": gasto_por_dia(dias=dias, umbral=umbral, hoy=hoy),
    }}


@app.get("/bridge/adherence-global")
def adherence_global_endpoint(x_service_token: str | None = Header(default=None)):
    """Semanas prescritas contra semanas ENTRENADAS, en toda la instalacion.

    Es la unica metrica del panel que no puede salir de MySQL: el expediente
    vive aqui, en el disco del servicio, una linea por semana cerrada.

    Y es la que dice si el producto sirve. Todo lo demas que mide el panel --
    altas, entradas, planes generados -- dice si la gente lo PRUEBA. Un plan
    generado es curiosidad; un plan entrenado, y otra semana despues, es el
    producto funcionando. Si el embudo va bien y esto va mal, el problema no
    esta en como se entra: esta en lo que se entrega.
    """
    _check_token(x_service_token)
    from services.ai.track_record import adherence_global
    return {"status": "success", "data": adherence_global(RUNTIME_STORAGE)}


@app.get("/bridge/loop-funnel")
def loop_funnel_endpoint(x_service_token: str | None = Header(default=None)):
    """En cual de los cinco pasos del bucle se cae cada atleta.

    El panel media el PRIMER paso (genero) y el ULTIMO (volvio a entrar), y nada
    de los tres del medio -- que es donde estaria la explicacion de las 37
    generaciones con 0 semanas cerradas de la beta.

    Sale del mismo sitio que la adherencia (el expediente en disco) mas el
    registro de subidas a Garmin, asi que no le pide nada nuevo al atleta: son
    ficheros que ya se escriben.
    """
    _check_token(x_service_token)
    from services.ai.track_record import embudo_del_bucle
    return {"status": "success", "data": embudo_del_bucle(RUNTIME_STORAGE)}


@app.post("/bridge/generate-season")
def generate_season(req: GenerateSeasonRequest, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)
    spec = {
        "user_id": req.user_id,
        "profile": _con_ajuste(
            _profile_dict(req.profile, _clave_para_el_spec(req.garmin_password)),
            req.adjustment,
            "Ajuste solicitado tras revisar el plan",
        ),
        # El worker la aplica a SU proceso antes de correr nada: un proceso por
        # generacion significa un atleta y una zona horaria, y con eso los mas
        # de sesenta `date.today()` del pipeline pasan a decir el dia que el
        # atleta ve en su reloj en vez del que ve el servidor.
        "timezone": req.timezone,
        # season_plan_md/season_phases son el macrociclo ANTERIOR: el worker
        # los escribe en el espejo antes de generar, y el servidor los lee de
        # ahi para el bloque "Macrociclo ANTERIOR". Sin ellos el espejo los
        # borraba y el bloque salia vacio.
        "context": {
            "notebook": req.notebook,
            "checkins": req.checkins,
            "summary": req.summary,
            "season_plan_md": req.season_plan_md,
            "season_phases": req.season_phases,
        },
    }
    job_id = _spawn_job(JOBS_DIR, "season", spec)
    return {"status": "success", "data": {"started": True, "kind": "season", "job_id": job_id}}


@app.post("/bridge/generate-week")
def generate_week(req: GenerateWeekRequest, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)

    # La ventana de antelacion (2 dias) la decide PaginaLocal, no este puente:
    # se llama a su misma funcion con su mismo estado en disco. Vivia dentro de
    # la RUTA `/api/generate-week` de server.py, y el puente llama directamente
    # a `run_week_generation_task`, asi que la ruta nunca se ejecutaba y la
    # regla no llegaba a aplicarse en produccion.
    #
    # Rehacer no cuenta: reescribe la semana que YA empezo, no adelanta la
    # siguiente, y no hay ningun dato futuro que esperar.
    # Antes de tocar el espejo: con una generacion en marcha, escribir aqui la
    # semana de MySQL pisaba la que esa generacion estaba escribiendo. El 409
    # habria llegado igual un poco mas abajo, en _spawn_job -- pero despues de
    # pisarla. Ver _espejo_ocupado.
    if _espejo_ocupado(req.user_id):
        raise HTTPException(409, "Ya tienes una generación o subida en curso. Espera a que termine.")

    # `force` salta la ventana de antelación, pero NO el tope de semanas por delante
    # (ver ventana_de_generacion): por eso se mira también con `force`.
    token = STORAGE_KEY.set(f"u{req.user_id}")
    try:
        d = current_storage_dir()
        sa.write_weekly_plan(d, req.previous_weekly_plan)
        existente = pl_server._load_plan_for_adherence(d)
    finally:
        STORAGE_KEY.reset(token)
    ahora = _ahora_del_atleta(req.today)
    # Qué semana se va a pagar, con la misma función que la escribe (Beta 3.0, F2).
    objetivo = pl_server.semana_objetivo(existente, ahora, req.start_date or None, req.redo)

    # El tope de versiones de una misma semana. La semana automática no cuenta (decisión
    # del responsable) y sin la migración 028 PHP no manda la lista: sin tope.
    if req.versiones_previas is not None and not req.automatic:
        from services.ai.cupo_de_generaciones import cupo_de_la_semana
        cupo = cupo_de_la_semana(objetivo, req.versiones_previas)
        if not cupo["allowed"]:
            raise HTTPException(409, cupo)

    if not req.redo:
        ventana = pl_server.ventana_de_generacion(existente, ahora)
        if ventana.get("limit") or (not ventana["allowed"] and not req.force):
            # 409 y no 400: no es una peticion mal formada, es que todavia no
            # toca. El cliente distingue los dos para ofrecer "generar de
            # todas formas" solo en este caso -- y nunca con `limit`.
            raise HTTPException(409, ventana)

    spec = {
        "user_id": req.user_id,
        "profile": _con_ajuste(
            _profile_dict(req.profile, _clave_para_el_spec(req.garmin_password)),
            req.adjustment,
            "Ajuste pedido por el atleta al revisar esta semana",
        ),
        "redo": req.redo,
        "timezone": req.timezone,   # ver generate_season
        # Solo se atiende en la PRIMERA semana; la tarea lo ignora en cuanto
        # hay una anterior de la que continuar.
        "start_date": req.start_date,
        "skip_checkin": req.skip_checkin,
        # Semana automática: el worker no registra quien pregunte, y la semana lo sabe.
        "automatic": req.automatic,
        "context": {
            "notebook": req.notebook,
            "checkins": req.checkins,
            # Lo que levantó en el gimnasio: de aquí sale la progresión de carga.
            "strength_logs": req.strength_logs,
            "season_plan_md": req.season_plan_md,
            "season_phases": req.season_phases,
            "previous_weekly_plan": req.previous_weekly_plan,
            "summary": req.summary,
        },
    }
    job_id = _spawn_job(JOBS_DIR, "week", spec)
    return {"status": "success", "data": {"started": True, "kind": "week", "job_id": job_id,
                                          "target_week": objetivo.isoformat()}}


@app.get("/bridge/generate-status")
def generate_status(user_id: int, x_service_token: str | None = Header(default=None)):
    _check_token(x_service_token)

    # Drenar aqui es lo que mantiene viva la cola: el cliente pregunta cada
    # pocos segundos mientras espera, asi que si un worker murio sin avisar, el
    # hueco se reparte en la siguiente pregunta y no dentro de 25 minutos.
    _jobs.drain(JOBS_DIR, PYTHON_BIN, WORKER_PY)

    s = _newest_status(JOBS_DIR, user_id)
    if not s:
        return {"status": "success", "data": dict(_IDLE_STATUS)}
    en_cola = bool(s.get("queued"))
    puesto = _jobs.queue_position(JOBS_DIR, str(s.get("job_id") or "")) if en_cola else 0
    return {
        "status": "success",
        "data": {
            "kind": s.get("kind"),
            "job_id": s.get("job_id"),
            # En cola tambien es "running" para el cliente: la peticion sigue
            # viva y hay que seguir preguntando. Lo que cambia es el texto.
            "running": bool(s.get("running")) or en_cola,
            "queued": en_cola,
            "queue_position": puesto,
            "progress": int(s.get("progress") or 0),
            "status_text": (
                f"En cola — eres el nº {puesto}. Empieza en cuanto termine el que va delante."
                if en_cola and puesto else (s.get("status_text") or "")
            ),
            "done": bool(s.get("done")),
            "error": s.get("error"),
            "result": s.get("result") if s.get("done") else None,
            # El coach esta esperando que el atleta conteste. Ver
            # ProveedorWeb en worker.py.
            "awaiting_answers": bool(s.get("awaiting_answers")) and not s.get("done"),
            "questions": s.get("questions") if s.get("awaiting_answers") else None,
            # Ya pasó el momento de las preguntas: cerrar la pestaña es seguro.
            "questions_done": bool(s.get("questions_done")),
        },
    }


@app.post("/bridge/coach-answers")
def coach_answers(req: CoachAnswersRequest, x_service_token: str | None = Header(default=None)):
    """Deja las respuestas del atleta donde el worker de su generacion las espera.

    Solo en el job que esta esperando, y solo si es de este atleta: el job_id
    viene del cliente y nunca basta por si solo para escribir en una carpeta.
    """
    _check_token(x_service_token)
    for d in _job_dirs(JOBS_DIR):
        if d.name != req.job_id:
            continue
        s = _read_status(d)
        if not s or _job_user(d, s) != str(req.user_id) or not s.get("awaiting_answers"):
            break
        tmp = d / "answers.json.tmp"
        tmp.write_text(json.dumps({"answers": [str(a or "")[:2000] for a in req.answers]},
                                  ensure_ascii=False), "utf-8")
        tmp.replace(d / "answers.json")
        return {"status": "success", "data": {"received": True}}
    return JSONResponse(status_code=409, content={
        "status": "error", "message": "Tu entrenador ya no está esperando esas respuestas: tu semana siguió sin ellas.",
    })
