"""La rutina que el atleta pega como texto, ordenada para que la confirme (Beta 2.1, S4).

Fernando (2026-09-17) mandó su rutina así, por WhatsApp:

    Upper A — 120 min
    Press de banca 4×8 - Elevaciones laterales — 4×12 - Crunch — 3×15
    ...
    Lower B — 60 min
    Extension de abductores (4×15R) - Extension de aductores (4×15R)

y aclaró en otro mensaje que cada línea son ejercicios "en simultáneo" con 2' entre
rondas. Eso NO está en el texto, así que aquí no se adivina: el formulario pregunta
cómo se agrupan y cuánto descansa, y lo que no quede claro se le pregunta al atleta
en la revisión.

## Quién hace qué

- **El modelo LEE** (compresión, no criterio: rol ``STRENGTH_ROUTINE_READER``, el
  barato). Copia nombres, series y repeticiones como están escritos, marca la línea
  de la que sale cada cosa y dice lo que no entiende. No corrige ni mejora nada:
  mejorar la rutina es del coach semanal, con motivo (S5).
- **El código COMPRUEBA** y convierte cada sospecha en una duda visible, nunca en
  un arreglo silencioso:
    * cada línea con texto se usó o aparece como no leída (``unread_lines``);
    * el nombre de cada ejercicio está en su línea;
    * sus series están en su línea;
    * una letra pegada a las repeticiones ("4×15R") es UNA duda para toda la rutina,
      con sus líneas, si nadie la preguntó ya;
    * sin descanso escrito ni dado en el formulario, se dice.
- **El código pone los ids** (estables, para que la semana pueda decir "cambio el
  ejercicio X") y resuelve la imagen por alias exacto (``exercise_catalog.resolver``)
  y el nombre de Garmin sólo si está verificado (``verified_exercise``).
- **El atleta confirma.** Hasta entonces no cuenta (``perfil_de_fuerza``).
"""

from __future__ import annotations

import logging
import re
from collections.abc import Awaitable, Callable

from pydantic import BaseModel, Field

from services.ai import exercise_catalog
from services.ai.perfil_de_fuerza import EJERCICIOS_POR_GRUPO
from services.garmin import garmin_vocabulary

logger = logging.getLogger(__name__)

# Cómo se agrupan los ejercicios: lo dice el atleta en el formulario, no el modelo.
AGRUPACION_POR_LINEA = "por_linea"      # cada línea es una superserie (Fernando)
AGRUPACION_UNO_A_UNO = "uno_a_uno"      # cada ejercicio va solo
AGRUPACION_LO_DICE_EL_TEXTO = "texto"   # "Superserie:", "Circuito", "A1/A2"...
AGRUPACIONES = (AGRUPACION_POR_LINEA, AGRUPACION_UNO_A_UNO, AGRUPACION_LO_DICE_EL_TEXTO)

# Un texto de rutina es corto. Un límite evita pegar por error un documento entero
# y pagar por leerlo; se le dice al atleta, no se recorta.
MAX_CARACTERES = 6000
MAX_LINEAS = 150


class RutinaDemasiadoLarga(ValueError):
    """El texto pasa del límite: se le dice al atleta en vez de recortarlo."""


# ── Lo que devuelve el modelo ─────────────────────────────────────────────────


class EjercicioLeido(BaseModel):
    name: str = Field(..., description="El nombre tal como está escrito, sin traducir ni corregir.")
    sets: int | None = Field(None, description="Series escritas. null si no están.")
    reps: str = Field("", description="Repeticiones o tiempo tal como están: '8', '10-12', '40 s'. Vacío si no están.")
    load: str = Field("", description="Carga sólo si está escrita ('20 kg', 'RIR 2'). Nunca inventada.")
    technique: str = Field("", description="Técnica sólo si está escrita: 'drop set', 'al fallo', '12/10/8'.")
    doubt: str = Field("", description="Lo que no está claro de este ejercicio, como pregunta al atleta. Vacío si nada.")
    line: int = Field(..., description="Número de la línea de la que sale.")
    garmin_category: str | None = Field(None, description="Categoría de la lista, sólo si es ese ejercicio seguro.")
    garmin_exercise: str | None = Field(None, description="Nombre de la lista, sólo si es ese ejercicio seguro.")


class GrupoLeido(BaseModel):
    exercises: list[EjercicioLeido] = Field(..., description="Ejercicios que se hacen seguidos, en orden.")
    rest_between_rounds_s: int | None = Field(None, description="Descanso entre rondas en segundos, sólo si está escrito.")
    rest_between_exercises_s: int | None = Field(None, description="Descanso entre ejercicios, sólo si está escrito.")


class SesionLeida(BaseModel):
    name: str = Field(..., description="El nombre de la sesión tal como está escrito ('Upper A').")
    emphasis: str = Field(
        "",
        description=(
            "Qué carga esa sesión, por sus ejercicios: 'pierna', 'torso', 'completo' o 'core'. "
            "No lo inventes de su nombre: míralo en lo que hay dentro. Vacío si no está claro."
        ),
    )
    minutes: int | None = Field(None, description="Minutos sólo si están escritos.")
    line: int = Field(..., description="Línea del encabezado de la sesión.")
    groups: list[GrupoLeido]


class LineaSinUsar(BaseModel):
    line: int
    reason: str = Field(..., description="Por qué no es un ejercicio ni una sesión.")


class RutinaLeida(BaseModel):
    sessions: list[SesionLeida]
    unused_lines: list[LineaSinUsar] = Field(default_factory=list)
    general_doubts: list[str] = Field(default_factory=list, description="Dudas que no son de un ejercicio.")


_REGLA_DE_AGRUPACION = {
    AGRUPACION_POR_LINEA: (
        "El atleta dice que los ejercicios de una MISMA línea los hace seguidos (superserie): "
        "cada línea con ejercicios es UN grupo, con sus ejercicios en el orden escrito."
    ),
    AGRUPACION_UNO_A_UNO: "El atleta dice que cada ejercicio va solo: cada ejercicio es su propio grupo.",
    AGRUPACION_LO_DICE_EL_TEXTO: (
        "Agrupa sólo lo que el texto marca como seguido ('superserie', 'circuito', 'A1/A2', '+'). "
        "Si el texto no deja claro si van juntos, pon cada ejercicio en su propio grupo y dilo en general_doubts."
    ),
}

PROMPT_DE_LECTURA = """Lees la rutina de gimnasio que un atleta pegó, tal como la escribió, y la pasas a una estructura.

NO eres su entrenador: no corriges, no mejoras, no completas, no traduces. Si algo no está escrito, queda vacío o null.
- Nombres, series y repeticiones exactamente como están ("Geelos" se queda "Geelos"; "4×10–12" son 4 series de "10-12").
- Cada sesión empieza en su encabezado ("Upper A — 120 min"). Cada ejercicio lleva el número de su línea.
- {agrupacion}
- Lo que no entiendas (una abreviatura, una letra pegada a las repeticiones, un nombre raro) va en `doubt` como pregunta corta al atleta. No lo resuelvas tú.
- Una línea que no es sesión ni ejercicio va en unused_lines con el motivo.
- `emphasis` de cada sesión sale de SUS ejercicios (pierna, torso, completo o core). Sirve para colocarla en la semana entre sus sesiones de correr, nadar o bici, así que no lo adivines del nombre.
- garmin_category y garmin_exercise: sólo si el ejercicio es inequívocamente uno de esta lista; si dudas, null.
{catalogo}"""


def numerar(texto: str) -> list[tuple[int, str]]:
    """Las líneas del texto con su número (desde 1), tal cual."""
    return [(i, linea) for i, linea in enumerate((texto or "").splitlines(), start=1)]


def mensaje_para_el_modelo(texto: str, agrupacion: str) -> tuple[str, str]:
    """(sistema, usuario). Las líneas van numeradas para que cada ejercicio diga de dónde sale."""
    sistema = PROMPT_DE_LECTURA.format(
        agrupacion=_REGLA_DE_AGRUPACION.get(agrupacion, _REGLA_DE_AGRUPACION[AGRUPACION_LO_DICE_EL_TEXTO]),
        catalogo=garmin_vocabulary.render_exercise_catalogue(),
    )
    usuario = "\n".join(f"L{n}: {linea}" for n, linea in numerar(texto))
    return sistema, usuario


# ── Las comprobaciones en código ──────────────────────────────────────────────


# Los mismos cuatro que usa la colocación (langgraph/nodes/paso_de_fuerza.py).
ENFASIS = ("pierna", "torso", "completo", "core")


def _enfasis(valor: str) -> str:
    """Lo que no se reconoce se deja vacío: quien lo lee trata el vacío como 'completo'."""
    limpio = (valor or "").strip().lower()
    return limpio if limpio in ENFASIS else ""


def _tokens(texto: str) -> set[str]:
    return {t for t in exercise_catalog.normalizar(texto).split("_") if t}


def _slug(texto: str, usados: set[str]) -> str:
    base = exercise_catalog.normalizar(texto)[:40] or "sesion"
    slug, n = base, 2
    while slug in usados:
        slug, n = f"{base}_{n}", n + 1
    usados.add(slug)
    return slug


_UNIDADES_CLARAS = frozenset({"s", "seg", "segs", "sec", "min", "m", "kg", "kgs", "lb", "lbs", "rep", "reps"})
_LETRA_PEGADA = re.compile(r"\d+\s*[x×]\s*\d+(?:\s*[-–]\s*\d+)?([A-Za-zÁÉÍÓÚáéíóú]+)\b")


def dudas_del_ejercicio(ejercicio: EjercicioLeido, linea: str) -> list[str]:
    """Lo que el código no puede dar por bueno de un ejercicio leído."""
    dudas = []
    if ejercicio.doubt.strip():
        dudas.append(ejercicio.doubt.strip())
    if not linea:
        dudas.append(f"«{ejercicio.name}» dice venir de la línea {ejercicio.line}, que no existe.")
        return dudas
    if not _tokens(ejercicio.name) <= _tokens(linea):
        dudas.append(f"«{ejercicio.name}» no está escrito así en la línea {ejercicio.line}: revisa el nombre.")
    if ejercicio.sets is None or not ejercicio.reps.strip():
        dudas.append(f"Faltan las series o las repeticiones de «{ejercicio.name}».")
    elif not re.search(rf"(?<!\d){ejercicio.sets}(?!\d)", linea):
        dudas.append(f"Las {ejercicio.sets} series de «{ejercicio.name}» no están en la línea {ejercicio.line}.")
    return dudas


_LETRA = "A-Za-zÁÉÍÓÚÜÑáéíóúüñ"


def _como_palabra(texto: str) -> str:
    """Un patrón que encuentra `texto` como palabra suelta, con o sin comillas alrededor.

    El modelo escribe las dudas como quiere: en las lecturas de la rutina de Fernando
    salió "la 'R'" y también "la R en las repeticiones"; "'Geelos'" y "es Geelos?".
    """
    return rf"(?<![{_LETRA}]){re.escape(texto)}(?![{_LETRA}])"


def _menciona(dudas: list[str], letra: str) -> bool:
    """Si alguna duda ya pregunta por esa letra (distingue mayúsculas: «R» no es «r»)."""
    return any(re.search(_como_palabra(letra), d) for d in dudas)


def dudas_de_notacion(lineas: dict[int, str], ya_preguntado: list[str], explicadas: str) -> list[str]:
    """Una pregunta por cada letra pegada a las repeticiones que nadie entendió ni preguntó.

    En la rutina de Fernando, "4×15R" aparece en las cuatro líneas de su Lower B: son
    una pregunta, no ocho.
    """
    donde: dict[str, list[int]] = {}
    for n, linea in lineas.items():
        for letra in _LETRA_PEGADA.findall(linea):
            donde.setdefault(letra, []).append(n)
    dudas = []
    for letra, numeros in donde.items():
        if _menciona(ya_preguntado, letra) or re.search(rf"\b{re.escape(letra)}\b", explicadas):
            continue
        # Unidades que no admiten otra lectura: "3x45s" son 45 segundos. Preguntarlas
        # (auditoría del 2026-09-23, "Plancha 3x45s") enseña a no leer las dudas.
        if letra.lower() in _UNIDADES_CLARAS:
            continue
        lista = ", ".join(str(n) for n in dict.fromkeys(numeros))
        dudas.append(f"¿Qué significa «{letra}» después de las repeticiones (líneas {lista})?")
    return dudas


def _dudas_que_nombran_un_ejercicio(sesiones: list[dict], dudas_generales: list[str]) -> None:
    """Una duda que nombra un ejercicio le quita su nombre de Garmin, y si nombra sólo
    ese ejercicio, pasa a ser SUYA (sale de las generales, para no preguntarla dos veces).

    En las tres lecturas reales de la rutina de Fernando (2026-09-17, flash-lite) el
    modelo preguntó "¿Qué es el ejercicio 'Geelos'?" y en dos de ellas le puso igual
    STANDING_CALF_RAISE. Un ejercicio que ni el modelo sabe cuál es no puede llegar al
    reloj con el nombre de otro. En la cuarta, desde la app, escribió "¿Qué ejercicio
    es Geelos?", sin comillas: se busca el nombre como palabra, con o sin ellas.
    """
    ejercicios = [e for s in sesiones for g in s["groups"] for e in g["exercises"]]
    for duda in list(dudas_generales):
        nombrados = [e for e in ejercicios if re.search(_como_palabra(e["name"]), duda, re.I)]
        if not nombrados:
            continue
        for ejercicio in nombrados:
            _sin_nombre_de_garmin(ejercicio)
        if len({e["name"].lower() for e in nombrados}) == 1 and len(nombrados) == 1:
            nombrados[0]["doubts"].append(duda)
            dudas_generales.remove(duda)
    for ejercicio in ejercicios:
        if any(re.search(_como_palabra(ejercicio["name"]), d, re.I) for d in ejercicio["doubts"]):
            _sin_nombre_de_garmin(ejercicio)


def _sin_nombre_de_garmin(ejercicio: dict) -> None:
    if ejercicio["garmin_category"] or ejercicio["garmin_exercise"]:
        logger.info("Rutina: «%s» tiene una duda sobre qué es; se quita su nombre de Garmin", ejercicio["name"])
    ejercicio["garmin_category"] = None
    ejercicio["garmin_exercise"] = None


_MARCAS_DE_GRUPO = re.compile(r"superserie|biserie|triserie|circuito|\+|\b[a-z]\d\b|seguid|simult", re.I)


def dudas_de_agrupacion(sesiones: list[dict], lineas: dict[int, str]) -> list[str]:
    """Si el modelo juntó ejercicios sin que el texto lo marque, se pregunta.

    Con "lo dice mi texto" y la rutina de Fernando, que separa con " - ", flash-lite
    juntó cada línea en una superserie sin decirlo, pese a que el prompt pedía
    preguntar. Esa agrupación la sabemos por su WhatsApp, no por el texto.
    """
    sin_marca = sorted({
        e["source_line"]
        for s in sesiones for g in s["groups"] if len(g["exercises"]) > 1
        for e in g["exercises"]
        if not _MARCAS_DE_GRUPO.search(lineas.get(e["source_line"], ""))
    })
    if not sin_marca:
        return []
    lista = ", ".join(str(n) for n in sin_marca)
    return [f"Juntamos como seguidos (superserie) los ejercicios de las líneas {lista}, pero el texto no lo dice: "
            "revisa cada grupo y sepáralos si no van juntos."]


def borrador_desde_lectura(
    lectura: RutinaLeida,
    texto: str,
    descanso_entre_rondas_s: int | None = None,
    agrupacion: str = AGRUPACION_LO_DICE_EL_TEXTO,
) -> dict:
    """La rutina lista para revisar: ids, imagen, Garmin verificado y todas las dudas a la vista."""
    lineas = dict(numerar(texto))
    usadas: set[int] = {u.line for u in lectura.unused_lines}
    ids: set[str] = set()
    sesiones = []

    for sesion in lectura.sessions:
        usadas.add(sesion.line)
        id_sesion = _slug(sesion.name, ids)
        grupos = []
        for g, grupo in enumerate(sesion.groups, start=1):
            if not grupo.exercises:
                continue
            ejercicios = []
            for e, ejercicio in enumerate(grupo.exercises, start=1):
                usadas.add(ejercicio.line)
                # El catálogo primero: el modelo puso HAMSTRING_CURL (un estiramiento) a la
                # extensión de cuádriceps del atleta 2. Ver exercise_catalog.garmin_de.
                categoria, garmin = exercise_catalog.garmin_de(
                    ejercicio.name, ejercicio.garmin_category, ejercicio.garmin_exercise)
                ejercicios.append({
                    "id": f"{id_sesion}_g{g}_e{e}",
                    "name": ejercicio.name.strip(),
                    "sets": ejercicio.sets,
                    "reps": ejercicio.reps.strip(),
                    "load": ejercicio.load.strip(),
                    "technique": ejercicio.technique.strip(),
                    "source_line": ejercicio.line,
                    "garmin_category": categoria,
                    "garmin_exercise": garmin,
                    "video_key": exercise_catalog.resolver(ejercicio.name),
                    "doubts": dudas_del_ejercicio(ejercicio, lineas.get(ejercicio.line, "")),
                })
            grupos.append({
                "id": f"{id_sesion}_g{g}",
                "rest_between_rounds_s": grupo.rest_between_rounds_s or descanso_entre_rondas_s,
                "rest_between_exercises_s": grupo.rest_between_exercises_s,
                "exercises": ejercicios,
            })
        sesiones.append({
            "id": id_sesion,
            "name": sesion.name.strip(),
            "minutes": sesion.minutes,
            "source_line": sesion.line,
            # Qué carga: decide en qué día de su semana cae (paso_de_fuerza). Lo que no
            # se reconoce queda vacío y se lee como "completo", que es lo prudente.
            "emphasis": _enfasis(sesion.emphasis),
            "groups": grupos,
        })

    dudas_generales = list(dict.fromkeys(d.strip() for d in lectura.general_doubts if d.strip()))
    _dudas_que_nombran_un_ejercicio(sesiones, dudas_generales)
    if agrupacion == AGRUPACION_LO_DICE_EL_TEXTO:
        dudas_generales += dudas_de_agrupacion(sesiones, lineas)
    todas = dudas_generales + [d for s in sesiones for g in s["groups"] for e in g["exercises"] for d in e["doubts"]]
    tecnicas = " ".join(e["technique"] for s in sesiones for g in s["groups"] for e in g["exercises"])
    dudas_generales += dudas_de_notacion(lineas, todas, tecnicas)
    sin_descanso = [g for s in sesiones for g in s["groups"] if not g["rest_between_rounds_s"]]
    if sin_descanso:
        dudas_generales.append("No encontramos el descanso entre rondas: escríbelo en cada grupo que lo necesite.")
    grandes = [g for s in sesiones for g in s["groups"] if len(g["exercises"]) > EJERCICIOS_POR_GRUPO[1]]
    if grandes:
        dudas_generales.append(
            f"Hay grupos de más de {EJERCICIOS_POR_GRUPO[1]} ejercicios seguidos: revisa si van todos juntos.")

    no_leidas = [
        {"line": n, "text": linea.strip(),
         "reason": next((u.reason for u in lectura.unused_lines if u.line == n), "No se leyó.")}
        for n, linea in lineas.items()
        if linea.strip() and (n not in usadas or any(u.line == n for u in lectura.unused_lines))
    ]
    return {
        "source": "atleta",
        "original_text": texto,
        "confirmed_at": None,
        "sessions": sesiones,
        "unread_lines": no_leidas,
        "doubts": dudas_generales,
    }


# ── La llamada ────────────────────────────────────────────────────────────────


LeerConModelo = Callable[[str, str], Awaitable[tuple[RutinaLeida, dict]]]


async def _leer_con_el_modelo(sistema: str, usuario: str) -> tuple[RutinaLeida, dict]:
    from langchain_core.callbacks.usage import get_usage_metadata_callback
    from langchain_core.messages import HumanMessage, SystemMessage

    from services.ai.ai_settings import AgentRole
    from services.ai.model_config import ModelSelector

    llm = ModelSelector.get_llm(AgentRole.STRENGTH_ROUTINE_READER)
    runnable = llm.with_structured_output(RutinaLeida)
    with get_usage_metadata_callback() as uso_cb:
        lectura = await runnable.ainvoke([SystemMessage(content=sistema), HumanMessage(content=usuario)])
        return lectura, dict(uso_cb.usage_metadata or {})


async def ordenar_rutina(
    texto: str,
    agrupacion: str = AGRUPACION_LO_DICE_EL_TEXTO,
    descanso_entre_rondas_s: int | None = None,
    leer: LeerConModelo | None = None,
) -> tuple[dict, dict]:
    """(borrador para revisar, uso del modelo). No guarda nada: guarda la confirmación."""
    texto = (texto or "").strip("\n")
    if not texto.strip():
        raise ValueError("No hay rutina que ordenar.")
    if len(texto) > MAX_CARACTERES or len(texto.splitlines()) > MAX_LINEAS:
        raise RutinaDemasiadoLarga(
            f"La rutina tiene {len(texto)} caracteres y {len(texto.splitlines())} líneas; "
            f"el máximo es {MAX_CARACTERES} y {MAX_LINEAS}. Pega sólo las sesiones de fuerza.")
    if agrupacion not in AGRUPACIONES:
        agrupacion = AGRUPACION_LO_DICE_EL_TEXTO
    sistema, usuario = mensaje_para_el_modelo(texto, agrupacion)
    lectura, uso = await (leer or _leer_con_el_modelo)(sistema, usuario)
    borrador = borrador_desde_lectura(lectura, texto, descanso_entre_rondas_s, agrupacion)
    logger.info(
        "Rutina ordenada: %d sesiones, %d ejercicios, %d líneas sin leer, %d dudas",
        len(borrador["sessions"]),
        sum(len(g["exercises"]) for s in borrador["sessions"] for g in s["groups"]),
        len(borrador["unread_lines"]),
        len(borrador["doubts"]) + sum(len(e["doubts"]) for s in borrador["sessions"]
                                      for g in s["groups"] for e in g["exercises"]),
    )
    return borrador, uso
