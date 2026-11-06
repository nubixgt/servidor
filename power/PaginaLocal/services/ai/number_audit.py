"""Check that every number an agent quoted was actually given to it.

Plan Maestro v6 Track 1, and the measuring instrument for v6's first
acceptance criterion: *"todo número del `week_rationale` es comprobable"*.

## The defect this measures

The first real generation told the athlete, in the week's rationale, that it
was *"permitir que tu ACWR baje de un elevado 1.47"*. Garmin's real value that
morning was **1.10, `OPTIMAL`** -- and the app's own log had printed it
correctly minutes earlier. The model was never handed the number, so it
inferred one from prose and got it wrong (v6 finding A1). The same paragraph
also cited a "TSB de -20.6" that no endpoint the app calls even reports.

A number the athlete can check against their own watch is the difference
between a coach and a plausible-sounding text generator. This module makes
that property measurable instead of a hope.

## It is a lint, not a law

This never blocks a generation and never rewrites a rationale. It reports
*candidates*: numbers that appear in the text and do not appear in what the
model was given. Some candidates will be legitimate -- a coach may correctly
say "las 3 sesiones de calidad" about a week they themselves just wrote. The
heuristics below exist to keep that noise low enough that the signal is worth
reading, and they are deliberately conservative:

- **Integers below 10 are ignored.** Rep counts, session counts, zone numbers
  and RPE values are derived from the plan itself, not quoted from data.
- **Decimals, clock times and percentages are always checked.** These are what
  a measurement looks like: 1.47, 5:47, 40%. A model does not arrive at
  "1.47" by counting something it wrote.
- **An integer is supported by any source number that rounds to it**, so a
  rationale saying "los 21 km" is supported by a prompt saying "21.2 km".

The one thing this must never become is a validator that fails a plan. A
guardrail that made generations retry against a text-matching heuristic would
be exactly the kind of rule this project has repeatedly found to be worse than
the problem it targets.
"""

from __future__ import annotations

import re
from collections.abc import Iterable
from datetime import date
from typing import Any

# Any run of digits possibly carrying decimal, thousands or clock separators.
# Deliberately excludes '-' so that "2026-08-28" yields 2026, 08 and 28 -- a
# rationale citing "el 28" is then supported by the date it came from.
_NUMBER = re.compile(r"\d[\d.,:]*\d|\d")

# Below this, an integer is treated as something the model counted rather than
# something it was told. See the module docstring.
SMALL_INTEGER_LIMIT = 10


def _normalize(token: str) -> str | None:
    """Reduce a numeric literal to a comparable canonical form.

    "1,10" and "1.10" and "1.1" all become "1.1"; "5:47" stays "5:47";
    "1.234" written as a thousands separator becomes "1234" only when it
    cannot be a decimal.
    """
    token = token.strip().rstrip(".,:")
    if not token:
        return None
    if ":" in token:
        # A clock/pace value. Normalise each field's leading zeros so
        # "05:47" and "5:47" compare equal.
        parts = token.split(":")
        if not all(part.isdigit() for part in parts):
            return None
        return ":".join(str(int(part)) if len(part) > 1 else part for part in parts)

    candidate = token.replace(",", ".")
    if candidate.count(".") > 1:
        # 1.234.567 -- separators can only be grouping.
        candidate = candidate.replace(".", "")
    elif "." in candidate:
        whole, _, frac = candidate.partition(".")
        # Exactly three trailing digits after a single separator is ambiguous
        # ("1.500" is 1.5 in English and 1500 in Spanish). Both readings are
        # kept comparable by normalising to the decimal one and letting the
        # rounding rule in `_supported` catch the other.
        if not frac.isdigit() or not whole.isdigit():
            return None
    try:
        value = float(candidate)
    except ValueError:
        return None
    if value == int(value):
        return str(int(value))
    return ("%f" % value).rstrip("0").rstrip(".")


def numbers_in(text: str) -> list[str]:
    """Every numeric literal in a piece of text, as written."""
    return _NUMBER.findall(text or "")


def _source_forms(sources: Iterable[str]) -> tuple[set[str], list[float]]:
    """Normalised numbers available to the model, plus their float values."""
    forms: set[str] = set()
    values: list[float] = []
    for source in sources:
        for token in numbers_in(source):
            normalized = _normalize(token)
            if normalized is None:
                continue
            forms.add(normalized)
            if ":" not in normalized:
                try:
                    values.append(float(normalized))
                except ValueError:
                    pass
    return forms, values


def _kind(token: str, normalized: str) -> str:
    if ":" in normalized:
        return "clock"
    if "." in normalized or "," in token or "." in token:
        return "decimal"
    return "integer"


def _supported(normalized: str, kind: str, forms: set[str], values: list[float]) -> bool:
    if normalized in forms:
        return True
    if kind == "integer":
        try:
            target = int(normalized)
        except ValueError:
            return False
        # "los 21 km" is supported by a prompt that said "21.2 km".
        return any(int(value) == target or round(value) == target for value in values)
    return False


def find_unsupported_numbers(
    text: str,
    sources: Iterable[str],
    small_integer_limit: int = SMALL_INTEGER_LIMIT,
    context_chars: int = 60,
) -> list[dict[str, Any]]:
    """Numbers quoted in `text` that do not appear in any of `sources`.

    `sources` is everything the model was legitimately given -- normally the
    rendered prompt, and optionally the model's own structured output when the
    text is meant to describe it.
    """
    forms, values = _source_forms(sources)
    findings: list[dict[str, Any]] = []
    seen: set[str] = set()

    for match in _NUMBER.finditer(text or ""):
        token = match.group(0)
        normalized = _normalize(token)
        if normalized is None or normalized in seen:
            continue
        kind = _kind(token, normalized)
        if kind == "integer":
            try:
                if abs(int(normalized)) < small_integer_limit:
                    continue
            except ValueError:
                continue
        if _supported(normalized, kind, forms, values):
            continue
        seen.add(normalized)
        start = max(0, match.start() - context_chars)
        end = min(len(text), match.end() + context_chars)
        findings.append({
            "literal": token,
            "normalized": normalized,
            "kind": kind,
            "context": text[start:end].replace("\n", " ").strip(),
        })
    return findings


def audit_summary(text: str, sources: Iterable[str]) -> dict[str, Any]:
    """A one-line verdict plus the offending numbers, for a bench report."""
    findings = find_unsupported_numbers(text, sources)
    # Counted the same way the findings are: once per distinct value, so the
    # ratio "2 of 9 unsupported" compares like with like.
    checked: set[str] = set()
    for token in numbers_in(text):
        normalized = _normalize(token)
        if normalized is None:
            continue
        kind = _kind(token, normalized)
        if kind == "integer":
            try:
                if abs(int(normalized)) < SMALL_INTEGER_LIMIT:
                    continue
            except ValueError:
                continue
        checked.add(normalized)
    return {
        "numbers_checked": len(checked),
        "unsupported_count": len(findings),
        "unsupported": findings,
    }


# "viernes 26/09" con el 26 en sábado. Primera semana del atleta 2 tras la auditoría del
# 2026-09-24: el racional decía "este bloque es corto (viernes 26/09 a domingo 27/09)" y el
# viernes era el 25, aunque la decisión recibía las fechas con su día. Qué falla -- el día o la
# fecha -- no se sabe, así que no se corrige: se AVISA en el worker.log, como el resto de este
# módulo, y se mide cuántas veces pasa antes de decidir nada más.
_DIAS_ES = {"lunes": 0, "martes": 1, "miercoles": 2, "miércoles": 2, "jueves": 3, "viernes": 4,
            "sabado": 5, "sábado": 5, "domingo": 6}
_DIA_Y_FECHA = re.compile(r"\b(lunes|martes|mi[eé]rcoles|jueves|viernes|s[aá]bado|domingo)\s+(\d{1,2})/(\d{1,2})\b",
                          re.IGNORECASE)


def dias_mal_nombrados(texto: str, referencia: date) -> list[str]:
    """Cada "día dd/mm" del texto cuyo día de la semana no es el de esa fecha. [] si todos cuadran.

    El año es el de `referencia` (el inicio de la semana), o el siguiente si la fecha caería más de
    dos meses antes: un "martes 20/04" escrito en septiembre es del año que viene, y un "20/09"
    escrito el 25 de septiembre es de hace cinco días.
    """
    nombres = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]
    malos = []
    for m in _DIA_Y_FECHA.finditer(texto or ""):
        dia, mes = int(m.group(2)), int(m.group(3))
        try:
            fecha = date(referencia.year, mes, dia)
            if (referencia - fecha).days > 60:
                fecha = date(referencia.year + 1, mes, dia)
        except ValueError:
            continue
        real = fecha.weekday()
        if _DIAS_ES[m.group(1).lower()] != real:
            malos.append(f"«{m.group(0)}» ({dia}/{mes} es {nombres[real]})")
    return malos


_NOMBRES_DIA = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]


def fecha_escrita(iso: str | None) -> str:
    """"2026-09-25" -> "viernes 25/09": la forma en que el atleta lee una fecha, calculada por el
    código para que ningún modelo tenga que calcular qué día de la semana es. "" si no se entiende."""
    try:
        d = date.fromisoformat(str(iso or "")[:10])
    except ValueError:
        return ""
    return f"{_NOMBRES_DIA[d.weekday()]} {d.day:02d}/{d.month:02d}"


def con_fecha_escrita(elementos: list | None, clave: str = "date") -> list:
    """Copias de cada dict con `se_escribe` al lado de su fecha. Lo que no es dict va tal cual."""
    salida = []
    for e in elementos or []:
        if isinstance(e, dict) and fecha_escrita(e.get(clave)):
            salida.append({**e, "se_escribe": fecha_escrita(e.get(clave))})
        else:
            salida.append(e)
    return salida
