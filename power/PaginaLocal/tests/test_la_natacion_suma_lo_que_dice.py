"""Una natación que dice "Total 2500 m" y suma 2.300 (auditoría del 2026-09-23).

Atleta 2 (José), semana generada el 2026-09-23 con su petición de cuaderno:
"Natación: quiero sesiones de mínimo 2,5 km, y los viernes una sesión larga de
3,5 km". La decisión (gpt-5.2) escribió para el miércoles

    400 m + 4x50 m + 6x100 m + 4x200 m + 300 m. Total 2500 m.

que suma 2.300, y un racional que decía "Sigo tu petición de natación (2,5 km y
3,5 km)". El codificador copió las series: al reloj iban 2.300 m. El viernes
(600 + 8x50 + 3x500 + 10x100 = 3.500) estaba bien. Sobre las 32 nataciones
archivadas de los atletas 2 y 3, más las del banco de modelos del mismo día (14 con
total declarado), la comprobación sólo marca ésta.
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.validation.plan_validator import totales_que_no_suman  # noqa: E402

NODO = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")

MIERCOLES = (
    "Calentamiento 400 m (8 largos) suave + 4x50 m (1 largo) técnica (sculling/fist drill/catch-up) con 20\" "
    "de pausa. Luego 6x100 m (2 largos) aeróbico a 1:47-1:53/100 con 20\" de pausa. Luego 4x200 m (4 largos) "
    "a ritmo estable entre 1:45-1:50/100 con 25\" de pausa. Enfriamiento 300 m (6 largos) muy suave. Total 2500 m."
)
VIERNES = (
    "Calentamiento 600 m (12 largos) suave. Luego 8x50 m (1 largo) técnica (fingertip drag/catch-up/streamline) "
    "con 20\" pausa. Bloque principal: 3x500 m (10 largos) aeróbico a 1:47-1:53/100 con 30\" pausa. Luego "
    "10x100 m (2 largos) a ritmo umbral/CSS 1:43-1:47/100 con 20\" pausa (si no sale, quédate en 1:47-1:53). "
    "Enfriamiento 0 m (ya incluido en el volumen). Total 3500 m."
)


def _dia(fecha, texto, deporte="swim"):
    return {"date": fecha, "sport": deporte, "session_slot": "pm", "focus": "Técnica + aeróbico",
            "session_sketch": texto}


class ElTotalSeCompruebaContraLasSeries(unittest.TestCase):
    def test_la_del_miercoles_se_marca(self):
        self.assertEqual(totales_que_no_suman([_dia("2026-09-23", MIERCOLES)]),
                         [{"date": "2026-09-23", "session_slot": "pm", "focus": "Técnica + aeróbico",
                           "declarado_m": 2500, "suman_m": 2300}])

    def test_la_del_viernes_cuadra(self):
        self.assertEqual(totales_que_no_suman([_dia("2026-09-25", VIERNES)]), [])

    def test_las_notas_no_suman_y_contar_de_mas_no_se_marca(self):
        """Semana del 2026-09-28 del atleta 2: esta natación suma bien
        (600 + 400 + 1.600 + 600 + 300 = 3.500), y la primera versión la leyó como 3.750
        -- contaba "(50 m pool)" y los "últimos 200 m" del calentamiento -- y le costó una
        vuelta de corrección con un mensaje equivocado."""
        viernes = (
            "Total 3.500 m (50 m pool). Calentamiento 600 m (12 largos) suave, últimos 200 m algo más "
            "alegres. Técnica 8x50 m (1 largo) (fingertip drag / catch-up alternando), 20\" pausa. Principal "
            "4x400 m (8 largos) a ritmo aeróbico 1:47–1:53/100 m con 30\" pausa. Luego 6x100 m (2 largos) a "
            "1:45–1:50/100 m con 20–25\" pausa. Enfriar 300 m (6 largos) muy suave."
        )
        self.assertEqual(totales_que_no_suman([_dia("2026-10-02", viernes)]), [])
        con_subtotales = ("400 m suave (8 largos). 8x50 m técnica (400 m). 6x100 m aeróbico a 1:50/100 m "
                          "(600 m). Total 1400 m.")
        self.assertEqual(totales_que_no_suman([_dia("x", con_subtotales)]), [])

    def test_bloques_repetidos_y_kilometros(self):
        self.assertEqual(totales_que_no_suman([_dia("x", "2x(4x100 m + 200 m) y 1 km suave. Total 2,2 km")]), [])

    def test_sin_total_declarado_no_se_opina(self):
        self.assertEqual(totales_que_no_suman([_dia("x", "400 m + 6x100 m suave")]), [])

    def test_solo_natacion(self):
        self.assertEqual(totales_que_no_suman([_dia("x", "3x2 km. Total 10 km", deporte="run")]), [])


class SeLeDevuelveEnLaMismaVuelta(unittest.TestCase):
    def test_el_mensaje_dice_los_dos_numeros_y_quien_manda(self):
        totales = totales_que_no_suman([_dia("2026-09-23", MIERCOLES)])
        texto = NODO.mensaje_de_correccion([], '{"week_number": 1}', totales)
        self.assertIn("2500 m y las series suman 2300 m", texto)
        self.assertIn("si es lo que pidió el atleta, manda el total", texto)
        self.assertNotIn("CRITICAL SCHEDULING RULES", texto)
        self.assertIn('{"week_number": 1}', texto)

    def test_va_en_la_vuelta_si_la_hay_y_no_paga_una_sola(self):
        """El responsable, 2026-09-24: nada de reparaciones caras. Una natación que no suma no
        paga por sí sola otra decisión entera (~0,13 USD); va en la vuelta si la semana ya
        vuelve por disponibilidad o carga extrema, y si no se anota en el log. Lo que evita el
        error es que la petición pida el subtotal de cada serie (prueba de abajo)."""
        fuente = Path(NODO.__file__).read_text(encoding="utf-8")
        self.assertIn("totales = totales_que_no_suman(week_intent.days)", fuente)
        # 2026-09-24: un día mal fechado también abre la vuelta (number_audit.dias_mal_nombrados).
        self.assertIn("if fuera or extrema or dias_mal:", fuente)
        self.assertNotIn("if fuera or totales", fuente)
        self.assertIn('"; va en la vuelta" if (fuera or extrema or dias_mal) else "; sólo se anota"', fuente)

    def test_la_peticion_pide_el_subtotal_de_cada_serie(self):
        from services.ai.notebook import _COMO_SE_LEE_UNA_PETICION
        texto = _COMO_SE_LEE_UNA_PETICION["semana"]
        # Sólo la natación con metros pedidos: con "metros o minutos" la decisión puso la cuenta
        # también en la bici, con correcciones a la vista ("= 63' ??? -> Ajuste: ... = 75'").
        self.assertIn("termina ESA natación con la cuenta de cada serie en una sola línea", texto)
        self.assertIn("comprueba que llega a lo que pidió", texto)
        self.assertIn("sin correcciones", texto)
        self.assertNotIn("o en minutos", texto)


if __name__ == "__main__":
    unittest.main()
