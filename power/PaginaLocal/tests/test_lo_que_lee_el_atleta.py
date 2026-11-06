"""Auditoría del macrociclo y la semana reales del atleta 2 (2026-09-13).

Trazas `reasoning/2026-09-13T14-57-06` (macrociclo) y `2026-09-13T15-08-07`
(semana). Cuatro cosas que ningún validador marcaba y que llegaban al atleta o
a la memoria del macrociclo:

1. El expediente guardó como CAÍDAS las dos sesiones de calidad de una semana
   que se acababa de escribir ("2026-09-16 VO2max", "2026-09-20 5K Monterrico"),
   y el macrociclo lee esa columna como "se cayeron".
2. El informe mandaba "carrera suave de 25-30' el miércoles" y el plan de esa
   misma semana, series de VO2max el miércoles. Presentaba el ACWR como
   "Riesgo de lesión" con un "óptimo entre 0.8 y 1.3", y escribía `chronic_ewma`.
3. La semana le escribió al atleta "sin perseguir la etiqueta de Garmin
   AEROBIC_HIGH_SHORTAGE" en el propósito de una bici suave: el bloque de idioma
   compartido decía que los identificadores de Garmin "se citan tal cual".
4. El experto de actividad llamó "tempo/umbral" a una pista a 7:11/km sin
   conocer las bandas del atleta (tempo 5:46-6:14, fácil 6:49-7:38).

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import shutil
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import track_record as tr  # noqa: E402
from services.ai.langgraph.nodes.prompt_components import get_language_directive  # noqa: E402
from services.ai.langgraph.nodes.synthesis_node import GUIA_DE_ESTILO_DEL_INFORME  # noqa: E402


def _dia(fecha, foco, clase):
    return {"date": fecha, "is_rest": False, "sport": "run", "discipline": "carrera",
            "focus": foco, "intensity_class": clase, "total_duration_minutes": 50}


SEMANA_RECIEN_ESCRITA = {"week_number": 1, "days": [
    _dia("2026-09-13", "Base + strides", "easy"),
    _dia("2026-09-16", "VO2max 2'", "vo2max"),
    _dia("2026-09-20", "5k Monterrico", "race_day"),
]}


class LaCalidadQueNoTocabaTodaviaNoSeCae(unittest.TestCase):
    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def test_una_semana_recien_prescrita_no_anota_caidas(self):
        entrada = tr.summarise_week(SEMANA_RECIEN_ESCRITA, None)
        self.assertEqual(entrada["quality_missed"], [])

    def test_los_dias_que_la_adherencia_aun_no_mira_no_se_caen(self):
        """A mitad de semana la adherencia sólo trae los días ya pasados."""
        from services.ai.adherence import AdherenceReport, SessionAdherence

        informe = AdherenceReport()
        informe.entries.append(SessionAdherence(
            date="2026-09-13", session_slot="unica", sport="run", focus="Base + strides",
            status="completed", planned_minutes=45, actual_minutes=46))
        entrada = tr.summarise_week(SEMANA_RECIEN_ESCRITA, informe.to_state_dict())
        self.assertEqual(entrada["quality_missed"], [])

    def test_lo_evaluado_y_no_hecho_se_sigue_cayendo(self):
        from services.ai.adherence import AdherenceReport, SessionAdherence

        informe = AdherenceReport()
        informe.entries.append(SessionAdherence(
            date="2026-09-16", session_slot="unica", sport="run", focus="VO2max 2'", status="missed"))
        entrada = tr.summarise_week(SEMANA_RECIEN_ESCRITA, informe.to_state_dict())
        self.assertEqual(entrada["quality_missed"], ["2026-09-16 VO2max 2'"])

    def test_el_macrociclo_no_ve_como_incumplida_la_semana_en_curso(self):
        cerrada = {"from": "2026-09-06", "to": "2026-09-12", "week_number": 0, "prescribed_hours": 7.0,
                   "prescribed_sessions": 9, "quality_prescribed": 2, "quality_done": 2, "quality_missed": [],
                   "completed_sessions": 8, "executed_hours": 6.7}
        abierta = tr.summarise_week(SEMANA_RECIEN_ESCRITA, None)
        tr._append(self.dir, cerrada)
        tr._append(self.dir, abierta)
        bloque = tr.render_track_record(self.dir)
        self.assertIn("realizadas: **2** (100%)", bloque)
        self.assertIn("| 2026-09-13 | 2.5h / 3 ses. | — | sin evaluar | — |", bloque)


class ElInformeNoContradiceAlPlanNiAlarma(unittest.TestCase):
    """Revisado con ojo de entrenador: prohibir no era arreglar.

    - "Riesgo de lesión" y "óptimo 0.8-1.3" no los escribió ningún experto
      (cero apariciones en sus salidas): los puso la síntesis de su propia
      cosecha, porque era el único nodo que hablaba de carga sin el bloque de
      Garmin, que es el que dice qué es el ACWR y qué cifra ve el atleta. La
      causa era un cable, no el tono.
    - La receta del miércoles la empujaba la propia guía: "las recomendaciones
      [...] las puede hacer esta semana", en un informe que se relee semanas
      después.
    - "Sin alarmas" habría callado también un aviso de verdad. Lo que se pide
      es proporción: la fuerza de las palabras es la del dato.
    """

    def test_las_recomendaciones_no_son_de_esta_semana(self):
        self.assertNotIn("las puede hacer esta semana", GUIA_DE_ESTILO_DEL_INFORME)
        self.assertIn("No prescriben sesiones, días, duraciones ni", GUIA_DE_ESTILO_DEL_INFORME)

    def test_proporcion_no_silencio(self):
        self.assertIn("La fuerza de las palabras es la del dato", GUIA_DE_ESTILO_DEL_INFORME)
        self.assertIn("Ni exagerar ni callar", GUIA_DE_ESTILO_DEL_INFORME)
        self.assertNotIn("Sin alarmas", GUIA_DE_ESTILO_DEL_INFORME)

    def test_la_sintesis_recibe_el_bloque_de_carga_de_garmin(self):
        from services.ai.langgraph.nodes.synthesis_node import construir_mensaje_de_usuario

        estado = {"athlete_name": "A", "competitions": [], "current_date": {"date": "2026-09-13"},
                  "metrics_outputs": None, "activity_outputs": None, "physiology_outputs": None,
                  "availability_constraints": {"load_state": {"block": "ACWR de Garmin 1.00 BLOQUE"}}}
        self.assertIn("ACWR de Garmin 1.00 BLOQUE", construir_mensaje_de_usuario(estado))
        sin_bloque = {k: v for k, v in estado.items() if k != "availability_constraints"}
        self.assertNotIn("{load_state}", construir_mensaje_de_usuario(sin_bloque))

    def test_las_cifras_son_las_de_su_reloj(self):
        self.assertIn("Cita las cifras que puede encontrar en su reloj", GUIA_DE_ESTILO_DEL_INFORME)


class ElCoachSabeQueDiaEs(unittest.TestCase):
    """Macrociclo del atleta 2, 2026-09-13: el coach preguntó "El 5K de Monterrico
    es el LUNES 20/09 ... coincide con tu lunes de fuerza fijo". El 20 era
    domingo, y el nodo de preguntas no recibía ni la fecha de hoy ni un solo día
    de la semana."""

    ESTADO = {"current_date": {"date": "2026-09-13"}, "competitions": [
        {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B"},
        {"name": "10K", "date": "2027-04-20", "priority": "A"},
        {"name": "Vieja", "date": "2026-08-01", "priority": "C"},
    ]}

    def test_hoy_y_cada_carrera_llevan_su_dia(self):
        from services.ai import preguntas_del_coach as pc

        texto = pc.calendario(self.ESTADO)
        self.assertIn("domingo 2026-09-13", texto)
        self.assertIn("5k Monterrico: domingo 2026-09-20", texto)
        self.assertIn("dentro de 7 días", texto)
        self.assertIn("10K: martes 2027-04-20", texto)
        self.assertNotIn("Vieja", texto)

    def test_el_prompt_tiene_el_hueco(self):
        from services.ai import preguntas_del_coach as pc

        self.assertIn("{calendario}", pc.USUARIO)
        self.assertEqual(pc.calendario({}), "")


class LasEtiquetasDeGarminNoLleganAlAtleta(unittest.TestCase):
    def test_el_bloque_de_idioma_lo_dice_a_todos_los_nodos(self):
        contexto = get_language_directive()
        self.assertIn("en el texto que lee el atleta", contexto)
        self.assertIn("AEROBIC_HIGH_SHORTAGE", contexto)
        self.assertNotIn("Se citan tal cual", contexto)


class ElExpertoDeActividadLeeConSusRitmos(unittest.TestCase):
    PRESCRIPCION = {
        "hr_zones_by_sport": {"running": {"1": [122, 139], "2": [140, 152], "3": [153, 166],
                                          "4": [167, 179], "5": [180, None]}},
        "pace_bands": {"umbral_tempo": [346, 374], "facil": [409, 458]},
    }

    def setUp(self):
        self.nodo = importlib.import_module("services.ai.langgraph.nodes.activity_expert_node")

    def test_recibe_sus_bandas(self):
        bloque = self.nodo._bloque_de_zonas({"prescription": self.PRESCRIPCION})
        self.assertIn("5:46-6:14/km", bloque)
        self.assertIn("6:49-7:38/km", bloque)
        self.assertIn("Z4 (167-179 ppm)", bloque)
        self.assertIn("no elijas una de las dos en silencio", bloque)

    def test_el_prompt_tiene_el_hueco(self):
        self.assertIn("{athlete_zones}", self.nodo.ACTIVITY_EXPERT_USER_PROMPT)

    def test_sin_prescripcion_cero_tokens(self):
        self.assertEqual(self.nodo._bloque_de_zonas({}), "")


if __name__ == "__main__":
    unittest.main()
