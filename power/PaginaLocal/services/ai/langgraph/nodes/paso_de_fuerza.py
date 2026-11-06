"""La fuerza de la semana sale del programa del atleta, no se reinventa (Beta 2.1, S5).

En las 8 semanas archivadas de José y Jessie los ~12 ejercicios de gimnasio se
barajaban distinto en CADA regeneración de la misma semana, y eran siempre
mancuernas y bandas a RIR 3-4 porque el modelo no sabía qué tenía el atleta ni qué
buscaba. Con una rutina confirmada (S4) eso deja de ser una decisión semanal:

1. **El código elige qué sesión va cada día** mirando la semana de resistencia que
   la rodea (``colocar_las_sesiones``): la que carga piernas se aleja de la calidad y
   de la tirada larga, no se repite el mismo énfasis en 48 h, y entre las que caben se
   usa la menos hecha. Si NO cabe lejos, se coloca igual en el mejor sitio y se le dice
   al atleta, con la recomendación de añadir un día y el porqué: quitarle su sesión de
   pierna porque no tenemos dónde ponerla sería decidir por él.
2. **El modelo propone CAMBIOS** sobre esa sesión, con su motivo, y una dosis que puede
   bajar y nunca subir: ve la semana que la rodea, la fase, el afinamiento o la vuelta
   de una carrera y las molestias declaradas. Y puede AÑADIR uno o dos ejercicios
   cuando hay algo declarado que lo pide -- una molestia que necesita su trabajo, o lo
   que busque la fase --, de la lista cerrada y sólo para esa semana: el programa que
   el atleta confirmó no se toca.
3. **El código construye el bloque** con los ejercicios del programa y los cambios
   aceptados, así que los nombres no derivan y lo que el atleta confirmó se respeta.

Lo que este paso NO hace: subir la dosis, meter ejercicios que el atleta no tiene con
qué hacer, ni decidir cuántos días de fuerza hay (eso lo decide la semana, con la
tabla del atleta). Un atleta sin rutina confirmada no pasa por aquí y su semana se
genera exactamente como hasta hoy.
"""

from __future__ import annotations

import logging
import math
from collections.abc import Awaitable, Callable
from datetime import date, timedelta

from pydantic import BaseModel, Field

from services.ai import exercise_catalog
from services.ai.exercise_catalog import render_catalogo_para_prompt
from services.ai.langgraph.schemas.plan_outputs import (
    StrengthBlock,
    StrengthExercise,
    StrengthGroup,
    WorkoutDay,
    WorkoutStep,
)

logger = logging.getLogger(__name__)

# El ÉNFASIS de una sesión: qué carga. Es lo que decide DÓNDE cae en la semana de
# un atleta de resistencia, y por eso viaja con cada sesión del programa.
PIERNA, TORSO, COMPLETO, CORE = "pierna", "torso", "completo", "core"
ENFASIS = (PIERNA, TORSO, COMPLETO, CORE)
# Las que dejan las piernas cargadas. La economía de carrera cae durante unas
# horas después de fuerza de tren inferior (revisión de entrenamiento concurrente,
# 2024), así que son las únicas cuya colocación importa de verdad.
CARGAN_PIERNA = (PIERNA, COMPLETO)

# Las sesiones de resistencia que no se pueden tocar: la calidad y la tirada larga.
SESIONES_CLAVE = ("vo2max", "tempo_threshold", "repeticiones", "long_run", "race_day", "hill_repeats")

# La dosis de la semana. Baja series, nunca las sube: el programa es el techo.
DOSIS = ("normal", "reducida", "minima", "ninguna")
_FACTOR_DE_DOSIS = {"normal": 1.0, "reducida": 0.7, "minima": 0.5}
_COMO_SE_DICE = {
    "normal": "",
    "reducida": "Dosis reducida esta semana",
    "minima": "Dosis mínima esta semana",
}


# ── Lo que el modelo puede escribir ───────────────────────────────────────────


class CambioDeEjercicio(BaseModel):
    exercise_id: str = Field(..., description="El id del ejercicio del programa que se cambia.")
    sets: int | None = Field(None, description="Series nuevas, sólo si cambian.")
    reps: str = Field("", description="Repeticiones nuevas, sólo si cambian.")
    load: str = Field("", description="Carga o esfuerzo (RIR/RPE). Kilos SÓLO si el atleta los dio.")
    replace_with: str = Field("", description="Ejercicio que lo sustituye, con el material que el atleta tiene.")
    skip: bool = Field(False, description="True para quitarlo esta semana.")
    reason: str = Field(..., description="Por qué, para el atleta. Sin motivo no se aplica el cambio.")


class EjercicioAnadido(BaseModel):
    """Un ejercicio que NO está en su programa y esta semana sí toca."""

    name: str = Field(..., description="Nombre del ejercicio, de la lista cerrada que se te da.")
    sets: int = Field(..., description="Series.")
    reps: str = Field(..., description="Repeticiones o tiempo: '10 por lado', '40 s'.")
    load: str = Field("", description="Carga por esfuerzo (RIR/RPE) o peso corporal. Kilos NO.")
    video_key: str = Field("", description="La clave de la lista cerrada; vacío si no está en ella.")
    reason: str = Field(..., description="Por qué ESTA semana, para el atleta. Sin motivo no se añade.")


class FuerzaDelDia(BaseModel):
    date: str = Field(..., description="YYYY-MM-DD de un día de fuerza de esta semana.")
    # Dos sesiones de fuerza el mismo día se distinguen por su franja. Hasta el
    # 2026-09-23 todo se emparejaba por fecha y la segunda pisaba a la primera.
    session_slot: str = Field("", description="La franja que se te da junto a la fecha (am, pm...), tal cual.")
    session_id: str = Field(..., description="La sesión del programa que toca ese día.")
    dose: str = Field("normal", description="normal | reducida | minima | ninguna.")
    dose_reason: str = Field("", description="Por qué esa dosis, si no es normal.")
    # El texto del día lo escribe quien sabe QUÉ sesión cae ese día. La decisión semanal
    # lo escribía antes de saberlo, y a la Upper B del atleta 2 (2026-09-28) le dijo
    # "baja series de pierna" en un día sin pierna.
    purpose: str = Field("", description="UNA frase: para qué sirve ESTA sesión esta semana.")
    adaptation: str = Field("", description="UNA frase: qué hacer si no es su día, con los ejercicios de ESTA sesión.")
    changes: list[CambioDeEjercicio] = Field(default_factory=list)
    # Lo que su programa no tiene y esta semana hace falta: una molestia declarada
    # que pide su prehab, o lo que la fase busca. Vacío es la respuesta normal.
    additions: list[EjercicioAnadido] = Field(default_factory=list)


class AjusteDeFuerza(BaseModel):
    days: list[FuerzaDelDia] = Field(default_factory=list)


AJUSTE_SYSTEM_PROMPT = """Eres el entrenador que ajusta la fuerza de ESTA semana sobre el programa que el atleta ya hace y confirmó.

Reglas:
- **El programa manda.** Devuelves CAMBIOS, no una sesión nueva. Sin nada que cambiar, devuelves el día con su sesión, dosis normal y `changes` vacío, y eso es una respuesta correcta y frecuente.
- **Cada cambio lleva su motivo**, escrito para el atleta. Un cambio sin motivo no se aplica.
- **La dosis sólo baja**: `normal`, `reducida`, `minima` o `ninguna`, con su motivo cuando no es normal. Nunca añades series ni ejercicios para "aprovechar" una semana fácil.
- **Mantener no es aflojar.** Con la dosis bajada se quitan SERIES, no carga: los kilos o el RIR/RPE de cada ejercicio se quedan como en su programa, porque lo que conserva la fuerza con poco volumen es seguir levantando pesado (Rønnestad 2011). La carga sólo baja por una molestia declarada o por lo que registró.
- **Con la dosis bajada no escribas un cambio de series por ejercicio**: la dosis ya las baja todas. Escribe sólo lo que la dosis no hace (sustituir, quitar, otras repeticiones, otra carga), cada motivo en una frase corta.
- **Kilos, sólo los que el atleta escribió.** Si no los tienes, la carga se dice por esfuerzo (RIR o RPE). Si tienes lo que LEVANTÓ registrado, ahí sí puedes proponer subir o bajar la carga, con su motivo y partiendo de ese número.
- **Una molestia declarada cambia el ejercicio que carga ESA estructura**, no la sesión entera, y se dice en el motivo. Una sesión de tren superior no es rehabilitación por una molestia de pierna.
- **Dentro de su tiempo de gimnasio puedes AÑADIR uno o dos ejercicios** (`additions`), de la lista cerrada de abajo y SÓLO para esta semana (su programa no se toca), cada uno con su motivo. Qué toca lo decides tú con lo que tienes delante:
  - **Prehab o rehab**: sólo si declaró una molestia, y para la estructura que le molesta. Un atleta sano no recibe prehab que no pidió.
  - **Pliometría**, si corre: pocos saltos cortos y de calidad mejoran la economía de carrera (Saunders 2006; Barnes y Kilding 2015). Si esta semana toca y cuánto lo decides por la fase y por su semana: no en el afinamiento de una carrera, no el día antes de su calidad y no por sistema todas las semanas.
  - Lo que busca la fuerza en esta fase, si la fase lo dice.
  Si nada de eso lo pide esta semana, no añades nada.
- **Sólo lo que el atleta puede hacer con su equipo.** Un ejercicio de sustitución que necesita material que no tiene no sirve.
- **La dosis normal es la norma; bajarla necesita una razón de verdad**: el afinamiento o la vuelta de una carrera, una molestia declarada, su recuperación persistentemente por debajo de su normal, o una semana de descarga. Que haya una sesión de calidad el día antes o una tirada larga dos o tres días después NO es razón: dónde cae cada sesión ya lo eligió el código lejos de su calidad, y con esa separación entrenar fuerza apenas resta a la resistencia ni a la hipertrofia (Schumann 2022). Tampoco el tiempo: su sesión dura lo que dura en su programa. Es su rutina, y recortársela sin motivo es quitarle lo que pidió.
- Mira lo que hay alrededor (calidad, tirada larga, carreras, cómo respondió la semana anterior) para decidir si hace falta cambiar un ejercicio concreto, no para bajar la dosis por sistema.
- No decides cuántos días de fuerza hay ni qué días son: eso ya está decidido con la tabla del atleta.
- **`purpose` y `adaptation`, una frase cada uno** y sobre ESTA sesión: para qué sirve esta semana y qué hacer si no es su día. No digas quién le pidió la fuerza ni cuántas sesiones lleva: eso ya lo sabe."""

_CATALOGO_PARA_ANADIR = """
## Si añades algo, sale de esta lista (nombre y clave)
{lista}
Elige la clave sólo si es EXACTAMENTE ese movimiento; si el que quieres no está, no lo añadas.
"""

AJUSTE_USER_PROMPT = """## Su programa de fuerza (confirmado por el atleta)
{programa}

## Cómo entrena la fuerza
{perfil}
(Es lo que tiene HOY en su perfil: si la fase del macrociclo dice otro objetivo, manda éste, que lo cambió
después de escribirse el macrociclo.)

## Los días de fuerza de esta semana, con la sesión que le corresponde
(la eligió el código mirando su semana: la que carga piernas se aleja de su calidad y de su tirada larga)
{dias}

## La semana alrededor
{semana}

## La fase del macrociclo
{fase}
{avisos}
{molestias}
## La fuerza de la semana anterior
{anterior}
{registro}
{catalogo}

Devuelve un `FuerzaDelDia` por cada día de fuerza de arriba, con la sesión que toca (cámbiala sólo si hay un motivo y dilo), su dosis y los cambios que haga falta."""


# ── El programa, leído ────────────────────────────────────────────────────────


def sesiones(rutina) -> list[dict]:
    """Las sesiones del programa confirmado, en orden."""
    return [s for s in (getattr(rutina, "sesiones", None) or ()) if isinstance(s, dict) and s.get("groups")]


def _ejercicios(sesion: dict) -> list[dict]:
    return [e for g in sesion.get("groups") or [] for e in g.get("exercises") or []]


_OBJETIVO_EN_SUS_PALABRAS = {"hipertrofia": "ganar músculo", "rendimiento": "rendir más en su deporte",
                             "prevencion": "mantenerse sano y compensar"}


def lo_que_declara_hoy(perfil) -> str:
    """Para qué hace fuerza y con qué, tal como lo tiene HOY en su perfil. "" si no lo dijo.

    El macrociclo lo recibe al escribirse y lo deja escrito en su informe; si el atleta lo cambia
    después, la semana leía el objetivo VIEJO: Jessie pasó de "ganar músculo" con barras a "rendir
    más en su deporte" con mancuernas (2026-09-24), su programa nuevo salió bien, y el prompt de su
    semana sólo decía "tu objetivo es ganar músculo, y ya dominas levantamientos con barra".
    """
    if perfil is None:
        return ""
    partes = []
    if getattr(perfil, "objetivo", None):
        partes.append(f"para qué: **{_OBJETIVO_EN_SUS_PALABRAS.get(perfil.objetivo, perfil.objetivo)}**")
    if getattr(perfil, "equipo", None):
        partes.append(f"con qué: {perfil.equipo.replace('_', ' ')}")
    if not partes:
        return ""
    return ("Lo que declara HOY de su fuerza (" + "; ".join(partes) + "). Si el plan de temporada dice otro "
            "objetivo u otro equipo, manda esto: lo cambió después de escribirse.\n")


def resumen_de_la_rutina(rutina, perfil=None) -> str:
    """El programa en una línea por sesión, SIN ejercicios: para la decisión semanal."""
    lineas = []
    for s in sesiones(rutina):
        grupos = s.get("groups") or []
        seguidos = max((len(g.get("exercises") or []) for g in grupos), default=1)
        minutos = f", ~{s['minutes']} min" if s.get("minutes") else ""
        lineas.append(
            f"- {s.get('name') or s.get('id')}{minutos}: {len(grupos)} grupos, "
            f"{len(_ejercicios(s))} ejercicios" + (f", hasta {seguidos} seguidos" if seguidos > 1 else ""))
    if not lineas:
        return ""
    titulo = ("### Su programa de gimnasio (se lo escribió su coach y el atleta lo confirmó)"
              if getattr(rutina, "origen", "atleta") == "coach"
              else "### Su rutina de gimnasio (la del atleta, confirmada por el atleta)")
    return (titulo + "\n"
            + lo_que_declara_hoy(perfil)
            + "\n".join(lineas)
            + "\n\nLa fuerza de esta semana sale de ahí: tú decides QUÉ DÍAS hay fuerza y cuánto tiempo, "
              "con su tabla de disponibilidad. Los ejercicios no los escribas: se copian de su programa. "
              "Tampoco sabes aún QUÉ sesión cae cada día (se elige después, mirando la semana): en "
              "`purpose` y `adaptation` de esos días no nombres la sesión ni los músculos; di en una "
              "frase para qué sirve la fuerza esta semana y qué hacer si no es su día. "
              "Sus minutos de gimnasio van aparte de las horas de resistencia y no compiten con ellas: "
              "dale a cada día el tiempo de su sesión salvo que su día no lo dé; si la semana pide menos "
              "fuerza, eso se decide después con la dosis, no acortando aquí.\n")


def programa_para_el_prompt(rutina) -> str:
    """El programa entero, con ids: es lo que el paso de fuerza puede cambiar."""
    bloques = []
    for s in sesiones(rutina):
        cabeza = f"### {s.get('name') or s['id']} (id: {s['id']}" + (
            f", ~{s['minutes']} min)" if s.get("minutes") else ")")
        lineas = [cabeza]
        for i, g in enumerate(s.get("groups") or []):
            ejercicios = g.get("exercises") or []
            descanso = f" — {g['rest_between_rounds_s']}s entre rondas" if g.get("rest_between_rounds_s") else ""
            lineas.append(f"Grupo {_letra(i)}" + (" (seguidos)" if len(ejercicios) > 1 else "") + descanso + ":")
            for e in ejercicios:
                carga = f", {e['load']}" if e.get("load") else ""
                tecnica = f", {e['technique']}" if e.get("technique") else ""
                lineas.append(f"  - {e['name']} {e.get('sets') or '?'}x{e.get('reps') or '?'}{carga}{tecnica} "
                              f"[id: {e['id']}]")
        bloques.append("\n".join(lineas))
    return "\n\n".join(bloques) or "(sin sesiones)"


def _letra(indice: int) -> str:
    letras = ""
    indice += 1
    while indice:
        indice, resto = divmod(indice - 1, 26)
        letras = chr(65 + resto) + letras
    return letras


def perfil_para_el_prompt(perfil) -> str:
    if perfil is None:
        return "(no lo dijo)"
    partes = []
    if perfil.objetivo:
        partes.append(f"objetivo: {perfil.objetivo}")
    if perfil.equipo:
        partes.append(f"equipo: {perfil.equipo.replace('_', ' ')}")
    if perfil.levantamientos_con_barra:
        partes.append("con barra ya hace: " + ", ".join(x.replace("_", " ") for x in perfil.levantamientos_con_barra))
    if perfil.minutos_por_sesion:
        partes.append(f"~{perfil.minutos_por_sesion} min por sesión")
    return "; ".join(partes) or "(no lo dijo)"


# ── La rotación: qué sesión toca ──────────────────────────────────────────────


_NOMBRE_DE_PIERNA = ("lower", "pierna", "inferior", "legs", "leg day")
_NOMBRE_DE_TORSO = ("upper", "torso", "superior", "push", "pull", "pecho", "espalda", "brazo")
_NOMBRE_COMPLETO = ("full", "completo", "cuerpo entero", "total body")


def enfasis_de(sesion: dict) -> str | None:
    """Qué carga esa sesión: la que dice el programa, o la que se deduce. None si no se sabe.

    Atleta 2, auditoría del 2026-09-18: su rutina se confirmó antes de que existiera
    `emphasis`, las cuatro sesiones lo tenían vacío y esto las trataba a todas como
    cuerpo completo, así que su plan decía "«Upper B» carga piernas" el día antes de
    su natación. Ahora se deduce del NOMBRE de la sesión (Upper/Lower/Full, como las
    llama casi todo el mundo) y, si no lo dice, de sus ejercicios. Lo que no se puede
    afirmar no genera avisos: un aviso falso enseña a no leerlos.
    """
    valor = str(sesion.get("emphasis") or "").strip().lower()
    if valor in ENFASIS:
        return valor
    nombre = f" {exercise_catalog.normalizar(sesion.get('name')).replace('_', ' ')} "
    for palabras, enfasis in ((_NOMBRE_COMPLETO, COMPLETO), (_NOMBRE_DE_PIERNA, PIERNA),
                              (_NOMBRE_DE_TORSO, TORSO)):
        if any(p in nombre for p in palabras):
            return enfasis
    regiones = [exercise_catalog.region_de(e.get("name")) for e in _ejercicios(sesion)]
    pierna = regiones.count(exercise_catalog.PIERNA)
    torso = regiones.count(exercise_catalog.TORSO)
    if not pierna and not torso:
        return CORE if exercise_catalog.CORE in regiones else None
    parte_de_pierna = pierna / (pierna + torso)
    if parte_de_pierna >= 0.6:
        return PIERNA
    if parte_de_pierna <= 0.25:
        return TORSO
    return COMPLETO


# La fuerza de pierna estorba a lo que se hace con las piernas: correr y pedalear.
# La calidad de la natación (el CSS del atleta 2) no se resiente de una sentadilla,
# y avisarle por eso era ruido.
_DEPORTES_DE_PIERNA = ("run", "bike")


def dias_clave(week_intent) -> dict[str, str]:
    """Las fechas con una sesión clave de correr o de bici, o una carrera, y cuál es."""
    salida = {}
    for dia in getattr(week_intent, "days", None) or []:
        if dia.is_rest or dia.sport == "strength":
            continue
        if dia.intensity_class == "race_day" or (
                dia.intensity_class in SESIONES_CLAVE and dia.sport in _DEPORTES_DE_PIERNA):
            salida.setdefault(dia.date, f"{dia.focus or dia.intensity_class}")
    return salida


def dias_clave_de_natacion(week_intent) -> set[str]:
    """Las fechas con calidad en el agua (CSS, series).

    Lo simétrico de lo de arriba: un día pesado de torso deja hombros y dorsales
    cargados para la natación del día siguiente, igual que la pierna para correr.
    Sólo ordena la colocación; no genera avisos (menos texto, 2026-09-23).
    """
    return {dia.date for dia in getattr(week_intent, "days", None) or []
            if not dia.is_rest and dia.sport == "swim" and dia.intensity_class in SESIONES_CLAVE}


def _dia_siguiente(fecha: str) -> str:
    return (date.fromisoformat(fecha) + timedelta(days=1)).isoformat()


def _dias_entre(a: str, b: str) -> int:
    return abs((date.fromisoformat(a) - date.fromisoformat(b)).days)


def usos_por_sesion(payload, ids: list[str]) -> dict[str, int]:
    """Cuántas veces se hizo cada sesión del programa en lo que hay guardado.

    Sustituye al contador de rotación: entre las sesiones que CABEN ese día se
    elige la menos hecha, así que ninguna se queda sin hacerse sin que haga falta
    un ciclo fijo que ignora el calendario.
    """
    usos = dict.fromkeys(ids, 0)
    for semana in getattr(payload, "weeks", None) or []:
        for dia in semana.days:
            bloque = getattr(dia, "strength_block", None)
            hecha = getattr(bloque, "routine_session_id", "") if bloque else ""
            if hecha in usos:
                usos[hecha] += 1
    return usos


# Lo que penaliza colocar una sesión que carga piernas. No son cuotas: ordenan
# candidatas entre sí, y el mejor sitio posible se usa aunque puntúe mal.
_ANTES_DE_UNA_CLAVE = 100     # el día ANTES de la calidad o la tirada larga: lo que hay que evitar
_MISMO_DIA_QUE_LA_CLAVE = 5   # el día duro se consolida: se hace después de la sesión clave
_MISMO_ENFASIS_EN_48H = 60    # dos veces lo mismo sin dejar recuperar


_OTRO_DIA_QUE_EL_SUYO = 1000   # más que cualquier otra razón: es lo que pidió
_DIAS_DEL_NOMBRE = ("lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo")


def dia_del_nombre(sesion: dict) -> int | None:
    """El día de la semana (0 = lunes) si el nombre de la sesión lo dice; None si no.

    Lectura real del 2026-09-23: "LUNES 💪 pecho y triceps", "MIERCOLES pierna".
    """
    palabras = exercise_catalog.normalizar(sesion.get("name")).split("_")
    encontrados = [i for i, d in enumerate(_DIAS_DEL_NOMBRE) if d in palabras]
    return encontrados[0] if len(encontrados) == 1 else None


def _coste(sesion: dict, fecha: str, claves: dict[str, str], asignadas: list[tuple[str, dict]],
           usos: dict[str, int], natacion: frozenset[str] | set[str] = frozenset(),
           dias_de_la_semana: frozenset[int] | set[int] = frozenset()) -> int:
    coste = 10 * usos.get(sesion["id"], 0)
    enfasis = enfasis_de(sesion)
    if enfasis in CARGAN_PIERNA:
        if _dia_siguiente(fecha) in claves:
            coste += _ANTES_DE_UNA_CLAVE
        if fecha in claves:
            coste += _MISMO_DIA_QUE_LA_CLAVE
    if enfasis in (TORSO, COMPLETO) and _dia_siguiente(fecha) in natacion:
        coste += _ANTES_DE_UNA_CLAVE
    # Si el atleta nombró la sesión por su día ("LUNES pecho y tríceps"), ese día es
    # una petición suya, como su tabla: va ahí cuando ese día tiene fuerza, y no a otro.
    suyo = dia_del_nombre(sesion)
    if suyo is not None and suyo in dias_de_la_semana:
        coste += 0 if date.fromisoformat(fecha).weekday() == suyo else _OTRO_DIA_QUE_EL_SUYO
    for otra_fecha, otra in asignadas:
        if enfasis is not None and enfasis_de(otra) == enfasis and _dias_entre(fecha, otra_fecha) <= 2:
            coste += _MISMO_ENFASIS_EN_48H
    return coste


def colocar_las_sesiones(rutina, fechas: list[str], week_intent, payload_anterior=None
                         ) -> tuple[list[str], list[dict]]:
    """Qué sesión va cada día, y lo que hay que decirle al atleta.

    En vez de una rotación ciega -- que a una triatleta con tres días fijos le ponía
    pierna un lunes y brazo el siguiente por turno --, cada día se queda con la sesión
    que menos estorba: la de pierna se aleja de la calidad y de la tirada larga, no se
    repite el mismo énfasis en 48 h, y entre las que caben se elige la menos hecha.

    **Si no cabe lejos, se coloca igual en el mejor sitio posible y se le dice** (lo
    decidió el responsable el 2026-09-17): el atleta puede añadir un día, y nosotros no
    podemos quitarle su sesión de pierna por no tener dónde ponerla.
    """
    disponibles = sesiones(rutina)
    if not disponibles or not fechas:
        return [], []
    claves = dias_clave(week_intent)
    natacion = dias_clave_de_natacion(week_intent)
    dias_de_la_semana = {date.fromisoformat(f).weekday() for f in fechas}
    usos = usos_por_sesion(payload_anterior, [s["id"] for s in disponibles])
    asignadas: list[tuple[str, dict]] = []
    elegidas = []
    for fecha in fechas:
        sesion = min(disponibles, key=lambda s, f=fecha: (
            _coste(s, f, claves, asignadas, usos, natacion, dias_de_la_semana), disponibles.index(s)))
        elegidas.append(sesion["id"])
        asignadas.append((fecha, sesion))
        usos[sesion["id"]] = usos.get(sesion["id"], 0) + 1
    return elegidas, avisos_de_colocacion(asignadas, claves, fechas)


def avisos_de_colocacion(asignadas: list[tuple[str, dict]], claves: dict[str, str],
                         fechas: list[str]) -> list[dict]:
    """Lo que el atleta tiene que saber de dónde cayó su fuerza, con el porqué.

    Va a su plan, no sólo al log: si sus días de gimnasio no dan para separar la
    pierna de la calidad, la decisión de añadir un día es suya y necesita el motivo.
    """
    avisos = []
    for fecha, sesion in asignadas:
        if enfasis_de(sesion) not in CARGAN_PIERNA:
            continue
        # El mismo día que su sesión clave: no se mueve nada (moverla es otra decisión
        # y más decisiones dan peores semanas), sólo se dice en qué orden conviene.
        # Hacer la clave con las piernas frescas y la fuerza después, con horas entre
        # medias, es lo que menos estorba a las dos (Robineau 2016).
        if fecha in claves:
            avisos.append({"date": fecha, "session_id": sesion["id"], "text": (
                f"Mismo día que tu {claves[fecha]}: haz primero esa y la fuerza después, "
                "a ser posible con unas 6 h entre medias.")})
            continue
        clave = claves.get(_dia_siguiente(fecha))
        if not clave:
            continue
        # Corto: se lee en el móvil, en el día, y el porqué largo no lo lee nadie.
        avisos.append({"date": fecha, "session_id": sesion["id"], "text": (
            f"Carga piernas el día antes de tu {clave}. Con tus días de gimnasio no cabe más lejos: "
            "si puedes, mueve o añade uno.")})
    return avisos


def avisar_en_los_dias(construidos: list[WorkoutDay], avisos: list[dict]) -> None:
    """El aviso va al DÍA del atleta, no sólo al log: es él quien decide si añade un día."""
    for aviso in avisos:
        dia = next((d for d in construidos if d.date == aviso["date"] and d.strength_block is not None
                    and d.strength_block.routine_session_id == aviso.get(
                        "session_id", d.strength_block.routine_session_id)), None)
        if dia is None:
            continue
        dia.strength_block.placement_note = aviso["text"]


# ── La llamada ────────────────────────────────────────────────────────────────


Llamar = Callable[[list], Awaitable[AjusteDeFuerza]]


def puede_anadir(molestias: str, fase: str, corre: bool = False) -> bool:
    """Añadir ejercicios sólo cuando hay algo DECLARADO que lo pida.

    Es la regla de la casa: un atleta sano recibe cero tokens sobre lesiones, y un
    catálogo en el prompt es una invitación a usarlo. Con molestias declaradas, o con
    una fase que dice qué busca la fuerza, el paso tiene algo contra lo que decidir.
    """
    # Y quien corre: la pliometría es entrenamiento de rendimiento, no prehab, y la
    # decide el paso por la fase y la semana (el responsable, 2026-09-23).
    return bool(molestias.strip() or "fuerza en esta fase busca" in (fase or "") or corre)


async def ajustar_la_fuerza(
    rutina,
    perfil,
    dias: list[dict],
    semana: str,
    fase: str,
    avisos: str,
    molestias: str,
    anterior: str,
    llamar: Llamar,
    registro: str = "",
) -> list[FuerzaDelDia]:
    """Los cambios del modelo sobre el programa, ya saneados. Nunca lanza: si falla, la
    rutina se usa tal cual (que es exactamente lo que el atleta confirmó)."""
    if not dias:
        return []
    mensajes = [
        {"role": "system", "content": AJUSTE_SYSTEM_PROMPT},
        {"role": "user", "content": AJUSTE_USER_PROMPT.format(
            programa=programa_para_el_prompt(rutina),
            perfil=perfil_para_el_prompt(perfil),
            dias="\n".join(f"- {d['date']} ({d.get('day_name_es', '')}, franja {d.get('session_slot') or 'unica'}): "
                           f"toca {d['session_id']}"
                           f"{', ' + str(int(d['minutes'])) + ' min previstos' if d.get('minutes') else ''}"
                           for d in dias),
            semana=semana or "(sin más sesiones)",
            fase=fase or "(sin fase)",
            avisos=f"\n## Esta semana\n{avisos}\n" if avisos else "",
            molestias=f"\n## Lo que le molesta (lo que declaró)\n{molestias}\n" if molestias else "",
            anterior=anterior or "(no hay semana anterior con fuerza de su rutina)",
            registro=f"\n{registro}" if registro else "",
            # El catálogo sólo viaja cuando se puede añadir algo: son ~4k caracteres
            # que en una semana sin nada declarado sólo diluyen las demás reglas.
            catalogo=(_CATALOGO_PARA_ANADIR.format(lista=render_catalogo_para_prompt())
                      if puede_anadir(molestias, fase, corre="): run " in (semana or "")) else ""),
        )},
    ]
    try:
        ajuste = await llamar(mensajes)
    except Exception:  # noqa: BLE001 - la semana no se pierde por la fuerza
        logger.exception("Paso de fuerza: falló el ajuste; se usa la rutina tal cual")
        return [FuerzaDelDia(date=d["date"], session_slot=d.get("session_slot") or "", session_id=d["session_id"])
                for d in dias]
    return _saneado(ajuste, dias, rutina)


def _propuesta_para(dia: dict, propuestas: list[FuerzaDelDia], usadas: set[int]) -> FuerzaDelDia | None:
    """La propuesta de ese día: por fecha y franja; por fecha sola si esa fecha no tiene otra."""
    franja = dia.get("session_slot") or ""
    libres = [(i, p) for i, p in enumerate(propuestas) if i not in usadas and p.date == dia["date"]]
    for i, p in libres:
        if (p.session_slot or "") == franja:
            usadas.add(i)
            return p
    if len(libres) == 1:
        usadas.add(libres[0][0])
        return libres[0][1]
    return None


def _saneado(ajuste: AjusteDeFuerza, dias: list[dict], rutina) -> list[FuerzaDelDia]:
    """Lo que el modelo devolvió, contrastado con el programa y con los días reales."""
    ids_de_sesion = {s["id"] for s in sesiones(rutina)}
    usadas: set[int] = set()
    salida = []
    for dia in dias:
        propuesta = _propuesta_para(dia, list(ajuste.days), usadas)
        if propuesta is None:
            logger.warning("Paso de fuerza: %s no vino en la respuesta; se usa su sesión tal cual", dia["date"])
            salida.append(FuerzaDelDia(date=dia["date"], session_slot=dia.get("session_slot") or "",
                                       session_id=dia["session_id"]))
            continue
        propuesta.session_slot = dia.get("session_slot") or ""
        if propuesta.session_id not in ids_de_sesion:
            logger.warning("Paso de fuerza: %s pidió la sesión «%s», que no está en el programa; se usa %s",
                           dia["date"], propuesta.session_id, dia["session_id"])
            propuesta.session_id = dia["session_id"]
        if propuesta.dose not in DOSIS:
            logger.warning("Paso de fuerza: dosis «%s» desconocida el %s; se usa normal",
                           propuesta.dose, dia["date"])
            propuesta.dose = "normal"
        # Un cambio sin motivo no se aplica: es la única forma de que "cada cambio
        # lleva su porqué" sea una garantía y no una petición del prompt.
        ejercicios = {e["id"] for s in sesiones(rutina) if s["id"] == propuesta.session_id for e in _ejercicios(s)}
        limpios = []
        for cambio in propuesta.changes:
            if not cambio.reason.strip():
                logger.warning("Paso de fuerza: cambio en %s sin motivo; se descarta", cambio.exercise_id)
                continue
            if cambio.exercise_id not in ejercicios:
                logger.warning("Paso de fuerza: el ejercicio %s no está en %s; se descarta",
                               cambio.exercise_id, propuesta.session_id)
                continue
            limpios.append(cambio)
        propuesta.changes = limpios
        propuesta.additions = _anadidos_saneados(propuesta.additions, dia["date"])
        salida.append(propuesta)
    return salida


# Tope de lo que se puede añadir a una sesión. No es una cuota: es el límite que
# hace que "añadir" siga siendo un ajuste de la semana y no una sesión nueva que
# el atleta nunca confirmó.
MAX_ANADIDOS = 2


def _es_una_clave(nombre: str) -> bool:
    """Si lo escrito es una clave del catálogo (`arco_corto`) y no un nombre."""
    limpio = (nombre or "").strip()
    return bool(limpio) and limpio == limpio.lower() and "_" in limpio and " " not in limpio


def _anadidos_saneados(anadidos: list[EjercicioAnadido], fecha: str) -> list[EjercicioAnadido]:
    """Lo añadido, contrastado: con motivo, con nombre que el catálogo reconoce, y con tope."""
    limpios = []
    for ejercicio in anadidos:
        if not ejercicio.reason.strip():
            logger.warning("Paso de fuerza: añadido «%s» sin motivo el %s; se descarta", ejercicio.name, fecha)
            continue
        if not ejercicio.name.strip() or not ejercicio.reps.strip():
            continue
        if len(limpios) >= MAX_ANADIDOS:
            logger.warning("Paso de fuerza: más de %d añadidos el %s; se queda con los primeros",
                           MAX_ANADIDOS, fecha)
            break
        limpios.append(ejercicio)
    return limpios


# ── El bloque, construido en código ───────────────────────────────────────────


def series_con_dosis(series: int | None, dose: str) -> int:
    """Las series de un ejercicio a la dosis de la semana. Baja, nunca sube.

    Al MÁS CERCANO, no hacia arriba. Con `ceil`, un ejercicio de 3 series a dosis
    reducida seguía en 3 (3 x 0,7 = 2,1 -> 3): la Lower A del programa de la atleta 3
    (2026-09-23) salió "dosis reducida" con seis de sus nueve ejercicios intactos, en
    42' en vez de 60', y la pantalla le decía "menos series". Reducida: 3->2, 4->3,
    5->4; mínima: 3->2, 4->2.
    """
    base = max(1, int(series or 1))
    if dose in ("normal", "ninguna") or dose not in _FACTOR_DE_DOSIS:
        return base
    return max(1, min(base, math.floor(base * _FACTOR_DE_DOSIS[dose] + 0.5)))


def series_del_ejercicio(ejercicio: dict, cambio, dose: str) -> int:
    """Las series que se hacen: las que pidió el paso con motivo, o las del programa a su dosis.

    **No se recorta dos veces.** En la primera llamada real (2026-09-17, gpt-5-mid) el
    modelo bajó las series de la sentadilla de 4 a 3 Y puso la semana en dosis reducida:
    aplicar además el factor habría dejado 2, un recorte que nadie decidió. Y nunca sube
    de lo que el atleta confirmó: el programa es el techo.
    """
    base = max(1, int(ejercicio.get("sets") or 1))
    if cambio is not None and cambio.sets:
        pedidas = max(1, int(cambio.sets))
        if pedidas > base:
            logger.warning("Paso de fuerza: pidió %d series de «%s» y su programa tiene %d; se queda en %d",
                           pedidas, ejercicio.get("name"), base, base)
        return min(pedidas, base)
    return series_con_dosis(base, dose)


def bloque_de_fuerza(sesion: dict, ajuste: FuerzaDelDia, perfil=None) -> StrengthBlock | None:
    """El `StrengthBlock` de esa sesión con los cambios aplicados. None si no queda nada."""
    cambios = {c.exercise_id: c for c in ajuste.changes}
    grupos: list[StrengthGroup] = []
    quitados: list[dict] = []
    for g, grupo in enumerate(sesion.get("groups") or []):
        ejercicios = []
        for ejercicio in grupo.get("exercises") or []:
            cambio = cambios.get(ejercicio["id"])
            if cambio and cambio.skip:
                quitados.append({"name": ejercicio["name"], "reason": cambio.reason.strip()})
                continue
            nombre = (cambio.replace_with.strip() if cambio and cambio.replace_with.strip() else ejercicio["name"])
            series = series_del_ejercicio(ejercicio, cambio, ajuste.dose)
            # Un ejercicio SUSTITUIDO pierde la imagen del viejo, y su nombre de Garmin
            # sale del catálogo si lo reconoce: al reloj no puede llegar con el de otro.
            mismo = nombre == ejercicio["name"]
            if mismo:
                categoria, garmin = exercise_catalog.garmin_de(
                    nombre, ejercicio.get("garmin_category"), ejercicio.get("garmin_exercise"))
            else:
                categoria, garmin = exercise_catalog.garmin_de(nombre)
            ejercicios.append(StrengthExercise(
                id=(ejercicio.get("id") or "") if mismo else "",
                name=nombre,
                sets=series,
                reps=(cambio.reps.strip() if cambio and cambio.reps.strip() else (ejercicio.get("reps") or "")),
                load=(cambio.load.strip() if cambio and cambio.load.strip() else (ejercicio.get("load") or "")),
                rest_seconds=grupo.get("rest_between_rounds_s"),
                cues=(ejercicio.get("technique") or "") if mismo else "",
                video_key=((ejercicio.get("video_key") or "") if mismo
                           else (exercise_catalog.resolver(nombre) or "")),
                garmin_exercise=garmin or "",
                garmin_category=categoria or "",
                change=_etiqueta_del_cambio(ejercicio, cambio, nombre, series, ajuste.dose),
                change_reason=(cambio.reason.strip() if cambio and _etiqueta_del_cambio(
                    ejercicio, cambio, nombre, series, ajuste.dose) else ""),
            ))
        if ejercicios:
            grupos.append(StrengthGroup(
                id=grupo.get("id") or f"g{g + 1}",
                label=_letra(g),
                rest_between_rounds_s=grupo.get("rest_between_rounds_s"),
                rest_between_exercises_s=grupo.get("rest_between_exercises_s"),
                exercises=ejercicios,
            ))
    if not grupos:
        return None
    # Lo añadido va en su PROPIO grupo, al final y con su etiqueta: el atleta ve de un
    # vistazo qué es suyo de siempre y qué le puso el coach esta semana.
    if ajuste.additions:
        anadidos = []
        for ejercicio in ajuste.additions:
            clave = exercise_catalog.resolver(ejercicio.name, ejercicio.video_key)
            # En la primera prueba real (2026-09-17) escribió la CLAVE donde iba el
            # nombre ("arco_corto"), y eso es lo que el atleta lee en el gimnasio.
            nombre = exercise_catalog.nombre_de(clave) if _es_una_clave(ejercicio.name) else None
            nombre = nombre or ejercicio.name.strip()
            categoria, garmin = exercise_catalog.garmin_de(nombre)
            anadidos.append(StrengthExercise(
                name=nombre,
                sets=max(1, int(ejercicio.sets or 1)),
                reps=ejercicio.reps.strip(),
                load=ejercicio.load.strip(),
                cues="",
                video_key=clave or "",
                garmin_exercise=garmin or "",
                garmin_category=categoria or "",
                change="nuevo esta semana",
                change_reason=ejercicio.reason.strip(),
            ))
        grupos.append(StrengthGroup(
            id=f"{sesion['id']}_anadidos",
            label=_letra(len(grupos)),
            rest_between_rounds_s=None,
            exercises=anadidos,
        ))
    # Nada de párrafos: cada cosa en su campo y la pantalla enseña una etiqueta con
    # el porqué a un toque (ver `StrengthBlock.session_name`). `notes` se queda vacío.
    dosis = ajuste.dose if ajuste.dose in _COMO_SE_DICE and ajuste.dose != "normal" else ""
    return StrengthBlock(
        warmup=calentamiento_de(grupos),
        cooldown="",
        groups=grupos,
        notes="",
        routine_session_id=sesion["id"],
        session_name=sesion.get("name") or sesion["id"],
        dose=dosis,
        dose_reason=ajuste.dose_reason.strip() if dosis else "",
        skipped=quitados,
    )


def calentamiento_de(grupos: list[StrengthGroup]) -> str:
    """Una línea: series de aproximación del primer ejercicio que carga de verdad.

    Su rutina no traía calentamiento y la sesión empezaba en frío con la carga de
    trabajo. Lo estándar son una o dos series de aproximación del primer ejercicio
    fuerte; una línea y no una sesión, porque en el móvil se lee de un vistazo.
    "" si la sesión no tiene ningún ejercicio de pierna o de torso.
    """
    for grupo in grupos:
        for ejercicio in grupo.exercises:
            if exercise_catalog.region_de(ejercicio.name) in (exercise_catalog.PIERNA, exercise_catalog.TORSO):
                # Sin "movilidad": muchos programas ya la traen como su primer grupo.
                return (f"Antes de {ejercicio.name}: 2 series de aproximación, una ligera y otra a "
                        "media carga, sin cansarte.")
    return ""


def _etiqueta_del_cambio(ejercicio: dict, cambio, nombre: str, series: int, dose: str = "normal") -> str:
    """La etiqueta corta de lo que cambió; "" si nada que el atleta tenga que notar.

    Con la dosis de la semana bajada, las series de menos no se etiquetan una a una:
    lo dice la etiqueta de la dosis, una vez. En la Upper A del atleta 2 (2026-09-28,
    dosis mínima) salían cinco "−1 serie" seguidos, que es otra vez el texto que nadie lee.
    """
    if cambio is None:
        return ""
    if nombre != ejercicio["name"]:
        return f"en vez de {ejercicio['name']}"
    base = max(1, int(ejercicio.get("sets") or 1))
    if cambio.sets and series != base:
        if dose in ("reducida", "minima"):
            return ""
        menos = base - series
        return f"−{menos} serie" + ("s" if menos > 1 else "")
    if cambio.reps.strip() and cambio.reps.strip() != (ejercicio.get("reps") or ""):
        return "otras repeticiones"
    if cambio.load.strip() and cambio.load.strip() != (ejercicio.get("load") or ""):
        return "otra carga"
    return ""


def minutos_de(sesion: dict, dose: str, previstos: float | None) -> float:
    """Los minutos de la sesión: los que decidió la semana, o los del programa a su dosis.

    **No se recorta dos veces**, igual que las series (`series_del_ejercicio`). Semana
    del atleta 2 del 2026-09-28: la decisión dio 42' a su Upper A, el paso puso dosis
    mínima y aplicar el factor encima dejó 21' para cinco ejercicios. El factor se aplica
    a los minutos de SU programa, y la sesión dura lo menor entre eso y lo que dio la
    semana: nunca los dos recortes a la vez.
    """
    factor = _FACTOR_DE_DOSIS.get(dose, 1.0)
    del_programa = float(sesion["minutes"]) * factor if sesion.get("minutes") else None
    candidatos = [m for m in (float(previstos) if previstos else None, del_programa) if m]
    return max(10.0, round(min(candidatos))) if candidatos else max(10.0, round(45 * factor))


def semana_alrededor(week_intent, fechas_de_fuerza: set[str]) -> str:
    """Las demás sesiones de la semana, en una línea cada una.

    Es lo que decide si la fuerza estorba: una sesión pesada de pierna el día antes
    de la tirada larga no es lo mismo que dos días después de ella.
    """
    lineas = []
    for dia in sorted(week_intent.days, key=lambda d: (d.date or "", d.session_slot or "")):
        if dia.is_rest:
            lineas.append(f"- {dia.date} ({dia.day_name_es}): descanso")
            continue
        marca = " ← fuerza de su rutina" if dia.date in fechas_de_fuerza and dia.sport == "strength" else ""
        minutos = f", {int(dia.total_duration_minutes)}'" if dia.total_duration_minutes else ""
        lineas.append(f"- {dia.date} ({dia.day_name_es}): {dia.sport} {dia.focus} "
                      f"[{dia.intensity_class}]{minutos}{marca}")
    return "\n".join(lineas)


def dias_faciles_que_quedan(carrera) -> int | None:
    """Cuántos de los días fáciles de vuelta de una carrera caen en esta semana (la misma
    cuenta que `weekly_planner_node._nota_de_recuperacion`). None si no se sabe: una carrera
    multideporte no tiene regla de días fáciles."""
    if not isinstance(carrera, dict) or not carrera.get("easy_days") or carrera.get("days_since") is None:
        return None
    return max(0, int(carrera["easy_days"]) - int(carrera["days_since"]) + 1)


def avisos_de_la_semana(load_target: dict | None) -> str:
    """Afinamiento o vuelta de carrera, dicho como lo que es. Sin números de cuota."""
    t = load_target or {}
    avisos = []
    if t.get("race_taper"):
        avisos.append("Es semana de AFINAMIENTO de una carrera: la fuerza acompaña, no construye.")
    carrera = t.get("race_recovery")
    if carrera:
        # "Acaba de competir: la semana es de recuperación" a secas bajó a mínima las tres fuerzas
        # del atleta 2 (bloque 26-sep → 4-oct, su 5K el 20/09): sus ~2 días fáciles habían pasado
        # antes de la semana y la decisión lo sabía; el paso de fuerza no (2026-09-25).
        quedan = dias_faciles_que_quedan(carrera)
        cuando = f" hace {carrera['days_since']} días" if isinstance(carrera, dict) and carrera.get("days_since") else ""
        if quedan == 0:
            avisos.append(f"Compitió{cuando}; sus días fáciles de vuelta de la carrera terminaron antes de esta "
                          "semana. Es la semana de después de una carrera (no se construye de más), pero la "
                          "carrera por sí sola ya no es motivo para bajar la fuerza.")
        elif quedan:
            avisos.append(f"Compitió{cuando}: le quedan ~{quedan} días fáciles de vuelta al empezar esta semana, "
                          "y en esos días la fuerza va ligera.")
        else:
            avisos.append(f"Acaba de competir{cuando}: la semana es de recuperación.")
    if t.get("is_deload"):
        avisos.append("Es semana de descarga del macrociclo.")
    return "\n".join(f"- {a}" for a in avisos)


def aviso_de_dosis(ajustes: list[FuerzaDelDia], load_target: dict | None) -> str:
    """LINT, nunca una corrección: una semana de afinamiento o de vuelta de carrera con
    toda la fuerza a dosis normal se dice en el log del worker y se sigue.

    No se baja aquí en código: cuánto acompaña la fuerza a una puesta a punto es
    criterio, y el paso tiene delante la carrera, la fase y cómo respondió el atleta.
    """
    t = load_target or {}
    vuelta = t.get("race_recovery") and dias_faciles_que_quedan(t["race_recovery"]) != 0
    if not (t.get("race_taper") or vuelta):
        return ""
    if not ajustes or any(a.dose != "normal" for a in ajustes):
        return ""
    cuando = "afinamiento" if t.get("race_taper") else "vuelta de una carrera"
    return (f"Fuerza: semana de {cuando} y las {len(ajustes)} sesiones quedaron a dosis normal; "
            "revisa si es lo que quieres.")


def lo_que_levanto(logs: list | None, rutina=None, sesiones_max: int = 6) -> str:
    """Lo que el atleta REGISTRÓ en el gimnasio, por ejercicio (Database/026).

    Es la única fuente de kilos de toda la app: los escribe él. Sin esto, su
    programa se queda congelado en lo que confirmó el primer día -- no hay contra
    qué progresar la carga, que es lo que hacen Hevy, Fitbod o Juggernaut y esta
    app no hacía. (Leerlo de su reloj se retiró en la Beta 3.0, F0.)

    Vacío mientras no registre nada, y entonces el paso no ve ni una palabra de esto.
    """
    entradas = [x for x in (logs or []) if isinstance(x, dict) and x.get("exercises")]
    if not entradas:
        return ""
    nombres = {s["id"]: (s.get("name") or s["id"]) for s in sesiones(rutina)} if rutina else {}
    lineas = []
    for entrada in entradas[:sesiones_max]:
        sesion = nombres.get(entrada.get("routine_session_id"), entrada.get("routine_session_id") or "")
        cabeza = f"- {entrada.get('fecha', '?')}" + (f" ({sesion})" if sesion else "")
        hechos = []
        for ejercicio in entrada.get("exercises") or []:
            if not isinstance(ejercicio, dict) or not ejercicio.get("name"):
                continue
            partes = [str(ejercicio["name"])]
            if ejercicio.get("sets") and ejercicio.get("reps"):
                partes.append(f"{ejercicio['sets']}x{ejercicio['reps']}")
            if ejercicio.get("weight_kg"):
                partes.append(f"{ejercicio['weight_kg']} kg")
            if ejercicio.get("rpe"):
                partes.append(f"RPE {ejercicio['rpe']}")
            if ejercicio.get("done") is False:
                partes.append("(no la hizo)")
            hechos.append(" ".join(partes))
        if hechos:
            lineas.append(f"{cabeza}: " + "; ".join(hechos))
        if (entrada.get("note") or "").strip():
            lineas.append(f"  dijo: {entrada['note'].strip()}")
    if not lineas:
        return ""
    return ("### Lo que levantó de verdad (lo registró el atleta, en la app o en su reloj)\n" + "\n".join(lineas)
            + "\n\nEs lo único de esta app donde los kilos son un dato y no una suposición. Si un ejercicio "
              "se le quedó corto o largo dos veces seguidas, ahí tienes con qué proponer la carga -- con su "
              "motivo, como todo lo demás.\n")


def fuerza_anterior(payload) -> str:
    """Qué sesión de su rutina hizo la semana pasada y con qué cambios."""
    lineas = []
    for semana in getattr(payload, "weeks", None) or []:
        for dia in sorted(semana.days, key=lambda d: d.date or ""):
            bloque = getattr(dia, "strength_block", None)
            if not bloque or not getattr(bloque, "routine_session_id", ""):
                continue
            lineas.append(f"- {dia.date}: {bloque.routine_session_id}{_lo_que_cambio(bloque)}")
    return "\n".join(lineas)


def _lo_que_cambio(bloque) -> str:
    """La dosis y los cambios de un bloque en una línea, para el prompt de la semana siguiente."""
    if getattr(bloque, "notes", ""):
        return f" — {bloque.notes}"  # un plan guardado antes del 2026-09-23
    partes = []
    if getattr(bloque, "dose", ""):
        partes.append(f"dosis {bloque.dose}" + (f" ({bloque.dose_reason})" if bloque.dose_reason else ""))
    for e in bloque.exercises:
        if getattr(e, "change", ""):
            partes.append(f"{e.name}: {e.change}" + (f" ({e.change_reason})" if e.change_reason else ""))
    for q in getattr(bloque, "skipped", None) or []:
        partes.append(f"sin {q.get('name')}" + (f" ({q.get('reason')})" if q.get("reason") else ""))
    return (" — " + "; ".join(partes)) if partes else ""


def dias_construidos(rutina, dias: list[dict], ajustes: list[FuerzaDelDia], perfil=None) -> list[WorkoutDay]:
    """Los días de fuerza de la semana, escritos por el CÓDIGO desde su programa."""
    por_id = {s["id"]: s for s in sesiones(rutina)}
    usadas: set[int] = set()
    salida = []
    for dia in dias:
        ajuste = _propuesta_para(dia, list(ajustes), usadas) or FuerzaDelDia(
            date=dia["date"], session_slot=dia.get("session_slot") or "", session_id=dia["session_id"])
        sesion = por_id.get(ajuste.session_id) or por_id.get(dia["session_id"])
        if not sesion:
            logger.warning("Fuerza del %s: no hay sesión en el programa; el día se queda sin bloque", dia["date"])
            continue
        if ajuste.dose == "ninguna":
            salida.append(_dia_sin_fuerza(dia, sesion, ajuste))
            continue
        bloque = bloque_de_fuerza(sesion, ajuste, perfil)
        if bloque is None:
            # Quitarlo todo no es una dosis: se hace su sesión tal cual y se dice.
            logger.warning("Fuerza del %s: los cambios dejaban la sesión vacía; va tal cual", dia["date"])
            bloque = bloque_de_fuerza(sesion, FuerzaDelDia(date=dia["date"], session_id=sesion["id"]), perfil)
        # El porqué y el plan B, del paso que conoce la sesión; si no los escribió, los de la semana.
        dia = {**dia, "purpose": ajuste.purpose.strip() or dia.get("purpose") or "",
               "adaptation": ajuste.adaptation.strip() or dia.get("adaptation") or ""}
        salida.append(dia_de_fuerza(dia, bloque, minutos_de(sesion, ajuste.dose, dia.get("minutes"))))
    return salida


def _dia_sin_fuerza(dia: dict, sesion: dict, ajuste: FuerzaDelDia) -> WorkoutDay:
    """La fuerza que NO se hace se dice; no desaparece del día en silencio."""
    motivo = ajuste.dose_reason.strip() or "esta semana no toca"
    return WorkoutDay(
        date=dia["date"], day_name_es=dia.get("day_name_es", ""),
        session_slot=dia.get("session_slot") or "unica", is_rest=True, sport="rest",
        focus="Descanso",
        purpose=f"Aquí iba «{sesion.get('name') or sesion['id']}» de tu rutina, pero {motivo}, así que se quitó.",
        adaptation="", total_duration_minutes=0, intensity_class="rest", discipline="descanso", steps=[],
    )


def reponer_la_fuerza(payload, construidos: dict) -> int:
    """Vuelve a poner los bloques de su rutina después de la reparación del validador.

    La reparación de tier 2 reescribe la semana ENTERA con un modelo que no conoce
    su programa: sin esto, una reparación por otro día borraría los ejercicios que
    el atleta confirmó. Si el validador convirtió el día en descanso (su tabla no
    da ese día), se respeta y se dice en el log: eso no se deshace desde aquí.
    """
    repuestos = 0
    for semana in getattr(payload, "weeks", None) or []:
        for i, dia in enumerate(semana.days):
            original = construidos.get((dia.date, dia.session_slot))
            if original is None:
                continue
            if dia.is_rest and not original.is_rest:
                logger.info("Fuerza del %s: el día quedó como descanso tras validar; no se repone", dia.date)
                continue
            semana.days[i] = original.model_copy(deep=True)
            repuestos += 1
    return repuestos


def dia_de_fuerza(intent, bloque: StrengthBlock, minutos: float) -> WorkoutDay:
    """El día de fuerza entero, en código: el codificador ya no lo escribe."""
    return WorkoutDay(
        date=intent["date"],
        day_name_es=intent.get("day_name_es", ""),
        session_slot=intent.get("session_slot") or "unica",
        is_rest=False,
        sport="strength",
        # El nombre de SU sesión ("Upper A"), no "Fuerza": es lo que busca en su rutina.
        focus=bloque.session_name or intent.get("focus") or "Fuerza",
        purpose=intent.get("purpose") or "",
        adaptation=intent.get("adaptation") or "",
        total_duration_minutes=minutos,
        intensity_class=intent.get("intensity_class") or "strength_freeform",
        discipline=intent.get("discipline") or "fuerza",
        steps=[WorkoutStep(
            step_order=1, step_type="main", duration_type="time", duration_value=minutos * 60,
            repeat_count=1, target_type="none",
            description=f"{bloque.session_name or intent.get('focus') or 'Fuerza'} — {int(minutos)} min",
        )],
        strength_block=bloque,
    )
