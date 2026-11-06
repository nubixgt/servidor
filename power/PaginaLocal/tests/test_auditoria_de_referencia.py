"""Auditoría del macrociclo y la semana de referencia del atleta 2 (2026-09-13).

Trazas `reasoning/2026-09-13T21-04-01` (macrociclo) y `2026-09-13T21-18-38`
(semana). Salieron a la primera -- sin reparaciones, un solo codificador --, y
aun así:

1. **Amplificación de lesión.** El atleta puntuó psoas 1/10 y rodilla 1/10. La
   semana nombró las dos en el `adaptation` de las diez sesiones; el macrociclo
   escribió una `limitacion` médica de un mes; el informe lo llamó "el
   principal riesgo para tu carrera".
2. **El reloj contradecía al paso.** Viernes, a dos días del 5K: "4×30" a ritmo
   5K controlado ~5:12/km" con un objetivo de 4:51-5:00/km.
3. **El afinamiento seguía cargado.** 8 sesiones de lunes a sábado, ~20 % menos
   de volumen; la nota decía "mantén casi toda la frecuencia", y el modelo
   acortó todo sin quitar nada accesorio. La evidencia para una carrera a tope
   es 40-60 % menos volumen con la intensidad intacta.
4. **Dos macrociclos seguidos con tapers de 3 y 4 semanas** para una 10K.
5. **El informe decía "Atleta: Usuario"** y la semana escribió `acwr_uncoupled`.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.prompt_components import get_language_directive  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import _ritmo_como_dice_el_paso  # noqa: E402
from services.ai.niggle_constraints import render_niggle_constraints  # noqa: E402

PSOAS_Y_RODILLA = [{"source": "check-in", "location": "Psoas y Colateral medial rodilla derecha",
                    "pain": 1, "detail": None}]


class UnaMolestiaLeveNoSeAmplifica(unittest.TestCase):
    def test_dolor_1_de_10_solo_donde_se_carga(self):
        bloque = render_niggle_constraints(PSOAS_Y_RODILLA)
        self.assertIn("Es una molestia leve", bloque)
        self.assertIn("no en todas", bloque)
        self.assertNotIn("Identifica **qué días de esta semana cargan esa estructura**", bloque)

    def test_un_dolor_alto_sigue_como_antes(self):
        bloque = render_niggle_constraints([{**PSOAS_Y_RODILLA[0], "pain": 5}])
        self.assertIn("Identifica **qué días de esta semana cargan esa estructura**", bloque)
        self.assertNotIn("Es una molestia leve", bloque)

    def test_sin_numero_no_se_da_por_leve(self):
        bloque = render_niggle_constraints([{**PSOAS_Y_RODILLA[0], "pain": None}])
        self.assertNotIn("Es una molestia leve", bloque)

    def test_el_macrociclo_no_hace_limitacion_de_un_1_de_10(self):
        prompt = importlib.import_module("services.ai.langgraph.nodes.season_planner_node").SEASON_PLANNER_USER_PROMPT
        self.assertIn("never a `limitacion`", prompt)

    def test_el_informe_no_la_dramatiza(self):
        from services.ai.langgraph.nodes.synthesis_node import GUIA_DE_ESTILO_DEL_INFORME
        self.assertIn("se menciona una vez y como lo que es", GUIA_DE_ESTILO_DEL_INFORME)
        self.assertIn("No afirmes mecanismos como certezas", GUIA_DE_ESTILO_DEL_INFORME)


def _semana(pasos, deporte="run"):
    dia = WorkoutDay(date="2026-09-18", day_name_es="Viernes", is_rest=False, sport=deporte, focus="Activación",
                     purpose="p", adaptation="a", intensity_class="neuromuscular", discipline="carrera",
                     total_duration_minutes=30.0, steps=pasos)
    return WeeklyPlanPayload(athlete_name="Jose", start_date="2026-09-13", zones_table="",
                             weeks=[WeekBlock(week_number=1, week_rationale="", days=[dia])])


def _paso(descripcion, bajo, alto):
    return WorkoutStep(step_order=1, step_type="interval", duration_type="time", duration_value=30,
                       target_type=TargetType.PACE, target_low=bajo, target_high=alto, description=descripcion)


class ElRelojDiceLoQueDiceElPaso(unittest.TestCase):
    def test_el_caso_real_del_viernes(self):
        plan = _semana([_paso("30\" a ritmo 5K controlado ~5:12/km", 291, 300)])
        plan, cambios = _ritmo_como_dice_el_paso(plan)
        paso = plan.weeks[0].days[0].steps[0]
        self.assertEqual((paso.target_low, paso.target_high), (306.0, 318.0))
        self.assertEqual(len(cambios), 1)

    def test_un_rango_coherente_no_se_toca(self):
        plan, cambios = _ritmo_como_dice_el_paso(_semana([_paso("2' a 5:05–5:18/km", 305, 318)]))
        self.assertEqual(cambios, [])

    def test_dos_ritmos_en_el_texto_no_tienen_una_lectura(self):
        plan, cambios = _ritmo_como_dice_el_paso(
            _semana([_paso("km 1 ~5:20/km, luego asienta en 5:12/km", 291, 312)]))
        self.assertEqual(cambios, [])

    def test_fuera_de_carrera_no_se_toca(self):
        plan, cambios = _ritmo_como_dice_el_paso(_semana([_paso("~5:12/km", 291, 300)], deporte="bike"))
        self.assertEqual(cambios, [])


class ElAfinamientoQuitaLoAccesorio(unittest.TestCase):
    def test_la_nota_no_pide_conservar_toda_la_frecuencia(self):
        nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")
        nota = nodo._NOTA_DE_AFINAMIENTO
        self.assertNotIn("casi toda la frecuencia", nota)
        self.assertIn("La frecuencia que hay que conservar es la del deporte de la carrera", nota)
        self.assertIn("40-60 %", nota)
        self.assertIn("7-10 días para un 5K o un 10K", nota)

    def test_el_macrociclo_dimensiona_el_taper_por_distancia(self):
        prompt = importlib.import_module("services.ai.langgraph.nodes.season_planner_node").SEASON_PLANNER_USER_PROMPT
        self.assertIn("about a week for a 5K-10K", prompt)


class LoQueLeeElAtletaTieneNombreYSinIdentificadores(unittest.TestCase):
    def test_el_informe_recibe_el_nombre(self):
        from services.ai.langgraph.nodes.synthesis_node import construir_mensaje_de_usuario
        mensaje = construir_mensaje_de_usuario({
            "athlete_name": "Jose Ines Castillo", "competitions": [], "current_date": {"date": "2026-09-13"},
            "metrics_outputs": None, "activity_outputs": None, "physiology_outputs": None,
        })
        self.assertIn("Athlete: Jose Ines Castillo", mensaje)

    def test_la_regla_de_idioma_nombra_los_identificadores_de_campo(self):
        self.assertIn("acwr_uncoupled", get_language_directive())


if __name__ == "__main__":
    unittest.main()
