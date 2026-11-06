"""Request/response shapes for the bridge. The PHP backend sends athlete data
pulled from MySQL; the bridge feeds it to PaginaLocal and returns the result.
"""
from __future__ import annotations

from typing import Any, Literal

from pydantic import BaseModel, Field


class EncryptCredentialRequest(BaseModel):
    """Un secreto que PHP quiere guardar sin poder leerlo.

    La clave de cifrado vive solo en este servicio, asi que PHP manda el texto
    en claro UNA vez -- cuando el atleta lo escribe -- y recibe un bloque opaco
    que es lo unico que llega a MySQL.
    """
    secret: str


class GarminImportRequest(BaseModel):
    garmin_email: str
    garmin_password: str


class ThresholdTestRequest(BaseModel):
    """El test de umbral: subirlo al reloj del atleta, o leer la actividad que hizo con él (sin IA)."""
    user_id: int
    garmin_email: str
    garmin_password: str
    sport: str
    action: str = "analizar"   # "subir" | "analizar"
    fecha: str = ""            # YYYY-MM-DD, sólo para "subir"


class GarminSyncRequest(BaseModel):
    """Cuándo sincronizó su reloj por última vez (el aviso del check-in). Sin IA."""
    user_id: int
    garmin_email: str
    garmin_password: str


class SessionStatusRequest(BaseModel):
    """✓/✗ de cada sesión desde Garmin, sin IA (2026-10-21)."""
    user_id: int
    garmin_email: str
    garmin_password: str
    weekly_plan: dict
    today: str = ""
    threshold_pace_sec_per_km: float | None = None
    force: bool = False


class CoachChatRequest(BaseModel):
    # Athlete profile in PaginaLocal's UserProfileModel shape (the PHP
    # ProfileEntity::toArray() output maps 1:1 to it).
    profile: dict[str, Any]
    user_id: int
    message: str
    chat_history: list[dict[str, str]] = Field(default_factory=list)

    # Optional context materialised into the throwaway storage dir so the
    # coach "knows" them. All come from MySQL.
    notebook: list[dict[str, Any]] = Field(default_factory=list)
    checkins: list[dict[str, Any]] = Field(default_factory=list)
    weekly_plan: dict[str, Any] | None = None
    season_plan_md: str | None = None
    season_phases: dict[str, Any] | list[Any] | None = None
    # De aqui salen DOS secciones del contexto del entrenador: sus numeros
    # reales (`prescription`: zonas de FC por deporte, ritmos umbral, bandas
    # por intensidad) y su adherencia al plan anterior.
    #
    # Sin esto el entrenador las leia de `summary.json`, o sea de lo que la
    # ultima generacion hubiera dejado en el disco del servicio. Funcionaba
    # por accidente y dejaba de funcionar en cuanto ese directorio se limpiaba
    # -- exactamente el mismo fallo que ya se corrigio para el plan semanal, y
    # por la misma razon: la fuente es MySQL.
    summary: dict[str, Any] | None = None

    # Que entrenador eligio el atleta (juan | fernanda | ben | natasha). Decide
    # el ESTILO de las respuestas del chat y nada mas: no toca los
    # planificadores ni al asistente de salud. El catalogo con lo que cada uno
    # anade al system prompt esta en services/ai/coach_chat.py, que es tambien
    # quien descarta un id que no conozca.
    coach_id: str | None = None

    # Lo que levantó (Database/026), para que el entrenador pueda hablar de su fuerza
    # con datos. Vacío = como antes.
    strength_logs: list[dict[str, Any]] = Field(default_factory=list)


class GarminUploadRequest(BaseModel):
    profile: dict[str, Any]
    user_id: int
    garmin_password: str
    weekly_plan: dict[str, Any]
    summary: dict[str, Any] | None = None
    dates: list[str] | None = None   # None = the whole plan


class GarminRetireRequest(BaseModel):
    """Sacar del reloj del atleta los entrenos que esta app le puso.

    Se manda al borrar el plan. La contrasena viaja igual que en la subida --
    la escribe el atleta en ese momento y no se guarda en ningun sitio -- y el
    `weekly_plan` viene con la peticion porque hay que saber QUE fechas se
    borran antes de que MySQL las pierda.
    """
    profile: dict[str, Any]
    user_id: int
    garmin_password: str
    weekly_plan: dict[str, Any] | None = None
    # Todo lo que el registro de subidas de este atleta tiene anotado, no sólo
    # las fechas del plan. Para el reinicio de la Beta 2.0: borrar las cuentas
    # sin retirar antes los entrenos los dejaba en el reloj, y después ya no hay
    # registro que diga cuáles eran nuestros.
    todo: bool = False


class ChartsRequest(BaseModel):
    profile: dict[str, Any]
    user_id: int
    weekly_plan: dict[str, Any] | None = None
    # If given, does 3 cheap Garmin calls to refresh the fitness/load/recovery snapshot.
    garmin_password: str | None = None
    # Para el progreso (Beta 3.0, F8): una molestia del check-in congela la racha, y las
    # medallas de fuerza salen de lo que registró. Vacíos, el progreso sale igual sin ellas.
    checkins: list[dict[str, Any]] = Field(default_factory=list)
    strength_logs: list[dict[str, Any]] = Field(default_factory=list)


class StrengthRoutineRequest(BaseModel):
    """La rutina de gimnasio que el atleta pegó, para ordenarla (Beta 2.1, S4). No guarda nada."""
    user_id: int
    profile: dict[str, Any]
    text: str
    # "por_linea" | "uno_a_uno" | "texto": cómo agrupa el atleta, dicho en el formulario.
    grouping: str = "texto"
    rest_between_rounds_s: int | None = None


class StrengthProgramRequest(BaseModel):
    """El programa de gimnasio que escribe el coach (Beta 2.1, S6). No guarda nada:
    el atleta lo revisa y lo confirma como si lo hubiera pegado él."""
    user_id: int
    profile: dict[str, Any]


class GenerateSeasonRequest(BaseModel):
    profile: dict[str, Any]
    # Garmin password is entered at generation time, never stored in MySQL.
    garmin_password: str
    user_id: int
    notebook: list[dict[str, Any]] = Field(default_factory=list)
    checkins: list[dict[str, Any]] = Field(default_factory=list)
    # `summary.json` acumulado de las generaciones anteriores. Lleva claves de
    # LAS DOS -- la temporada aporta goal_realism y volume_baseline, la semana
    # aporta adherence y prescription -- y cada corrida MEZCLA lo suyo sobre lo
    # que ya hubiera. Sin esto el espejo se quedaba sin fichero y la mezcla
    # partia de cero: generar una semana borraba el `goal_realism` de la
    # temporada, que es lo que el informe del macrociclo imprime al pie.
    summary: dict[str, Any] | None = None
    # EL MACROCICLO QUE ESTE PLAN VA A REEMPLAZAR.
    #
    # `GenerateWeekRequest` los declara desde siempre; este no, y por eso el
    # bloque "Macrociclo ANTERIOR" (services/ai/previous_season.py) llegaba
    # SIEMPRE VACIO en la app multiusuario: el worker materializa el espejo
    # antes de generar, y `write_season_plan(dir, None, None)` BORRA los dos
    # ficheros -- el espejo refleja lo que MySQL manda, y MySQL no mandaba
    # nada. Cuando el servidor iba a leer el plan viejo para contarselo al
    # planificador, ya no estaba.
    #
    # Es la quinta vez que aparece este fallo con esta forma exacta: un campo
    # que el consumidor necesita, que otra ruta si envia, y que esta no.
    season_plan_md: str | None = None
    season_phases: dict[str, Any] | list[Any] | None = None
    # Lo que el atleta pidio cambiar al rechazar el plan. Viaja al
    # `planning_context` de la COPIA del perfil que recibe la tarea, nunca al
    # perfil guardado: hasta v7 se acumulaba en un campo que nadie limpiaba, y
    # un ajuste pedido una vez dirigia en silencio todos los planes futuros.
    adjustment: str = ""

    # La zona horaria del atleta (IANA, p.ej. "America/Guatemala"), leida del
    # navegador. El worker la aplica a SU proceso antes de correr nada, y con
    # eso los mas de sesenta `date.today()` del pipeline pasan a decir el dia
    # que el atleta ve en su reloj y no el del servidor. Vacio = la del
    # servidor, que es como se comportaba antes.
    timezone: str = ""
    # El dia de HOY para el atleta. Se usa para la ventana de antelacion, que
    # se resuelve en el servicio y no en el worker.
    today: str = ""


class GenerateWeekRequest(BaseModel):
    profile: dict[str, Any]
    garmin_password: str
    user_id: int
    # From a previous generate-season call, stored by PHP in MySQL.
    season_plan_md: str
    season_phases: dict[str, Any] | list[Any] | None = None
    # The current weekly_plan.json (for adherence + "where the next week starts").
    previous_weekly_plan: dict[str, Any] | None = None
    notebook: list[dict[str, Any]] = Field(default_factory=list)
    checkins: list[dict[str, Any]] = Field(default_factory=list)
    # Lo que el atleta levantó de verdad en el gimnasio (Database/026): una
    # entrada por sesión con sus ejercicios, kilos y RPE. Vacío mientras no
    # registre nada, y entonces la fuerza se comporta como hasta ahora.
    strength_logs: list[dict[str, Any]] = Field(default_factory=list)
    # `summary.json` acumulado de las generaciones anteriores. Lleva claves de
    # LAS DOS -- la temporada aporta goal_realism y volume_baseline, la semana
    # aporta adherence y prescription -- y cada corrida MEZCLA lo suyo sobre lo
    # que ya hubiera. Sin esto el espejo se quedaba sin fichero y la mezcla
    # partia de cero: generar una semana borraba el `goal_realism` de la
    # temporada, que es lo que el informe del macrociclo imprime al pie.
    summary: dict[str, Any] | None = None
    # EL MACROCICLO QUE ESTE PLAN VA A REEMPLAZAR.
    #
    # `GenerateWeekRequest` los declara desde siempre; este no, y por eso el
    # bloque "Macrociclo ANTERIOR" (services/ai/previous_season.py) llegaba
    # SIEMPRE VACIO en la app multiusuario: el worker materializa el espejo
    # antes de generar, y `write_season_plan(dir, None, None)` BORRA los dos
    # ficheros -- el espejo refleja lo que MySQL manda, y MySQL no mandaba
    # nada. Cuando el servidor iba a leer el plan viejo para contarselo al
    # planificador, ya no estaba.
    #
    # Es la quinta vez que aparece este fallo con esta forma exacta: un campo
    # que el consumidor necesita, que otra ruta si envia, y que esta no.
    season_plan_md: str | None = None
    season_phases: dict[str, Any] | list[Any] | None = None
    # Lo que el atleta pidio cambiar al rechazar el plan. Viaja al
    # `planning_context` de la COPIA del perfil que recibe la tarea, nunca al
    # perfil guardado: hasta v7 se acumulaba en un campo que nadie limpiaba, y
    # un ajuste pedido una vez dirigia en silencio todos los planes futuros.
    adjustment: str = ""
    redo: bool = False
    # "Generar de todas formas": el atleta ya vio por que todavia no tocaba y
    # decidio seguir. Salta la ventana de antelacion, nada mas.
    force: bool = False

    # La zona horaria del atleta (IANA, p.ej. "America/Guatemala"), leida del
    # navegador. El worker la aplica a SU proceso antes de correr nada, y con
    # eso los mas de sesenta `date.today()` del pipeline pasan a decir el dia
    # que el atleta ve en su reloj y no el del servidor. Vacio = la del
    # servidor, que es como se comportaba antes.
    timezone: str = ""
    # El dia de HOY para el atleta. Se usa para la ventana de antelacion, que
    # se resuelve en el servicio y no en el worker.
    today: str = ""
    # Desde que dia arranca la semana. Solo se atiende en la PRIMERA, cuando
    # no hay una anterior de la que continuar, y solo acepta hoy o manana: a
    # las 8 de la tarde escribirle entrenos para hoy es escribirle un dia que
    # ya se le fue, y a las 11 de la manana puede ser lo que quiere.
    start_date: str = ""
    # El atleta pulsó "Saltar" en el check-in: la semana se genera sin ninguno,
    # tampoco el que contestó hace un rato para otra generación.
    skip_checkin: bool = False
    # «Generar mi semana automáticamente» (Database/027): la lanza el script de las
    # semanas automáticas, sin nadie delante. Sin preguntas del coach, y el cerebro
    # sabe que el atleta no dijo nada de esta semana (Backend AutoWeekService).
    automatic: bool = False
    # El tope de versiones de una misma semana (D2, Beta 3.0 F2): los inicios de las
    # semanas que este atleta ya generó y cuentan (no automáticas, sin error), de
    # `athlete_generations`. None = PHP sin la migración 028, y entonces no hay tope.
    versiones_previas: list[str] | None = None


class NotebookReorganizeRequest(BaseModel):
    """«Revisa mi cuaderno» (Beta 3.0, F9): sus anotaciones vigentes, su perfil y, en la
    segunda vuelta, lo que respondió a las preguntas del coach. No guarda nada."""
    user_id: int
    profile: dict[str, Any] = Field(default_factory=dict)
    entries: list[dict[str, Any]] = Field(default_factory=list)
    answers: list[dict[str, Any]] = Field(default_factory=list)


class PlanEditRequest(BaseModel):
    """Cambiar una sesión desde la app, sin IA (Beta 3.0, F6). Sólo calcula: guarda PHP."""
    user_id: int
    weekly_plan: dict[str, Any]
    op: str = "mover"
    session_key: str
    to_date: str = ""
    modo: str = "intercambiar"
    today: str = ""
    # La rejilla del perfil (`sport_days`), para avisar de un día que su tabla no da.
    sport_days: dict[str, Any] | None = None
    # F7: ajustar (los elementos del editor), las bandas del atleta para las plantillas y
    # los avisos, y la versión del coach (para volver a ella y para el aviso de ±30 %).
    elementos: list[dict[str, Any]] | None = None
    prescripcion: dict[str, Any] | None = None
    original: dict[str, Any] | None = None
    # 2026-10-23: la fuerza de la semana (grupos del editor de fuerza) y un entreno que añade el atleta.
    grupos: list[dict[str, Any]] | None = None
    deporte: str = ""
    titulo: str = ""


class LedgerMoveRequest(BaseModel):
    """Lleva los ids del registro de subidas a las claves nuevas (Beta 3.0, F6)."""
    user_id: int
    cambios: dict[str, str]


class GarminSessionsRequest(BaseModel):
    """Qué lleva el reloj de cada sesión del plan (Beta 3.0, F5). Sólo lee el registro."""
    user_id: int
    weekly_plan: dict[str, Any] | None = None


class SupportBundleRequest(BaseModel):
    """«Pedir ayuda» (Beta 3.0, F3): lo que PHP sabe y el AiService no. Sin contraseñas."""
    user_id: int
    categoria: str = "otra"
    session_key: str | None = None
    # Ya con lista blanca en PHP, y otra vez aquí (diagnostico_de_ayuda.CLAVES_DE_PHP).
    php: dict[str, Any] = Field(default_factory=dict)
    client_errors: list[Any] = Field(default_factory=list)
    weekly_plan: dict[str, Any] | None = None
    summary: dict[str, Any] | None = None


class ReportRequest(BaseModel):
    """Un informe imprimible, renderizado en el servidor.

    Las tres plantillas (plan semanal, macrociclo con su calendario de semanas,
    informe de salud) existen desde v6/v7 en
    `services/ai/rendering/templates/`, con sus tests en `test_printable.py`,
    y la app multiusuario no tenia por donde pedirlas: la vista de Informes
    rehacia a mano versiones peores en Vue. El informe de analisis SI iba por
    el camino bueno -- se guarda como HTML en MySQL -- asi que la tuberia ya
    existia y solo faltaba esta puerta.

    Todo lo que hace falta llega en la peticion: MySQL es la fuente y el
    espejo del disco es un espejo, nunca al reves.
    """

    user_id: int
    # "analysis": el informe de analisis, re-renderizado desde la sintesis de la
    # ultima traza de temporada. Antes solo se servia el HTML guardado al
    # generar, asi que un cambio de diseño no llegaba hasta el macro siguiente.
    # Hubo un cuarto, "medical". Se retiro con la 017: leia
    # `doctor_performance_report`, la columna que esa migracion borra, asi que
    # en produccion solo podia devolver 404. La plantilla y su render siguen
    # vivos por si el asistente de salud vuelve.
    kind: Literal["plan", "season", "analysis"]
    profile: dict[str, Any]
    weekly_plan: dict[str, Any] | None = None
    season_plan_md: str | None = None
    season_phases: dict[str, Any] | None = None
    summary: dict[str, Any] | None = None
    generated_at: str | None = None


class PlanDeletedRequest(BaseModel):
    """PHP acaba de borrar un plan en MySQL; hay que limpiar lo que quede aqui.

    Va aparte de `garmin-retire` porque son cosas distintas y se piden en
    momentos distintos: retirar del reloj SOLO ocurre si el atleta lo pidio y
    hay contrasena, mientras que la traza sobra siempre que el plan se fue.
    Colgarlo del mismo endpoint habria dejado la traza en disco justo en el
    caso mas comun -- borrar sin tocar Garmin.
    """

    user_id: int
    # 'week' | 'season' | 'all'. Se borra SOLO el razonamiento de ese alcance:
    # una traza de temporada no describe la semana, y al reves tampoco.
    scope: str = "all"


class CoachAnswersRequest(BaseModel):
    """Las respuestas del atleta a las preguntas que su coach le hizo a mitad de generacion.

    Una por pregunta y en el mismo orden; una cadena vacia es "no quiero
    contestar esta", y el coach sigue con los datos.
    """

    user_id: int
    job_id: str
    answers: list[str] = []
