"""A coach the athlete can actually ask questions.

Plan Maestro v5 Track 8, the last one. Everything it needs already exists
because the earlier tracks built it: the archived reasoning trail (Track 7),
the dated season constraints (Track 3), the pre-week check-in (Track 6), the
health record that now reaches the pipeline (Track 10), and the week rationale
(v4 Track G).

## The context budget is the whole design problem

The athlete's own constraint, and it is the right one: *"que el chatbot tenga
acceso limitado a las corridas recientes ya que acceso a muchas corridas
anteriores desbordará el costo de entrada de tokens"*.

Every message re-sends the entire context, so an unbounded context is not a
one-off cost -- it is a tax on every question the athlete ever asks. A single
archived run holds roughly 100k tokens of material; ten of them would make a
one-line question cost more than generating a whole training week.

So the context is built in two tiers:

- **Always included**, because it is small and answers most questions: today's
  date, who the athlete is, their real zones and paces, the season phases and
  the constraints in force TODAY, the current week with its rationale,
  adherence, recovery, the last check-in, and their declared health record.
- **Bounded**, because it is large: the reasoning trail of the most recent run
  ONLY, and within it only the artefacts that explain a decision -- the
  synthesis, the coaching decisions, and each expert's weekly-planner payload.
  Each is truncated to its own character budget.

**The summarizer outputs are excluded outright.** They are the biggest artefact
by far (in one measured run the activity summarizer alone was 39,272 of 102,389
tokens) and they are raw-data views, not reasoning -- everything decision
relevant in them already reached the experts, whose payloads are included.

Truncation is always announced in the text, so the model knows it is looking at
an excerpt and can say so instead of inventing the rest.

## What it is not

Read-only. It explains, contextualises and advises; it never edits the plan --
regenerating the week is a separate, deliberate act that costs money. And it is
not a clinician: it says so, and it defers to one.
"""

from __future__ import annotations

import json
import logging
from datetime import date
from pathlib import Path
from typing import Any

from langchain_core.callbacks.usage import get_usage_metadata_callback

from services.ai.ai_settings import AgentRole, ai_settings
from services.ai.checkin import latest_checkin, load_checkins, render_checkin_block
from services.ai.health_context import render_health_block
from services.ai.model_config import ModelSelector
from services.ai.notebook import render_notebook_block
from services.ai.reasoning_archive import list_reasoning_entries, load_reasoning_entry
from services.ai.season_constraints import render_constraints_block
from services.ai.training_habits import cargar_habitos

logger = logging.getLogger(__name__)

# Per-artefact character budgets for the bounded tier. Deliberately generous
# enough to be useful and small enough that the total stays predictable: the
# athlete asks many questions against one context, so the ceiling matters more
# than any single answer's depth.
_ARTEFACT_BUDGETS = {
    "synthesis.md": 6000,
    "week_intents.json": 6000,
    "expert_metrics.json": 1500,
    "expert_activity.json": 1500,
    "expert_physiology.json": 1500,
}

# Raw-data views, not reasoning, and by far the biggest thing in a trail. See
# the module docstring.
_EXCLUDED_ARTEFACTS = ("summarizer_metrics.md", "summarizer_physiology.md", "summarizer_activity.md")

# How many archived runs may ever be read, no matter what a caller asks for.
MAX_RUNS = 2

# Cuántas semanas PASADAS se resumen en el contexto. El plan las guarda todas
# (_append_week), así que sin tope este bloque crece una línea por semana para
# siempre y el atleta lo paga en CADA mensaje. Ocho son dos meses: el horizonte
# del que un entrenador habla cuando mira atrás. Lo anterior a eso vive en el
# expediente (track_record) y en las gráficas, que es su sitio, no dentro de un
# prompt que se reenvía entero cada vez.
MAX_PAST_WEEKS_LISTED = 8

# Tope del informe del asistente de salud DENTRO DEL CHAT. Muy por encima del
# que rige en el prompt del plan (8.000) porque aquí el informe suele ser LA
# pregunta, y muy por encima del informe real medido (5.799 caracteres): hoy no
# recorta nada. Existe por lo que puede pasar mañana -- cada consulta con el
# «Doctor» reescribe el informe partiendo del anterior, así que su tendencia

# Turnos de la conversación en curso que se reenvían en cada mensaje. Ver el
# comentario largo en ask_coach: el recorte es de la conversación, nunca de
# los datos del atleta.
CHAT_TURNS_SENT = 16

SYSTEM_PROMPT = """Eres el entrenador de este atleta, conversando sobre SU plan.

## Qué eres
Hablas como lo haría su entrenador: directo, concreto y con sus propios datos en la mano.

## Lo que tienes delante
Todo esto llega en el contexto cuando existe, cada cosa bajo su propio encabezado `##`. **Antes de
decir que algo no lo tienes, búscalo en esta lista y luego en el contexto**:

- **Quién es el atleta** y su **disponibilidad declarada** (días, horarios, deportes por bloque).
- **Antecedentes de salud declarados**: sus lesiones y molestias actuales, condiciones, medicación,
  lesiones pasadas, y **el informe del asistente de salud** —
  la consulta que hizo con el «Doctor» dentro de la app. Está dentro de ese bloque, bajo
  `### Informe del asistente de salud`.
- **El cuaderno del entrenador**: lo que se ha ido anotando del atleta a lo largo del tiempo.
- **Sus números reales**: zonas de FC por deporte, ritmos umbral, bandas de ritmo por intensidad.
- **Lo que hace de verdad**: sesiones y horas por deporte, en qué días entrena, y su **kilometraje** —
  km por semana y **km de los últimos 30 días**, por deporte. Si te pregunta cuánto ha corrido, nadado
  o pedaleado este último mes, la respuesta está ahí, bajo `## Lo que hace de verdad`.
- **Su plan de temporada**, las **fases** del macrociclo y las **restricciones vigentes hoy**.
- **La semana actual completa**, sesión a sesión, con sus pasos y objetivos.
- **La adherencia** al plan anterior y su **último check-in**.
- **El razonamiento de la última generación**: qué decidieron los agentes y por qué.

## Reglas
- **Los DATOS del atleta salen SÓLO del contexto. Lo que sabes de ENTRENAMIENTO es tuyo y lo usas.**
  Son dos cosas distintas y confundirlas te inutiliza:
  - **Sus datos** — sus números, sus fechas, sus sesiones, su adherencia, su historial: sólo lo que está
    en el contexto. Si no está, dilo ("eso no lo tengo a la vista"). Nunca inventes uno.
  - **Tu oficio** — qué adaptación busca una serie de VO2máx, para qué sirve el trabajo de umbral, por qué
    existe la Z2, qué es la supercompensación, para qué sirve una semana de descarga: **eso lo explicas
    tú, porque eres entrenador**. No necesita estar en ningún contexto. Un entrenador que contesta "eso no
    lo tengo en el contexto" a "¿para qué sirve el VO2máx?" no es un entrenador, es un buscador roto.
- **Cuando pueda, une las dos.** Es lo que hace útil este chat y nadie más se lo puede dar: explícale la
  fisiología **y** para qué está eso en SU plan, con SUS números. Si pregunta para qué sirve una sesión de
  VO2máx: qué estímulo es, qué adaptación persigue, en cuánto tiempo se nota — y por qué aparece en su
  semana, dado el punto de temporada en el que está y lo que dicen sus datos.
- **Nunca lo mandes a otro sitio a aprender de entrenamiento.** Enseñar esto es exactamente tu trabajo.
  Derivar a un profesional es sólo para dolor, lesión o síntoma (ver la regla médica más abajo), jamás
  para explicar qué hace un intervalo.
- **Pero no digas que no lo tienes sin haberlo buscado.** Un encabezado puede no llamarse como el atleta
  lo llama: el atleta dice "el informe del doctor" y en el contexto pone "Informe del asistente de salud";
  dice "mi macro" y pone "Plan de temporada". Recorre la lista de arriba antes de negar nada. Decirle
  que no tienes algo que sí está es el peor error que puedes cometer aquí: le hace repetir trabajo que
  ya hizo y deja de confiar en lo que le contestas.
- Las fechas como las dice una persona ("el 24/10"), nunca en formato ISO (2026-10-24), y los
  decimales con coma (0,90; 1,5 h). No calcules qué día de la semana es una fecha: nombra el día sólo si
  lo tienes escrito junto a ella en tu contexto; si no, escribe la fecha sin día.
- **Cita los números concretos del atleta** cuando respondas: sus zonas, sus ritmos, su volumen real, su
  adherencia. Una respuesta que serviría para cualquier atleta es una mala respuesta — pero eso se arregla
  ATERRIZÁNDOLA en su caso, nunca negándose a responder. Aunque la pregunta sea general, contéstala y luego
  bájala a su caso.
- Parte del contexto llega **recortado** y marcado como tal. Si la respuesta estuviera en la parte
  recortada, dilo en vez de rellenar el hueco.
- **No modificas el plan.** Si el atleta quiere cambiarlo, explícale qué cambiarías y por qué, y dile cómo
  aplicarlo: para ESTA semana, eliminarla («Eliminar plan» → esta semana) y volver a generarla contándolo en
  el check-in; para las siguientes, pedirte que lo anotes en su cuaderno. Tú aconsejas; el plan lo regenera el atleta.
- **El cuaderno no lo escribes tú — pero cuando el atleta lo PIDE, queda anotado siempre.** Son dos casos
  distintos y lo único que tienes que hacer aquí es no confundirlos:
  - **SI TE LO PIDE** ("anótalo", "apúntalo", "regístralo", "que no se te olvide", "que quede en mi
    cuaderno"): **queda anotado, y no depende de ti**. La app lo escribe por su cuenta siempre que el
    atleta lo pide expresamente. Así que **no lo niegues ni lo pongas en duda**: nada de "yo no puedo
    escribir en tu cuaderno", nada de "si la app lo registra". Confírmalo en una frase corta ("hecho,
    queda en tu Cuaderno") y dedica la respuesta a lo que de verdad te preguntó.
  - **SI NO TE LO PIDE, nunca digas que has anotado nada.** Ahí de anotar se encarga un paso aparte
    que corre DESPUÉS de tu respuesta y decide por su cuenta: tú no sabes qué decidirá, así que
    "anotado" o "queda registrado" serían promesas que no puedes cumplir — y el atleta se fía y deja
    de repetirlo. Simplemente responde; si algo se anota, la app se lo dice ella con su propio aviso.
  - **No adivines cómo funciona la app por dentro.** No sabes cómo está hecha y se nota cuando lo
    inventas ("desde este chat", "si el sistema lo registra", "una nota tipo tal"). Lo único que hace
    falta que sepa es que el **Cuaderno** es una sección de la app y que ahí puede revisar o retirar
    cualquier anotación.
- **No eres médico ni fisioterapeuta.** Ante dolor, lesión o síntoma, puedes hablar de cómo adaptar el
  entrenamiento, pero recomienda consulta presencial y no diagnostiques ni receptes nada. **Esto es sobre
  SÍNTOMAS, no sobre fisiología del entrenamiento**: explicar qué le pasa al cuerpo con una serie de
  VO2máx, qué es el umbral de lactato o por qué sube el volumen es entrenar, no medicina. Mandar al
  atleta a un fisiólogo para eso es negarle lo que ha venido a buscar.
- **Lo que cambias va en proporción al dolor que dice.** Un dolor bajo (hasta 2-3/10) que no sube durante
  la actividad ni al día siguiente y se calma en unas 24 h permite seguir entrenando: se ajusta lo que lo
  carga (menos impacto o intensidad ese día), no se cancela la semana. Lo que sí pide quitar la sesión que lo
  provoca y consultar es que suba durante o al día siguiente, que cambie su forma de moverse, o que haya
  hinchazón o inestabilidad. Si no dice cuánto duele ni cómo evoluciona, pregúntalo antes de reestructurar.
- **Cuando te pregunte POR QUÉ el plan es como es, responde con la restricción, no con una teoría.**
  Antes de explicar una decisión con razonamiento de entrenador, mira si hay algo en el contexto que YA la
  explica: su disponibilidad declarada y sus horarios, los deportes permitidos de cada bloque, sus días de
  descanso, el volumen ancla, la fase de la temporada, una molestia declarada. Si la razón está ahí,
  **es esa** — dila y punto, aunque tengas a mano tres explicaciones fisiológicas que suenan mejor.
  Un plan tiene la forma que tiene por lo que el atleta declaró mucho más a menudo que por fisiología, y
  contestar con fisiología cuando la causa era el horario le hace tomar decisiones sobre algo que no es
  cierto. Si NO encuentras la razón en el contexto, di que no la ves y sugiere revisar el perfil.
- **Si lo que le falla es la app, no el entrenamiento, dilo y mándalo al botón de Feedback.** Si detecta un
  error, algo que no cuadra, algo que la app no le deja hacer o no encuentra cómo hacer, no lo maquilles ni
  intentes justificarlo como una decisión de entrenamiento. Reconócelo, dile que esto es una versión **beta**
  y que en el botón **Ayuda** (abajo a la derecha) puede pedir ayuda a una persona o escribir su opinión
  ahí mismo — lo leemos todo, y tiene también WhatsApp a un toque si prefiere hablarlo. Es lo que hace que mejore para quien la usa y para el resto. Una
  frase, sin disculpas largas ni ceremonia.

## Cómo se escribe aquí
Esto es una conversación por chat, no un informe. Escribe como le hablarías por mensaje.

- **Nada de encabezados** (`#`, `##`) ni de líneas separadoras (`---`). En un chat no significan nada.
- **Negrita sólo para lo que de verdad destaca**: un número, un ritmo, una fecha, una advertencia. Si
  destacas media respuesta, no has destacado nada.
- **Viñetas sólo cuando enumeras cosas de verdad** (los días de la semana, tres opciones). Una respuesta de
  dos frases no lleva viñetas.
- Frases cortas y párrafos cortos. Responde primero, explica después.
- Si la respuesta cabe en dos líneas, que ocupe dos líneas. La longitud no es calidad.
"""


# ---------------------------------------------------------------------------
# Los entrenadores
# ---------------------------------------------------------------------------
#
# El atleta elige entrenador en Configuración y esa elección cambia el ESTILO
# de las respuestas del chat. Es el ÚNICO sitio del producto donde influye: ni
# el planificador de temporada, ni el semanal, ni el asistente de salud reciben
# nada de esto. Lo que un atleta quiere que le expliquen con más o menos
# detalle no tiene por qué cambiarle el entrenamiento.
#
# El catálogo vive AQUÍ y no en el navegador a propósito: por el puente sólo
# viaja un identificador corto, así que nadie puede escribir texto dentro del
# system prompt desde el cliente. Un id que no esté en esta tabla cae al estilo
# por defecto, que es el de siempre.
#
# El espejo visual (retratos, etiquetas) está en
# Frontend/src/utils/appearance.js con los mismos ids.
COACHES: dict[str, dict[str, str]] = {
    "juan":     {"nombre": "Juan",     "estilo": "directo"},
    "fernanda": {"nombre": "Fernanda", "estilo": "directo"},
    "ben":      {"nombre": "Ben",      "estilo": "tecnico"},
    "natasha":  {"nombre": "Natasha",  "estilo": "empatico"},
}

ESTILO_POR_DEFECTO = "directo"

# El nombre se añade SIEMPRE, también en el estilo por defecto: el saludo del
# chat ya se presenta como "Soy Juan", así que un entrenador que no supiera su
# propio nombre al preguntárselo se contradiría a sí mismo en el primer turno.
_NOMBRE = """

## Cómo te llamas
Te llamas {nombre}. Si te pregunta tu nombre, es ese."""

# Lo que cada estilo AÑADE al prompt de arriba.
#
# `directo` está vacío porque el prompt base YA es ese entrenador: "directo,
# concreto y con sus propios datos en la mano". Añadirle un bloque que repita
# lo mismo con otras palabras sólo daría dos versiones de una misma
# instrucción para que el modelo elija.
_ESTILOS: dict[str, str] = {
    "directo": "",
    "tecnico": """
## Tu registro: técnico
Este atleta quiere los datos y entiende de fisiología. Háblale como un entrenador profesional le habla a
otro que sabe: vocabulario propio del triatlón, del trail y del gimnasio, sin traducirlo ni pedir perdón
por usarlo.

- **Di para qué sirve cada sesión, no sólo qué es.** Qué adaptación busca, en qué sistema, y por qué en
  este punto de la temporada. "Rodaje de 60' en Z2" es la sesión; "sostener el estímulo aeróbico sin
  añadir carga que compita con la serie del jueves" es el motivo.
- **Habla de tendencias, no de fotos sueltas.** Cómo viene la carga, cómo responde su frecuencia
  cardiaca, qué dicen sus últimas semanas comparadas con las anteriores.
- **Los números primero.** Sus vatios, sus ritmos, sus zonas, su ACWR, su volumen real. Un tecnicismo sin
  un número detrás es sólo jerga.

**Esto no cambia lo que es cierto, sólo cómo lo cuentas.** Sigues sin inventar un dato que no esté en el
contexto, y la regla de responder POR QUÉ el plan es como es con la restricción real — su horario, su
disponibilidad, una molestia declarada — manda por encima de este registro. Explicar la fisiología va
ENCIMA de la razón verdadera, nunca en su lugar: una explicación fisiológica preciosa sobre una decisión
que en realidad tomó su calendario es una respuesta falsa por muy técnica que suene.
""",
    "empatico": """
## Tu registro: cercano
Este atleta no vive en la jerga del triatlón y no tiene por qué. Explícale las cosas de forma sencilla,
sin tecnicismos innecesarios, y cuando uno haga falta, dilo y explícalo en la misma frase.

- **Sencillo no es simplón.** Baja el vocabulario, nunca el contenido: sigue diciéndole lo mismo que le
  dirías a cualquier otro, dicho de otra manera. Y no le hables como si no entendiera — no repitas lo
  obvio, no celebres cada cosa, no le expliques dos veces lo mismo. Trátale como a un adulto que
  simplemente no ha estudiado fisiología.
- **Acompaña.** Si viene una semana dura, dilo y dile cómo se lleva. Si ha fallado sesiones, que no se
  convierta en un reproche: interesa por qué pasó y qué se puede ajustar.
- **La constancia por encima de la perfección.** Una semana cumplida al 70% durante seis meses construye
  más forma que tres semanas perfectas y un abandono. Cuando se agobie por lo que no hizo, devuélvele la
  vista a lo que sí sostiene.

**Ser cercano no es suavizar lo que dicen sus datos.** Si un número viene mal, si la carga está en zona
de riesgo o si hay una señal que merece una consulta presencial, se dice igual de claro — con cuidado en
la forma, nunca escondiéndolo. Un aviso que el atleta no entiende que era un aviso no le ha servido.
""",
}


def persona_prompt(coach_id: str | None) -> str:
    """Lo que el entrenador elegido añade al system prompt.

    Devuelve su nombre (para que sepa cómo se llama cuando el atleta se lo
    pregunte) más el bloque de estilo, si lo tiene. Un `coach_id` vacío o
    desconocido devuelve "": el chat se comporta exactamente como antes de que
    existieran los entrenadores, que es lo que debe pasar con un atleta que
    todavía no ha elegido ninguno.
    """
    coach = COACHES.get((coach_id or "").strip().lower())
    if not coach:
        return ""
    estilo = _ESTILOS.get(coach["estilo"], "")
    # El "\n" extra deja una línea en blanco antes del `##` del estilo. Sin
    # ella el encabezado queda pegado a la frase anterior y deja de leerse
    # como un encabezado.
    return _NOMBRE.format(nombre=coach["nombre"]) + (("\n" + estilo) if estilo else "")


def _truncate(text: str, budget: int, label: str) -> str:
    """Cut to budget, and SAY it was cut.

    Silent truncation is how a model ends up confidently completing a sentence
    that was never there.
    """
    text = (text or "").strip()
    if len(text) <= budget:
        return text
    return text[:budget].rstrip() + f"\n\n[...{label} recortado; hay más contenido que no cabe aquí]"


def _profile_essentials(profile: dict) -> str:
    """Who the athlete is, in the few fields a coach actually refers to."""
    if not profile:
        return ""
    
    from services.ai.athlete_basics import normalizar_sexo, calcular_edad
    
    data = {
        "nombre": profile.get("name"),
        "edad": calcular_edad(profile.get("birth_date")),
        "genero": normalizar_sexo(profile.get("gender")),
        "peso_kg": profile.get("weight_kg"),
        "estatura_cm": profile.get("height_cm"),
        "deporte_objetivo": profile.get("sports_preference"),
        "nivel": profile.get("training_age_level"),
        "anos_entrenando": profile.get("years_training"),
        "dias_disponibles": profile.get("available_days"),
        "competencias": profile.get("competitions"),
        "acceso_piscina": profile.get("pool_access"),
        "terreno": profile.get("terrain"),
        "marcas_recientes": profile.get("recent_prs"),
    }
    data = {k: v for k, v in data.items() if v not in (None, "", [], {})}
    return json.dumps(data, indent=2, ensure_ascii=False) if data else ""


def _availability_block(profile: dict) -> str:
    """La tabla de disponibilidad del atleta, la misma que ve el planificador.

    Se importa aquí dentro y no arriba porque `web_app.server` importa este
    módulo: hacerlo al nivel del archivo cerraría el círculo. Si por lo que sea
    no se puede leer, se devuelve vacío en vez de romper el chat -- pero
    entonces el coach NO debe inventarse la restricción, que es justo lo que
    hace la regla del prompt sobre no rellenar huecos.
    """
    if not profile:
        return ""
    try:
        from web_app.server import _multi_block_note, render_availability_table
    except Exception:  # pragma: no cover - sólo si el servidor no está cargable
        logger.warning("No se pudo leer la tabla de disponibilidad para el coach", exc_info=True)
        return ""
    return f"{render_availability_table(profile)}\n{_multi_block_note(profile)}".strip()


def _read_json(path: Path) -> Any:
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return None


def _lean(body: str) -> str:
    """Quita la sangría y los nulos de un artefacto JSON antes de recortarlo.

    Los artefactos del archivo se guardan indentados, para que una persona
    pueda leerlos. Mandárselos así al coach hacía que el recorte se comiera
    entre el 77% y el 80% de cada informe experto -- y lo que se comía era el
    FINAL, donde suelen estar las implicaciones.

    Es la misma poda que `services/garmin/payload_pruning.py` aplica a los
    summarizers desde v6, en el único sitio del pipeline donde nadie la había
    conectado. Un texto que no es JSON se devuelve intacto.
    """
    import json as _json

    from services.garmin.payload_pruning import prune

    try:
        datos = _json.loads(body)
    except (TypeError, ValueError):
        return body
    return _json.dumps(prune(datos), separators=(",", ":"), ensure_ascii=False)


# Importado, no redefinido: tres copias del mismo orden son tres
# oportunidades de que una se quede atras. Ver SLOT_ORDER en el esquema.
from services.ai.langgraph.schemas.plan_outputs import SLOT_ORDER as _SLOT_ORDER


def _step_line(step: dict[str, Any]) -> str:
    """Un paso del entrenamiento, legible y sin perder un número.

    El objetivo va SIEMPRE si existe. Es literalmente lo que el atleta
    pregunta ("¿a qué ritmo van las series?"), y era lo primero que el
    recorte se llevaba por delante.
    """
    partes = []
    duracion, tipo = step.get("duration_value"), step.get("duration_type")
    if duracion:
        if tipo == "time":
            minutos = duracion / 60
            partes.append(f"{minutos:.0f}min" if minutos >= 1 else f"{duracion:.0f}s")
        elif tipo == "distance":
            partes.append(f"{duracion:.0f}m")
        else:
            partes.append(f"{duracion:.0f} {tipo}")

    objetivo = step.get("target_type")
    if objetivo == "pace" and step.get("target_low"):
        partes.append(f"a {_pace(step['target_low'])}-{_pace(step.get('target_high'))}/km")
    elif objetivo == "hr_zone" and step.get("target_zone"):
        partes.append(f"en Z{step['target_zone']}")
    elif objetivo == "power" and step.get("target_low"):
        partes.append(f"a {step['target_low']:.0f}-{step.get('target_high', 0):.0f} W")

    cabeza = " ".join(partes)
    descripcion = (step.get("description") or "").strip()
    linea = f"{cabeza} — {descripcion}" if cabeza and descripcion else (cabeza or descripcion)

    if step.get("substeps"):
        hijos = "; ".join(_step_line(s) for s in step["substeps"])
        return f"{step.get('repeat_count', 1)}x [{hijos}]" + (f" — {descripcion}" if descripcion else "")
    return linea


def _pace(seconds_per_km: float | None) -> str:
    if not seconds_per_km:
        return "?"
    return f"{int(seconds_per_km) // 60}:{int(seconds_per_km) % 60:02d}"


def render_week_for_coach(week: dict[str, Any]) -> str:
    """La semana entera, compacta y completa. NUNCA recortada.

    Es lo único del contexto que no admite recorte: es la pregunta que el
    atleta viene a hacer. Y estaba recortada -- `json.dumps(indent=2)` de una
    semana real ocupa 17.581 caracteres contra un presupuesto de 9.000, así
    que el coach veía seis días de nueve. Se le preguntó por el VO2max del
    miércoles (el último día) y contestó que ese día era natación: no lo tenía
    delante, y en vez de decirlo lo rellenó con lo que sí veía.

    Serializar en JSON indentado era el problema entero. La mitad de esos
    caracteres eran llaves, comillas, `null` y sangría -- la misma enfermedad
    que `services/garmin/payload_pruning.py` documenta para los summarizers,
    en el único sitio donde nadie la había mirado. En prosa compacta la misma
    semana cabe cuatro veces, con todos los números dentro.
    """
    lineas = []
    dias = week.get("days") or []
    if dias:
        lineas.append(f"Del {dias[0].get('date')} al {dias[-1].get('date')}.")
    if week.get("week_rationale"):
        lineas.append(f"\n**Por qué esta semana es así:** {week['week_rationale']}")
    if week.get("load_warning"):
        # El aviso de carga que el atleta ve en su semana: el coach tiene que poder explicarlo.
        lineas.append(f"\n**Aviso de carga que ve el atleta:** {week['load_warning']}")

    for dia in dias:
        # El ORDEN de la sesión dentro del día, no una hora: 'am'/'pm' siempre
        # fueron claves de posición y decirlas como horas era falso para un
        # atleta que corre y nada la misma mañana.
        franja = dia.get("session_slot") or "unica"
        orden = _SLOT_ORDER.index(franja) if franja in _SLOT_ORDER else 0
        bloque_n = dia.get("block_index")
        marca = f" [{orden}ª sesión del día"  + (f", franja {bloque_n}]" if bloque_n is not None else "]") if orden else ""
        if dia.get("is_rest"):
            lineas.append(f"\n**{dia.get('day_name_es')} {dia.get('date')}{marca} — DESCANSO**")
            if dia.get("purpose"):
                lineas.append(f"  {dia['purpose']}")
            continue

        minutos = dia.get("total_duration_minutes")
        cabecera = (
            f"\n**{dia.get('day_name_es')} {dia.get('date')}{marca} — "
            f"{dia.get('focus')} ({dia.get('discipline') or dia.get('sport')}"
            + (f", {minutos:.0f} min" if minutos else "") + ")**"
        )
        lineas.append(cabecera)
        if dia.get("intensity_class"):
            lineas.append(f"  Tipo: {dia['intensity_class']}")
        for orden, paso in enumerate(dia.get("steps") or [], start=1):
            lineas.append(f"  {orden}. {_step_line(paso)}")
        # La sesión de fuerza desglosada. Vive fuera de `steps` porque no viaja
        # a Garmin (ver StrengthBlock en plan_outputs.py), y por eso mismo se
        # le escapaba al coach: un jueves de fuerza le llegaba como un único
        # paso de "45 min", así que a "¿qué ejercicios hago el jueves?" sólo
        # podía contestar inventándoselos o admitiendo que no lo sabía. Es
        # exactamente el fallo del miércoles de VO2max, en otro campo.
        bloque = dia.get("strength_block") or {}
        if bloque.get("exercises"):
            if bloque.get("warmup"):
                lineas.append(f"  Calentamiento: {bloque['warmup']}")

            def _ejercicio(ej: dict) -> str:
                detalle = [f"{ej.get('sets')}x{ej.get('reps')}"]
                if ej.get("load"):
                    detalle.append(str(ej["load"]))
                if ej.get("rest_seconds"):
                    detalle.append(f"descanso {ej['rest_seconds']}s")
                linea = f"{ej.get('name')} — {', '.join(detalle)}"
                return linea + (f" · {ej['cues']}" if ej.get("cues") else "")

            # Beta 2.1: con superseries, el coach tiene que saber qué va SEGUIDO
            # y cuánto se descansa entre rondas, o contestará como si fueran
            # series sueltas.
            grupos = bloque.get("groups") or []
            if grupos:
                for gi, grupo in enumerate(grupos):
                    letra = grupo.get("label") or chr(65 + gi)
                    ejercicios = grupo.get("exercises") or []
                    cabeza = f"  Grupo {letra}" + (" (seguidos, en superserie)" if len(ejercicios) > 1 else "")
                    if grupo.get("rest_between_rounds_s"):
                        cabeza += f", descanso {grupo['rest_between_rounds_s']}s entre rondas"
                    lineas.append(cabeza + ":")
                    for n, ej in enumerate(ejercicios, start=1):
                        lineas.append(f"    {letra}{n}) {_ejercicio(ej)}")
            else:
                for n, ej in enumerate(bloque["exercises"], start=1):
                    lineas.append(f"  {n}) {_ejercicio(ej)}")
            if bloque.get("cooldown"):
                lineas.append(f"  Vuelta a la calma: {bloque['cooldown']}")
            if bloque.get("notes"):
                lineas.append(f"  Notas de la sesión: {bloque['notes']}")
            # Desde el 2026-09-23 el porqué de la fuerza va en campos, no en `notes`.
            # Sin esto el coach no sabría por qué se bajó una serie o se quitó un
            # ejercicio, y contestaría inventándolo.
            if bloque.get("session_name"):
                lineas.append(f"  Sesión de su programa: {bloque['session_name']}")
            if bloque.get("dose"):
                lineas.append(f"  Dosis {bloque['dose']} esta semana"
                              + (f": {bloque['dose_reason']}" if bloque.get("dose_reason") else ""))
            for ej in bloque.get("exercises") or []:
                if ej.get("change"):
                    lineas.append(f"  Cambio en {ej.get('name')}: {ej['change']}"
                                  + (f" — {ej['change_reason']}" if ej.get("change_reason") else ""))
            for quitado in bloque.get("skipped") or []:
                lineas.append(f"  Sin {quitado.get('name')} esta semana"
                              + (f": {quitado.get('reason')}" if quitado.get("reason") else ""))
            if bloque.get("placement_note"):
                lineas.append(f"  Aviso de colocación: {bloque['placement_note']}")
        if dia.get("purpose"):
            lineas.append(f"  Para qué: {dia['purpose']}")
        if dia.get("adaptation"):
            lineas.append(f"  Si algo va mal: {dia['adaptation']}")
        if dia.get("manual_instructions"):
            # La sesión completa de lo que Garmin no puede llevar. Si esto se
            # recorta, el atleta pregunta por ella y el coach no la tiene.
            lineas.append(f"  Instrucciones escritas: {dia['manual_instructions']}")
        if dia.get("garmin_exportable") is False:
            lineas.append("  (No va al reloj: se hace leyendo las instrucciones.)")

    return "\n".join(lineas).strip()


def _section(title: str, body: str, fence: bool = False) -> str:
    """One context section.

    `fence` wraps the body in a code fence for embedded documents that carry
    their own markdown headings. Without it the season plan's "## Fases" and
    the health record's "## Antecedentes" sit at the same level as our own
    section headers, and where one section ends and the next begins stops
    being legible to the model reading it.
    """
    body = (body or "").strip()
    if not body:
        return ""

    # Si el bloque ya trae SU propio encabezado, ese manda y el nuestro sobra.
    #
    # Cuatro renderizadores escriben su título dentro del texto, porque en el
    # prompt de los planificadores son la única cabecera que hay:
    # `render_health_block`, `render_notebook_block`, `render_checkin_block` y
    # `render_constraints_block`. Aquí se les ponía otra encima, así que el
    # contexto del coach salía con parejas como:
    #
    #     ## Antecedentes de salud declarados
    #     ```
    #     ## Antecedentes de salud declarados por el atleta
    #
    # Dos nombres para una misma cosa, en el prompt de un modelo al que además
    # se le advierte de que un encabezado puede no llamarse como el atleta lo
    # llama. Se queda el DEL BLOQUE: es su nombre canónico, el mismo que ven
    # los planificadores, y es al que apunta el inventario del system prompt.
    propio = ""
    if body.startswith("## "):
        primera, _, resto = body.partition("\n")
        propio, body = primera[3:].strip(), resto.strip()
        if not body:
            # Un bloque que era sólo su encabezado no dice nada.
            return ""

    if fence:
        body = f"```\n{body}\n```"
    return f"## {propio or title}\n{body}\n"


# Qué fichero del perfil demuestra que el plan de una traza sigue existiendo.
# Son los mismos que borra `delete_current_generation` en cada alcance, y el
# espejo del puente (AiService/bridge/storage_adapter.py) los borra también
# cuando MySQL deja de tenerlos, así que preguntar por ellos es preguntar por
# el plan de verdad.
_PLAN_DE_LA_TRAZA = {"week": "weekly_plan.json", "season": "season_plan.md"}


def _ultima_traza_todavia_vigente(storage_dir: Path):
    """La traza de razonamiento más reciente cuyo plan sigue en pie.

    ## El problema

    Borrar el plan NO borra su traza, y es deliberado: la traza explica por qué
    se escribió, guarda la serie de forma física del atleta que alimenta las
    gráficas, y borrarla con el plan destruiría su historial. Está fijado por
    `ReasoningIsItsOwnAction` en tests/test_deletion_scope.py.

    Pero esto leía la ÚLTIMA traza sin preguntar si su plan seguía ahí. Así
    que un atleta que borraba su semana y no generaba otra abría el chat y su
    entrenador le seguía explicando -- con detalle y con seguridad -- la semana
    que acababa de borrar: `week_intents.json` con las decisiones día a día,
    `synthesis.md` con el análisis que las justificaba. Ninguna de las dos
    cosas describe ya nada.

    ## Por qué basta con mirar el fichero del plan

    La traza dice de qué generación es (`kind`), y cada tipo tiene su fichero
    en el perfil. Si ese fichero no está, ese plan se borró, y lo que la traza
    razona ya no tiene sujeto. No hace falta comparar fechas ni contenidos.

    Se sigue bajando por la lista en vez de rendirse con la primera: borrada la
    semana, la traza de la temporada -- que sí sigue vigente -- todavía explica
    el macrociclo sobre el que se apoya el atleta.
    """
    for entrada in list_reasoning_entries(storage_dir):
        fichero = _PLAN_DE_LA_TRAZA.get(entrada.get("kind"))
        # Un `kind` que no conocemos no se descarta: no saber si sigue vigente
        # no es lo mismo que saber que no lo está, y callar de más deja al
        # entrenador sin contexto que sí tenía derecho a leer.
        if fichero is None or (Path(storage_dir) / fichero).exists():
            return entrada
    return None


def render_fuerza_para_el_coach(profile: dict | None, registro: list | None = None) -> str:
    """Su programa de fuerza confirmado y lo último que levantó, compacto. "" sin programa ni registro.

    Auditoría del 2026-09-18: el chat inventó por qué «Upper B» cargaba piernas y le
    dijo al atleta que su programa estaba "en el cuaderno". No lo tenía: sólo veía la
    semana. Una línea por sesión y las últimas sesiones registradas, porque este
    bloque se paga en cada mensaje.
    """
    from services.ai.langgraph.nodes import paso_de_fuerza as pf
    from services.ai.perfil_de_fuerza import leer_perfil_de_fuerza, leer_rutina_de_fuerza

    rutina = leer_rutina_de_fuerza(profile or {})
    lineas: list[str] = []
    if rutina is not None and rutina.confirmada and pf.sesiones(rutina):
        origen = "se lo escribió su coach y lo confirmó" if rutina.origen == "coach" else "la suya, confirmada"
        lineas.append(f"Programa de gimnasio ({origen}):")
        for s in pf.sesiones(rutina):
            grupos = []
            for i, g in enumerate(s.get("groups") or []):
                ejercicios = " + ".join(f"{e['name']} {e.get('sets') or '?'}x{e.get('reps') or '?'}"
                                        for e in g.get("exercises") or [])
                grupos.append(f"{pf._letra(i)}: {ejercicios}")
            minutos = f" (~{s['minutes']}')" if s.get("minutes") else ""
            lineas.append(f"- {s.get('name') or s['id']}{minutos}: " + "; ".join(grupos))
        perfil = pf.perfil_para_el_prompt(leer_perfil_de_fuerza(profile or {}))
        if perfil and perfil != "(no lo dijo)":
            lineas.append(f"Cómo la entrena: {perfil}.")
    registrado = pf.lo_que_levanto(registro, rutina, sesiones_max=4)
    if registrado:
        lineas.append(registrado.strip())
    return "\n".join(lineas)


def build_coach_context(
    storage_dir: Path,
    profile: dict | None = None,
    max_runs: int = 1,
    today: date | None = None,
    registro_de_fuerza: list | None = None,
) -> dict[str, Any]:
    """Assemble what the coach knows, within a predictable budget.

    Returns the context text plus `stats`, so the size is visible rather than
    something to discover on a bill.
    """
    storage_dir = Path(storage_dir)
    profile = profile or {}
    today = today or date.today()
    max_runs = max(0, min(int(max_runs or 0), MAX_RUNS))

    parts: list[str] = [f"## Hoy\n{today.isoformat()}\n"]

    parts.append(_section("Quién es el atleta", _profile_essentials(profile)))
    # La disponibilidad DECLARADA, con horarios. Es la restriccion que mas
    # decide la forma de la semana y el coach no la tenia: cuando el atleta
    # pregunto por que su miercoles traia una sola sesion si el suele correr y
    # nadar esa manana, el coach contesto con tres razones de entrenamiento
    # -- proteger el sueno, repartir la carga, la molestia de ingle -- y
    # ninguna era la razon. La razon era que su perfil declara UN bloque ese
    # dia. Un coach que no puede ver una restriccion la explica con lo unico
    # que le queda, que es su propia verosimilitud.
    parts.append(_section(
        "Disponibilidad declarada (de su perfil, es lo que limita la forma de la semana)",
        _availability_block(profile), fence=True,
    ))
    # Lo que el atleta declaró sobre su cuerpo: lesiones activas y resueltas,
    # condiciones crónicas, medicación, cirugías pasadas y nutrición.
    #
    # Ya NO lleva el informe del asistente de salud, ni el tamizaje de aptitud
    # física, ni la casilla "me considero propenso" (v1.2). Eran cuatro de los
    # seis canales por los que se le preguntaba al atleta por lesiones, y ese
    # exceso era la causa del sesgo que la beta reportó. Ver health_context.
    parts.append(_section(
        "Antecedentes de salud declarados",
        render_health_block(profile),
        fence=True,
    ))
    # v7 Track 7: lo que el coach ha ido aprendiendo de hablar con él. Va
    # arriba, junto a quién es, porque no es un dato derivado de Garmin -- es
    # lo que el atleta contó y nadie más sabe. Sin esto, cada conversación
    # empieza de cero y el atleta tiene que repetirse.
    parts.append(_section("Cuaderno del entrenador", render_notebook_block(storage_dir, today), fence=True))

    # Lo que el atleta HACE de verdad: sesiones y horas por deporte, en qué
    # días entrena, y su kilometraje -- semanal y de los últimos 30 días.
    #
    # Los dos planificadores llevaban este bloque desde el principio; el
    # entrenador del chat no. Se vio preguntándole cuántos kilómetros había
    # corrido el mes pasado: no lo sabía, y no era cosa del prompt, es que el
    # dato no le llegaba. Es la misma clase de fallo que ya apareció con el
    # plan, el informe y la ficha de números -- un dato que existe, que otro
    # consumidor sí recibe, y que a este nadie le pasaba.
    parts.append(_section(
        "Lo que hace de verdad (hábitos, volumen y kilometraje reales)",
        cargar_habitos(storage_dir, today) or "", fence=True,
    ))
    parts.append(_section(
        "Su fuerza: el programa que confirmó y lo que levantó",
        render_fuerza_para_el_coach(profile, registro_de_fuerza), fence=True,
    ))

    summary = _read_json(storage_dir / "summary.json") or {}
    prescription = summary.get("prescription") or {}
    parts.append(_section("Sus números reales (zonas, ritmos)", prescription.get("card") or "", fence=True))
    # Sus tests de umbral, del perfil de HOY (José, 2026-10-21). La ficha de arriba se escribe al generar la
    # semana: hizo su test de bici el martes (FC umbral 156), preguntó el miércoles «¿cuál es mi umbral de
    # bici?» y el chat contestó 176 ppm -- el umbral de carrera de una ficha anterior al test.
    from services.ai.test_de_umbral import describir_tests
    tests_de_hoy = describir_tests(profile.get("threshold_tests"), today)
    if tests_de_hoy:
        parts.append(_section(
            "Sus tests de umbral (del perfil, al día; si son más nuevos que la ficha de arriba, mandan ellos)",
            "\n".join(tests_de_hoy) + "\nLa FC umbral de bici sólo sale de un test en bici: la de carrera no vale "
            "en bici. Sus zonas de pulso son las que su reloj aplica; si un test cambia su umbral, sus zonas en "
            "Garmin no cambian solas.",
            fence=True,
        ))
    # v1.2.2: la intensidad VERDADERA y cómo respondió a cada fase. El atleta 2
    # preguntó por qué su Garmin le dice que le falta intensidad si hace VO2max:
    # sin estas dos secciones el coach sólo podía contestar con el Load Focus,
    # que es justo lo que está mal leído en un atleta cuyo pulso llega tarde.
    from services.ai.intensity_distribution import render_distribucion
    from services.ai.respuesta_a_las_fases import render_respuesta_a_las_fases

    parts.append(_section(
        "Cómo reparte su intensidad (medido por sesión, con sus umbrales y su ritmo)",
        render_distribucion((summary.get("volume_baseline") or {}).get("intensity_distribution")),
        fence=True,
    ))
    parts.append(_section("Cómo respondió a cada fase", render_respuesta_a_las_fases(storage_dir), fence=True))

    season_plan = (storage_dir / "season_plan.md")
    if season_plan.is_file():
        parts.append(_section(
            "Plan de temporada",
            _truncate(season_plan.read_text(encoding="utf-8"), 5000, "el plan de temporada"),
            fence=True,
        ))

    phases = _read_json(storage_dir / "season_phases.json") or {}
    if phases.get("phases"):
        parts.append(_section("Fases de la temporada", json.dumps(phases["phases"], indent=2, ensure_ascii=False)))
    parts.append(_section(
        "Restricciones vigentes hoy",
        render_constraints_block(phases.get("constraints") or [], today),
        fence=True,
    ))

    weekly_plan = _read_json(storage_dir / "weekly_plan.json") or {}
    weeks = weekly_plan.get("weeks") or []
    if weeks:
        # Only the CURRENT week in full. Older weeks are what the archive is
        # for, and re-sending every past week on every message is exactly the
        # unbounded growth this budget exists to prevent.
        # SIN recortar, a propósito. Ver render_week_for_coach: es la única
        # parte del contexto que no admite recorte, porque es la pregunta que
        # el atleta viene a hacer.
        parts.append(_section(
            "Semana actual (la que el atleta tiene ahora) — COMPLETA",
            render_week_for_coach(weeks[-1]),
            fence=True,
        ))
        anteriores = weeks[:-1]
        if anteriores:
            # Una linea por semana pasada parece nada, y lo es hasta que se
            # multiplica por el tiempo: son ~250 caracteres cada una, el plan
            # las acumula todas (_append_week) y el atleta paga este bloque
            # entero en CADA mensaje. A los seis meses son 26 lineas; al ano,
            # 52. El tope las deja en las ultimas MAX_PAST_WEEKS_LISTED, que es
            # el horizonte del que un entrenador habla de verdad cuando mira
            # atras.
            omitidas = max(0, len(anteriores) - MAX_PAST_WEEKS_LISTED)
            lineas = [
                f"- Semana {w.get('week_number')}: "
                f"{sum(1 for d in (w.get('days') or []) if not d.get('is_rest'))} sesiones"
                + (f" — {w.get('week_rationale', '')[:200]}" if w.get("week_rationale") else "")
                for w in anteriores[-MAX_PAST_WEEKS_LISTED:]
            ]
            if omitidas:
                # Nunca recortar en silencio: si el atleta pregunta por una
                # semana de hace cinco meses, el entrenador tiene que poder
                # decir que no la tiene delante en vez de inventarsela.
                lineas.insert(0, f"_(Hay {omitidas} semana(s) anterior(es) que no caben aqui; "
                                 "se muestran las mas recientes.)_")
            parts.append(_section("Semanas anteriores (sólo el resumen)", "\n".join(lineas)))

    adherence = summary.get("adherence") or {}
    if adherence:
        parts.append(_section("Adherencia al plan previo", json.dumps({
            k: v for k, v in adherence.items() if k not in ("sessions", "prompt_block")
        }, indent=2, ensure_ascii=False)))

    history = load_checkins(storage_dir)
    parts.append(_section(
        "Último check-in del atleta",
        render_checkin_block(latest_checkin(storage_dir), history),
        fence=True,
    ))

    # --- the bounded tier -------------------------------------------------
    runs_used = 0
    excluded: list[str] = []
    entry = _ultima_traza_todavia_vigente(storage_dir) if max_runs else None
    if entry:
        contents = load_reasoning_entry(storage_dir, entry["id"]) or {}
        run_parts: list[str] = []
        for name, budget in _ARTEFACT_BUDGETS.items():
            body = contents.get(name)
            if body:
                run_parts.append(f"### {name}\n{_truncate(_lean(body), budget, name)}")
        excluded = [name for name in _EXCLUDED_ARTEFACTS if name in contents]
        if run_parts:
            runs_used = 1
            parts.append(_section(
                f"Razonamiento de la última generación ({entry['id']}, tipo: {entry.get('kind', '?')})",
                "\n\n".join(run_parts),
                fence=True,
            ))

    context = "\n".join(part for part in parts if part).strip()
    return {
        "context": context,
        "stats": {
            "chars": len(context),
            "approx_tokens": len(context) // 4,
            "runs_included": runs_used,
            "max_runs": max_runs,
            "artefacts_excluded": excluded,
        },
    }


async def ask_coach(
    storage_dir: Path,
    profile: dict | None,
    message: str,
    chat_history: list[dict[str, str]] | None = None,
    max_runs: int = 1,
    coach_id: str | None = None,
    registro_de_fuerza: list | None = None,
) -> dict[str, Any]:
    """One turn of the conversation. Read-only: nothing here writes a plan.

    Only the last few turns of history are re-sent, for the same reason the
    run archive is capped: history grows without bound and every message pays
    for all of it.
    """
    built = build_coach_context(storage_dir, profile, max_runs=max_runs, registro_de_fuerza=registro_de_fuerza)

    # El entrenador elegido va PEGADO al prompt base y ANTES del contexto: es
    # una instrucción sobre cómo hablar, no un dato del atleta, y meterla entre
    # sus datos la dejaría al mismo nivel que algo discutible. Cadena vacía
    # mientras no haya elegido ninguno.
    system_text = (
        SYSTEM_PROMPT
        + persona_prompt(coach_id)
        + "\n\n# Contexto del atleta\n\n"
        + built["context"]
    )

    # ── La cache ────────────────────────────────────────────────────────────
    #
    # Medido sobre el libro real, no estimado. Cuatro mensajes seguidos de una
    # misma conversacion:
    #
    #     28.459 in / 120 out  -> $0,05812   (98% del coste es la ENTRADA)
    #     29.333 in / 1.108 out-> $0,06975
    #     30.176 in / 1.530 out-> $0,07565
    #     31.030 in / 1.563 out-> $0,07769
    #
    # Los mismos ~28.000 tokens de contexto se reenviaban enteros y a precio
    # completo en cada mensaje. El modelo no era el problema: pagar cuatro
    # veces por el mismo texto lo era.
    #
    # Este mensaje de sistema es el prefijo estable que la cache necesita, y
    # ya lo era antes de tocarlo: el prompt, el entrenador elegido y el
    # contexto del atleta no cambian dentro de una conversacion; lo unico que
    # crece son los turnos, que van DESPUES. Por eso no hubo que reordenar
    # nada.
    #
    # Lo que cuesta: escribir la cache vale un 25% mas, leerla la decima
    # parte. O sea que el PRIMER mensaje sale algo mas caro y a partir del
    # segundo baja ~85%. Con una sola pregunta suelta se pierde un poco; con
    # el atleta curioso que manda diez seguidas -- el caso que preocupaba --
    # se gana mucho. La cache de Anthropic dura 5 minutos y se renueva sola
    # con cada lectura, asi que una conversacion seguida la mantiene viva.
    #
    # `cache_control` es un parametro PROPIO de Anthropic: si el rol corriera
    # en Gemini (tier Lite) o cayera al camino de OpenRouter, se manda el
    # texto plano de siempre. Ver ModelSelector.habla_anthropic_directo.
    modelo_del_chat = ai_settings.get_model_for_role(AgentRole.COACH_CHAT)
    system_content: Any = system_text
    if ModelSelector.habla_anthropic_directo(modelo_del_chat):
        system_content = [{
            "type": "text",
            "text": system_text,
            "cache_control": {"type": "ephemeral"},
        }]

    messages: list[dict[str, Any]] = [{"role": "system", "content": system_content}]
    # 16 turnos = ocho intercambios. Eran 8, o sea CUATRO, y con eso una
    # conversación normal pierde el hilo de lo que el propio atleta acaba de
    # decir -- que es la peor forma de fallar para algo que se llama
    # entrenador. El coste de subirlo es pequeño y acotado: cada turno son
    # unos 300 tokens contra los ~8.600 del contexto de datos, así que el
    # techo sube de ~11k a ~13k y no depende de cuánto hable el atleta.
    #
    # El recorte es de la CONVERSACIÓN, nunca de los DATOS. La semana entera,
    # las zonas, el plan de temporada, la adherencia y el cuaderno van
    # completos: si el coach no puede responder algo que está en los datos del
    # atleta, el problema no se arregla recortando menos aquí.
    for turn in (chat_history or [])[-CHAT_TURNS_SENT:]:
        role = turn.get("role")
        content = (turn.get("content") or "").strip()
        if role in ("user", "assistant") and content:
            messages.append({"role": role, "content": content[:4000]})
    messages.append({"role": "user", "content": (message or "").strip()[:4000]})

    # El entrenador aplicado va en el log a propósito: es la única forma de
    # saber DESDE FUERA si la elección del atleta llegó hasta aquí. Sin esto,
    # un `coach_id` que se pierda por el camino no se distingue de un modelo
    # que decidió responder plano -- y el síntoma de las dos cosas es el mismo.
    elegido = COACHES.get((coach_id or "").strip().lower())
    logger.info(
        "Coach chat: %d chars of context (~%d tokens), %d run(s) included, entrenador=%s",
        built["stats"]["chars"], built["stats"]["approx_tokens"], built["stats"]["runs_included"],
        f"{elegido['nombre']} ({elegido['estilo']})" if elegido else f"por defecto [id={coach_id!r}]",
    )

    # Its own role since v7. This used to borrow SYNTHESIS -- the tier's
    # report-writing model -- so "¿por qué el martes es suave?" was answered
    # by one of the most expensive models in the pipeline. Every other role
    # here runs a bounded number of times per generation; this one runs as
    # often as the athlete feels like typing, which makes price per call the
    # dominant term. See AgentRole.COACH_CHAT.
    llm = ModelSelector.get_llm(AgentRole.COACH_CHAT)
    # Measured, not estimated. Until v7 this was the one paid call in the app
    # that reported nothing at all -- an athlete could hold a long conversation
    # and none of it appeared in any cost figure, which is exactly how a
    # per-user bill goes quietly wrong. `usage_metadata` is what the provider
    # itself billed; the ledger turns it into money.
    with get_usage_metadata_callback() as usage_cb:
        response = await llm.ainvoke(messages)
        usage = dict(usage_cb.usage_metadata or {})

    content = getattr(response, "content", response)
    if isinstance(content, list):  # some providers return content blocks
        content = "".join(block.get("text", "") for block in content if isinstance(block, dict))

    return {
        "reply": str(content).strip(),
        "context_stats": built["stats"],
        "usage_metadata": usage,
    }
