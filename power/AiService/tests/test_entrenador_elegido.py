"""El entrenador que elige el atleta tiene que LLEGAR al chat.

## Por que existe esta prueba

Es exactamente la clase de fallo que ya aparecio cuatro veces en este puente:
un campo declarado en el esquema que nadie rellena, y el consumidor tirando de
un valor por defecto sin que nada de error. Paso con `weekly_plan`, con
`season_plan_md`, con `season_phases` y con `summary`. En los cuatro casos el
sintoma fue el mismo -- el entrenador contestando sobre datos que no eran los
del atleta -- y en ninguno hubo una excepcion que lo delatara.

Aqui el sintoma seria mas silencioso todavia: el atleta elige a Natasha, la
pantalla le pone la cara de Natasha, y le responde el estilo por defecto. Nada
falla, nadie se entera, y la funcion entera no sirve para nada.

Asi que se comprueba el eslabon: que `coach_id` viaje del cuerpo de la peticion
hasta `ask_coach`, sin quedarse por el camino.

Correr con:  python -m unittest discover -s AiService/tests -v
"""

import os
import sys
import unittest
from pathlib import Path
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

# El puente exige token en cada llamada; sin esto responde 500 antes de mirar
# nada. Se pone ANTES de importar main, que lo lee al cargarse.
os.environ.setdefault("BRIDGE_SERVICE_TOKEN", "test-token")

from fastapi.testclient import TestClient  # noqa: E402

import main  # noqa: E402
from bridge.schemas import CoachChatRequest  # noqa: E402

CLIENTE = TestClient(main.app)
CABECERA = {"X-Service-Token": os.environ["BRIDGE_SERVICE_TOKEN"]}


def _cuerpo(**extra):
    cuerpo = {
        "user_id": 42,
        "profile": {"name": "Jose"},
        "message": "¿por qué el martes es suave?",
    }
    cuerpo.update(extra)
    return cuerpo


class ElIdViajaHastaElChat(unittest.TestCase):
    """Se sustituye `ask_coach` por un espia: lo que se mide es QUE RECIBE, no
    lo que contesta un modelo."""

    def setUp(self):
        self.recibido = {}

        async def espia(storage_dir, profile, message, chat_history, coach_id=None, **kw):
            self.recibido["coach_id"] = coach_id
            return {"reply": "ok", "usage_metadata": {}}

        async def sin_notas(*a, **k):
            return [], {}

        self.enterContext(patch.object(main, "ask_coach", espia))
        self.enterContext(patch.object(main, "_anotar_costo", lambda *a, **k: None))
        # OJO: `propose_entries` se importa DENTRO del endpoint, asi que hay
        # que parchearlo en su modulo de origen y no en `main`. Sin esto la
        # prueba llama de verdad a la API de Anthropic -- se vio en el log al
        # correrla la primera vez ("HTTP Request: POST api.anthropic.com").
        # Ninguna prueba de este repo gasta dinero ni depende de la red.
        self.enterContext(patch("services.ai.notebook.propose_entries", sin_notas))

    def _pedir(self, **extra):
        r = CLIENTE.post("/bridge/coach-chat", json=_cuerpo(**extra), headers=CABECERA)
        self.assertEqual(r.status_code, 200, r.text)
        return self.recibido["coach_id"]

    def test_EL_FALLO_el_entrenador_elegido_llega(self):
        self.assertEqual(self._pedir(coach_id="natasha"), "natasha")

    def test_los_cuatro_llegan_tal_cual(self):
        for coach in ("juan", "fernanda", "ben", "natasha"):
            with self.subTest(coach=coach):
                self.assertEqual(self._pedir(coach_id=coach), coach)

    def test_sin_entrenador_elegido_llega_vacio_y_no_revienta(self):
        """Un atleta que nunca abrio Configuracion. El chat tiene que
        responder igual, con el estilo de siempre."""
        self.assertIn(self._pedir(), (None, ""))
        self.assertIn(self._pedir(coach_id=None), (None, ""))


class ElEsquemaLoDeclara(unittest.TestCase):
    def test_coach_id_es_opcional(self):
        """Sin valor por defecto, cualquier cliente viejo que no lo mande
        empezaria a recibir un 422 en mitad de una conversacion."""
        req = CoachChatRequest(user_id=1, profile={}, message="hola")
        self.assertIsNone(req.coach_id)


if __name__ == "__main__":
    unittest.main()
