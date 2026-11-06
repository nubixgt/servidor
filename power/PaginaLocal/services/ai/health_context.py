"""Turn the athlete's declared health record into something the coach agents
actually read.

Plan Maestro v5 Track 10.

The health module collected a lot and delivered none of it. `current_injuries`
(location, laterality, onset, pain 0-10, aggravating factors, treatment) were
persisted on the profile and **no agent ever saw any of them** -- `build_custom_planning_context`
read only the free-text `limitations` field. A form whose output is discarded
is worse than no form: it asks the athlete to disclose an irritated knee and
then prescribes as if they had not.

So the fix here is not a blocking rule. A deterministic gate that refused to
generate on a positive screening answer would produce nothing the athlete can
use and would not make them any safer. What makes the module worth having is
the cable: the coach sees the knee, and the athlete sees a clear, dated
recommendation to have it looked at by a person.

Deliberately NOT done here:
- No diagnosis, no naming a condition the athlete did not declare, no
  treatment. The block says so explicitly to the model.
- No population-threshold rule ("pain >= 7 means no running"). Pain scales are
  personal and a number alone does not decide a session; it is context handed
  to a coach, the same treatment HRV gets in services/ai/recovery_state.py.
"""

from __future__ import annotations

from typing import Any

_LATERALITY_ES = {
    "izquierda": "izquierda",
    "derecha": "derecha",
    "bilateral": "bilateral",
}

def _injury_rows(profile: dict) -> list[dict]:
    """Las lesiones declaradas que al menos dicen dónde, en bruto."""
    out: list[dict] = []
    for injury in profile.get("current_injuries") or []:
        data = injury if isinstance(injury, dict) else getattr(injury, "__dict__", {})
        if str(data.get("location") or "").strip():
            out.append(data)
    return out


def active_injuries(profile: dict) -> list[dict]:
    """Las que duelen AHORA.

    Desde la v1.1 una lesión curada se queda en la tabla con
    `status="resuelta"` en vez de borrarse -- el historial empieza a valer
    justo cuando deja de doler. Pero una lesión resuelta **no es una
    restricción**, y meterla aquí haría que el plan siguiera esquivando una
    rodilla que lleva seis meses bien. Ese es exactamente el sesgo que los
    atletas de la beta reportaron.

    Sin `status` se asume activa: es lo que significaba la fila antes de que
    la columna existiera, y las de los cinco atletas de la beta no lo traen.
    """
    return [r for r in _injury_rows(profile)
            if str(r.get("status") or "activa").strip().lower() != "resuelta"]


def resolved_injuries(profile: dict) -> list[dict]:
    """Las que ya no duelen. Son historial, no restricción."""
    return [r for r in _injury_rows(profile)
            if str(r.get("status") or "").strip().lower() == "resuelta"]


def health_flags(profile: dict) -> dict[str, Any]:
    """Compact machine-readable read of the athlete's declared health record.

    Used by the UI to decide what to surface, and archived with each
    generation so it is possible to see later what the coach was told.
    """
    injuries = active_injuries(profile)
    return {
        "active_injury_count": len(injuries),
        "injury_locations": [i.get("location") for i in injuries],
        "resolved_injury_count": len(resolved_injuries(profile)),
    }


def _render_injury(injury: dict) -> str:
    location = str(injury.get("location") or "").strip()
    parts = [location]

    laterality = _LATERALITY_ES.get(str(injury.get("laterality") or "").strip().lower())
    if laterality:
        parts.append(f"lado {laterality}")

    pain = injury.get("pain_scale")
    if pain is not None:
        parts.append(f"dolor {pain}/10")

    onset = str(injury.get("onset") or "").strip()
    if onset:
        parts.append(f"desde {onset}")

    line = f"- **{parts[0]}**" + (f" ({', '.join(parts[1:])})" if len(parts) > 1 else "")

    aggravating = str(injury.get("aggravating_factors") or "").strip()
    if aggravating:
        line += f"\n  - Empeora con: {aggravating}"

    treatment = str(injury.get("treatment") or "").strip()
    if treatment:
        line += f"\n  - Tratamiento en curso: {treatment}"

    # Lo que el atleta escribio con sus palabras sobre ESTA lesion. Va el
    # ultimo y entero: es lo unico de la ficha que no cabe en un desplegable.
    notes = str(injury.get("notes") or "").strip()
    if notes:
        line += f"\n  - Dice el atleta: {notes}"

    return line


def render_health_block(profile: dict) -> str:
    """The athlete's declared health record, framed for a coach and not a clinician.

    Returns "" when the athlete declared nothing, so an empty record costs no
    prompt tokens rather than emitting a block full of "ninguno".
    """
    injuries = active_injuries(profile)
    resueltas = resolved_injuries(profile)
    past = str(profile.get("past_injuries_surgeries") or "").strip()
    chronic = str(profile.get("chronic_conditions") or "").strip()
    medications = str(profile.get("medications") or "").strip()
    nutricion = str(profile.get("nutrition_notes") or "").strip()

    # Ya NO se leen aqui, y es a proposito (v1.2):
    #
    #   - `par_q_flags`, el tamizaje de aptitud fisica de siete preguntas
    #   - `injury_prone`, la casilla "me considero propenso"
    #   - `doctor_performance_report`, el informe del asistente de salud
    #   - `health_disclaimer_accepted` y `last_medical_evaluation`
    #
    # Los cuatro primeros eran cuatro de los SEIS canales por los que se le
    # preguntaba al atleta por lesiones, y ese exceso -- no los prompts -- era
    # la causa del sesgo que la beta reporto: lo que sentian era lo que ellos
    # mismos habian declarado, volviendoles amplificado. La v1.1 quito el chat
    # del doctor de la UI; esto quita lo que quedaba de esa rama.
    #
    # El descargo medico lo sustituye la PUERTA LEGAL (`users.terms_accepted_at`,
    # migracion 015), que lo dice mejor y en el sitio correcto: antes de entrar,
    # bloqueando, y con constancia de version. Dos mecanismos para lo mismo eran
    # uno de mas.
    #
    # Lo que SI sigue: lesiones activas y resueltas, condiciones cronicas,
    # medicacion, cirugias pasadas y nutricion. Eso es historial declarado que
    # cambia la modalidad y la carga, y el atleta lo escribe cuando le pasa --
    # no un cuestionario clinico que hay que rellenar para entrar.
    if not any([injuries, resueltas, past, chronic, medications, nutricion]):
        return ""

    lines: list[str] = [
        "## Antecedentes de salud declarados por el atleta",
        "",
        "Esto es **historial autodeclarado**, no un diagnóstico. Úsalo para decidir modalidad, carga y "
        "progresión — nunca para diagnosticar, nombrar una condición que el atleta no declaró, ni "
        "prescribir tratamiento.",
        "",
    ]

    if injuries:
        lines.append("### Lesiones o molestias actuales")
        lines.extend(_render_injury(injury) for injury in injuries)
        lines.append("")
        lines.append(
            "Trátalas como **restricciones reales de modalidad y de carga**: elige superficies, deportes "
            "y progresiones que las respeten, y di en `purpose`/`adaptation` cómo la sesión las tiene en "
            "cuenta. Que una molestia exista no significa que el atleta deba descansar: casi siempre "
            "significa cambiar el estímulo, no eliminarlo."
        )
        lines.append("")

    if chronic:
        lines.extend([f"### Condiciones crónicas declaradas\n{chronic}", ""])

    if medications:
        lines.extend([
            f"### Medicación declarada\n{medications}",
            "",
            "Algunos fármacos alteran la respuesta de frecuencia cardíaca o la tolerancia al calor. Si es "
            "el caso, apóyate más en ritmo y esfuerzo percibido que en zonas de FC, y dilo explícitamente.",
            "",
        ])

    if resueltas:
        # Historial, NO restriccion. Un plan que sigue esquivando una rodilla
        # que lleva seis meses bien es exactamente el sesgo que los atletas de
        # la beta reportaron: "la parte de lesiones es uno de los sesgos mas
        # graves que afectan la utilidad de la app".
        lines.append("### Lesiones ya resueltas")
        lines.extend(_render_injury(injury) for injury in resueltas)
        lines.append("")
        lines.append(
            "Estas **ya no duelen y NO son una restriccion**. Sirven para saber por donde se le ha roto "
            "antes a este cuerpo, que cambia como se le introduce un estimulo nuevo -- no para esquivar "
            "esa zona indefinidamente. No las menciones en `adaptation` salvo que la sesion cargue de "
            "verdad esa estructura y haya una razon concreta para decirlo."
        )
        lines.append("")

    if past:
        lines.extend([f"### Lesiones y cirugías pasadas\n{past}", ""])

    if nutricion:
        # `nutrition_notes` no llegaba a NINGÚN modelo salvo el asistente de
        # salud. Ni al planificador de temporada, ni al semanal, ni a los tres
        # expertos, ni al coach. Un atleta real había escrito ahí la frase más
        # decisiva de su ficha -- "tengo 25% de grasa y debo tenerla en 15%; el
        # nutricionista me pidió 3 sesiones de fuerza a la semana" -- y su plan
        # salió con la fuerza reducida a prehab, porque lo único que el modelo
        # llegó a ver de él fueron sus cuatro lesiones pasadas.
        #
        # Va en esta sección y no en un bloque propio a propósito: es
        # información declarada por el atleta sobre su cuerpo, del mismo tipo y
        # con la misma advertencia que todo lo demás que hay aquí.
        lines.extend([
            f"### Nutrición y composición corporal, según el atleta\n{nutricion}",
            "",
            "Es lo que el atleta declara, a veces citando a un profesional que le atiende. **Léelo "
            "como una restricción y como un objetivo suyo, no como una sugerencia que se pueda "
            "descartar en silencio**: si pide un número de sesiones de fuerza a la semana, eso es "
            "una demanda concreta sobre la forma de la semana, igual que un día que el atleta marcó como fijo. Si no "
            "cabe con el volumen o con la fase, dilo y explica qué priorizaste — lo que no vale es "
            "que desaparezca sin mención. Nada de esto te convierte en nutricionista: no "
            "prescribas dieta, calorías ni suplementos.",
            "",
        ])

    return "\n".join(lines).strip()
