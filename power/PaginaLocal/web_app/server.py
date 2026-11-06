import dataclasses
import json
import logging
import os
import shutil
import sys
import threading
import time
import traceback
from datetime import date, datetime, timedelta
from pathlib import Path

from pydantic import BaseModel

# Windows consoles default to cp1252, which can't encode characters like "→"
# that show up in log messages — avoids "Logging error" / UnicodeEncodeError spam.
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

# Add the project root to sys.path to enable core/services imports
PROJECT_ROOT = Path(__file__).resolve().parent.parent
sys.path.append(str(PROJECT_ROOT))

from fastapi import BackgroundTasks, FastAPI, HTTPException, Request, Response
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse, HTMLResponse, JSONResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles

from core import auth, usage_ledger

# Import core coach services
from core.config import reload_config
from core.credential_crypto import decrypt_secret, encrypt_secret
from services.ai.adherence import build_adherence_report, render_adherence_block
from services.ai.ai_settings import ai_settings
from services.ai.athlete_basics import render_basics_block
from services.ai.charts import build_charts
from services.ai.checkin import (
    checkin_to_state,
    latest_checkin,
    load_checkins,
    normalize_checkin,
    save_checkin,
)
from services.ai.coach_chat import ask_coach, build_coach_context
from services.ai.fitness_series import build_fitness_series
from services.ai.goal_realism import assess_goals, build_goal_evidence, competitions_missing_distance
from services.ai.health_context import health_flags, render_health_block
from services.ai.langgraph.schemas.plan_outputs import WeekBlock, WeeklyPlanPayload
from services.ai.langgraph.utils.workflow_cost_tracker import NODE_STATUS_LABELS_ES
from services.ai.langgraph.workflows.planning_workflow import (
    run_analysis_and_season_planning,
    run_week_generation,
)
from services.ai.load_state import build_load_state, load_state_to_state
from services.ai.race_distances import normalizar_competicion, solo_las_que_vienen
from services.ai.training_response import build_training_response
from services.ai.weekly_load import (
    carga_semanal_real,
    combinar_con_guardadas,
    guardar_tasas,
    tasas_de_carga,
)
from services.ai.niggle_constraints import collect as collect_niggles
from services.ai.niggle_constraints import niggles_to_state
from services.ai.niggle_constraints import unaddressed as unaddressed_niggles
from services.ai.notebook import (
    CATEGORIES_ES as NOTEBOOK_CATEGORIES,
)
from services.ai.notebook import (
    STATUS_ACTIVE as NOTEBOOK_ACTIVE,
)
from services.ai.notebook import (
    STATUS_PROPOSED as NOTEBOOK_PROPOSED,
)
from services.ai.notebook import (
    STATUS_RETIRED as NOTEBOOK_RETIRED,
)
from services.ai.notebook import (
    active_entries as notebook_active,
)
from services.ai.notebook import (
    add_entry as notebook_add,
)
from services.ai.notebook import (
    delete_entry as notebook_delete,
)
from services.ai.notebook import (
    load_notebook,
    render_notebook_block,
    render_peticiones_de_entrenamiento,
)
from services.ai.notebook import (
    propose_entries as notebook_propose,
)
from services.ai.notebook import (
    update_entry as notebook_update,
)
from services.ai.prescription import build_prescription, format_pace, prescription_to_state
from services.ai.previous_season import render_previous_season
from services.ai.reasoning_archive import (
    REASONING_DIRNAME,
    borrar_trazas,
    latest_reasoning_entry,
    list_reasoning_entries,
    load_reasoning_entry,
    podar_trazas,
    save_reasoning,
)
from services.ai.recovery_state import build_recovery_snapshot, recovery_to_state
from services.ai.rendering.printable_renderer import (
    markdown_to_html,
    render_printable_plan,
    render_printable_season,
)
from services.ai.track_record import lo_vivido, record_week, render_track_record
from services.ai.aerobic_decoupling import medir_deriva, render_deriva
from services.ai.intensity_distribution import es_sesion_dura, medir_distribucion, referencias_de_prescripcion
from services.ai.respuesta_a_las_fases import (
    fase_de_la_semana,
    marcadores_de_la_semana,
    render_respuesta_a_las_fases,
)
from services.ai.training_habits import (
    build_training_habits,
    cargar_habitos,
    fijos_poco_hechos,
    guardar_habitos,
    render_training_habits,
)
from services.ai.utils.plan_storage import sanitize_storage_name
from services.garmin import ExtractionConfig, TriathlonCoachDataExtractor, upload_ledger
from services.garmin.client import GarminConnectClient
from services.garmin.extraction_windows import (
    ACUTE_LOAD_WINDOW_DAYS,
    CHRONIC_LOAD_WINDOW_DAYS,
    SEASON_WINDOW,
    WEEK_WINDOW,
)
from services.garmin.garmin_upload_service import GarminUploadService, UploadResult
from services.garmin.workout_builder import compute_zone_config


# --- Garmin Data Deep Serializer ---
def garmin_data_to_dict(garmin_data) -> dict:
    """Recursively serialize a GarminData dataclass (and all nested dataclasses)
    to plain Python dicts/lists so json.dumps() works without errors."""
    def _convert(obj):
        if dataclasses.is_dataclass(obj) and not isinstance(obj, type):
            return {f.name: _convert(getattr(obj, f.name)) for f in dataclasses.fields(obj)}
        elif isinstance(obj, list):
            return [_convert(item) for item in obj]
        elif isinstance(obj, dict):
            return {k: _convert(v) for k, v in obj.items()}
        elif isinstance(obj, (str, int, float, bool, type(None))):
            return obj
        else:
            # Fallback for any other types (e.g. enums, dates)
            return str(obj)
    return _convert(garmin_data)

# Set up redirecting logs and print statements to capture stdout for the UI
LOG_LIST = []

class StdoutRedirector:
    def __init__(self, log_list):
        self.log_list = log_list
        self.stdout = sys.stdout

    def write(self, message):
        self.stdout.write(message)
        clean = message.strip()
        if clean:
            # Format message with time
            time_str = datetime.now().strftime("%H:%M:%S")
            self.log_list.append(f"[{time_str}] {clean}")
            # Keep logs limited to 1500 lines
            if len(self.log_list) > 1500:
                self.log_list.pop(0)

    def flush(self):
        self.stdout.flush()

    def isatty(self):
        return hasattr(self.stdout, "isatty") and self.stdout.isatty()

sys.stdout = StdoutRedirector(LOG_LIST)

# Also configure python logging to write to stdout so it gets captured
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)]
)
logger = logging.getLogger("web_app_server")

app = FastAPI(title="Garmin AI Coach")

# CORS: this app serves its own frontend from the same origin it runs on
# (see the StaticFiles mount at the bottom of this file), so the browser
# doesn't need cross-origin access at all in normal use -- allow_origins=["*"]
# combined with allow_credentials=True (both were set here before) is a
# request that accepts credentialed requests from ANY origin, which is
# exactly the CORS misconfiguration that lets a malicious page in the user's
# browser call this API using their session. Default to just this app's own
# local origins; CORS_ALLOWED_ORIGINS in .env can override with a comma-
# separated list if this is ever deployed somewhere other than localhost.
# La cookie de sesion sale con Secure (solo por HTTPS) salvo que se diga lo
# contrario. En localhost no hay HTTPS y el navegador la descartaria, asi que
# el desarrollo la apaga -- pero por omision va cerrada: un despliegue que se
# olvide de configurarla queda seguro, no abierto.
def _env_flag(name: str, default: str = "true") -> bool:
    """Lee una bandera booleana del entorno. Fuera como funcion para poder
    probar el valor POR OMISION sin recargar el modulo -- recargarlo en un
    test rebindea los globales y otro test que ya tenia una referencia al
    objeto viejo deja de ver los cambios (paso: rompio el 409 de
    delete_current_generation en test_plan_history)."""
    return os.getenv(name, default).strip().lower() not in ("0", "false", "no")


COOKIE_SECURE = _env_flag("COOKIE_SECURE")


def _demand_credential_key() -> None:
    """Sin GARMIN_CREDENTIALS_KEY las contrasenas de Garmin se guardan EN
    CLARO, y hasta v7 eso solo dejaba un WARNING en el log -- de los que nadie
    lee. Son credenciales de un TERCERO: la cuenta de Garmin del atleta, no un
    dato de esta app.

    Se exige solo cuando ya hay usuarios creados, o sea cuando esto dejo de
    ser la instalacion local de una persona. Un clon recien hecho sigue
    arrancando sin configurar nada, que es lo que hace que el proyecto se
    pueda probar; en el momento en que hay una cuenta, hay datos ajenos que
    proteger y el arranque se planta.
    """
    if os.getenv("GARMIN_CREDENTIALS_KEY"):
        return
    if not auth.any_users_exist():
        logger.warning(
            "GARMIN_CREDENTIALS_KEY no esta puesta: las contrasenas de Garmin se guardaran en "
            "texto plano. Configurala antes de dar de alta a nadie (ver .env.example)."
        )
        return
    raise RuntimeError(
        "GARMIN_CREDENTIALS_KEY no esta configurada y esta instalacion ya tiene usuarios. "
        "Sin ella las contrasenas de Garmin de tus atletas se guardarian en texto plano. "
        "Generala con:  python -c \"from cryptography.fernet import Fernet; "
        "print(Fernet.generate_key().decode())\"  y ponla en .env (ver .env.example)."
    )


_demand_credential_key()

_cors_origins_env = os.getenv("CORS_ALLOWED_ORIGINS", "")
CORS_ALLOWED_ORIGINS = [o.strip() for o in _cors_origins_env.split(",") if o.strip()] or [
    "http://localhost:8000",
    "http://127.0.0.1:8000",
]
app.add_middleware(
    CORSMiddleware,
    allow_origins=CORS_ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# Lo unico que se sirve sin sesion. Todo lo demas -- rutas de API que aun no
# existen incluidas -- requiere una, porque este middleware es fail-closed: un
# endpoint nuevo nace protegido y hay que decidir explicitamente abrirlo.
# Poner el guardia en cada ruta habria sido lo contrario: 33 decisiones
# correctas y la 34 olvidada.
_PUBLIC_PATHS = frozenset({
    "/login", "/login.html", "/login.css", "/login.js",
    "/api/auth/login", "/api/auth/logout", "/api/auth/me",
    "/favicon.ico",
})

# Los ESTATICOS del front tambien son publicos, y esto no es una concesion:
# es la unica forma de que el guardia no rompa la propia pagina que protege.
#
# El bug que lo demostro: /styles.css y /app.js no estaban en la lista, asi
# que sin sesion devolvian un 302 a /login. El navegador pedia app.js y
# recibia el HTML del login -> error de sintaxis -> app.js no se ejecutaba.
# Con un index.html cacheado de antes de que existiera el login, el atleta
# veia la app entera muerta: sin perfil, sin botones, sin manera de iniciar
# sesion. Un guardia que sirve HTML donde se espera JavaScript no bloquea:
# rompe.
#
# No hay nada que proteger aqui. Son el mismo CSS y el mismo JavaScript para
# todos los atletas, sin un solo dato dentro; TODO lo que es de alguien viaja
# por /api/, que sigue cerrado. Servir el cascaron a un anonimo es lo que hace
# cualquier aplicacion web con login.
_PUBLIC_SUFFIXES = (".css", ".js", ".map", ".woff", ".woff2", ".ttf",
                    ".png", ".jpg", ".jpeg", ".svg", ".ico", ".webp")


def _is_public(path: str) -> bool:
    return path in _PUBLIC_PATHS or path.endswith(_PUBLIC_SUFFIXES)


@app.middleware("http")
async def _require_session(request: Request, call_next):
    """La puerta. Sin sesion valida no se pasa de /login.

    Antes de v7 no habia ninguna: cada ruta recibia `?profile=` y no
    comprobaba de quien era, asi que dos atletas en el mismo servidor se leian
    (y se borraban) el plan mutuamente, y `POST /api/server/shutdown` dejaba a
    cualquiera apagar el servidor de todos.

    Si todavia no hay usuarios creados, se deja pasar: es la instalacion
    recien hecha, y bloquearla dejaria al operador sin forma de entrar a
    crear el primero. `scripts/nuevo_usuario.py` cierra esa ventana, y
    /api/auth/me la reporta para que la UI lo diga en voz alta.
    """
    path = request.url.path
    if _is_public(path) or not auth.any_users_exist():
        return await call_next(request)

    user = auth.session_user(request.cookies.get(auth.SESSION_COOKIE))
    if user is None:
        if path.startswith("/api/"):
            return JSONResponse({"detail": "Inicia sesion."}, status_code=401)
        return RedirectResponse("/login", status_code=302)

    # Una contrasena que viajo por correo no puede quedarse siendo LA
    # contrasena. Hasta que la cambie, este usuario solo puede cambiarla.
    if user.get("must_change_password") and path not in ("/api/auth/change-password", "/"):
        if path.startswith("/api/"):
            return JSONResponse(
                {"detail": "Cambia tu contrasena temporal antes de continuar.",
                 "must_change_password": True},
                status_code=403,
            )

    request.state.user = user

    # El cerrojo de verdad. 22 rutas reciben `?profile=` y ninguna comprobaba
    # de quien era: escribir otro nombre bastaba para leer, regenerar o
    # BORRAR el plan ajeno. Comprobarlo aqui y no ruta por ruta es
    # deliberado -- 22 comprobaciones correctas y la 23 olvidada es como se
    # ve este bug la segunda vez. Una ruta nueva que acepte `?profile=` queda
    # cubierta sin que nadie se acuerde.
    #
    # Las rutas que reciben el perfil en el CUERPO (generar, chat, check-in,
    # subir a Garmin) no pasan por aqui: leer el cuerpo en un middleware lo
    # consume para el endpoint. Esas llaman a _allowed_profile() ellas mismas.
    if not user.get("is_admin"):
        pedido = request.query_params.get("profile")
        propio = user.get("profile")
        if pedido and pedido != propio:
            logger.warning(
                "Usuario '%s' pidio '%s' en %s y solo puede ver '%s'",
                user.get("username"), pedido, path, propio,
            )
            return JSONResponse({"detail": "Ese plan no es tuyo."}, status_code=403)

    return await call_next(request)


@app.middleware("http")
async def _no_cache_static_assets(request: Request, call_next):
    """StaticFiles serves app.js/index.html/styles.css with no explicit
    Cache-Control, so browsers can (and did, in practice) keep serving a
    stale app.js after a real code change -- restarting the server doesn't
    help, since the browser never re-requests the file at all. This forces
    revalidation on every load (still cheap: the browser gets a 304 when
    unchanged) instead of trusting a blind disk cache. Only applies to the
    static frontend files, never to API responses (which already set their
    own semantics via each endpoint's return value)."""
    response = await call_next(request)
    path = request.url.path
    if path == "/" or path.endswith((".js", ".css", ".html")):
        response.headers["Cache-Control"] = "no-cache"
    return response

# Secure path for user credentials and profiles
# La configuracion de lanzamiento (services/ai/ai_settings.py, AIMode.PRO).
# Un perfil guardado antes de v7 puede traer "standard"; se respeta, porque es
# el interruptor de emergencia de costo. Lo que ya no ocurre es que un perfil
# NUEVO nazca en Lite por defecto.
DEFAULT_AI_MODE = "pro"

SECURE_DATA_DIR = PROJECT_ROOT / "user_secure_data"
PROFILES_FILE = SECURE_DATA_DIR / "profiles.json"
DATA_DIR = PROJECT_ROOT / "data"

SECURE_DATA_DIR.mkdir(exist_ok=True)
DATA_DIR.mkdir(exist_ok=True)

STORAGE_DIR = DATA_DIR / "storage"


def _profile_storage_dir(profile_name: str) -> Path:
    """Per-profile directory for generated plans/reports. Previously all
    profiles clobbered a single shared data/weekly_plan.md — this keys
    storage by profile name instead.

    Uses the same sanitize_storage_name() as services/ai/utils/plan_storage.py's
    FilePlanStorage — they used to diverge (this function sanitized, FilePlanStorage
    used the raw profile name verbatim), so e.g. "Jose Ines Castillo" resolved to
    two different directories depending on which code wrote/read it. Confirmed in
    production: season_phases.json (mesocycle/deload data) and the season plan's
    "existing plan" continuity check both went through FilePlanStorage and were
    silently reading/writing an empty sibling directory — the season plan looked
    like it "restarted from scratch" every regeneration, and the deload-aware
    weekly planning from Fase 3 never actually reached a real generation."""
    d = STORAGE_DIR / sanitize_storage_name(profile_name)
    d.mkdir(parents=True, exist_ok=True)
    return d


def _migrate_legacy_storage_dirs() -> None:
    """One-time cleanup for directories created before FilePlanStorage and
    _profile_storage_dir shared the same sanitization (see sanitize_storage_name's
    docstring). Only touches directories whose name differs from its sanitized
    form: empty ones are removed outright; non-empty ones have their files moved
    into the correctly-named directory (never overwriting an existing file),
    then removed if left empty. Safe to run on every startup — a no-op once
    migrated."""
    if not STORAGE_DIR.exists():
        return
    for entry in list(STORAGE_DIR.iterdir()):
        if not entry.is_dir():
            continue
        target_name = sanitize_storage_name(entry.name)
        if target_name == entry.name:
            continue

        target_dir = STORAGE_DIR / target_name
        target_dir.mkdir(parents=True, exist_ok=True)
        moved, skipped = 0, 0
        for item in entry.iterdir():
            dest = target_dir / item.name
            if dest.exists():
                skipped += 1
                continue
            item.rename(dest)
            moved += 1
        try:
            entry.rmdir()
        except OSError:
            logger.warning(
                "Legacy storage dir '%s' not empty after migration (%d moved, %d skipped) — left in place",
                entry, moved, skipped,
            )
        else:
            if moved or skipped:
                logger.info("Migrated legacy storage dir '%s' -> '%s' (%d moved, %d skipped)", entry.name, target_name, moved, skipped)


_migrate_legacy_storage_dirs()


# Canonical sport vocabulary used by the availability-constraint validator
# (services/ai/langgraph/validation/plan_validator.py) and the Garmin workout
# builder. Maps the Spanish labels shown in the Step 1 UI to canonical keys.
_GARMIN_DAY_TO_ES = {
    "MONDAY": "Lunes", "TUESDAY": "Martes", "WEDNESDAY": "Miércoles",
    "THURSDAY": "Jueves", "FRIDAY": "Viernes", "SATURDAY": "Sábado", "SUNDAY": "Domingo",
}

SPORT_LABEL_TO_KEY = {
    "Correr": "run",
    # v7. "Trail" no es un deporte distinto de correr: es TERRENO, y por eso se
    # declara por franja horaria y no en el perfil. Un corredor de montaña casi
    # nunca tiene monte todos los días -- tiene monte el domingo por la mañana,
    # y entre semana escaleras, una cuesta del barrio o la cinta. Declararlo
    # por bloque es lo que deja al planificador saber CUÁNDO puede mandarle al
    # sendero y cuándo tiene que simularlo.
    #
    # Se expande a ["run", "trail"] en _canonical_sports_for_day: marcar trail
    # nunca puede QUITAR la posibilidad de correr en esa franja, y si lo
    # hiciera un domingo de sendero se convertiría en un día sin correr.
    "Trail": "trail",
    "Bici": "bike",
    "Nadar": "swim",
    "Fuerza": "strength",
    "Cualquiera": None,  # unconstrained
}

# Pydantic Schemas
class CompetitionModel(BaseModel):
    name: str
    date: str | None = ""
    # Sin "Olympic" por defecto: ver race_distances.normalizar_competicion.
    race_type: str | None = None
    priority: str | None = "B"
    target_time: str | None = ""
    # Added in v5 Track 2. Without an explicit distance, comparing a target
    # time against Garmin's race predictions would mean sniffing the distance
    # out of the competition's free-text NAME -- the exact keyword-guessing
    # pattern v4 removed from the validator, and far more consequential here:
    # matching the wrong distance would frame the goal against the wrong
    # reference entirely.
    distance: str | None = ""  # "5K"|"10K"|"half_marathon"|"marathon"|"" (otra)

class InjuryModel(BaseModel):
    location: str = ""
    laterality: str | None = ""      # "izquierda"|"derecha"|"bilateral"|""
    onset: str | None = ""           # cuándo empezó
    pain_scale: int | None = None    # 0-10
    aggravating_factors: str | None = ""
    treatment: str | None = ""

class UserProfileModel(BaseModel):
    name: str
    garmin_email: str
    garmin_password: str
    sports_preference: str = "triathlon"  # triathlon, run, swim, bike
    target_distance: str | None = ""   # E.g. Olympic, Half-Marathon, Gran Fondo
    triathlon_distance: str | None = "Olympic"
    run_distance: str | None = "Half-Marathon"
    bike_distance: str | None = "Road Cycling"
    available_days: list[str] = []
    hours_per_day: dict | None = {}
    activities_per_day: dict | None = {}
    # Real per-day time blocks (start/end clock time + allowed sports), up to
    # 2 per day — the primary source of truth going forward. hours_per_day/
    # activities_per_day above are kept as derived aggregates (computed by
    # the frontend at save time) so every older consumer of those two fields
    # keeps working unchanged; see web_app/server.py's _day_availability.
    day_time_blocks: dict | None = {}
    # Se conserva para que un perfil guardado antes de la v1.2 siga cargando,
    # y NO se lee en ningun sitio: al atleta ya no se le pregunta cuantas horas
    # tiene, porque no es una pregunta que pueda contestar bien y su respuesta
    # llegaba al prompt como un techo. Ver `build_custom_planning_context`.
    available_hours: float | None = None
    limitations: str | None = ""
    # Kept on the model so profiles saved before v5 Track 12 still load,
    # but no longer read anywhere: the extraction windows are decided in
    # services/garmin/extraction_windows.py, not by the athlete.
    activities_days: int | None = None
    metrics_days: int | None = None
    # v7: la version unica de lanzamiento. Era "standard" ("Lite"), que es la
    # razon por la que las tres primeras corridas reales del producto fueron
    # Lite y el modo Pro no se ejecuto nunca -- el selector de la UI venia con
    # Lite marcado por defecto y nadie lo cambio.
    #
    # El campo NO se elimina: sigue siendo el interruptor de emergencia si
    # alguna vez hay que bajar el costo de golpe. Lo que se quito es la
    # eleccion del atleta, que no tiene forma de tomarla con criterio.
    ai_mode: str | None = None
    planning_context: str | None = ""
    analysis_context: str | None = ""
    competitions: list[CompetitionModel] | None = []
    prompts: dict | None = {}
    # Optional single threshold values used to derive 5 HR/pace/power zones
    # for Garmin workout targets (see services/garmin/workout_builder.py's
    # compute_zone_config). Left blank -> uploads degrade to no.target.
    lactate_threshold_hr: float | None = None
    lactate_threshold_pace_sec_per_km: float | None = None
    ftp_watts: float | None = None
    # Natación: el umbral por 100 m de un TEST del atleta (p. ej. un 400 m a
    # tope), no la media que calcula el reloj. Garmin cuenta los largos por
    # acelerómetro y produce parciales imposibles cuando falla un viraje, así
    # que derivar la referencia de ahí seria construir sobre ruido.
    swim_threshold_pace_sec_per_100m: float | None = None
    # El ritmo de SU rodaje suave, declarado (Database/022). Gana a la banda
    # "Fácil" derivada de las predicciones. Ver prescription.build_prescription.
    easy_run_pace_fast_sec_per_km: float | None = None
    easy_run_pace_slow_sec_per_km: float | None = None

    # --- C. Historial y Nivel de Entrenamiento ---
    training_age_level: str | None = ""   # "principiante"|"intermedio"|"avanzado"|"elite"
    years_training: float | None = None
    current_weekly_volume_hours: float | None = None
    longest_recent_session_hours: float | None = None
    recent_prs: dict | None = {}          # e.g. {"run_5k": "22:30"}

    # --- D. Fisiología (además de las zonas ya existentes arriba) ---
    weight_kg: float | None = None
    height_cm: float | None = None
    gender: str | None = ""
    birth_date: str | None = ""
    resting_heart_rate: int | None = None
    vo2max_running: float | None = None
    vo2max_cycling: float | None = None

    # --- E. Equipo y Entorno ---
    pool_access: str | None = ""    # "ninguno"|"limitado"|"completo"
    # ""|"si"|"no". Vacio = que decida el entrenador. Es una PREFERENCIA, no
    # una capacidad: hay quien los odia y quien los necesita, y encadenar dos
    # sesiones el mismo dia no es un brick.
    brick_preference: str | None = ""
    bike_setup: str | None = ""     # "outdoor"|"rodillo"|"ambos"|""
    treadmill_access: bool | None = False
    altitude_m: float | None = None
    climate: str | None = ""        # "templado"|"calido_humedo"|"frio"|"variable"
    terrain: str | None = ""        # "llano"|"montanoso"|"mixto"

    # --- F. Estilo de Vida y Recuperación ---
    occupation_type: str | None = ""  # "sedentario"|"activo_fisico"|"mixto"
    avg_sleep_hours: float | None = None
    sleep_source: str | None = ""     # "garmin"|"manual"
    stress_level_self_report: str | None = ""  # "bajo"|"medio"|"alto"
    travel_frequency: str | None = ""
    nutrition_notes: str | None = ""
    menstrual_tracking_opt_in: bool | None = False
    menstrual_cycle_notes: str | None = ""

    # --- G. Salud (Doctor Deportivo) — campos estructurados previos al chat ---
    current_injuries: list[InjuryModel] | None = []
    past_injuries_surgeries: str | None = ""
    chronic_conditions: str | None = ""
    medications: str | None = ""
    # LO QUE SE RETIRO EN LA v1.2:
    #
    #   par_q_flags, last_medical_evaluation, medical_snapshot_hash,
    #   doctor_performance_report, doctor_chat_history,
    #   health_disclaimer_accepted(_at)
    #
    # Los cuatro primeros eran cuatro de los SEIS canales por los que se le
    # preguntaba al atleta por lesiones, y ese exceso -- no los prompts -- era
    # la causa del sesgo que la beta reporto. El descargo lo sustituye la
    # puerta legal (users.terms_accepted_at, migracion 015), que lo hace
    # bloqueando y con constancia de version. Ver Database/017.

    # --- H. Preferencias de Coaching ---
    coaching_tone: str | None = ""    # "directo"|"empatico"|"tecnico"
    strength_training_preference: str | None = ""  # "si"|"no"|"indiferente"
    preferred_rest_day: str | None = ""
    # "Lunes".."Domingo": el dia en que empieza su semana. Ver _fin_de_bloque.
    week_start_day: str | None = ""
    cross_training_open: bool | None = True
    # Opt-OUT (not opt-in): the ACWR load guardrail (services/ai/langgraph/
    # validation/plan_validator.py) automatically trims week 1's volume when
    # the athlete's real, current Garmin acute:chronic workload ratio is in
    # the high-injury-risk zone (>1.5) and the plan doesn't already reflect
    # a pullback. Defaults to protecting the athlete; only an explicit,
    # informed choice should turn it off.
    ai_load_guardrail_enabled: bool | None = True

    # Qué campos vinieron del auto-import de Garmin (para mostrar el badge
    # "🔗 Garmin" en el frontend); se sobreescribe cada vez que se reimporta.
    garmin_autofilled_fields: list[str] | None = []
    # El test de umbral que el atleta hizo y usó, por deporte, con su fecha
    # (Database/023). Ver services/ai/test_de_umbral.py y prescription.py.
    threshold_tests: dict | None = {}
    # Cómo entrena la fuerza y su programa (Beta 2.1, Database/025). None = no
    # contestó y el plan sigue como hasta hoy. Se leen SIEMPRE con
    # services/ai/perfil_de_fuerza.py, que descarta lo que no reconoce.
    strength_profile: dict | None = None
    strength_routine: dict | None = None

class GarminImportModel(BaseModel):
    garmin_email: str
    garmin_password: str

class GarminUploadRequestModel(BaseModel):
    profile_name: str
    dates: list[str] | None = None  # None = upload the whole plan

# Helper to read/write profiles

# Guards every load-modify-save cycle on profiles.json. FastAPI runs sync
# `def` routes in a thread pool, so two requests (e.g. two browser tabs
# saving a profile at once) genuinely can interleave here -- without this,
# both could load the same pre-mutation snapshot and the second save would
# silently drop the first request's change. Combined with the atomic write
# in save_profiles, this also closes the failure mode seen in testing where
# a concurrent reader caught the file mid-write ("Expecting value: line 1
# column 1" from a torn write) -- that specific error is actually fixed by
# the atomic write alone (a reader now always sees a complete old-or-new
# file, never a partial one), but the lock is what prevents lost updates.
_profiles_lock = threading.Lock()


def load_profiles() -> dict:
    if PROFILES_FILE.exists():
        try:
            with open(PROFILES_FILE, encoding="utf-8") as f:
                return json.load(f)
        except Exception as e:
            logger.error(f"Error loading profiles: {e}")
            return {}
    return {}


def save_profiles(profiles: dict):
    """Atomic write: build the complete new content in a temp file in the
    same directory, then os.replace() it over profiles.json. os.replace is
    atomic on both POSIX and Windows for a same-volume rename -- any
    concurrent reader sees either the whole old file or the whole new one,
    never a half-written one."""
    try:
        tmp_path = PROFILES_FILE.with_name(PROFILES_FILE.name + ".tmp")
        with open(tmp_path, "w", encoding="utf-8") as f:
            json.dump(profiles, f, indent=2, ensure_ascii=False)
        os.replace(tmp_path, PROFILES_FILE)
    except Exception as e:
        logger.error(f"Error saving profiles: {e}")

# API Endpoints

class LoginModel(BaseModel):
    username: str = ""
    password: str = ""


class ChangePasswordModel(BaseModel):
    current_password: str = ""
    new_password: str = ""


def current_user(request: Request) -> dict:
    """El usuario de esta peticion. El middleware ya la dejo pasar."""
    user = getattr(request.state, "user", None)
    if user is None:
        raise HTTPException(status_code=401, detail="Inicia sesion.")
    return user


def require_admin(request: Request) -> dict:
    """Solo el operador. Para lo que no es de ningun atleta en particular.

    Cuando todavia no hay usuarios creados la app esta abierta (ver el
    middleware), y esto tambien: es la instalacion recien hecha, y el primer
    usuario se crea desde el servidor, no desde aqui.
    """
    user = getattr(request.state, "user", None)
    if user is None:
        return {}
    if not user.get("is_admin"):
        raise HTTPException(status_code=403, detail="Solo el administrador.")
    return user


def _allowed_profile(request: Request, profile: str) -> str:
    """El perfil que esta peticion PUEDE tocar, no el que pidio.

    Aqui esta el agujero que v7 cierra. Cada ruta recibia `?profile=` de la
    URL y lo usaba tal cual: con dos atletas en el mismo servidor, escribir
    otro nombre bastaba para leer, regenerar o borrar el plan ajeno. El
    parametro deja de ser una entrada de confianza -- la identidad sale de la
    cookie de sesion, que el cliente no puede falsificar.

    El administrador si puede pasar un perfil ajeno: es quien atiende la beta
    y tiene que poder mirar el caso que le acaban de reportar.
    """
    user = getattr(request.state, "user", None)
    if user is None:
        # Instalacion sin usuarios todavia (ver el middleware): comportarse
        # como antes, que es lo unico que permite crear el primero.
        return profile
    if user.get("is_admin"):
        return profile
    owned = user.get("profile")
    if not owned:
        raise HTTPException(
            status_code=403,
            detail="Tu usuario no tiene un perfil asignado todavia. Escribe a josecastillo.eas@gmail.com.",
        )
    if profile and profile != owned:
        logger.warning(
            "Usuario '%s' pidio el perfil '%s' y solo puede ver '%s'",
            user.get("username"), profile, owned,
        )
        raise HTTPException(status_code=403, detail="Ese plan no es tuyo.")
    return owned


@app.get("/login")
def login_page():
    return FileResponse(str(Path(__file__).parent / "static" / "login.html"))


@app.get("/api/auth/me")
def auth_me(request: Request):
    """Quien soy, o que no hay nadie. Lo primero que pregunta el frontend."""
    user = auth.session_user(request.cookies.get(auth.SESSION_COOKIE))
    if user is None:
        return {
            "authenticated": False,
            # Una instalacion recien hecha no tiene usuarios y deja pasar a
            # todo el mundo (si no, nadie podria crear el primero). Decirlo
            # en voz alta es mejor que dejarlo como sorpresa.
            "setup_required": not auth.any_users_exist(),
        }
    return {
        "authenticated": True,
        "username": user.get("username"),
        "profile": user.get("profile"),
        "is_admin": bool(user.get("is_admin")),
        "must_change_password": bool(user.get("must_change_password")),
    }


@app.post("/api/auth/login")
def auth_login(data: LoginModel, response: Response):
    user = auth.authenticate(data.username, data.password)
    if user is None:
        # Un solo mensaje para "no existe" y para "contrasena incorrecta": el
        # que distingue los dos regala una lista de usuarios validos.
        raise HTTPException(status_code=401, detail="Usuario o contraseña incorrectos.")
    token, expires = auth.start_session(user["username"])
    response.set_cookie(
        auth.SESSION_COOKIE,
        token,
        httponly=True,          # fuera del alcance de cualquier JS de la pagina
        samesite="lax",         # no viaja en peticiones de otros sitios
        secure=COOKIE_SECURE,   # solo por HTTPS en produccion
        max_age=int((expires - datetime.now(expires.tzinfo)).total_seconds()),
        path="/",
    )
    return {
        "status": "ok",
        "username": user["username"],
        "profile": user.get("profile"),
        "is_admin": bool(user.get("is_admin")),
        "must_change_password": bool(user.get("must_change_password")),
    }


@app.post("/api/auth/logout")
def auth_logout(request: Request, response: Response):
    auth.end_session(request.cookies.get(auth.SESSION_COOKIE))
    response.delete_cookie(auth.SESSION_COOKIE, path="/")
    return {"status": "ok"}


@app.post("/api/auth/change-password")
def auth_change_password(data: ChangePasswordModel, request: Request):
    """Cambiar la contrasena cierra las demas sesiones (ver core/auth.py)."""
    user = auth.session_user(request.cookies.get(auth.SESSION_COOKIE))
    if user is None:
        raise HTTPException(status_code=401, detail="Inicia sesion.")
    if not auth.verify_password(data.current_password or "", user.get("password")):
        raise HTTPException(status_code=403, detail="La contraseña actual no coincide.")
    if (data.new_password or "") == (data.current_password or ""):
        raise HTTPException(status_code=400, detail="La contraseña nueva tiene que ser distinta.")
    try:
        auth.set_password(user["username"], data.new_password or "")
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc
    # La sesion actual tambien murio: hay que volver a entrar, con la nueva.
    return {"status": "ok", "relogin_required": True}


@app.get("/api/profiles")
def get_profiles(request: Request):
    # Garmin passwords are encrypted at rest (see core/credential_crypto.py)
    # but decrypted here before returning to the frontend, which autofills
    # them into the profile form for a frictionless local experience -- same
    # tradeoff as before encryption was added (localhost-only app, the file
    # itself is gitignored), just no longer plaintext on disk.
    #
    # v7: ese "same tradeoff" dejo de ser el mismo en cuanto hay mas de una
    # persona. Esta ruta devolvia TODOS los perfiles, con la contrasena de
    # Garmin de cada uno EN CLARO, a cualquiera que la pidiera -- la peor
    # fuga de las 33 rutas, porque son credenciales de un tercero, no datos
    # de entrenamiento. Cada atleta ve el suyo; el operador los ve todos
    # porque es quien atiende la beta.
    profiles = load_profiles()
    user = getattr(request.state, "user", None)
    if user is not None and not user.get("is_admin"):
        propio = user.get("profile")
        profiles = {k: v for k, v in profiles.items() if k == propio}

    decrypted = {}
    for name, profile_dict in profiles.items():
        profile_dict = dict(profile_dict)
        profile_dict["garmin_password"] = decrypt_secret(profile_dict.get("garmin_password"))
        # Backward compatibility for profiles saved before this field existed
        # (Fase 5): the frontend renders an ABSENT key as unchecked, which
        # would silently show -- and, if the athlete then saves without
        # noticing, silently persist -- the ACWR guardrail as OFF for every
        # pre-existing profile. That inverts the "opt-out, not opt-in" intent
        # (protect by default). Found during Fase D's end-to-end review
        # against this app's own real profile. Same lazy-migration pattern as
        # credential encryption: default applied on read, no migration script.
        profile_dict.setdefault("ai_load_guardrail_enabled", True)
        decrypted[name] = profile_dict
    return decrypted

@app.post("/api/profiles")
def create_profile(profile: UserProfileModel, request: Request):
    # El perfil viene en el CUERPO, asi que el middleware no lo vio (leerlo alli
    # lo consumiria para este endpoint). Ver _allowed_profile.
    _allowed_profile(request, profile.name)
    profile_dict = profile.model_dump()
    profile_dict["garmin_password"] = encrypt_secret(profile_dict.get("garmin_password"))
    with _profiles_lock:
        profiles = load_profiles()
        # `ai_mode` ya no lo manda la UI (v7 retiro el selector Lite/Pro). El
        # campo sobrevive como interruptor de emergencia de costo, editable a
        # mano en profiles.json -- y si la UI lo pisara en cada guardado, no
        # seria un interruptor, seria un boton que vuelve solo. Asi que un
        # payload sin ai_mode conserva el que ya estaba, y solo un perfil
        # nuevo estrena el modo de lanzamiento.
        if not profile_dict.get("ai_mode"):
            stored = (profiles.get(profile.name) or {}).get("ai_mode")
            profile_dict["ai_mode"] = stored or DEFAULT_AI_MODE
        profiles[profile.name] = profile_dict
        save_profiles(profiles)
    return {"status": "success", "message": f"Profile '{profile.name}' saved."}

@app.delete("/api/profiles/{name}")
def delete_profile(name: str, request: Request):
    _allowed_profile(request, name)
    with _profiles_lock:
        profiles = load_profiles()
        found = name in profiles
        if found:
            del profiles[name]
            save_profiles(profiles)
    if found:
        # El perfil se va; su gasto se queda. Lo que este atleta costo en
        # Anthropic, OpenAI y Google ya se pago, y una contabilidad que se
        # borra cuando el cliente borra sus datos no es una contabilidad.
        # Queda un evento en el libro para que el informe pueda decir
        # "(perfil eliminado)" en vez de perder el historial entero.
        usage_ledger.record_profile_deleted(name)
        return {"status": "success", "message": f"Profile '{name}' deleted."}
    raise HTTPException(status_code=404, detail="Profile not found")

@app.post("/api/profile/garmin-import")
def garmin_import(request: GarminImportModel):
    """Lightweight Garmin connect: only get_user_profile() + a 7-day
    physiological-markers pull (resting HR), NOT the full multi-day
    activity extraction used by /api/generate. Returns a flat dict of
    fields the frontend can use to prefill the profile form, each one
    still editable — the athlete has the final say over the reading."""
    try:
        extractor = TriathlonCoachDataExtractor(request.garmin_email, request.garmin_password)
        profile = extractor.get_user_profile()
        end = date.today()
        markers = extractor.get_physiological_markers(end - timedelta(days=7), end)
        # El volumen semanal REAL, de la lista de actividades. Una sola llamada
        # y ninguna IA: es aritmetica sobre lo que el reloj ya midio. Antes se
        # le PREGUNTABA al atleta, y el propio prompt del planificador tenia que
        # decirle al modelo que era "a rough athlete estimate, not a
        # measurement". Si falla, no se cae el alta entera: el resto de campos
        # sigue siendo util.
        try:
            volumen = extractor.get_weekly_training_volume(weeks=12)
        except Exception as e:  # noqa: BLE001
            logger.warning(f"No se pudo calcular el volumen semanal: {e}")
            volumen = None

    except Exception as e:
        logger.error(f"Garmin import failed: {e}")
        from services.garmin.client import explicar_error_de_garmin
        raise HTTPException(
            status_code=400,
            detail=explicar_error_de_garmin(e) or f"No se pudo conectar a Garmin Connect: {e}",
        ) from e

    imported: dict = {}

    def _set(key, value):
        if value not in (None, "", []):
            imported[key] = value

    _set("gender", (profile.gender or "").lower() or None)
    _set("weight_kg", profile.weight)
    _set("height_cm", profile.height)
    _set("birth_date", profile.birth_date)
    _set("vo2max_running", profile.vo2max_running)
    _set("vo2max_cycling", profile.vo2max_cycling)
    _set("lactate_threshold_hr", profile.lactate_threshold_heart_rate)
    if profile.lactate_threshold_speed:
        _set("lactate_threshold_pace_sec_per_km", round(1000.0 / profile.lactate_threshold_speed, 1))
    if markers.resting_heart_rate:
        _set("resting_heart_rate", markers.resting_heart_rate)

    # El volumen semanal deja de ser una estimacion del atleta y pasa a ser una
    # medida. Se rellena como cualquier otro campo importado -- editable, con su
    # insignia de GARMIN -- porque sigue siendo el atleta quien tiene la ultima
    # palabra: puede saber que esas doce semanas no le representan.
    if volumen and volumen.get("hours_per_week"):
        _set("current_weekly_volume_hours", volumen["hours_per_week"])

    # sleep_time/wake_time are seconds-since-midnight; derive avg_sleep_hours
    # handling the overnight wrap (e.g. sleep 22:00 -> wake 05:00 = 7h).
    if isinstance(profile.sleep_time, (int, float)) and isinstance(profile.wake_time, (int, float)):
        sleep_secs = (profile.wake_time - profile.sleep_time) % 86400
        if 0 < sleep_secs <= 16 * 3600:
            imported["avg_sleep_hours"] = round(sleep_secs / 3600.0, 1)
            imported["sleep_source"] = "garmin"

    # Day suggestions are NOT auto-applied to available_days/day_time_blocks —
    # unlike the scalar fields above, silently toggling which days the
    # athlete trains on would override a real schedule decision. The
    # frontend shows these as a one-click "apply?" banner instead.
    def _garmin_days_to_es(day_names) -> list[str]:
        return [d for name in (day_names or []) if (d := _GARMIN_DAY_TO_ES.get(str(name).upper()))]

    suggested_long_days = _garmin_days_to_es(profile.preferred_long_training_days)
    suggested_available_days = _garmin_days_to_es(profile.available_training_days)

    return {
        "status": "success",
        "imported": imported,
        "fields": list(imported.keys()),
        "suggested_long_days": suggested_long_days,
        "suggested_available_days": suggested_available_days,
        # El detalle del volumen, aparte de `imported`: no es un campo del
        # perfil, es el contexto que hace que el numero se pueda leer. Una media
        # de 4 h/semana no dice lo mismo si entreno las doce semanas que si
        # entreno seis, y `active_weeks` es lo que distingue los dos casos.
        "volumen_semanal": volumen,
        # Quién es esta cuenta para Garmin (su profileId, que no cambia aunque cambie el correo).
        # PHP lo usa para las semanas gratis (Database/035: una cuenta Garmin regala una sola
        # vez) y no se lo manda a la pantalla.
        "garmin_cuenta": _cuenta_garmin(extractor),
    }


def _cuenta_garmin(extractor) -> str | None:
    """El `profileId` de la cuenta Garmin (o su `displayName`), o None si no se sabe."""
    try:
        perfil = extractor.garmin.client.garth.profile or {}
        cuenta = perfil.get("profileId") or perfil.get("displayName")
        return str(cuenta) if cuenta else None
    except Exception:  # noqa: BLE001 - sin esto no se regala nada, pero conectar sigue
        logger.warning("No se pudo leer el profileId de Garmin", exc_info=True)
        return None


# Cuánto antes de la fecha programada se acepta haber hecho el test (el atleta
# lo adelanta un día o dos) y hasta cuánto después se busca.
_DIAS_ANTES_DEL_TEST = 3
_MAX_ACTIVIDADES_POR_LLAMADA = 200


def _el_test_mas_reciente(registro: list[dict], deporte: str) -> dict | None:
    tests = [e for e in registro if upload_ledger.es_test(e) and e.get("sport") == deporte]
    return max(tests, key=lambda e: (str(e.get("date") or ""), str(e.get("uploaded_at") or ""))) if tests else None


def subir_test_de_umbral(garmin_email: str, garmin_password: str, storage_dir: Path, deporte: str,
                         fecha: str) -> dict:
    """Programa el test de umbral en el reloj del atleta y lo anota en el registro de subidas.

    Un test nuevo del mismo deporte REEMPLAZA al que estuviera pendiente: el
    test se repite cada pocos meses y el que manda es el último. El entreno
    viejo se retira del reloj (sólo el anotado, nunca uno del atleta); las
    actividades ya hechas no se tocan. Ver services/ai/test_de_umbral.py.
    """
    import asyncio

    from services.ai.test_de_umbral import FRANJA_DEL_TEST, NOMBRE_DEL_ENTRENO, sesion_del_test
    from services.garmin.client import explicar_error_de_garmin

    if deporte not in ("run", "bike"):
        raise HTTPException(status_code=400, detail="El test al reloj es de carrera o de bici.")
    try:
        dia = date.fromisoformat(str(fecha)[:10])
    except ValueError as e:
        raise HTTPException(status_code=400, detail="Elige el día en que vas a hacer el test.") from e
    if dia < date.today():
        raise HTTPException(status_code=400, detail="El test no se puede programar en un día que ya pasó.")

    registro = upload_ledger.cargar(storage_dir)
    previo = _el_test_mas_reciente(registro, deporte)
    try:
        client = GarminConnectClient()
        client.connect(garmin_email, garmin_password)
        servicio = GarminUploadService(client)
        resultado = asyncio.run(servicio.upload_day(
            sesion_del_test(deporte, dia.isoformat()), compute_zone_config(),
            replaces=int(previo["workout_id"]) if previo else None,
        ))
    except Exception as e:
        logger.error(f"Threshold test upload failed: {e}")
        raise HTTPException(status_code=400,
                            detail=explicar_error_de_garmin(e) or f"No se pudo subir el test a Garmin: {e}") from e
    if not resultado.success or not resultado.workout_id:
        raise HTTPException(status_code=502, detail=resultado.error or "Garmin no aceptó el entreno del test.")

    if previo and resultado.replaced_workout_id:
        registro = upload_ledger.olvidar(registro, [previo["workout_id"]])
    registro = upload_ledger.anotar(
        registro, fecha=dia.isoformat(), franja=f"{FRANJA_DEL_TEST}-{deporte}", workout_id=resultado.workout_id,
        nombre=f"{NOMBRE_DEL_ENTRENO} - {dia.isoformat()}", deporte=deporte, tipo=upload_ledger.TIPO_TEST,
    )
    upload_ledger.guardar(storage_dir, registro)
    return {"deporte": deporte, "programado": dia.isoformat(), "workout_id": resultado.workout_id,
            "nombre": f"{NOMBRE_DEL_ENTRENO} - {dia.isoformat()}", "avisos": resultado.warnings or []}


def ultima_sincronizacion_del_reloj(garmin_email: str, garmin_password: str) -> str | None:
    """Cuándo subió algo su reloj por última vez (ISO, UTC), para avisarle en el check-in antes de
    generar (José, 2026-10-19). Una llamada; la sesión de Garmin se reanuda con sus tokens."""
    extractor = TriathlonCoachDataExtractor(garmin_email, garmin_password)
    return extractor.get_ultima_sincronizacion()


def estado_de_las_sesiones_del_reloj(garmin_email: str, garmin_password: str, storage_dir: Path,
                                     weekly_plan: dict, hoy: date, umbral_ritmo_s_km: float | None = None) -> dict:
    """✓/✗ de cada sesión de las semanas recientes, leído de Garmin y sin IA (el responsable, 2026-10-21).

    Las mismas lecturas que la adherencia de la generación: las actividades (con su `workoutId`), las zonas
    de SU reloj por deporte, la última sincronización y el registro de subidas. Ver
    `services/ai/estado_de_las_sesiones.py`.
    """
    from services.ai.estado_de_las_sesiones import estados

    desde = hoy - timedelta(days=13)
    extractor = TriathlonCoachDataExtractor(garmin_email, garmin_password)
    actividades = extractor.get_recent_activities(desde - timedelta(days=1), hoy)
    zonas = extractor.get_garmin_hr_zones()
    ultima = extractor.get_ultima_sincronizacion()
    return {
        "sesiones": estados(weekly_plan, actividades, hoy, desde=desde, zonas=zonas,
                            umbral_ritmo_s_km=umbral_ritmo_s_km, subidas=upload_ledger.cargar(storage_dir),
                            ultima_sincronizacion=ultima),
        "ultima_sincronizacion": ultima,
    }


def analizar_test_de_umbral(garmin_email: str, garmin_password: str, storage_dir: Path, deporte: str) -> dict:
    """Lee la actividad que el atleta hizo con SU test, por el `workoutId` del entreno. Ninguna otra.

    La primera versión buscaba entre las actividades recientes la que pareciera
    un test, y en la cuenta real del atleta 2 aceptó sus series y dos rodajes.
    Garmin guarda en cada actividad el `workoutId` del entreno con el que se
    hizo (verificado el 2026-09-14: su "Ritmo 5K" del 09-09 lleva el del entreno
    subido), así que no hay nada que adivinar. Si repitió el test, manda la
    actividad más reciente.
    """
    from services.ai.test_de_umbral import analizar_test
    from services.garmin.client import explicar_error_de_garmin

    if deporte not in ("run", "bike"):
        raise HTTPException(status_code=400, detail="El test por vueltas es de carrera o de bici.")
    test = _el_test_mas_reciente(upload_ledger.cargar(storage_dir), deporte)
    if not test:
        raise HTTPException(status_code=400,
                            detail="Primero envía el test a tu reloj; la app lee sólo la actividad hecha con él.")
    programado = date.fromisoformat(str(test["date"])[:10])
    hoy = date.today()
    base = {"deporte": deporte, "programado": programado.isoformat(), "nombre_del_entreno": test.get("name")}
    desde = programado - timedelta(days=_DIAS_ANTES_DEL_TEST)
    if hoy < desde:
        return {**base, "encontrada": False,
                "motivo": f"Tu test está programado para el {programado.isoformat()}: todavía no toca."}
    try:
        extractor = TriathlonCoachDataExtractor(garmin_email, garmin_password)
        crudas = extractor.garmin.client.get_activities_by_date(desde.isoformat(), hoy.isoformat()) or []
    except Exception as e:
        logger.error(f"Threshold test analysis failed: {e}")
        raise HTTPException(status_code=400,
                            detail=explicar_error_de_garmin(e) or f"No se pudo conectar a Garmin Connect: {e}") from e

    hechas = [a for a in (crudas if isinstance(crudas, list) else [])[:_MAX_ACTIVIDADES_POR_LLAMADA]
              if isinstance(a, dict) and str(a.get("workoutId") or "") == str(test["workout_id"])]
    if not hechas:
        return {**base, "encontrada": False, "motivo": (
            f"Todavía no hay ninguna actividad hecha con el entreno «{test.get('name')}». Hazlo desde tu reloj "
            "(en tus entrenamientos o en el calendario de ese día) y vuelve cuando se haya sincronizado.")}
    hecha = max(hechas, key=lambda a: str(a.get("startTimeLocal") or ""))

    lthr_de_garmin = None
    if deporte == "run":
        try:
            lthr_de_garmin = extractor.get_user_profile().lactate_threshold_heart_rate
        except Exception as e:  # noqa: BLE001
            logger.warning(f"No se pudo leer la FC umbral de Garmin para el test: {e}")
    tipo = str((hecha.get("activityType") or {}).get("typeKey") or "")
    return {
        **base, "encontrada": True,
        "activity_id": hecha["activityId"],
        "fecha": str(hecha.get("startTimeLocal") or "")[:10],
        "nombre": hecha.get("activityName") or "",
        "minutos": round(float(hecha.get("duration") or 0) / 60),
        **analizar_test(extractor.get_activity_laps(hecha["activityId"]), deporte,
                        en_cinta="treadmill" in tipo, lthr_de_garmin=lthr_de_garmin),
    }


@app.get("/api/logs")
def get_logs(request: Request):
    """Solo el operador: el log del proceso es UNO para todo el servidor, con
    nombres, correos y trazas de todos los atletas dentro. Antes de v7 era una
    instalacion de una persona y no habia nada ajeno que filtrar."""
    require_admin(request)
    return {"logs": LOG_LIST}

@app.delete("/api/logs")
def clear_logs(request: Request):
    require_admin(request)
    LOG_LIST.clear()
    return {"status": "success"}

# Global execution state to prevent parallel runs and track generation status
execution_status = {
    "running": False,
    "progress": 0,
    "status": "idle",
    "result": None,
    "error": None
}


def _make_generation_progress_callback(weekly_chunk_count: int):
    """Builds an on_progress callback for run_analysis_and_season_planning /
    run_week_generation that updates execution_status with what's ACTUALLY happening (real node
    completions + weekly_planner's own "semana N de 4" sub-progress, both
    coming from the LangGraph astream in workflow_cost_tracker.py) instead of
    the old fixed 10/40/90/100 waypoints, which left the UI showing "40% -
    Generando plan con IA" for the whole ~2.5min generation regardless of
    what was actually happening. expected_events is a rough estimate (13
    labeled graph nodes + one weekly-plan chunk per week + one "validating"
    step) -- it doesn't need to be exact, just proportional, since the bar
    only needs to visibly move in step with real progress, not hit 100%
    precisely (the caller clamps final progress to 100 separately)."""
    expected_events = len(NODE_STATUS_LABELS_ES) + weekly_chunk_count + 1
    state = {"seen": 0}

    def _on_progress(label: str) -> None:
        state["seen"] += 1
        execution_status["status"] = label
        fraction = min(state["seen"] / expected_events, 1.0)
        execution_status["progress"] = min(89, 40 + round(fraction * 49))

    return _on_progress

# Mapping English day names to Spanish for consistent use in the planner
_DAY_ES = {
    "Monday": "Lunes", "Tuesday": "Martes", "Wednesday": "Miércoles",
    "Thursday": "Jueves", "Friday": "Viernes", "Saturday": "Sábado", "Sunday": "Domingo"
}

def build_custom_planning_context(profile: dict, afinamiento: dict | None = None,
                                  abiertos: dict[str, list[str]] | None = None,
                                  referencia: date | None = None,
                                  carreras: dict[str, str] | None = None) -> str:
    """El contexto del atleta para los planificadores.

    `afinamiento` es la carrera cuyo afinamiento cae en la semana que se genera
    (`weekly_planner_node.carrera_en_afinamiento`), o None. Con ella las reglas de
    los días FIJO se ESCRIBEN ya como valen esa semana. Antes se escribían
    siempre como inviolables y el nodo semanal las reescribía con una expresión
    regular sobre esta prosa (`contexto_de_la_semana`): un parche que dependía de
    que nadie tocara dos frases de este fichero.

    `abiertos` son las fechas de ESA semana que su tabla no le da y que abrió en
    el check-in (`checkin.dias_abiertos`, Database/024). Por lo mismo: las reglas
    marcadas NEVER VIOLATE ganan a cualquier bloque que venga detrás, así que
    tienen que decir ya que esos días se pueden usar. Sin días abiertos el texto
    no cambia ni un carácter.

    `carreras` son los días de ESA semana en los que compite, con lo que se lee de
    cada uno (`describir_carreras_de_la_semana`): están disponibles para su carrera
    aunque su tabla los tenga de descanso (Hugo, 2026-10-09; ver
    `plan_validator.dias_de_carrera`). Sin carreras, el texto tampoco cambia.
    """
    sports_pref = profile.get("sports_preference", "triathlon")
    target_distance = profile.get("target_distance", "")
    # `available_days` NO se lee aqui: la disponibilidad entra por
    # render_availability_table(), mas abajo, que ademas trae los bloques
    # horarios. Esta linea era un resto de antes de esa tabla.
    #
    # `available_hours` TAMPOCO, y ya no se le pregunta al atleta. Era un techo
    # semanal que el atleta escribia a mano, y es una pregunta que no puede
    # contestar bien: no sabe cuantas horas necesita su plan, solo cuantas
    # cree que le sobran. El numero que de verdad gobierna el volumen sale de
    # su carga cronica medida en Garmin, de la fase del macrociclo y del margen
    # de progresion (`compute_weekly_volume_target`), y ese camino no pasaba
    # por aqui.
    #
    # Ademas tenia un valor por defecto de 8.0 que llegaba al prompt como
    # "Total Available Weekly Hours: 8 hours net" para cualquiera que no lo
    # tocara -- un numero inventado con pinta de dato, que es la forma exacta
    # de fallo que este repo persigue. La rejilla dice QUE quiere entrenar y
    # que dias; cuanto, lo decidimos nosotros.
    limitations = profile.get("limitations", "")
    # `or ""`: el perfil trae la clave con None cuando el atleta no escribió
    # nada, y el prompt de la semana imprimía la palabra "None" como contexto.
    base_context = profile.get("planning_context") or ""
    competitions = [normalizar_competicion(c) if isinstance(c, dict) else c
                    for c in (profile.get("competitions") or [])]

    # A single-sport preference is the athlete's GOAL DISCIPLINE, not a ban on
    # supporting work. This used to read "The weekly plan MUST contain RUNNING
    # workouts ONLY. Do NOT schedule cycling, swimming, or weight training." --
    # which directly contradicted two other things in this very same prompt:
    # the athlete's own availability table (which lists Nadar/Fuerza/Bici as
    # allowed on specific blocks) and the Season Plan (which for this athlete
    # explicitly prescribed cycling as the primary volume driver to protect an
    # irritated knee/ankle). Verified against two real consecutive generations:
    # the planner resolved the contradiction differently each week -- week 1
    # emitted three REST days on blocks whose only allowed sports were
    # swim/strength ("Sin entrenamiento ... según las preferencias de carrera
    # exclusiva"), week 2 scheduled strength anyway. Neither is coaching; both
    # are a model flip-flopping on an instruction that contradicts itself.
    _GOAL_SPORT_LABELS = {"run": "Running", "swim": "Swimming", "bike": "Cycling"}
    sport_instruction = ""
    distance_instruction = ""
    if sports_pref in _GOAL_SPORT_LABELS:
        goal_label = _GOAL_SPORT_LABELS[sports_pref]
        sport_instruction = (
            f"GOAL DISCIPLINE: {goal_label}. Every quality/specific session — the sessions this athlete's "
            f"progression is actually measured by — must be {goal_label.lower()}. Supporting work is ALLOWED "
            "and often desirable (strength/prehab; low-impact cross-training such as cycling or swimming to "
            "add aerobic volume without impact), but ONLY under both of these conditions: (a) the athlete's "
            "own availability table below lists that sport for that day, and (b) the Season Plan "
            "or the experts' analysis actually calls for it (e.g. protecting a joint, managing load). "
            "Never schedule a sport the table does not list for that day. And never turn an available day "
            f"into REST just because {goal_label.lower()} is not one of its sports — use what that day does "
            "allow, or leave it rest only when recovery genuinely warrants it."
        )
        if target_distance:
            # La clave del catálogo tal cual ("ow_10000") no le dice nada a un
            # modelo; el triatlón ya pasaba por `describe` y esto no.
            from services.ai.race_distances import describe as _describe_distancia
            legible = _describe_distancia(target_distance) or target_distance
            distance_instruction = f"Target race distance: {legible}. Structure training volumes, long sessions, and intensity to peak for this distance."
    elif sports_pref == "triathlon":
        # `.get(clave, defecto)` NO cubre una clave presente con valor None, que
        # es como llega un triatleta que nunca declaro su distancia: el perfil
        # trae `triathlon_distance: None` y el prompt acababa diciendo, en una
        # linea marcada CRITICAL, "training for a None Triathlon". Verificado
        # contra la cuenta real el 2026-09-10 (atleta 2, sports_preference
        # triathlon, triathlon_distance NULL en MySQL).
        tri_dist = (profile.get("triathlon_distance") or "").strip()
        # Si es una clave del catalogo, el prompt dice los TRAMOS y no la
        # clave: "tri_olympic" no le dice nada a un modelo, "Olimpico (1.5 km
        # de natacion + 40 km de ciclismo + 10 km de carrera)" si, y es lo que
        # decide las proporciones. `describe()` devuelve "" cuando no conoce la
        # clave, asi que una distancia escrita a mano se respeta tal cual.
        from services.ai.race_distances import describe as _describe_carrera
        tri_dist = _describe_carrera(tri_dist) or tri_dist
        # "and optional strength workouts" enumerated ONE supporting discipline
        # out of six the schema accepts. An enumeration inside a line marked
        # CRITICAL is read as the closed list, so plyometrics, prehab, rehab and
        # mobility were effectively invisible to the planner even though
        # `Discipline` has carried them since v7.
        soporte = ("Supporting work is yours to choose from the full `discipline` vocabulary — strength, "
                   "plyometrics, prehab, rehab, mobility, technique — and is not limited to generic strength.")
        if tri_dist:
            sport_instruction = (f"CRITICAL: The athlete is training for a {tri_dist} Triathlon. Schedule running, "
                                 f"cycling and swimming in appropriate proportions for this distance. {soporte}")
            distance_instruction = (f"Target triathlon distance: {tri_dist}. Ensure swim/bike/run volumes match "
                                    "the demands of this race format.")
        else:
            # Abstenerse antes que acertar por suerte: rellenar "Olympic" en
            # silencio le da al planificador una certeza que nadie le dio, y de
            # ahi salen proporciones swim/bike/run para una carrera que el
            # atleta no va a correr. Se le dice que NO esta declarada y de donde
            # deducirla -- el calendario de competiciones, que si es suyo.
            sport_instruction = (f"CRITICAL: The athlete trains triathlon. Schedule running, cycling and swimming. "
                                 f"{soporte}")
            # "...y que necesita confirmarse" se quedaba escrito aunque el
            # atleta ya lo hubiera contestado: el 2026-09-11 respondio
            # "olimpico" a la pregunta del coach, la respuesta quedo en su
            # cuaderno, y la semana siguiente le volvia a pedir que confirmara.
            # Y sin triatlón en el calendario NO se supone uno. "Otherwise infer it from the
            # competition calendar" empujó al macrociclo del atleta 2 (un 10K A y una MTB B,
            # ningún triatlón) a "supongo provisionalmente un triatlón sprint", y el chat le
            # dijo después que, si no buscaba un triatlón, redujera la natación que él mismo
            # había pedido (auditoría del 2026-09-24).
            distance_instruction = ("Target triathlon distance: not set in the profile. If the athlete's notebook "
                                    "or their answers to your questions name it, that is the answer; if a "
                                    "triathlon is on the competition calendar, its format is. If neither is, do "
                                    "NOT assume a triathlon format: train the three sports for the races that ARE "
                                    "on the calendar and for what the athlete asks, and do not present a triathlon "
                                    "as a goal or an assumption.")

    # Build explicit day-by-day availability table. Days with 2 real time
    # blocks (see web_app/static/app.js's initDaysGrid) get one line per
    # block with clock times, so the planner can reason about heat/fatigue/
    # pool-hours and correctly tag session_slot am/pm for two-a-days; days
    # with only the legacy single-hours entry get the old one-line format.
    day_table = render_availability_table(profile)
    two_block_note = _multi_block_note(profile, afinamiento, abiertos, carreras)
    reglas_del_dia, regla_de_dobles = _reglas_criticas_de_disponibilidad(profile, afinamiento, abiertos, carreras)

    # Build athlete profile context (history/level, equipment/environment,
    # lifestyle) — feeds the planner concrete constraints a real coach would
    # only get from knowing the athlete in person.
    profile_lines = ["\n[ATHLETE PROFILE — HISTORY, ENVIRONMENT & LIFESTYLE]"]
    training_age_level = profile.get("training_age_level")
    if training_age_level:
        # This is a self-reported label, not ground truth — it must NEVER
        # override what the Activity/Metrics experts' analysis of real
        # recent Garmin data shows the athlete is actually already doing.
        # An athlete who labels themselves "principiante" but whose real
        # activity history shows intervals/tempo/threshold work should keep
        # training at that real level, not get reset to a generic beginner
        # progression — see WEEKLY_PLANNER_SYSTEM_PROMPT's "Evidence over
        # labels" principle.
        profile_lines.append(f"- Self-reported training level: {training_age_level} (context only — defer to the Activity/Metrics experts' analysis of actual recent training below if it shows a different reality).")
    years_training = profile.get("years_training")
    if years_training:
        profile_lines.append(f"- Years training: {years_training}.")
    current_weekly_volume_hours = profile.get("current_weekly_volume_hours")
    if current_weekly_volume_hours:
        # Desde la v1.1 este numero puede ser una MEDIDA y no una estimacion: el
        # alta lo calcula sumando la duracion de las actividades de las ultimas
        # 12 semanas (data_extractor.get_weekly_training_volume). Decirle al
        # modelo que es "a rough athlete estimate" cuando salio del reloj le
        # hace descartar el dato mas fiable que tiene sobre el volumen.
        #
        # La procedencia viaja en `garmin_autofilled_fields`, que es el mismo
        # canal que ya usa el frontend para pintar la insignia GARMIN.
        de_garmin = "current_weekly_volume_hours" in (profile.get("garmin_autofilled_fields") or [])
        if de_garmin:
            profile_lines.append(
                f"- Current weekly volume: {current_weekly_volume_hours}h — MEASURED from the athlete's "
                f"own Garmin activity over the last 12 weeks (total duration / 12), not self-reported. "
                f"Weeks with no training count as zero, so a long break pulls this down: read it together "
                f"with the Activity expert's view of how consistent that period actually was."
            )
        else:
            profile_lines.append(f"- Self-reported current weekly volume: {current_weekly_volume_hours}h — a rough athlete estimate, not a measurement. If the Activity/Metrics experts' analysis of real recent Garmin activity shows a materially different actual volume, trust the real data over this self-report for ramp-up decisions.")
    longest_recent_session_hours = profile.get("longest_recent_session_hours")
    if longest_recent_session_hours:
        profile_lines.append(f"- Longest recent single session (self-reported): {longest_recent_session_hours}h.")
    # Las marcas del atleta. Desde la v1.1 son una LISTA abierta -- puede
    # declarar un olimpico, una 5K de aguas abiertas o un kilometro vertical, no
    # solo las cuatro distancias de asfalto que cabian en los campos fijos.
    #
    # Se leen las DOS formas porque los perfiles de la beta tienen la vieja y
    # nadie los ha migrado: la nueva trae `marks`, la vieja es un diccionario
    # plano. Convertir la base de datos habria sido una migracion con riesgo
    # para ganar exactamente nada.
    recent_prs = profile.get("recent_prs") or {}
    _PR_VIEJAS = {
        "run_5k": "5K", "run_10k": "10K", "run_half_marathon": "Half Marathon",
        "run_marathon": "Marathon", "ftp_test_20min": "FTP (20min test)", "swim_1500m": "1500m swim",
    }
    pr_parts: list[str] = []
    marcas = recent_prs.get("marks")
    if isinstance(marcas, list):
        for m in marcas:
            if not isinstance(m, dict):
                continue
            tiempo = str(m.get("time") or "").strip()
            nombre = str(m.get("label") or m.get("key") or "").strip()
            if not tiempo or not nombre:
                continue
            cuando = str(m.get("date") or "").strip()
            pr_parts.append(f"{nombre}: {tiempo}" + (f" ({cuando})" if cuando else ""))
    else:
        pr_parts = [f"{label}: {recent_prs[key]}"
                    for key, label in _PR_VIEJAS.items() if recent_prs.get(key)]
    if recent_prs.get("notes"):
        pr_parts.append(str(recent_prs["notes"]).strip())
    if pr_parts:
        profile_lines.append(
            f"- Recent personal records: {', '.join(pr_parts)}. Use these as a sanity check against the "
            f"calculated training zones — if a session's target pace/power/HR implied by a PR looks "
            f"inconsistent with the zones table, prefer the PR (more specific, more recent evidence)."
        )

    pool_access = profile.get("pool_access")
    if pool_access == "ninguno":
        profile_lines.append("- CRITICAL: NO pool access. Never schedule swimming sessions, regardless of sport preference.")
    elif pool_access == "limitado":
        profile_lines.append("- Limited pool access: schedule swimming sparingly and only on days it's explicitly available.")

    # El LARGO de la piscina, que hasta la v1.1 no se sabia. `8x100m` son cuatro
    # largos en una de 25 m y dos en una de 50, y el atleta los contaba de
    # cabeza dentro del agua. Las series se siguen escribiendo en metros -- es
    # como se prescribe natacion y como lo espera Garmin -- pero ahora la
    # DESCRIPCION puede decir los largos, que es lo que el nadador cuenta.
    pool_length = profile.get("pool_length_m")
    try:
        pool_length = int(pool_length) if pool_length else None
    except (TypeError, ValueError):
        pool_length = None
    if pool_length and pool_access != "ninguno":
        profile_lines.append(
            f"- Pool length: {pool_length} m. Keep prescribing swim sets in METRES, but add the number of "
            f"lengths to the step description when it divides cleanly (\"8x100m (4 largos)\" in a {pool_length} m "
            f"pool). A set that does not divide into whole lengths is a set the athlete cannot swim: "
            f"prefer distances that are multiples of {pool_length}."
        )
    bike_setup = profile.get("bike_setup")
    if bike_setup == "rodillo":
        profile_lines.append("- Bike training is indoor-trainer only — write cycling workouts as trainer sessions (structured intervals work well here), not outdoor rides.")
    elif bike_setup == "ninguno" or bike_setup == "":
        pass
    # La cinta. El atleta la declara en su ficha, el dato viaja hasta aquí y
    # HASTA AHORA se leía y se tiraba: esta línea existía sin un `if` debajo,
    # así que el planificador nunca supo si podía contar con ella. Lo encontró
    # el linter nuevo (F841, variable asignada y no usada) — es exactamente la
    # clase de fallo que no da error y que sólo se ve mirando.
    #
    # Importa porque cambia lo que se puede prescribir: con cinta, una serie
    # de ritmo controlado se salva de la lluvia, del calor y de la noche; sin
    # ella, esa misma sesión hay que escribirla de otra forma o moverla.
    if profile.get("treadmill_access"):
        profile_lines.append(
            "- Has a treadmill: controlled-pace intervals and tempo work can be prescribed indoors when "
            "weather, heat, darkness or safety would otherwise compromise the session. On a treadmill the "
            "MACHINE sets the pace: write the speed in km/h next to the pace in the text (\"3' a 11,3 km/h "
            "(5:18/km) en cinta\"); the watch does not measure pace reliably indoors, so its target is a heart-rate "
            "zone or none (Beta 3.0, F10)."
        )
    altitude_m = profile.get("altitude_m")
    if altitude_m and altitude_m >= 1500:
        profile_lines.append(f"- Athlete trains at {altitude_m}m altitude — account for reduced pace/power at a given effort and longer recovery needs.")
    climate = profile.get("climate")
    if climate == "calido_humedo":
        profile_lines.append("- Hot/humid climate — reduce intensity expectations and note hydration in workout descriptions.")
    # EL BRICK NO SE MENCIONA AQUI, y no es lo mismo que prohibirlo.
    #
    # Habia cuatro ramas sobre `brick_preference`, y las cuatro hablaban de
    # `sport='brick'`, que **ya no existe en el esquema** desde la v1.1: un
    # multideporte no le llega bien a varios relojes. Eran cable viejo, y el
    # peor de los cuatro era el `else`, que se disparaba para todo atleta sin
    # preferencia y le metia el tema en el prompt por defecto.
    #
    # Lo que hace falta decir ya esta dicho donde toca: el prompt de decision
    # semanal explica que un brick son DOS sesiones el mismo dia y que lo que
    # las encadena va escrito en el texto de la primera. El entrenador lo
    # prescribe cuando la carrera objetivo lo pide, y no hay nada que el
    # atleta tenga que configurar.
    terrain = profile.get("terrain")
    if terrain == "montanoso":
        profile_lines.append("- Predominantly hilly/mountainous terrain — expect elevation gain on runs/rides; factor into pacing guidance.")
    elif terrain == "mixto":
        # Llegaba de la ficha y se tiraba (2026-09-14, atletas 2 y 3): con
        # "mixto" tiene cuestas a mano, que es lo que sostiene un bloque hacia
        # un trail o las series en cuesta sin montaña.
        profile_lines.append("- Mixed terrain: both flat routes and hills are available — flat for pace work, hills when the phase calls for them.")

    occupation_type = profile.get("occupation_type")
    if occupation_type:
        profile_lines.append(f"- Occupation: {occupation_type}.")
    avg_sleep_hours = profile.get("avg_sleep_hours")
    if avg_sleep_hours:
        profile_lines.append(f"- Average sleep: {avg_sleep_hours}h/night.")
    stress_level = profile.get("stress_level_self_report")
    # Decía "bias toward conservative intensity distribution": una regla fija
    # de población disparada por un desplegable, la misma clase de regla que se
    # retiró del HRV y del ACWR. El estrés de la vida suma al del entrenamiento,
    # pero cuánto le pesa a ESTE atleta lo dicen su recuperación y su check-in.
    if stress_level == "alto":
        profile_lines.append(
            "- Self-reported HIGH life stress. Life stress adds to training stress: read it together with "
            "their measured recovery and check-in when deciding intensity and where the hard days go. It is "
            "not, by itself, a reason to train less."
        )
    # LA FUERZA LA PIDE LA REJILLA, no un desplegable aparte.
    #
    # Habia un campo `strength_training_preference` ("si"/"no"/"indiferente")
    # que se preguntaba en Preferencias de Coaching, y desde la v1.1 decia lo
    # mismo que la fila "Fuerza" de la rejilla -- con la diferencia de que las
    # dos podian contradecirse. Un atleta con "No" en el desplegable y tres
    # dias de Fuerza marcados dejaba a alguien teniendo que decidir cual de las
    # dos ganaba, y esa decision no la tomaba nadie a proposito.
    #
    # Marcar Fuerza un dia ES pedir fuerza. No marcarla ningun dia ES no
    # quererla. El campo se retiro del alta en la v1.2 y aqui se deja de leer.
    if _quiere_fuerza(profile):
        # "where the schedule allows" es un permiso sin cantidad, y este repo ya
        # sabe como se lee eso: igual que los bloques horarios, un permiso que
        # nunca dice cuanto se cumple con cero. El atleta que pedia fuerza
        # recibia una sesion simbolica de prehab, o ninguna, y el plan no decia
        # por que. Lo que falta no es autorizacion: es un numero.
        profile_lines.append(
            "- Athlete WANTS strength training. Treat it as part of the plan, not as a leftover: "
            "decide how many sessions the week gets and say why in the rationale. If the athlete "
            "or a professional they see has asked for a specific number (see the health record's "
            "nutrition/body-composition section), that number is the target — meet it, or say "
            "explicitly what you prioritised instead. Strength is not automatically prehab: "
            "prehab addresses a declared injury, and a strength session can equally be there to "
            "build force, power or body composition."
        )
    preferred_rest_day = profile.get("preferred_rest_day")
    if preferred_rest_day:
        linea = f"- Prefers full rest specifically on: {preferred_rest_day} (respect if that day is already marked REST; otherwise treat as a soft preference)."
        # Jessie, 2026-09-15: la semana le puso "descanso completo como
        # prefieres" el sábado en el que ella pedía su activación. Lo que dice
        # para ESTA semana gana a lo que prefiere siempre.
        abiertos_ese_dia = [f for f in sorted(abiertos or {})
                            if ALL_DAYS_ES[date.fromisoformat(f).weekday()] == preferred_rest_day]
        if abiertos_ese_dia:
            linea += (f" This week the athlete opened {', '.join(abiertos_ese_dia)} in their check-in: that wins "
                      "over this preference.")
        carrera_ese_dia = [f for f in sorted(carreras or {})
                           if ALL_DAYS_ES[date.fromisoformat(f).weekday()] == preferred_rest_day]
        if carrera_ese_dia:
            linea += f" The athlete races on {', '.join(carrera_ese_dia)}: the race wins over this preference."
        profile_lines.append(linea)

    # Dos campos que el atleta rellena en su ficha y que NINGUN planificador
    # veia nunca. Estaban declarados en el modelo, viajaban hasta aqui y se
    # tiraban: solo los leia el asistente de salud. Es la misma clase de fallo
    # que ya aparecio con la cinta (F841), con el plan, con el informe y con la
    # ficha de numeros -- un dato que existe, que otro consumidor si recibe, y
    # que a este nadie le pasaba.
    #
    # Los dos cambian decisiones de verdad: uno dice si la semana puede contar
    # con estar en casa, y el otro si el volumen aerobico puede salir de otro
    # deporte cuando el principal no conviene.
    travel_frequency = profile.get("travel_frequency")
    if travel_frequency == "frecuente":
        profile_lines.append(
            "- Travels FREQUENTLY. Do not build the week around sessions that need a fixed venue "
            "(pool lanes, a specific gym, a known route) unless the availability table shows that day "
            "is protected. Prefer sessions that survive a hotel or an unknown city — running, "
            "bodyweight strength — for the days most likely to move, and say so in the rationale so "
            "the athlete knows which sessions are the portable ones."
        )
    elif travel_frequency == "ocasional":
        profile_lines.append(
            "- Travels occasionally: the week can assume a normal venue, but avoid making a single "
            "unrepeatable session the whole week's key workout."
        )

    # `cross_training_open` nace en True en el formulario, asi que el caso que
    # de verdad informa es el False: es una NEGATIVA explicita del atleta, y
    # sin ella el planificador puede sustituirle carrera por bici creyendo que
    # le hace un favor. El True se dice tambien, pero como permiso subordinado
    # a las reglas de arriba (disciplina objetivo y tabla de disponibilidad),
    # nunca como invitacion a repartir el volumen a su gusto.
    if profile.get("cross_training_open"):
        profile_lines.append(
            "- Open to cross-training: low-impact aerobic substitution (bike/swim/elliptical for an "
            "easy run, for instance) is an acceptable tool when load, a niggle or the weather make the "
            "original session a bad idea — still only within what the goal discipline and the sports "
            "allowed that day permit. Say why you substituted."
        )
    else:
        profile_lines.append(
            "- NOT open to cross-training: do not substitute one discipline for another to keep aerobic "
            "volume. If a session cannot be done as prescribed, adjust its duration or intensity, or "
            "leave it rest — but do not swap the sport."
        )

    profile_context_text = "\n".join(profile_lines) if len(profile_lines) > 1 else ""

    # Build competitions context
    competitions_text = ""
    # Una carrera ya corrida no va bajo "UPCOMING" ni bajo "MUST periodize towards these":
    # la semana del atleta 2 del 2026-10-05 recibía su 5K del 2026-09-20 como próxima
    # (auditoría del 2026-09-24). Se dice aparte, como corrida: la semana de después de
    # una carrera sí necesita saberla.
    def _fecha_de(c) -> str:
        return str((c.get("date") if isinstance(c, dict) else getattr(c, "date", "")) or "")[:10]

    # Ya corrida respecto a la semana que se escribe (`referencia`, su primer día), no a hoy:
    # la semana del lunes se genera el sábado, y el 5K del domingo es para ella una carrera
    # corrida, no una por preparar. Y sólo las recientes, las que la nota de después de una
    # carrera todavía mira: una de hace dos meses no es "esfuerzo reciente".
    from services.ai.langgraph.nodes.weekly_planner_node import _VENTANA_DE_RECUPERACION_MAX

    ref = referencia or date.today()
    hoy_iso = ref.isoformat()
    desde_iso = (ref - timedelta(days=_VENTANA_DE_RECUPERACION_MAX)).isoformat()
    corridas = [c for c in competitions if _fecha_de(c) and desde_iso <= _fecha_de(c) < hoy_iso]
    competitions = [c for c in competitions if not (_fecha_de(c) and _fecha_de(c) < hoy_iso)]
    # Las de antes de la semana que todavía no se han corrido (entre hoy y su primer día) NO son «ya corridas»
    # (2026-10-22): la semana del lunes 26 se generó el jueves 22 y el coach recibió el gravel del sábado 24
    # como próxima y como corrida a la vez, y preguntó cuál era. Para la semana cuentan igual (esfuerzo reciente).
    hoy_de_verdad = date.today().isoformat()
    ya_corridas = [c for c in corridas if _fecha_de(c) < hoy_de_verdad]
    antes_de_la_semana = [c for c in corridas if _fecha_de(c) >= hoy_de_verdad]

    def _linea(c) -> str:
        return f"- {(c.get('name') if isinstance(c, dict) else getattr(c, 'name', '')) or 'Carrera'} | {_fecha_de(c)}\n"

    if ya_corridas:
        competitions_text += "\n[CARRERAS YA CORRIDAS — no se preparan; cuentan como esfuerzo reciente]\n" + "".join(
            _linea(c) for c in ya_corridas)
    if antes_de_la_semana:
        competitions_text += (
            f"\n[CARRERAS ANTES DE ESTA SEMANA — todavía por correr (hoy es {hoy_de_verdad}); la semana empieza "
            "después de ellas: no se preparan aquí y cuentan como esfuerzo reciente]\n"
            + "".join(_linea(c) for c in antes_de_la_semana))
    if competitions:
        competitions_text += "\n[UPCOMING COMPETITIONS]\n"
        for comp in competitions:
            if isinstance(comp, dict):
                cname = comp.get("name", "Unknown")
                cdate = comp.get("date", "TBD")
                ctype = comp.get("race_type", "")
                cprio = comp.get("priority", "B")
                ctarget = comp.get("target_time", "")
            else:
                cname = getattr(comp, 'name', 'Unknown')
                cdate = getattr(comp, 'date', 'TBD')
                ctype = getattr(comp, 'race_type', '')
                cprio = getattr(comp, 'priority', 'B')
                ctarget = getattr(comp, 'target_time', '')
            target_str = f" (Target: {ctarget})" if ctarget else ""
            competitions_text += f"- {cname} | {cdate} | {ctype} | Priority: {cprio}{target_str}\n"
        competitions_text += "The training plan MUST periodize correctly towards these competitions, with a taper before Priority A races."

    custom_context = f"""
{base_context}

[ATHLETE SPORT PREFERENCES]
- Target Sport: {sports_pref.upper()}
- {sport_instruction}
- {distance_instruction}
- Physical & Time Limitations: {limitations or 'None'}
{day_table}
{two_block_note}
{profile_context_text}
{competitions_text}

CRITICAL SCHEDULING RULES (MANDATORY — NEVER VIOLATE):
{reglas_del_dia}
5. The athlete is NOT asked how many hours a week they have, and no declared ceiling exists. The right weekly volume comes from what this athlete actually absorbs — the volume target, the chronic load and the training-habits block — and from the phase of the season plan. Hours spent on a sport the athlete has no history of doing are hours taken away from the ones they really train.
6. Physical and time limitations must be fully respected.
7. If competitions are listed, taper correctly before Priority A events.
8. If pool access is "ninguno", NEVER schedule swimming — substitute with another sport from the day's allowed list, even for triathlon.
9. Base starting intensity/complexity on what the Activity/Metrics experts' analysis shows the athlete is ACTUALLY doing in recent training (session types, volumes, intensities already performed) — not on the self-reported training level alone. Only ramp volume gradually from a genuinely low real baseline; never downgrade an athlete who real data shows is already doing intervals/tempo/threshold work to a generic beginner plan just because they self-reported as "principiante".
{regla_de_dobles}
"""

    # Edad y sexo. Estaban en la ficha, en el validador de PHP y en MySQL, y no
    # llegaban a ningun prompt -- comprobado sobre los prompts archivados de una
    # generacion real, no leyendo el codigo. Van antes del historial de salud
    # porque son lo primero que un entrenador sabe de alguien, y el bloque trae
    # su propia regla: calibran lo que ESPERAS de sus numeros y no autorizan a
    # prescribirle menos. Ver services/ai/athlete_basics.py.
    basics_block = render_basics_block(profile)
    if basics_block:
        custom_context += "\n\n" + basics_block

    # The athlete's declared health record. Everything below used to be
    # collected by the health form and then discarded: structured injuries with
    # location/laterality/pain, the seven screening answers, and the intake
    # report were persisted on the profile and no agent ever read them -- only
    # the free-text `limitations` field above made it this far.
    health_block = render_health_block(profile)
    if health_block:
        custom_context += "\n\n" + health_block

    return custom_context.strip()


def _canonical_sports_for_day(raw_sports) -> list | None:
    """activities_per_day[day] can be: a list[str] of Spanish labels (new UI,
    see SPORT_LABEL_TO_KEY), or a legacy free-text string (old profiles saved
    before the Step 1 checkbox change) — treated as unconstrained ("Any")
    since we can't reliably parse free text into canonical sport keys."""
    if not raw_sports or isinstance(raw_sports, str):
        return None
    keys = []
    for label in raw_sports:
        key = SPORT_LABEL_TO_KEY.get(label, None)
        if key is None:
            return None  # "Cualquiera" or an unrecognized label present -> unconstrained
        keys.append(key)
    # Terreno, no deporte: quien marca "Trail" en una franja sigue pudiendo
    # correr en ella. Sin esta línea, un bloque de sólo trail rechazaría toda
    # sesión de carrera -- que es literalmente lo que se hace en un sendero.
    if "trail" in keys and "run" not in keys:
        keys.append("run")
    return keys or None


# Cuantas ventanas de tiempo distintas puede declarar un atleta en un dia.
# Debe coincidir con MAX_BLOCKS_PER_DAY en web_app/static/app.js.
MAX_BLOCKS_PER_DAY = 3


def _parse_day_blocks(raw_blocks) -> list[dict]:
    """day_time_blocks[day] entries: {"start": "06:00", "end": "07:00", "sports": [labels]}.
    Returns a chronologically sorted list of {"start","end","hours","sports"} (canonical sport
    keys, None = unconstrained), skipping malformed entries. Empty/missing input (legacy
    profiles predating time blocks, see web_app/static/app.js's initDaysGrid) -> []."""
    if not isinstance(raw_blocks, list):
        return []
    parsed = []
    for block in raw_blocks:
        if not isinstance(block, dict):
            continue
        start, end = block.get("start"), block.get("end")
        if not start or not end:
            continue
        try:
            sh, sm = (int(x) for x in start.split(":")[:2])
            eh, em = (int(x) for x in end.split(":")[:2])
            hours = round(((eh * 60 + em) - (sh * 60 + sm)) / 60.0, 2)
        except (ValueError, AttributeError):
            continue
        if hours <= 0:
            continue
        parsed.append({
            "start": start, "end": end, "hours": hours,
            "sports": _canonical_sports_for_day(block.get("sports")),
        })
    parsed.sort(key=lambda b: b["start"])
    # Un bloque ya no es una sesion: un bloque de 05:30-07:00 admite correr 45'
    # y nadar 40' dentro. Tres bloques son las tres ventanas reales de un dia
    # (manana, mediodia, tarde); el numero de SESIONES lo limita
    # plan_validator.MAX_SESSIONS_PER_DAY, no esto.
    return parsed[:MAX_BLOCKS_PER_DAY]


def _day_availability(profile: dict, day_es: str) -> dict:
    """Single source of truth for one day's availability, used by both
    build_custom_planning_context (prose) and build_availability_constraints
    (machine-checkable). Prefers the new day_time_blocks (real clock times,
    up to 2 sessions/day) and falls back to the legacy single hours_per_day
    number + activities_per_day list when no blocks are set (old profiles,
    or a day the athlete filled the old way) — never breaks existing data."""
    # LA REJILLA MANDA CUANDO EXISTE.
    #
    # El fallo que arregla, visto en una generacion REAL (atleta 2, 2026-09-10):
    # el atleta marco Nadar el martes como `fijo`, el prompt lo pidio bien y el
    # planificador lo puso -- y el validador lo BORRO, dejando el dia en
    # descanso, con este motivo:
    #
    #     'swim' no esta en los deportes permitidos ['strength'] para Martes
    #
    # Sus `day_time_blocks` de antes decian Martes 05:30-07:00 = solo Fuerza.
    # El prompt ya leia la rejilla nueva y el validador seguia leyendo las
    # franjas viejas, asi que las dos fuentes se contradecian y ganaba el
    # validador: el atleta pedia nadar y el sistema se lo quitaba en silencio.
    # Peor que antes de la v1.1, donde al menos nadie se lo habia prometido.
    #
    # Las franjas NO se borran (ver Database/013 y ../CLAUDE.md): se dejan de
    # consultar en cuanto su dueño contesta la rejilla. Un perfil sin rejilla
    # cae al camino de siempre, intacto.
    #
    # `hours` queda en None a proposito: la rejilla no tiene horas de reloj --
    # esa fue la decision de la v1.1, "sin horas, sin minutos; la IA decide
    # duracion e intensidad" -- y el tope que sigue en pie es el semanal.
    rejilla = _rejilla_por_dia(profile)
    if rejilla:
        entrada = rejilla.get(day_es)
        if not entrada:
            # Dia fuera de la rejilla = descanso. No se cae a las franjas: eso
            # resucitaria un dia que su dueño acaba de quitar.
            return {"hours": None, "sports": [], "blocks": []}
        etiquetas = sorted(set(entrada[ESTADO_FIJO]) | set(entrada[ESTADO_POSIBLE]))
        return {"hours": None, "sports": _canonical_sports_for_day(etiquetas), "blocks": []}

    day_time_blocks = profile.get("day_time_blocks", {}) or {}
    blocks = _parse_day_blocks(day_time_blocks.get(day_es))
    if blocks:
        total_hours = round(sum(b["hours"] for b in blocks), 2)
        sports_lists = [b["sports"] for b in blocks]
        # Aggregate sports = union across blocks, unless any block is
        # unconstrained (None) -> whole day unconstrained (same "Cualquiera
        # dominates" semantics as the single-block legacy path).
        if any(s is None for s in sports_lists):
            agg_sports = None
        else:
            agg_sports = sorted({s for lst in sports_lists for s in lst}) or None
        return {"hours": total_hours, "sports": agg_sports, "blocks": blocks}

    hours_per_day = profile.get("hours_per_day", {}) or {}
    activities_per_day = profile.get("activities_per_day", {}) or {}
    hrs = hours_per_day.get(day_es)
    try:
        hrs = float(hrs) if hrs not in (None, "", "?") else None
    except (TypeError, ValueError):
        hrs = None
    return {"hours": hrs, "sports": _canonical_sports_for_day(activities_per_day.get(day_es)), "blocks": []}


def _quiere_fuerza(profile: dict) -> bool:
    """Si la rejilla pide fuerza algun dia, en cualquiera de los dos estados.

    `fijo` y `posible` cuentan igual aqui a proposito: los dos significan que
    el atleta quiere fuerza en su semana, y lo que los distingue -- si ESE dia
    es obligatorio -- ya lo dice la tabla de peticiones. Lo que esta linea del
    prompt aporta es otra cosa: que la sesion de fuerza se disene entera, con
    un numero de sesiones decidido y dicho, en vez de caer como un resto.
    """
    rejilla = profile.get("sport_days") or {}
    if not isinstance(rejilla, dict):
        return False
    fila = rejilla.get("Fuerza") or rejilla.get("fuerza") or {}
    return isinstance(fila, dict) and any(
        estado in ("fijo", "posible") for estado in fila.values()
    )


# Aqui vivian `_admite_bici_y_carrera` y `dias_para_brick`, que miraban si
# alguna franja del atleta admitia bici y carrera seguidas. Solo existian
# para decidir cual de las cuatro ramas de `brick_preference` se emitia, y
# esa preferencia se retiro en la v1.2: un brick son dos sesiones el mismo
# dia y lo decide el entrenador, no un desplegable del perfil.


# Files that together make up one generation. Archived as a set so a history
# entry can be read back exactly as it was served the day it was generated.
#
# Split by scope (v5 Track 11) because "borrar generación" used to wipe all of
# them at once: an athlete who only wanted to drop this week's sessions lost
# their whole macrocycle too, with a dialog that never said so. Generating a
# week only ever touches the WEEK files, so deleting one should not reach
# further than generating one did.
_WEEK_FILES = ("weekly_plan.json", "planning.html")
_SEASON_FILES = ("season_plan.md", "season_phases.json", "analysis.html")
# summary.json holds BOTH halves, so a scoped delete strips its keys instead of
# removing the file -- deleting it would take the other half's cost, validation
# and prescription with it.
_SHARED_FILES = ("summary.json",)

_GENERATION_FILES = _WEEK_FILES + _SEASON_FILES + _SHARED_FILES

# Which summary.json keys belong to which half.
_WEEK_SUMMARY_KEYS = ("weekly_plan", "plan_validation", "adherence", "prescription", "fijos_poco_hechos")
_SEASON_SUMMARY_KEYS = ("season_plan", "goal_realism", "volume_baseline", "volume_reconciliation_note")


def _history_dir(storage_dir: Path) -> Path:
    return storage_dir / "history"


def archive_previous_generation(storage_dir: Path) -> str | None:
    """Copy the current generation into history/ before it gets overwritten.

    Every generation used to overwrite the last one in place, so asking for a
    new plan permanently destroyed the previous one -- an athlete who printed
    Monday's plan and regenerated on Tuesday had no way back to it, and the
    coach had no record of what it had actually prescribed. Archiving runs
    BEFORE the new files are written, which is the only moment the old ones
    still exist.

    Returns the entry id, or None when there is nothing to archive (a first
    generation) or the archive failed -- never raises, because losing the
    history copy must not cost the athlete the plan they just paid to generate.
    """
    summary_path = storage_dir / "summary.json"
    if not summary_path.exists():
        return None

    try:
        # Named after the generation it holds, not the moment it was archived,
        # so an entry keeps the same id no matter when it got copied.
        generated_at = json.loads(summary_path.read_text(encoding="utf-8")).get("analysis_date")
        stamp = (generated_at or datetime.now().isoformat())[:19].replace(":", "-")
        entry_dir = _history_dir(storage_dir) / stamp
        if entry_dir.exists():
            return stamp  # already archived (e.g. a retried generation)
        entry_dir.mkdir(parents=True, exist_ok=True)

        copied = 0
        for filename in _GENERATION_FILES:
            source = storage_dir / filename
            if source.exists():
                shutil.copy2(source, entry_dir / filename)
                copied += 1

        if not copied:
            entry_dir.rmdir()
            return None
        logger.info("Archived previous generation to history/%s (%d files)", stamp, copied)
        return stamp
    except (OSError, json.JSONDecodeError):
        logger.exception("Could not archive the previous generation; continuing without it")
        return None


def _read_history_entry(entry_dir: Path) -> dict | None:
    """One archived generation, in the same shape /api/plans/current returns."""
    summary_path = entry_dir / "summary.json"
    if not summary_path.exists():
        return None
    try:
        summary = json.loads(summary_path.read_text(encoding="utf-8"))
    except json.JSONDecodeError:
        return None

    weekly_plan = None
    weekly_path = entry_dir / "weekly_plan.json"
    if weekly_path.exists():
        try:
            weekly_plan = json.loads(weekly_path.read_text(encoding="utf-8"))
        except json.JSONDecodeError:
            weekly_plan = {"error": "corrupt_weekly_plan_file"}

    season_path = entry_dir / "season_plan.md"
    return {
        "id": entry_dir.name,
        "generated_at": summary.get("analysis_date"),
        # Entries archived before v5 Track 11 carry no generation_kind, so it
        # is inferred from what the snapshot actually holds rather than shown
        # as unknown.
        "kind": summary.get("generation_kind") or ("week" if weekly_plan else "season"),
        "athlete": summary.get("athlete"),
        "weekly_plan": weekly_plan,
        "season_plan": season_path.read_text(encoding="utf-8") if season_path.exists() else "",
        "plan_validation": summary.get("plan_validation"),
        "prescription": summary.get("prescription"),
        "total_cost_usd": summary.get("total_cost_usd"),
        "total_tokens": summary.get("total_tokens"),
        "cost_breakdown": summary.get("cost_breakdown", []),
        "has_planning_html": (entry_dir / "planning.html").exists(),
        "has_analysis_html": (entry_dir / "analysis.html").exists(),
    }


def _load_plan_for_adherence(storage_dir: Path) -> WeeklyPlanPayload | None:
    """The plan currently on disk — i.e. the one the athlete has been following.

    Read before run_generation_task overwrites it, so adherence is measured
    against what the athlete was actually given, not what we're about to give
    them. A failed previous generation left a placeholder rather than a plan
    and is skipped.
    """
    weekly_path = storage_dir / "weekly_plan.json"
    if not weekly_path.exists():
        return None
    try:
        raw = json.loads(weekly_path.read_text(encoding="utf-8"))
        if "error" in raw:
            return None
        return WeeklyPlanPayload.model_validate(raw)
    except (OSError, json.JSONDecodeError, ValueError):
        logger.warning("Could not read the previous plan for adherence from %s", weekly_path, exc_info=True)
        return None


def _read_json_if_exists(path: Path) -> dict | None:
    """Plan Maestro v4 Track B: summary.json is now written by TWO independent
    tasks (season-only generation and week generation), each owning half of
    it -- this lets each one merge its own fields on top of whatever the
    other already wrote, instead of clobbering it."""
    if not path.exists():
        return None
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        logger.warning("Could not read %s; treating as empty", path, exc_info=True)
        return None


_MAX_PLAUSIBLE_DAYS_AHEAD = 90  # see docstring below


def _next_week_start(existing: WeeklyPlanPayload | None, now: datetime) -> date:
    """The day after the last date already in weekly_plan.json, or today if
    no week has been generated yet -- clamped forward to today if the athlete
    fell behind (didn't click "Generar Semana" for a while), since generating
    a week that's already fully in the past isn't useful.

    Also clamped back to today if the latest date on file is implausibly far
    in the future (verified real case: a since-fixed validator bug -- see
    _race_day_violations' `expected_dates` gating -- synthesized race-day
    entries for competitions MONTHS away and stapled them onto week 1; the
    next "Generar Semana" click then read those as "the latest date", and
    started the new week in 2027 instead of the following real week). A
    legitimate sequential "Generar Semana" click only ever advances ~7 days
    past the previous correctly-computed start, so anything more than
    `_MAX_PLAUSIBLE_DAYS_AHEAD` out can only be corrupted/stray data, never a
    real gap -- trusting it would keep compounding into further wrong dates
    forever instead of self-correcting."""
    if existing is None or not existing.weeks:
        return now.date()
    all_dates = [day.date for week in existing.weeks for day in week.days]
    if not all_dates:
        return now.date()
    last_date = max(datetime.strptime(d, "%Y-%m-%d").date() for d in all_dates)
    if (last_date - now.date()).days > _MAX_PLAUSIBLE_DAYS_AHEAD:
        logger.warning(
            "weekly_plan.json's latest date (%s) is more than %d days ahead of today (%s) -- "
            "treating it as corrupted/stray data and starting the new week from today instead",
            last_date, _MAX_PLAUSIBLE_DAYS_AHEAD, now.date(),
        )
        return now.date()
    return max(last_date + timedelta(days=1), now.date())


# Cuantos dias antes del arranque de la semana se puede generar.
#
# No es una restriccion burocratica: es lo unico que protege el valor del
# producto. Cada generacion lee el estado del atleta HOY -- HRV, readiness,
# ACWR, adherencia a lo que lleva hecho -- y escribe siete dias a partir de
# ese retrato. Generar el lunes la semana que empieza el viernes tira a la
# basura cuatro dias de evolucion: el plan se escribe contra un atleta que ya
# no existe cuando le toca entrenarlo.
#
# Dos dias y no cero porque el atleta necesita ver su semana antes de que
# empiece, y no todo el mundo abre la app la vispera.
DIAS_DE_ANTELACION = 2

# Cuántas semanas por delante de hoy puede tener un atleta ya escritas además de la que
# está en curso: una. Ver ventana_de_generacion.
SEMANAS_POR_DELANTE = 1


def _dia_de_arranque(
    existing: WeeklyPlanPayload | None, now: datetime, pedido: str | date | None
) -> date:
    """Desde qué día se escribe la semana.

    Lo normal lo decide `_next_week_start`: el día siguiente al último que ya
    hay en el plan. `pedido` sólo entra cuando NO hay plan todavía, que es la
    única vez que la pregunta tiene sentido — después, la siguiente semana
    empieza donde acabó la anterior, y dejar elegir ahí abriría huecos o
    solapes en el calendario del atleta.

    Se acota a hoy o mañana. Fuera de ahí no es una preferencia, es un error:
    una semana que empieza el mes que viene se escribiría contra el retrato de
    hoy, que es justo lo que la ventana de antelación existe para impedir.
    """
    por_defecto = _next_week_start(existing, now)
    if not pedido or (existing is not None and existing.weeks):
        return por_defecto
    try:
        elegido = pedido if isinstance(pedido, date) else datetime.strptime(str(pedido), "%Y-%m-%d").date()
    except (ValueError, TypeError):
        logger.warning("Fecha de arranque ilegible (%r); se usa %s", pedido, por_defecto)
        return por_defecto
    hoy = now.date()
    if elegido not in (hoy, hoy + timedelta(days=1)):
        logger.warning(
            "Arranque pedido %s fuera de hoy/mañana (%s); se usa %s", elegido, hoy, por_defecto
        )
        return por_defecto
    return elegido


# Un primer bloque de uno o dos dias no se genera suelto: se une a la semana
# siguiente. Una generacion cuesta lo mismo escriba uno o siete dias, y un
# bloque de un dia obligaria al atleta a generar otra vez al dia siguiente.
MIN_DIAS_DEL_BLOQUE = 3


def dias_del_bloque(inicio: date, week_start_day: str | None) -> int:
    """Cuantos dias escribe esta generacion: hasta la vispera del dia en que empieza su semana.

    El atleta elige en su perfil que dia empieza su semana (`week_start_day`,
    Database/019). Si hoy es miercoles y su semana empieza el lunes, el bloque
    va de miercoles a domingo y la siguiente generacion ya cae de lunes a
    domingo. Sin eleccion, siete dias, como siempre.
    """
    dias = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]
    if week_start_day not in dias:
        return 7
    hasta = (dias.index(week_start_day) - inicio.weekday()) % 7 or 7
    return hasta if hasta >= MIN_DIAS_DEL_BLOQUE else hasta + 7


_DIAS_ES = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]
_MESES_ES = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto",
             "septiembre", "octubre", "noviembre", "diciembre"]


def _fecha_para_el_atleta(d: date) -> str:
    """'lunes 12 de octubre': lo que lee el atleta, no '2026-10-12' (auditoría del 2026-09-24)."""
    return f"{_DIAS_ES[d.weekday()]} {d.day} de {_MESES_ES[d.month - 1]}"


def ventana_de_generacion(existing: WeeklyPlanPayload | None, now: datetime) -> dict:
    """Si toca ya generar la siguiente semana, y desde cuando.

    Nunca bloquea a quien no tiene plan, ni a quien se quedo atras: ahi la
    semana siguiente empieza hoy o antes, y esperar no aporta nada."""
    inicio = _next_week_start(existing, now)
    desde = inicio - timedelta(days=DIAS_DE_ANTELACION)
    faltan = (desde - now.date()).days
    # EL TOPE: como mucho la semana en curso y la siguiente (el responsable, 2026-09-24).
    # La ventana de arriba se puede saltar ("generar de todas formas"), y así se encadenaban
    # semanas futuras sin haber entrenado ninguna: el atleta 2 tenía tres, a ~0,30 USD cada
    # una y escritas todas contra el retrato del mismo día. Una semana que empezaría dentro
    # de más de una semana ya es la tercera: eso no se salta.
    tope = inicio > now.date() + timedelta(days=SEMANAS_POR_DELANTE * 7)
    if tope:
        return {
            "next_start": inicio.isoformat(),
            "allowed_from": desde.isoformat(),
            "allowed": False,
            "limit": True,
            "days_until_allowed": max(faltan, 0),
            "reason": (f"Ya tienes tu semana en curso y la siguiente. La próxima empieza el "
                       f"{_fecha_para_el_atleta(inicio)} y podrás generarla desde el "
                       f"{_fecha_para_el_atleta(desde)}: así se escribe con cómo vas de verdad y con "
                       "lo que hayas entrenado."),
        }
    return {
        "next_start": inicio.isoformat(),
        "allowed_from": desde.isoformat(),
        "allowed": faltan <= 0,
        "limit": False,
        "days_until_allowed": max(faltan, 0),
        "reason": "" if faltan <= 0 else (
            f"Tu siguiente semana empieza el {_fecha_para_el_atleta(inicio)}: podrás generarla "
            f"desde el {_fecha_para_el_atleta(desde)} "
            f"({'falta 1 día' if faltan == 1 else f'faltan {faltan} días'}). "
            "El plan se escribe con tus datos de HOY: generarlo antes de tiempo lo construye "
            "sobre un retrato tuyo que ya habrá cambiado cuando toque entrenarlo. "
            "Cuanto más cerca del arranque, mejor; lo ideal es la víspera."
        ),
    }


# Cuantas semanas se conservan en weekly_plan.json. Doce son tres meses: mas
# que suficiente para cerrar semanas con su adherencia y para el resumen que lee
# el coach (que ademas solo lista ocho). El expediente y las graficas leen de
# track_record.jsonl, no de aqui, asi que esto no recorta el historial del
# atleta -- recorta una copia de trabajo que crecia sin que nadie la mirase.
MAX_WEEKS_KEPT = 12


def _append_week(existing: WeeklyPlanPayload | None, new_week: WeeklyPlanPayload) -> WeeklyPlanPayload:
    """Appends the single WeekBlock a run_week_generation call produced onto
    the athlete's running weekly_plan.json.

    The block from run_week_generation is always numbered week_number=1 --
    it's built inside its own isolated 1-week payload (see
    create_week_generation_workflow), with no idea where it actually falls in
    the athlete's rolling calendar -- so it gets renumbered here to its real
    position before being appended.

    Known limitation: this always APPENDS the next week: it does not support
    regenerating/replacing the most recently generated week in place. An
    athlete who dislikes a week can delete the whole current generation
    (existing "Eliminar esta generación") to fall back to the previous
    archived state, but not surgically redo just the last week."""
    new_block = new_week.weeks[0] if new_week.weeks else WeekBlock(week_number=1, days=[])
    if existing is None or not existing.weeks:
        return WeeklyPlanPayload(
            athlete_name=new_week.athlete_name,
            start_date=new_week.start_date,
            zones_table=new_week.zones_table or "",
            weeks=[new_block.model_copy(update={"week_number": 1})],
        )
    next_number = existing.weeks[-1].week_number + 1
    # El plan guardaba TODAS las semanas para siempre. Una pesa 38 KB en disco,
    # viaja entera PHP -> puente en cada mensaje del chat y vive en una columna
    # JSON de MySQL: al ano son ~2 MB por atleta que nadie vuelve a leer.
    #
    # Lo que se recorta es solo esta copia de trabajo. El historial de verdad
    # esta en otro sitio y no se toca: `track_record.jsonl` guarda cada semana
    # cerrada con su ejecucion real (es de donde salen las graficas), y las
    # trazas de razonamiento siguen archivadas aparte. Aqui lo unico que
    # aportan las semanas viejas es una linea de resumen en el contexto del
    # coach -- que ya tiene su propio tope, mas corto que este.
    #
    # `next_number` se calcula ANTES de recortar y sale de la ultima semana, no
    # de cuantas hay, asi que la numeracion del macrociclo no se desplaza por
    # tirar las de delante.
    conservadas = [*existing.weeks, new_block.model_copy(update={"week_number": next_number})]
    return WeeklyPlanPayload(
        athlete_name=existing.athlete_name,
        start_date=existing.start_date,
        zones_table=new_week.zones_table or existing.zones_table,
        # model_copy rather than rebuilding from `days`: a rebuilt WeekBlock
        # silently drops every field the constructor is not handed, which is
        # how week_rationale would vanish the moment a week got renumbered.
        weeks=conservadas[-MAX_WEEKS_KEPT:],
    )


def semana_a_completar(existing: WeeklyPlanPayload | None, hoy: date,
                       rehacer: bool = False) -> WeeklyPlanPayload | None:
    """La semana ANTERIOR a la que se cierra, si ya terminó del todo, para medirla entera.

    Una semana se cierra al generar la siguiente, y eso se hace hasta dos días antes de que
    termine: su sábado y su domingo (a menudo la tirada o la bici larga) quedaban pendientes y
    nadie volvía a medirlos, porque la generación siguiente cierra la semana siguiente. Así el
    expediente, Analytics y el macrociclo veían cada semana con sus últimos días sin hacer
    (auditoría del 2026-09-24). None si no hay o si todavía no terminó.
    """
    if existing is None or not existing.weeks:
        return None
    semanas = existing.weeks[:-2] if rehacer else existing.weeks[:-1]
    if not semanas:
        return None
    semana = semanas[-1]
    fechas = [d.date for d in semana.days if d.date]
    if not fechas or max(fechas) >= hoy.isoformat():
        return None
    return existing.model_copy(update={"weeks": [semana]})


def semana_a_cerrar(existing: WeeklyPlanPayload | None, rehacer: bool = False) -> WeeklyPlanPayload | None:
    """La semana que acaba de terminar, sola, para medir su adherencia y cerrarla.

    Es la ultima del plan; al REHACER, la ultima es la que se va a reescribir
    (todavia no ha ocurrido) y la que termina es la anterior. None si no hay.
    """
    if existing is None or not existing.weeks:
        return None
    semanas = existing.weeks[:-1] if rehacer else existing.weeks
    if not semanas:
        return None
    return existing.model_copy(update={"weeks": [semanas[-1]]})


# «Generar mi semana automáticamente» (Database/027, decisión del responsable del
# 2026-09-24): la semana se genera sola la víspera, sin check-in ni preguntas. Sin
# check-in el prompt no dice NADA de cómo llega el atleta, y un silencio se rellena:
# "como te sientes bien", la molestia de hace un mes como si fuera de hoy. Esto dice
# lo que hay y lo que no ("no asumir cosas que no se afirman en esa semana").
NOTA_DE_SEMANA_AUTOMATICA = (
    "[SEMANA AUTOMÁTICA]\n"
    "Esta semana la genera la app sola, la víspera de empezar, porque el atleta encendió "
    "«Generar mi semana automáticamente». No hubo check-in ni preguntas: el atleta no ha dicho nada "
    "de cómo llega a esta semana. Lo que sabes de ella es lo que miden Garmin y su historial; lo que "
    "dijo antes (cuaderno, check-ins y respuestas de otras semanas, su ficha) vale con su fecha, no "
    "como algo dicho para esta semana. No le atribuyas sensaciones, cansancio, molestias ni peticiones "
    "que no haya dicho, y no escribas como si hubiera contestado. Si algo que sólo él sabe cambiaría "
    "la semana, dilo una vez en el racional: puede pedirle al coach en el chat que lo anote para las "
    "siguientes, o eliminar esta semana y volver a generarla contándolo en el check-in."
)


def render_respuestas_previas(existing: WeeklyPlanPayload | None) -> str:
    """Lo que el atleta contesto al generar la semana anterior, o "".

    Existe porque el coach volvia a preguntar lo mismo cada semana: medido el
    2026-09-11, las tres preguntas de la semana 2 eran las tres de la semana 1,
    veinte minutos despues. Se va con la semana cuando se borra.
    """
    if existing is None or not existing.weeks:
        return ""
    qa = getattr(existing.weeks[-1], "coach_answers", None) or []
    lineas = [f"- **{q.get('pregunta', '').strip()}** {q.get('respuesta', '').strip()}"
              for q in qa if q.get("respuesta")]
    if not lineas:
        return ""
    return "## Lo que te contesto al generar su semana anterior\n" + "\n".join(lineas)


def inicio_de_la_ultima_semana(existing: WeeklyPlanPayload | None) -> date | None:
    """Donde empieza la ultima semana del plan, para poder rehacerla EN SU SITIO."""
    if existing is None or not existing.weeks or not existing.weeks[-1].days:
        return None
    fechas = [d.date for d in existing.weeks[-1].days if d.date]
    if not fechas:
        return None
    try:
        return min(datetime.strptime(f, "%Y-%m-%d").date() for f in fechas)
    except ValueError:
        return None


def semana_objetivo(existing: WeeklyPlanPayload | None, now: datetime,
                    pedido: str | date | None = None, rehacer: bool = False) -> date:
    """El primer día de la semana que va a escribir esta generación.

    La misma cuenta que hace `run_week_generation_task`: rehacer reescribe la última en su
    sitio; si no, la siguiente (o hoy/mañana en la primera). La usa el tope de versiones
    (`cupo_de_generaciones`), que tiene que saber qué semana se va a pagar ANTES de lanzarla.
    """
    inicio_ultima = inicio_de_la_ultima_semana(existing) if rehacer else None
    return inicio_ultima or _dia_de_arranque(existing, now, pedido)


def _replace_last_week(existing: WeeklyPlanPayload | None,
                       new_week: WeeklyPlanPayload) -> WeeklyPlanPayload:
    """La semana nueva ocupa el sitio de la ultima, en vez de ir detras.

    Hasta aqui solo se sabia anadir, y `_append_week` lo dice como limitacion
    conocida: un atleta al que no le gustaba SU semana solo podia borrar la
    generacion entera o pedir "Rechazar / Ajustar", que rehace el analisis y el
    macrociclo tambien -- una generacion completa por querer mover un
    miercoles.

    Conserva el numero de semana: es la MISMA semana del ciclo, escrita otra
    vez, y renumerarla desplazaria todo el macrociclo que cuelga de ella.
    """
    nuevo = new_week.weeks[0] if new_week.weeks else WeekBlock(week_number=1, days=[])
    if existing is None or not existing.weeks:
        return _append_week(existing, new_week)
    numero = existing.weeks[-1].week_number
    return WeeklyPlanPayload(
        athlete_name=existing.athlete_name,
        start_date=existing.start_date,
        zones_table=new_week.zones_table or existing.zones_table,
        weeks=[*existing.weeks[:-1], nuevo.model_copy(update={"week_number": numero})],
    )


def _load_saved_prescription(storage_dir: Path) -> dict:
    """The prescription stored alongside the plan it was used to write.

    Missing or unreadable is normal, not an error: plans generated before this
    existed simply have none, and callers fall back to formula-derived zones.
    """
    summary_path = storage_dir / "summary.json"
    if not summary_path.exists():
        return {}
    try:
        return json.loads(summary_path.read_text(encoding="utf-8")).get("prescription") or {}
    except (json.JSONDecodeError, OSError) as exc:
        logger.warning("Could not read the saved prescription from %s: %s", summary_path, exc)
        return {}


ALL_DAYS_ES = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]


#: Los deportes de la rejilla, en el orden en que se le enseñan al atleta.
DEPORTES_REJILLA = ("Correr", "Trail", "Bici", "Nadar", "Fuerza")
ESTADO_FIJO = "fijo"
ESTADO_POSIBLE = "posible"


def _rejilla_por_dia(profile: dict) -> dict[str, dict[str, list[str]]]:
    """La rejilla `{deporte: {dia: estado}}` dada la vuelta a `{dia: {...}}`.

    Devuelve `{}` si el atleta todavía no la ha contestado, que es la señal de
    que hay que caer al camino viejo de franjas horarias.
    """
    bruto = profile.get("sport_days") or {}
    if not isinstance(bruto, dict):
        return {}
    por_dia: dict[str, dict[str, list[str]]] = {}
    for deporte in DEPORTES_REJILLA:
        fila = bruto.get(deporte)
        if not isinstance(fila, dict):
            continue
        for dia, estado in fila.items():
            if dia not in ALL_DAYS_ES or estado not in (ESTADO_FIJO, ESTADO_POSIBLE):
                continue
            entrada = por_dia.setdefault(dia, {ESTADO_FIJO: [], ESTADO_POSIBLE: []})
            entrada[estado].append(deporte)
    return por_dia


def render_sport_days_table(profile: dict) -> str:
    """Lo que el atleta PIDE, que no es lo mismo que lo que puede.

    ## El fallo que arregla

    Hasta la v1.1 el perfil sólo sabía expresar PERMISO. Las franjas horarias
    decían cuándo estaba libre, y el bloque de reglas se lo repetía al modelo
    con todas las letras: "UN BLOQUE ES UN PERMISO, NO UNA OBLIGACIÓN". No
    existía ningún campo donde dijera qué QUERÍA entrenar.

    En la beta salió en cuatro comentarios y el más claro fue: "quieren nadar y
    la IA no dejó natación para ese día". No fue un fallo del modelo — leyó un
    permiso y decidió, correctamente según sus reglas, no usarlo. Nadie se lo
    había pedido.

    ## Los tres estados, y por qué el del medio importa

    `fijo` es una petición y el plan la cumple. `posible` es el permiso de
    siempre. Ausente es descanso. El caso real que lo motivó: "los domingos
    puedo hacer bici y correr, pero para mí, bici que lo decida el coach".

    Devuelve "" cuando el atleta no ha contestado la rejilla, para que
    `render_availability_table` siga usando la tabla de franjas de los perfiles
    viejos. Ni un dato se pierde por un cambio de pantalla.
    """
    por_dia = _rejilla_por_dia(profile)
    if not por_dia:
        return ""

    lineas = [
        "\n[LO QUE EL ATLETA PIDE PARA SU SEMANA]",
        "Dia        | FIJO (lo ha pedido)      | POSIBLE (decides tu)",
        "-----------|--------------------------|---------------------",
    ]
    for dia in ALL_DAYS_ES:
        entrada = por_dia.get(dia)
        if not entrada:
            lineas.append(f"{dia:<11}| —                        | — (descanso)")
            continue
        fijos = ", ".join(entrada[ESTADO_FIJO]) or "—"
        posibles = ", ".join(entrada[ESTADO_POSIBLE]) or "—"
        lineas.append(f"{dia:<11}| {fijos:<25}| {posibles}")
    return "\n".join(lineas)


def render_availability_table(profile: dict) -> str:
    """La disponibilidad declarada, en una tabla que se lee de un vistazo.

    Fuente unica: la lee el planificador semanal y tambien el coach del chat.
    Que el coach NO la tuviera es lo que produjo el fallo mas caro de esta
    version: el atleta pregunto por que su miercoles tenia una sola sesion si
    el suele correr y nadar esa manana, y el coach -- que no podia ver que su
    perfil declara UN bloque ese dia -- se invento tres razones de
    entrenamiento (proteger el sueno, repartir la carga, la molestia de ingle).
    Ninguna de las tres era la razon. Un coach que no puede ver una restriccion
    la explica con lo unico que le queda, que es su propia verosimilitud.
    """
    rejilla = render_sport_days_table(profile)
    if rejilla:
        return rejilla

    available_days = profile.get("available_days", []) or []
    lineas = ["\n[ATHLETE DAILY AVAILABILITY — EXACT SCHEDULE]",
              "Day        | Available | Block(s) | Allowed Sports",
              "-----------|-----------|----------|---------------"]
    for day_es in ALL_DAYS_ES:
        if day_es not in available_days:
            lineas.append(f"{day_es:<11}| REST      | -        | Rest day — NO training sessions")
            continue
        avail = _day_availability(profile, day_es)
        blocks = avail["blocks"]
        if blocks:
            for i, b in enumerate(blocks):
                sports_label = ", ".join(b["sports"]) if b["sports"] else "Any"
                lineas.append(
                    f"{day_es:<11}| YES       | bloque {i}: {b['start']}-{b['end']} ({b['hours']}h) | {sports_label}"
                )
        else:
            hrs = avail["hours"] if avail["hours"] is not None else "?"
            sports_label = ", ".join(avail["sports"]) if avail["sports"] else "Any"
            lineas.append(f"{day_es:<11}| YES       | {hrs}h (sin horario) | {sports_label}")
    return "\n".join(lineas)


def _nota_del_fijo(afinamiento: dict | None) -> str:
    """Cómo se lee un FIJO la semana que se genera. En afinamiento cede, y se dice.

    Semanas del 5K Monterrico de los atletas 2 y 3 (2026-09-14): la nota de carga
    decía que los fijos pasaban a posibles y esta tabla decía "Ponlo"; ganó la
    tabla y las dos semanas conservaron sus tres fuerzas fijas a seis días de la
    carrera. Ver `_NOTA_DE_AFINAMIENTO` en weekly_planner_node.py.
    """
    if afinamiento:
        return (
            f"\n- **FIJO**: esta semana es el afinamiento de {afinamiento.get('name')} ({afinamiento.get('date')}), "
            "así que un FIJO cuenta como POSIBLE. El deporte de la carrera se conserva; una sesión fija accesoria "
            "se puede acortar o quitar, y se dice en `week_rationale`."
        )
    return (
        "\n- **FIJO**: el atleta quiere ESE deporte ESE dia. Ponlo. Si por una razon de carga o de "
        "salud crees que no debe ir, no lo borres en silencio -- ponlo mas corto o mas suave y di en "
        "`adaptation` por que. Quitar un dia fijo sin explicarlo es la forma mas rapida de que "
        "reescriba la semana entera."
    )


def _excepcion_de_la_semana(abiertos: dict[str, list[str]] | None) -> str:
    """La frase que dice qué días abrió esta semana, o "" si ninguno."""
    if not abiertos:
        return ""
    from services.ai.checkin import describir_dias_abiertos
    return (f" Esta semana el atleta abrio en su check-in {describir_dias_abiertos(abiertos)}: esos dias cuentan "
            "como POSIBLE con esos deportes.")


def describir_carreras_de_la_semana(competitions: list | None, fechas, cerradas=()) -> dict[str, str]:
    """{fecha: "domingo 11/10: «Trail X» (Correr)"} de las carreras de ESTA semana.

    Las fechas que el atleta cerró en el check-in no salen: gana lo que dijo para esta
    semana, igual que en `plan_validator.restriccion_del_dia`.
    """
    from services.ai.langgraph.validation.plan_validator import deportes_de_la_competicion
    from services.ai.number_audit import fecha_escrita

    nombres = {"run": "Correr", "bike": "Bici", "swim": "Nadar"}
    fechas = {str(f)[:10] for f in (fechas or [])} - {str(f)[:10] for f in (cerradas or [])}
    salida: dict[str, str] = {}
    for comp in competitions or []:
        if not isinstance(comp, dict) or str(comp.get("date") or "")[:10] not in fechas:
            continue
        fecha = str(comp["date"])[:10]
        deportes = [nombres[d] for d in ("run", "bike", "swim") if d in deportes_de_la_competicion(comp)]
        texto = f"«{comp.get('name') or 'competencia'}» ({', '.join(deportes) or 'su deporte'})"
        salida[fecha] = f"{salida[fecha]}; {texto}" if fecha in salida else f"{fecha_escrita(fecha)}: {texto}"
    return dict(sorted(salida.items()))


def _carreras_de_la_semana(carreras: dict[str, str] | None) -> str:
    """La frase de la nota de la tabla: el día de una carrera está disponible para ella."""
    if not carreras:
        return ""
    return ("\n- **El dia de una carrera esta disponible para esa carrera**, aunque su tabla lo tenga de descanso: "
            f"{'; '.join(carreras.values())}. Su tabla es su rutina de entrenamiento, no su calendario de "
            "carreras. Ese dia es la carrera (`race_day`), no un entreno mas.")


def _sport_days_note(profile: dict, afinamiento: dict | None = None,
                     abiertos: dict[str, list[str]] | None = None,
                     carreras: dict[str, str] | None = None) -> str:
    """Cómo se lee la tabla de peticiones. Devuelve "" si no hay rejilla.

    El texto que sustituye es el que empieza por "UN BLOQUE ES UN PERMISO", y
    la diferencia entre los dos es todo el cambio: aquel describía un hueco en
    la agenda y éste describe una decisión del atleta.

    La frontera se mantiene donde estaba y no se mueve ni un centímetro: **el
    deporte y el día son suyos; el volumen y la intensidad son nuestros.** Un
    `fijo` no autoriza a nadie a meter una serie de VO2max porque el atleta
    pidió correr: pidió correr, no pidió esa sesión.
    """
    por_dia = _rejilla_por_dia(profile)
    if not por_dia:
        return ""

    fijos_totales = sum(len(e[ESTADO_FIJO]) for e in por_dia.values())
    nota = (
        "\nCOMO SE LEE ESTA TABLA. No es una agenda de huecos libres: es lo que el atleta ha PEDIDO."
        + _nota_del_fijo(afinamiento)
        + "\n- **POSIBLE**: puede ese dia y te deja decidir. Usalo o no segun la fase, la carga y sus "
        "habitos. Dejarlo vacio es normal y correcto. Marcar muchos dias como posibles no es pedir "
        "entrenar muchos dias: cuantos usa lo dicen sus habitos reales, la fase y su recuperacion. Y "
        "bajar volumen tambien es quitar una sesion entera, no solo acortarlas todas."
        "\n- **Un dia sin nada es descanso.** No inventes sesiones ahi."
        + _excepcion_de_la_semana(abiertos)
        + _carreras_de_la_semana(carreras)
        + "\n- Un dia puede llevar dos deportes si los dos estan marcados y hay razon de entrenamiento. "
        "Cada sesion lleva su `session_slot` en orden cronologico: 'am', 'pm', 's3'."
        "\n\nLO QUE DECIDES TU ES EL VOLUMEN Y LA INTENSIDAD, no el deporte ni el dia. Por ejemplo "
        "(hipotetico, no es de este atleta): si alguien pide nadar un martes, cuanto nada y a que ritmo "
        "lo dices tu, con su carga cronica, su "
        "fase y su recuperacion delante. Un dia fijo no es permiso para meterle una sesion de calidad: "
        "pidio el deporte, no la sesion."
    )
    if fijos_totales == 0:
        nota += (
            "\n\nEste atleta no ha marcado ningun dia como fijo: te ha dejado toda la forma de la "
            "semana. Decide tu, con sus habitos reales."
        )
    return nota


def _regla_del_fijo(afinamiento: dict | None) -> str:
    """La regla 4 de CRITICAL SCHEDULING RULES, tal como vale la semana que se genera."""
    if afinamiento:
        return (
            f"4. This week is the taper for {afinamiento.get('name')} ({afinamiento.get('date')}), so FIJO counts "
            "as POSIBLE this week: keep the race's own sport; an accessory FIJO session may be shortened or "
            "removed — say which and why in `week_rationale`."
        )
    return (
        "4. Every FIJO appears on its day. If load or health argue against it, make it shorter or "
        "easier and say why in `adaptation` — never drop it silently."
    )


def _reglas_criticas_de_disponibilidad(profile: dict, afinamiento: dict | None = None,
                                       abiertos: dict[str, list[str]] | None = None,
                                       carreras: dict[str, str] | None = None) -> tuple[str, str]:
    """Las reglas 1-4 y 10 de CRITICAL SCHEDULING RULES, para la tabla que haya.

    Con la rejilla de la v1.1 esas reglas seguian hablando de la tabla de
    franjas: "Available: YES", la columna "Allowed Sports" y "un bloque es un
    PERMISO". Visto en el prompt archivado del atleta 2 el 2026-09-11: ninguna
    de esas columnas existia en su prompt, y la seccion marcada "NEVER VIOLATE"
    decia permiso justo debajo de una tabla cuyo FIJO significa "ponlo". Lo que
    va bajo ese encabezado gana a todo lo demas (tests/test_prompt_coherente.py),
    asi que tiene que describir la tabla que el modelo tiene delante.
    """
    # Los días que abrió esta semana (Database/024) son la única excepción, y va
    # DENTRO de las reglas: un bloque aparte que las contradice pierde.
    abiertas = ""
    if abiertos:
        from services.ai.checkin import describir_dias_abiertos
        abiertas = describir_dias_abiertos(abiertos)
    extra_1 = f", or on a date the athlete opened for THIS week in their check-in: {abiertas}" if abiertas else ""
    extra_2 = (" — except the dates opened this week, which count as POSIBLE with the sports they were opened "
               "with") if abiertas else ""
    extra_3 = " (on a date opened this week, only the sports it was opened with)" if abiertas else ""
    # Y el día de una carrera (Hugo, 2026-10-09): bajo NEVER VIOLATE, "un día sin nada es
    # descanso" ganó a "el día de una carrera es `race_day`" y la carrera acabó quitada.
    if carreras:
        lista = "; ".join(carreras.values())
        extra_1 += f", or on the date of one of the athlete's races this week, for that race: {lista}"
        extra_2 += (" — and except" if extra_2 else " — except") + " a race's own date, which is available for the race"
        extra_3 += " (on a race's date, the race's own sports as well)"
    if _rejilla_por_dia(profile):
        return (
            "1. ONLY schedule training on days where the athlete's table above lists at least one sport "
            "(FIJO or POSIBLE)" + extra_1 + ".\n"
            "2. A day with nothing in either column is a Rest day — absolutely NO training whatsoever"
            + extra_2 + ".\n"
            "3. Each session may ONLY use a sport the athlete marked for that day, FIJO or POSIBLE"
            + extra_3 + ".\n"
            + _regla_del_fijo(afinamiento),
            "10. POSIBLE is a PERMISSION, not a slot to fill: leaving it empty is normal, and how many "
            "POSIBLE days get a session follows the athlete's real habits, the phase and recovery — not how "
            "many boxes they ticked. Removing a whole session is a valid way to lower volume. Several "
            "sessions on one date are ALLOWED — never required — when both sports are marked that day "
            "and there is a training reason. Each gets its own WorkoutDay, `session_slot` \"am\", \"pm\", "
            "\"s3\" in chronological order (keys, not times of day); `session_slot=\"unica\"` only when "
            "the day really has one session.",
        )
    return (
        '1. ONLY schedule training sessions on days marked as "Available: YES" in the table above' + extra_1 + '.\n'
        '2. On days marked as "REST", assign a Rest day — absolutely NO training whatsoever' + extra_2 + '.\n'
        '3. On each available day, ONLY use the sports listed in the "Allowed Sports" column for that block'
        + extra_3 + '.\n'
        '4. If a block says "Allowed Sports: Correr" — do NOT assign cycling or swimming to that block.',
        "10. A block is a PERMISSION, not a slot to fill: leaving one empty is normal, and an athlete with "
        "three blocks on one day very rarely trains three times that day. Several sessions on one date are "
        "ALLOWED — never required — whenever the BLOCK'S HOURS fit them and there is a training reason; the "
        "number of blocks is not the limit, and neither is it a target. A single 05:00-07:00 block whose "
        'allowed sports are "run, swim" holds a 45\' run AND a 40\' swim: two WorkoutDay entries, same '
        '`block_index`, `session_slot` "am" then "pm" in chronological order (they are keys, not times of '
        'day). Use `session_slot="unica"` only when the day really has one session. What must always hold: '
        "the sessions of a block must fit in that block's hours, and each session may only use that "
        "block's allowed sports.",
    )


def _multi_block_note(profile: dict, afinamiento: dict | None = None,
                      abiertos: dict[str, list[str]] | None = None,
                      carreras: dict[str, str] | None = None) -> str:
    """La regla que convierte la tabla en algo que el planificador puede usar."""
    rejilla = _sport_days_note(profile, afinamiento, abiertos, carreras)
    if rejilla:
        return rejilla

    available_days = profile.get("available_days", []) or []
    varios = [d for d in ALL_DAYS_ES
              if d in available_days and len(_day_availability(profile, d)["blocks"]) > 1]
    nota = (
        # v7.2. Antes esto decia que un bloque con 'run, swim' era "una
        # invitacion explicita a poner AMBAS cosas". Junto a una TABLA de
        # bloques y sin decir en ningun sitio que un bloque puede quedarse
        # vacio, el modelo leyo una rejilla de huecos y los relleno: un atleta
        # con tres bloques el lunes recibio tres micro-sesiones ese lunes.
        # El texto decia cuando PUEDE anadir sesiones y nunca cuando puede no
        # hacerlo, que es la misma clase de fallo que la regla 10.
        "\nUN BLOQUE ES UN PERMISO, NO UNA OBLIGACION. La tabla dice CUANDO PUEDE entrenar este "
        "atleta, no cuando TIENE QUE hacerlo. Dejar un bloque vacio es normal y correcto, y un "
        "atleta con tres bloques un dia y uno los demas casi nunca entrena tres veces ese dia: "
        "tiene mas margen ese dia, nada mas. Rellenar cada hueco con una micro-sesion no es un "
        "plan, es una agenda -- y ademas entrena peor, porque tres sesiones de 25' no producen la "
        "adaptacion de una de 75'."
        "\nCuantas sesiones hay y de que son lo deciden el objetivo, la fase del mesociclo y lo que "
        "este atleta YA hace (ver sus habitos de entrenamiento), nunca el numero de huecos libres."
        "\nDicho eso, un bloque SI admite varias sesiones cuando sus horas alcanzan y hay una razon "
        "de entrenamiento: un bloque de 05:30-07:00 con 'run, swim' permite 45' de carrera y 40' de "
        "natacion el mismo dia. Hazlo si el historial muestra que el atleta ya entrena asi, o si la "
        "fase lo pide; no por ocupar el bloque. Cada sesion lleva su `block_index` (el numero de "
        "bloque de la tabla) y su propia `session_slot` en orden cronologico: 'am', 'pm', 's3', "
        "'s4'. La SUMA de las sesiones de un bloque debe caber en sus horas."
    )
    if varios:
        nota += f"\nDias con mas de un bloque: {', '.join(varios)}."
    return nota


def anotar_gasto(profile_dict: dict, kind: str, result: dict, detail: str | None = None) -> None:
    """Anota lo que costó la corrida EN CUANTO el flujo devuelve.

    ## El fallo que arregla, con los números

    El 2026-09-10 se generaron cinco corridas del atleta 2 y el libro sólo
    guardó TRES. Las dos que murieron a mitad no dejaron ni una línea, aunque
    su propio log dijera lo que habían costado:

        Week generation workflow complete: $0.1153 (76768 tokens, 8 node calls)
        ERROR - La semana no se pudo generar.

    La causa es de posición, no de lógica: `record_run` vivía ~170 líneas por
    debajo del `raise` que corta una semana sin plan, así que sólo se llegaba a
    él por el camino feliz. Y `cost_summary` ni siquiera se leía antes.

    **Eso hace que el panel del administrador mienta a la baja justo donde más
    importa.** El bucle de regeneración es lo que la v1.1 salió a medir — 37
    generaciones para 5 atletas — y los intentos fallidos son parte de ese
    bucle: cuestan dinero de verdad y son los que más se repiten, porque un
    atleta al que le falla la generación la vuelve a lanzar. Contarlos fuera
    convierte el número que decide el MVP en un suelo disfrazado de total.

    `record_run` ya estaba escrito para esto -- su docstring dice que acepta un
    resumen vacío porque "anotar ese cero es más útil que no anotar nada". Sólo
    faltaba llamarlo desde donde se puede.

    ## Por qué no puede reventar

    Porque va delante del trabajo que sí le importa al atleta. Una generación
    buena no se puede perder porque falle la contabilidad, así que esto se traga
    sus errores y los deja en el log -- la misma tolerancia que
    `EventRepository::record()`.
    """
    resumen = (result or {}).get("cost_summary") or {}
    try:
        usage_ledger.record_run(
            profile_dict.get("name") or "",
            kind,
            {
                "total_cost_usd": float(resumen.get("total_cost_usd") or 0.0),
                "total_tokens": int(resumen.get("total_tokens") or 0),
                "agents": resumen.get("agents", []),
            },
            mode=os.environ.get("AI_MODE"),
            detail=detail,
        )
    except Exception:
        logger.exception("No se pudo anotar el gasto de la corrida %s", kind)


def build_availability_constraints(profile: dict) -> dict:
    """Structured (machine-checkable) counterpart to build_custom_planning_context's
    prose table, consumed by services/ai/langgraph/validation/plan_validator.py."""
    available_days = profile.get("available_days", [])
    all_days_es = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]

    days = {}
    for day_es in all_days_es:
        if day_es in available_days:
            avail = _day_availability(profile, day_es)
            days[day_es] = {"available": True, **avail}
        else:
            days[day_es] = {"available": False, "hours": 0, "sports": [], "blocks": []}

    # Lo que el atleta PIDIO, aparte de lo que PUEDE. El validador comprueba
    # que cada `fijo` aparece; los `posible` no se comprueban porque no
    # usarlos es una decision legitima del entrenador.
    #
    # `{}` cuando el perfil todavia no tiene rejilla, y entonces la regla no
    # dispara: un perfil viejo no ha pedido nada, asi que no puede faltarle
    # nada.
    # En CLAVES canonicas (`run`, `bike`…), no en las etiquetas que ve el
    # atleta: es lo que compara el validador, que lee `day.sport`. Y "Trail" es
    # terreno y no deporte, asi que un trail fijo se satisface con una sesion de
    # carrera -- la misma regla que ya aplica `_canonical_sports_for_day`.
    peticiones = {}
    for dia, entrada in _rejilla_por_dia(profile).items():
        claves = sorted({
            SPORT_LABEL_TO_KEY.get(etiqueta)
            for etiqueta in entrada[ESTADO_FIJO]
            if SPORT_LABEL_TO_KEY.get(etiqueta)
        } - {"trail"} | ({"run"} if "Trail" in entrada[ESTADO_FIJO] else set()))
        if claves:
            peticiones[dia] = claves

    return {
        "days": days,
        # Sin `weekly_hours_budget` del perfil: el presupuesto de la semana lo
        # pone `compute_weekly_volume_target` mas adelante, con la carga
        # cronica medida. Ver el comentario de `build_custom_planning_context`.
        "sport_days_fijos": peticiones,
        # En qué día empieza su semana: las semanas del macrociclo son las suyas.
        # Ver services/ai/semanas_del_macrociclo.py.
        "week_start_day": profile.get("week_start_day") or "",
    }

def _compute_recent_actual_weekly_hours(garmin_data, days: int = 7) -> float | None:
    """Sums ActivitySummary.duration (seconds, Garmin's own convention) for
    recent_activities in the last `days` days -> real weekly training hours,
    to compare against the athlete's self-reported current_weekly_volume_hours."""
    activities = getattr(garmin_data, "recent_activities", None) or []
    cutoff = datetime.now() - timedelta(days=days)
    total_seconds = 0.0
    found_any = False
    for act in activities:
        start = getattr(act, "start_time", None)
        summary = getattr(act, "summary", None)
        duration = getattr(summary, "duration", None) if summary else None
        if not start or duration is None:
            continue
        try:
            start_dt = datetime.fromisoformat(str(start).replace("Z", "+00:00")).replace(tzinfo=None)
        except ValueError:
            continue
        if start_dt >= cutoff:
            total_seconds += float(duration)
            found_any = True
    return round(total_seconds / 3600.0, 1) if found_any else None


def _horas_semanales_por_tipo(garmin_data, days: int) -> dict[str, float] | None:
    """Horas por semana de RESISTENCIA (nadar, bici, correr) y de FUERZA, por separado.

    Fernando, Beta 2.0 publicada, 2026-09-22: su media de 8,1 h/semana mezclaba su
    gimnasio con su natación, la fase decía ~7,5 h y la semana sumó 7 h 55' con 130'
    de fuerza: la fuerza se comía las horas de la natación que pidió en su cuaderno.
    Lo demás (caminar, yoga) no entra en ninguna de las dos.
    """
    from services.ai.load_state import _canonical_sport

    activities = getattr(garmin_data, "recent_activities", None) or []
    cutoff = datetime.now() - timedelta(days=days)
    segundos = {"resistencia": 0.0, "fuerza": 0.0}
    visto = False
    for act in activities:
        start = getattr(act, "start_time", None)
        summary = getattr(act, "summary", None)
        duration = getattr(summary, "duration", None) if summary else None
        if not start or duration is None:
            continue
        try:
            start_dt = datetime.fromisoformat(str(start).replace("Z", "+00:00")).replace(tzinfo=None)
        except ValueError:
            continue
        if start_dt < cutoff:
            continue
        visto = True
        deporte = _canonical_sport(getattr(act, "activity_type", None))
        if deporte in ("swim", "bike", "run"):
            segundos["resistencia"] += float(duration)
        elif deporte == "strength":
            segundos["fuerza"] += float(duration)
    if not visto:
        return None
    semanas = days / 7.0
    return {clave: round(s / 3600.0 / semanas, 1) for clave, s in segundos.items()}


def _last_week_planned_hours(payload: WeeklyPlanPayload | None) -> float | None:
    """Planned (not executed) hours of the most recent week already on file.

    None si esa semana acabo hace mas de 14 dias. Un atleta que vuelve tras un
    mes parado no tiene carga cronica medida, asi que la referencia de volumen
    caia en ESTE numero -- las horas PLANIFICADAS de una semana de hace meses,
    +10 %: a alguien desentrenado se le prescribia su pico antiguo. Auditoria
    previa al merge, 2026-09-11.
    """
    if payload is None or not payload.weeks:
        return None
    fechas = [d.date for d in payload.weeks[-1].days if d.date]
    try:
        ultima = max(datetime.strptime(f, "%Y-%m-%d").date() for f in fechas) if fechas else None
    except ValueError:
        ultima = None
    if ultima is not None and (date.today() - ultima).days > 14:
        return None
    minutes = sum(d.total_duration_minutes or 0 for d in payload.weeks[-1].days if not d.is_rest)
    return round(minutes / 60.0, 1) if minutes > 0 else None


def build_volume_baseline(profile: dict, garmin_data, previous_plan: WeeklyPlanPayload | None) -> dict:
    """The real numbers a weekly volume target has to be anchored to.

    Plan Maestro v4 Track F. Three separate quantities, deliberately kept
    apart because they mean different things and were previously conflated:

    - `chronic_weekly_hours`: what the athlete has actually been absorbing,
      averaged over 4 weeks of real Garmin activity. Stable enough not to be
      thrown off by one big week or one missed week -- this is the anchor
      progression should be judged against, not last week's number alone.
    - `previous_week_planned_hours`: what we prescribed most recently.
    There used to be a third, `available_hours_ceiling`: the hours the athlete
    typed into their profile. It is gone, and the athlete is no longer asked.
    It was inert anyway for exactly the reason it was written down here
    (14.25h declared vs ~6h actually trained, so no plan could ever trip it),
    and its default of 8.0 reached the prompt as a measured-looking fact for
    anyone who never touched the field.

    See compute_weekly_volume_target in weekly_planner_node.py for how these
    become an actual prescribed target for one specific week.
    """
    chronic_total = _compute_recent_actual_weekly_hours(garmin_data, days=CHRONIC_LOAD_WINDOW_DAYS)
    chronic_weeks = CHRONIC_LOAD_WINDOW_DAYS / 7.0

    # LA CARGA, que desde la v1.2 es la moneda del plan y no las horas. Ver
    # services/ai/weekly_load.py para por que, con los numeros del atleta.
    #
    # Dos fuentes y se guardan las dos: la de Garmin (media exponencial, la que
    # el atleta ve en su reloj, con el rango que Garmin considera optimo PARA
    # EL) y la nuestra (suma de la carga de cada actividad, 28 dias / 4), que
    # es la que existe aunque Garmin no haya calculado su estado ese dia.
    actividades = getattr(garmin_data, "recent_activities", None) or []
    hoy = date.today()
    # Una sola verdad de intensidad para las tasas y el reparto: umbral de
    # lactato, ritmo umbral y primer umbral de ritmo del atleta. build_prescription
    # es pura; volver a llamarla aquí no toca nada.
    zonas = getattr(garmin_data, "garmin_hr_zones", None)
    referencias = referencias_de_prescripcion(build_prescription(garmin_data, profile))
    ts = getattr(garmin_data, "training_status", None)
    carga_garmin = (getattr(ts, "acute_training_load", None) or {}) if ts else {}
    if not isinstance(carga_garmin, dict):
        carga_garmin = {}
    return {
        "chronic_weekly_hours": round(chronic_total / chronic_weeks, 1) if chronic_total else None,
        # Las mismas 4 semanas partidas: la fuerza no resta horas a la resistencia.
        "chronic_hours_by_kind": _horas_semanales_por_tipo(garmin_data, CHRONIC_LOAD_WINDOW_DAYS),
        "last_7d_actual_hours": _compute_recent_actual_weekly_hours(garmin_data, days=ACUTE_LOAD_WINDOW_DAYS),
        # Recorded so a reader (and the archived reasoning trail) can tell what
        # window the chronic average was actually taken over, instead of having
        # to trust that it matches what was fetched.
        "chronic_window_days": CHRONIC_LOAD_WINDOW_DAYS,
        "previous_week_planned_hours": _last_week_planned_hours(previous_plan),
        "chronic_weekly_load": carga_semanal_real(actividades, hoy, CHRONIC_LOAD_WINDOW_DAYS),
        "last_7d_load": carga_semanal_real(actividades, hoy, ACUTE_LOAD_WINDOW_DAYS),
        "garmin_acute_load": carga_garmin.get("acute_load"),
        "garmin_chronic_load": carga_garmin.get("chronic_load"),
        "garmin_optimal_min": carga_garmin.get("chronic_optimal_min"),
        "garmin_optimal_max": carga_garmin.get("chronic_optimal_max"),
        "load_rates": tasas_de_carga(
            actividades, hoy, dias=180, es_dura=lambda a: es_sesion_dura(a, zonas, referencias),
        ),
        # v1.2.2: cómo reparte su intensidad, POR SESIÓN, con 3 zonas ancladas a
        # sus umbrales y el ritmo cuando el pulso llega tarde. None sin zonas o
        # sin muestras. Ver services/ai/intensity_distribution.py.
        "intensity_distribution": medir_distribucion(actividades, zonas, hoy, referencias=referencias),
    }


def build_volume_reconciliation_note(profile: dict, garmin_data) -> str | None:
    """Self-reported current_weekly_volume_hours (a rough athlete guess) can
    diverge a lot from what Garmin's real activity history shows. When it
    does (>30%), surface it explicitly — both to the LLM (already told in
    build_custom_planning_context's profile section to prefer real data, but
    a concrete number makes the instruction harder to ignore) and to the
    athlete in the UI, instead of silently picking one number."""
    self_reported = profile.get("current_weekly_volume_hours")
    if not self_reported or self_reported <= 0:
        return None
    # Dos errores juntos, vistos en el macrociclo de la atleta 3 (2026-09-14):
    # "Reportaste 5.9h/semana ... tus actividades de los últimos 7 días suman
    # ~2.6h ... Se priorizó el dato real". Las 5,9 h NO eran autorreporte: el
    # alta las MIDE en Garmin sobre 12 semanas (`garmin_autofilled_fields`). Y
    # los 7 días eran una semana de viaje. El macrociclo arrancaba anclado a la
    # peor semana del trimestre, llamándola "el dato real".
    #
    # Medido contra medido no hay nada que conciliar: el planificador ya tiene
    # los hábitos de 12 semanas y la carga crónica. Declarado contra medido se
    # compara con cuatro semanas, no con una.
    if "current_weekly_volume_hours" in (profile.get("garmin_autofilled_fields") or []):
        return None
    total = _compute_recent_actual_weekly_hours(garmin_data, days=CHRONIC_LOAD_WINDOW_DAYS)
    if total is None:
        return None
    real_hours = round(total / (CHRONIC_LOAD_WINDOW_DAYS / 7.0), 1)
    diff_pct = abs(real_hours - self_reported) / self_reported
    if diff_pct < 0.30:
        return None
    direction = "más" if real_hours > self_reported else "menos"
    return (
        f"El atleta declaró {self_reported}h/semana en su perfil; sus actividades de Garmin de las últimas "
        f"{CHRONIC_LOAD_WINDOW_DAYS // 7} semanas promedian ~{real_hours}h/semana, {direction} de lo declarado. "
        f"Parte de lo medido."
    )


def extract_plan_text(raw) -> str:
    """
    Robustly extract a clean markdown string from any form the workflow may
    return the weekly/season plan in:
      - None
      - plain str
      - AgentOutput object  (.output is a str or a list of message-dicts)
      - dict  {"output": str | list, "content": str}
      - list of message dicts  [{"message": "...", ...}, ...]
    """
    if raw is None:
        return ""

    # Unwrap AgentOutput dataclass / pydantic model
    if hasattr(raw, "output"):
        raw = raw.output

    # Unwrap dict
    if isinstance(raw, dict):
        raw = raw.get("output") or raw.get("content") or raw

    # SeasonPlanContent.model_dump() shape ({"markdown": ..., "phases": [...],
    # "season_start_date": ...}) — pull the narrative text out, don't stringify the whole dict.
    if isinstance(raw, dict) and "markdown" in raw:
        raw = raw["markdown"]

    # If it's still a dict at this point, stringify
    if isinstance(raw, dict):
        return str(raw)

    # Plain string — done
    if isinstance(raw, str):
        return raw

    # List — could be a list of AgentOutput message dicts
    if isinstance(raw, list):
        parts = []
        for item in raw:
            # Each item might be a dict with a "message" key (LangGraph message format)
            if isinstance(item, dict):
                msg = item.get("message") or item.get("content") or item.get("text") or ""
                if msg:
                    parts.append(str(msg))
            elif hasattr(item, "message"):
                parts.append(str(item.message))
            elif hasattr(item, "content"):
                parts.append(str(item.content))
            else:
                parts.append(str(item))
        return "\n\n".join(p for p in parts if p.strip())

    return str(raw)


def extract_season_phases_dict(raw) -> dict | None:
    """Mirrors extract_plan_text's unwrapping, but pulls the structured
    {"phases": [...], "season_start_date": ...} out of a SeasonPlanContent
    result instead of the markdown — None for HITL questions, missing/failed
    generation, or a plain-string legacy season plan with no structure."""
    if raw is None:
        return None
    if hasattr(raw, "output"):
        raw = raw.output
    if isinstance(raw, dict):
        raw = raw.get("output", raw)
    if not isinstance(raw, dict) or "phases" not in raw or "season_start_date" not in raw:
        return None
    return {
        "phases": raw["phases"],
        "season_start_date": raw["season_start_date"],
        # v5 Track 3. Persisted alongside the phases rather than in their own
        # file: the weekly planner already loads this dict wholesale
        # (_load_season_phases), so the constraints reach it with no new
        # plumbing, and they can never end up out of sync with the phases they
        # belong to.
        "constraints": raw.get("constraints") or [],
    }


async def run_generation_task(profile_dict: dict):
    """Plan Maestro v4 Track B: this generates ONLY the season plan (macrocycle)
    now -- no weekly_planner, no daily sessions. The athlete sees phases, not
    a calendar, until they click "Generar Semana" (see run_week_generation_task
    below), which produces the next 7 days on demand against fresh data.

    This used to run the full analysis+season+weekly pipeline in one ~$1,
    ~10-minute call, asking the weekly_planner to write 28 days of step-level
    detail in one shot -- the single biggest driver of the "paso de respaldo"
    quality collapse this app's real generations kept hitting (see
    contexto/historico/auditoria_v4_contraste_estado_real.md and the Plan Maestro v4 Tracks
    A/C/D that preceded this one). Splitting generation into "macro once, week
    on demand" is the structural fix, not another validator patch."""
    global execution_status
    execution_status["running"] = True
    execution_status["progress"] = 10
    # En español, como todos los demás: este texto lo lee el atleta mientras
    # espera. Era la única línea en inglés de las seis, así que la temporada se
    # generaba en un idioma y la semana en otro.
    execution_status["status"] = "Extrayendo datos recientes de Garmin..."
    execution_status["error"] = None
    execution_status["result"] = None

    try:
        profile_name = profile_dict.get("name") or "default"
        storage_dir = _profile_storage_dir(profile_name)

        os.environ["AI_MODE"] = profile_dict.get("ai_mode") or DEFAULT_AI_MODE
        reload_config()
        ai_settings.reload()

        email = profile_dict.get("garmin_email")
        password = profile_dict.get("garmin_password")

        print(f"Connecting to Garmin Connect for {email}...")
        extractor = TriathlonCoachDataExtractor(email, password)

        # The season plan reasons about months of trend, so it keeps the long
        # extraction window (unlike run_week_generation_task below, which
        # deliberately uses a short, fresh one). The numbers come from
        # extraction_windows.py with their reasoning attached -- they used to
        # be two number inputs the athlete filled in by hand, which is a
        # technical decision dressed up as a preference. Any value saved on an
        # existing profile is deliberately ignored: it was a guess.
        extraction_config = ExtractionConfig(
            activities_range=SEASON_WINDOW.activities_days,
            metrics_range=SEASON_WINDOW.metrics_days,
            include_detailed_activities=True,
            include_metrics=True,
        )

        print("Extracting activities and metrics...")
        garmin_data = extractor.extract_data(extraction_config)
        print("Garmin extraction complete!")
        execution_status["progress"] = 40
        execution_status["status"] = "Generando plan de temporada con IA..."

        custom_planning_context = build_custom_planning_context(profile_dict)
        analysis_context = profile_dict.get("analysis_context", "")

        volume_reconciliation_note = build_volume_reconciliation_note(profile_dict, garmin_data)
        if volume_reconciliation_note:
            print(f"⚠️ {volume_reconciliation_note}")
            custom_planning_context += f"\n\n[VOLUMEN DECLARADO FRENTE A MEDIDO]\n{volume_reconciliation_note}"

        now = datetime.now()
        current_date = {
            "date": now.strftime("%Y-%m-%d"),
            "day_name": now.strftime("%A"),
            "day_name_es": _DAY_ES.get(now.strftime("%A"), now.strftime("%A")),
        }

        raw_competitions = profile_dict.get("competitions", [])
        competitions = solo_las_que_vienen([normalizar_competicion(c if isinstance(c, dict) else vars(c))
                                            for c in raw_competitions])

        # v5 Track 2: the season planner used to receive only name, date,
        # competitions and expert prose. It set a weekly hours target per phase
        # having never seen what the athlete actually trains, and weighed race
        # goals having never seen a predicted time. All of this already existed
        # for the weekly path; none of it had a wire to here.
        season_availability = build_availability_constraints(profile_dict)
        # Beta 2.1 (S7): para qué hace gimnasio y si ya tiene programa confirmado.
        # Sin nada contestado, el prompt del macrociclo no cambia (perfil_de_fuerza).
        season_availability["fuerza"] = {
            "strength_profile": profile_dict.get("strength_profile"),
            "strength_routine": profile_dict.get("strength_routine"),
        }
        existing_payload = _load_plan_for_adherence(storage_dir)
        season_availability["volume_baseline"] = build_volume_baseline(
            profile_dict, garmin_data, existing_payload,
        )
        # Las tasas de carga calibradas con 180 dias se guardan para que la
        # semana, que solo baja 30, no recalibre con cuatro salidas en bici.
        guardar_tasas(storage_dir, season_availability["volume_baseline"].get("load_rates") or {})
        # v1.2. El estado de carga -- aguda, cronica, el rango optimo de Garmin
        # y sobre todo el FOCO de carga (aerobico bajo / alto / anaerobico
        # contra sus objetivos) -- solo lo veia la semana. Asi el macrociclo
        # fijaba el enfasis de cada fase sin saber que al atleta le faltaba
        # trabajo aerobico alto (AEROBIC_HIGH_SHORTAGE, 227 contra 275-538) y le
        # sobraba del bajo (771 contra 205-468): exactamente lo que decide de
        # que tiene que estar hecha una fase.
        season_availability["load_state"] = load_state_to_state(
            build_load_state(garmin_data, today=date.today().isoformat())
        )
        # Como respondio el cuerpo a sus sesiones mas exigentes: el cruce que
        # un entrenador hace y que ningun experto podia hacer por estar
        # partidos por dominio. Ver services/ai/training_response.py.
        season_availability["training_response"] = {"block": build_training_response(garmin_data)}
        # v1.2.2: la deriva cardíaca de sus sesiones largas y sostenidas, para
        # el experto de fisiología. Es una señal de TEMPORADA: seis meses del
        # atleta 2 dieron 3 sesiones válidas de 24. Ver
        # services/ai/aerobic_decoupling.py.
        season_availability["decoupling"] = {"block": render_deriva(medir_deriva(
            getattr(garmin_data, "recent_activities", None), getattr(garmin_data, "garmin_hr_zones", None),
        ))}
        # v7: el cuaderno tambien al macrociclo. Guarda hechos DURADEROS, y
        # duradero es la escala de este nodo: una medicacion, un horario fijo,
        # una piscina que cierra en agosto o una operacion en noviembre
        # cambian la forma de las 16 semanas, no la tactica de una.
        season_availability["notebook"] = {
            "block": render_notebook_block(storage_dir),
            # Lo que pide de su entrenamiento, como petición y no como algo que
            # contrastar con Garmin: igual que la semana (ver
            # notebook._COMO_SE_LEE_UNA_PETICION, atleta 2 el 2026-09-23).
            "peticiones": render_peticiones_de_entrenamiento(storage_dir, para="macrociclo"),
            "block_sin_peticiones": render_notebook_block(storage_dir, sin_peticiones=True),
        }
        # El expediente: que le prescribimos y cuanto cumplio, semana a
        # semana. La adherencia normal solo mide contra weekly_plan.json, que
        # desde la generacion semana a semana es UNA semana -- el macrociclo
        # decidia dieciseis semanas mirando siete dias.
        season_availability["track_record"] = {"block": render_track_record(storage_dir)}
        # v1.2.2: y cómo respondió a cada fase cerrada -- lo planificado, lo
        # hecho y cómo se movieron sus marcadores. Es lo que deja al macrociclo
        # aprender qué reparto le funciona a ESTE atleta. "" sin fases medidas.
        # Ver services/ai/respuesta_a_las_fases.py.
        season_availability["phase_response"] = {"block": render_respuesta_a_las_fases(storage_dir)}

        # El macrociclo que este plan REEMPLAZA. La adherencia de arriba dice
        # qué se le prescribió y qué cumplió, pero no de qué plan salían esas
        # prescripciones: el planificador escribía el macrociclo dos sin saber
        # en qué fase iba el atleta ni qué recomendó el anterior. Ver
        # services/ai/previous_season.py.
        #
        # Se lee AQUÍ, antes de que la generación sobrescriba esos ficheros más
        # abajo: en este punto todavía contienen el plan que se va a reemplazar.
        plan_viejo = storage_dir / "season_plan.md"
        season_availability["previous_season"] = {"block": render_previous_season(
            plan_viejo.read_text(encoding="utf-8") if plan_viejo.is_file() else "",
            _read_json_if_exists(storage_dir / "season_phases.json"),
        )}

        # v7.2. La forma de la semana habitual del atleta. El nodo del
        # macrociclo la pedia desde que se escribio (`{training_habits}` en su
        # plantilla) y SIEMPRE la recibia vacia, porque solo la construia la
        # generacion de semana. Asi que el plan de temporada -- el que decide
        # las horas de cada fase -- se escribia sin haber visto nunca que dias
        # entrena este atleta ni de que.
        #
        # Aqui es ademas donde MEJOR se puede leer: esta generacion baja 180
        # dias de actividades, frente a los 30 de la semana. Se guarda para que
        # las semanas la reusen sin volver a pedirle nada a Garmin.
        habitos_temporada = build_training_habits(
            garmin_data, profile_dict, dias_de_datos=SEASON_WINDOW.activities_days,
        )
        season_availability["habits"] = {"block": render_training_habits(habitos_temporada)}
        guardar_habitos(storage_dir, habitos_temporada)
        if habitos_temporada.dias_de_calidad:
            print(f"Dias de calidad habituales: {', '.join(habitos_temporada.dias_de_calidad)}")

        season_prescription = build_prescription(garmin_data, profile_dict)
        season_prescription_state = prescription_to_state(season_prescription)

        season_adherence_state = None
        if existing_payload is not None:
            season_adherence_report = build_adherence_report(
                existing_payload, getattr(garmin_data, "recent_activities", None),
                subidas=upload_ledger.cargar(storage_dir),
                # Lo que el reloj aún no subió no cuenta como no hecho (2026-10-19).
                ultima_sincronizacion=getattr(garmin_data, "ultima_sincronizacion", None),
                # Las zonas REALES del reloj, para poder distinguir una sesion
                # de calidad hecha de una hecha en facil. Sin ellas la
                # comprobacion se abstiene, que es lo correcto: no saber no es
                # lo mismo que saber que estuvo mal.
                hr_zones=getattr(garmin_data, "garmin_hr_zones", None),
                # Y su ritmo umbral: una sesion de calidad con el pulso por
                # debajo de Z4 no es "sin intensidad" si el ritmo si llego.
                umbral_ritmo_s_km=season_prescription.threshold_pace_sec_per_km,
            )
            if season_adherence_report.evaluated:
                season_adherence_state = season_adherence_report.to_state_dict()
                season_adherence_state["prompt_block"] = render_adherence_block(season_adherence_report)

        goal_assessments = assess_goals(
            competitions, season_prescription_state.get("race_predictions") or [],
            pace_bands=season_prescription_state.get("pace_bands"),
        )
        # v6 Track 4: how much the predictions above are worth believing --
        # how many activities, over what span, whether the threshold pace was
        # measured or derived, what the athlete says about themselves. Handed
        # over as evidence for the season planner to weigh, never folded into
        # a score: deciding how much a thin data set should widen the
        # uncertainty around a goal is a judgement about this athlete.
        season_availability["goal_evidence"] = build_goal_evidence(
            season_prescription_state, garmin_data, profile_dict,
        )
        for assessment in goal_assessments:
            print(
                f"Meta '{assessment['name']}': {assessment['target_clock']} vs prediccion "
                f"{assessment.get('predicted_clock', 'n/d')} -> {assessment['verdict']}"
                + (" (distancia deducida)" if assessment.get("distance_source") == "deducida" else "")
            )
        for race in competitions_missing_distance(competitions):
            print(
                f"⚠️ '{race}' tiene tiempo objetivo pero no se puede saber su distancia. "
                "Ponle la distancia en el perfil para que la meta se pueda evaluar."
            )

        chronic = season_availability["volume_baseline"].get("chronic_weekly_hours")
        print(
            f"Volumen cronico real: {chronic if chronic is not None else 'sin datos'} h/sem"
        )

        print(f"Running analysis + season planning workflow with {len(competitions)} competitions...")
        result = await run_analysis_and_season_planning(
            user_id=profile_name,
            athlete_name=profile_dict.get("name", "Athlete"),
            garmin_data=garmin_data_to_dict(garmin_data),
            analysis_context=analysis_context,
            planning_context=custom_planning_context,
            competitions=competitions,
            current_date=current_date,
            plotting_enabled=False,
            hitl_enabled=False,
            skip_synthesis=False,
            availability_constraints=season_availability,
            prescription=season_prescription_state,
            adherence=season_adherence_state,
            on_progress=_make_generation_progress_callback(0),
        )

        # LO PRIMERO, antes que cualquier cosa que pueda fallar: el dinero ya
        # se gastó. Ver anotar_gasto.
        anotar_gasto(profile_dict, usage_ledger.KIND_SEASON, result)

        execution_status["progress"] = 90
        execution_status["status"] = "Guardando resultados..."

        workflow_errors = result.get("errors", [])
        for err in workflow_errors:
            print(f"⚠️ Workflow warning: {err}")

        season_plan_raw = result.get("season_plan")
        season_plan_md = extract_plan_text(season_plan_raw)
        if not season_plan_md:
            # Antes se escribia "No se pudo generar el plan de temporada." como
            # si fuera el plan, se devolvia exito y MySQL guardaba ese texto
            # junto a las fases del macro ANTERIOR -- y la semana se podia
            # generar contra eso. La ruta de la semana ya fallaba de frente; la
            # del macro, tambien. Auditoria previa al merge, 2026-09-11.
            raise RuntimeError(
                "El planificador de temporada no devolvio un plan. Tu macrociclo anterior se conserva; "
                "vuelve a intentarlo."
            )

        # Archived BEFORE overwriting, same as before -- but this call never
        # touches weekly_plan.json, so whatever week(s) the athlete already
        # had stay exactly as they were; only the season files are archived
        # and replaced.
        archived_id = archive_previous_generation(storage_dir)
        if archived_id:
            print(f"Plan anterior archivado en el historial como {archived_id}")

        season_plan_path = storage_dir / "season_plan.md"
        season_plan_path.write_text(season_plan_md, encoding="utf-8")

        season_phases = extract_season_phases_dict(season_plan_raw)
        if season_phases:
            (storage_dir / "season_phases.json").write_text(
                json.dumps(season_phases, indent=2, ensure_ascii=False), encoding="utf-8"
            )

        analysis_html = result.get("analysis_html") or ""

        import re as _re

        def strip_code_fence(text: str) -> str:
            """Remove ```html / ``` wrappers the LLM sometimes adds."""
            text = text.strip()
            text = _re.sub(r"^```(?:html)?\s*\n?", "", text)
            text = _re.sub(r"\n?```\s*$", "", text)
            return text.strip()

        if analysis_html:
            (storage_dir / "analysis.html").write_text(strip_code_fence(analysis_html), encoding="utf-8")

        cost_summary = result.get("cost_summary") or {}
        cost_total = float(cost_summary.get("total_cost_usd", 0.0))
        total_tokens = int(cost_summary.get("total_tokens", 0))

        # summary.json is the athlete's CURRENT COMBINED state (season +
        # whatever week was last generated) -- this call only owns the
        # season half, so anything run_week_generation_task previously wrote
        # (weekly_plan, plan_validation, adherence, prescription) is carried
        # over from disk instead of being wiped by this write.
        summary = {
            **(_read_json_if_exists(storage_dir / "summary.json") or {}),
            "athlete": profile_dict.get("name"),
            "analysis_date": datetime.now().isoformat(),
            "total_cost_usd": cost_total,
            "total_tokens": total_tokens,
            "cost_breakdown": cost_summary.get("agents", []),
            "generation_kind": "season",
            "season_plan": season_plan_md,
            "volume_reconciliation_note": volume_reconciliation_note,
            "goal_realism": goal_assessments,
            "volume_baseline": season_availability.get("volume_baseline"),
        }

        (storage_dir / "summary.json").write_text(json.dumps(summary, indent=2, ensure_ascii=False), encoding="utf-8")
        # El libro de costos, que NO vive en storage_dir, ya se anotó arriba en
        # cuanto el flujo devolvió: summary.json lo pisa la corrida siguiente y
        # rmtree lo borra entero, y una corrida que muere a mitad no llega
        # hasta aquí. Ver anotar_gasto y core/usage_ledger.py.

        # Everything the agents thought on the way here -- summarizer views,
        # the nine expert payloads, the synthesis markdown. It was paid for
        # with real credits and used to be discarded when the process exited.
        reasoning_id = save_reasoning(
            storage_dir, result, kind="season",
            extra={
                "competitions": competitions,
                "planning_context": custom_planning_context,
                "health_flags": health_flags(profile_dict),
                "goal_realism": goal_assessments,
                "volume_baseline": season_availability.get("volume_baseline"),
                "prescription": season_prescription_state,
                # Las series largas que Garmin YA trajo en esta extraccion:
                # el arco de VO2max de un ano y la HRV/pulso en reposo dia a
                # dia. Se extraian, se resumian en prosa y se tiraban; ahora
                # se archivan para que las graficas tengan historia desde la
                # primera generacion. Cero llamadas extra a Garmin.
                "fitness_series": build_fitness_series(garmin_data, season_prescription_state),
            },
        )
        if reasoning_id:
            print(f"Razonamiento de los agentes archivado en reasoning/{reasoning_id}")
        # Y se tira lo que ya sobra. `save_reasoning` nunca ha borrado nada
        # ("Nothing is pruned", decia su docstring), asi que el archivo crecia
        # ~500 KB por generacion para siempre. Ver TRAZAS_QUE_SE_CONSERVAN.
        podar_trazas(storage_dir)

        execution_status["progress"] = 100
        execution_status["status"] = "success"
        execution_status["result"] = summary
        print(f"Season plan generated successfully and saved to 'data/storage/{storage_dir.name}/season_plan.md'!")

    except Exception as e:
        logger.error(f"Error in generation task: {e}")
        traceback.print_exc()
        execution_status["progress"] = 100
        execution_status["status"] = "failed"
        # Si falló Garmin, en palabras del atleta; si no, el error tal cual.
        from services.garmin.client import explicar_error_de_garmin
        execution_status["error"] = explicar_error_de_garmin(e) or str(e)
    finally:
        execution_status["running"] = False


async def run_week_generation_task(
    profile_dict: dict, rehacer: bool = False, inicio: str | date | None = None, sin_checkin: bool = False,
    registro_de_fuerza: list | None = None, automatica: bool = False,
):
    """Plan Maestro v4 Track B: the "Generar Semana" button. Generates the
    next 7 days against a FRESH, short Garmin extraction (not the season
    plan's 90/56-day window), reusing the persisted season plan/phases
    instead of re-running season_planner, and appends the result onto
    weekly_plan.json instead of overwriting the whole thing.

    `inicio` es el día desde el que el atleta quiere que arranque la semana.
    Sólo tiene sentido en la PRIMERA generación, y por eso se ignora en cuanto
    hay una semana anterior: a partir de ahí la siguiente empieza donde acabó
    la última, y dejar elegir ahí sólo abriría huecos o solapes en su
    calendario.

    Existe porque "hoy" no es la respuesta correcta a todas horas. A las ocho
    de la tarde, escribirle entrenos para hoy es escribirle un día que ya se le
    fue; a las once de la mañana, después de su sesión, puede ser exactamente
    lo que quiere. Sólo el atleta sabe cuál de los dos es su caso."""
    global execution_status
    execution_status["running"] = True
    execution_status["progress"] = 10
    execution_status["status"] = "Extrayendo datos recientes de Garmin..."
    execution_status["error"] = None
    execution_status["result"] = None

    try:
        profile_name = profile_dict.get("name") or "default"
        storage_dir = _profile_storage_dir(profile_name)

        season_plan_path = storage_dir / "season_plan.md"
        if not season_plan_path.exists():
            raise ValueError(
                "Genera primero el plan de temporada (macrociclo) antes de generar una semana."
            )
        season_plan_text = season_plan_path.read_text(encoding="utf-8")

        os.environ["AI_MODE"] = profile_dict.get("ai_mode") or DEFAULT_AI_MODE
        reload_config()
        ai_settings.reload()

        email = profile_dict.get("garmin_email")
        password = profile_dict.get("garmin_password")

        print(f"Connecting to Garmin Connect for {email}...")
        extractor = TriathlonCoachDataExtractor(email, password)

        # Deliberately short: the whole point of generating week by week is
        # that each week sees near-current recovery/adherence state, not a
        # snapshot averaged over the season plan's long window. It still has to
        # COVER the chronic load window -- this used to fetch 21 days while
        # build_volume_baseline averaged "28 days" over them and divided by 4,
        # understating chronic weekly hours by up to 25% and biasing the volume
        # target of every week generated.
        extraction_config = ExtractionConfig(
            activities_range=WEEK_WINDOW.activities_days,
            metrics_range=WEEK_WINDOW.metrics_days,
            include_detailed_activities=True,
            include_metrics=True,
        )

        print("Extracting recent activities and metrics...")
        garmin_data = extractor.extract_data(extraction_config)
        print("Garmin extraction complete!")
        execution_status["progress"] = 30
        execution_status["status"] = "Generando la semana con IA..."

        # El contexto se construye más abajo, cuando se conocen las fechas de la
        # semana y si es de afinamiento. Aquí sólo se juntan sus añadidos.
        extras_del_contexto: list[str] = []
        availability_constraints = build_availability_constraints(profile_dict)

        training_status = getattr(garmin_data, "training_status", None)
        acute_training_load = getattr(training_status, "acute_training_load", None) if training_status else None
        current_acwr = acute_training_load.get("acwr") if acute_training_load else None
        # Ya no viaja al validador: la regla que lo leía (>1,5 recorta la
        # semana 1) se retiró el 2026-09-14. Ver plan_validator.py.
        if current_acwr is not None:
            print(f"ACWR actual (Garmin): {current_acwr:.2f}")

        # v6 Track 2. Until now `current_acwr` above was read ONLY by the
        # validator's guardrail -- the planner itself never saw a single load
        # or readiness number, just the metrics expert's prose about them, and
        # wrote a rationale quoting a race-day ACWR as if it were today's.
        # This block carries them all, each with its source and its date, and
        # deliberately reports BOTH ACWRs (Garmin's exponential one and this
        # app's rolling one) rather than choosing between them. See
        # services/ai/load_state.py.
        load_state = build_load_state(garmin_data, today=date.today().isoformat())
        availability_constraints["load_state"] = load_state_to_state(load_state)
        availability_constraints["training_response"] = {"block": build_training_response(garmin_data)}
        if load_state.top_efforts:
            hardest = load_state.top_efforts[0]
            print(
                f"Esfuerzo mas exigente de los ultimos {load_state.window_days} dias: "
                f"{hardest['date']} ({hardest.get('name') or hardest.get('activity_type')}), "
                f"carga {hardest.get('load')}"
            )

        # v7.2. La forma de la semana habitual del atleta: que dias entrena, de
        # que, cuantas calidades y en que dia caen. No la miraba nadie -- el
        # experto de actividad tiene prohibido hablar de carga global y el de
        # metricas ve ACWR y CTL, que son un numero por dia sin forma semanal.
        # Un atleta con calidad todos los miercoles desde hace meses recibio una
        # semana entera en Z1/Z2. Ver services/ai/training_habits.py.
        # Primero el retrato largo que dejo el macrociclo: son 180 dias de
        # actividades contra los 30 que baja esta generacion, y leer un habito
        # de doce semanas en cuatro no se puede. Si no hay o esta viejo, se
        # calcula con lo que hay aqui.
        #
        # La ventana de calculo sale SIEMPRE de la misma constante que la de
        # extraccion. Sin eso se calculaban 12 semanas sobre 30 dias y las
        # siete semanas y media que nunca se bajaron entraban como ceros:
        # "0.0 ses/sem" de todo, cuatro lineas por encima de "carga cronica
        # 5.0 h/sem", dentro del mismo prompt.
        habits = build_training_habits(
            garmin_data, profile_dict, dias_de_datos=WEEK_WINDOW.activities_days,
        )
        bloque_habitos = cargar_habitos(storage_dir) or render_training_habits(habits)
        availability_constraints["habits"] = {"block": bloque_habitos}
        if habits.dias_de_calidad:
            print(f"Dias de calidad habituales: {', '.join(habits.dias_de_calidad)}")
        for c in habits.contraste_declarado:
            print(f"Declarado vs hecho: {c}")
        # Sus «sí o sí» que casi no hace: la pantalla se lo enseña, sin IA (2026-10-23).
        poco_hechos = fijos_poco_hechos(habits, profile_dict)
        if poco_hechos:
            print("«Sí o sí» que casi no hace: " + "; ".join(
                f"{f['deporte']} el {f['dia']} ({f['veces']} de {f['semanas']})" for f in poco_hechos))

        prescription = build_prescription(garmin_data, profile_dict)
        prescription_state = prescription_to_state(prescription)
        if prescription.threshold_pace_sec_per_km:
            print(
                f"Ritmo umbral: {format_pace(prescription.threshold_pace_sec_per_km)}/km "
                f"({prescription.threshold_pace_source}) | zonas FC: {prescription.hr_zones_source}"
            )
        for warning in prescription.warnings:
            print(f"⚠️ Prescripción: {warning}")

        # Read now, before this task overwrites weekly_plan.json -- used for
        # BOTH adherence (what the athlete actually did with it) and to know
        # where the new week should start (the day after the last date it
        # already has).
        existing_payload = _load_plan_for_adherence(storage_dir)

        # Plan Maestro v4 Track F: the real numbers behind this week's volume
        # target. Threaded through availability_constraints -- the same
        # side-channel that already carries the prescription/ACWR/competitions
        # -- because weekly_planner_node is where the season phase (and thus
        # whether this is a deload week) is known.
        availability_constraints["volume_baseline"] = build_volume_baseline(
            profile_dict, garmin_data, existing_payload,
        )
        base = availability_constraints["volume_baseline"]
        base["load_rates"] = combinar_con_guardadas(storage_dir, base.get("load_rates") or {})

        # Plan Maestro v4 Track E: HRV/RHR as real numbers against this
        # athlete's OWN Garmin baseline. Previously these reached the planner
        # only as a sentence from the physiology expert, which is why it kept
        # inventing absolute HRV cutoffs nobody had computed for this athlete.
        recovery = build_recovery_snapshot(garmin_data)
        availability_constraints["recovery"] = recovery_to_state(recovery)
        # La deriva, también en la semana. En 30 días casi nunca hay una sesión
        # válida y entonces el bloque es "": ver aerobic_decoupling.
        availability_constraints["decoupling"] = {"block": render_deriva(medir_deriva(
            getattr(garmin_data, "recent_activities", None), getattr(garmin_data, "garmin_hr_zones", None),
        ))}

        # El mismo bloque de edad/sexo que ya va en `planning_context`, puesto
        # tambien en el canal lateral porque el experto de FISIOLOGIA vive en la
        # rama de analisis y no recibe `planning_context`: lo unico de texto que
        # le llega es `analysis_context`, que es el campo libre que escribe el
        # atleta. Es el nodo que mas lo necesita -- juzga la recuperacion.
        availability_constraints["athlete_basics"] = render_basics_block(profile_dict)

        # v5 Track 6: the athlete's own words, answered right before this run.
        # Optional -- skipping the form leaves this empty and changes nothing.
        # Read against their OWN previous answers, never a population threshold,
        # and with no automatic guardrail behind it (see services/ai/checkin.py).
        checkin_history = load_checkins(storage_dir)
        # De los ultimos 2 dias o ninguno: un check-in de hace semanas no es
        # "como llega hoy". Ver latest_checkin.
        #
        # `sin_checkin`: el atleta pulsó "Saltar". Entonces NO se usa ninguno.
        # Antes se usaba igual el de los dos últimos días, así que la nota de la
        # semana, el viernes sin entrenar y el sábado abierto de Jessie
        # (2026-09-15) seguían valiendo para la generación siguiente aunque no
        # los hubiera vuelto a contestar. Rehacer no pregunta y sigue usándolo:
        # es la misma semana.
        latest = None if sin_checkin else latest_checkin(storage_dir, max_age_days=2)
        if sin_checkin:
            print("El atleta saltó el check-in: esta semana se genera sin ninguno")
        if automatica:
            extras_del_contexto.append(NOTA_DE_SEMANA_AUTOMATICA)
            print("Semana automática: sin preguntas del coach ni check-in")
        # Lo que ya contesto al generar la semana anterior, para que el coach no
        # repita las mismas preguntas siete dias despues.
        availability_constraints["respuestas_previas"] = {
            "block": render_respuestas_previas(existing_payload),
        }
        availability_constraints["checkin"] = checkin_to_state(latest, checkin_history)
        # Los días que el atleta marcó "esta semana no puedo" (Database/022,
        # pedido por Fernando el 2026-09-14). Van a la disponibilidad que
        # comprueba el validador y a las reglas de la semana, no sólo al texto
        # del check-in: un texto libre el validador no lo lee.
        from services.ai.checkin import dias_sin_entrenar, render_dias_sin_entrenar
        sin_entrenar = dias_sin_entrenar(latest)
        if sin_entrenar:
            availability_constraints["unavailable_dates"] = sin_entrenar
            extras_del_contexto.append(render_dias_sin_entrenar(sin_entrenar))
            print(f"Días sin entrenar esta semana (check-in): {', '.join(sin_entrenar)}")
        # Y los que su tabla no le da y esta semana SÍ puede (Database/024, Jessie
        # 2026-09-15). Entran más abajo, cuando ya se sabe qué fechas tiene la semana.
        from services.ai.checkin import dias_abiertos, render_dias_abiertos
        abiertos = dias_abiertos(latest)
        # v7 Track 7: el cuaderno del coach. Lo que el atleta le conto en el
        # chat, con fecha, entrando al PLAN y no solo a la conversacion -- que
        # es lo unico que convierte recordar en cambiar algo.
        notebook_block = render_notebook_block(storage_dir)
        availability_constraints["notebook"] = {
            "block": notebook_block,
            # La decisión semanal recibe las «Preferencias de entrenamiento» como
            # petición, junto a lo que se respeta tal cual, y el resto del cuaderno
            # sin ellas (Fernando, 2026-09-22: ver notebook.render_peticiones_de_entrenamiento).
            "peticiones": render_peticiones_de_entrenamiento(storage_dir),
            "block_sin_peticiones": render_notebook_block(storage_dir, sin_peticiones=True),
        }
        if notebook_block:
            vigentes = len(notebook_active(storage_dir))
            print(f"Cuaderno del entrenador: {vigentes} anotacion(es) vigente(s) en el prompt")
        if availability_constraints["checkin"]["available"]:
            print(
                f"Check-in del atleta ({latest.get('date')}): Hooper "
                f"{latest.get('hooper_total')}/28"
                + (f", molestia: {latest['niggle_location']}" if latest.get("niggle_location") else "")
            )
        # v6 Track 3: the check-in niggle and the health record's declared
        # injuries, collected into one constraint the planner cannot answer
        # for the wrong body part. Both were already reaching the prompt --
        # separately, in two places, saying it two different ways.
        declared_niggles = collect_niggles(latest, profile_dict)
        availability_constraints["niggles"] = niggles_to_state(declared_niggles)
        for niggle in declared_niggles:
            print(f"Molestia declarada ({niggle['source']}): {niggle['location']}")

        if recovery.hrv_status:
            print(f"Estado de recuperacion (HRV Garmin): {recovery.hrv_status}")
        for warning in recovery.warnings:
            print(f"⚠️ Recuperación: {warning}")

        adherence_state = None
        # SOLO la semana que termina. El plan guarda hasta 12 semanas y la
        # semana se genera con 30 dias de actividades: medir todas contra ese
        # mes marcaba "no realizada" cada sesion de hace mas de 30 dias (desde
        # la semana 5, un atleta que lo hizo todo salia al ~50 %), y el
        # expediente cerraba cada semana guardada con los totales de TODAS.
        # Encontrado en la auditoria previa al merge, 2026-09-11.
        semana_que_termina = semana_a_cerrar(existing_payload, rehacer=rehacer)
        if semana_que_termina is not None:
            adherence_report = build_adherence_report(
                semana_que_termina,
                getattr(garmin_data, "recent_activities", None),
                hr_zones=getattr(garmin_data, "garmin_hr_zones", None),
                umbral_ritmo_s_km=prescription.threshold_pace_sec_per_km,
                # Lo hecho con el entreno del reloj se empareja por workoutId.
                subidas=upload_ledger.cargar(storage_dir),
                # Lo que el reloj aún no subió no cuenta como no hecho (2026-10-19).
                ultima_sincronizacion=getattr(garmin_data, "ultima_sincronizacion", None),
            )
            if adherence_report.evaluated:
                adherence_state = adherence_report.to_state_dict()
                adherence_state["prompt_block"] = render_adherence_block(adherence_report)
                print(
                    f"Adherencia al plan anterior: {adherence_report.adherence_pct:.0f}% "
                    f"({adherence_report.completed_count} completadas, "
                    f"{adherence_report.modified_count} modificadas, "
                    f"{adherence_report.under_intensity_count} sin la intensidad prescrita, "
                    f"{adherence_report.missed_count} no realizadas)"
                )

        now = datetime.now()
        current_date = {
            "date": now.strftime("%Y-%m-%d"),
            "day_name": now.strftime("%A"),
            "day_name_es": _DAY_ES.get(now.strftime("%A"), now.strftime("%A")),
        }

        # Rehacer significa volver a escribir la ultima semana EN SU SITIO,
        # con los datos de hoy y el ajuste que pidio el atleta. Si no hay
        # ninguna todavia, rehacer y generar son lo mismo.
        inicio_ultima = inicio_de_la_ultima_semana(existing_payload) if rehacer else None
        # La misma función que usa el tope de versiones para saber qué semana se paga.
        start_date = semana_objetivo(existing_payload, now, inicio, rehacer)
        if inicio_ultima:
            print(f"Rehaciendo la semana del {inicio_ultima} en su sitio (no se anade otra)")
        # Rehacer no es continuar. Sin esto, el bloque "Plan anterior" del
        # prompt le presentaba al modelo la semana que iba a REEMPLAZAR bajo
        # el encabezado "lo que YA le dosificaste" y con la instruccion "no
        # repitas la misma semana" -- asi que la cambiaba entera. Ver
        # _format_previous_plan en weekly_planner_node.py.
        availability_constraints["rehacer_semana"] = bool(inicio_ultima)
        if inicio_ultima:
            # Rehacer reescribe la misma semana, con los mismos dias que tenia.
            n_dias = len({d.date for d in existing_payload.weeks[-1].days if d.date}) or 7
        else:
            n_dias = dias_del_bloque(start_date, profile_dict.get("week_start_day"))
        week_dates = [
            {
                "date": (start_date + timedelta(days=offset)).strftime("%Y-%m-%d"),
                "day_name": (start_date + timedelta(days=offset)).strftime("%A"),
                "day_name_es": _DAY_ES.get(
                    (start_date + timedelta(days=offset)).strftime("%A"),
                    (start_date + timedelta(days=offset)).strftime("%A"),
                ),
            }
            for offset in range(n_dias)
        ]
        availability_constraints["week_start_day"] = profile_dict.get("week_start_day") or ""
        # Beta 2.1 (S5): cómo entrena la fuerza y su programa confirmado. Sin una
        # rutina confirmada esto no cambia NADA: el prompt de la decisión queda
        # igual y el codificador sigue escribiendo la fuerza como hasta hoy.
        # Ver services/ai/langgraph/nodes/paso_de_fuerza.py.
        # Lo que su historial respalda y la semana no ve sola (services/ai/carga_segura.py):
        # su carrera más larga de 30 días, contra la que se mide un pico de sesión.
        from services.ai.carga_segura import historial_de_carga
        availability_constraints["historial_de_carga"] = historial_de_carga(
            getattr(garmin_data, "recent_activities", None))
        availability_constraints["fuerza"] = {
            "strength_profile": profile_dict.get("strength_profile"),
            "strength_routine": profile_dict.get("strength_routine"),
            # Lo que levantó de verdad, por sesión (Database/026). Vacío mientras no
            # registre nada, y entonces la fuerza va como hasta ahora.
            "logs": registro_de_fuerza or [],
        }

        raw_competitions = profile_dict.get("competitions", [])
        # Desde el inicio de ESTA semana y tan atrás como mira la nota de después de una
        # carrera (carrera_reciente), no tres días desde hoy. Ver solo_las_que_vienen.
        from services.ai.langgraph.nodes.weekly_planner_node import _VENTANA_DE_RECUPERACION_MAX
        competitions = solo_las_que_vienen([normalizar_competicion(c if isinstance(c, dict) else vars(c))
                                            for c in raw_competitions],
                                           hoy=date.fromisoformat(week_dates[0]["date"]),
                                           dias_atras=_VENTANA_DE_RECUPERACION_MAX)

        # El contexto se escribe AQUÍ, cuando ya se sabe qué semana es: en el
        # afinamiento de una carrera, las reglas de los FIJO dicen que ceden. Lo
        # que se le añadió antes (los días sin entrenar del check-in) va detrás.
        from services.ai.langgraph.nodes.weekly_planner_node import carrera_en_afinamiento
        afinamiento = carrera_en_afinamiento(competitions, week_dates)
        fechas_de_la_semana = {d["date"] for d in week_dates}
        abiertos = {f: deportes for f, deportes in abiertos.items() if f in fechas_de_la_semana}
        if abiertos:
            # En claves (`run`, `bike`…), que es lo que compara el validador.
            availability_constraints["open_dates"] = {
                f: _canonical_sports_for_day(deportes) or [] for f, deportes in abiertos.items()}
            extras_del_contexto.append(render_dias_abiertos(abiertos))
            print(f"Días que abre esta semana (check-in): {', '.join(abiertos)}")
        # El día de una carrera está disponible para ella aunque su tabla lo tenga de
        # descanso (Hugo, 2026-10-09). Lo que ESTA semana dijo que no puede, gana.
        from services.ai.langgraph.validation.plan_validator import dias_de_carrera
        cerradas = set(availability_constraints.get("unavailable_dates") or [])
        availability_constraints["race_dates"] = {
            f: d for f, d in dias_de_carrera(competitions, fechas_de_la_semana).items() if f not in cerradas}
        carreras_de_la_semana = describir_carreras_de_la_semana(competitions, fechas_de_la_semana, cerradas)
        if carreras_de_la_semana:
            print(f"Días de carrera esta semana (disponibles para la carrera): {', '.join(carreras_de_la_semana)}")
        custom_planning_context = build_custom_planning_context(
            profile_dict, afinamiento, abiertos, referencia=date.fromisoformat(week_dates[0]["date"]),
            carreras=carreras_de_la_semana) + "".join(
            f"\n\n{extra}" for extra in extras_del_contexto)
        if afinamiento:
            print(f"Semana de afinamiento de {afinamiento['name']}: los FIJO cuentan como POSIBLE")

        print(f"Generating week starting {week_dates[0]['date']} with {len(competitions)} competitions...")
        result = await run_week_generation(
            user_id=profile_name,
            athlete_name=profile_dict.get("name", "Athlete"),
            garmin_data=garmin_data_to_dict(garmin_data),
            planning_context=custom_planning_context,
            competitions=competitions,
            current_date=current_date,
            week_dates=week_dates,
            availability_constraints=availability_constraints,
            prescription=prescription_state,
            adherence=adherence_state,
            season_plan={"output": season_plan_text},
            on_progress=_make_generation_progress_callback(1),
        )

        # LO PRIMERO, antes que cualquier cosa que pueda fallar -- y esta ruta
        # falla: el `raise` de unas líneas más abajo corta la semana cuando el
        # flujo no devuelve un plan usable, y así se perdieron dos apuntes de
        # $0.1153 cada uno el 2026-09-10. Ver anotar_gasto.
        # La semana automática se marca (`detail`): el panel la cuenta aparte, porque no
        # la pidió nadie con un clic y el tope de generaciones no la ve.
        anotar_gasto(profile_dict, usage_ledger.KIND_WEEK, result,
                     detail=usage_ledger.DETALLE_AUTOMATICA if automatica else None)

        execution_status["progress"] = 90
        execution_status["status"] = "Guardando resultados..."

        workflow_errors = result.get("errors", [])
        for err in workflow_errors:
            print(f"⚠️ Workflow warning: {err}")

        weekly_plan_raw = result.get("weekly_plan")
        new_week_payload = None
        respondido_hoy = [qa for qa in (result.get("respuestas_al_coach") or []) if qa.get("respuesta")]
        weekly_output = getattr(weekly_plan_raw, "output", None)
        if isinstance(weekly_output, WeeklyPlanPayload):
            new_week_payload = weekly_output

        if new_week_payload is None:
            # `print` + "revisa la consola" era una via muerta. `sys.stdout`
            # esta redirigido a LOG_LIST (un buffer en memoria que nadie sirve
            # desde el AiService), asi que la unica pista del fallo moria con
            # el proceso: una generacion pagada perdida sin dejar rastro. El
            # motivo real va ahora al LOG, que es lo unico que sobrevive.
            detalle = " | ".join(str(e) for e in workflow_errors) or "el flujo no devolvio ningun error con nombre"
            logger.error("La semana no se pudo generar. Errores del flujo: %s", detalle)
            raise ValueError(f"No se pudo generar la semana: {detalle[:300]}")

        # Lo que contesto al generar ESTA semana, guardado con ella: asi la
        # siguiente generacion no le vuelve a preguntar lo mismo, y se va
        # cuando la semana se borra. Ver WeekBlock.coach_answers.
        if respondido_hoy and new_week_payload.weeks:
            new_week_payload.weeks[-1].coach_answers = respondido_hoy

        merged_payload = (_replace_last_week if inicio_ultima else _append_week)(
            existing_payload, new_week_payload)

        # v6 Track 3, reported and never enforced. It answers only the question
        # the athlete asked implicitly by filling the field in -- "did anybody
        # write anything about the thing I said hurts?" -- and says so in the
        # log. Judging WHICH days should carry it is a coaching call, not a
        # string match, so a mention on any day counts.
        if declared_niggles and new_week_payload.weeks:
            missing = unaddressed_niggles(
                declared_niggles,
                [day.model_dump() for day in new_week_payload.weeks[-1].days],
            )
            for location in missing:
                print(f"⚠️ La molestia declarada «{location}» no aparece en ninguna sesión de la semana.")

        archived_id = archive_previous_generation(storage_dir)
        if archived_id:
            print(f"Plan anterior archivado en el historial como {archived_id}")

        weekly_plan_path = storage_dir / "weekly_plan.json"
        weekly_plan_path.write_text(merged_payload.model_dump_json(indent=2), encoding="utf-8")

        planning_html = result.get("planning_html") or ""

        import re as _re

        def strip_code_fence(text: str) -> str:
            text = text.strip()
            text = _re.sub(r"^```(?:html)?\s*\n?", "", text)
            text = _re.sub(r"\n?```\s*$", "", text)
            return text.strip()

        if planning_html:
            (storage_dir / "planning.html").write_text(strip_code_fence(planning_html), encoding="utf-8")

        cost_summary = result.get("cost_summary") or {}
        cost_total = float(cost_summary.get("total_cost_usd", 0.0))
        total_tokens = int(cost_summary.get("total_tokens", 0))

        # Same merge pattern as run_generation_task: preserve whatever the
        # season generation wrote (season_plan) and overwrite only the
        # weekly-generation fields this call owns.
        summary = {
            **(_read_json_if_exists(storage_dir / "summary.json") or {}),
            "athlete": profile_dict.get("name"),
            "analysis_date": datetime.now().isoformat(),
            "total_cost_usd": cost_total,
            "total_tokens": total_tokens,
            "cost_breakdown": cost_summary.get("agents", []),
            "generation_kind": "week",
            "weekly_plan": merged_payload.model_dump(),
            "plan_validation": result.get("weekly_plan_validation"),
            "prescription": prescription_state,
            # La base de carga de ESTA semana, reparto medido incluido. Sólo la
            # guardaba el macrociclo, así que el chat leía el reparto de hace
            # semanas o ninguno.
            "volume_baseline": availability_constraints.get("volume_baseline"),
            # Sus «sí o sí» que hizo como mucho una vez en 4 semanas, con la fecha de la cuenta: Plan y su
            # tabla del perfil se lo enseñan (services/ai/training_habits.fijos_poco_hechos).
            "fijos_poco_hechos": {"hasta": habits.hasta, "lista": poco_hechos},
            "adherence": (
                {k: v for k, v in adherence_state.items() if k != "prompt_block"}
                if adherence_state else None
            ),
        }

        (storage_dir / "summary.json").write_text(json.dumps(summary, indent=2, ensure_ascii=False), encoding="utf-8")
        # El libro de costos, que NO vive en storage_dir. summary.json lo pisa
        # la corrida siguiente y rmtree lo borra entero; la factura del
        # proveedor no hace ninguna de las dos cosas. Ver core/usage_ledger.py.
        # v7: el expediente. Una linea por semana con lo prescrito, lo
        # ejecutado y las sesiones de calidad que se cayeron -- unos 200
        # bytes. Es lo que le da al macrociclo memoria de mas de siete dias
        # sin mandarle el historial entero. Ver services/ai/track_record.py.
        try:
            # DOS anotaciones, y la distincion importa.
            #
            # La adherencia que se acaba de medir describe el plan ANTERIOR:
            # es lo que el atleta hizo con lo que ya tenia. La semana que se
            # acaba de generar todavia no ha ocurrido. Anotar la nueva con esa
            # adherencia -- que es lo que hacia la primera version -- le
            # atribuia a la semana que empieza la ejecucion de la que termina,
            # y el patron que el macrociclo leeria estaria corrido una semana.
            #
            # Asi que primero se CIERRA la semana anterior con su ejecucion
            # real (record_week es idempotente por fecha, de modo que esto
            # actualiza su linea en vez de duplicarla), y luego se ABRE la
            # nueva, prescrita y sin ejecutar.
            if semana_que_termina is not None and adherence_state:
                # Sólo lo que se vivió bajo ese plan: los días antes de que empiece el nuevo.
                # Ver track_record.lo_vivido.
                corte = min((d.get("date") for d in merged_payload.model_dump()["weeks"][-1].get("days") or []
                             if d.get("date")), default=None)
                # Una sola: la que termina, con SU adherencia. Las anteriores ya
                # se cerraron cuando les toco, y re-cerrarlas con este informe
                # les copiaba los totales de otra semana.
                for semana_previa in semana_que_termina.model_dump().get("weeks") or []:
                    semana_previa = lo_vivido(semana_previa, corte)
                    if semana_previa is None:
                        continue
                    # La foto del atleta al cerrar esta semana, con la fase a la
                    # que pertenecía. Nunca tumba el cierre: sin marcadores la
                    # semana se anota igual. Ver respuesta_a_las_fases.
                    marcadores = None
                    try:
                        from services.ai.langgraph.nodes.weekly_planner_node import compute_mesocycle_position

                        fechas = sorted(d.get("date") for d in (semana_previa.get("days") or []) if d.get("date"))
                        if fechas:
                            fases_json = _read_json_if_exists(storage_dir / "season_phases.json")
                            fase = fase_de_la_semana(
                                fases_json, fechas[0], compute_mesocycle_position(fases_json, fechas[0]),
                            )
                            marcadores = marcadores_de_la_semana(
                                garmin_data, prescription, recovery, fechas[0], fechas[-1], fase,
                            )
                    except Exception:
                        logger.exception("No se pudieron tomar los marcadores de la semana que se cierra")
                    cerrada = record_week(storage_dir, semana_previa, adherence_state, marcadores=marcadores)
                    if cerrada["from"]:
                        print(
                            f"Expediente: semana {cerrada['from']} cerrada con su ejecucion "
                            f"({cerrada['quality_done']}/{cerrada['quality_prescribed']} de calidad)"
                        )

            # Y la de antes, que se cerró con sus últimos días pendientes, se mide ahora entera.
            # Ver semana_a_completar.
            completar = semana_a_completar(existing_payload, date.today(), rehacer=rehacer)
            if completar is not None:
                informe = build_adherence_report(
                    completar,
                    getattr(garmin_data, "recent_activities", None),
                    hr_zones=getattr(garmin_data, "garmin_hr_zones", None),
                    umbral_ritmo_s_km=prescription.threshold_pace_sec_per_km,
                    subidas=upload_ledger.cargar(storage_dir),
                    # Lo que el reloj aún no subió no cuenta como no hecho (2026-10-19).
                    ultima_sincronizacion=getattr(garmin_data, "ultima_sincronizacion", None),
                )
                if informe.evaluated:
                    for semana_entera in completar.model_dump().get("weeks") or []:
                        completada = record_week(storage_dir, semana_entera, informe.to_state_dict())
                        print(f"Expediente: semana {completada['from']} medida entera "
                              f"({completada.get('executed_hours')} h hechas)")

            abierta = record_week(
                storage_dir, merged_payload.model_dump()["weeks"][-1], None,
                load_state=(availability_constraints.get("load_state") or {}),
            )
            print(
                f"Expediente: semana {abierta['from']} abierta "
                f"({abierta['prescribed_hours']}h prescritas, "
                f"{abierta['quality_prescribed']} sesion(es) de calidad)"
            )
        except Exception:
            logger.exception("No se pudo anotar la semana en el expediente")

        # El gasto ya quedó anotado en cuanto el flujo devolvió. Ver
        # anotar_gasto: aquí abajo sólo se llega si la semana salió bien, y las
        # que no salen cuestan igual.

        # Same archive as the season run, plus the two things only this path
        # knows: what the coach actually decided day by day (week_intents,
        # carried out of the decision layer through workflow state) and the
        # real recovery/volume numbers that decision was made against.
        reasoning_id = save_reasoning(
            storage_dir, result, kind="week",
            extra={
                "prescription": prescription_state,
                "adherence": adherence_state,
                "recovery": availability_constraints.get("recovery"),
                "load_state": availability_constraints.get("load_state"),
                "niggles": availability_constraints.get("niggles"),
                "volume_baseline": availability_constraints.get("volume_baseline"),
                "week_dates": week_dates,
                "health_flags": health_flags(profile_dict),
                "checkin": availability_constraints.get("checkin"),
                "fitness_series": build_fitness_series(garmin_data, prescription_state),
            },
        )
        if reasoning_id:
            print(f"Razonamiento de los agentes archivado en reasoning/{reasoning_id}")
        # Y se tira lo que ya sobra. `save_reasoning` nunca ha borrado nada
        # ("Nothing is pruned", decia su docstring), asi que el archivo crecia
        # ~500 KB por generacion para siempre. Ver TRAZAS_QUE_SE_CONSERVAN.
        podar_trazas(storage_dir)

        execution_status["progress"] = 100
        execution_status["status"] = "success"
        execution_status["result"] = summary
        print(f"Week generated successfully and saved to 'data/storage/{storage_dir.name}/weekly_plan.json'!")

    except Exception as e:
        logger.error(f"Error in week generation task: {e}")
        traceback.print_exc()
        execution_status["progress"] = 100
        execution_status["status"] = "failed"
        # Si falló Garmin, en palabras del atleta; si no, el error tal cual.
        from services.garmin.client import explicar_error_de_garmin
        execution_status["error"] = explicar_error_de_garmin(e) or str(e)
    finally:
        execution_status["running"] = False

@app.post("/api/generate")
def generate_plan(profile: UserProfileModel, background_tasks: BackgroundTasks, request: Request):
    global execution_status
    # El perfil viene en el CUERPO, asi que el middleware no lo vio (leerlo alli
    # lo consumiria para este endpoint). Ver _allowed_profile.
    _allowed_profile(request, profile.name)
    if execution_status["running"]:
        raise HTTPException(status_code=400, detail="An analysis is already running.")
        
    # Save/update profile. profile_dict (plaintext password) is what
    # run_generation_task actually uses to connect to Garmin below -- only
    # the copy written to disk gets the password encrypted.
    profile_dict = profile.model_dump()
    stored_profile_dict = dict(profile_dict)
    stored_profile_dict["garmin_password"] = encrypt_secret(profile_dict.get("garmin_password"))
    with _profiles_lock:
        profiles = load_profiles()
        profiles[profile.name] = stored_profile_dict
        save_profiles(profiles)

    # Start generation in the background
    background_tasks.add_task(run_generation_task, profile_dict)
    return {"status": "started", "message": "Plan generation task started."}

@app.get("/api/generate/status")
def get_generation_status():
    return execution_status


class GenerateWeekRequest(BaseModel):
    profile: str
    # Rehacer la ULTIMA semana en su sitio en vez de anadir la siguiente.
    redo: bool = False
    # Lo que el atleta quiere distinto, de un solo uso: entra en el prompt de
    # ESTA generacion y no se guarda en su perfil. Un ajuste pedido una vez que
    # se quedara guardado dirigiria en silencio todos sus planes futuros.
    adjustment: str = ""
    # El atleta puede saltarse la ventana a sabiendas (probando la app, o un
    # viaje que le obliga a cerrar la semana antes). Se le dice por que no
    # conviene y se le deja decidir: la app no sabe mas que el sobre su vida.
    force: bool = False


@app.post("/api/generate-week")
def generate_week(payload: GenerateWeekRequest, background_tasks: BackgroundTasks, request: Request):
    """Plan Maestro v4 Track B: generates the next 7 days for a profile that
    already has a season plan. Reuses execution_status (same mutex/polling
    contract as /api/generate) since only one generation of any kind should
    run at a time."""
    global execution_status
    # ANTES de mirar si el perfil existe: un 404 en vez de un 403 le confirma
    # a quien prueba nombres cuales existen. Y esta ruta gasta dinero, asi que
    # sin la comprobacion un atleta podria quemarle los creditos a otro -- y
    # el libro de costos se los cobraria a la victima.
    # El perfil viene en el CUERPO, asi que el middleware no lo vio.
    _allowed_profile(request, payload.profile)

    if execution_status["running"]:
        raise HTTPException(status_code=400, detail="An analysis is already running.")

    profiles = load_profiles()
    stored_profile_dict = profiles.get(payload.profile)
    if not stored_profile_dict:
        raise HTTPException(status_code=404, detail=f"Perfil '{payload.profile}' no encontrado.")

    # Rehacer no crea una semana nueva: reescribe la que el atleta ya tiene
    # delante, asi que la ventana de antelacion no le aplica -- no hay ningun
    # dato futuro que esperar.
    if not payload.force and not payload.redo:
        ventana = ventana_de_generacion(
            _load_plan_for_adherence(_profile_storage_dir(payload.profile)), datetime.now()
        )
        if not ventana["allowed"]:
            # 409 y no 400: no es una peticion mal formada, es que todavia no
            # toca. El frontend distingue los dos para ofrecer "generar de
            # todas formas" solo en este caso.
            raise HTTPException(status_code=409, detail=ventana)

    profile_dict = dict(stored_profile_dict)
    profile_dict["garmin_password"] = decrypt_secret(profile_dict.get("garmin_password"))
    if payload.adjustment.strip():
        # Sobre `planning_context`, que es el canal que ya lee
        # build_custom_planning_context. Va sobre la COPIA que recibe la tarea:
        # el perfil en disco no se toca.
        #
        # SE CONCATENA, no se asigna. Antes esta linea era `=` y borraba, para
        # esta generacion, el texto libre que el atleta tenia guardado en su
        # ficha -- justo el campo donde escribe las cosas que quiere que su
        # entrenador no olvide. Un ajuste puntual no puede tapar una preferencia
        # permanente: son dos cosas distintas y el prompt necesita las dos.
        guardado = str(profile_dict.get("planning_context") or "").strip()
        ajuste = f"Ajuste pedido por el atleta al revisar esta semana: {payload.adjustment.strip()}"
        separador = "\n\n"
        profile_dict["planning_context"] = f"{guardado}{separador}{ajuste}" if guardado else ajuste

    background_tasks.add_task(run_week_generation_task, profile_dict, payload.redo)
    return {"status": "started", "message": "Week generation task started."}


@app.get("/api/generate-week/window")
def get_generate_week_window(profile: str = ""):
    """Desde cuando se puede generar la siguiente semana, para que el boton lo
    diga antes de que el atleta lo pulse."""
    return ventana_de_generacion(_load_plan_for_adherence(_profile_storage_dir(profile)), datetime.now())

# --- Garmin upload ---
# Separate mutex from execution_status: generating a plan and uploading it
# to Garmin are independent user actions and shouldn't block each other.
garmin_upload_status = {
    "running": False,
    "progress": 0,
    "status": "idle",
    "result": None,
    "error": None,
}


async def run_garmin_upload_task(profile_name: str, dates: list | None):
    global garmin_upload_status
    garmin_upload_status["running"] = True
    garmin_upload_status["progress"] = 10
    garmin_upload_status["status"] = "Conectando a Garmin Connect..."
    garmin_upload_status["error"] = None
    garmin_upload_status["result"] = None

    try:
        profiles = load_profiles()
        profile_dict = profiles.get(profile_name)
        if not profile_dict:
            raise ValueError(f"Perfil '{profile_name}' no encontrado.")

        storage_dir = _profile_storage_dir(profile_name)
        weekly_plan_path = storage_dir / "weekly_plan.json"
        if not weekly_plan_path.exists():
            raise ValueError("No hay un plan generado para este perfil. Genera un plan primero.")

        raw = json.loads(weekly_plan_path.read_text(encoding="utf-8"))
        if "error" in raw:
            raise ValueError("El plan guardado no es válido (generación previa falló). Genera un plan nuevo.")
        payload = WeeklyPlanPayload.model_validate(raw)

        # Prefer the athlete's real Garmin zones, captured when the plan was
        # generated, over anything derived from a formula.
        saved_prescription = _load_saved_prescription(storage_dir)
        # Running is the reference table here: heart-rate targets upload as
        # named zones (the watch then applies the right per-sport table
        # itself), so this only backs the rare explicit-bpm-range step.
        zones_by_sport = saved_prescription.get("hr_zones_by_sport") or {}
        run_zones = zones_by_sport.get("run") or next(iter(zones_by_sport.values()), {})
        real_hr_zones = {
            int(zone): (int(bounds[0]), None if bounds[1] is None else int(bounds[1]))
            for zone, bounds in run_zones.items()
        } or None

        zones = compute_zone_config(
            lactate_threshold_hr=profile_dict.get("lactate_threshold_hr"),
            lactate_threshold_pace_sec_per_km=(
                saved_prescription.get("threshold_pace_sec_per_km")
                or profile_dict.get("lactate_threshold_pace_sec_per_km")
            ),
            ftp_watts=profile_dict.get("ftp_watts"),
            garmin_hr_zones=real_hr_zones,
        )

        garmin_upload_status["progress"] = 30
        garmin_upload_status["status"] = "Subiendo entrenamientos..."

        client = GarminConnectClient()
        client.connect(profile_dict["garmin_email"], decrypt_secret(profile_dict["garmin_password"]))
        service = GarminUploadService(client)

        # Lo que ya pusimos en el Garmin de este atleta. Subir una sesion que
        # ya estaba REEMPLAZA la anterior en vez de dejar dos entradas en su
        # calendario, que es lo que pasaba hasta ahora con cada clic en
        # "Subir". Ver services/garmin/upload_ledger.py.
        registro = upload_ledger.cargar(storage_dir)
        previos = {k: e["workout_id"] for k, e in upload_ledger.por_sesion(registro).items()}

        results: list[UploadResult] = await service.upload_plan(
            payload, zones, only_dates=dates, previous_ids=previos,
        )

        dia_por_sesion = {
            f"{d.date}|{d.session_slot}": d for w in payload.weeks for d in w.days
        }
        for r in results:
            if not r.success or not r.workout_id:
                continue
            dia = dia_por_sesion.get(f"{r.date}|{r.session_slot}")
            registro = upload_ledger.anotar(
                registro, fecha=r.date, franja=r.session_slot, workout_id=r.workout_id,
                nombre=(dia.focus if dia else "") or r.sport, deporte=r.sport,
                huella_sesion=upload_ledger.huella(dia) if dia else "",
            )

        # Huerfanos: al regenerar una semana, el lunes que antes tenia fuerza
        # AM y bici PM puede quedarse con una sola sesion. La subida reemplaza
        # las sesiones que siguen existiendo, pero la que desaparecio del plan
        # se quedaria en el calendario del atleta para siempre. Solo se mira
        # dentro de la ventana del plan que se acaba de subir entera: fuera de
        # ella no hay plan con el que comparar.
        # Con fechas («Enviar al reloj», sólo lo que cambió), los sobrantes de ESAS fechas: un entreno que
        # el atleta añadió y quitó después de enviarlo se quedaba en su calendario (2026-10-23).
        fechas_del_plan = set(dates) if dates else {d.date for w in payload.weeks for d in w.days}
        claves_vivas = {f"{d.date}|{d.session_slot}" for w in payload.weeks
                        for d in w.days if not d.is_rest}
        # Nunca el test de umbral: no es del plan (ver upload_ledger.huerfanas).
        sobrantes = upload_ledger.huerfanas(registro, fechas_del_plan, claves_vivas)

        # Y se retira lo que ya paso. Esta es la razon de que exista el
        # registro: sin el, la biblioteca de Garmin crecia sin techo hasta que
        # el reloj avisaba de que habia alcanzado su limite de sesiones.
        retirados = 0
        for vieja in [*sobrantes, *upload_ledger.caducadas(registro)]:
            if service.delete_workout(vieja["workout_id"]):
                registro = upload_ledger.olvidar(registro, [vieja["workout_id"]])
                retirados += 1
        upload_ledger.guardar(storage_dir, registro)
        if retirados:
            logger.info("Retirados %d entrenos ya pasados de la biblioteca de Garmin", retirados)

        succeeded = sum(1 for r in results if r.success)
        garmin_upload_status["progress"] = 100
        garmin_upload_status["status"] = "success"
        garmin_upload_status["result"] = {
            "uploaded": succeeded,
            "total": len(results),
            "replaced": sum(1 for r in results if r.replaced_workout_id),
            "pruned": retirados,
            "details": [r.__dict__ for r in results],
        }
        print(f"✅ Garmin upload: {succeeded}/{len(results)} entrenamientos subidos para '{profile_name}'.")

    except Exception as e:
        logger.error(f"Error in Garmin upload task: {e}")
        traceback.print_exc()
        garmin_upload_status["progress"] = 100
        garmin_upload_status["status"] = "failed"
        from services.garmin.client import explicar_error_de_garmin
        garmin_upload_status["error"] = explicar_error_de_garmin(e) or str(e)
    finally:
        garmin_upload_status["running"] = False


@app.post("/api/garmin/upload")
def garmin_upload(request: GarminUploadRequestModel, background_tasks: BackgroundTasks, http_request: Request):
    global garmin_upload_status
    # El perfil viene en el CUERPO, asi que el middleware no lo vio (leerlo alli
    # lo consumiria para este endpoint). Ver _allowed_profile.
    _allowed_profile(http_request, request.profile_name)
    if garmin_upload_status["running"]:
        raise HTTPException(status_code=400, detail="A Garmin upload is already running.")

    background_tasks.add_task(run_garmin_upload_task, request.profile_name, request.dates)
    return {"status": "started", "message": "Garmin upload task started."}


@app.get("/api/garmin/upload/status")
def get_garmin_upload_status():
    return garmin_upload_status


@app.get("/api/garmin/uploads")
def get_garmin_uploads(profile: str = ""):
    """Que sesiones de este plan ya estan en el Garmin del atleta.

    Hasta ahora la pantalla no lo sabia: el "Subido" que se veia venia del
    sondeo de ESA subida y desaparecia al recargar, asi que el boton invitaba
    a volver a pulsarlo y cada clic dejaba otra entrada en el calendario.
    """
    entradas = upload_ledger.cargar(_profile_storage_dir(profile))
    plan = _load_plan_for_adherence(_profile_storage_dir(profile))
    huellas = {
        upload_ledger.clave(d.date, d.session_slot): upload_ledger.huella(d)
        for w in (plan.weeks if plan else []) for d in w.days if not d.is_rest
    }
    salida = {}
    for e in entradas:
        k = upload_ledger.clave(e["date"], e.get("session_slot"))
        if k not in huellas:
            continue  # de un plan anterior: no hay nada que marcar en pantalla
        # Una sesion regenerada conserva su fecha y su franja, asi que la clave
        # sigue coincidiendo aunque el entreno del reloj sea el de la version
        # vieja. La huella es lo unico que distingue las dos.
        salida[k] = {
            "workout_id": e["workout_id"],
            "uploaded_at": e.get("uploaded_at"),
            "stale": bool(e.get("fingerprint")) and e["fingerprint"] != huellas[k],
        }
    return {"sessions": salida}


@app.get("/api/reports/planning")
def get_planning_report(profile: str = ""):
    """Serve the planning.html report generated by the workflow."""
    html_path = _profile_storage_dir(profile) / "planning.html"
    if not html_path.exists():
        raise HTTPException(status_code=404, detail="Planning report not found. Generate a plan first.")
    return HTMLResponse(content=html_path.read_text(encoding="utf-8"))

@app.get("/api/reports/analysis")
def get_analysis_report(profile: str = ""):
    """Serve the analysis.html report generated by the workflow."""
    html_path = _profile_storage_dir(profile) / "analysis.html"
    if not html_path.exists():
        raise HTTPException(status_code=404, detail="Analysis report not found. Generate a plan first.")
    return HTMLResponse(content=html_path.read_text(encoding="utf-8"))

@app.post("/api/charts/refresh")
def refresh_charts(profile: str = ""):
    """Refresca las series del tablero leyendo el reloj. Sin IA.

    Tres llamadas baratas a Garmin -- estado de entrenamiento, predicciones de
    carrera, y los dias de recuperacion que falten -- y ni un modelo. Ver
    services/ai/dashboard_refresh.py para por que estas tres y no la extraccion
    completa: el detalle de cada actividad con sus vueltas tarda dos minutos en
    una cuenta real, y para dibujar una linea no hace falta.
    """
    from services.ai import dashboard_refresh

    perfil = load_profiles().get(profile) or {}
    email, password = perfil.get("garmin_email"), perfil.get("garmin_password")
    if not email or not password:
        raise HTTPException(status_code=400, detail="Este perfil no tiene credenciales de Garmin guardadas.")

    storage_dir = _profile_storage_dir(profile)
    try:
        extractor = TriathlonCoachDataExtractor(email, password)
        resultado = dashboard_refresh.refresh(extractor, storage_dir)
    except Exception as exc:
        logger.warning("No se pudo refrescar el tablero de %s: %s", profile, exc)
        raise HTTPException(status_code=502, detail=f"No se pudo conectar a Garmin: {exc}")

    return {**resultado, **build_charts(storage_dir, perfil)}


@app.get("/api/race-distances")
def get_race_distances():
    """El catalogo de formatos de carrera, agrupado por disciplina.

    Viene del servidor y no esta escrito a mano en el frontend para que haya
    UNA lista: la misma que usa `goal_realism` para evaluar la meta. Dos listas
    que puedan divergir es exactamente como se colo el fallo de las clases de
    calidad -- una meta con una distancia que el evaluador no conoce vuelve a
    ser "sin referencia", que es el problema que esto viene a arreglar.
    """
    from services.ai.race_distances import options_for_ui
    return {"groups": options_for_ui()}


@app.get("/api/charts")
def get_charts(profile: str = ""):
    """Las series que la app dibuja. Cero coste: sale de lo que ya hay en disco.

    Sin llamadas a Garmin y sin llamadas a ningun modelo -- una grafica es
    presentacion, y pedirle a un LLM que resuma numeros que ya tenemos exactos
    seria pagar por perder precision. Ver services/ai/charts.py.

    El perfil viaja porque una de las graficas -- la prediccion contra la meta
    -- necesita la meta, y esa es lo unico de todo esto que no sale de un
    archivo del atleta sino de una decision suya.
    """
    from services.ai import dashboard_refresh

    storage_dir = _profile_storage_dir(profile)
    salida = build_charts(storage_dir, load_profiles().get(profile) or {})
    # Que el front sepa si lo que ensena es de hoy. Un tablero que muestra un
    # numero viejo sin decir que es viejo es peor que uno que no lo muestra.
    salida["last_refresh"] = dashboard_refresh.last_snapshot_date(storage_dir)
    salida["stale"] = dashboard_refresh.is_stale(storage_dir)
    return salida



@app.get("/api/plans/current")
def get_current_plans(profile: str = ""):
    """Return the latest weekly plan (structured JSON) and season plan (markdown) for a profile."""
    storage_dir = _profile_storage_dir(profile)
    weekly_path = storage_dir / "weekly_plan.json"
    season_path = storage_dir / "season_plan.md"
    summary_path = storage_dir / "summary.json"

    weekly_plan = None
    if weekly_path.exists():
        try:
            weekly_plan = json.loads(weekly_path.read_text(encoding="utf-8"))
        except json.JSONDecodeError:
            weekly_plan = {"error": "corrupt_weekly_plan_file"}

    plan_validation = None
    volume_reconciliation_note = None
    total_cost_usd = None
    total_tokens = None
    cost_breakdown = []
    adherence = None
    if summary_path.exists():
        try:
            summary = json.loads(summary_path.read_text(encoding="utf-8"))
            plan_validation = summary.get("plan_validation")
            volume_reconciliation_note = summary.get("volume_reconciliation_note")
            total_cost_usd = summary.get("total_cost_usd")
            total_tokens = summary.get("total_tokens")
            cost_breakdown = summary.get("cost_breakdown", [])
            adherence = summary.get("adherence")
        except json.JSONDecodeError:
            pass

    return {
        "weekly_plan": weekly_plan,
        "season_plan": season_path.read_text(encoding="utf-8") if season_path.exists() else "",
        "has_planning_html": (storage_dir / "planning.html").exists(),
        "has_analysis_html": (storage_dir / "analysis.html").exists(),
        "plan_validation": plan_validation,
        "volume_reconciliation_note": volume_reconciliation_note,
        "total_cost_usd": total_cost_usd,
        "total_tokens": total_tokens,
        "cost_breakdown": cost_breakdown,
        "adherence": adherence,
    }

def _fechas_del_plan(storage_dir: Path) -> set[str]:
    payload = _read_json_if_exists(storage_dir / "weekly_plan.json") or {}
    return {d.get("date") for w in (payload.get("weeks") or [])
            for d in (w.get("days") or []) if d.get("date")}


def retirar_semana_de_garmin(profile_name: str, storage_dir: Path) -> dict:
    """Saca del Garmin del atleta los entrenos de la semana que va a borrar.

    Borrar la semana en la app y dejarla en el reloj es el desajuste que este
    proyecto lleva persiguiendo: la app dice que ese plan ya no existe y la
    muneca sigue pitando para hacerlo. Hasta v7 no habia forma de arreglarlo
    -- nadie sabia que entrenos eran nuestros -- y ahora si.

    **Nunca hace fallar el borrado.** El plan es del atleta y esta en su disco;
    si Garmin no contesta se borra igual y se le dice que el reloj se quedo con
    las sesiones. Devuelve lo que hizo para poder contarselo.
    """
    try:
        perfil = load_profiles().get(profile_name) or {}
        email = perfil["garmin_email"]
        password = decrypt_secret(perfil["garmin_password"])
    except Exception as exc:  # noqa: BLE001 - sin credenciales no hay nada que retirar
        logger.warning("No hay credenciales de Garmin para retirar la semana borrada: %s", exc)
        entradas = upload_ledger.para_fechas(
            upload_ledger.cargar(storage_dir), _fechas_del_plan(storage_dir)
        )
        return {"retirados": 0, "pendientes": len(entradas), "error": str(exc)}

    return retirar_semana_de_garmin_con(email, password, storage_dir)


def retirar_semana_de_garmin_con(
    garmin_email: str, garmin_password: str, storage_dir: Path, todas: bool = False
) -> dict:
    """Lo mismo, con las credenciales en la mano en vez de en `profiles.json`.

    Existe porque el puente (AiService) guarda a los atletas en MySQL y no
    tiene ese fichero: su contrasena de Garmin no se almacena en ningun sitio
    y la escribe el atleta cada vez. Partir la funcion en dos deja UNA sola
    implementacion del borrado -- duplicarla es exactamente como el puente se
    quedo sin anotar el costo del chat.

    `todas=True` retira TODO lo anotado en el registro, de cualquier fecha: es
    lo que hace falta antes de reiniciar la beta, cuando la cuenta entera se va
    y con ella el registro que dice qué entrenos son nuestros. La regla de
    seguridad no cambia: sólo ids que esta app creó y anotó.
    """
    registro_completo = upload_ledger.cargar(storage_dir)
    entradas = (registro_completo if todas
                else upload_ledger.para_fechas(registro_completo, _fechas_del_plan(storage_dir)))
    if not entradas:
        return {"retirados": 0, "pendientes": 0, "error": None}

    try:
        client = GarminConnectClient()
        client.connect(garmin_email, garmin_password)
        service = GarminUploadService(client)
    except Exception as exc:
        logger.warning("No se pudo conectar a Garmin para retirar la semana borrada: %s", exc)
        return {"retirados": 0, "pendientes": len(entradas), "error": str(exc)}

    retirados, registro = 0, upload_ledger.cargar(storage_dir)
    for e in entradas:
        if service.delete_workout(e["workout_id"]):
            registro = upload_ledger.olvidar(registro, [e["workout_id"]])
            retirados += 1
    upload_ledger.guardar(storage_dir, registro)
    return {"retirados": retirados, "pendientes": len(entradas) - retirados, "error": None}


def describe_deletion(storage_dir: Path, scope: str) -> dict:
    """Exactly what disappears if the athlete confirms, in their own terms.

    v5 Track 11. The old dialog said the same fixed sentence no matter what
    was on disk, so the athlete could not tell whether they were about to drop
    this week's sessions or a 16-week macrocycle they had built a season
    around. Everything here is read from the files themselves.
    """
    items: list[str] = []

    if scope in ("week", "all"):
        payload = _read_json_if_exists(storage_dir / "weekly_plan.json") or {}
        weeks = payload.get("weeks") or []
        sessions = sum(
            1 for week in weeks for day in (week.get("days") or []) if not day.get("is_rest")
        )
        dates = sorted(
            day.get("date") for week in weeks for day in (week.get("days") or []) if day.get("date")
        )
        if weeks:
            span = f" (del {dates[0]} al {dates[-1]})" if dates else ""
            items.append(
                f"{len(weeks)} semana(s) de entrenamiento{span}, con {sessions} sesiones prescritas"
            )
        en_garmin = upload_ledger.para_fechas(upload_ledger.cargar(storage_dir), set(dates))
        if en_garmin:
            items.append(
                f"{len(en_garmin)} sesion(es) que tambien se quitaran de tu Garmin y de tu reloj "
                "(tus actividades ya hechas no se tocan)"
            )

    if scope in ("season", "all"):
        phases = (_read_json_if_exists(storage_dir / "season_phases.json") or {}).get("phases") or []
        if (storage_dir / "season_plan.md").exists() or phases:
            detail = f" con {len(phases)} fases" if phases else ""
            items.append(f"el macrociclo completo{detail}, y sus restricciones de temporada")

    if scope == "reasoning":
        entries = list_reasoning_entries(storage_dir)
        if entries:
            items.append(
                f"{len(entries)} traza(s) de razonamiento de los agentes — los resúmenes, el análisis de "
                "los expertos y la decisión de coaching que explican por qué tu plan es como es"
            )

    warnings: list[str] = []
    if scope == "all":
        archived = sorted(
            (d for d in _history_dir(storage_dir).iterdir() if d.is_dir()), reverse=True
        ) if _history_dir(storage_dir).exists() else []
        if archived:
            warnings.append(f"Se restaurará la generación anterior ({archived[0].name}) en su lugar.")
        else:
            warnings.append("No hay ninguna generación anterior que restaurar: quedarás sin plan.")
    # Lo que pasa con las trazas, dicho con precisión y por alcance.
    #
    # Esta línea decía "El razonamiento archivado NO se borra con esto", y
    # dejó de ser verdad cuando el borrado empezó a llevarse la traza del plan
    # que se borra. Un aviso que describe mal lo que va a pasar es peor que no
    # tenerlo: el atleta confirma otra cosa distinta de la que pidió.
    #
    # Lo que sí sigue en pie es que **no se cruzan los alcances**: borrar la
    # semana no toca el razonamiento del macrociclo, y al revés tampoco.
    _RAZONAMIENTO = {
        "week": "También se borra el razonamiento de ESA semana (ya no describe nada). "
                "El del macrociclo se queda.",
        "season": "También se borra el razonamiento del macrociclo. El de tu semana en curso se queda.",
        "all": "También se borra el razonamiento de las dos.",
    }
    if scope in _RAZONAMIENTO:
        warnings.append(_RAZONAMIENTO[scope])
        warnings.append(
            "Tu historial de forma física, carga y recuperación NO se toca: sale de las "
            "generaciones que sobreviven y es dato tuyo, no del plan."
        )

    return {"scope": scope, "items": items, "warnings": warnings, "empty": not items}


def _strip_summary_keys(storage_dir: Path, keys) -> None:
    """Remove one half's keys from the shared summary, keeping the other half.

    Deleting summary.json outright on a scoped delete would take the surviving
    half's cost, validation and prescription down with it.
    """
    summary = _read_json_if_exists(storage_dir / "summary.json")
    if not summary:
        return
    for key in keys:
        summary.pop(key, None)
    (storage_dir / "summary.json").write_text(
        json.dumps(summary, indent=2, ensure_ascii=False), encoding="utf-8"
    )


class CoachChatModel(BaseModel):
    profile: str = ""
    message: str = ""
    history: list[dict] | None = []
    # Capped server-side regardless (coach_chat.MAX_RUNS): every message
    # re-sends the whole context, so an unbounded archive would tax every
    # question the athlete ever asks.
    max_runs: int | None = 1


@app.post("/api/coach/chat")
async def coach_chat(data: CoachChatModel, request: Request):
    """Ask the coach about the current plan. Read-only -- it never edits it."""
    # El perfil viene en el CUERPO, asi que el middleware no lo vio (leerlo alli
    # lo consumiria para este endpoint). Ver _allowed_profile.
    _allowed_profile(request, data.profile)
    if not (data.message or "").strip():
        raise HTTPException(status_code=400, detail="Escribe una pregunta.")

    profile = load_profiles().get(data.profile) or {}
    os.environ["AI_MODE"] = profile.get("ai_mode") or DEFAULT_AI_MODE
    reload_config()
    ai_settings.reload()
    try:
        answer = await ask_coach(
            _profile_storage_dir(data.profile),
            profile,
            data.message,
            data.history or [],
            max_runs=data.max_runs or 1,
        )
        # El chat es el unico gasto sin tope de frecuencia del producto: el
        # atleta puede mandar veinte mensajes en una tarde. Anotarlo por
        # mensaje es lo que hace que el costo por usuario sea real y no una
        # estimacion de las generaciones solamente.
        uso = answer.pop("usage_metadata", None) or {}

        # v7 Track 7: el coach mira lo que acaba de pasar y decide si hay algo
        # duradero que anotar. Llamada aparte y BARATA -- solo ve el
        # intercambio y el cuaderno, no el contexto entero (~1k tokens contra
        # los ~8k de la respuesta). Casi siempre no anota nada, que es lo
        # correcto: la mayoria de los mensajes son preguntas sobre el plan.
        #
        # Va despues de responder y su fallo no puede costarle la respuesta al
        # atleta: ya la tiene.
        propuestas: list = []
        try:
            propuestas, uso_extra = await notebook_propose(
                _profile_storage_dir(data.profile), data.message, answer.get("reply", "")
            )
            for modelo, u in (uso_extra or {}).items():
                actual = uso.setdefault(modelo, {"input_tokens": 0, "output_tokens": 0, "total_tokens": 0})
                for clave in ("input_tokens", "output_tokens", "total_tokens"):
                    actual[clave] = (actual.get(clave) or 0) + (u.get(clave) or 0)
        except Exception:
            logger.exception("El cuaderno no pudo procesar este intercambio")

        # Anotar el costo NO puede costar la respuesta. Ocurre despues de
        # generarla, asi que un fallo aqui le cobraba al atleta una llamada
        # que terminaba en error 502 -- paso de verdad, con un desacuerdo de
        # formato entre record_call y record_run.
        try:
            entry = usage_ledger.record_call(
                data.profile,
                usage_ledger.KIND_CHAT,
                uso,
                mode=os.environ.get("AI_MODE"),
            )
            answer["cost_usd"] = entry["cost_usd"]
        except Exception:
            logger.exception("No se pudo registrar el costo de este mensaje del chat")
        # Se devuelven para que la UI pueda decir "he anotado esto" y el
        # atleta lo confirme o lo tire. Nacen como propuesta, no como hecho.
        answer["notebook_proposals"] = propuestas
        return answer
    except Exception as exc:
        logger.exception("Coach chat failed")
        raise HTTPException(status_code=502, detail=f"El entrenador no pudo responder: {exc}") from exc


# Durante la beta el atleta ve lo que cuesta su propio entrenamiento -- es
# parte de lo que se le esta pidiendo validar. Cuando el producto pase a
# precio cerrado esto se apaga poniendolo en False: el suscriptor de un
# producto terminado no tiene por que ver el costo de proveedor, igual que no
# ve el de su gimnasio. El endpoint de operador (/api/usage/admin) no depende
# de esta bandera.
SHOW_COST_TO_ATHLETE = True


@app.get("/api/usage")
def usage_for_athlete(profile: str = ""):
    """Lo que ha costado ESTE atleta: total, por tipo, por mes y lo reciente.

    Sale del libro append-only, no de summary.json, asi que sobrevive tanto a
    la corrida siguiente como a que el atleta borre su plan.
    """
    if not SHOW_COST_TO_ATHLETE:
        raise HTTPException(status_code=404, detail="No disponible.")
    if not profile:
        raise HTTPException(status_code=400, detail="Falta el perfil.")
    return usage_ledger.usage_for_profile(profile)


@app.get("/api/usage/admin")
def usage_for_operator(request: Request):
    """Todos los atletas, incluidos los eliminados, y el total del mes."""
    require_admin(request)
    return usage_ledger.usage_by_profile()


class NotebookEntryModel(BaseModel):
    profile: str = ""
    text: str = ""
    category: str = "otro"


class NotebookPatchModel(BaseModel):
    profile: str = ""
    text: str | None = None
    category: str | None = None
    status: str | None = None
    retired_reason: str = ""


@app.get("/api/notebook")
def get_notebook(profile: str = ""):
    """El cuaderno entero, incluidas las retiradas.

    Se devuelven TODAS y no solo las vigentes a proposito: el atleta tiene
    derecho a ver lo que el coach escribio sobre el y tambien lo que dejo de
    aplicar. Un cuaderno que solo ensena lo vigente esconde su propio
    historial, que es justo lo que hace que una memoria no sea auditable.
    """
    entradas = load_notebook(_profile_storage_dir(profile))
    return {
        "entries": entradas,
        "categories": NOTEBOOK_CATEGORIES,
        "counts": {
            "vigentes": sum(1 for e in entradas if e.get("status") == NOTEBOOK_ACTIVE),
            "propuestas": sum(1 for e in entradas if e.get("status") == NOTEBOOK_PROPOSED),
            "retiradas": sum(1 for e in entradas if e.get("status") == NOTEBOOK_RETIRED),
        },
    }


@app.post("/api/notebook")
def create_notebook_entry(data: NotebookEntryModel, request: Request):
    """Una anotacion escrita por el atleta. Nace vigente: es suya."""
    _allowed_profile(request, data.profile)
    try:
        return notebook_add(
            _profile_storage_dir(data.profile), data.text,
            category=data.category, source="manual", status=NOTEBOOK_ACTIVE,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc


@app.patch("/api/notebook/{entry_id}")
def patch_notebook_entry(entry_id: str, data: NotebookPatchModel, request: Request):
    """Editar, confirmar una propuesta, o retirarla.

    Retirar NO borra: marca la fecha de baja y conserva el texto. Es la
    operacion que el coach tiene disponible, y la que deja rastro.
    """
    _allowed_profile(request, data.profile)
    campos = {k: v for k, v in data.model_dump(exclude={"profile"}).items() if v is not None and v != ""}
    if not campos:
        raise HTTPException(status_code=400, detail="No hay nada que cambiar.")
    try:
        entrada = notebook_update(_profile_storage_dir(data.profile), entry_id, **campos)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc
    if entrada is None:
        raise HTTPException(status_code=404, detail="Esa anotación no existe.")
    return entrada


@app.delete("/api/notebook/{entry_id}")
def remove_notebook_entry(entry_id: str, profile: str = ""):
    """Borrado de verdad, no retirada.

    Existe porque son sus datos: el atleta puede eliminar lo que el coach
    escribio sobre el, no solo taparlo. Es la unica via que borra de verdad
    del cuaderno; el coach solo puede retirar.
    """
    if not notebook_delete(_profile_storage_dir(profile), entry_id):
        raise HTTPException(status_code=404, detail="Esa anotación no existe.")
    return {"status": "ok", "deleted": entry_id}


@app.get("/api/coach/context")
def coach_context(profile: str = "", max_runs: int = 1):
    """What the coach would be told, and how big it is.

    Exposed so the size of the context is something the athlete (and we) can
    see, rather than something to discover on a bill.
    """
    profile_dict = load_profiles().get(profile) or {}
    return build_coach_context(_profile_storage_dir(profile), profile_dict, max_runs=max_runs)


@app.get("/api/plans/deletion-preview")
def get_deletion_preview(profile: str = "", scope: str = "all"):
    """What a delete would actually remove, so the dialog can say it out loud."""
    if scope not in ("week", "season", "all", "reasoning"):
        raise HTTPException(status_code=400, detail="Alcance de borrado no válido.")
    return describe_deletion(_profile_storage_dir(profile), scope)


@app.delete("/api/plans/current")
def delete_current_generation(profile: str = "", scope: str = "all"):
    """Delete the current plan, at the scope the athlete chose.

    `scope="week"` drops only the generated sessions, `scope="season"` only the
    macrocycle, `scope="all"` both. Before v5 Track 11 there was only the "all"
    behaviour behind a button labelled "borrar generación", so an athlete who
    wanted to redo a single week silently lost the macrocycle underneath it.

    "all" also promotes the most recent archived generation back into place --
    so "undo this run" means going back to the plan they had before it, not
    being left with nothing. The narrower scopes do NOT restore: bringing back
    half of an older snapshot on top of the surviving half would produce a plan
    that never existed.

    **Las trazas de razonamiento SÍ se van, y sólo las del alcance borrado.**
    Antes no: la traza sobrevivía al plan que explicaba y el chat se la seguía
    leyendo, así que un atleta que borraba su semana y no generaba otra abría
    el chat y su entrenador le explicaba con detalle la semana que acababa de
    borrar. Se van por tipo, no todas: una traza de temporada no describe la
    semana, y borrar el macrociclo no debe llevarse el razonamiento de la
    semana en curso.

    Lo que NO se toca es el historial de forma física que vive dentro de esas
    trazas (`fitness_series.json`, `load_state.json`) para las generaciones
    que sobreviven -- son datos del atleta, no del plan, y alimentan las
    gráficas del panel.
    """
    if scope not in ("week", "season", "all"):
        raise HTTPException(status_code=400, detail="Alcance de borrado no válido.")

    storage_dir = _profile_storage_dir(profile)
    if not (storage_dir / "summary.json").exists() and not (storage_dir / "weekly_plan.json").exists():
        raise HTTPException(status_code=404, detail="No hay ninguna generación que eliminar.")

    if execution_status.get("running"):
        raise HTTPException(status_code=409, detail="Hay una generación en curso. Espera a que termine.")

    if scope == "week":
        garmin = retirar_semana_de_garmin(profile, storage_dir)
        for filename in _WEEK_FILES:
            (storage_dir / filename).unlink(missing_ok=True)
        _strip_summary_keys(storage_dir, _WEEK_SUMMARY_KEYS)
        borrar_trazas(storage_dir, kind="week")
        logger.info("Deleted the current WEEK for %s", profile)
        return {"deleted": True, "scope": scope, "restored": None, "garmin": garmin}

    if scope == "season":
        for filename in _SEASON_FILES:
            (storage_dir / filename).unlink(missing_ok=True)
        _strip_summary_keys(storage_dir, _SEASON_SUMMARY_KEYS)
        borrar_trazas(storage_dir, kind="season")
        logger.info("Deleted the current SEASON PLAN for %s", profile)
        return {"deleted": True, "scope": scope, "restored": None}

    garmin = retirar_semana_de_garmin(profile, storage_dir)
    for filename in _GENERATION_FILES:
        (storage_dir / filename).unlink(missing_ok=True)

    restored = None
    history_dir = _history_dir(storage_dir)
    if history_dir.exists():
        archived = sorted((d for d in history_dir.iterdir() if d.is_dir()), reverse=True)
        if archived:
            newest = archived[0]
            for filename in _GENERATION_FILES:
                source = newest / filename
                if source.exists():
                    shutil.copy2(source, storage_dir / filename)
            # It is the live plan again, so it should not also sit in history
            # -- otherwise deleting twice would keep restoring the same entry.
            shutil.rmtree(newest, ignore_errors=True)
            restored = newest.name
            logger.info("Deleted the current generation and restored %s for %s", restored, profile)

    if restored is None:
        logger.info("Deleted the current generation for %s (no archive to restore)", profile)

    return {"deleted": True, "scope": scope, "restored": restored, "garmin": garmin}


class CheckinModel(BaseModel):
    """One pre-week check-in. Every field optional on purpose: a partial answer
    is still the only self-reported data the app has, and rejecting it for
    incompleteness would throw that away."""

    profile: str = ""
    sleep: int | None = None
    fatigue: int | None = None
    stress: int | None = None
    soreness: int | None = None
    motivation: int | None = None
    niggle_location: str | None = ""
    niggle_pain: int | None = None
    last_week_note: str | None = ""
    week_ahead: str | None = ""
    week_note: str | None = ""
    unavailable_dates: list[str] | None = []
    open_dates: dict[str, list[str]] | None = {}


@app.post("/api/checkin")
def submit_checkin(data: CheckinModel, request: Request):
    """Save the athlete's check-in for today. Same-day answers replace."""
    # El perfil viene en el CUERPO, asi que el middleware no lo vio (leerlo alli
    # lo consumiria para este endpoint). Ver _allowed_profile.
    _allowed_profile(request, data.profile)
    storage_dir = _profile_storage_dir(data.profile)
    checkin = normalize_checkin(data.model_dump(exclude={"profile"}))
    save_checkin(storage_dir, checkin)
    return {"saved": True, "checkin": checkin}


@app.get("/api/checkin/latest")
def get_latest_checkin(profile: str = ""):
    """The most recent check-in plus this athlete's own Hooper baseline.

    The baseline is the whole point: an absolute score is uninterpretable --
    16 is a bad week for someone who usually reports 9 and a good one for
    someone who usually reports 22.
    """
    storage_dir = _profile_storage_dir(profile)
    history = load_checkins(storage_dir)
    return checkin_to_state(latest_checkin(storage_dir), history)


@app.delete("/api/reasoning")
def delete_all_reasoning(profile: str = ""):
    """Delete every archived reasoning trail for a profile.

    Deliberately separate from deleting a plan: deleting a plan does not delete
    WHY it was written. The trails are the athlete's own record of how their
    training was reasoned about, and the material the coach chat reads.
    """
    base = _profile_storage_dir(profile) / REASONING_DIRNAME
    removed = len([d for d in base.iterdir() if d.is_dir()]) if base.is_dir() else 0
    if removed:
        shutil.rmtree(base, ignore_errors=True)
    logger.info("Deleted %d reasoning trail(s) for %s", removed, profile)
    return {"deleted": removed}


@app.delete("/api/reasoning/{entry_id}")
def delete_reasoning_entry(entry_id: str, profile: str = ""):
    if "/" in entry_id or "\\" in entry_id or ".." in entry_id:
        raise HTTPException(status_code=400, detail="Identificador de razonamiento inválido.")

    entry_dir = _profile_storage_dir(profile) / REASONING_DIRNAME / entry_id
    if not entry_dir.is_dir():
        raise HTTPException(status_code=404, detail="No se encontró ese razonamiento archivado.")

    shutil.rmtree(entry_dir, ignore_errors=True)
    return {"deleted": entry_id}


@app.get("/api/reasoning")
def get_reasoning_entries(profile: str = ""):
    """Every archived reasoning trail for a profile, newest first.

    Lightweight: manifests only (what kind of run, cost, which artefacts were
    missing). Use /api/reasoning/{entry_id} for the actual agent text.
    """
    return {"entries": list_reasoning_entries(_profile_storage_dir(profile))}


@app.get("/api/reasoning/latest")
def get_latest_reasoning(profile: str = "", kind: str = ""):
    """The most recent trail, optionally restricted to "season" or "week".

    This is what the coach chatbot reads to answer "why does my week look like
    this?" with the real reasoning rather than a reconstruction.
    """
    storage_dir = _profile_storage_dir(profile)
    entry = latest_reasoning_entry(storage_dir, kind or None)
    if not entry:
        return {"entry": None, "contents": {}}
    return {"entry": entry, "contents": load_reasoning_entry(storage_dir, entry["id"]) or {}}


@app.get("/api/reasoning/{entry_id}")
def get_reasoning_entry(entry_id: str, profile: str = ""):
    # entry_id comes from the URL, so it must never be able to walk out of the
    # reasoning directory into the rest of the filesystem.
    if "/" in entry_id or "\\" in entry_id or ".." in entry_id:
        raise HTTPException(status_code=400, detail="Identificador de razonamiento inválido.")

    contents = load_reasoning_entry(_profile_storage_dir(profile), entry_id)
    if contents is None:
        raise HTTPException(status_code=404, detail="No se encontró ese razonamiento archivado.")
    return {"id": entry_id, "contents": contents}


@app.delete("/api/plans/history/{entry_id}")
def delete_history_entry(entry_id: str, profile: str = ""):
    if "/" in entry_id or "\\" in entry_id or ".." in entry_id:
        raise HTTPException(status_code=400, detail="Identificador de historial inválido.")

    entry_dir = _history_dir(_profile_storage_dir(profile)) / entry_id
    if not entry_dir.is_dir():
        raise HTTPException(status_code=404, detail="No se encontró ese plan en el historial.")

    shutil.rmtree(entry_dir, ignore_errors=True)
    return {"deleted": entry_id}


@app.post("/api/server/shutdown")
def shutdown_server(request: Request):
    """Stop the local server from the UI.

    This app runs as a personal tool on 127.0.0.1, and an athlete who started
    it by double-clicking a .bat has no obvious way to stop it. Restricted to
    loopback callers so it can never be triggered from elsewhere on a network,
    even if the host is ever bound more widely.

    v7: la comprobacion de loopback SOLA deja de bastar en cuanto esto vive en
    un servidor. Detras de nginx, `request.client.host` es el proxy -- o sea
    127.0.0.1 para TODOS los visitantes -- asi que el filtro que protegia la
    instalacion local dejaria a cualquier atleta apagar el servidor de todos.
    Se exige ademas ser operador, que es una propiedad de quien llama y no de
    por donde llego.
    """
    client_host = request.client.host if request.client else ""
    if client_host not in ("127.0.0.1", "::1", "localhost"):
        raise HTTPException(status_code=403, detail="Solo se puede apagar desde este equipo.")

    user = getattr(request.state, "user", None)
    if user is not None and not user.get("is_admin"):
        raise HTTPException(status_code=403, detail="Solo el administrador puede apagar el servidor.")

    if execution_status.get("running") or garmin_upload_status.get("running"):
        raise HTTPException(
            status_code=409,
            detail="Hay una tarea en curso (generación o subida a Garmin). Espera a que termine.",
        )

    def _stop():
        # Give the HTTP response time to reach the browser; uvicorn has no
        # in-process stop hook we can call from a request handler.
        #
        # Note this only stops the app for good when uvicorn runs as a single
        # process, which is how run_app.py (and the .bat) launch it -- verified
        # end to end: the port is released and the process exits 0. Under
        # `uvicorn --reload` the parent reloader immediately starts a fresh
        # worker, so the button appears to do nothing. That's a dev-only
        # surprise, not a bug in the shipped path.
        time.sleep(0.5)
        os._exit(0)

    threading.Thread(target=_stop, daemon=True).start()
    logger.info("Shutdown requested from %s", client_host)
    return {"stopping": True}


@app.get("/api/reports/print")
def get_printable_plan(profile: str = "", kind: str = "plan", entry: str = ""):
    """The plan laid out for paper, ready for the browser's "Save as PDF".

    `entry` selects an archived generation instead of the current one, so an
    athlete can reprint the plan they were following last month.
    """
    if kind not in ("plan", "season"):
        raise HTTPException(status_code=400, detail="Tipo de impresión inválido.")

    storage_dir = _profile_storage_dir(profile)
    if entry:
        if "/" in entry or "\\" in entry or ".." in entry:
            raise HTTPException(status_code=400, detail="Identificador de historial inválido.")
        storage_dir = _history_dir(storage_dir) / entry

    summary = {}
    summary_path = storage_dir / "summary.json"
    if summary_path.exists():
        try:
            summary = json.loads(summary_path.read_text(encoding="utf-8"))
        except json.JSONDecodeError:
            summary = {}

    athlete_name = summary.get("athlete") or profile or "Atleta"

    if kind == "season":
        phases_path = storage_dir / "season_phases.json"
        season_phases = {}
        if phases_path.exists():
            try:
                season_phases = json.loads(phases_path.read_text(encoding="utf-8"))
            except json.JSONDecodeError:
                season_phases = {}

        season_path = storage_dir / "season_plan.md"
        narrative = season_path.read_text(encoding="utf-8") if season_path.exists() else ""
        if not season_phases and not narrative:
            raise HTTPException(status_code=404, detail="No hay plan de temporada para imprimir.")

        profiles = load_profiles()
        competitions = (profiles.get(profile) or {}).get("competitions") or []
        # v6 Track 7: the goals the plan was built against, and the athlete's
        # own zones -- both were already computed and archived, and neither
        # reached the sheet the athlete prints.
        season_summary = _read_json_if_exists(storage_dir / "summary.json") or {}
        return HTMLResponse(render_printable_season(
            athlete_name=athlete_name,
            season_phases=season_phases,
            competitions=competitions,
            narrative_html=markdown_to_html(narrative),
            goals=season_summary.get("goal_realism") or [],
            prescription=season_summary.get("prescription") or {},
        ))

    weekly_path = storage_dir / "weekly_plan.json"
    if not weekly_path.exists():
        raise HTTPException(status_code=404, detail="No hay un plan generado para imprimir.")
    try:
        raw = json.loads(weekly_path.read_text(encoding="utf-8"))
        if "error" in raw:
            raise HTTPException(status_code=404, detail="El plan guardado no es válido. Genera uno nuevo.")
        payload = WeeklyPlanPayload.model_validate(raw)
    except (json.JSONDecodeError, ValueError) as exc:
        raise HTTPException(status_code=500, detail="No se pudo leer el plan guardado.") from exc

    return HTMLResponse(render_printable_plan(
        payload,
        prescription=summary.get("prescription"),
        generated_at=summary.get("analysis_date"),
        # Para poner la HORA REAL de la franja en una sesion de un dia con
        # varias, en vez de un "AM"/"PM" que miente cuando las dos son de
        # manana. Ver slot_label en printable_renderer.py.
        availability=build_availability_constraints(load_profiles().get(profile) or {}),
    ))


@app.get("/api/plans/history")
def get_plan_history(profile: str = ""):
    """Every archived generation for a profile, newest first.

    Deliberately light: enough for a list view without loading four weeks of
    workouts per entry. Use /api/plans/history/{entry_id} for the full plan.
    """
    history_dir = _history_dir(_profile_storage_dir(profile))
    if not history_dir.exists():
        return {"entries": []}

    entries = []
    for entry_dir in sorted(history_dir.iterdir(), reverse=True):
        if not entry_dir.is_dir():
            continue
        entry = _read_history_entry(entry_dir)
        if not entry:
            continue
        weekly_plan = entry.get("weekly_plan") or {}
        weeks = weekly_plan.get("weeks") or []
        entries.append({
            "id": entry["id"],
            "generated_at": entry["generated_at"],
            "kind": entry.get("kind"),
            "weeks": len(weeks),
            "sessions": sum(
                1 for week in weeks for day in week.get("days", []) if not day.get("is_rest")
            ),
            "total_cost_usd": entry["total_cost_usd"],
            "has_planning_html": entry["has_planning_html"],
        })
    return {"entries": entries}


@app.get("/api/plans/history/{entry_id}")
def get_plan_history_entry(entry_id: str, profile: str = ""):
    # entry_id comes from the URL, so it must never be able to walk out of the
    # history directory into the rest of the filesystem.
    if "/" in entry_id or "\\" in entry_id or ".." in entry_id:
        raise HTTPException(status_code=400, detail="Identificador de historial inválido.")

    entry = _read_history_entry(_history_dir(_profile_storage_dir(profile)) / entry_id)
    if entry is None:
        raise HTTPException(status_code=404, detail="No se encontró ese plan en el historial.")
    return entry


@app.get("/api/reports/history/{entry_id}")
def get_history_report(entry_id: str, profile: str = "", kind: str = "planning"):
    """The archived HTML report for one past generation."""
    if "/" in entry_id or "\\" in entry_id or ".." in entry_id or kind not in ("planning", "analysis"):
        raise HTTPException(status_code=400, detail="Petición de informe inválida.")

    report = _history_dir(_profile_storage_dir(profile)) / entry_id / f"{kind}.html"
    if not report.exists():
        raise HTTPException(status_code=404, detail="Ese informe no está en el historial.")
    return HTMLResponse(report.read_text(encoding="utf-8"))


# Serve Static files — must be LAST so API routes take priority
static_dir = Path(__file__).parent / "static"
if static_dir.exists():
    app.mount("/", StaticFiles(directory=str(static_dir), html=True), name="static")
else:
    @app.get("/")
    def read_root():
        return {"message": "Static folder not found. Please create web_app/static directory."}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8000)
