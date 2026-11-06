"""What hurts, in the athlete's own words, attached to the days that load it.

Plan Maestro v6 Track 3.

## The failure this fixes

The athlete's check-in said: *"Un poco de dolor ingle izquierda despues de la
21k"*. The plan came back with a bike session whose `adaptation` read *"Si la
**rodilla** izquierda molesta, reduce la resistencia…"*.

The instruction to write it into `adaptation` already existed. What did not
exist was any requirement that the structure named be **the one the athlete
declared**. So the model produced a plausible-sounding contingency for a
different body part, on the one session -- cycling, which is repeated hip
flexion -- most likely to actually involve the declared one.

An adaptation written for the wrong structure is worse than no adaptation. It
tells the athlete, in writing, that nobody read what they wrote.

## Two sources, one constraint

A complaint reaches the coach two ways and they were being handled separately:

- the **check-in** niggle, answered right before this week (v5 Track 6), and
- the **declared active injuries** on the health record (v5 Track 10).

Both are the athlete saying "this part of me hurts". They are collected here
into one block so a coach cannot address one and forget the other.

## The gesture map is context, not a rule

The block includes a short account of which movement patterns load which
structures, across all four modalities this app plans. It exists so the model
can answer *"which of this week's sessions actually load the thing they
declared?"* -- the question it got wrong -- rather than guessing from the sport
label.

It is explicitly **not**:

- **A diagnosis.** It names movement patterns and structures, never a
  condition. The block tells the model the same thing, because a coach who
  writes "eso suena a tendinitis" has stopped being a coach.
- **A validator.** Nothing here blocks or rewrites a plan. `unaddressed()`
  below reports, after the fact, that a declared complaint never showed up in
  any adaptation -- so the gap is visible in the log instead of only visible to
  the athlete reading their week.
- **Exhaustive.** It says so. A complaint not on the list gets reasoned about
  the same way, not ignored for being absent.
"""

from __future__ import annotations

import re
import unicodedata
from typing import Any

# Which movement patterns load which structures. Written as a coach would
# explain it to an athlete, and deliberately covering swim, bike, run and
# strength so no discipline is left with "figure it out from the sport name".
#
# The unloading half matters as much as the loading half: this project's
# conflict policy says never to resolve a problem with rest when shortening,
# easing or switching modality preserves the habit and the aerobic stimulus.
# A coach who knows a pull buoy takes the legs out of a swim has an option
# other than cancelling.
GESTURE_MAP_ES = """**Qué carga cada gesto** (para decidir QUÉ sesiones tocan la estructura declarada; es orientación
biomecánica, no un diagnóstico ni una lista cerrada):
- **Correr en llano**: impacto repetido en pie, tibia, rodilla y cadera; sóleo, gemelo y tendón de Aquiles
  en el impulso.
- **Correr en bajada**: carga excéntrica fuerte de cuádriceps y rodilla.
- **Correr en subida o cuestas**: sóleo y Aquiles, glúteo, flexores de cadera.
- **Series y cambios de ritmo**: isquiotibiales y aductores en la fase de vuelo; tendones en general.
- **Pedalear**: flexión de cadera repetida — psoas, aductores e **ingle** —, cuádriceps, y la postura carga
  zona lumbar y cervical. Desarrollo grande con cadencia baja carga la rodilla anterior.
- **Nadar crol**: hombro y manguito rotador en la brazada; zona lumbar en la posición extendida; tobillo en
  la patada.
- **Patada de pecho/braza**: rodilla por dentro y aductores — es la patada que más carga la ingle.
- **Fuerza**: depende del ejercicio. Sentadilla, peso muerto y zancada cargan rodilla, cadera y lumbar;
  empujes por encima de la cabeza cargan el hombro.
- **Pliometría y saltos**: impacto y tendones (Aquiles, rotuliano) por encima de casi cualquier otra cosa.
- **Brick (bici→carrera)**: se empieza a correr con el cuádriceps ya fatigado y los flexores de cadera
  acortados; carga la cadera y la ingle más que correr en fresco.

**Qué descarga**: nadar con pull-buoy quita las piernas de la ecuación; correr en agua profunda o pedalear
suave quitan el impacto sin quitar el estímulo aeróbico; llano y superficie blanda descargan rodilla y
tendones frente a bajada y asfalto."""


def _strip_accents(text: str) -> str:
    return "".join(
        c for c in unicodedata.normalize("NFD", text or "") if unicodedata.category(c) != "Mn"
    ).lower()


# Body parts an endurance athlete actually names, accent-free stems so that
# "aductor" matches "aductores" and "tendon" matches "tendones". This is a
# lexicon, not a rule: it is used ONLY by the after-the-fact lint below, to
# tell "the coach wrote about the groin" from "the coach wrote about the
# knee". Nothing here gates or rewrites a plan.
_BODY_PARTS = (
    "pie", "empeine", "planta", "fascia", "metatars", "dedo", "talon", "aquiles", "tobillo",
    "gemelo", "soleo", "pantorrilla", "tibia", "espinilla", "peroneo",
    "rodilla", "rotula", "menisco", "cuadriceps", "isquio", "biceps femoral", "cintilla",
    "iliotibial", "aductor", "abductor", "ingle", "pubis", "psoas", "cadera", "gluteo",
    "flexor", "lumbar", "espalda", "dorsal", "sacro", "cervical", "cuello", "trapecio",
    "hombro", "manguito", "clavicula", "codo", "muneca", "mano", "costilla", "abdomen",
    "oblicuo", "core", "tendon", "ligamento", "cintura",
)

# Words that describe HOW something hurts or WHERE on the body's midline, not
# WHAT hurts. Matching on these is how "ingle izquierda" was scored as
# addressed by a sentence about the "rodilla izquierda".
_GENERIC = {
    "izquierda", "izquierdo", "derecha", "derecho", "bilateral", "ambos", "ambas",
    "dolor", "dolores", "molestia", "molestias", "poco", "algo", "mucho", "leve", "fuerte",
    "ligero", "ligera", "despues", "antes", "tras", "luego", "semana", "semanas", "dia",
    "dias", "zona", "parte", "lado", "siento", "duele", "punzada", "tension", "rigidez",
    "cansancio", "sensacion", "the", "que", "por", "con", "sin", "para", "una", "uno",
    "los", "las", "del", "mis", "muy", "mas", "esta", "este",
}


def _structures(text: str) -> list[str]:
    """Which anatomical structures a declared complaint names.

    Falls back to the complaint's own content words when nothing in the
    lexicon matches -- an athlete may well write something it does not cover,
    and reporting "we could not tell" would be less useful than a best effort.
    """
    normalized = _strip_accents(text)
    found = [part for part in _BODY_PARTS if part in normalized]
    if found:
        return found
    return [
        word for word in re.findall(r"[a-z]+", normalized)
        if len(word) > 3 and word not in _GENERIC
    ]


def collect(checkin: dict | None, profile: dict | None) -> list[dict[str, Any]]:
    """Every complaint the athlete has declared, from both places they can.

    Each item keeps the athlete's own wording in `location` -- paraphrasing it
    here would reintroduce the very drift this module exists to stop.
    """
    found: list[dict[str, Any]] = []

    niggle = str((checkin or {}).get("niggle_location") or "").strip()
    if niggle:
        found.append({
            "source": "check-in",
            "location": niggle,
            "pain": (checkin or {}).get("niggle_pain"),
            "detail": None,
        })

    for injury in (profile or {}).get("current_injuries") or []:
        data = injury if isinstance(injury, dict) else getattr(injury, "__dict__", {})
        location = str(data.get("location") or "").strip()
        if not location:
            continue
        # Una lesión RESUELTA no es una molestia. Desde la v1.1 se queda en la
        # tabla con `status="resuelta"` en vez de borrarse, y meterla aquí haría
        # que cada semana llevara una contingencia para una rodilla que lleva
        # seis meses bien -- justo el sesgo que los atletas de la beta
        # reportaron. Sin `status` se asume activa: es lo que significaba la
        # fila antes de que la columna existiera.
        if str(data.get("status") or "activa").strip().lower() == "resuelta":
            continue
        laterality = str(data.get("laterality") or "").strip()
        detail_parts = []
        if data.get("aggravating_factors"):
            detail_parts.append(f"empeora con {str(data['aggravating_factors']).strip()}")
        if data.get("treatment"):
            detail_parts.append(f"en tratamiento: {str(data['treatment']).strip()}")
        found.append({
            "source": "ficha de salud",
            "location": f"{location} ({laterality})" if laterality else location,
            "pain": data.get("pain_scale"),
            "detail": "; ".join(detail_parts) or None,
        })

    return found


def render_niggle_constraints(niggles: list[dict[str, Any]] | None) -> str:
    """The prompt block. Empty when the athlete declared nothing."""
    if not niggles:
        return ""

    lines = [
        "### Molestias declaradas por el atleta",
        "",
        "Esto lo escribió el atleta, no un sensor. Es lo más accionable del check-in: cambia qué sesiones, "
        "qué superficie y qué progresión tienen sentido esta semana.",
        "",
    ]
    for niggle in niggles:
        pain = f", dolor {niggle['pain']}/10" if niggle.get("pain") is not None else ""
        lines.append(f'- **«{niggle["location"]}»**{pain} — declarado en {niggle["source"]}.')
        if niggle.get("detail"):
            lines.append(f"  - {niggle['detail']}")

    # La fuerza de la respuesta es la del dolor (2026-09-13). Con psoas 1/10 y
    # rodilla 1/10, la semana de referencia del atleta 2 nombró las dos
    # estructuras en el `adaptation` de las DIEZ sesiones -- bici y fuerza
    # incluidas --, el macrociclo lo convirtió en una limitación médica de un
    # mes y el informe lo llamó "el principal riesgo para tu carrera". Es el
    # sesgo que la beta describió: lo que el atleta declaró, devuelto
    # amplificado. Un dolor de 1-2 se vigila donde se carga, no en todas partes.
    leves = all(isinstance(n.get("pain"), (int, float)) and n["pain"] <= 2 for n in niggles)
    donde = (
        "- **Es una molestia leve (dolor ≤2/10) y el atleta sigue entrenando.** Escribe la contingencia sólo "
        "en las dos o tres sesiones de la semana que MÁS cargan esa estructura con impacto o intensidad (la "
        "calidad, la carrera, la fuerza pesada de pierna), no en todas: repetirla en cada sesión le devuelve "
        "amplificado lo que el propio atleta dijo que es poco. En un rodaje suave, una natación o una fuerza ligera no "
        "hace falta nombrarla. No reorganices la semana por ella ni la conviertas en el tema del racional."
        if leves else
        "- Identifica **qué días de esta semana cargan esa estructura** (usa el mapa de abajo) y escribe el "
        "`adaptation` de **esos** días pensando en ella."
    )
    lines.extend([
        "",
        "**Cómo tiene que aparecer en la semana:**",
        donde,
        "- El `adaptation` tiene que **nombrar la estructura que el atleta declaró, con sus palabras y su "
        "lado**. Si dijo «ingle izquierda», el texto dice ingle izquierda — no rodilla, no cadera, no «la "
        "zona». Una adaptación escrita para otra parte del cuerpo es peor que no escribir ninguna: le dice "
        "al atleta por escrito que nadie leyó lo que puso.",
        "- Primero **adapta**, no canceles: acortar, bajar intensidad, cambiar superficie o cambiar de "
        "modalidad conserva el hábito y el estímulo; cancelar no conserva nada.",
        "- **No diagnostiques.** No nombres una lesión, una patología ni un tratamiento que el atleta no "
        "haya declarado. Si el dolor es alto, persistente o va a más, dilo y recomiéndale que lo vea un "
        "profesional presencialmente — eso sí es tu trabajo.",
        "",
        GESTURE_MAP_ES,
        "",
        "Si lo que declaró no está en el mapa, razona con la misma lógica: qué gesto repite esa sesión y qué "
        "estructura pone en juego. La lista es orientación, no un límite.",
    ])
    return "\n".join(lines) + "\n"


# --- Una molestia contada en las RESPUESTAS al coach (Beta 3.0, F10) -------------------------
#
# Jessie (2026-09-15) contestó «muy ligera molestia en el aquiles» a una pregunta del coach y su
# semana la citó en TODAS las sesiones. La proporción al dolor (arriba) sólo existía para la
# molestia del check-in; las respuestas llegaban a la decisión tal cual, sin ninguna regla. Las
# preguntas no se tocan (decisión del responsable): esto lee las respuestas en el nodo que las
# recibe y, sólo si dicen que algo molesta, le añade la misma regla. Un atleta que no menciona
# nada no recibe ni un token más.

_QUEJA = re.compile(r"molesti|dolor|duele|lesion|tendinit|sobrecarg|pinchaz|punzad|tiran|contractur")
_NEGADA = re.compile(r"\b(sin|ninguna?|ningun|nada de|no tengo|no me duele|no siento|ya no|ya paso|se me paso|"
                     r"desaparecio|curad[oa])\b")
_LEVE = re.compile(r"\b(leve|ligera|ligero|ligerita|poco|poquito|pequena|minima|minimo|apenas|casi nada)\b")
_DOLOR = re.compile(r"\b(\d{1,2})\s*(?:/|de|sobre)\s*10\b")


def molestias_en_respuestas(texto: str | None) -> list[dict[str, Any]]:
    """Las molestias que el atleta CUENTA en sus respuestas: una por frase que dice que algo
    molesta y no lo niega, con su número si lo da y si él mismo la llama leve."""
    salida = []
    for frase in re.split(r"[\n.;]+", texto or ""):
        normal = _strip_accents(frase)
        dolores = [int(n) for n in _DOLOR.findall(normal) if int(n) <= 10]
        # «peroneo 1/10 y psoas 1/10» (atleta 2, 2026-10-09) contesta a una pregunta de dolor sin
        # decir «dolor»: una parte del cuerpo con su número sobre 10 también lo es.
        queja = _QUEJA.search(normal) or (dolores and any(p in normal for p in _BODY_PARTS))
        if not queja or _NEGADA.search(normal):
            continue
        salida.append({
            "frase": frase.strip(" -0123456789.)").strip() or frase.strip(),
            "dolor": max(dolores) if dolores else None,
            "dice_que_es_leve": bool(_LEVE.search(normal)),
        })
    return salida


def nota_de_molestias_en_respuestas(texto: str | None) -> str:
    """La regla de proporción para lo que contó en sus respuestas, o "" si no contó nada."""
    molestias = molestias_en_respuestas(texto)
    if not molestias:
        return ""
    leves = all((m["dolor"] is not None and m["dolor"] <= 2) or (m["dolor"] is None and m["dice_que_es_leve"])
                for m in molestias)
    citas = "; ".join(f"«{m['frase']}»" for m in molestias)
    if leves:
        regla = ("El propio atleta la describe como leve (o con un dolor ≤2/10) y sigue entrenando. Nómbrala sólo en las "
                 "dos o tres sesiones que MÁS cargan esa estructura con impacto o intensidad, no en todas: "
                 "repetirla en cada sesión le devuelve amplificado lo que el atleta dijo que es poco. No reorganices la "
                 "semana por ella ni la conviertas en el tema del racional.")
    else:
        regla = ("Pesa en proporción a cómo la describe: escríbela en las sesiones que cargan esa estructura y "
                 "adapta (acortar, bajar intensidad, otra superficie) antes de cancelar. Si no dio un número, "
                 "no la des por leve ni la exageres: pídeselo en el racional.")
    return (f"Sobre lo que contó en sus respuestas ({citas}): es lo que el atleta dijo, no un diagnóstico. {regla} "
            "No nombres una lesión que no haya dicho.")


def unaddressed(niggles: list[dict[str, Any]] | None, days: list[dict[str, Any]] | None) -> list[str]:
    """Declared complaints that no day's `adaptation` mentions at all.

    A lint, run after generation and reported in the log -- never a gate. It
    answers exactly one question, the one the athlete asked implicitly by
    filling in the field: *did anybody write anything about the thing I said
    hurts?* A complaint mentioned on the wrong day still counts as mentioned;
    judging WHICH days should carry it is a coaching call, not a string match.
    """
    if not niggles or not days:
        return []

    corpus = _strip_accents(" ".join(
        " ".join(str(day.get(field) or "") for field in ("adaptation", "purpose", "session_sketch", "focus"))
        for day in days
        if isinstance(day, dict)
    ))

    missing: list[str] = []
    for niggle in niggles:
        structures = _structures(str(niggle.get("location") or ""))
        if structures and not any(structure in corpus for structure in structures):
            missing.append(str(niggle["location"]))
    return missing


def niggles_to_state(niggles: list[dict[str, Any]] | None) -> dict[str, Any]:
    """Serialize for LangGraph state and for the archived reasoning trail."""
    niggles = niggles or []
    return {
        "declared": niggles,
        "count": len(niggles),
        "block": render_niggle_constraints(niggles),
    }
