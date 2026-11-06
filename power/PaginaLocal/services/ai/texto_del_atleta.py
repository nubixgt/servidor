"""Palabras de alfabetos ajenos en el texto que lee el atleta.

El macrociclo regenerado de la atleta 3 (2026-09-14) empezaba "Plan estratégico
de temporada — Jessie Navas (RUN) դեպի 10K A": una palabra en armenio ("hacia")
en el título del informe que ella abre. Es un fallo conocido de los modelos al
mezclar idiomas, no algo que un prompt pueda impedir del todo, y un informe con
un alfabeto que el atleta no lee parece roto.

Se QUITAN las secuencias de alfabetos que un plan en español no usa nunca
(cirílico, armenio, hebreo, árabe, devanagari, georgiano, CJK, hangul) y se
registra qué se quitó. El griego se conserva a propósito: "Δ", "μ" o "VO₂" son
notación, no un idioma colado. No se intenta traducir: adivinar la palabra que
faltaba sería inventar texto.
"""

from __future__ import annotations

import logging
import re

logger = logging.getLogger(__name__)

_AJENOS = re.compile(
    "[Ѐ-ԯ԰-֏֐-׿؀-ۿऀ-ॿ"
    "Ⴀ-ჿ぀-ヿ㐀-䶿一-鿿가-힯]+"
)


def sin_alfabetos_ajenos(texto: str | None, donde: str = "") -> str:
    """El texto sin palabras de alfabetos ajenos, con los espacios recompuestos."""
    if not texto:
        return texto or ""
    encontrados = _AJENOS.findall(texto)
    if not encontrados:
        return texto
    logger.warning("Texto del atleta con alfabetos ajenos%s; se quitan: %s",
                   f" ({donde})" if donde else "", ", ".join(sorted(set(encontrados))))
    limpio = _AJENOS.sub("", texto)
    return re.sub(r"[ \t]{2,}", " ", limpio)
