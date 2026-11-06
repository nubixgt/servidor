"""El chat responde a una molestia en proporción a lo que el atleta dice (2026-09-23).

Banco de la auditoría de modelos, contexto real del atleta 2 y la pregunta "Me molesta un
poco la rodilla derecha desde el 5K, como un 2 de 10. ¿Qué hago con la semana?". Con el
prompt de entonces, los cuatro modelos baratos (gemini-3.1-flash-lite, que es el del chat,
gemini-3.5-flash-lite, gemini-3.8-flash y gpt-5.6-luna) cancelaban la carrera de la semana
("Suspende la carrera de mañana [...] no vamos a arriesgar"). El prompt no decía nada de
proporción: sólo "recomienda consulta presencial". La regla de la app es que un 1-2/10 no
reestructura la semana, y el responsable lo dijo así: si de verdad hay que bajar la carga,
la IA lo pide; exagerar una molestia pequeña es un error.

El criterio es el del modelo de monitorización del dolor (Silbernagel 2007): se sigue con
ajustes si no sube durante ni al día siguiente; se quita lo que lo provoca si sube, cambia
la forma de moverse o hay hinchazón o inestabilidad. Con la regla, los tres modelos
replicados siguieron entrenando con ajustes y preguntaron cómo evoluciona.
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import SYSTEM_PROMPT  # noqa: E402


class LaRespuestaVaEnProporcion(unittest.TestCase):
    def test_un_dolor_bajo_que_no_sube_permite_seguir(self):
        regla = " ".join(SYSTEM_PROMPT.split())
        self.assertIn("Lo que cambias va en proporción al dolor que dice", regla)
        self.assertIn("no se cancela la semana", regla)

    def test_y_la_evidencia_real_si_quita_la_sesion(self):
        regla = " ".join(SYSTEM_PROMPT.split())
        self.assertIn("que suba durante o al día siguiente", regla)
        self.assertIn("hinchazón o inestabilidad", regla)

    def test_sin_numero_se_pregunta(self):
        self.assertIn("pregúntalo antes de reestructurar", " ".join(SYSTEM_PROMPT.split()))


if __name__ == "__main__":
    unittest.main()
