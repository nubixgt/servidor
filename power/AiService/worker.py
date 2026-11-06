"""Detached long-task runner.

Invoked by main.py as:  python worker.py <job_dir>

It runs ONE long PaginaLocal task (season / week generation, or Garmin upload)
to completion in its own process, so it survives the HTTP response returning
and any Passenger worker recycle.

    <job_dir>/spec.json    written by main.py  -> the request payload
    <job_dir>/status.json  written here (atomic replace) -> what generate-status returns
    <job_dir>/worker.log   stdout + stderr of this process
"""
from __future__ import annotations

import asyncio
import json
import os
import sys
import threading
import time
from pathlib import Path

JOB_DIR = Path(sys.argv[1]).resolve()
SPEC = json.loads((JOB_DIR / "spec.json").read_text("utf-8"))


def sin_contrasenas(spec: dict) -> dict:
    """El spec tal cual, menos la contraseña de Garmin, que sólo vive en memoria.

    main.py la escribe en claro para pasársela a este proceso, y hasta aquí
    sólo la subida borraba el fichero. Las generaciones de temporada y semana
    la llevan dentro de `profile` y se quedaban en disco para siempre: 20 de
    26 carpetas de trabajo del atleta 2 la tenían el 2026-09-11. El resto del
    spec se conserva porque es lo que se lee al depurar (CLAUDE.md).
    """
    limpio = {k: v for k, v in spec.items() if k != "garmin_password"}
    if isinstance(spec.get("profile"), dict):
        limpio["profile"] = {k: v for k, v in spec["profile"].items() if k != "garmin_password"}
    return limpio


(JOB_DIR / "spec.json").write_text(json.dumps(sin_contrasenas(SPEC), ensure_ascii=False), "utf-8")


def _usar_la_hora_del_atleta() -> None:
    """Este proceso vive en la zona horaria del atleta, no en la del servidor.

    ## Por qué aquí y no en cada llamada

    El plan se escribe con `date.today()` y `datetime.now()`, y eso aparece en
    más de sesenta sitios entre `services/ai` y `web_app/server.py`: qué día
    empieza la semana, qué día es hoy para el prompt, la ventana de doce
    semanas de hábitos, el corte de las métricas. Pasar una fecha por parámetro
    hasta los sesenta sería frágil, y bastaría con olvidar uno para que el plan
    volviera a hablar de otro día.

    Un proceso por generación (ver el docstring de arriba) hace que esto sea
    posible de la forma sencilla: UN atleta, UNA zona horaria, fijada antes de
    que corra nada. Los sesenta sitios pasan a decir la verdad sin tocar ni uno.

    ## Lo que arregla

    El servidor de producción no está en Guatemala. A las 8 de la tarde allí ya
    es el día siguiente en UTC, así que la app le escribía al atleta una semana
    que empezaba mañana cuando él veía hoy, y el calendario le decía que
    entrenara un día que para él ya había pasado.

    ## SÓLO donde existe tzset, y esto no es una formalidad

    La primera versión de esto ponía `TZ` siempre y sólo llamaba a `tzset()` si
    existía, con un comentario que decía que en Windows "no hace nada".

    Es falso, y se midió: el CRT de Windows lee `TZ` al arrancar el proceso,
    NO entiende los nombres IANA, y ante uno que no reconoce se va a **UTC**.

        TZ=(sin poner)        -> now() = 2026-08-31 19:44   (correcto)
        TZ=America/Guatemala  -> now() = 2026-09-01 01:44   (+6h, UTC)

    O sea que en Windows introducía exactamente el fallo que venía a arreglar,
    invertido: el plan se escribía seis horas por delante del atleta. Se vio en
    el archivo de razonamiento de una generación real, fechado a las 01:34 del
    día siguiente cuando el trabajo salió a las 19:29.

    Así que `TZ` no se toca si no hay `tzset`. En Windows la hora de la máquina
    ya es la del atleta (es su portátil), que es la respuesta correcta de todas
    formas.

    ## El otro límite

    Una zona horaria que el sistema no conozca se ignora en vez de reventar la
    generación — una hora mal es un mal plan; una excepción aquí es ningún plan.
    """
    tz = str(SPEC.get("timezone") or "").strip()
    if not tz:
        return

    import time
    if not hasattr(time, "tzset"):
        print(f"Zona horaria {tz} recibida; este sistema no tiene tzset (Windows), "
              "se usa la hora de la maquina SIN tocar TZ (ponerla la llevaria a UTC)")
        return

    import os
    anterior = os.environ.get("TZ")
    try:
        os.environ["TZ"] = tz
        time.tzset()
        # Que la zona se aplicara no significa que se entendiera: una cadena
        # que no reconoce tambien acaba en UTC, en silencio.
        if time.tzname and time.tzname[0] in ("UTC", "GMT") and not tz.startswith(("UTC", "GMT", "Etc/")):
            raise ValueError(f"el sistema no reconoce {tz} (quedo en {time.tzname[0]})")
        print(f"Hora del atleta: {tz} (ahora son las {time.strftime('%Y-%m-%d %H:%M')})")
    except Exception as exc:  # noqa: BLE001
        if anterior is None:
            os.environ.pop("TZ", None)
        else:
            os.environ["TZ"] = anterior
        time.tzset()
        print(f"No se pudo aplicar la zona horaria {tz} ({exc}); se usa la del servidor")


_usar_la_hora_del_atleta()

# Importing this triggers the in-memory monkeypatch of PaginaLocal (storage
# redirection, PROFILES_FILE, etc.). Must come before anything touches pl_server.
from bridge import storage_adapter as sa  # noqa: E402
from bridge.paginalocal import STORAGE_KEY, current_storage_dir, pl_server  # noqa: E402


def _descifrar_claves(spec: dict) -> None:
    """La contraseña llega cifrada en el spec (ver main.py::_clave_para_el_spec).

    Se descifra aqui, EN MEMORIA: el fichero ya se reescribio sin ella.
    `decrypt_secret` devuelve intacto lo que no lleve el prefijo, asi que un
    spec viejo sigue funcionando.
    """
    for contenedor in (spec, spec.get("profile") if isinstance(spec.get("profile"), dict) else None):
        if contenedor and contenedor.get("garmin_password"):
            try:
                contenedor["garmin_password"] = pl_server.decrypt_secret(contenedor["garmin_password"])
            except Exception:  # noqa: BLE001 - sin descifrar, Garmin dira que la clave no vale
                print("No se pudo descifrar la clave de Garmin del spec")


_descifrar_claves(SPEC)

STATUS: dict = {
    "kind": SPEC["kind"],
    "job_id": JOB_DIR.name,
    # Cada _flush() REEMPLAZA status.json entero, asi que el dueño del trabajo
    # tiene que viajar aqui dentro: si no, el primer flush borraria el user_id
    # que main.py escribio y el trabajo dejaria de ser atribuible a nadie.
    "user_id": SPEC.get("user_id"),
    # Este proceso solo arranca cuando la cola le dio paso, asi que en cuanto
    # existe ya no esta en espera. Cada _flush() reescribe status.json entero:
    # si faltara, el primer flush lo devolveria a la cola y se relanzaria.
    "queued": False,
    "running": True,
    "progress": 5,
    "status_text": "Iniciando...",
    "done": False,
    "error": None,
    "result": None,
}


_FLUSH_LOCK = threading.Lock()


def _flush() -> None:
    # Con candado: lo llaman el hilo de progreso y el de las preguntas del
    # coach a la vez, sobre el mismo .tmp; un json.dumps mientras el otro
    # anade claves, o dos replace cruzados en Windows, perdian las preguntas o
    # las respuestas sin error visible. Auditoria previa al merge.
    with _FLUSH_LOCK:
        tmp = JOB_DIR / f"status.json.{threading.get_ident()}.tmp"
        tmp.write_text(json.dumps(STATUS, ensure_ascii=False), "utf-8")
        tmp.replace(JOB_DIR / "status.json")  # atomic on POSIX


# Cuanto espera el coach a que el atleta conteste antes de seguir sin respuesta.
# Diez minutos: suficiente para leer tres preguntas y escribir, y no tanto como
# para que un atleta que cerro la app deje su generacion colgada media hora.
ESPERA_RESPUESTAS_S = int(os.getenv("COACH_ESPERA_RESPUESTAS_S", "600"))


class ProveedorWeb:
    """Le hace llegar al atleta las preguntas del coach a traves de este job.

    Publica las preguntas en status.json -- que es lo que el frontend ya
    consulta cada pocos segundos --, y espera a que el AiService deje las
    respuestas en `answers.json` dentro de la carpeta del job. Es la misma
    carpeta que ya lleva spec.json y status.json: un proceso por generacion y
    un fichero por mensaje, sin nada que coordinar entre procesos.

    Sin respuesta en ESPERA_RESPUESTAS_S, sigue: el coach decide con los
    datos. Una pregunta nunca puede dejar una generacion colgada.
    """

    def __init__(self, job_dir: Path) -> None:
        self.respuestas = job_dir / "answers.json"

    def preguntar(self, preguntas: list[dict]) -> list[str]:
        self.respuestas.unlink(missing_ok=True)
        STATUS["questions"] = preguntas
        STATUS["awaiting_answers"] = True
        STATUS["status_text"] = "Tu coach tiene preguntas para ti"
        _flush()
        limite = time.time() + ESPERA_RESPUESTAS_S
        respuestas: list[str] = []
        while time.time() < limite:
            if self.respuestas.is_file():
                try:
                    datos = json.loads(self.respuestas.read_text("utf-8"))
                    respuestas = [str(r or "") for r in (datos.get("answers") or [])]
                    break
                except (OSError, ValueError):
                    pass  # a medio escribir: se relee en la siguiente vuelta
            time.sleep(2)
        STATUS["awaiting_answers"] = False
        STATUS["questions"] = None
        STATUS["questions_done"] = True
        # "Prefiero no contestar" manda cadenas vacías: eso no son respuestas.
        contesto = any(r.strip() for r in respuestas)
        STATUS["status_text"] = "Planificando con tus respuestas..." if contesto else "Seguimos con tus datos..."
        _flush()
        return respuestas

    def sin_preguntas(self) -> None:
        """El coach decidió no preguntar: desde aquí el atleta ya puede cerrar.

        La pantalla decía "puedes cerrar la pestaña" desde el primer segundo,
        que era verdad antes de que el coach preguntara a mitad de generación.
        Un atleta que cerraba entonces no veía las preguntas: la generación
        esperaba diez minutos y seguía sin sus respuestas. `questions_done` es
        lo que le deja a la pantalla decir cuándo cerrar es seguro.
        """
        STATUS["questions_done"] = True
        _flush()


# Tope total de una generación. Una real tarda 7-12 minutos, más hasta 10 si el
# coach espera respuestas.
#
# Existe por lo que pasó el 2026-09-14: la semana de la atleta 3 se quedó 19
# minutos esperando al experto de fisiología, cuya llamada a OpenAI nunca
# volvió. Como `_poll_progress` sigue tocando `status.json` cada dos segundos,
# para el AiService el worker estaba vivo: ocupaba un hueco de la cola, la
# pantalla decía "Analizando actividades recientes..." y nadie podía volver a
# generar. Ahora las llamadas tienen tiempo límite (model_config.TIEMPO_LIMITE_LLM_S),
# y esto es la red de debajo por si algo más se cuelga.
TOPE_DE_LA_GENERACION_S = int(os.getenv("WORKER_TOPE_S", str(40 * 60)))


def _vigilar_el_tope(stop: threading.Event) -> None:
    """Si la generación pasa del tope, se da por fallida, se dice y se libera el hueco."""
    if stop.wait(TOPE_DE_LA_GENERACION_S):
        return
    STATUS["running"] = False
    STATUS["done"] = True
    STATUS["progress"] = 100
    STATUS["awaiting_answers"] = False
    STATUS["error"] = (
        f"La generación no terminó en {TOPE_DE_LA_GENERACION_S // 60} minutos: uno de los servicios de "
        "inteligencia artificial dejó de responder. Vuelve a intentarlo en unos minutos."
    )
    _flush()
    try:
        from bridge import jobs as _jobs
        _jobs.drain(JOB_DIR.parent, sys.executable, Path(__file__).resolve())
    except Exception:  # noqa: BLE001
        pass
    os._exit(3)


def _poll_progress(stop: threading.Event, es: dict) -> None:
    while not stop.is_set():
        try:
            STATUS["progress"] = max(STATUS["progress"], int(es.get("progress") or 0))
            txt = es.get("status")
            # Mientras el coach espera respuestas, su texto manda: si no, este
            # hilo lo pisaria a los 2 s con el del pipeline.
            if txt and txt not in ("idle", "failed") and not STATUS.get("awaiting_answers"):
                STATUS["status_text"] = txt
            _flush()
        except Exception:  # noqa: BLE001
            pass
        stop.wait(2.0)


async def _main() -> None:
    # Una semana automática no pregunta nada: dicho desde el PRIMER estado. Se ponía más abajo y el
    # primer sondeo de la pantalla («Iniciando…», 5 %) pedía quedarse a esperar las preguntas del
    # coach (prueba del 2026-10-19).
    if SPEC.get("kind") == "week" and SPEC.get("automatic"):
        STATUS["questions_done"] = True
    _flush()
    token = STORAGE_KEY.set(f"u{SPEC['user_id']}")
    kind = SPEC["kind"]
    es = pl_server.execution_status if kind in ("season", "week") else pl_server.garmin_upload_status
    stop = threading.Event()
    threading.Thread(target=_poll_progress, args=(stop, es), daemon=True).start()
    threading.Thread(target=_vigilar_el_tope, args=(stop,), daemon=True).start()

    try:
        storage_dir = current_storage_dir()

        # profiles.json guarda la contraseña de Garmin cifrada, y hasta v7.1
        # era UN fichero global indexado por el nombre visible del atleta. Cada
        # subida hacia `save_profiles({name: ...})`, que reemplaza el
        # diccionario entero: borraba las credenciales de los demas, y dos
        # atletas que se llamaran igual compartian entrada. Aqui pasa a vivir
        # dentro del directorio del usuario, que ya esta aislado por STORAGE_KEY.
        pl_server.PROFILES_FILE = storage_dir / "profiles.json"

        ctx = SPEC.get("context", {})
        sa.write_notebook(storage_dir, ctx.get("notebook", []))
        sa.write_checkins(storage_dir, ctx.get("checkins", []))
        sa.write_season_plan(storage_dir, ctx.get("season_plan_md"), ctx.get("season_phases"),
                             (SPEC.get("profile") or {}).get("week_start_day"))
        sa.write_weekly_plan(storage_dir, ctx.get("weekly_plan") or ctx.get("previous_weekly_plan"))
        sa.write_summary(storage_dir, ctx.get("summary"))
        es.update({"running": True, "progress": 5, "status": "Iniciando...", "error": None, "result": None})

        automatica = kind == "week" and bool(SPEC.get("automatic", False))
        if kind in ("season", "week"):
            # El coach puede preguntarle al atleta a mitad de la generacion.
            # Ver ProveedorWeb arriba y PaginaLocal/services/ai/preguntas_del_coach.py.
            # Una semana AUTOMÁTICA no tiene a nadie delante: sin proveedor el paso de
            # las preguntas no pregunta ni gasta (Backend AutoWeekService, 2026-09-24).
            from services.ai.preguntas_del_coach import usar_proveedor
            if automatica:
                usar_proveedor(None)
                STATUS["questions_done"] = True
                _flush()
            else:
                usar_proveedor(ProveedorWeb(JOB_DIR))

        if kind == "season":
            await pl_server.run_generation_task(SPEC["profile"])
            STATUS["error"] = pl_server.execution_status.get("error")
            # Sin resultado si fallo: `read_back` nunca devuelve vacio (lee lo
            # que haya en el espejo, que es el plan ANTERIOR), y PHP guardaba
            # eso como si fuera nuevo -- una entrada duplicada en Historial y
            # "generado hoy" sobre un plan viejo. Auditoria previa al merge.
            STATUS["result"] = None if STATUS["error"] else sa.read_back(storage_dir)

        elif kind == "week":
            await pl_server.run_week_generation_task(
                SPEC["profile"],
                rehacer=SPEC.get("redo", False),
                # Lo que levantó en el gimnasio (Database/026): sin esto la carga
                # de su programa no puede progresar contra nada.
                registro_de_fuerza=ctx.get("strength_logs") or [],
                # Desde qué día quiere el atleta que arranque la semana. Sólo
                # llega en la PRIMERA, cuando no hay una anterior de la que
                # continuar: a las 8 de la tarde no tiene sentido escribirle
                # entrenos para hoy, y a las 11 de la mañana puede que sí.
                inicio=SPEC.get("start_date") or None,
                # "Saltar" en el check-in: la semana no usa ninguno anterior.
                sin_checkin=bool(SPEC.get("skip_checkin", False)),
                automatica=automatica,
            )
            STATUS["error"] = pl_server.execution_status.get("error")
            STATUS["result"] = None if STATUS["error"] else sa.read_back(storage_dir)

        elif kind == "garmin-upload":
            name = SPEC["profile"].get("name") or "Atleta"
            stored = dict(SPEC["profile"])
            stored["name"] = name
            stored["garmin_password"] = pl_server.encrypt_secret(SPEC["garmin_password"])
            pl_server.save_profiles({name: stored})
            # cifrada en profiles.json; el spec en disco ya va sin ella (sin_contrasenas)
            await pl_server.run_garmin_upload_task(name, SPEC.get("dates"))
            STATUS["result"] = pl_server.garmin_upload_status.get("result")
            STATUS["error"] = pl_server.garmin_upload_status.get("error")

        else:
            STATUS["error"] = f"kind desconocido: {kind}"

    except Exception as e:  # noqa: BLE001
        STATUS["error"] = repr(e)
    finally:
        stop.set()
        STORAGE_KEY.reset(token)
        STATUS["running"] = False
        STATUS["done"] = True
        STATUS["progress"] = 100
        _flush()
        # Al liberar el hueco, dar paso al siguiente. Si esto falla, la cola
        # sigue avanzando en el siguiente sondeo del cliente -- pero no hacerlo
        # dejaria esperando a alguien que ya tiene sitio.
        try:
            from bridge import jobs as _jobs
            _jobs.drain(JOB_DIR.parent, sys.executable, Path(__file__).resolve())
        except Exception:  # noqa: BLE001
            pass


if __name__ == "__main__":
    # El libro de redes (core/redes_ledger.py, Beta 3.0 F1) sólo se enciende aquí: las
    # redes corren en el worker, y así ni las pruebas de PaginaLocal ni las del AiService
    # (que importan el puente) anotan correcciones que nunca pasaron.
    from core import redes_ledger
    redes_ledger.activar()
    asyncio.run(_main())
