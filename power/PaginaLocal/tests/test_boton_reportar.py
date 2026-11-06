"""El coach manda al atleta a un botón que tiene que existir, y estar donde dice.

Durante toda la beta el prompt del coach dijo *"el botón Feedback (arriba, a la
derecha)"* — y ese botón no existía en ninguna parte de la app: ni en el
frontend, ni en el backend, ni en la base de datos. El atleta lo buscó y no lo
encontró. Es la peor forma de fallar de un asistente: mandar con seguridad a un
sitio que no está.

Ahora existe (`Frontend/src/components/layout/FeedbackButton.vue`), se llama
**Ayuda** (era «Opinar» hasta la Beta 3.0, F3), está abajo a la derecha y abre
`components/modals/AyudaModal.vue`, donde el mensaje se escribe en la propia app.

Del 2026-10-10 al 2026-10-11 el botón ya decía «Ayuda» y el prompt del coach y la
Bienvenida seguían mandando a «Opinar»: la misma forma de fallar que el «Feedback» de
arriba. Estas pruebas estuvieron en rojo esos días porque miraban el botón y no la
ventana que abre. Estas pruebas atan las dos mitades:
que el botón siga montado, y que lo que el coach dice de él siga siendo verdad.
No comprueban maquetación — comprueban que el prompt y el componente no se
contradigan, que es lo que se rompe cuando alguien mueve uno de los dos.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import SYSTEM_PROMPT  # noqa: E402

# El frontend es hermano de PaginaLocal, no hijo: PaginaLocal/ -> raiz -> Frontend/
RAIZ = Path(__file__).resolve().parents[2]
BOTON = RAIZ / "Frontend/src/components/layout/FeedbackButton.vue"
# Lo que el botón abre (Beta 3.0, F3): pedir ayuda, opinar y ver lo que ya preguntó.
VENTANA = RAIZ / "Frontend/src/components/modals/AyudaModal.vue"
BIENVENIDA = RAIZ / "Frontend/src/views/admin/WelcomeView.vue"
LAYOUT = RAIZ / "Frontend/src/components/layout/MainLayout.vue"
# El botón dejó de posicionarse solo: ahora vive apilado con el del Coach.
FLOTANTES = RAIZ / "Frontend/src/components/layout/FloatingActions.vue"


class ElBotonExiste(unittest.TestCase):
    def setUp(self):
        if not BOTON.exists():
            self.skipTest("Frontend/ no esta presente en este arbol")

    def test_el_componente_existe(self):
        self.assertTrue(BOTON.exists(), f"falta {BOTON}")

    def test_esta_montado_en_el_layout(self):
        """Llega a TODAS las pantallas, que es lo que el prompt promete.

        Antes se montaba directamente en MainLayout. Ahora entra a través de
        FloatingActions, que es quien lo apila con el botón del Coach. Lo que
        se comprueba es la cadena entera -- que el botón sigue llegando al
        layout -- y no un `<FeedbackButton />` literal: esa aserción fallaba
        por haberlo movido de sitio, no por haberlo perdido, y una prueba que
        falla cuando nada se ha roto enseña a ignorarla.
        """
        # Desde el 2026-09-24 se esconde mientras el atleta elige entrenador o escribe
        # su cuaderno en la bienvenida (tapaba "Continuar" en el teléfono) y en el paso a
        # paso del perfil. En todo lo demás sigue: la condición es sólo esa.
        layout = LAYOUT.read_text(encoding="utf-8")
        self.assertIn('<FloatingActions v-if="!enElAlta" />', layout)
        self.assertIn("FloatingActions.vue", layout)
        self.assertIn("route.path.startsWith('/admin/bienvenida') && store.altaEnCurso", layout)

        flotantes = FLOTANTES.read_text(encoding="utf-8")
        self.assertIn("<FeedbackButton", flotantes)
        self.assertIn("FeedbackButton.vue", flotantes)

    def test_esta_abajo_a_la_derecha(self):
        """Que es lo que el prompt le promete al atleta.

        La posición está ahora en el contenedor, no en el botón, así que es
        ahí donde hay que mirarla.
        """
        texto = FLOTANTES.read_text(encoding="utf-8")
        self.assertRegex(texto, r"fixed\b")
        self.assertRegex(texto, r"bottom-\d")
        self.assertRegex(texto, r"right-\d")

    def test_no_puede_volver_a_taparse_con_el_del_coach(self):
        """El fallo que reporto el atleta: en un movil el boton del Coach caia
        justo encima del de Reportar y no se leia ninguno de los dos. Cada uno
        traia su propio `fixed` con coordenadas distintas (bottom-8/right-8 y
        bottom-5/right-5) y se solapaban.

        Apilados en un contenedor con flex ya no pueden pisarse. Lo que se ata
        aqui es eso: que ninguno de los dos vuelva a posicionarse por su cuenta.
        """
        self.assertNotRegex(
            BOTON.read_text(encoding="utf-8"), r"fixed\s+bottom-",
            "El boton de Reportar volvio a posicionarse solo; se pisara con el del Coach",
        )
        flotantes = FLOTANTES.read_text(encoding="utf-8")
        self.assertIn("flex-col", flotantes, "Los botones flotantes dejaron de estar apilados")

    def test_se_escribe_EN_LA_APP_y_whatsapp_es_la_alternativa(self):
        """Hasta el 2026-09-11 el boton solo abria WhatsApp. Lo que llega por
        ahi no se puede leer junto, no dice de que pantalla vino, y hay atletas
        que no le escriben a alguien que conocen. Ahora se escribe aqui y queda
        en athlete_feedback; WhatsApp sigue, a un toque."""
        self.assertIn("store.abrirAyuda()", BOTON.read_text(encoding="utf-8"))
        texto = VENTANA.read_text(encoding="utf-8")
        self.assertIn("enviarFeedback", texto)
        self.assertIn("services/feedback", texto)
        self.assertIn("VITE_FEEDBACK_WHATSAPP", texto)

    def test_le_dice_al_atleta_si_su_mensaje_no_llego(self):
        """Se tomo el tiempo de escribir: un fallo silencioso se lo traga."""
        self.assertIn("Mándanoslo por WhatsApp", VENTANA.read_text(encoding="utf-8"))


class ElCoachNoMienteSobreDondeEsta(unittest.TestCase):
    """La regresion concreta: el prompt decia "arriba, a la derecha"."""

    def test_el_prompt_lo_manda_abajo_a_la_derecha(self):
        self.assertIn("abajo a la derecha", SYSTEM_PROMPT)

    def test_y_ya_no_dice_arriba(self):
        self.assertNotIn("Feedback** (arriba", SYSTEM_PROMPT)
        self.assertNotIn("arriba, a la derecha", SYSTEM_PROMPT)

    def test_lo_llama_por_el_nombre_que_lleva_escrito(self):
        """Si el prompt dice "Feedback" y el boton dice "Reportar", el atleta
        busca una palabra que no esta en su pantalla."""
        if not BOTON.exists():
            self.skipTest("Frontend/ no esta presente en este arbol")
        nombre = re.search(r'<span class="hidden sm:inline">([^<]+)</span>', BOTON.read_text(encoding="utf-8"))
        self.assertIsNotNone(nombre, "el botón dejó de llevar su nombre escrito")
        self.assertIn(f"**{nombre.group(1)}**", SYSTEM_PROMPT)
        self.assertIn(f"font-bold\">{nombre.group(1)}</span>", BIENVENIDA.read_text(encoding="utf-8"))
        self.assertNotIn("**Opinar**", SYSTEM_PROMPT, "el botón ya no se llama así")

    def test_sigue_diciendo_que_esto_es_una_beta(self):
        """Es lo que convierte un fallo en algo que el atleta perdona."""
        self.assertIn("beta", SYSTEM_PROMPT)


if __name__ == "__main__":
    unittest.main()
