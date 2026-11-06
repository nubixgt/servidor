"""Take the noise out of what the summarizers read, and only the noise.

Plan Maestro v6 Track 6.

## The number

One real season generation sent the activity summarizer **353,613 input
tokens** and got 1,814 back. 171 activities, 855 KB of Garmin JSON, compressed
195:1 before a single coaching decision was made.

## Why the obvious prune is the wrong prune

The tempting fix is to send less: fewer activities, no laps, summary rows
only. That would be trading away the product.

The athlete put it plainly: the difference between this app and Runna is *"los
detalles y el feedback constante haciendo planes de entreno totalmente
adaptativos"*. Lap splits are how you see that an interval session faded in the
last two reps; average HR per lap is how you see that a "Z2" run drifted into
Z3. Delete those and the plan gets cheaper and worse — which is the one trade
this project is not willing to make.

## So nothing that carries a number is removed

Every reduction here is provably lossless for a reader:

- **`null` fields are dropped.** `"avg_power": null` on a run appears on every
  activity and every lap and teaches nobody anything; 13 of the 29 fields in a
  real activity summary were null. An absent key and a null key say the same
  thing, and one of them costs tokens 171 times over.
- **Float noise is rounded away.** `0.9390000104904175` is 0.939 with fifteen
  digits of IEEE-754 representation error attached. `2.622999906539917` is
  2.623. Nobody coaches off the sixteenth decimal.
- **Zero-length laps are dropped, and counted.** A 0.6-second, 0.0-metre lap
  is the athlete pressing a button, not a split. They are removed with an
  explicit `laps_omitted` marker so the reader knows the list is not the whole
  list -- silence about a gap is exactly what this codebase's archive rules
  forbid.
- **Empty lists and dicts are dropped**, for the same reason as nulls.

And separately, the payload stops being pretty-printed. `indent=2` on 855 KB
of JSON is pure whitespace: it makes a file easier for a person to read and
costs a model real money to receive, and no person reads this one.

## What is deliberately NOT done

No field is dropped for being "probably unused". The moment this file starts
guessing which numbers a coach needs, it becomes the thing it was written to
avoid.
"""

from __future__ import annotations

from typing import Any

# Below both of these a lap is a button press, not a split: no distance, no
# meaningful time. Kept deliberately tiny -- a 10-second stride IS a split.
#
# ## El fallo que estos dos numeros causaron, y como se ve
#
# Los umbrales siempre fueron 5 segundos y 20 metros, y siempre fueron los
# correctos. Lo que estaba mal eran las UNIDADES de lo que se comparaba contra
# ellos: `get_activity_laps` entregaba la duracion en MINUTOS y la distancia en
# KILOMETROS, asi que la regla que se aplicaba de verdad era **"menos de 5
# minutos y menos de 20 kilometros"** -- es decir, casi cualquier tramo que no
# fuera un rodaje largo.
#
# Medido contra la cuenta real el 2026-09-10, ventana de 10 dias:
#
#   Guatemala - Ritmo 5K (20 tramos)   -> sobreviven 2: WARMUP y COOLDOWN
#   Guatemala - VO2max   (14 tramos)   -> sobreviven 4: WARMUP y 3 COOLDOWN
#   Natacion en piscina  (24 tramos)   -> sobreviven 2
#   ---------------------------------------------------------------
#   124 tramos reales -> 40 llegaban al modelo. El 68% borrado, y el
#   **100% de las repeticiones**: ni una sola serie de 2' sobrevivio.
#
# El sesgo no era aleatorio, que es lo que lo hacia caro: borraba exactamente
# el trabajo corto e intenso y dejaba intacto el largo y facil. El planificador
# de temporada escribio, con razon para lo que veia, *"el analisis de laps
# revela que en realidad son rodajes sostenidos sin estructura de intervalos"*
# sobre un atleta que habia hecho 8x2' a 4:45-5:39/km con picos de 188 ppm el
# dia anterior, y aplazo todo el trabajo de VO2max a la semana 21.
#
# Por eso las claves ahora llevan la unidad en el nombre (`duration_s`,
# `distance_m`): un umbral no puede volver a leer una magnitud que no es la
# suya sin que se vea en la linea.
_ARTIFACT_LAP_SECONDS = 5.0
_ARTIFACT_LAP_METRES = 20.0

# Enough precision for any training decision: 1 mm of distance, 1/1000 of a
# metre per second, a thousandth of a beat. Everything past this is float
# representation noise.
_DECIMALS = 3


def _round(value: float) -> float | int:
    rounded = round(value, _DECIMALS)
    return int(rounded) if rounded == int(rounded) else rounded


def _is_empty(value: Any) -> bool:
    """Null, or an empty container. Not zero -- a zero IS a measurement."""
    return value is None or (isinstance(value, (list, dict, str)) and len(value) == 0)


def _lap_is_artifact(lap: Any) -> bool:
    """Un tramo que es una pulsacion del boton, no un split.

    Lee SOLO las claves con unidad (`duration_s`, `distance_m`). Un tramo que
    no las trae no se descarta: no saber cuanto duro no es saber que no duro
    nada, y equivocarse aqui borra entrenamiento de verdad.
    """
    if not isinstance(lap, dict):
        return False
    duration = lap.get("duration_s")
    distance = lap.get("distance_m")
    try:
        short = duration is not None and float(duration) < _ARTIFACT_LAP_SECONDS
        tiny = distance is None or float(distance) < _ARTIFACT_LAP_METRES
    except (TypeError, ValueError):
        return False
    return short and tiny


def prune(value: Any) -> Any:
    """Drop nulls and empties, round float noise, recursively.

    Never removes a field that holds a number, a non-empty string, or a
    non-empty container.
    """
    if isinstance(value, bool):
        return value
    if isinstance(value, float):
        return _round(value)
    if isinstance(value, list):
        pruned = [prune(item) for item in value]
        return [item for item in pruned if not _is_empty(item)]
    if isinstance(value, dict):
        out: dict[str, Any] = {}
        for key, item in value.items():
            if key == "laps" and isinstance(item, list):
                kept = [lap for lap in item if not _lap_is_artifact(lap)]
                omitted = len(item) - len(kept)
                pruned_laps = prune(kept)
                if pruned_laps:
                    out["laps"] = pruned_laps
                if omitted:
                    # Named, never silent: the reader must be able to tell
                    # "this session had no splits" from "we hid some".
                    out["laps_omitted"] = f"{omitted} vuelta(s) de <5s y <20m (pulsaciones del boton)"
                continue
            cleaned = prune(item)
            if not _is_empty(cleaned):
                out[key] = cleaned
        return out
    return value


def measure(before: Any, after: Any) -> dict[str, Any]:
    """How much was saved, for a log line that can be checked."""
    import json

    raw = len(json.dumps(before, indent=2, ensure_ascii=False, default=str))
    lean = len(json.dumps(after, separators=(",", ":"), ensure_ascii=False, default=str))
    return {
        "chars_before": raw,
        "chars_after": lean,
        "saved_pct": round((1 - lean / raw) * 100, 1) if raw else 0.0,
    }
