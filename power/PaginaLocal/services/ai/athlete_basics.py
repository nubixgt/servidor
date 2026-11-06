"""Edad y sexo del atleta, que se recogían y no llegaban a ningún prompt.

## El hueco

La ficha los pide, el backend los valida (`gender ∈ female|male|other`,
`birth_date` como fecha) y MySQL los guarda. De ahí no salían: se comprobó
sobre los prompts archivados de una generación real, no leyendo el código —
cero apariciones de `gender`, `female`, `male` o de una edad en el prompt del
experto de fisiología y en el del planificador semanal.

Importa porque el experto de fisiología juzga la recuperación, y lo que es
esperable en HRV, en frecuencia cardiaca de reposo y en el tiempo que tarda
alguien en asimilar una sesión dura no es igual a los 25 que a los 55. Sin el
dato, el modelo no se abstiene: rellena el hueco con una norma poblacional
recordada, que es justo lo que el resto de este módulo existe para evitar.

## Lo que este bloque NO hace

No razona sobre el ciclo menstrual. La app sí lo recoge, pero **sólo si la
atleta lo activa expresamente** (`menstrual_tracking_opt_in`), y entonces
viaja por otro camino: el asistente de salud lo pregunta y queda en su
historial con sus propias palabras. Dar el sexo sin la fase invita a un modelo
a inventarse la fase, así que el bloque lo prohíbe explícitamente en vez de
callarse y confiar.

Tampoco autoriza a prescribir menos. La edad y el sexo calibran lo que
ESPERAS de sus números; lo que el atleta puede entrenar lo dicen sus datos
reales. Es la misma regla que ya rige la etiqueta de nivel autodeclarado en
`build_custom_planning_context`: evidencia por encima de etiqueta.
"""

from __future__ import annotations

import logging
from datetime import date, datetime
from typing import Any

logger = logging.getLogger(__name__)

# Los tres valores que el backend acepta (Backend/src/Support/ProfileFields.php).
# Cualquier otra cosa se ignora en vez de traducirse a medias: un valor que no
# reconocemos es un dato que no tenemos, no un dato que adivinar.
_SEXO_ES = {
    "female": "mujer",
    "male": "hombre",
    "other": "otro / no especificado",
}

# Una edad fuera de esto es un error de tecleo o una fecha de relleno, no un
# atleta. Se descarta en silencio: mejor no decir nada que decirle al
# planificador que su atleta tiene 120 años.
_EDAD_MIN = 10
_EDAD_MAX = 100


def calcular_edad(birth_date: Any, hoy: date | None = None) -> int | None:
    """Los años cumplidos, o None si la fecha no sirve.

    Tolerante a propósito: el campo llega de un `<input type="date">`
    (`YYYY-MM-DD`), pero también puede venir de Garmin o de un perfil viejo, y
    una fecha rara no puede tumbar una generación que el atleta ya pagó.
    """
    if not birth_date:
        return None

    if isinstance(birth_date, datetime):
        nacimiento = birth_date.date()
    elif isinstance(birth_date, date):
        nacimiento = birth_date
    else:
        texto = str(birth_date).strip()
        if not texto:
            return None
        # Se queda con la parte de fecha: un ISO con hora ("1992-03-15T00:00:00")
        # es una forma normal de que llegue esto.
        texto = texto.replace("/", "-").split("T")[0].split(" ")[0]
        try:
            nacimiento = date.fromisoformat(texto)
        except ValueError:
            logger.debug("birth_date no interpretable: %r", birth_date)
            return None

    hoy = hoy or date.today()
    edad = hoy.year - nacimiento.year - ((hoy.month, hoy.day) < (nacimiento.month, nacimiento.day))
    return edad if _EDAD_MIN <= edad <= _EDAD_MAX else None


def normalizar_sexo(gender: Any) -> str | None:
    """El sexo declarado en español, o None si no está o no se reconoce."""
    if not gender:
        return None
    return _SEXO_ES.get(str(gender).strip().lower())


def render_basics_block(profile: dict | None, hoy: date | None = None) -> str:
    """El bloque de prompt. Devuelve "" cuando no hay ni edad ni sexo.

    Vacío y no un bloque con "no disponible": una sección que anuncia lo que
    falta invita al modelo a razonar sobre el hueco, y aquí lo correcto es que
    no vea sección ninguna. Misma regla que `render_recovery_block`.
    """
    if not profile:
        return ""

    edad = calcular_edad(profile.get("birth_date"), hoy=hoy)
    sexo = normalizar_sexo(profile.get("gender"))
    peso = profile.get("weight_kg")
    estatura = profile.get("height_cm")
    if edad is None and sexo is None and peso is None and estatura is None:
        return ""

    lineas = ["\n### Quién es este atleta (declarado en su ficha)"]
    if edad is not None:
        lineas.append(f"- Edad: **{edad} años**.")
    if sexo is not None:
        lineas.append(f"- Sexo: **{sexo}**.")
    if peso is not None:
        lineas.append(f"- Peso: **{peso} kg**.")
    if estatura is not None:
        lineas.append(f"- Estatura: **{estatura} cm**.")

    lineas.append(
        "\n**Cómo usar esto**: la edad y el sexo calibran lo que es ESPERABLE en sus números —"
        " qué rango de HRV, qué frecuencia cardiaca en reposo y máxima, cuánto suele tardar en"
        " asimilar una sesión dura. Sirven para no leer sus datos contra los de otra persona."
        " **No son permiso para prescribirle menos**: lo que este atleta puede entrenar lo dicen"
        " sus datos reales de Garmin y su historial, nunca su edad ni su sexo. Un máster con"
        " veinte años de rodaje aguanta más que un veinteañero que empezó en marzo."
    )
    if sexo == "mujer":
        # El sexo sin la fase del ciclo es exactamente la clase de dato que un
        # modelo completa por su cuenta. Se le cierra la puerta aquí, donde
        # aparece el dato, en vez de confiar en que no se le ocurra.
        lineas.append(
            "\n**Lo que NO puedes concluir de aquí**: nada sobre el ciclo menstrual. Esta app sólo"
            " recoge esa información si la atleta la activa expresamente, y cuando lo ha hecho la"
            " verás en su historial de salud con sus propias palabras. No supongas una fase, no la"
            " infieras de un HRV bajo o de una noche mala, y no menciones el ciclo si ella no lo ha"
            " declarado."
        )

    return "\n".join(lineas) + "\n"
