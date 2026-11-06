"""Estadísticas sin jerga (Beta 3.0, F4).

Decisión del responsable (2026-10-10): quitarle al atleta las estadísticas de jerga y
dejar las famosas como el VO2max. El ACWR, el Load Focus, la VFC, el estrés, la
disponibilidad y el sueño de Garmin se siguen calculando y la IA los sigue leyendo; el
atleta ve lo que entiende y el administrador todo con «Vista técnica».

El Inicio enseña «Tu última semana»: horas hechas contra las prescritas y km POR DEPORTE.
Con la semana real del atleta 2 del 26-sep al 4-oct: 7,9 h de 7,0, con 30,7 km de bici,
6,5 nadando y 6,3 corriendo. Sumados daban «43,6 km», que no es ningún entrenamiento.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.charts import last_week  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]
FRONT = RAIZ / "Frontend" / "src"

SEMANAS_DEL_ATLETA_2 = [
    {"from": "2026-09-21", "to": "2026-09-22", "prescribed_hours": 1.0, "executed_hours": 0.45,
     "completed_sessions": 1, "executed_by_discipline": {"carrera": {"hours": 0.45, "km": 5.17}}},
    {"from": "2026-09-26", "to": "2026-10-04", "prescribed_hours": 7.0, "executed_hours": 7.91,
     "completed_sessions": 8, "executed_by_discipline": {
         "bici": {"hours": 2.55, "km": 30.74}, "fuerza": {"hours": 2.76, "km": 0.0},
         "natacion": {"hours": 1.4, "km": 6.47}, "carrera": {"hours": 1.2, "km": 6.35}}},
    # La semana en curso: sin medir todavía (executed_* a None), no es «la última semana».
    {"from": "2026-10-12", "to": "2026-10-18", "prescribed_hours": 3.85, "executed_hours": None,
     "completed_sessions": None, "executed_by_discipline": None},
]


class LaUltimaSemana(unittest.TestCase):

    def test_la_semana_real_del_atleta_2(self):
        s = last_week(SEMANAS_DEL_ATLETA_2)
        self.assertEqual((s["from"], s["to"], s["hours"], s["prescribed_hours"]), ("2026-09-26", "2026-10-04", 7.9, 7.0))
        self.assertEqual(list(s["km_by_sport"]), ["Bici", "Natación", "Carrera"], "de más a menos km")
        self.assertNotIn("Fuerza", s["km_by_sport"], "donde no hay km no se dice")
        self.assertNotIn("km", s, "los km de deportes distintos no se suman")

    def test_sin_semanas_cerradas_no_hay_nada(self):
        self.assertEqual(last_week(SEMANAS_DEL_ATLETA_2[-1:]), {})

    def test_llega_a_las_graficas(self):
        fuente = (RAIZ / "PaginaLocal/services/ai/charts.py").read_text(encoding="utf-8")
        self.assertIn('"last_week": last_week(weeks),', fuente)


class LoQueVeElAtleta(unittest.TestCase):

    def _lista(self):
        js = (FRONT / "utils/progreso.js").read_text(encoding="utf-8")
        bloque = js[js.index("TARJETAS_DEL_ATLETA = ["):js.index("];", js.index("TARJETAS_DEL_ATLETA = ["))]
        return re.findall(r"'(\w+)'", bloque)

    def test_las_famosas_si_la_jerga_no(self):
        visibles = self._lista()
        self.assertIn("fitness", visibles, "el VO2max se queda")
        for jerga in ("load", "balance", "hrv", "stress", "readiness", "sleep_s"):
            self.assertNotIn(jerga, visibles)

    def test_ni_el_peso(self):
        """Decisión del responsable (2026-10-11): el peso, el pulso en reposo y el sueño no
        se quedan en las estadísticas del atleta. El plan F4 dejaba el peso dentro."""
        self.assertNotIn("weight", self._lista())

    def test_cada_tarjeta_de_la_lista_existe(self):
        """Una clave mal escrita escondería en silencio una tarjeta que sí debía verse."""
        vista = (FRONT / "views/admin/AnalyticsView.vue").read_text(encoding="utf-8")
        existentes = set(re.findall(r"key: '(\w+)'", vista))
        self.assertEqual(set(self._lista()) - existentes, set())

    def test_las_tecnicas_solo_para_el_administrador(self):
        vista = (FRONT / "views/admin/AnalyticsView.vue").read_text(encoding="utf-8")
        self.assertIn("store.isAdmin && vistaTecnica.value", vista)
        # Lo que se pinta es la lista filtrada (`visibles`), con o sin índice.
        self.assertRegex(vista, r'v-for="\(?c(, i\))? in visibles"')
        salud = (FRONT / "components/modals/HealthModal.vue").read_text(encoding="utf-8")
        for dato in ("hrv", "rhr", "acwr"):
            self.assertIn(f'v-if="{dato} && store.isAdmin"', salud)
        self.assertIn('v-if="store.profile?.resting_heart_rate && store.isAdmin"', salud,
                      "ni el pulso en reposo declarado (decisión del 2026-10-11)")
        inicio = (FRONT / "views/admin/DashboardView.vue").read_text(encoding="utf-8")
        self.assertIn('v-if="store.isAdmin && avanzadasVisibles && (acwr || recuperacion)"', inicio)

    def test_hooper_no_aparece_en_pantalla(self):
        for archivo in ("components/modals/CheckinModal.vue", "views/admin/DashboardView.vue"):
            plantilla = (FRONT / archivo).read_text(encoding="utf-8").split("<script")[0]
            sin_comentarios = re.sub(r"<!--.*?-->", "", plantilla, flags=re.S)
            self.assertNotIn("Hooper", sin_comentarios, archivo)


if __name__ == "__main__":
    unittest.main()
