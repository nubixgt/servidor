"""Inicio, la primera experiencia y los fallos de lógica que salieron al revisarlos (2026-10-21).

El responsable: «mejora la forma visual del resto de la app, empezando por Inicio; con ojo crítico,
determina fallos de lógica, UX y UI desde la primera interacción». Lo que se encontró, probado como José
(atleta 2) y Miguel (administrador, perfil al 0 %) en local, escritorio y 375 px:

- «Volumen» y «Semanas» de Inicio sumaban TODAS las semanas guardadas (hasta 12): no decían nada de esta.
- La adherencia de Inicio era la de la última generación («aparece al generar…»): ahora, los ✓/✗ de su
  Garmin de esta semana, sin IA (`test_estado_de_las_sesiones.py`).
- «Fuerza 10 km» en «Tu última semana»: la fuerza llegaba con distancia de Garmin.
- Progreso e Informes sólo estaban en la barra de arriba, que no se ve en el teléfono.
- El Legal partía las frases a mitad («Al pulsar / Acepto…»): cada salto del texto era un salto.
- La fecha salía «21 De Octubre» (`capitalize` de CSS).

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.charts import last_week, volume_by_sport  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]
INICIO = (RAIZ / "Frontend/src/views/admin/DashboardView.vue").read_text(encoding="utf-8")


def _semana(km_fuerza):
    return {"from": "2026-10-12", "to": "2026-10-18", "executed_hours": 3.4, "prescribed_hours": 3.9,
            "week_closed": True,
            "executed_by_discipline": {"fuerza": {"hours": 1.0, "km": km_fuerza}, "carrera": {"hours": 1.0, "km": 6.4}}}


class LaFuerzaNoTieneKilometros(unittest.TestCase):
    """La semana del 12 al 18 de octubre de José: «Fuerza 10 km» junto a «Carrera 6,4 km»."""

    def test_en_el_total_por_deporte(self):
        v = volume_by_sport([_semana(10.0)])
        self.assertEqual(v["total_km"], 6.4)
        self.assertEqual({p["label"]: p["km"] for p in v["points"]}["Fuerza"], 0.0)

    def test_en_la_ultima_semana(self):
        import services.ai.charts as charts
        original = charts._is_closed
        charts._is_closed = lambda w: True
        try:
            self.assertNotIn("Fuerza", last_week([_semana(10.0)])["km_by_sport"])
        finally:
            charts._is_closed = original


class Inicio(unittest.TestCase):

    def test_no_suma_todas_las_semanas_guardadas(self):
        for retirado in ("planWeeksCount", "planHours", "planSessions", "adherenceOffset"):
            self.assertNotIn(retirado, INICIO)

    def test_hoy_y_la_semana_salen_de_los_cheques_del_reloj(self):
        self.assertIn("store.estadoSesiones?.sesiones", INICIO)
        self.assertIn("progresoSemana", INICIO)

    def test_los_pasos_del_usuario_nuevo(self):
        for paso in ("'perfil'", "'garmin'", "'temporada'", "'semana'"):
            self.assertIn(f"id: {paso}", INICIO)
        self.assertIn("MINIMO_PERFIL", INICIO, "el mismo mínimo que pide el servidor para generar")

    def test_la_fecha_sin_capitalize(self):
        """En una clase: el comentario que explica por qué no se usa sí puede nombrarlo."""
        import re
        self.assertIsNone(re.search(r'class="[^"]*\bcapitalize\b', INICIO))


class LaNavegacionEnElTelefono(unittest.TestCase):

    def test_progreso_e_informes_en_el_menu_lateral(self):
        menu = (RAIZ / "Frontend/src/components/layout/Sidebar.vue").read_text(encoding="utf-8")
        self.assertIn("route: '/admin/analytics'", menu)
        self.assertIn("route: '/admin/reports'", menu)

    def test_la_barra_de_arriba_en_espanol(self):
        barra = (RAIZ / "Frontend/src/components/layout/TopHeader.vue").read_text(encoding="utf-8")
        self.assertNotIn("DASHBOARD", barra)
        self.assertNotIn("REPORTS", barra)


class ElLegalSeLeeEnParrafos(unittest.TestCase):

    def test_la_puerta_usa_el_modo_documento(self):
        puerta = (RAIZ / "Frontend/src/components/layout/LegalGate.vue").read_text(encoding="utf-8")
        self.assertIn("renderDocumento(doc.markdown)", puerta)


if __name__ == "__main__":
    unittest.main()
