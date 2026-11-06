"""El coach arma el programa de gimnasio cuando el atleta no tiene uno (Beta 2.1, S6).

Jessie dijo que los ejercicios de gimnasio le salían "muy básicos". No era el prompt:
era que cada semana se inventaban de cero, sin saber qué equipo tenía, para qué los
hacía ni cómo quería repartirlos. S6 le da lo mismo que S4 le da a Fernando, pero
escrito por el coach:

- **Se escribe UNA vez por bloque**, no cada semana, y el atleta lo revisa y lo
  confirma en la MISMA pantalla que una rutina pegada (``RevisarRutina.vue``). A
  partir de ahí la semana lo usa igual que la de Fernando (S5): el código elige la
  sesión y construye el bloque, y el paso de fuerza sólo propone cambios con motivo.
  Por eso los ejercicios dejan de bailar entre regeneraciones.
- **Sale de lo que el atleta declaró** -- su objetivo, su equipo, cómo quiere
  repartirlo, cuántos ejercicios seguidos, cuántos minutos, qué levantamientos con
  barra ya hace y qué le molesta -- y de cuántos días de fuerza pidió en su tabla.
- **Sin números de criterio nuestros.** Ni series por grupo muscular, ni un RIR
  objetivo, ni "3 sesiones a la semana": el programa se justifica contra lo que ESE
  atleta declaró, y si algo no se sabe, no se rellena.

El código hace lo mismo que con una rutina pegada: pone los ids, resuelve la imagen
por alias exacto y deja el nombre de Garmin sólo si está verificado. Lo que no cuadra
con lo que el atleta declaró se convierte en una pregunta de la revisión, no en un
arreglo silencioso.
"""

from __future__ import annotations

import logging
from collections.abc import Awaitable, Callable

from pydantic import BaseModel, Field

from services.ai import exercise_catalog
from services.ai.perfil_de_fuerza import EJERCICIOS_POR_GRUPO, MINUTOS_POR_SESION
from services.garmin import garmin_vocabulary

logger = logging.getLogger(__name__)

MAX_SESIONES = 6


class EjercicioPropuesto(BaseModel):
    name: str = Field(..., description="Nombre del ejercicio en español, como se lo dirías al atleta.")
    sets: int = Field(..., description="Series.")
    reps: str = Field(..., description="Repeticiones o tiempo: '8-10', '12 por lado', '40 s'.")
    load: str = Field("", description="Cómo elegir la carga (RIR o RPE). Kilos SÓLO si el atleta los dio.")
    cues: str = Field("", description="Una frase de ejecución: qué vigilar para hacerlo bien.")
    video_key: str = Field("", description="Clave de la lista cerrada de imágenes; vacío si no está.")
    garmin_category: str = Field("", description="Categoría de Garmin de la lista; vacío si ninguna encaja.")
    garmin_exercise: str = Field("", description="Nombre de Garmin de la lista; vacío si no es ese movimiento.")


class GrupoPropuesto(BaseModel):
    exercises: list[EjercicioPropuesto] = Field(..., description="Ejercicios que se hacen seguidos, en orden.")
    rest_between_rounds_s: int | None = Field(None, description="Descanso entre rondas, en segundos.")


class SesionPropuesta(BaseModel):
    name: str = Field(..., description="Nombre corto: 'Torso A', 'Pierna', 'Cuerpo completo 1'.")
    emphasis: str = Field(
        ...,
        description=(
            "Qué carga la sesión: 'pierna', 'torso', 'completo' o 'core'. Es lo que decide en qué día de su "
            "semana cae, así que tiene que ser verdad."
        ),
    )
    minutes: int | None = Field(None, description="Minutos de la sesión.")
    why: str = Field("", description="Una frase: qué entrena y por qué, para ESTE atleta.")
    groups: list[GrupoPropuesto]


class ProgramaPropuesto(BaseModel):
    sessions: list[SesionPropuesta] = Field(..., description="Las sesiones del programa, en el orden de rotación.")
    notes: str = Field("", description="Lo que el atleta debe saber del programa entero. Vacío si no hay nada.")


PROGRAMA_SYSTEM_PROMPT = """Eres el entrenador que le escribe a un atleta de resistencia su programa de gimnasio para este bloque.

Lo escribes UNA vez: el atleta lo revisa, lo confirma y se usa durante semanas, así que tiene que poder repetirlo y progresarlo, no ser una sesión suelta.

Reglas:
- **Sale de lo que ÉL declaró**: su objetivo, su equipo, cómo quiere repartirlo, los ejercicios seguidos que pidió, sus minutos, los levantamientos con barra que ya hace con buena técnica y lo que le molesta. No prescribas un movimiento con barra que no haya declarado, ni material que no tenga.
- **La fuerza acompaña a su deporte**: es un atleta que además corre, nada o va en bici, y esas sesiones no se resienten. Dilo en `why` de cada sesión.
- **Carga por esfuerzo** (RIR o RPE), nunca kilos: no sabes lo que levanta.
- **Una molestia declarada cambia los ejercicios que cargan ESA estructura**, no el programa entero, y no convierte el gimnasio en rehabilitación.
- `why` de cada sesión dice qué entrena y por qué encaja con ESTE atleta, con lo que declaró. Nada de frases genéricas de entrenador.
- Elige `video_key`, `garmin_category` y `garmin_exercise` SÓLO de las listas que se te dan y sólo si son exactamente ese movimiento; si no está, déjalo vacío y explica el movimiento en `cues`.
- **Cada sesión dice qué carga** (`emphasis`). No las hagas todas de pierna ni todas de cuerpo completo: sus días de gimnasio van entre sesiones de correr, nadar o bici, y la app coloca cada una donde menos estorbe a su calidad y a su tirada larga. Con tres días, un reparto que deja colocar es pierna / torso / completo; con cuatro, pierna 1 / torso 1 / pierna 2 / torso 2.
- Las sesiones van en el orden en que las escribas; el día lo elige después la semana, con sus sesiones clave delante."""

PROGRAMA_USER_PROMPT = """## El atleta
{perfil}

## Lo que entrena
{deporte}

## Días de gimnasio que pidió en su tabla
{dias}
Los días concretos los elige la app cada semana, con su calidad y su tirada larga delante: tú escribes las sesiones y qué carga cada una.
{molestias}
## Listas cerradas

### Nombres de Garmin (para que el reloj los muestre con su nombre)
{garmin}

### Claves de imagen (lo que el atleta ve en el gimnasio)
{imagenes}

Escribe {sesiones} sesión(es) distintas, en el orden en que se rotan."""


Llamar = Callable[[list], Awaitable[ProgramaPropuesto]]


def _slug(texto: str, usados: set[str]) -> str:
    base = exercise_catalog.normalizar(texto)[:40] or "sesion"
    slug, n = base, 2
    while slug in usados:
        slug, n = f"{base}_{n}", n + 1
    usados.add(slug)
    return slug


def cuantas_sesiones(dias_de_gimnasio: int, perfil=None) -> int:
    """Cuántas sesiones distintas tiene el programa: las que su tabla le da.

    **Sin forzar un número par** (2026-09-17). Cuando torso/pierna forzaba pares, una
    atleta con TRES días fijos recibía dos sesiones alternándose, y con la rotación de
    entonces le tocaba pierna un lunes y torso el siguiente, sin mirar que el lunes
    lleva calidad. Con tres días se escriben tres sesiones (pierna / torso / completo);
    el reparto lo decide el coach y el DÍA lo decide la semana.
    """
    return max(1, min(int(dias_de_gimnasio or 1), MAX_SESIONES))


def _perfil_para_el_prompt(perfil) -> str:
    if perfil is None:
        return "- No contestó nada de su fuerza."
    lineas = []
    if perfil.objetivo:
        lineas.append(f"- Para qué la hace: {perfil.objetivo}")
    if perfil.equipo:
        lineas.append(f"- Con qué entrena: {perfil.equipo.replace('_', ' ')}")
    if perfil.estructura:
        lineas.append(f"- Cómo quiere repartirla: {perfil.estructura.replace('_', ' ')}")
    if perfil.ejercicios_por_grupo:
        lineas.append(f"- Ejercicios seguidos: {perfil.ejercicios_por_grupo} "
                      f"(1 = uno a uno, 2-{EJERCICIOS_POR_GRUPO[1]} = superseries)")
    if perfil.minutos_por_sesion:
        lineas.append(f"- Minutos por sesión: {perfil.minutos_por_sesion} "
                      f"(entre {MINUTOS_POR_SESION[0]} y {MINUTOS_POR_SESION[1]})")
    if perfil.levantamientos_con_barra:
        lineas.append("- Levantamientos con barra que YA hace con buena técnica: "
                      + ", ".join(x.replace("_", " ") for x in perfil.levantamientos_con_barra))
    else:
        lineas.append("- No declaró ningún levantamiento con barra que ya haga con buena técnica.")
    return "\n".join(lineas)


def mensajes(perfil, dias_de_gimnasio: int, deporte: str, molestias: str) -> list[dict]:
    return [
        {"role": "system", "content": PROGRAMA_SYSTEM_PROMPT},
        {"role": "user", "content": PROGRAMA_USER_PROMPT.format(
            perfil=_perfil_para_el_prompt(perfil),
            deporte=deporte or "(no consta)",
            dias=f"{dias_de_gimnasio} a la semana" if dias_de_gimnasio else "(no marcó ninguno todavía)",
            molestias=f"\n## Lo que le molesta (lo que declaró)\n{molestias}\n" if molestias else "",
            garmin=garmin_vocabulary.render_exercise_catalogue(),
            imagenes=exercise_catalog.render_catalogo_para_prompt(),
            sesiones=cuantas_sesiones(dias_de_gimnasio, perfil),
        )},
    ]


def borrador_desde_el_programa(programa: ProgramaPropuesto, perfil=None) -> dict:
    """El programa del coach con la MISMA forma que una rutina pegada: misma revisión.

    Lo que no cuadra con lo que el atleta declaró se convierte en pregunta de la
    revisión (no en un arreglo silencioso): más ejercicios seguidos de los que pidió,
    o un levantamiento con barra que no dijo que hiciera.
    """
    ids: set[str] = set()
    sesiones = []
    # Las notas del coach NO son una duda: van en `notes` y la revisión las enseña
    # como "Cómo progresarlo" (hasta el 2026-09-23 salían en "Échale un ojo a esto").
    dudas: list[str] = []
    for sesion in programa.sessions:
        id_sesion = _slug(sesion.name, ids)
        grupos = []
        for g, grupo in enumerate(sesion.groups, start=1):
            if not grupo.exercises:
                continue
            ejercicios = []
            for e, ejercicio in enumerate(grupo.exercises, start=1):
                # La misma regla que una rutina pegada: el catálogo manda (exercise_catalog.garmin_de).
                categoria, garmin = exercise_catalog.garmin_de(
                    ejercicio.name, ejercicio.garmin_category, ejercicio.garmin_exercise)
                ejercicios.append({
                    "id": f"{id_sesion}_g{g}_e{e}",
                    "name": ejercicio.name.strip(),
                    "sets": ejercicio.sets,
                    "reps": ejercicio.reps.strip(),
                    "load": ejercicio.load.strip(),
                    "technique": ejercicio.cues.strip(),
                    "source_line": None,
                    "garmin_category": categoria,
                    "garmin_exercise": garmin,
                    "video_key": exercise_catalog.resolver(ejercicio.name, ejercicio.video_key),
                    "doubts": [],
                })
            grupos.append({
                "id": f"{id_sesion}_g{g}",
                "rest_between_rounds_s": grupo.rest_between_rounds_s,
                "rest_between_exercises_s": None,
                "exercises": ejercicios,
            })
        sesiones.append({
            "id": id_sesion,
            "name": sesion.name.strip(),
            "minutes": sesion.minutes,
            "source_line": None,
            "why": sesion.why.strip(),
            "emphasis": sesion.emphasis.strip().lower(),
            "groups": grupos,
        })
    dudas += _lo_que_no_cuadra(sesiones, perfil)
    return {
        "source": "coach",
        "original_text": "",
        "confirmed_at": None,
        # Lo que el coach quiera decir del programa entero se GUARDA (no sólo se lee en
        # la revisión): es lo que explica la rotación y cómo progresarlo, y el atleta lo
        # va a leer dentro de tres semanas, no hoy.
        "notes": programa.notes.strip(),
        "sessions": sesiones,
        "unread_lines": [],
        "doubts": dudas,
    }


_CON_BARRA = {
    "sentadilla": ("sentadilla con barra", "sentadilla trasera", "back squat", "sentadilla frontal"),
    "peso_muerto": ("peso muerto con barra", "peso muerto convencional", "deadlift"),
    "press_banca": ("press de banca con barra", "press banca con barra", "bench press"),
    "press_militar": ("press militar",),
    "remo_con_barra": ("remo con barra",),
    "hip_thrust": ("hip thrust con barra",),
}


def _lo_que_no_cuadra(sesiones: list[dict], perfil) -> list[str]:
    """Las preguntas que el atleta tiene que mirar antes de confirmar."""
    dudas = []
    if perfil is None:
        return dudas
    tope = perfil.ejercicios_por_grupo
    if tope:
        grandes = [f"{s['name']}" for s in sesiones for g in s["groups"] if len(g["exercises"]) > tope]
        if grandes:
            dudas.append(f"Pediste {tope} ejercicio(s) seguidos y hay grupos con más en: "
                         f"{', '.join(dict.fromkeys(grandes))}. Sepáralos si no te encajan.")
    declarados = set(perfil.levantamientos_con_barra)
    for sesion in sesiones:
        for grupo in sesion["groups"]:
            for ejercicio in grupo["exercises"]:
                nombre = ejercicio["name"].lower()
                for clave, formas in _CON_BARRA.items():
                    if clave in declarados:
                        continue
                    if any(forma in nombre for forma in formas):
                        aviso = (f"«{ejercicio['name']}» es un levantamiento con barra que no marcaste como "
                                 "tuyo. Si no lo haces con técnica, cámbialo o quítalo.")
                        ejercicio["doubts"].append(aviso)
    return dudas


async def escribir_el_programa(perfil, dias_de_gimnasio: int, deporte: str, molestias: str,
                               llamar: Llamar) -> tuple[dict, ProgramaPropuesto]:
    """(borrador para revisar, lo que escribió el modelo). No guarda nada."""
    programa = await llamar(mensajes(perfil, dias_de_gimnasio, deporte, molestias))
    borrador = borrador_desde_el_programa(programa, perfil)
    logger.info("Programa del coach: %d sesiones, %d ejercicios, %d dudas",
                len(borrador["sessions"]),
                sum(len(g["exercises"]) for s in borrador["sessions"] for g in s["groups"]),
                len(borrador["doubts"]) + sum(len(e["doubts"]) for s in borrador["sessions"]
                                              for g in s["groups"] for e in g["exercises"]))
    return borrador, programa
