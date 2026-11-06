"""El entrenador prometía anotar, y el cuaderno decidía que no.

## Lo que reportó el atleta el 2026-09-03

    "El cuaderno no escribió nada en un intento, cuando le dije al coach que me
     sentía bien y explícitamente que lo anotara. Dice 'Anotado en el cuaderno'
     pero no anotó todo, o a veces no anota nada."

Eran DOS fallos encadenados, y ninguno daba error.

## 1. El coach prometía algo que no puede hacer

El entrenador del chat **no escribe en el cuaderno**. De anotar se encarga
`propose_entries`, una llamada aparte que corre DESPUÉS de su respuesta y
decide por su cuenta. Pero su prompt sólo mencionaba el cuaderno como algo que
LEE ("lo que se ha ido anotando de él"), y nada le decía que no puede
escribirlo. Así que cuando el atleta le pidió que anotara, hizo lo servicial:
dijo "Anotado en el cuaderno".

El aviso de la app (`📓 He anotado N cosas…`) sí es honesto — sólo aparece con
`notes_added > 0`. El de la captura era texto del propio modelo.

## 2. El extractor tenía prohibido justo lo que le pidieron

"Me siento bien" es un estado de un solo día, y el prompt de extracción lo
tiene en su lista de *qué NO se anota* ("para eso está el check-in"). La regla
es buena para lo que el atleta NO pidió. Aplicada a lo que pidió expresamente,
convierte una petición suya en silencio.

Un atleta al que le fallan las cosas que pide expresamente deja de pedirlas.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import SYSTEM_PROMPT  # noqa: E402
from services.ai.notebook import (  # noqa: E402
    EXTRACTION_SYSTEM_PROMPT,
    pidio_que_se_anotara,
)

# Los dos prompts van justificados a ~100 columnas, así que una frase puede
# quedar partida. Se compara con los espacios colapsados.
COACH = re.sub(r"\s+", " ", SYSTEM_PROMPT)
EXTRACTOR = re.sub(r"\s+", " ", EXTRACTION_SYSTEM_PROMPT)


class ElCoachDiceLaVERDADSobreElCuaderno(unittest.TestCase):
    """Segunda pasada, 2026-09-04, con otra captura del atleta.

    La primera pasada arregló que el entrenador PROMETIERA anotar. Lo arregló
    de más: le prohibió decirlo en absoluto ("TÚ NO ESCRIBES EN EL CUADERNO.
    Nunca digas que has anotado algo"). Y esa frase dejó de ser cierta el mismo
    día, porque el arreglo de al lado -- el respaldo determinista de
    `pidio_que_se_anotara` -- convirtió la petición explícita en una GARANTÍA.

    Así que el atleta preguntó "¿puedes anotarlo en mi cuaderno?" y vio, una
    debajo de otra, estas dos cosas:

        Coach: "Yo no puedo escribir directamente en tu Cuaderno desde este chat."
        App:   "📓 He anotado 1 cosa en tu Cuaderno."

    Las dos mitades cumplieron lo que se les mandó. Lo que estaba mal era el
    mandato: una prohibición absoluta describiendo un sistema que ya no lo era.

    La regla ahora distingue los dos casos, que es lo único que hacía falta:
    si lo pidió, se anota siempre y no debe negarlo; si no lo pidió, sigue sin
    poder prometerlo.
    """

    def test_EL_FALLO_si_lo_pide_NO_puede_negarlo(self):
        self.assertIn("queda anotado, y no depende de ti", COACH)
        self.assertIn("no lo niegues ni lo pongas en duda", COACH)

    def test_se_nombran_las_dos_negativas_QUE_DIJO_de_verdad(self):
        """Prohibir "no lo niegues" en abstracto deja al modelo decidiendo qué
        cuenta como negar. Las dos frases de la captura van literales."""
        self.assertIn("yo no puedo escribir en tu cuaderno", COACH.lower())
        self.assertIn("si la app lo registra", COACH)

    def test_y_se_le_da_QUE_decir_en_su_lugar(self):
        self.assertIn("queda en tu Cuaderno", COACH)

    def test_si_NO_lo_pide_sigue_sin_poder_prometer(self):
        """Lo que la primera pasada arregló no se pierde: fuera del caso en que
        el atleta lo pide, el paso que anota sigue decidiendo solo y el coach
        sigue sin saber qué decidirá."""
        self.assertIn("SI NO TE LO PIDE, nunca digas que has anotado nada", COACH)
        self.assertIn("corre DESPUÉS de tu respuesta", COACH)
        self.assertIn("tú no sabes qué decidirá", COACH.lower())

    def test_no_se_inventa_como_funciona_la_app_por_dentro(self):
        """El otro daño de la captura: como tenía prohibido prometer, rellenó
        el hueco especulando sobre sus propias tripas ("desde este chat", "si
        la app lo registra", "una nota tipo..."). Y esa especulación acabó
        guardada como si fueran las palabras del atleta."""
        self.assertIn("No adivines cómo funciona la app por dentro", COACH)
        self.assertIn("desde este chat", COACH)

    def test_sigue_pudiendo_LEER_el_cuaderno(self):
        """Lo que nunca ha hecho es escribirlo él: el cuaderno es una de sus
        fuentes principales y sin él vuelve a empezar de cero cada charla."""
        self.assertIn("El cuaderno del entrenador", COACH)


class ElPromptYElCodigoDicenLoMISMO(unittest.TestCase):
    """La garantía la da el código, no el prompt. Tienen que coincidir.

    El entrenador ahora CONFIRMA la anotación cuando el atleta la pide, y quien
    la escribe de verdad es el respaldo determinista, que decide con una
    expresión regular. Si el prompt pone como ejemplo una forma de pedirlo que
    la expresión no reconoce, el entrenador dice "hecho, queda en tu Cuaderno"
    y no se guarda nada -- que es exactamente el fallo original, del revés.

    Escribir esta prueba encontró dos: "escríbelo en mi cuaderno" (la rama
    decía `escrib`, sin la tilde de la í) y "que quede en mi cuaderno" (la rama
    de "que quede" exigía un participio detrás).
    """

    # Las mismas formas que el prompt del entrenador le pone como ejemplo.
    FORMAS = [
        "anótalo",
        "apúntalo",
        "regístralo",
        "que no se te olvide",
        "que quede en mi cuaderno",
        # La de la captura, tal cual la escribió.
        "Consulto, puedes anotarlo en mi cuaderno?",
    ]

    def test_cada_forma_que_el_prompt_promete_la_reconoce_el_codigo(self):
        for forma in self.FORMAS:
            with self.subTest(forma=forma):
                self.assertTrue(
                    pidio_que_se_anotara(forma),
                    f"El prompt le dice al entrenador que confirme ante {forma!r}, "
                    "pero el respaldo no lo reconoce y no se anotaría nada.",
                )

    def test_las_formas_del_prompt_estan_TODAS_en_el_prompt(self):
        """Al revés: si alguien quita un ejemplo del prompt, esta lista deja de
        describir lo que el entrenador promete."""
        for forma in ("anótalo", "apúntalo", "regístralo", "que no se te olvide"):
            with self.subTest(forma=forma):
                self.assertIn(forma, COACH)

    def test_una_pregunta_normal_NO_dispara_el_respaldo(self):
        """El respaldo es deliberadamente amplio, pero no tanto: si cualquier
        mensaje contara como petición, el cuaderno sería un volcado del chat."""
        for forma in ("¿por qué mi semana está planteada así?",
                      "¿cuántos km he corrido este mes?",
                      "¿para qué sirve una sesión de VO2máx?"):
            with self.subTest(forma=forma):
                self.assertFalse(pidio_que_se_anotara(forma))


class UnaPeticionExplicitaSeAnotaSiempre(unittest.TestCase):
    def test_EL_FALLO_pedirlo_manda_sobre_todas_las_demas_reglas(self):
        self.assertIn("Si el atleta PIDE que lo anotes, se anota. Sin excepción", EXTRACTOR)
        self.assertIn("eso manda por encima de todo lo de abajo", EXTRACTOR)

    def test_se_nombran_las_formas_en_que_lo_pide_de_verdad(self):
        """"Anótalo" no es la única: el modelo tiene que reconocer la petición
        aunque venga con otras palabras."""
        for forma in ("anótalo", "apúntalo", "que no se te olvide"):
            with self.subTest(forma=forma):
                self.assertIn(forma, EXTRACTOR)

    def test_la_peticion_gana_incluso_a_un_estado_de_un_solo_dia(self):
        """El caso exacto que falló: "me siento bien" + "anótalo"."""
        self.assertIn("aunque sea un estado de un solo día", EXTRACTOR)
        self.assertIn("aunque no cambie ninguna decisión de planificación", EXTRACTOR)

    def test_la_lista_de_prohibidos_dice_que_queda_anulada(self):
        """La regla nueva no sirve de nada si tres líneas más abajo el modelo
        lee "estados de un solo día: NO" sin saber que ya no aplica."""
        self.assertIn("Todo esto queda anulado si el atleta pidió expresamente", EXTRACTOR)

    def test_lo_normal_SIGUE_siendo_no_anotar_nada(self):
        """El arreglo no puede convertir el cuaderno en un volcado del chat: la
        regla por defecto se conserva, sólo deja de aplicarse cuando él pide."""
        self.assertIn("Lo normal es no anotar nada", EXTRACTOR)
        self.assertIn("No inventes una anotación para parecer atento", EXTRACTOR)

    def test_y_que_ahi_la_lista_vacia_es_un_FALLO(self):
        self.assertIn("si lo pidió, la lista vacía es un fallo", EXTRACTOR.lower())


if __name__ == "__main__":
    unittest.main()
