"""Los «sí o sí» que el atleta casi no hace, enseñados sin IA (decisión del responsable, 2026-10-23).

«Que la app le alerte y le diga eso sin costo de IA, cuando sea 1 de 4.» Fernando (u13 en producción) marcó
13 «sí o sí» sin un día de descanso. Cada uno va al plan en su día (regla 4), así que sus semanas salían con
14-17 sesiones, la carga extrema y una segunda decisión pagada todas las semanas: $0,63 de media por semana
contra $0,28-0,46 de los demás (libro de costos desde el 23-sep), y él hacía 3,7-8,8 h de las 8,5-13
prescritas. Su log del 18-oct ya lo sabía y sólo lo escribía para el modelo («Declarado vs hecho: declaró
natación los jueves: lo hizo en 0 de 4 semanas»). Los números de abajo son los de ese log y su tabla.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.training_habits import TrainingHabits, fijos_poco_hechos  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]

# Su tabla, tal como está en su prompt del 18-oct (FIJO y POSIBLE).
TABLA_DE_FERNANDO = {
    "Bici": {"Lunes": "fijo", "Miércoles": "fijo", "Sábado": "fijo"},
    "Nadar": {"Lunes": "fijo", "Martes": "fijo", "Jueves": "fijo", "Viernes": "fijo", "Domingo": "posible"},
    "Fuerza": {"Lunes": "fijo", "Martes": "fijo", "Jueves": "fijo", "Sábado": "posible"},
    "Correr": {"Martes": "fijo", "Jueves": "fijo", "Viernes": "posible", "Domingo": "fijo"},
}

# «Declarado vs hecho» de su log del 18-oct: semanas (de 4) en que hizo ese deporte ese día.
LO_QUE_HIZO = {
    "lunes": {"bici": 2, "fuerza": 2, "natación": 3},
    "martes": {"correr": 3, "fuerza": 2, "natación": 3},
    "miércoles": {"bici": 2},
    "jueves": {"correr": 1, "fuerza": 1},           # natación: 0
    "viernes": {"natación": 1},                    # correr (posible): 0
    "sábado": {"bici": 1, "fuerza": 1},
    "domingo": {"correr": 2, "natación": 1},
}


def _habitos(semanas_con_actividad=4):
    h = TrainingHabits(desde="2026-09-20", hasta="2026-10-18", semanas=4, actividades=45,
                       semanas_con_actividad=semanas_con_actividad)
    h.ritmo_semanal = {d: {"semanas": 4, "deportes": dict(v), "vigorosas": 0} for d, v in LO_QUE_HIZO.items()}
    return h


class LosSiOSiQueCasiNoHace(unittest.TestCase):

    def test_los_de_fernando(self):
        lista = fijos_poco_hechos(_habitos(), {"sport_days": TABLA_DE_FERNANDO})
        self.assertEqual(
            [(f["deporte"], f["dia"], f["veces"]) for f in lista],
            [("Correr", "Jueves", 1), ("Fuerza", "Jueves", 1), ("Nadar", "Jueves", 0),
             ("Nadar", "Viernes", 1), ("Bici", "Sábado", 1)],
        )
        self.assertTrue(all(f["semanas"] == 4 for f in lista))

    def test_un_puedo_no_se_avisa(self):
        """Correr el viernes lo hizo 0 de 4, pero ya es «Puedo»: ahí decide el coach."""
        lista = fijos_poco_hechos(_habitos(), {"sport_days": TABLA_DE_FERNANDO})
        self.assertNotIn(("Correr", "Viernes"), [(f["deporte"], f["dia"]) for f in lista])

    def test_dos_de_cuatro_no_es_casi_nunca(self):
        lista = fijos_poco_hechos(_habitos(), {"sport_days": TABLA_DE_FERNANDO})
        self.assertNotIn(("Correr", "Domingo"), [(f["deporte"], f["dia"]) for f in lista])

    def test_sin_historial_no_se_juzga(self):
        """Un Garmin recién conectado o un mes de lesión: con menos de 3 semanas con actividad, nada."""
        self.assertEqual(fijos_poco_hechos(_habitos(semanas_con_actividad=2), {"sport_days": TABLA_DE_FERNANDO}), [])

    def test_sin_tabla_nada(self):
        self.assertEqual(fijos_poco_hechos(_habitos(), {}), [])


class LosCables(unittest.TestCase):

    def test_viaja_en_el_resumen_y_la_pantalla_lo_lee(self):
        servidor = (RAIZ / "PaginaLocal/web_app/server.py").read_text(encoding="utf-8")
        self.assertIn('"fijos_poco_hechos": {"hasta": habits.hasta, "lista": poco_hechos}', servidor)
        util = (RAIZ / "Frontend/src/utils/fijos.js").read_text(encoding="utf-8")
        self.assertIn("summary?.fijos_poco_hechos?.lista", util.replace("resumen?.", "summary?."))
        plan = (RAIZ / "Frontend/src/views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn("fijosPocoHechos(plan.value?.summary, store.profile?.sport_days)", plan)
        perfil = (RAIZ / "Frontend/src/views/admin/ProfileView.vue").read_text(encoding="utf-8")
        self.assertIn("fijosPocoHechos(resumenDelPlan.value, formData.sportDays)", perfil)
        self.assertIn("const pasarAPuedo = () =>", perfil)


if __name__ == "__main__":
    unittest.main()
