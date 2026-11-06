"""Cuánto pasado se le enseña al planificador, y cuánto se le deja al reloj.

Dos decisiones distintas que se tomaron el mismo día por el mismo motivo: el
pasado se acumulaba solo, sin que nadie hubiera decidido cuánto valía.

## El contexto del planificador

`_format_previous_plan` recorría TODAS las semanas guardadas -- hasta
`MAX_WEEKS_KEPT`, que son 12. Medido: 2.966 tokens por generación contra los
433 de una sola, ~230 por cada semana extra.

El argumento para recortarlo no es el precio, es que las otras once no aportan
nada que no llegue ya por un canal mejor y más fiable:

  - la FASE, el volumen objetivo y el énfasis, del plan de temporada;
  - lo que el atleta REALMENTE hizo, del bloque de adherencia -- medido contra
    Garmin, no contra lo que se le prescribió;
  - la memoria de más de siete días, de `track_record.jsonl`, que existe
    exactamente para eso.

Lo único que la semana anterior aporta y nadie más da son los ANCLAJES: qué día
es el de calidad, cuál la tirada larga, qué dobles ya hace este atleta. Para eso
basta la última.

## La limpieza del reloj

`DIAS_QUE_SE_CONSERVAN` eran 14 "para comparar planificado contra hecho", pero
esa comparación no se hace mirando el reloj: la hace `adherence.py` contra las
ACTIVIDADES. Lo único que dejaban los siete días de más era la semana pasada
conviviendo con la vigente en el calendario del atleta.

Correr con:  python -m unittest discover -s tests -v
"""

import copy
import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    SEMANAS_DE_CONTEXTO,
    _format_previous_plan,
)
from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload  # noqa: E402
from services.garmin import upload_ledger  # noqa: E402

UN_DIA = {
    "date": "2026-09-15", "day_name_es": "Martes", "is_rest": False, "sport": "swim",
    "focus": "CSS suave", "purpose": ".", "adaptation": ".", "intensity_class": "easy",
    "discipline": "natacion", "total_duration_minutes": 45.0, "steps": [],
}


def _plan(n_semanas: int) -> WeeklyPlanPayload:
    semanas = []
    for i in range(n_semanas):
        dia = copy.deepcopy(UN_DIA)
        dia["focus"] = f"ancla semana {i + 1}"
        semanas.append({"week_number": i + 1, "week_rationale": "", "days": [dia]})
    return WeeklyPlanPayload(athlete_name="T", start_date="2026-09-10",
                             zones_table="N/A", weeks=semanas)


class ElContextoEsUnaSemana(unittest.TestCase):
    def test_solo_se_enseña_una(self):
        self.assertEqual(SEMANAS_DE_CONTEXTO, 1)

    def test_con_doce_guardadas_solo_entra_la_ultima(self):
        """El caso real: `MAX_WEEKS_KEPT` son 12 y todas iban al prompt."""
        texto = _format_previous_plan(_plan(12), "2026-09-17")
        self.assertIn("Semana 12:", texto)
        # Por encabezado exacto: "ancla semana 1" es subcadena de "ancla
        # semana 12", y un `assertNotIn` sobre eso falla contra codigo correcto.
        for vieja in range(1, 12):
            with self.subTest(semana=vieja):
                self.assertNotIn(f"Semana {vieja}:", texto)

    def test_el_bloque_no_crece_con_las_semanas_guardadas(self):
        """Doce semanas costaban 2.966 tokens; una cuesta 433 y ya no depende
        de cuánto lleve el atleta usando la app."""
        uno = len(_format_previous_plan(_plan(1), "2026-09-17"))
        doce = len(_format_previous_plan(_plan(12), "2026-09-17"))
        self.assertLess(abs(doce - uno), 40, "el bloque sigue creciendo con el historial")

    def test_un_atleta_nuevo_no_recibe_bloque(self):
        self.assertEqual(_format_previous_plan(None, "2026-09-17"), "")


class LaLimpiezaDelReloj(unittest.TestCase):
    def test_se_conserva_una_semana_y_no_dos(self):
        self.assertEqual(upload_ledger.DIAS_QUE_SE_CONSERVAN, 7)

    def test_la_semana_pasada_se_retira(self):
        """Lo que el atleta veía: dos lunes en el calendario del reloj."""
        registro = [{"date": "2026-09-01", "workout_id": 1},
                    {"date": "2026-09-12", "workout_id": 2}]
        viejas = upload_ledger.caducadas(registro, hoy=date(2026, 9, 15))
        self.assertEqual([e["workout_id"] for e in viejas], [1])

    def test_una_sesion_de_hace_exactamente_siete_dias_sobrevive(self):
        """El margen es a propósito: quien mueve el entreno del domingo al
        lunes lo encuentra donde lo dejó."""
        registro = [{"date": "2026-09-08", "workout_id": 1}]
        self.assertEqual(upload_ledger.caducadas(registro, hoy=date(2026, 9, 15)), [])

    def test_una_fecha_ilegible_no_se_borra(self):
        """Ante la duda no se toca el reloj del atleta."""
        registro = [{"date": "no-es-una-fecha", "workout_id": 1}]
        self.assertEqual(upload_ledger.caducadas(registro, hoy=date(2026, 9, 15)), [])


if __name__ == "__main__":
    unittest.main()
