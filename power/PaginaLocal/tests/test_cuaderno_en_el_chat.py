"""Coach y Cuaderno juntos, y «Reordenar cuaderno» en la pantalla (Beta 3.0, F9).

Lo que fija, leyendo el código del frontend y del PHP (no hay navegador en la suite):

- El cuaderno es UN panel (`components/chat/CuadernoPanel.vue`) que usan su página y la
  pestaña «Lo que sé de ti» del chat: dos copias divergen.
- Lo que el chat anota sigue guardándose solo (regla del cuaderno) y se ENSEÑA en el chat con
  «Quitar», que RETIRA con su motivo, nunca borra.
- Al aplicar un reorden el servidor decide la categoría de las preferencias: no se fía de lo
  que manda la pantalla, porque «Preferencias de entrenamiento» es lo que llega a su semana.

Dos fallos encontrados probándolo en el navegador como el atleta 2 (2026-10-11), con una
propuesta simulada de cuatro anotaciones:

1. Los tres botones «Aceptar» salían desactivados: `:disabled="a.estado"` con
   `estado = ''`, y en Vue 3 una cadena vacía en un atributo booleano lo deja PUESTO.
2. Al aceptar la fusión de dos anotaciones quedaban 3 vigentes, la caja se escondía (sólo
   salía con 4 o más) y las otras dos propuestas desaparecían a mitad de revisión.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
FRONT = RAIZ / "Frontend" / "src"
PANEL = (FRONT / "components" / "chat" / "CuadernoPanel.vue").read_text(encoding="utf-8")
CHAT = (FRONT / "components" / "modals" / "AiCoachModal.vue").read_text(encoding="utf-8")
PAGINA = (FRONT / "views" / "admin" / "CuadernoView.vue").read_text(encoding="utf-8")
SERVICIO_PHP = (RAIZ / "Backend" / "src" / "Services" / "NotebookService.php").read_text(encoding="utf-8")
COACH_PHP = (RAIZ / "Backend" / "src" / "Controllers" / "CoachController.php").read_text(encoding="utf-8")


class UnSoloPanel(unittest.TestCase):

    def test_la_pagina_y_el_chat_usan_el_mismo_panel(self):
        self.assertIn("import CuadernoPanel from '../../components/chat/CuadernoPanel.vue'", PAGINA)
        self.assertIn("import CuadernoPanel from '../chat/CuadernoPanel.vue'", CHAT)
        self.assertIn("<CuadernoPanel compacto />", CHAT)
        self.assertNotIn("handleAddNote", PAGINA, "la lógica vive en el panel, no copiada en la página")

    def test_el_chat_tiene_las_dos_pestanas(self):
        self.assertIn("'Conversación'", CHAT)
        self.assertIn("'Lo que sé de ti'", CHAT)


class LoQueAnotaElChat(unittest.TestCase):

    def test_el_servidor_devuelve_las_notas_creadas(self):
        self.assertIn("'notes' => $notas", COACH_PHP)

    def test_quitar_retira_con_motivo_y_no_borra(self):
        cuerpo = CHAT[CHAT.index("const quitarNota"):CHAT.index("const handleSend")]
        self.assertIn("status: 'retirada'", cuerpo)
        self.assertIn("retired_reason", cuerpo)
        self.assertNotIn("deleteNote", cuerpo)


class ReordenarEnLaPantalla(unittest.TestCase):

    def test_ningun_disabled_lee_una_cadena(self):
        """Fallo 1: `estado` es '' o 'aceptada'; el atributo booleano necesita un booleano."""
        self.assertNotRegex(PANEL, r':disabled="a\.estado"')
        self.assertIn(':disabled="!!a.estado"', PANEL)

    def test_la_caja_sigue_mientras_hay_propuesta(self):
        """Fallo 2: aceptar una fusión baja de 4 a 3 vigentes y la revisión no puede irse."""
        # Desde el 2026-10-11 se ofrece con 2 o más («Revisa mi cuaderno»); lo que importa es que
        # la condición incluya la propuesta abierta.
        m = re.search(r'<div v-if="([^"]*vigentes\.length >= \d[^"]*)"', PANEL)
        self.assertIsNotNone(m)
        self.assertIn("propuesta", m.group(1))

    def test_al_perfil_no_escribe_el_perfil(self):
        self.assertIn("Cópialo allí; aquí sólo se retira del cuaderno.", PANEL)
        cuerpo = SERVICIO_PHP[SERVICIO_PHP.index("function aplicarReorden"):]
        self.assertNotIn("ProfileService", cuerpo.split("function ", 2)[1])

    def test_el_servidor_no_se_fia_de_la_categoria_de_una_preferencia(self):
        cuerpo = SERVICIO_PHP[SERVICIO_PHP.index("function aplicarReorden"):]
        self.assertIn("'preferencia'", cuerpo.split("\n    public function", 1)[0])


if __name__ == "__main__":
    unittest.main()
