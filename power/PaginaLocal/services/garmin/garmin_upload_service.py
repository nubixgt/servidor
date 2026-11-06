import logging
import time
from dataclasses import dataclass
from datetime import date

from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload, WorkoutDay, is_garmin_exportable
from services.garmin.client import GarminConnectClient
from services.garmin.workout_builder import AthleteZoneConfig, UnsupportedSportError, build_garmin_workout

logger = logging.getLogger(__name__)

# Cuantas veces se intenta crear/programar un entreno antes de rendirse.
_REINTENTOS_DE_SUBIDA = 3


@dataclass
class UploadResult:
    date: str
    sport: str
    success: bool
    workout_id: int | None = None
    error: str | None = None
    session_slot: str = "unica"
    # El entreno que ESTE reemplaza en Garmin, ya borrado. Volver a subir una
    # sesion crea uno nuevo y retira el viejo: sin esto el calendario del
    # atleta acumulaba una entrada por cada clic en "Subir".
    replaced_workout_id: int | None = None
    # Filled by the post-upload read-back (see _verify_uploaded_workout).
    # Empty means Garmin stored what we sent; non-empty means it did not, and
    # the athlete needs to know before they go out and run it.
    warnings: list[str] | None = None


def _count_expected_steps(steps) -> int:
    """Executable steps Garmin should end up with, counting each repeat block's
    contents once (Garmin stores the block, not the expanded repetitions)."""
    total = 0
    for step in steps or []:
        if step.substeps:
            total += _count_expected_steps(step.substeps)
        else:
            total += 1
    return total


def _nested(step: dict) -> list:
    """A stored step's children, under whichever key this response uses.

    Garmin's own API nests under "workoutSteps"; the curated shape some
    tooling returns uses "steps". Accepting both keeps verification working
    against either without a second parser.
    """
    return step.get("workoutSteps") or step.get("steps") or []


def _stored_target_key(step: dict) -> str | None:
    target = step.get("targetType")
    if isinstance(target, dict):
        return target.get("workoutTargetTypeKey")
    return step.get("target_type")


def _count_stored_steps(steps) -> int:
    total = 0
    for step in steps or []:
        children = _nested(step)
        total += _count_stored_steps(children) if children else 1
    return total


def _sent_steps(sent: dict) -> list:
    """Los pasos del entreno tal como se mandaron, de todos sus tramos.

    De TODOS: un brick es un multideporte con varios `workoutSegments`, y
    contar solo el primero acusaria de discrepancia a cada brick que se suba.
    """
    pasos = []
    for segmento in (sent.get("workoutSegments") or []):
        pasos.extend(segmento.get("workoutSteps") or [])
    return pasos


def _sent_rounds(steps) -> list[int]:
    """Las iteraciones de cada bloque de repeticion enviado."""
    salida = []
    for paso in steps or []:
        hijos = _nested(paso)
        if hijos:
            iteraciones = paso.get("numberOfIterations")
            if iteraciones:
                salida.append(int(iteraciones))
            salida.extend(_sent_rounds(hijos))
    return salida


def _walk(steps) -> list:
    out = []
    for step in steps or []:
        out.append(step)
        out.extend(_walk(step.substeps))
    return out


def _collect_repeat_counts(steps) -> list[int]:
    counts = []
    for step in steps or []:
        children = _nested(step)
        if children:
            counts.append(int(step.get("numberOfIterations") or step.get("repeat_count") or 0))
            counts.extend(_collect_repeat_counts(children))
    return sorted(counts)


def _collect_stored_targets(steps) -> list[str]:
    found = []
    for step in steps or []:
        children = _nested(step)
        if children:
            found.extend(_collect_stored_targets(children))
        else:
            key = _stored_target_key(step)
            if key:
                found.append(key)
    return found


class GarminUploadService:
    """Uploads structured WorkoutDay plans to Garmin Connect.

    Reuses the exact create-then-schedule call pattern already proven
    working in the old Calendarizar.py (garth.client.post against
    connectapi.garmin.com) — only the JSON construction changed, from an
    LLM freehand attempt to services.garmin.workout_builder's deterministic
    output. Auth is unchanged: GarminConnectClient already handles OAuth
    token resume/refresh.
    """

    def __init__(self, client: GarminConnectClient):
        self._client = client

    def _buscar_recien_creado(self, nombre: str) -> int | None:
        """El entreno que quiza SI se creo antes de que expirara la espera.

        Un tiempo de espera agotado no dice que Garmin no hiciera nada: dice
        que no nos llego la respuesta. Reintentar a ciegas es como se acaba con
        dos entrenos identicos el mismo dia en el calendario del atleta. Asi
        que antes de reintentar se mira si ya esta, por su nombre exacto, entre
        los ultimos creados. Un fallo al mirar no es un fallo: se reintenta.
        """
        try:
            recientes = self._client.client.get_workouts(0, 10) or []
        except Exception:
            logger.debug("No se pudo comprobar si el entreno ya existia", exc_info=True)
            return None
        for w in recientes:
            if (w.get("workoutName") or "") == nombre:
                return int(w["workoutId"])
        return None

    def _upload_and_schedule(self, workout_json: dict, date_str: str) -> int:
        """Crea el entreno y lo programa, reintentando si Garmin tarda.

        No lo hacia, y por eso un unico pico de latencia dejaba una sesion sin
        subir con un aspa roja junto a un entreno perfectamente valido. Paso de
        verdad con la natacion del 2026-08-28, dos veces seguidas, mientras las
        otras seis sesiones subian sin problema en la misma llamada.
        """
        import garth

        nombre = workout_json.get("workoutName") or ""
        ultimo_error = None
        for intento in range(_REINTENTOS_DE_SUBIDA):
            try:
                response = garth.client.post(
                    "connectapi", "/workout-service/workout", json=workout_json, api=True)
                created = response.json() if hasattr(response, "json") else response
                workout_id = created.get("workoutId")
                if not workout_id:
                    raise RuntimeError(f"Garmin did not return a workoutId: {created}")
                break
            except Exception as exc:
                ultimo_error = exc
                if intento == _REINTENTOS_DE_SUBIDA - 1:
                    raise
                # Puede que si se creara y solo se perdiera la respuesta.
                ya_estaba = self._buscar_recien_creado(nombre) if nombre else None
                if ya_estaba:
                    logger.info("El entreno %r ya se habia creado pese al error; se reutiliza", nombre)
                    workout_id = ya_estaba
                    break
                logger.warning("Fallo al crear %r (intento %d): %s. Reintentando",
                               nombre, intento + 1, exc)
                time.sleep(2 ** intento)
        else:  # pragma: no cover - el for siempre sale por break o raise
            raise ultimo_error or RuntimeError("No se pudo crear el entreno")

        # Programar es idempotente en Garmin: repetirlo no duplica la entrada
        # del calendario, asi que aqui reintentar no tiene contrapartida.
        for intento in range(_REINTENTOS_DE_SUBIDA):
            try:
                garth.client.post("connectapi", f"/workout-service/schedule/{workout_id}",
                                  json={"date": date_str}, api=True)
                return workout_id
            except Exception as exc:
                if intento == _REINTENTOS_DE_SUBIDA - 1:
                    raise
                logger.warning("Fallo al programar %s (intento %d): %s. Reintentando",
                               workout_id, intento + 1, exc)
                time.sleep(2 ** intento)
        return workout_id

    def delete_workout(self, workout_id: int) -> bool:
        """Retira un entreno de la biblioteca de Garmin. Devuelve si se fue.

        Borrar el entreno borra tambien su entrada del calendario -- verificado
        contra la cuenta real: se programo uno para el 31/12, se borro, y el
        calendario quedo vacio. Por eso NO existe la opcion de "escribir solo
        en el calendario": en Garmin la entrada del calendario es un puntero a
        la biblioteca, no una copia.

        Un 404 es exito, no fallo: significa que ya no estaba (el atleta lo
        borro a mano), que es justo el estado que buscabamos.

        Quien llama SOLO debe pasar ids que esten en el registro de subidas.
        Ver services/garmin/upload_ledger.py.
        """
        import garth

        try:
            respuesta = garth.client.delete("connectapi", f"/workout-service/workout/{workout_id}", api=True)
            if getattr(respuesta, "status_code", 200) == 404:
                logger.info("El entreno %s ya no estaba en Garmin", workout_id)
            return True
        except Exception as exc:
            if "404" in str(exc):
                logger.info("El entreno %s ya no estaba en Garmin", workout_id)
                return True
            logger.warning("No se pudo borrar el entreno %s de Garmin: %s", workout_id, exc)
            return False

    async def upload_day(self, day: WorkoutDay, zones: AthleteZoneConfig,
                         replaces: int | None = None) -> UploadResult:
        if day.is_rest:
            return UploadResult(date=day.date, sport=day.sport, success=False, error="Rest day — nothing to upload", session_slot=day.session_slot)

        # v5 Track 5: the plan is designed in coaching terms and exported to
        # Garmin afterwards, so some sessions are PLANNED to live outside the
        # watch (plyometrics, rehab, prehab, mobility, bricks). That is a
        # designed outcome, not a failure -- the athlete already has the whole
        # session in `manual_instructions`, shown on the day's card and in the
        # printable plan.
        # Se RECALCULA al subir, no se lee del plan: `garmin_exportable` se
        # guarda el dia que se genera la semana, con la regla de ese dia. Asi
        # una regla corregida no llegaba nunca a las semanas ya generadas --
        # que es justo lo que el atleta intenta subir. Ver is_garmin_exportable.
        if not is_garmin_exportable(day.discipline, day.sport, day.is_rest, day.steps,
                                    getattr(day, "strength_block", None)):
            return UploadResult(
                date=day.date, sport=day.sport, success=False,
                error=(
                    f"Esta sesion ({day.discipline or day.sport}) no tiene una forma fiel en Garmin, asi "
                    "que va escrita en tu plan en vez de en el reloj. No hace falta que hagas nada."
                ),
                session_slot=day.session_slot,
            )

        try:
            workout_json = build_garmin_workout(day, zones)
        except UnsupportedSportError as exc:
            logger.info("Skipping %s (%s): %s", day.date, day.sport, exc)
            # Athletes see UploadResult.error verbatim in the frontend (see
            # web_app/static/app.js's pollGarminUploadStatus) -- the raw
            # exception was previously an English message straight from
            # workout_builder.py ("Sport 'strength' is not supported by this
            # builder yet"), confusing for a Spanish-speaking athlete and
            # also now stale ("strength" IS supported as of Fase 4; only
            # "brick" -- multi-sport-in-one-session -- still isn't).
            friendly_error = (
                f"Los entrenamientos combinados (brick) todavia no se pueden subir a Garmin "
                f"automaticamente. Sube el entrenamiento de '{day.date}' manualmente desde la app de Garmin."
                if day.sport == "brick"
                else f"El deporte '{day.sport}' todavia no se puede subir a Garmin automaticamente."
            )
            return UploadResult(date=day.date, sport=day.sport, success=False, error=friendly_error, session_slot=day.session_slot)

        try:
            workout_id = self._upload_and_schedule(workout_json, day.date)
            logger.info("Uploaded and scheduled workout for %s (%s) -> workout_id=%s", day.date, day.sport, workout_id)
            warnings = self._verify_uploaded_workout(workout_id, day, workout_json)
            # Se sube el nuevo ANTES de retirar el viejo, y no al reves. Si el
            # orden fuera el contrario y la subida fallase, el atleta se
            # quedaria sin la sesion en el calendario por haber pulsado
            # "volver a subir". Asi, lo peor que puede pasar es que se quede
            # con la que ya tenia.
            retirado = None
            if replaces and int(replaces) != int(workout_id):
                retirado = int(replaces) if self.delete_workout(int(replaces)) else None
            return UploadResult(
                date=day.date, sport=day.sport, success=True, workout_id=workout_id,
                session_slot=day.session_slot, warnings=warnings or None,
                replaced_workout_id=retirado,
            )
        except Exception as exc:
            logger.exception("Failed to upload workout for %s (%s)", day.date, day.sport)
            return UploadResult(date=day.date, sport=day.sport, success=False, error=str(exc), session_slot=day.session_slot)

    def _verify_uploaded_workout(self, workout_id: int, day: WorkoutDay,
                                 sent: dict | None = None) -> list[str]:
        """Read the workout back from Garmin and check it says what we sent.

        Uploading used to be fire-and-forget: a 200 response was treated as
        success, so anything Garmin quietly dropped or rewrote (it silently
        corrupts a repeat count when endCondition is malformed, for instance)
        reached the athlete's watch unnoticed. Reading it back is the only way
        to know what is actually on the device.

        Verification failing is NOT upload failing -- the workout is on Garmin
        either way. Discrepancies come back as warnings the athlete sees, and
        a read-back error is ignored rather than turned into a false alarm.
        """
        try:
            stored = self._client.client.get_workout_by_id(workout_id)
        except Exception:
            logger.warning("Could not read workout %s back from Garmin to verify it", workout_id, exc_info=True)
            return []

        if not isinstance(stored, dict):
            return []

        segments = stored.get("workoutSegments") or stored.get("segments") or []
        stored_steps = []
        for segment in segments:
            stored_steps.extend(segment.get("workoutSteps") or segment.get("steps") or [])

        warnings: list[str] = []

        # Se cuenta lo que se MANDO, no `day.steps`.
        #
        # Desde v7 no son lo mismo: una sesion de fuerza lleva UN paso en
        # `day.steps` -- el bloque que existe solo para que la app muestre una
        # duracion -- y sube como los ocho pasos que salen de `strength_block`.
        # Comparar contra `day.steps` acusaba de discrepancia a TODOS los dias
        # de fuerza ("Garmin guardo 8 pasos y el plan tenia 1"), con el entreno
        # perfectamente bien en Garmin. Una alarma que salta siempre deja de
        # ser una alarma, y esta existe para cazar lo que Garmin corrompe.
        enviados = _sent_steps(sent) if sent else None
        expected_count = (
            _count_stored_steps(enviados) if enviados is not None
            else _count_expected_steps(day.steps)
        )
        stored_count = _count_stored_steps(stored_steps)
        if stored_count != expected_count:
            warnings.append(
                f"Garmin guardó {stored_count} pasos y el plan tenía {expected_count}. "
                "Revisa el entrenamiento en Garmin Connect antes de hacerlo."
            )

        # Every repeat block must have kept its iteration count: this is the
        # one Garmin is known to corrupt silently.
        expected_rounds = (
            sorted(_sent_rounds(enviados)) if enviados is not None
            else sorted(s.repeat_count for s in _walk(day.steps) if s.substeps)
        )
        stored_rounds = sorted(_collect_repeat_counts(stored_steps))
        if expected_rounds and expected_rounds != stored_rounds:
            warnings.append(
                f"Las repeticiones no coinciden: se enviaron {expected_rounds} y Garmin guardó "
                f"{stored_rounds}."
            )

        # A target silently downgraded to "no target" means the athlete gets a
        # workout with no intensity guidance at all.
        #
        # Solo cuenta si SE ENVIO alguno. Desde la v1.2 un rodaje suave con
        # progresivos va sin objetivo a proposito -- calentamiento, enfriamiento
        # y recuperaciones no llevan zona, y los progresivos van por sensacion --,
        # y este aviso salia igual en la subida real del 2026-09-11 sobre una
        # sesion que Garmin habia guardado exactamente como se mando.
        enviados_con_objetivo = any(
            (getattr(getattr(s, "target_type", None), "value", getattr(s, "target_type", None)) or "none")
            != "none"
            for s in _walk(day.steps)
        )
        if day.sport not in ("swim", "strength") and enviados_con_objetivo:
            stored_targets = _collect_stored_targets(stored_steps)
            if stored_targets and all(t == "no.target" for t in stored_targets):
                warnings.append(
                    "Garmin no conservó ningún objetivo de intensidad (ritmo o frecuencia cardiaca) "
                    "para este entrenamiento."
                )

        if warnings:
            logger.warning("Read-back mismatch for workout %s (%s): %s", workout_id, day.date, warnings)
        return warnings

    async def upload_plan(
        self,
        payload: WeeklyPlanPayload,
        zones: AthleteZoneConfig,
        only_dates: list[str] | None = None,
        previous_ids: dict[str, int] | None = None,
    ) -> list[UploadResult]:
        """`only_dates` entries match either a plain "YYYY-MM-DD" (every
        session that date — used by "Subir Todo") or "YYYY-MM-DD|am"/"|pm"
        (one specific session — used by the per-day retry button, so
        re-uploading one side of a two-a-day never re-uploads the other).

        `previous_ids` mapea "fecha|franja" al entreno que esa sesion ya tiene
        en Garmin, para que subirla otra vez lo REEMPLACE en vez de duplicarlo.
        Misma clave que el registro (upload_ledger.clave)."""
        results: list[UploadResult] = []
        wanted = set(only_dates) if only_dates else None

        # Sin `only_dates`, de HOY en adelante. El plan guarda hasta 12 semanas
        # y "Subir a Garmin" mandaba las ~80 sesiones de todas ellas, cada una
        # con su lectura de verificacion: las semanas pasadas iban primero y la
        # que importa, la ultima; si Garmin empezaba a rechazar por ritmo, la
        # que fallaba era justo esa. Ademas las de hace mas de 7 dias se
        # borran despues (server.py), asi que se subian para nada.
        # Auditoria previa al merge, 2026-09-11.
        hoy = date.today().isoformat()

        for week in payload.weeks:
            for day in week.days:
                if wanted is not None and day.date not in wanted and f"{day.date}|{day.session_slot}" not in wanted:
                    continue
                if wanted is None and (day.date or "") < hoy:
                    continue
                if day.is_rest:
                    continue
                anterior = (previous_ids or {}).get(f"{day.date}|{day.session_slot}")
                results.append(await self.upload_day(day, zones, replaces=anterior))

        return results
