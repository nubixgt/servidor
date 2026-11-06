"""El cuaderno del entrenador: lo que aprende de hablar contigo, con fecha.

Plan Maestro v7 Track 7.

## El problema

El chat del coach conoce el plan, las zonas, la adherencia y el razonamiento
archivado. Lo que no conoce es **lo que el atleta le contó ayer**. Cada
conversación empieza de cero, así que un dato como *"entrenar fuerte de tarde
me arruina el sueño"* o *"tomo un antihistamínico que me sube las pulsaciones"*
vive lo que dura la pestaña abierta.

Eso deja dos cosas rotas a la vez. La obvia: el atleta tiene que repetirse. La
que importa: **el plan de la semana siguiente no se entera**. Un coach que
recuerda y no cambia nada no es memoria, es cortesía.

## Por eso el cuaderno entra en DOS sitios

`render_notebook_block()` se inyecta en el contexto del chat **y** en el prompt
del planificador semanal, por el mismo canal lateral que ya llevan la ficha de
prescripción, las molestias declaradas y el check-in. Si sólo alimentara el
chat, el coach recordaría que las tardes te arruinan el sueño y la semana
siguiente te volvería a poner series a las 6 de la tarde.

## Nada se borra en silencio

Retirar una entrada la marca con fecha de baja y conserva el texto; no la
borra. Un cuaderno capaz de reescribir su propio pasado no es una memoria, es
una fuente de errores que nadie puede auditar -- y aquí los errores terminan
en un entreno. La única excepción es el atleta: sus datos son suyos y puede
eliminarlos de verdad, que es distinto de que el coach los tape.

## El coach propone, no dispone

La extracción sale por `with_structured_output`, nunca pidiéndole JSON en
prosa: es la falla exacta que rompió el chat del doctor con
`MALFORMED_RESPONSE`. Y las entradas nacen `propuesta`: el coach sugiere, el
atleta confirma. Lo contrario sería que una frase mal entendida en un chat
dirija en silencio el entrenamiento de las semanas siguientes.
"""

from __future__ import annotations

import json
import logging
import re
import uuid
from datetime import date, datetime
from pathlib import Path
from typing import Any

from pydantic import BaseModel, Field

logger = logging.getLogger(__name__)

NOTEBOOK_FILE = "notebook.json"

# Cuántas entradas vigentes caben en el prompt. El cuaderno crece sin límite y
# el prompt no: por encima de esto se envían las más recientes y se dice
# cuántas quedaron fuera, porque un bloque truncado en silencio hace que el
# modelo complete con seguridad lo que no vio.
MAX_ENTRIES_IN_PROMPT = 40

STATUS_ACTIVE = "vigente"
STATUS_PROPOSED = "propuesta"
STATUS_RETIRED = "retirada"

# Categorías, en el vocabulario del coach y no en el de la base de datos. Son
# orientación para agrupar el bloque del prompt, no una lista cerrada: una
# entrada con una categoría desconocida se guarda igual y aparece bajo "Otros".
# Perder el dato por no saber dónde ponerlo sería el peor de los dos errores.
CATEGORIES_ES = {
    "medicacion": "Medicación y suplementos",
    "sueno": "Sueño y horarios",
    "logistica": "Logística y disponibilidad",
    "preferencia": "Preferencias de entrenamiento",
    "molestia": "Molestias y antecedentes",
    "nutricion": "Nutrición",
    "vida": "Trabajo, viajes y vida",
    "otro": "Otros",
}


class NotebookProposal(BaseModel):
    """Una anotación que el coach propone tras una conversación."""

    text: str = Field(
        ...,
        description=(
            "La anotación, en español, en UNA frase y en tercera persona "
            "('Entrena mejor de mañana; las sesiones intensas de tarde le "
            "alteran el sueño'). Cita lo que el atleta dijo, no lo que tú "
            "concluyes de ello."
        ),
    )
    category: str = Field(
        ...,
        description="Una de: medicacion, sueno, logistica, preferencia, molestia, nutricion, vida, otro",
    )
    quote: str = Field(
        "",
        description="Las palabras del atleta que lo motivan, literales y breves. Vacío si no las hubo.",
    )
    affects_training: bool = Field(
        ...,
        description=(
            "True sólo si esto debería cambiar cómo se planifica su semana. "
            "Un dato interesante que no cambia nada no es una anotación."
        ),
    )


class NotebookExtraction(BaseModel):
    """Lo que salió de una conversación. Vacío es la respuesta normal."""

    entries: list[NotebookProposal] = Field(
        default_factory=list,
        description=(
            "Anotaciones NUEVAS y duraderas de este intercambio. La mayoría de "
            "los mensajes no producen ninguna: pregunta sobre el plan, dudas "
            "de ejecución, charla. Lista vacía es la respuesta correcta y "
            "esperada casi siempre."
        ),
    )


# --- almacenamiento --------------------------------------------------------

def _path(storage_dir: Path) -> Path:
    return Path(storage_dir) / NOTEBOOK_FILE


def load_notebook(storage_dir: Path) -> list[dict[str, Any]]:
    try:
        data = json.loads(_path(storage_dir).read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return []
    entries = data.get("entries") if isinstance(data, dict) else data
    return entries if isinstance(entries, list) else []


def save_notebook(storage_dir: Path, entries: list[dict[str, Any]]) -> None:
    path = _path(storage_dir)
    path.parent.mkdir(parents=True, exist_ok=True)
    payload = {"updated_at": datetime.now().isoformat(timespec="seconds"), "entries": entries}
    path.write_text(json.dumps(payload, indent=2, ensure_ascii=False), encoding="utf-8")


def _now() -> str:
    return datetime.now().isoformat(timespec="seconds")


def add_entry(
    storage_dir: Path,
    text: str,
    *,
    category: str = "otro",
    source: str = "manual",
    quote: str = "",
    status: str = STATUS_ACTIVE,
) -> dict[str, Any]:
    text = (text or "").strip()
    if not text:
        raise ValueError("Una anotación vacía no dice nada.")
    entry = {
        "id": uuid.uuid4().hex[:12],
        "text": text,
        "category": category if category in CATEGORIES_ES else "otro",
        "quote": (quote or "").strip(),
        "source": source,
        "status": status,
        "created_at": _now(),
        "updated_at": _now(),
        "retired_at": None,
        "retired_reason": "",
    }
    entries = load_notebook(storage_dir)
    entries.append(entry)
    save_notebook(storage_dir, entries)
    return entry


def update_entry(storage_dir: Path, entry_id: str, **fields: Any) -> dict[str, Any] | None:
    """Edita el texto, la categoría o el estado. El historial de fechas se
    conserva: `created_at` no se toca nunca."""
    entries = load_notebook(storage_dir)
    for entry in entries:
        if entry.get("id") != entry_id:
            continue
        if "text" in fields:
            nuevo = (fields["text"] or "").strip()
            if not nuevo:
                raise ValueError("Una anotación vacía no dice nada.")
            entry["text"] = nuevo
        if "category" in fields:
            entry["category"] = fields["category"] if fields["category"] in CATEGORIES_ES else "otro"
        if "status" in fields and fields["status"] in (STATUS_ACTIVE, STATUS_PROPOSED, STATUS_RETIRED):
            entry["status"] = fields["status"]
            if fields["status"] == STATUS_RETIRED:
                entry["retired_at"] = _now()
                entry["retired_reason"] = fields.get("retired_reason", "")
            else:
                entry["retired_at"] = None
                entry["retired_reason"] = ""
        entry["updated_at"] = _now()
        save_notebook(storage_dir, entries)
        return entry
    return None


def retire_entry(storage_dir: Path, entry_id: str, reason: str = "") -> dict[str, Any] | None:
    """Deja de aplicar, sin dejar de constar.

    Ésta es la operación que el COACH puede hacer. Borrar de verdad es del
    atleta (delete_entry): una memoria que puede reescribir su propio pasado
    no es auditable, y aquí lo que se audita termina en un entrenamiento.
    """
    return update_entry(storage_dir, entry_id, status=STATUS_RETIRED, retired_reason=reason)


def delete_entry(storage_dir: Path, entry_id: str) -> bool:
    """Borrado de verdad. Sólo a petición del atleta: son sus datos."""
    entries = load_notebook(storage_dir)
    quedan = [e for e in entries if e.get("id") != entry_id]
    if len(quedan) == len(entries):
        return False
    save_notebook(storage_dir, quedan)
    return True


def active_entries(storage_dir: Path) -> list[dict[str, Any]]:
    return [e for e in load_notebook(storage_dir) if e.get("status") == STATUS_ACTIVE]


def _normalize_quote(quote: str | None) -> str:
    """La cita, reducida a lo que la hace la misma cita.

    Sirve para detectar que dos anotaciones hablan del mismo hecho aunque el
    modelo las haya redactado distinto. Minúsculas y sin puntuación ni espacios
    de más: el atleta escribió una sola frase y las cuatro paráfrasis que
    genera el modelo la citan igual.
    """
    import re

    return re.sub(r"[^\w\s]", "", (quote or "").lower()).strip()


def deduplicate(storage_dir: Path) -> int:
    """Colapsa anotaciones que citan lo mismo. Devuelve cuántas se fueron.

    Conserva la MÁS ANTIGUA de cada grupo, porque su `created_at` es cuándo el
    atleta lo contó de verdad -- que es la fecha que el bloque del prompt usa
    para decidir si un dato sigue vigente. Las retiradas no se tocan: son
    historial, y colapsarlas borraría el rastro que existen para dejar.
    """
    entries = load_notebook(storage_dir)
    vistas: dict[str, str] = {}
    quedan = []
    for entry in sorted(entries, key=lambda e: e.get("created_at") or ""):
        cita = _normalize_quote(entry.get("quote"))
        if entry.get("status") == STATUS_RETIRED or not cita:
            quedan.append(entry)
            continue
        if cita in vistas:
            continue
        vistas[cita] = entry["id"]
        quedan.append(entry)
    fuera = len(entries) - len(quedan)
    if fuera:
        save_notebook(storage_dir, quedan)
    return fuera


# --- el bloque que leen el chat y el planificador --------------------------

def _age_note(created_at: str, today: date | None = None) -> str:
    """Cuánto hace de esto.

    La fecha va SIEMPRE. Un dato de hace catorce meses y uno de anteayer no
    valen lo mismo, y sin la fecha el modelo no tiene forma de saberlo -- se
    creería igual de vigente 'tiene una lesión en la rodilla' escrito el año
    pasado que el check-in de esta mañana.
    """
    try:
        cuando = datetime.fromisoformat(created_at).date()
    except (TypeError, ValueError):
        return ""
    dias = ((today or date.today()) - cuando).days
    if dias <= 0:
        return "hoy"
    if dias == 1:
        return "ayer"
    if dias < 30:
        return f"hace {dias} días"
    meses = dias // 30
    return f"hace {meses} mes" if meses == 1 else f"hace {meses} meses"


def _vigentes_para_el_prompt(storage_dir: Path) -> tuple[list[dict[str, Any]], int]:
    """Las anotaciones vigentes que caben, las más recientes primero, y cuántas no caben."""
    entries = active_entries(storage_dir)
    entries.sort(key=lambda e: e.get("created_at") or "", reverse=True)
    omitidas = max(0, len(entries) - MAX_ENTRIES_IN_PROMPT)
    return entries[:MAX_ENTRIES_IN_PROMPT], omitidas


def _lineas_de(entry: dict[str, Any], today: date | None) -> list[str]:
    edad = _age_note(entry.get("created_at", ""), today)
    sufijo = f" _(anotado {edad})_" if edad else ""
    lineas = [f"- {entry['text']}{sufijo}"]
    if entry.get("quote"):
        lineas.append(f'  - Sus palabras: "{entry["quote"]}"')
    return lineas


# Lo que el atleta pide de su entrenamiento, para la decisión semanal.
#
# Fernando, Beta 2.0 publicada, 2026-09-22: escribió en el cuaderno (categoría
# "Preferencias de entrenamiento") "Mantener sesiones de aproximadamente 2-3 km, con
# una sesión larga de 3 km" y cuatro nataciones por semana, y sus dos semanas
# siguientes le dieron tres nataciones de 1.200-1.800 m. En el prompt esa frase iba
# en el bloque del cuaderno, en mitad de la entrada, bajo "Son afirmaciones del
# atleta, no mediciones: contrástalas con sus datos de Garmin"; lo que el prompt sí
# llamaba petición con todas las letras era la rutina de gimnasio, y la semana
# respetó sus días de gimnasio y no sus metros.
CATEGORIA_DE_PETICIONES = "preferencia"


# Lo que se le dice a cada planificador de una petición. La semana la sigue; el
# macrociclo cuenta con ella. Antes del 2026-09-23 el macrociclo recibía el cuaderno
# entero bajo "contrástalas con Garmin" y escribió, sobre la natación de 2,5-3,5 km
# del atleta 2, "construir hacia ahí, no exigirlo desde ya" y "se ajustará bajando
# otra cosa": el fallo de Fernando, un nivel más arriba, y un texto que la semana
# recibe y que contradecía su regla.
_COMO_SE_LEE_UNA_PETICION = {
    "semana": ("Son peticiones suyas, como su tabla de disponibilidad, no afirmaciones que contrastar con "
               "Garmin: esta semana las sigue. Si pide metros por sesión, termina ESA natación con la cuenta "
               "de cada serie en una sola línea (\"Total: 400 + 8x50 (400) + 6x200 (1.200) + 500 = 2.500 m\") "
               "y comprueba que llega a lo que pidió: el reloj recibe las series, no el total. Haz la cuenta "
               "antes de escribir: el texto lleva ya los números finales, sin correcciones. Si alguna no puede "
               "ir, di en `week_rationale` cuál y por qué."),
    "macrociclo": ("Son peticiones suyas, como su tabla de disponibilidad, no afirmaciones que contrastar con "
                   "Garmin ni que aplazar: cada semana las sigue desde la primera, y no se pagan con los otros "
                   "deportes. Las horas y la carga de cada fase cuentan con ellas además de lo demás. Que hasta "
                   "ahora hiciera menos no es motivo para aplazarlas; una evidencia real sí (un dolor declarado, su "
                   "recuperación persistentemente por debajo de su normal, un afinamiento, la vuelta de una "
                   "carrera): entonces dilo en esa fase, con esa evidencia."),
}


def render_peticiones_de_entrenamiento(storage_dir: Path, today: date | None = None,
                                       para: str = "semana") -> str:
    """Las anotaciones de «Preferencias de entrenamiento», como PETICIÓN. "" si no hay.

    `para` es "semana" (la decisión semanal) o "macrociclo" (el planificador de temporada).
    """
    entries, _ = _vigentes_para_el_prompt(storage_dir)
    peticiones = [e for e in entries if (e.get("category") or "otro") == CATEGORIA_DE_PETICIONES]
    if not peticiones:
        return ""
    lineas = [
        "### Lo que el atleta pide de su entrenamiento (de su cuaderno, con fecha)",
        _COMO_SE_LEE_UNA_PETICION[para],
    ]
    for entry in peticiones:
        lineas.extend(_lineas_de(entry, today))
    return "\n".join(lineas)


def render_notebook_block(storage_dir: Path, today: date | None = None,
                          sin_peticiones: bool = False) -> str:
    """El cuaderno, listo para meter en un prompt. Vacío si no hay nada.

    Con `sin_peticiones` deja fuera las «Preferencias de entrenamiento», que la
    decisión semanal recibe aparte (`render_peticiones_de_entrenamiento`). El
    chat, el macrociclo y las preguntas del coach lo siguen recibiendo entero.
    """
    entries, omitidas = _vigentes_para_el_prompt(storage_dir)
    if sin_peticiones:
        entries = [e for e in entries if (e.get("category") or "otro") != CATEGORIA_DE_PETICIONES]
    if not entries:
        return ""

    por_categoria: dict[str, list[dict[str, Any]]] = {}
    for entry in entries:
        por_categoria.setdefault(entry.get("category") or "otro", []).append(entry)

    lineas = [
        "## Cuaderno del entrenador (lo que este atleta ha contado, con fecha)",
        "",
        "Notas acumuladas de conversaciones anteriores. **Son afirmaciones del atleta, "
        "no mediciones**: úsalas como contexto y contrástalas con sus datos de Garmin "
        "cuando se contradigan. Cada una lleva cuánto hace que se anotó, porque un dato "
        "de hace meses puede haber dejado de ser cierto. Lo que dice de CÓMO quiere entrenar "
        "(su rutina de gimnasio, sus días, lo que le funciona) es distinto: es una **petición**, "
        "como su tabla de disponibilidad. Síguela y hazla progresar, y si no la sigues di por qué.",
        "",
    ]
    for clave, titulo in CATEGORIES_ES.items():
        grupo = por_categoria.get(clave)
        if not grupo:
            continue
        lineas.append(f"**{titulo}**")
        for entry in grupo:
            lineas.extend(_lineas_de(entry, today))
        lineas.append("")

    if omitidas:
        # Nunca truncar en silencio: el lector tiene que poder distinguir
        # "esto es todo" de "esto es lo que cupo".
        lineas.append(
            f"_(Hay {omitidas} anotación(es) más antigua(s) que no caben aquí; "
            "se muestran las más recientes.)_"
        )
    return "\n".join(lineas).strip()


# --- lo que el coach propone anotar ----------------------------------------

EXTRACTION_SYSTEM_PROMPT = """Eres el entrenador de este atleta y acabas de terminar un intercambio con el atleta.

Tu tarea ahora NO es responderle. Es decidir si en lo que acaba de decir hay algo que
valga la pena recordar para las próximas semanas.

## Qué SÍ se anota
Hechos duraderos sobre el atleta que cambiarían cómo planificas su entrenamiento:
- Medicación o suplementos que toma, y cómo le afectan.
- Horarios que le funcionan o le perjudican (sueño, turnos, comidas).
- Restricciones logísticas estables (viaja los martes, la piscina cierra en agosto).
- Preferencias fuertes de entrenamiento, y su motivo.
- Molestias que describe como persistentes o que cambian cómo entrena, antecedentes o condiciones. Una
  sensación de un día o algo que ya se le pasó no es un hecho duradero: para eso está el check-in, y
  anotarlo lo convierte en algo que se le recuerda todas las semanas.
- Cambios de contexto vital que afectan la carga que puede asumir.

## Si el atleta PIDE que lo anotes, se anota. Sin excepción.
Es su cuaderno. Cuando dice "anótalo", "apúntalo", "que no se te olvide", "quiero que quede
registrado" o cualquier variante, **eso manda por encima de todo lo de abajo**: se anota aunque sea
un estado de un solo día, aunque te parezca poco importante, aunque no cambie ninguna decisión de
planificación. Marca `affects_training` según tu criterio, pero **anótalo igual**.

El motivo es concreto: pidió expresamente que se guardara, se le respondió, y no se guardó nada. Un
atleta al que le fallan las cosas que pide expresamente deja de pedirlas — y las reglas de abajo
están para filtrar lo que el atleta NO pidió, no para llevarle la contraria cuando sí lo pide.

## Qué NO se anota
(Todo esto queda anulado si el atleta pidió expresamente que lo anotaras.)
- Preguntas sobre el plan ("¿por qué el martes es suave?").
- Estados de un solo día ("hoy estoy cansado") — para eso está el check-in.
- Lo que ya está en el cuaderno que se te muestra abajo.
- Conclusiones tuyas. Anota lo que ÉL dijo, no lo que tú deduces.
- Datos que ya vienen de Garmin: zonas, ritmos, cargas, HRV.

## Cómo se escribe
En español, una frase, tercera persona, concreta. `quote` lleva sus palabras literales.
`affects_training` es True sólo si esto cambiaría una decisión de planificación.

**Lo normal es no anotar nada** — salvo que lo haya pedido. La mayoría de los
mensajes son preguntas sobre el plan, y ahí una lista vacía es la respuesta correcta.
No inventes una anotación para parecer atento: un cuaderno lleno de obviedades es peor
que uno vacío, porque entierra lo que sí importa.

Pero **si lo pidió, la lista vacía es un fallo**, no una respuesta prudente."""


# Cuando el atleta PIDE que se anote algo, no se le pregunta a un modelo si
# quiere hacerle caso.
#
# Falló dos veces seguidas: "necesito que lo escribas en mi cuaderno para la
# próxima generación" y no se anotó nada. La primera vez se arregló pidiéndoselo
# al modelo en el prompt; la segunda demostró que eso no basta. Una petición
# explícita del atleta es una de las pocas cosas de esta app que el código puede
# GARANTIZAR, y lo que el código puede garantizar no se delega en un modelo.
#
# El patrón es deliberadamente amplio -- anotar de más cuando lo pidió es
# trivial de deshacer con un clic; no anotar cuando lo pidió le hace perder la
# confianza en la función entera.
_PIDE_ANOTAR = re.compile(
    r"("
    r"an[oó]t\w*"                              # anota, anótalo, anotar, anotara
    r"|ap[uú]nt\w*"                            # apunta, apúntalo, apuntar
    # `reg[ií]str`, con la tilde: "regístralo" es como se escribe bien, y el
    # primer patrón sólo cazaba "registralo". Los acentos hay que ponerlos a
    # mano en cada rama -- \w los incluye, pero no convierte í en i.
    r"|reg[ií]str\w*"                          # registra, regístralo, registrar
    r"|gu[aá]rda\w*\s+(esto|eso|lo)?\s*(en|a)\s+(mi|el|tu)\s+cuaderno"
    # `escr[ií]b`, con la tilde. Era `escrib`, y "escríbelo en mi cuaderno" --
    # que es como se escribe bien -- no lo cazaba: la í no es una i para \w.
    # Mismo descuido que ya se corrigio arriba en `reg[ií]str`, en la rama de
    # al lado. Los acentos hay que ponerlos a mano en CADA rama.
    r"|escr[ií]b\w*\s+.{0,30}?cuaderno"        # "escribas en mi cuaderno"
    r"|(que\s+)?no\s+se\s+te\s+olvide"
    r"|que\s+quede\s+(registrad|anotad|guardad)\w*"
    # "que quede EN mi cuaderno" no lleva participio detras, asi que la rama
    # de arriba no lo veia. Es de las formas mas naturales de pedirlo y el
    # prompt del entrenador la nombra como ejemplo: si el codigo no la
    # reconoce, el entrenador confirma una anotacion que nadie escribe.
    r"|que\s+quede\s+en\s+(mi|el|tu)\s+cuaderno"
    r")",
    re.IGNORECASE,
)

# Cuánto del mensaje del atleta entra en la anotación de respaldo. Una frase
# larga sigue siendo útil; un párrafo entero convierte el cuaderno en un
# volcado del chat.
_MAX_RESPALDO = 400


def pidio_que_se_anotara(mensaje: str | None) -> bool:
    """Si el atleta pidió expresamente que esto quedara en su cuaderno."""
    return bool(_PIDE_ANOTAR.search(mensaje or ""))


async def propose_entries(
    storage_dir: Path,
    athlete_message: str,
    coach_reply: str,
) -> tuple[list[dict[str, Any]], dict[str, Any]]:
    """Mira UN intercambio y propone qué anotar. Devuelve (entradas, uso).

    Deliberadamente NO recibe el contexto entero del chat: sólo el intercambio
    y el cuaderno actual. Es lo que la hace barata (~1k tokens contra los ~8k
    de una respuesta del coach) y lo que la mantiene honesta -- si viera el
    plan entero, empezaría a "recordar" cosas que ya sabe por otras vías.
    """
    from langchain_core.callbacks.usage import get_usage_metadata_callback
    from langchain_core.messages import HumanMessage, SystemMessage

    from services.ai.ai_settings import AgentRole
    from services.ai.model_config import ModelSelector

    # Vigentes Y PROPUESTAS. Las propuestas contaban como "todavía no está en
    # el cuaderno", así que el modelo no veía las suyas pendientes y volvía a
    # proponer lo mismo en cada mensaje: una prueba real dejó 7 anotaciones
    # para 2 hechos, parafraseadas lo justo ("5:00 AM", "5 a.m.", "5am") para
    # que comparar textos no las cazara.
    existentes = [
        e for e in load_notebook(storage_dir)
        if e.get("status") in (STATUS_ACTIVE, STATUS_PROPOSED)
    ]
    ya_anotado = (
        "\n\n## Ya está en el cuaderno (no lo repitas)\n"
        + "\n".join(f"- {e['text']}" for e in existentes[-MAX_ENTRIES_IN_PROMPT:])
    ) if existentes else ""

    # El aviso va en el mensaje de usuario y no sólo en las reglas generales:
    # una regla se pondera, un "ESTE mensaje lo pidió" no se puede leer de otra
    # forma. Aun así hay respaldo determinista más abajo, porque esto sigue
    # siendo una instrucción y las instrucciones se incumplen.
    lo_pidio = pidio_que_se_anotara(athlete_message)
    aviso = (
        "\n\n**EL ATLETA HA PEDIDO EXPRESAMENTE QUE ANOTES ESTO.** Devolver una lista "
        "vacía aquí es un fallo: anótalo aunque sea un estado de un solo día o aunque "
        "te parezca poco importante."
        if lo_pidio else ""
    )

    mensajes = [
        SystemMessage(content=EXTRACTION_SYSTEM_PROMPT + ya_anotado),
        HumanMessage(content=(
            f"El atleta dijo:\n{athlete_message}\n\n"
            f"Tú respondiste:\n{coach_reply}\n\n"
            "¿Hay algo duradero que anotar?" + aviso
        )),
    ]

    # Rol propio desde el banco del 2026-09-23: el chat pasó a gpt-6-luna, que
    # conversa igual de bien y aquí perdió 2 de 3 anotaciones (ni una molestia 3/10).
    llm = ModelSelector.get_llm(AgentRole.NOTEBOOK)
    runnable = llm.with_structured_output(NotebookExtraction)
    with get_usage_metadata_callback() as usage_cb:
        try:
            extraccion = await runnable.ainvoke(mensajes)
        except Exception:
            # Que la memoria falle no puede costarle la respuesta al atleta:
            # ya la tiene. Se pierde la anotación y queda en el log.
            logger.exception("No se pudo extraer anotaciones del cuaderno")
            return [], dict(usage_cb.usage_metadata or {})
        uso = dict(usage_cb.usage_metadata or {})

    # La cita es lo que de verdad identifica un hecho. El texto lo redacta el
    # modelo y varía en cada pasada; la cita son las palabras del atleta, que
    # no cambian. Es lo único que caza las cuatro versiones de "los martes
    # entro a las 5" que una prueba real produjo.
    citas_conocidas = {_normalize_quote(e.get("quote")) for e in existentes if e.get("quote")}
    citas_conocidas.discard("")

    creadas = []
    for propuesta in extraccion.entries:
        if _normalize_quote(propuesta.quote) in citas_conocidas:
            continue
        if not propuesta.affects_training:
            # El propio esquema deja al modelo marcar lo que no cambia nada.
            # Filtrarlo aquí y no en el prompt es lo que hace la regla
            # verificable en vez de una súplica.
            continue
        try:
            creadas.append(add_entry(
                storage_dir,
                propuesta.text,
                category=propuesta.category,
                quote=propuesta.quote,
                source="chat",
                # Nace VIGENTE, por decisión del atleta (v7, segunda pasada).
                #
                # La primera versión las dejaba en `propuesta` hasta que él
                # las confirmara, razonando que una frase mal entendida no
                # debía dirigir el entrenamiento en silencio. El problema de
                # esa versión es de uso, no de lógica: una anotación que
                # requiere un segundo viaje a otra pestaña para servir de algo
                # es una anotación que nadie confirma, y un cuaderno que nunca
                # se llena no alimenta ningún plan.
                #
                # Lo que sustituye a la confirmación previa NO es nada: es
                # avisarlo SIEMPRE en la misma conversación, en el momento, y
                # dejar el borrado a un clic. El control sigue siendo del
                # atleta; lo que cambia es que el silencio ya no es el estado
                # por defecto de la memoria, y que revisar es una corrección
                # barata en vez de un trámite previo.
                #
                # `STATUS_PROPOSED` se conserva: sigue siendo el estado que
                # usa una entrada importada o dudosa, y la UI ya sabe
                # pintarla distinta.
                status=STATUS_ACTIVE,
            ))
        except ValueError:
            continue

    # RESPALDO. Si lo pidió y el modelo no anotó nada, se anota igual.
    #
    # Esto es lo único que convierte "se lo pedimos al modelo" en una garantía.
    # Falló dos veces con la petición escrita en el prompt, y la segunda vez el
    # entrenador incluso le dijo al atleta "lo acabo de escribir en tu
    # cuaderno". Una promesa hecha y no cumplida es peor que no haber ofrecido
    # la función.
    #
    # El texto sale de SUS palabras, no de una interpretación: aquí no hay
    # modelo que las resuma, así que inventarle una redacción sería peor que
    # citarle. La categoría es "otro" por lo mismo -- clasificar sin entender
    # es adivinar -- y el atleta puede corregir las dos cosas con un clic.
    if lo_pidio and not creadas:
        logger.info("El atleta pidio anotar y la extraccion no devolvio nada: se anota su mensaje")
        texto = " ".join((athlete_message or "").split())[:_MAX_RESPALDO]
        if texto:
            try:
                creadas.append(add_entry(
                    storage_dir,
                    f"Pidió que se anotara: {texto}",
                    category="otro",
                    quote=texto,
                    source="chat",
                    status=STATUS_ACTIVE,
                ))
            except ValueError:
                pass

    return creadas, uso
