"""Qué entrenos puso ESTA app en el Garmin del atleta, y cuáles siguen ahí.

Hasta v7 la app subía y se olvidaba. El `workoutId` que Garmin devolvía se
usaba para releer el entreno y verificarlo, y después se tiraba. Eso tiene tres
consecuencias que se vieron todas el mismo día:

  1. La biblioteca de Garmin crece sin techo. Seis generaciones de prueba en
     una tarde dejaron ~40 entrenos, y el reloj avisó de que había alcanzado su
     límite de sesiones.
  2. Volver a pulsar "Subir" duplica: se crea un entreno nuevo y el calendario
     queda con dos entradas para el mismo día.
  3. La pantalla no puede decir qué está subido, porque nadie lo guardó. El
     "✅ Subido" que se veía era del sondeo de ESA subida, y se perdía al
     recargar.

Este fichero es la respuesta a las tres: un registro por perfil de cada
`workoutId` que creamos, con la sesión a la que corresponde.

**La regla de seguridad es una y no se negocia: sólo se borra un id que esté
en este registro.** Nunca se busca por nombre, nunca se listan los entrenos del
atleta para adivinar cuáles son nuestros. El atleta tiene entrenos suyos —
plantillas de Garmin Coach, cosas que se creó a mano, referencias — y ninguno
de ellos aparece aquí, así que ninguno se puede borrar por accidente. Un
borrado "inteligente" que dedujera la autoría por el nombre es exactamente el
riesgo que hace que esto dé miedo, y no hace falta: Garmin nos dice el id.
"""

from __future__ import annotations

import hashlib
import json
import logging
from datetime import date, datetime, timedelta
from pathlib import Path

logger = logging.getLogger(__name__)

FICHERO = "garmin_uploads.json"

# Cuántos días hacia atrás se conservan los entrenos ya pasados.
#
# Borrar el entreno borra también su entrada del calendario (verificado contra
# la cuenta real: se programó uno, se borró, y el calendario quedó vacío), así
# que esto decide cuánto pasado ve el atleta en su Garmin.
#
# SIETE, no catorce. Eran catorce "para comparar planificado contra hecho",
# pero esa comparación no se hace mirando el reloj: la hace `adherence.py`
# contra las ACTIVIDADES, y el expediente la guarda en `track_record.jsonl`.
# Lo único que dejaban los siete días de más era la semana pasada conviviendo
# con la vigente en el calendario del atleta, que es justo lo que confunde:
# abre el reloj el lunes y ve dos lunes.
#
# La cuenta sale a una semana en la biblioteca (~10 entrenos), y el corte es
# `< hoy - 7`, así que una sesión de hace exactamente siete días todavía
# sobrevive un día. Ese margen es a propósito: el atleta que mueve el
# entreno del domingo al lunes lo encuentra donde lo dejó.
#
# Las ACTIVIDADES que el atleta ya entrenó no se tocan nunca. Son otra cosa
# distinta en Garmin, y son su historial de verdad.
DIAS_QUE_SE_CONSERVAN = 7


def _ruta(storage_dir: Path) -> Path:
    return Path(storage_dir) / FICHERO


def cargar(storage_dir: Path) -> list[dict]:
    """Las entradas del registro. Un registro que no existe es una lista vacía,
    no un error: es el estado normal de un perfil que nunca ha subido nada."""
    ruta = _ruta(storage_dir)
    if not ruta.exists():
        return []
    try:
        datos = json.loads(ruta.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        logger.warning("No se pudo leer %s; se trata como vacío", ruta, exc_info=True)
        return []
    entradas = datos.get("entries") if isinstance(datos, dict) else datos
    return [e for e in (entradas or []) if isinstance(e, dict) and e.get("workout_id")]


def guardar(storage_dir: Path, entradas: list[dict]) -> None:
    ruta = _ruta(storage_dir)
    ruta.parent.mkdir(parents=True, exist_ok=True)
    ruta.write_text(
        json.dumps({"version": 1, "entries": entradas}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )


def clave(fecha: str, franja: str | None) -> str:
    """Identifica una SESIÓN, no un día.

    Un lunes con fuerza a las 05:30 y bici a las 17:00 son dos sesiones
    distintas, cada una con su entreno en Garmin. Sin la franja, subir la
    segunda borraría la primera."""
    return f"{fecha}|{franja or 'unica'}"


def por_sesion(entradas: list[dict]) -> dict[str, dict]:
    return {clave(e.get("date", ""), e.get("session_slot")): e for e in entradas}


def huella(day) -> str:
    """Un resumen corto de LO QUE la sesión pide hacer.

    Existe para poder responder "¿lo que está en el reloj sigue siendo esto?".
    Sin ello, regenerar una semana dejaba cada día marcado como «Subido»
    -- las claves son fecha|franja y la semana nueva ocupa las mismas fechas --
    mientras en el reloj seguía el entreno de la generación ANTERIOR. El
    atleta habría salido a hacer un entreno que su plan ya no decía.

    Se resume la prescripción, no el texto: el propósito o la adaptación
    pueden reescribirse sin que cambie ni un minuto de lo que se corre.
    """
    def paso(p):
        return [p.step_type, p.duration_type, p.duration_value, p.target_type,
                p.target_zone, p.target_low, p.target_high, p.repeat_count,
                getattr(p, "leg_sport", None), getattr(p, "stroke", None),
                [paso(h) for h in (p.substeps or [])]]

    partes = [day.sport, day.discipline, day.focus,
              day.total_duration_minutes, [paso(p) for p in (day.steps or [])]]
    # Lo que cambió el ATLETA (2026-10-23): la fuerza va al reloj desde su bloque de ejercicios, no desde
    # `steps`, y una nota sólo cambia el texto del paso; sin esto, cambiar un ejercicio o escribir «crol con
    # pull buoy» seguía saliendo «En tu reloj». Sólo en sesiones que él cambió, para que la huella de todo lo
    # que ya está en los relojes (y nadie tocó) no cambie y no aparezca como «sin enviar».
    editado = getattr(day, "edited", None) or {}
    if isinstance(editado, dict) and (editado.get("adjusted") or editado.get("added")):
        from services.ai.ajuste_de_fuerza import firma

        bloque = day.strength_block.model_dump() if getattr(day, "strength_block", None) else None
        partes.append([firma(bloque), [_textos(p) for p in (day.steps or [])]])
    crudo = json.dumps(partes, ensure_ascii=False, sort_keys=True, default=str)
    return hashlib.sha1(crudo.encode("utf-8")).hexdigest()[:16]


def _textos(p) -> list:
    return [p.description, [_textos(h) for h in (p.substeps or [])]]


# El test de umbral no es una sesión del plan (services/ai/test_de_umbral.py):
# se programa aparte, y ni subir la semana, ni regenerarla, ni borrarla lo
# retira del reloj. Y se conserva más: hasta que el atleta lo hace y lo analiza
# hace falta su `workout_id`, que es lo único que dice qué actividad es el test.
TIPO_TEST = "test"
DIAS_QUE_SE_CONSERVA_EL_TEST = 28


def es_test(entrada: dict) -> bool:
    return (entrada or {}).get("kind") == TIPO_TEST


def huerfanas(entradas: list[dict], fechas_del_plan, claves_vivas) -> list[dict]:
    """Lo que el plan recién subido ya no tiene: sesiones que desaparecieron al regenerarlo.

    Nunca un test: no pertenece al plan, así que no puede "desaparecer" de él.
    """
    fechas, vivas = set(fechas_del_plan or ()), set(claves_vivas or ())
    return [e for e in entradas
            if not es_test(e) and e.get("date") in fechas
            and clave(e["date"], e.get("session_slot")) not in vivas]


def anotar(entradas: list[dict], *, fecha: str, franja: str, workout_id: int,
           nombre: str, deporte: str, huella_sesion: str = "",
           adoptada: bool = False, tipo: str = "") -> list[dict]:
    """Deja constancia de una subida, reemplazando la anterior de esa sesión.

    `adoptada` distingue CÓMO llegó aquí el id, y es lo único que separa las
    dos maneras de que un entreno entre en la lista de lo borrable:

      - Sin la marca (el caso normal): el id lo devolvió Garmin cuando ESTA
        app creó el entreno. Es nuestro por definición, no por deducción.
      - Con la marca: lo emparejó `scripts/limpiar_garmin.py` por el nombre,
        recogiendo la deuda anterior a que existiera este registro. Eso es una
        inferencia, y la única puerta por la que algo del atleta podría acabar
        en un id que luego se borra.

    Guardarlo cuesta un campo y responde para siempre a "¿de dónde salió este
    id?" sin depender de que alguien recuerde la conversación en que se decidió.
    """
    k = clave(fecha, franja)
    resto = [e for e in entradas if clave(e.get("date", ""), e.get("session_slot")) != k]
    entrada = {
        "date": fecha,
        "session_slot": franja or "unica",
        "workout_id": int(workout_id),
        "name": nombre,
        "sport": deporte,
        "fingerprint": huella_sesion,
        "uploaded_at": datetime.now().isoformat(timespec="seconds"),
        # La fecha en la que está programado EN EL RELOJ (Beta 3.0, F5). Hoy coincide con
        # `date`; deja de coincidir cuando el atleta mueve la sesión en la app y todavía
        # no se ha vuelto a subir. Ver `mover` y `estado_de_la_sesion`.
        "scheduled_date": fecha,
    }
    if adoptada:
        entrada["adopted"] = True
    if tipo:
        entrada["kind"] = tipo
    return [*resto, entrada]


def olvidar(entradas: list[dict], workout_ids) -> list[dict]:
    fuera = {int(w) for w in workout_ids}
    return [e for e in entradas if int(e["workout_id"]) not in fuera]


def caducadas(entradas: list[dict], hoy: date | None = None,
              dias: int = DIAS_QUE_SE_CONSERVAN) -> list[dict]:
    """Las entradas de sesiones ya pasadas que toca retirar de Garmin.

    Sólo mira la FECHA de la sesión, nunca cuándo se subió: lo que sobra en la
    biblioteca del reloj es el entreno de un martes que ya pasó, se hiciera o
    no. Una fecha ilegible se conserva -- ante la duda, no se borra."""
    hoy = hoy or date.today()
    viejas = []
    for e in entradas:
        # El test se conserva más: hasta que el atleta lo hace y lo analiza, su
        # `workout_id` es lo único que dice qué actividad es el test.
        corte = hoy - timedelta(days=max(dias, DIAS_QUE_SE_CONSERVA_EL_TEST) if es_test(e) else dias)
        try:
            if date.fromisoformat(e.get("date", "")) < corte:
                viejas.append(e)
        except ValueError:
            logger.warning("Entrada del registro con fecha ilegible, se conserva: %r", e)
    return viejas


def para_fechas(entradas: list[dict], fechas) -> list[dict]:
    """Las entradas cuyas sesiones caen en `fechas` (para retirar una semana
    concreta, por ejemplo al borrar o regenerar la generación actual).

    Sin el test de umbral: no es de la semana, y borrar o regenerar el plan no
    puede quitarle al atleta el test que programó. Retirarlo todo (el reinicio,
    borrar la cuenta) lee el registro entero y sí se lo lleva."""
    quiero = set(fechas or ())
    return [e for e in entradas if e.get("date") in quiero and not es_test(e)]


# --- Mover y saber qué lleva el reloj (Beta 3.0, F5) --------------------------------
#
# Mover una sesión en la app (F6) no toca el reloj en el momento: Garmin no reprograma un
# entreno sin cambiarle el nombre (que lleva la fecha), así que se BORRA y se vuelve a
# CREAR en la siguiente subida. Hasta entonces el registro tiene que seguir sabiendo dos
# cosas: qué id es de esa sesión (para reemplazarlo y no dejar dos) y en qué fecha está
# de verdad en el reloj (para decir «cambios sin enviar» y no un falso «subido»).


def fecha_programada(entrada: dict) -> str:
    """Dónde está en el reloj. Las entradas anteriores a la F5 no la guardaban: era `date`."""
    return (entrada or {}).get("scheduled_date") or (entrada or {}).get("date", "")


def estado_de_la_sesion(entrada: dict | None, day) -> str:
    """`en_el_reloj`, `cambios_sin_enviar` o `sin_subir`, para la sesión `day` del plan.

    La huella no mira la fecha (ver `huella`): una sesión movida tiene la misma huella y
    otra fecha, y sin comparar la fecha programada saldría «en el reloj» estando el
    entreno en el día viejo.
    """
    if not entrada:
        return "sin_subir"
    if fecha_programada(entrada) != day.date:
        return "cambios_sin_enviar"
    if entrada.get("fingerprint") and entrada["fingerprint"] != huella(day):
        return "cambios_sin_enviar"
    return "en_el_reloj"


def mover(entradas: list[dict], cambios: dict[str, str]) -> list[dict]:
    """Cambia la clave de las sesiones movidas: `{"2026-10-13|unica": "2026-10-15|unica"}`.

    Nunca toca `workout_id` ni `scheduled_date`: la siguiente subida encuentra el id en
    su clave nueva, lo reemplaza (borra el viejo y crea el nuevo) y sólo entonces cambia
    la fecha programada. Si esa subida falla, el id sigue aquí y se puede retirar.

    Los cambios se aplican A LA VEZ, para que un intercambio (martes ↔ jueves) no pise una
    sesión con la otra. Una clave nueva que ya estuviera ocupada por una sesión que NO se
    mueve sería perder un id: eso no se hace y se lanza `ValueError`.
    """
    origen = set(cambios)
    ocupadas = {clave(e.get("date", ""), e.get("session_slot")) for e in entradas} - origen
    choque = ocupadas & set(cambios.values())
    if choque:
        raise ValueError(f"Mover pisaría sesiones que siguen en su sitio: {sorted(choque)}")
    salida = []
    for e in entradas:
        k = clave(e.get("date", ""), e.get("session_slot"))
        if k in cambios and not es_test(e):
            fecha, _, franja = cambios[k].partition("|")
            e = {**e, "date": fecha, "session_slot": franja or "unica",
                 "scheduled_date": fecha_programada(e)}
        salida.append(e)
    return salida
